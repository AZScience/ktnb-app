<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class DocumentRecordExtractionService
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
     *     doc_number: string,
     *     title: string,
     *     ai_summary: string,
     *     extracted_text: string,
     *     abstract: string,
     *     message: string
     * }
     */
    public function extract(string $originalFile, ?string $docType = null, ?string $fileName = null): array
    {
        $file = $this->resolveFile($originalFile, $fileName);
        if ($file === null) {
            return $this->emptyResult('Không đọc được tệp văn bản. Hãy tải file lên lại.');
        }

        $extractedText = $this->extractRawText($file);
        if (mb_strlen(trim($extractedText)) < 40 && $this->aiApiKey() !== '') {
            $geminiText = $this->ocrWithGemini($file);
            if ($geminiText !== '') {
                $extractedText = $geminiText;
            }
        }

        if (trim($extractedText) === '') {
            return $this->heuristicFromFileName($file['name'], 'Không trích xuất được nội dung văn bản. Đã gợi ý từ tên file.');
        }

        $structured = $this->parseStructured($extractedText, $docType, $file['name']);

        return [
            'doc_number' => $structured['doc_number'],
            'title' => $structured['title'],
            'ai_summary' => $structured['ai_summary'],
            'extracted_text' => $extractedText,
            'abstract' => $structured['abstract'],
            'message' => $structured['message'],
        ];
    }

    /** @return array{path: string, name: string, mime: string}|null */
    private function resolveFile(string $originalFile, ?string $fileName): ?array
    {
        $url = trim($originalFile);
        $name = trim((string) $fileName);

        if (str_contains($originalFile, ':::')) {
            [$namePart, $url] = explode(':::', $originalFile, 2);
            if ($name === '') {
                $name = $namePart;
            }
        }

        $localPath = $this->pathFromStorageUrl($url);
        if ($localPath && is_file($localPath)) {
            return [
                'path' => $localPath,
                'name' => $name !== '' ? $name : basename($localPath),
                'mime' => mime_content_type($localPath) ?: 'application/octet-stream',
            ];
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            try {
                $response = Http::timeout(30)->get($url);
                if (! $response->successful()) {
                    return null;
                }
                $ext = pathinfo($name ?: parse_url($url, PHP_URL_PATH) ?: 'file', PATHINFO_EXTENSION) ?: 'bin';
                $temp = storage_path('app/temp/doc-extract-'.Str::uuid().'.'.$ext);
                if (! is_dir(dirname($temp))) {
                    mkdir(dirname($temp), 0755, true);
                }
                file_put_contents($temp, $response->body());

                return [
                    'path' => $temp,
                    'name' => $name !== '' ? $name : basename(parse_url($url, PHP_URL_PATH) ?: 'document'),
                    'mime' => $response->header('Content-Type') ? explode(';', $response->header('Content-Type'))[0] : mime_content_type($temp),
                    'temp' => true,
                ];
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function pathFromStorageUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = urldecode($path);

        if (preg_match('#/storage/(.+)$#', $path, $matches)) {
            $relative = $matches[1];
            $full = Storage::disk('public')->path($relative);
            if (is_file($full)) {
                return $full;
            }
        }

        if (is_file($url)) {
            return $url;
        }

        return null;
    }

    /** @param array{path: string, name: string, mime: string} $file */
    private function extractRawText(array $file): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        return match ($ext) {
            'txt' => $this->readTextFile($file['path']),
            'docx' => $this->extractDocx($file['path']),
            'doc' => $this->readTextFile($file['path']),
            'xlsx', 'xls' => $this->extractSpreadsheet($file['path']),
            default => '',
        };
    }

    private function readTextFile(string $path): string
    {
        $content = @file_get_contents($path);

        return is_string($content) ? trim($content) : '';
    }

    private function extractDocx(string $path): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return '';
        }

        $xml = preg_replace('/<w:tab\/>/', "\t", $xml) ?? $xml;
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/u", "\n\n", $text) ?? '') ?? '');
    }

    private function extractSpreadsheet(string $path): string
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $lines = [];
            foreach ($sheet->toArray() as $row) {
                $cells = array_filter(array_map(fn ($v) => trim((string) $v), $row));
                if ($cells !== []) {
                    $lines[] = implode(' | ', $cells);
                }
            }

            return implode("\n", $lines);
        } catch (\Throwable) {
            return '';
        }
    }

    /** @param array{path: string, name: string, mime: string} $file */
    private function ocrWithGemini(array $file): string
    {
        $apiKey = $this->aiApiKey();
        if ($apiKey === '') {
            return '';
        }

        $binary = @file_get_contents($file['path']);
        if ($binary === false || $binary === '') {
            return '';
        }

        $mime = $this->normalizeMime($file['mime'], $file['name']);
        $part = [
            'inline_data' => [
                'mime_type' => $mime,
                'data' => base64_encode($binary),
            ],
        ];

        $prompt = 'Hãy OCR toàn bộ văn bản trong tệp này. Trả về plain text tiếng Việt, giữ xuống dòng hợp lý. Chỉ trả nội dung văn bản, không giải thích.';

        foreach ($this->geminiModels() as $model) {
            try {
                $response = Http::timeout(120)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [[
                            'parts' => [
                                ['text' => $prompt],
                                $part,
                            ],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 8192,
                        ],
                    ],
                );

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return '';
    }

    /** @return array{doc_number: string, title: string, ai_summary: string, abstract: string, message: string} */
    private function parseStructured(string $extractedText, ?string $docType, string $fileName): array
    {
        $apiKey = $this->aiApiKey();
        if ($apiKey !== '') {
            $ai = $this->parseWithGemini($extractedText, $docType, $fileName, $apiKey);
            if ($ai !== null) {
                return $ai;
            }
        }

        return $this->heuristicParse($extractedText, $fileName);
    }

    /** @return array{doc_number: string, title: string, ai_summary: string, abstract: string, message: string}|null */
    private function parseWithGemini(string $text, ?string $docType, string $fileName, string $apiKey): ?array
    {
        $snippet = mb_substr($text, 0, 12000);
        $typeHint = $docType ? "Loại văn bản: {$docType}." : '';

        $prompt = <<<PROMPT
Bạn là chuyên gia hồ sơ văn bản hành chính Việt Nam. {$typeHint}
Tên file: {$fileName}

Đọc nội dung OCR sau và trả về JSON (chỉ JSON, không markdown):
{
  "doc_number": "Số / ký hiệu văn bản (vd: 123/QĐ-UBND), rỗng nếu không có",
  "title": "Tiêu đề / trích yếu chính thức của văn bản",
  "abstract": "Trích yếu ngắn 1-3 câu",
  "ai_summary": "Tóm lược nội dung 3-5 câu bằng tiếng Việt"
}

Nội dung OCR:
{$snippet}
PROMPT;

        foreach ($this->geminiModels() as $model) {
            try {
                $response = Http::timeout(90)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [[
                            'parts' => [['text' => $prompt]],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 2048,
                        ],
                    ],
                );

                if (! $response->successful()) {
                    continue;
                }

                $raw = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if (! is_string($raw) || ! preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
                    continue;
                }

                $parsed = json_decode($matches[0], true);
                if (! is_array($parsed)) {
                    continue;
                }

                return [
                    'doc_number' => trim((string) ($parsed['doc_number'] ?? '')),
                    'title' => trim((string) ($parsed['title'] ?? '')),
                    'abstract' => trim((string) ($parsed['abstract'] ?? '')),
                    'ai_summary' => trim((string) ($parsed['ai_summary'] ?? '')),
                    'message' => 'Đã trích xuất thông tin từ văn bản bằng AI.',
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /** @return array{doc_number: string, title: string, ai_summary: string, abstract: string, message: string} */
    private function heuristicParse(string $text, string $fileName): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/u', $text) ?: [])));

        $docNumber = '';
        if (preg_match('/(?:Số|Số\/Ký hiệu|Ký hiệu)\s*[:.]?\s*([^\n\r;]+)/iu', $text, $m)) {
            $docNumber = trim($m[1]);
        } elseif (preg_match('/\b(\d{1,5}\/[A-Z0-9Đ\-]+(?:-[A-Z0-9]+)*)\b/u', $text, $m)) {
            $docNumber = trim($m[1]);
        }

        $title = '';
        foreach ($lines as $line) {
            if (preg_match('/^(?:V\/v|V/v|Về việc|THÔNG BÁO|QUYẾT ĐỊNH|CÔNG VĂN|TỜ TRÌNH)\b/iu', $line)) {
                $title = $line;
                break;
            }
        }
        if ($title === '') {
            $title = collect($lines)
                ->sortByDesc(fn ($line) => mb_strlen($line))
                ->first(fn ($line) => mb_strlen($line) >= 12 && mb_strlen($line) <= 240) ?? '';
        }
        if ($title === '') {
            $title = strtoupper(str_replace(['-', '_'], ' ', pathinfo($fileName, PATHINFO_FILENAME)));
        }

        $abstract = mb_substr(implode(' ', array_slice($lines, 0, 3)), 0, 400);
        $summary = mb_substr($text, 0, 600);

        return [
            'doc_number' => $docNumber,
            'title' => $title,
            'abstract' => $abstract,
            'ai_summary' => $summary !== '' ? $summary : 'Chưa cấu hình AI API Key — đã trích xuất cơ bản từ nội dung văn bản.',
            'message' => 'Đã trích xuất thông tin từ nội dung văn bản.',
        ];
    }

    /** @return array{doc_number: string, title: string, ai_summary: string, extracted_text: string, abstract: string, message: string} */
    private function heuristicFromFileName(string $fileName, string $message): array
    {
        $title = strtoupper(str_replace(['-', '_'], ' ', pathinfo($fileName, PATHINFO_FILENAME)));

        return [
            'doc_number' => '',
            'title' => $title,
            'ai_summary' => '',
            'extracted_text' => '',
            'abstract' => '',
            'message' => $message,
        ];
    }

    /** @return array{doc_number: string, title: string, ai_summary: string, extracted_text: string, abstract: string, message: string} */
    private function emptyResult(string $message): array
    {
        return [
            'doc_number' => '',
            'title' => '',
            'ai_summary' => '',
            'extracted_text' => '',
            'abstract' => '',
            'message' => $message,
        ];
    }

    private function aiApiKey(): string
    {
        return trim((string) ($this->parameters->get('aiApiKey') ?: env('GOOGLE_GENAI_API_KEY', '')));
    }

    /** @return list<string> */
    private function geminiModels(): array
    {
        $requested = trim((string) ($this->parameters->get('aiModel') ?: 'gemini-2.0-flash'));
        $requested = preg_replace('/^models\//', '', $requested) ?? $requested;

        return array_values(array_unique(array_filter([$requested, ...self::GEMINI_MODELS])));
    }

    private function normalizeMime(string $mime, string $name): string
    {
        $mime = explode(';', $mime)[0];
        if ($mime !== '' && $mime !== 'application/octet-stream') {
            return $mime;
        }

        return match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}
