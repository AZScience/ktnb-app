<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ViolationCardExtractionService
{
    private const GEMINI_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
    ];

    public function __construct(
        private SystemParameterService $parameters,
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     full_name: string,
     *     student_id: string,
     *     citizen_id: string,
     *     class: string,
     *     department: string,
     *     message: string
     * }
     */
    public function extract(string $imageData): array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return $this->emptyResult('API Key cho AI chưa được cấu hình. Vui lòng kiểm tra Tham số hệ thống (khóa ai_api_key).');
        }

        $imagePart = $this->toInlineImagePart($imageData);
        if ($imagePart === null) {
            return $this->emptyResult('Định dạng ảnh không hợp lệ.');
        }

        $prompt = <<<'PROMPT'
Bạn là chuyên gia đọc giấy tờ sinh viên và CCCD Việt Nam từ ảnh.
Hãy đọc trực tiếp các trường in trên thẻ/ảnh giấy tờ (không cần quét QR hay barcode).

Trả về JSON duy nhất với cấu trúc:
{
  "full_name": "họ tên đầy đủ",
  "student_id": "mã số sinh viên nếu có",
  "citizen_id": "số CCCD/CMND nếu có",
  "class": "lớp học nếu có",
  "department": "khoa/ngành nếu có"
}

Quy tắc:
- Chỉ trả JSON, không giải thích thêm.
- Trường không đọc được để chuỗi rỗng "".
- citizen_id chỉ gồm chữ số (12 số với CCCD).
- student_id giữ nguyên định dạng trên thẻ.
PROMPT;

        foreach ($this->geminiModels() as $model) {
            try {
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [[
                            'parts' => [
                                ['text' => $prompt],
                                $imagePart,
                            ],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'maxOutputTokens' => 1024,
                        ],
                    ],
                );

                if (! $response->successful()) {
                    continue;
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if (! is_string($text) || trim($text) === '') {
                    continue;
                }

                if (! preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                    continue;
                }

                $parsed = json_decode($matches[0], true);
                if (! is_array($parsed)) {
                    continue;
                }

                $fullName = trim((string) ($parsed['full_name'] ?? ''));
                $studentId = trim((string) ($parsed['student_id'] ?? ''));
                $citizenId = preg_replace('/\D+/', '', (string) ($parsed['citizen_id'] ?? '')) ?: '';
                $class = trim((string) ($parsed['class'] ?? ''));
                $department = trim((string) ($parsed['department'] ?? ''));

                if ($fullName === '' && $studentId === '' && $citizenId === '') {
                    return $this->emptyResult('Không trích xuất được thông tin từ ảnh. Hãy chụp lại rõ hơn.');
                }

                return [
                    'success' => true,
                    'full_name' => $fullName,
                    'student_id' => $studentId,
                    'citizen_id' => $citizenId,
                    'class' => $class,
                    'department' => $department,
                    'message' => 'Đã trích xuất thông tin từ ảnh giấy tờ.',
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->emptyResult('Không thể trích xuất thông tin từ ảnh. Vui lòng thử lại.');
    }

    /** @return array{success: bool, full_name: string, student_id: string, citizen_id: string, class: string, department: string, message: string} */
    private function emptyResult(string $message): array
    {
        return [
            'success' => false,
            'full_name' => '',
            'student_id' => '',
            'citizen_id' => '',
            'class' => '',
            'department' => '',
            'message' => $message,
        ];
    }

    private function apiKey(): string
    {
        return trim((string) (
            $this->parameters->get('aiApiKey')
            ?: config('services.google.ai_key')
            ?: env('GOOGLE_GENAI_API_KEY', '')
        ));
    }

    /** @return list<string> */
    private function geminiModels(): array
    {
        $requested = trim((string) ($this->parameters->get('aiModel') ?: 'gemini-2.0-flash'));

        return array_values(array_unique(array_filter([$requested, ...self::GEMINI_MODELS])));
    }

    private function toInlineImagePart(string $value): ?array
    {
        $value = trim($value);

        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $value, $matches)) {
            return [
                'inline_data' => [
                    'mime_type' => $matches[1],
                    'data' => $matches[2],
                ],
            ];
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            try {
                $response = Http::timeout(20)->get($value);
                if (! $response->successful()) {
                    return null;
                }
                $mime = $response->header('Content-Type') ?: 'image/jpeg';
                if (! Str::startsWith($mime, 'image/')) {
                    $mime = 'image/jpeg';
                }

                return [
                    'inline_data' => [
                        'mime_type' => explode(';', $mime)[0],
                        'data' => base64_encode($response->body()),
                    ],
                ];
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
