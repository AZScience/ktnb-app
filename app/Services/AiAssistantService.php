<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiAssistantService
{
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-flash-8b',
        'gemini-2.0-flash-lite',
    ];

    public function __construct(
        private SystemParameterService $parameters,
        private GoogleSheetService $sheets,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function ask(string $question, string $searchMode = 'faq', array $history = []): string
    {
        $params = $this->parameters->all();

        if ($searchMode === 'faq') {
            return $this->askFaq($question, $params);
        }

        return $this->askGeneral($question, $history, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function askFaq(string $question, array $params): string
    {
        $tabName = (string) ($params['faqSheetTabName'] ?? 'FAQ');
        $sheetConfig = $this->sheets->getConfig();

        if ($sheetConfig['sheet_id'] === '' || $sheetConfig['email'] === '' || $sheetConfig['private_key'] === '') {
            return 'Google Sheet FAQ chưa được cấu hình. Vui lòng kiểm tra Tham số hệ thống > Google Sheets (googleSheetId, googleServiceAccountEmail, googlePrivateKey, faqSheetTabName).';
        }

        try {
            $faqAnswer = $this->sheets->findFaqAnswer($question, $tabName);
            if ($faqAnswer !== null && trim($faqAnswer) !== '') {
                return trim($faqAnswer);
            }

            $entries = $this->sheets->loadFaqEntries($tabName);
        } catch (\Throwable $e) {
            return 'Không thể tra cứu FAQ: '.$e->getMessage();
        }

        if ($entries === []) {
            return "Không có dữ liệu FAQ trên tab \"{$tabName}\". Vui lòng kiểm tra Google Sheet và tên tab trong Tham số hệ thống.";
        }

        return 'Không tìm thấy câu trả lời phù hợp trong danh sách FAQ. Bạn có thể thử diễn đạt lại câu hỏi hoặc chuyển sang tab Kiến thức chung.';
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $params
     */
    private function askGeneral(string $question, array $history, array $params): string
    {
        $apiKey = trim((string) ($params['aiApiKey'] ?? ''));
        if ($apiKey === '') {
            return 'API Key cho AI chưa được cấu hình. Vui lòng kiểm tra Tham số hệ thống > AI.';
        }

        $systemPrompt = trim((string) ($params['aiSystemPrompt'] ?? ''))
            ?: 'Bạn là Trợ lý ảo thông minh. Hãy trả lời câu hỏi của người dùng một cách lịch sự và chuyên nghiệp.';

        $contents = [];
        foreach ($history as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => (string) $message['content']]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $question]],
        ];

        $requested = trim((string) ($params['aiModel'] ?? 'gemini-1.5-flash'));
        $requested = preg_replace('/^models\//', '', $requested) ?? $requested;
        $models = array_values(array_unique(array_filter([$requested, ...self::FALLBACK_MODELS])));

        $temperature = (float) ($params['aiTemperature'] ?? 0.7);
        $lastError = '';

        foreach ($models as $model) {
            try {
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => $temperature,
                            'maxOutputTokens' => 2048,
                        ],
                    ],
                );

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }

                $lastError = $response->json('error.message') ?? $response->body();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return 'Lỗi kết nối AI: '.($lastError ?: 'Tất cả các model đều không phản hồi.').' Vui lòng kiểm tra API Key trong Tham số hệ thống > AI.';
    }

    /**
     * @return array<string, string>
     */
    public function extractAssetReception(string $base64Image, string $mimeType): array
    {
        $params = $this->parameters->all();
        $apiKey = trim((string) ($params['aiApiKey'] ?? ''));
        if ($apiKey === '') {
            throw new \Exception('API Key cho AI chưa được cấu hình.');
        }

        $systemPrompt = 'Bạn là trợ lý AI chuyên trích xuất dữ liệu từ hình ảnh phiếu tiếp nhận tài sản/đồ vật. Hãy đọc ảnh và trích xuất các thông tin sau, trả về định dạng JSON thuần túy (không bọc trong markdown block). Các trường: giver_name, giver_employee_code, giver_id, giver_class, giver_unit, giver_phone, content, asset_state, reception_day, reception_month, reception_year. Nếu không thấy thông tin, để chuỗi rỗng.';

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image,
                        ]
                    ]
                ]
            ]
        ];

        $requested = trim((string) ($params['aiModel'] ?? 'gemini-1.5-flash'));
        $requested = preg_replace('/^models\//', '', $requested) ?? $requested;
        $models = array_values(array_unique(array_filter([$requested, ...self::FALLBACK_MODELS])));

        $lastError = '';

        foreach ($models as $model) {
            try {
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => $contents,
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.1,
                        ],
                    ],
                );

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        $decoded = json_decode(trim($text), true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }
                $lastError = $response->json('error.message') ?? $response->body();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception('Lỗi trích xuất: ' . $lastError);
    }

    /**
     * @return array<string, mixed>
     */
    public function extractServiceRequest(string $base64Image, string $mimeType): array
    {
        $params = $this->parameters->all();
        $apiKey = trim((string) ($params['aiApiKey'] ?? ''));
        if ($apiKey === '') {
            throw new \Exception('API Key cho AI chưa được cấu hình.');
        }

        $systemPrompt = 'Bạn là trợ lý AI chuyên trích xuất dữ liệu từ hình ảnh phiếu thông tin hỗ trợ giải quyết / phiếu yêu cầu. Hãy đọc ảnh và trích xuất các thông tin sau, trả về định dạng JSON thuần túy (không bọc trong markdown block). 
Các trường: 
- ticket_number (số phiếu yêu cầu, ví dụ "04")
- request_type (loại phiếu, ví dụ "Phiếu yêu cầu" hoặc "Phiếu tiếp nhận")
- student_name (họ và tên)
- student_id (MSSV)
- class (Lớp)
- department (Khoa/ đơn vị)
- phone (Số điện thoại)
- content (Nội dung yêu cầu hỗ trợ giải quyết)
- is_processed_immediately (Kết quả giải quyết -> true nếu "Đã hỗ trợ xử lý ngay" được tick, ngược lại false)
- appointment_date (Hẹn trả lời ngày -> dạng dd/mm/yyyy, nếu trống thì chuỗi rỗng)
- note (Khác -> nội dung chú thích khác)
- reception_day, reception_month, reception_year (Ngày người yêu cầu/ người tiếp nhận ký)
- resolution_day, resolution_month, resolution_year (Ngày cán bộ giải quyết ký)
Nếu không thấy thông tin, để chuỗi rỗng đối với string, hoặc false đối với boolean.';

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image,
                        ]
                    ]
                ]
            ]
        ];

        $requested = trim((string) ($params['aiModel'] ?? 'gemini-1.5-flash'));
        $requested = preg_replace('/^models\//', '', $requested) ?? $requested;
        $models = array_values(array_unique(array_filter([$requested, ...self::FALLBACK_MODELS])));

        $lastError = '';

        foreach ($models as $model) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => $contents,
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.1,
                        ],
                    ],
                );

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        $decoded = json_decode(trim($text), true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }
                $lastError = $response->json('error.message') ?? $response->body();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \Exception('Lỗi trích xuất: ' . $lastError);
    }
    public function extractPetition(string $base64Image, string $mimeType): array
    {
        $params = $this->parameters->all();
        $apiKey = trim((string) ($params['aiApiKey'] ?? ''));
        if ($apiKey === '') {
            throw new \Exception('API Key cho AI chưa được cấu hình.');
        }

        $systemPrompt = 'Bạn là trợ lý AI chuyên trích xuất dữ liệu từ hình ảnh "Phiếu thông tin hỗ trợ giải quyết" hoặc "Phiếu tiếp nhận và xử lý đơn thư". Hãy đọc ảnh và trích xuất các thông tin sau, trả về định dạng JSON thuần túy (không bọc trong markdown block). Các trường: citizen_name, citizen_id, citizen_class, citizen_unit, citizen_phone, summary. Nếu không thấy thông tin, để chuỗi rỗng.';

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image,
                        ]
                    ]
                ]
            ]
        ];

        $modelParams = $this->buildModelParams($params);
        $model = $modelParams['model'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = \Illuminate\Support\Facades\Http::withOptions([
            'verify' => false,
        ])->post($url, [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'citizen_name' => ['type' => 'STRING', 'description' => 'Họ và tên người yêu cầu / công dân'],
                        'citizen_id' => ['type' => 'STRING', 'description' => 'Mã số sinh viên (MSSV) hoặc CCCD'],
                        'citizen_class' => ['type' => 'STRING', 'description' => 'Lớp của sinh viên (nếu có)'],
                        'citizen_unit' => ['type' => 'STRING', 'description' => 'Khoa / Đơn vị (nếu có)'],
                        'citizen_phone' => ['type' => 'STRING', 'description' => 'Số điện thoại liên hệ'],
                        'summary' => ['type' => 'STRING', 'description' => 'Nội dung yêu cầu hỗ trợ giải quyết / Tóm tắt vụ việc'],
                    ],
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Lỗi khi gọi API AI: ' . $response->body());
        }

        $responseData = $response->json();
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        $text = trim($text);
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
            if (str_ends_with($text, '```')) {
                $text = substr($text, 0, -3);
            }
        }
        $text = trim($text);

        $parsed = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Lỗi parse JSON từ AI: ' . json_last_error_msg());
        }

        return $parsed;
    }

}
