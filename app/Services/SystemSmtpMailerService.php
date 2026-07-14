<?php

namespace App\Services;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class SystemSmtpMailerService
{
    public function __construct(private SystemParameterService $params) {}

    public function isConfigured(): bool
    {
        return $this->credentialsFromParameters() !== null;
    }

    /**
     * @return array{host: string, port: string, user: string, pass: string, fromName: string}|null
     */
    public function credentialsFromParameters(): ?array
    {
        $config = $this->params->all();
        $host = trim((string) ($config['smtpHost'] ?? ''));
        $user = trim((string) ($config['smtpUser'] ?? ''));
        $pass = (string) ($config['smtpPass'] ?? '');

        if ($host === '' || $user === '' || $pass === '') {
            return null;
        }

        return [
            'host' => $host,
            'port' => (string) ($config['smtpPort'] ?? '587'),
            'user' => $user,
            'pass' => $pass,
            'fromName' => (string) ($config['smtpFromName'] ?? 'Phòng Kiểm tra nội bộ'),
        ];
    }

    public function createTransport(string $host, string $port, string $user, string $pass): EsmtpTransport
    {
        $portNum = (int) ($port ?: 587);
        $transport = new EsmtpTransport($host, $portNum, $portNum === 465);
        $transport->setUsername($user);
        $transport->setPassword($pass);

        return $transport;
    }

    /**
     * @param  array{host: string, port: string, user: string, pass: string, fromName?: string}  $credentials
     */
    public function createMailer(array $credentials): Mailer
    {
        return new Mailer($this->createTransport(
            $credentials['host'],
            $credentials['port'],
            $credentials['user'],
            $credentials['pass'],
        ));
    }

    /**
     * @param  array{host: string, port: string, user: string, pass: string, fromName?: string}|null  $credentials
     */
    public function send(Email $email, ?array $credentials = null): void
    {
        $credentials ??= $this->credentialsFromParameters();
        if ($credentials === null) {
            throw new \RuntimeException('Chưa cấu hình SMTP trong Tham số hệ thống (tab Email).');
        }

        $this->createMailer($credentials)->send($email);
    }

    public function formatSmtpError(string $message): string
    {
        if (str_contains($message, 'EAUTH') || str_contains($message, '535')) {
            return 'Lỗi xác thực SMTP (sai Username hoặc Password).';
        }
        if (str_contains($message, 'ECONNREFUSED')) {
            return 'Kết nối SMTP bị từ chối (sai Host hoặc Port).';
        }
        if (str_contains($message, 'ETIMEDOUT')) {
            return 'Kết nối SMTP quá hạn (kiểm tra firewall hoặc mạng).';
        }

        return $message !== '' ? $message : 'Không thể kết nối máy chủ SMTP.';
    }
}
