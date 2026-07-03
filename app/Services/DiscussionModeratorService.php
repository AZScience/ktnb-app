<?php

namespace App\Services;

use App\Models\DiscussionSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscussionModeratorService
{
    public function __construct(private DiscussionParticipantService $participants) {}

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @return array{token: string, hash: string} */
    public function generateTokenPair(): array
    {
        $token = Str::random(48);

        return [
            'token' => $token,
            'hash' => self::hashToken($token),
        ];
    }

    public function tokenIsValid(DiscussionSection $section, ?string $token): bool
    {
        if (! $token || ! $section->moderator_token_hash) {
            return false;
        }

        return hash_equals($section->moderator_token_hash, self::hashToken($token));
    }

    public function rememberTokenSession(DiscussionSection $section, string $token): bool
    {
        if (! $this->tokenIsValid($section, $token)) {
            return false;
        }

        session(["discussion_mod.{$section->id}" => true]);

        return true;
    }

    public function hasSessionAccess(string $sectionId): bool
    {
        return (bool) session("discussion_mod.{$sectionId}");
    }

    public function tokenFromRequest(Request $request): ?string
    {
        return $request->query('key')
            ?? $request->input('key')
            ?? $request->header('X-Discussion-Token')
            ?? $request->input('moderatorToken')
            ?? $request->input('moderator_token');
    }

    public function canModerate(?User $user, DiscussionSection $section, Request $request): bool
    {
        $token = $this->tokenFromRequest($request);
        if ($token && $this->tokenIsValid($section, $token)) {
            return true;
        }

        if ($this->hasSessionAccess($section->id)) {
            return true;
        }

        return $user ? $this->participants->isModerator($user, $section) : false;
    }

    public function assertCanModerate(?User $user, DiscussionSection $section, Request $request): void
    {
        if (! $this->canModerate($user, $section, $request)) {
            abort(403, 'Chỉ quản trị viên phiên (Giảng viên qua extension) mới được thực hiện thao tác này.');
        }
    }

    /** @return array<string, mixed> */
    public function guestParticipant(): array
    {
        return [
            'email' => '',
            'name' => 'Khách',
            'role' => 'guest',
            'roleLabel' => 'Sinh viên / Khách',
        ];
    }

    /** @return array<string, mixed> */
    public function moderatorGuestParticipant(string $lecturerName = 'Giảng viên'): array
    {
        return [
            'email' => '',
            'name' => $lecturerName,
            'role' => 'lecturer',
            'roleLabel' => 'Giảng viên (Extension)',
        ];
    }
}
