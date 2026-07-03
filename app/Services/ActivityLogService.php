<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService
{
    public function log(string $action, string $targetType, ?string $details = null, ?array $previous = null, ?array $new = null): void
    {
        $user = Auth::user();

        ActivityLog::create($this->buildPayload($user, $action, $targetType, $details, $previous, $new));
    }

    public function logDeferred(string $action, string $targetType, ?string $details = null, ?User $user = null): void
    {
        $user ??= Auth::user();
        if (! $user) {
            return;
        }

        $payload = $this->buildPayload($user, $action, $targetType, $details);

        dispatch(static function () use ($payload): void {
            ActivityLog::create($payload);
        })->afterResponse();
    }

    /** @return array<string, mixed> */
    private function buildPayload(?User $user, string $action, string $targetType, ?string $details = null, ?array $previous = null, ?array $new = null): array
    {
        return [
            'id' => (string) Str::uuid(),
            'logged_at' => now(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => Str::limit($action, 128, ''),
            'target_type' => Str::limit($targetType, 255, ''),
            'details' => $details,
            'ip_address' => request()->ip(),
            'previous_data' => $previous,
            'new_data' => $new,
        ];
    }
}
