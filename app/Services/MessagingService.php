<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Message;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MessagingService
{
    /** @return Collection<int, Message> */
    public function messagesForUser(User $user, string $folder = 'inbox', int $limit = 300): Collection
    {
        $userId = (int) $user->id;

        $query = Message::query()
            ->with('sender')
            ->orderByDesc('sent_at')
            ->limit($limit);

        if ($folder === 'sent') {
            $query->where('sender_user_id', $userId);
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_user_id', $userId)
                    ->orWhereJsonContains('recipient_user_ids', $userId);
            });
        }

        return $query->get()->filter(function (Message $message) use ($userId, $folder) {
            $trash = in_array($userId, $message->trash_by_user_ids ?? [], true);
            $isInbox = in_array($userId, $message->recipient_user_ids ?? [], true);
            $isSent = (int) $message->sender_user_id === $userId;

            return match ($folder) {
                'trash' => $trash,
                'sent' => $isSent && ! $trash,
                default => $isInbox && ! $trash,
            };
        })->values();
    }

    public function unreadInboxCount(User $user): int
    {
        $userId = (int) $user->id;

        return Message::query()
            ->whereJsonContains('recipient_user_ids', $userId)
            ->where('is_read', false)
            ->where(function ($query) use ($userId) {
                $query->whereNull('trash_by_user_ids')
                    ->orWhereJsonDoesntContain('trash_by_user_ids', $userId);
            })
            ->count();
    }

    /** @return Collection<int, Message> */
    public function unreadNotificationsForUser(User $user, int $limit = 15): Collection
    {
        $userId = (int) $user->id;

        return Message::query()
            ->with('sender')
            ->whereJsonContains('recipient_user_ids', $userId)
            ->where('is_read', false)
            ->orderByDesc('sent_at')
            ->limit($limit * 3)
            ->get()
            ->reject(fn (Message $message) => in_array($userId, $message->trash_by_user_ids ?? [], true))
            ->take($limit)
            ->values();
    }

    /** @return array<string, \App\Models\Employee> */
    public function employeeByUserId(): array
    {
        return Employee::query()
            ->whereNotNull('user_id')
            ->get()
            ->keyBy(fn (Employee $e) => (int) $e->user_id)
            ->all();
    }

    /** @return array<int, string> */
    public function positionNames(): array
    {
        return Position::query()->pluck('name', 'id')->all();
    }

    /** @return array<string, mixed> */
    public function formatMessage(Message $message, array $employeeByUserId, array $positionNames): array
    {
        $senderUserId = (int) ($message->sender_user_id ?? 0);
        $employee = $employeeByUserId[$senderUserId] ?? null;
        $positionKey = $employee?->position;
        $positionName = $positionKey && isset($positionNames[$positionKey])
            ? $positionNames[$positionKey]
            : ($positionKey ?: '');

        return [
            'id' => $message->id,
            'subject' => $message->subject,
            'body' => $message->body,
            'body_preview' => Str::limit(trim(strip_tags((string) $message->body)), 140),
            'is_read' => (bool) $message->is_read,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'sent_label' => $message->sent_at?->format('d/m/Y H:i'),
            'sender_user_id' => $senderUserId,
            'sender_name' => $employee?->name ?? $message->sender?->name ?? 'Người dùng',
            'sender_avatar' => $employee?->avatar_url,
            'position_name' => $positionName,
            'attachments' => $message->attachments ?? [],
        ];
    }

    /** @return list<array{user_id: int, employee_id: ?string, name: string, email: ?string, position: string, avatar_url: ?string}> */
    public function composeRecipients(?int $excludeUserId = null): array
    {
        $positions = $this->positionNames();

        return Employee::query()
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($positions, $excludeUserId) {
                $userId = $employee->user_id
                    ? (int) $employee->user_id
                    : (int) (User::query()->where('email', $employee->email)->value('id') ?: 0);

                if ($userId <= 0 || ($excludeUserId && $userId === $excludeUserId)) {
                    return null;
                }

                return [
                    'user_id' => $userId,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'position' => ($employee->position && isset($positions[$employee->position]))
                        ? $positions[$employee->position]
                        : ($employee->position ?? ''),
                    'avatar_url' => $employee->avatar_url,
                ];
            })
            ->filter()
            ->unique('user_id')
            ->values()
            ->all();
    }

    public function resolveLegacyUserId(?string $legacyId): ?int
    {
        if (! $legacyId) {
            return null;
        }

        $employee = Employee::query()->find($legacyId);

        return $employee?->user_id ? (int) $employee->user_id : null;
    }

    /** @param  list<string|int>  $legacyIds
     * @return list<int>
     */
    public function resolveLegacyUserIds(array $legacyIds): array
    {
        return collect($legacyIds)
            ->map(fn ($id) => $this->resolveLegacyUserId((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
