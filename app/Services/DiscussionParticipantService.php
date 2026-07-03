<?php

namespace App\Services;

use App\Models\DiscussionSection;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Support\Str;

class DiscussionParticipantService
{
    public function __construct(private PermissionService $permissions) {}

    /** @return 'guest'|'student'|'lecturer'|'employee'|'admin' */
    public function resolveRole(?User $user): string
    {
        if (! $user) {
            return 'guest';
        }

        if ($this->permissions->isSuperAdmin($user)) {
            return 'admin';
        }

        $email = strtolower(trim($user->email));

        if (Lecturer::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return 'lecturer';
        }

        if (Employee::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return 'employee';
        }

        return 'student';
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Quản trị hệ thống',
            'lecturer' => 'Giảng viên',
            'employee' => 'Nhân viên',
            'student' => 'Sinh viên',
            default => 'Khách',
        };
    }

    public function isModerator(?User $user, DiscussionSection $section): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }

        $email = strtolower(trim($user->email));

        foreach ([$section->moderator_email, $section->author_email] as $stored) {
            if ($stored && strtolower(trim($stored)) === $email) {
                return true;
            }
        }

        if (! $section->moderator_email && ! filled($section->author_email)) {
            if ($this->resolveRole($user) === 'lecturer') {
                $lecturer = Lecturer::query()->whereRaw('LOWER(email) = ?', [$email])->first();
                if ($lecturer && $this->namesMatch($lecturer->name, $section->author_name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gắn email đăng nhập làm quản trị viên phiên khi GV tạo bảng từ extension (chưa có email).
     */
    public function claimModeratorIfEligible(DiscussionSection $section, ?User $user): DiscussionSection
    {
        if (! $user || $this->isModerator($user, $section)) {
            return $section;
        }

        if ($this->resolveRole($user) !== 'lecturer') {
            return $section;
        }

        if ($section->moderator_email || $section->author_email) {
            return $section;
        }

        $lecturer = Lecturer::query()->whereRaw('LOWER(email) = ?', [strtolower($user->email)])->first();
        if (! $lecturer || ! $this->namesMatch($lecturer->name, $section->author_name)) {
            return $section;
        }

        $section->update([
            'moderator_email' => $user->email,
            'author_email' => $section->author_email ?: $user->email,
        ]);

        return $section->fresh();
    }

    /** @return array<string, mixed> */
    public function participantPayload(?User $user): array
    {
        if (! $user) {
            return [
                'email' => '',
                'name' => 'Khách',
                'role' => 'guest',
                'roleLabel' => $this->roleLabel('guest'),
            ];
        }

        $role = $this->resolveRole($user);

        return [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $role,
            'roleLabel' => $this->roleLabel($role),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function normalizeComments(mixed $comments): array
    {
        if (is_array($comments)) {
            return array_values(array_filter($comments, is_array(...)));
        }

        if ($comments === null || $comments === '') {
            return [];
        }

        if (is_string($comments)) {
            $decoded = json_decode($comments, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, is_array(...)));
            }
            if (is_string($decoded)) {
                $again = json_decode($decoded, true);
                if (is_array($again)) {
                    return array_values(array_filter($again, is_array(...)));
                }
            }
        }

        return [];
    }

    /** @param  array<int, array<string, mixed>>|mixed  $comments */
    public function enrichComments(mixed $comments): array
    {
        $comments = self::normalizeComments($comments);

        $emailSet = collect($comments)
            ->pluck('authorEmail')
            ->filter()
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->unique()
            ->flip();

        if ($emailSet->isEmpty()) {
            return collect($comments)->map(function (array $comment) {
                $comment['authorRole'] = 'student';
                $comment['authorRoleLabel'] = $this->roleLabel('student');

                return $comment;
            })->all();
        }

        $lecturerEmails = Lecturer::query()->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => $emailSet->has($e))
            ->flip();
        $employeeEmails = Employee::query()->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => $emailSet->has($e))
            ->flip();

        return collect($comments)->map(function (array $comment) use ($lecturerEmails, $employeeEmails) {
            $email = strtolower(trim((string) ($comment['authorEmail'] ?? '')));
            $role = 'student';
            if ($email !== '') {
                if ($lecturerEmails->has($email)) {
                    $role = 'lecturer';
                } elseif ($employeeEmails->has($email)) {
                    $role = 'employee';
                }
            }

            $comment['authorRole'] = $role;
            $comment['authorRoleLabel'] = $this->roleLabel($role);

            return $comment;
        })->all();
    }

    private function namesMatch(?string $a, ?string $b): bool
    {
        $left = $this->normalizeName($a);
        $right = $this->normalizeName($b);

        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right || str_contains($left, $right) || str_contains($right, $left);
    }

    private function normalizeName(?string $name): string
    {
        $normalized = Str::lower(trim((string) $name));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return $normalized;
    }
}
