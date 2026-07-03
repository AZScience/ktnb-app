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
}
