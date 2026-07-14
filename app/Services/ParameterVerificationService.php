<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class ParameterVerificationService
{
    public function __construct(
        private GoogleSheetService $googleSheets,
        private SystemSmtpMailerService $smtpMailer,
    ) {}

    public function verifyGoogleSheet(string $sheetId, string $email, string $privateKey, ?string $tabName = null): array
    {
        return $this->googleSheets->verifyConnection($sheetId, $email, $privateKey, $tabName);
    }

    public function verifyEvidence(
        string $sheetId,
        string $sheetEmail,
        string $sheetKey,
        ?string $tabName,
        string $folderId,
        string $driveEmail,
        string $driveKey,
    ): array {
        $sheetRes = $this->googleSheets->verifyConnection($sheetId, $sheetEmail, $sheetKey, $tabName);
        if (! ($sheetRes['success'] ?? false)) {
            return ['success' => false, 'message' => 'Lỗi Google Sheet: '.($sheetRes['message'] ?? '')];
        }

        $driveRes = $this->googleSheets->verifyDriveConnection($folderId, $driveEmail, $driveKey);
        if (! ($driveRes['success'] ?? false)) {
            return ['success' => false, 'message' => 'Lỗi Google Drive: '.($driveRes['message'] ?? '')];
        }

        return [
            'success' => true,
            'message' => 'Tất cả kết nối tốt! '.($sheetRes['message'] ?? '').' và '.($driveRes['message'] ?? ''),
        ];
    }

    public function verifyAi(string $apiKey, string $model): array
    {
        $key = trim($apiKey);
        if ($key === '') {
            return ['success' => false, 'message' => 'API Key cho AI chưa được cấu hình.'];
        }

        $requested = trim(str_replace('models/', '', $model ?: 'gemini-1.5-flash'));
        $queue = array_values(array_unique([
            $requested,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ]));

        $lastError = '';
        foreach ($queue as $name) {
            $response = Http::timeout(30)
                ->withQueryParameters(['key' => $key])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$name}:generateContent",
                    ['contents' => [['parts' => [['text' => "Say 'OK' if you can hear me."]]]]],
                );

            if ($response->successful()) {
                $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
                if ($text !== '') {
                    $message = $name === $requested
                        ? 'Kết nối AI thành công!'
                        : "Kết nối thành công (sử dụng fallback: {$name}).";

                    return ['success' => true, 'message' => $message];
                }
            }

            $lastError = $this->apiError($response);
            if (str_contains(strtolower($lastError), 'api_key_invalid')) {
                break;
            }
        }

        if (str_contains($lastError, '429') || str_contains(strtolower($lastError), 'quota')) {
            $lastError = "Hết hạn mức (Quota). Chi tiết: {$lastError}";
        }

        return ['success' => false, 'message' => "Lỗi AI: {$lastError}"];
    }

    public function verifyLecturerPortal(string $clientId, string $emailDomains): array
    {
        $clientId = trim($clientId);
        if ($clientId === '') {
            return [
                'success' => false,
                'message' => 'Chưa nhập Google Client ID. Dán Client ID từ Google Cloud Console rồi lưu.',
            ];
        }

        if (! preg_match('/^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$/i', $clientId)) {
            return [
                'success' => false,
                'message' => 'Client ID không đúng định dạng. Ví dụ: 123456789-abc.apps.googleusercontent.com',
            ];
        }

        $domains = array_filter(array_map('trim', explode(',', $emailDomains)));
        if ($domains === []) {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất một miền email được phép (ví dụ: nttu.edu.vn,gmail.com).',
            ];
        }

        return [
            'success' => true,
            'message' => 'Cấu hình hợp lệ. Lưu thay đổi rồi mở /lecturer-portal để thử nút đăng nhập Google.',
            'domains' => $domains,
        ];
    }

    public function verifySmtp(
        string $host,
        string $port,
        string $user,
        string $pass,
        string $fromName,
        bool $sendTest = true,
    ): array {
        if ($host === '' || $user === '' || $pass === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ Host, User và Pass.'];
        }

        try {
            $transport = $this->smtpMailer->createTransport($host, $port, $user, $pass);
            $mailer = new Mailer($transport);

            $transport->start();
            if ($sendTest) {
                $email = (new Email)
                    ->from(sprintf('"%s" <%s>', $fromName ?: 'Phòng Kiểm tra nội bộ', $user))
                    ->to($user)
                    ->subject('Kiểm tra kết nối Email')
                    ->text('Đây là tin nhắn kiểm tra từ Hệ thống Kiểm tra nội bộ. Nếu bạn nhận được email này, cấu hình SMTP của bạn đã chính xác.');

                $mailer->send($email);
            }

            return [
                'success' => true,
                'message' => $sendTest
                    ? 'Kết nối SMTP hoạt động tốt. Vui lòng kiểm tra hộp thư của bạn.'
                    : 'Kết nối SMTP thành công.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $this->smtpMailer->formatSmtpError($e->getMessage())];
        }
    }

    private function apiError(Response $response): string
    {
        $message = $response->json('error.message');

        return is_string($message) && $message !== ''
            ? $message
            : 'HTTP '.$response->status();
    }
}
