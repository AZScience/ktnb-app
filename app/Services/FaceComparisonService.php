<?php

namespace App\Services;

use App\Services\SystemParameterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FaceComparisonService
{
    public function compare(string $portraitPhoto, string $documentPhoto): array
    {
        $apiKey = trim((string) (
            app(SystemParameterService::class)->get('aiApiKey')
            ?: config('services.google.ai_key')
            ?: env('GOOGLE_GENAI_API_KEY', '')
        ));

        if ($apiKey === '') {
            return [
                'is_match' => false,
                'confidence' => 0,
                'message' => 'API Key cho AI chưa được cấu hình. Vui lòng kiểm tra Tham số hệ thống (khóa ai_api_key).',
            ];
        }

        $portraitPart = $this->toInlineImagePart($portraitPhoto);
        $documentPart = $this->toInlineImagePart($documentPhoto);

        if (! $portraitPart || ! $documentPart) {
            return [
                'is_match' => false,
                'confidence' => 0,
                'message' => 'Định dạng ảnh không hợp lệ.',
            ];
        }

        $model = config('services.google.ai_model', 'gemini-2.0-flash');
        $prompt = <<<'PROMPT'
Bạn là một chuyên gia nhận diện khuôn mặt. Hãy so sánh khuôn mặt trong hai ảnh sau:
1. Ảnh chân dung (Portrait)
2. Ảnh khuôn mặt trên giấy tờ (Document Photo)

Nhiệm vụ:
- Xác định xem hai khuôn mặt này có phải là cùng một người hay không.
- Trả về kết quả dưới dạng JSON với cấu trúc:
  {
    "isMatch": boolean,
    "confidence": number (từ 0 đến 100),
    "reason": string (giải thích ngắn gọn bằng tiếng Việt)
  }

Lưu ý: Chỉ trả về JSON, không thêm văn bản khác.
PROMPT;

        $response = Http::timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        $portraitPart,
                        $documentPart,
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return [
                'is_match' => false,
                'confidence' => 0,
                'message' => 'Lỗi kết nối AI: '.($response->json('error.message') ?? $response->body()),
            ];
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (! is_string($text) || $text === '') {
            return [
                'is_match' => false,
                'confidence' => 0,
                'message' => 'AI không trả về kết quả.',
            ];
        }

        if (! preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            return [
                'is_match' => Str::contains(Str::lower($text), 'true'),
                'confidence' => 50,
                'message' => 'Kết quả phân tích từ AI không đúng định dạng.',
            ];
        }

        $parsed = json_decode($matches[0], true);
        if (! is_array($parsed)) {
            return [
                'is_match' => false,
                'confidence' => 0,
                'message' => 'Không thể đọc kết quả từ AI.',
            ];
        }

        return [
            'is_match' => (bool) ($parsed['isMatch'] ?? false),
            'confidence' => (int) ($parsed['confidence'] ?? 0),
            'message' => (string) ($parsed['reason'] ?? (($parsed['isMatch'] ?? false) ? 'Khuôn mặt trùng khớp.' : 'Khuôn mặt không trùng khớp.')),
        ];
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
