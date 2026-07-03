<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsSecurityService
{
    public function buildConfig(Request $request): array
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        return [
            'account' => $this->accountSummary($user),
            'sessions' => $this->listSessions($user, $currentSessionId),
            'recent_activity' => $this->recentSecurityActivity($user),
            'routes' => [
                'password' => route('password.update'),
                'refresh' => route('settings.security'),
                'destroy_other_sessions' => route('settings.security.sessions.others'),
                'destroy_session' => url('/settings/security/sessions'),
            ],
        ];
    }

    public function destroyOtherSessions(Request $request): int
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function destroySession(Request $request, string $sessionId): bool
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        if ($sessionId === $currentSessionId) {
            return false;
        }

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    private function accountSummary(User $user): array
    {
        return [
            'email' => $user->email,
            'name' => $user->name,
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'email_verified' => $user->email_verified_at !== null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listSessions(User $user, string $currentSessionId): array
    {
        if (config('session.driver') !== 'database') {
            return [[
                'id' => $currentSessionId,
                'ip_address' => request()->ip(),
                'device' => $this->parseUserAgent(request()->userAgent()),
                'last_active' => now()->toIso8601String(),
                'is_current' => true,
            ]];
        }

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'device' => $this->parseUserAgent($session->user_agent),
                    'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->toIso8601String(),
                    'is_current' => $session->id === $currentSessionId,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentSecurityActivity(User $user): array
    {
        return ActivityLog::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('action', 'like', '%đăng nhập%')
                    ->orWhere('action', 'like', '%login%')
                    ->orWhere('action', 'like', '%mật khẩu%')
                    ->orWhere('action', 'like', '%password%')
                    ->orWhere('action', 'like', '%phiên%')
                    ->orWhere('action', 'like', '%session%')
                    ->orWhere('target_type', 'Auth');
            })
            ->orderByDesc('logged_at')
            ->limit(8)
            ->get(['action', 'details', 'ip_address', 'logged_at'])
            ->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'details' => $log->details,
                'ip_address' => $log->ip_address,
                'logged_at' => $log->logged_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    public function parseUserAgent(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Microsoft Edge',
            str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/') => 'Google Chrome',
            str_contains($ua, 'firefox/') => 'Mozilla Firefox',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            default => 'Trình duyệt không xác định',
        };

        $os = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Hệ điều hành không xác định',
        };

        return "{$browser} · {$os}";
    }
}
