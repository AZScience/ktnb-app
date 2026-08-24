<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Trích xuất nội dung chữ viết (tay hoặc in) từ ảnh phiếu ghi nhận
 * để điền vào trường "Chi tiết việc phát sinh".
 */
class IncidentDetailExtractionService
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
     * @return array{success: bool, text: string, message: string}
     */
    public function extract(string $imageData, string $module = 'homeroom'): array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return $this->result(false, '', 'API Key cho AI chưa được cấu hình. Vui lòng kiểm tra Tham số hệ thống (khóa ai_api_key).');
        }

        $imagePart = $this->toInlineImagePart($imageData);
        if ($imagePart === null) {
            return $this->result(false, '', 'Định dạng ảnh không hợp lệ.');
        }

        if ($module === 'exams') {
            $prompt = <<<'PROMPT'
Bạn là chuyên gia đọc phiếu ghi chép tiếng Việt (chữ viết tay hoặc chữ in) từ ảnh.
Ảnh là "Phiếu ghi nhận tình hình phòng thi" do giám thị / giám sát ghi.

Nhiệm vụ: CHỈ chép lại phần được ghi ở các mục:
1. "Họ, tên và MSSV vi phạm và hình thức xử lý:"
2. "Tình hình buổi thi:"

Trả về JSON duy nhất với cấu trúc:
{
  "text": "nội dung được ghi"
}

Quy tắc:
- Chỉ trả JSON, không giải thích thêm.
- Nếu có thông tin ở cả hai mục, hãy gộp lại thành 1 chuỗi rõ ràng (VD: Vi phạm: Nguyễn Văn A - 12345 (Cảnh cáo); Tình hình: Bình thường).
- Không chép lại các mục khác (tiêu đề, quốc hiệu, ngày, lớp, sỉ số, họ tên giám thị...).
- Nếu một mục trống, bỏ qua nội dung mục đó.
- Không tìm thấy các mục này hoặc mục bị bỏ trống thì để chuỗi rỗng "".
PROMPT;
        } else {
            $prompt = <<<'PROMPT'
Bạn là chuyên gia đọc phiếu ghi chép tiếng Việt (chữ viết tay hoặc chữ in) từ ảnh.
Ảnh là phiếu sinh hoạt lớp do cố vấn học tập (CVHT) / giảng viên ghi.

Nhiệm vụ: CHỈ chép lại phần được ghi ở mục "Nội dung sinh hoạt (CVHT)"
(tên mục có thể ghi hơi khác: "Nội dung sinh hoạt", "Nội dung sinh hoạt CVHT"...).

Trả về JSON duy nhất với cấu trúc:
{
  "text": "nội dung mục Nội dung sinh hoạt (CVHT)"
}

Quy tắc:
- Chỉ trả JSON, không giải thích thêm.
- CHỈ lấy chữ ghi trong mục "Nội dung sinh hoạt (CVHT)" — KHÔNG lấy các mục khác
  (tiêu đề phiếu, quốc hiệu, ngày, lớp, sĩ số, họ tên, chữ ký, ý kiến khác...).
- Không chép lại chính dòng tiêu đề mục, chỉ lấy nội dung được ghi bên trong mục.
- Giữ đúng chính tả tiếng Việt có dấu; sửa lỗi nhận dạng hiển nhiên cho câu mạch lạc.
- Các dòng/ý khác nhau ngăn cách bằng dấu "; ".
- Không tìm thấy mục này hoặc mục bị bỏ trống thì để chuỗi rỗng "".
PROMPT;
        }

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
                            'maxOutputTokens' => 2048,
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

                $content = trim((string) ($parsed['text'] ?? ''));
                if ($content === '') {
                    return $this->result(false, '', 'Không tìm thấy nội dung ở mục "Nội dung sinh hoạt (CVHT)" trên ảnh. Hãy chụp rõ mục này, đủ sáng.');
                }

                return $this->result(true, $content, 'Đã trích xuất mục "Nội dung sinh hoạt (CVHT)" từ ảnh phiếu.');
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->result(false, '', 'Không thể trích xuất nội dung từ ảnh. Vui lòng thử lại.');
    }

    /** @return array{success: bool, text: string, message: string} */
    private function result(bool $success, string $text, string $message): array
    {
        return [
            'success' => $success,
            'text' => $text,
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

        return null;
    }
}
