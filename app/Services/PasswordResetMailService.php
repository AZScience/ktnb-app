<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

class PasswordResetMailService
{
    public function __construct(private SystemSmtpMailerService $smtpMailer) {}

    public function send(User $user, string $token): void
    {
        if (! $this->smtpMailer->isConfigured()) {
            throw new \RuntimeException('Chưa cấu hình SMTP trong Tham số hệ thống (tab Email).');
        }

        $credentials = $this->smtpMailer->credentialsFromParameters();
        $emailAddress = $user->getEmailForPasswordReset();
        $resetUrl = URL::route('password.reset', [
            'token' => $token,
            'email' => $emailAddress,
        ], absolute: true);

        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $fromName = $credentials['fromName'] ?? 'Phòng Kiểm tra nội bộ';
        $appName = (string) config('app.name', 'Hệ thống Kiểm tra nội bộ');
        $year = date('Y');

        $html = <<<HTML
            <div style="font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden;">
                <div style="background:#0056b3;color:white;padding:20px;text-align:center;">
                    <h2 style="margin:0;">Đặt lại mật khẩu</h2>
                </div>
                <div style="padding:20px;">
                    <p>Xin chào,</p>
                    <p>Bạn nhận được email này vì chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản <strong>{$emailAddress}</strong> trên {$appName}.</p>
                    <div style="text-align:center;margin:30px 0;">
                        <a href="{$resetUrl}" style="background:#0056b3;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-weight:bold;">Đặt lại mật khẩu</a>
                    </div>
                    <p>Liên kết có hiệu lực trong <strong>{$expireMinutes} phút</strong>. Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.</p>
                    <p style="font-size:12px;color:#666;word-break:break-all;">Nếu nút không hoạt động, sao chép đường dẫn sau vào trình duyệt:<br>{$resetUrl}</p>
                </div>
                <div style="background:#f4f4f4;color:#777;padding:15px;text-align:center;font-size:12px;">
                    <p style="margin:0;">Đây là email tự động, vui lòng không trả lời email này.</p>
                    <p style="margin:5px 0 0 0;">&copy; {$year} {$fromName}</p>
                </div>
            </div>
        HTML;

        $message = (new Email)
            ->from(sprintf('"%s" <%s>', $fromName, $credentials['user']))
            ->to($emailAddress)
            ->subject('Đặt lại mật khẩu — '.$appName)
            ->html($html);

        try {
            $this->smtpMailer->send($message, $credentials);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Không gửi được email đặt lại mật khẩu: '.$this->smtpMailer->formatSmtpError($e->getMessage()),
                0,
                $e,
            );
        }
    }
}
