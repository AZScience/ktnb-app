<?php

namespace App\Services;

use App\Models\SystemParameter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemParameterService
{
    public const ALLOWED_KEYS = [
        'bannerUrl',
        'bannerHeight',
        'googleSheetId',
        'summaryReportGoogleSheetId',
        'googleServiceAccountEmail',
        'googlePrivateKey',
        'googleDriveFolderId',
        'faqSheetTabName',
        'reportSheetTabName',
        'adminEmail',
        'supportPhone',
        'website',
        'smtpFromName',
        'smtpHost',
        'smtpPort',
        'smtpUser',
        'smtpPass',
        'aiProvider',
        'aiApiKey',
        'aiModel',
        'aiSystemPrompt',
        'aiTemperature',
        'loginImageUrl',
        'loginQuote',
        'loginQuoteAuthor',
        'feedbackSheetId',
        'feedbackTabName',
        'evidenceServiceAccountEmail',
        'evidencePrivateKey',
        'googleClientId',
        'lecturerPortalEmailDomains',
    ];

    private const IMAGE_KEYS = ['bannerUrl', 'loginImageUrl'];

    private const LEGACY_ALIASES = [
        'banner_url' => 'bannerUrl',
        'ai_api_key' => 'aiApiKey',
    ];

    public function defaults(): array
    {
        return [
            'bannerUrl' => 'https://kiemtranoibo.ntt.edu.vn/wp-content/uploads/2025/09/PHONG-KIEM-TRA-NOI-BO.png',
            'bannerHeight' => '40',
            'googleSheetId' => '',
            'summaryReportGoogleSheetId' => (string) config('nttu.summary_report_google_sheet_id', ''),
            'googleServiceAccountEmail' => '',
            'googlePrivateKey' => '',
            'googleDriveFolderId' => (string) config('nttu.feedback_google_drive_folder_id', ''),
            'faqSheetTabName' => 'FAQ',
            'reportSheetTabName' => 'Báo cáo Tổng hợp',
            'adminEmail' => '',
            'supportPhone' => '',
            'website' => 'https://kiemtranoibo.ntt.edu.vn',
            'smtpFromName' => 'Phòng Kiểm tra nội bộ',
            'smtpHost' => 'smtp.gmail.com',
            'smtpPort' => '587',
            'smtpUser' => '',
            'smtpPass' => '',
            'aiProvider' => 'google',
            'aiApiKey' => '',
            'aiModel' => 'gemini-1.5-flash',
            'aiSystemPrompt' => 'Bạn là một trợ lý AI chuyên nghiệp hỗ trợ công tác kiểm tra nội bộ tại trường Đại học Nguyễn Tất Thành. Hãy trả lời ngắn gọn, chính xác và chuyên nghiệp.',
            'aiTemperature' => '0.7',
            'loginImageUrl' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080',
            'loginQuote' => 'Công nghệ chỉ là một công cụ. Về mặt khích lệ bọn trẻ làm việc cùng nhau và động viên chúng, giáo viên là người quan trọng nhất.',
            'loginQuoteAuthor' => 'Bill Gates',
            'feedbackSheetId' => (string) config('nttu.feedback_google_sheet_id', ''),
            'feedbackTabName' => 'Trang tính1',
            'evidenceServiceAccountEmail' => '',
            'evidencePrivateKey' => '',
            'googleClientId' => '',
            'lecturerPortalEmailDomains' => 'nttu.edu.vn,ntt.edu.vn,gmail.com',
        ];
    }

    public function all(bool $normalizeImages = true): array
    {
        unset($normalizeImages);

        return Cache::remember('system_params:all', now()->addHour(), function () {
            return $this->buildAllParams();
        });
    }

    /** @return array<string, string> */
    public function forLoginPage(): array
    {
        return Cache::remember('system_params:login', now()->addHour(), function () {
            $defaults = $this->defaults();
            $keys = ['bannerUrl', 'bannerHeight', 'loginImageUrl', 'loginQuote', 'loginQuoteAuthor'];
            $stored = SystemParameter::query()
                ->whereIn('key', $keys)
                ->pluck('value', 'key')
                ->all();

            $params = [];
            foreach ($keys as $key) {
                $params[$key] = $this->normalizeStoredValue($stored[$key] ?? $defaults[$key] ?? '');
            }

            return $params;
        });
    }

    public function clearCache(): void
    {
        Cache::forget('system_params:all');
        Cache::forget('system_params:login');
    }

    /** @return array<string, string> */
    private function buildAllParams(): array
    {
        $stored = SystemParameter::pluck('value', 'key')->all();
        $merged = array_merge($this->defaults(), $stored);

        foreach (self::LEGACY_ALIASES as $legacy => $canonical) {
            if (
                isset($stored[$legacy])
                && ($merged[$canonical] === '' || $merged[$canonical] === $this->defaults()[$canonical])
            ) {
                $merged[$canonical] = $stored[$legacy];
            }
        }

        $params = array_intersect_key($merged, array_flip(self::ALLOWED_KEYS));

        foreach ($params as $key => $value) {
            $params[$key] = $this->normalizeStoredValue($value);
        }

        return $params;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? (string) $all[$key] : $default;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function updateMany(array $params): void
    {
        foreach ($params as $key => $value) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            $value = $this->normalizeIncomingValue($value);

            if (in_array($key, self::IMAGE_KEYS, true) && str_starts_with($value, 'data:image/')) {
                $value = $this->persistDataUrl($key, $value);
            }

            SystemParameter::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->clearCache();
    }

    private function normalizeIncomingValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return '';
        }

        return $this->normalizeStoredValue($value);
    }

    private function normalizeStoredValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = trim((string) $value);

        return strtolower($string) === 'null' ? '' : $string;
    }

    public function persistDataUrl(string $key, string $dataUrl): string
    {
        if (! preg_match('#^data:image/(\w+);base64,(.+)$#', $dataUrl, $matches)) {
            return $dataUrl;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            return $dataUrl;
        }

        $filename = $key.'-'.now()->format('YmdHis').'.'.$extension;
        $path = 'system-parameters/'.$filename;
        Storage::disk('public')->put($path, $binary);

        $url = Storage::disk('public')->url($path);
        SystemParameter::updateOrCreate(['key' => $key], ['value' => $url]);
        $this->clearCache();

        return $url;
    }
}
