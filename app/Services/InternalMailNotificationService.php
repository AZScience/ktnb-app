<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Symfony\Component\Mime\Email;

class InternalMailNotificationService
{
    public function __construct(
        private SystemParameterService $params,
        private SystemSmtpMailerService $smtpMailer,
    ) {}

    /**
     * @param  list<int>  $recipientUserIds
     * @param  list<array{name?: string, url?: string}>  $attachments
     */
    public function sendMessageNotification(
        array $recipientUserIds,
        string $subject,
        string $body,
        array $attachments = [],
    ): void {
        $credentials = $this->smtpMailer->credentialsFromParameters();
        if ($credentials === null) {
            return;
        }

        $emails = $this->resolveRecipientEmails($recipientUserIds);
        if ($emails === []) {
            return;
        }

        $fromName = $credentials['fromName'];

        try {
            $attachmentHtml = '';
            if ($attachments !== []) {
                $items = collect($attachments)
                    ->map(fn ($a) => sprintf(
                        '<li><a href="%s">%s</a></li>',
                        e($a['url'] ?? '#'),
                        e($a['name'] ?? 'Tệp đính kèm'),
                    ))
                    ->implode('');
                $attachmentHtml = <<<HTML
                    <div style="margin-top:20px;padding:10px;background:#f4f4f4;border-radius:5px;">
                        <p style="margin:0;font-weight:bold;">Tệp đính kèm:</p>
                        <ul style="margin:5px 0 0 0;padding-left:20px;">{$items}</ul>
                    </div>
                HTML;
            }

            $appUrl = rtrim((string) config('app.url', ''), '/');
            $year = date('Y');
            $html = <<<HTML
                <div style="font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden;">
                    <div style="background:#0056b3;color:white;padding:20px;text-align:center;">
                        <h2 style="margin:0;">Thông báo từ Hệ thống Kiểm tra nội bộ</h2>
                    </div>
                    <div style="padding:20px;">
                        <p>Chào bạn,</p>
                        <p>Bạn vừa nhận được một tin nhắn nội bộ mới với tiêu đề:</p>
                        <h3 style="color:#0056b3;">{$subject}</h3>
                        <div style="background:#f9f9f9;padding:15px;border-left:4px solid #0056b3;margin:20px 0;">
                            {$body}
                        </div>
                        {$attachmentHtml}
                        <p style="margin-top:30px;">Vui lòng đăng nhập vào hệ thống để xem chi tiết và phản hồi.</p>
                        <div style="text-align:center;margin-top:30px;">
                            <a href="{$appUrl}" style="background:#0056b3;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-weight:bold;">Truy cập Hệ thống</a>
                        </div>
                    </div>
                    <div style="background:#f4f4f4;color:#777;padding:15px;text-align:center;font-size:12px;">
                        <p style="margin:0;">Đây là email tự động, vui lòng không trả lời email này.</p>
                        <p style="margin:5px 0 0 0;">&copy; {$year} {$fromName}</p>
                    </div>
                </div>
            HTML;

            $email = (new Email)
                ->from(sprintf('"%s" <%s>', $fromName, $credentials['user']))
                ->to(...$emails)
                ->subject('[NTTU] '.$subject)
                ->html($html);

            $this->smtpMailer->send($email, $credentials);
        } catch (\Throwable) {
            // Email is best-effort; in-app message is already saved.
        }
    }

    /** @param  list<int>  $userIds
     * @return list<string>
     */
    private function resolveRecipientEmails(array $userIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));

        $fromUsers = User::query()
            ->whereIn('id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        $fromEmployees = Employee::query()
            ->whereIn('user_id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        return array_values(array_unique(array_filter(array_merge($fromUsers, $fromEmployees))));
    }
}
