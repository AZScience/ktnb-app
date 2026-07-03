<?php

namespace App\Http\Controllers;

use App\Models\DiscussionSection;
use App\Models\User;
use App\Services\DiscussionModeratorService;
use App\Services\DiscussionParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function __construct(
        private DiscussionParticipantService $participants,
        private DiscussionModeratorService $moderators,
    ) {}

    public function index(Request $request): View
    {
        $highlightId = $request->get('id');

        if ($highlightId) {
            return $this->publicSectionView($request, $highlightId);
        }

        if ($request->user()) {
            return $this->boardView($request);
        }

        return view('discussion.index', [
            'sections' => [],
            'highlightId' => null,
            'needsLink' => true,
            'participant' => $this->moderators->guestParticipant(),
        ]);
    }

    private function publicSectionView(Request $request, string $highlightId): View
    {
        $highlight = DiscussionSection::find($highlightId);
        if (! $highlight) {
            abort(404, 'Không tìm thấy bảng thảo luận. Vui lòng kiểm tra lại link từ giảng viên.');
        }

        $token = $this->moderators->tokenFromRequest($request);
        if ($token) {
            $this->moderators->rememberTokenSession($highlight, $token);
        }

        $highlight = $this->participants->claimModeratorIfEligible($highlight, $request->user());

        return view('discussion.index', [
            'sections' => [
                $this->mapSection($highlight, $request),
            ],
            'highlightId' => $highlightId,
            'needsLink' => false,
            'participant' => $this->resolvePageParticipant($request, $highlightId, $highlight),
        ]);
    }

    private function boardView(Request $request): View
    {
        $user = $request->user();
        $participant = $this->participants->participantPayload($user);

        $sections = DiscussionSection::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DiscussionSection $section) => $this->mapSectionForBoard($section, $user, $participant))
            ->values()
            ->all();

        return view('discussion.board', [
            'sections' => $sections,
            'participant' => $participant,
            'routes' => [
                'store' => route('discussion.store'),
                'updateTemplate' => route('discussion.update', ['section' => '__ID__']),
                'destroyTemplate' => route('discussion.destroy', ['section' => '__ID__']),
                'commentTemplate' => route('discussion.comment', ['section' => '__ID__']),
                'commentDestroyTemplate' => route('discussion.comment.destroy', ['section' => '__SECTION__', 'commentId' => '__COMMENT__']),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function mapSectionForBoard(DiscussionSection $section, User $user, array $participant): array
    {
        $email = strtolower(trim($user->email));
        $authorEmail = strtolower(trim((string) $section->author_email));
        $role = $participant['role'] ?? 'student';
        $isAuthor = $authorEmail !== '' && $authorEmail === $email;
        $isLecturer = in_array($role, ['lecturer', 'admin'], true);

        return [
            'id' => $section->id,
            'title' => $section->title,
            'student_content' => $section->student_content,
            'author_name' => $section->author_name,
            'author_email' => $section->author_email,
            'class_id' => $section->class_id,
            'created_at' => $section->created_at?->timezone(config('app.timezone'))->format('H:i d/m/Y'),
            'created_at_iso' => $section->created_at?->format('Y-m-d'),
            'comments' => $this->participants->enrichComments($section->commentsList()),
            'can_edit_content' => $isAuthor,
            'can_edit_title' => $isAuthor || $isLecturer,
            'can_delete' => $isAuthor || $isLecturer,
            'can_comment' => $isLecturer,
        ];
    }

    /** @return array<string, mixed> */
    private function mapSection(DiscussionSection $section, Request $request): array
    {
        return [
            'id' => $section->id,
            'title' => $section->title,
            'student_content' => $section->student_content,
            'author_name' => $section->author_name,
            'author_email' => $section->author_email,
            'moderator_email' => $section->moderator_email,
            'class_id' => $section->class_id,
            'created_at' => $section->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'created_at_iso' => $section->created_at?->format('Y-m-d'),
            'comments' => $this->participants->enrichComments($section->commentsList()),
            'is_moderator' => $this->moderators->canModerate(null, $section, $request),
        ];
    }

    /** @return array<string, mixed> */
    private function resolvePageParticipant(Request $request, string $highlightId, DiscussionSection $section): array
    {
        if ($this->moderators->canModerate(null, $section, $request)) {
            return $this->moderators->moderatorGuestParticipant($section->author_name ?: 'Giảng viên');
        }

        return $this->moderators->guestParticipant();
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'student_content' => 'nullable|string',
        ]);

        $user = $request->user();
        $tokenPair = $this->moderators->generateTokenPair();
        $id = (string) Str::uuid();

        DiscussionSection::create([
            'id' => $id,
            'title' => $data['title'],
            'student_content' => $data['student_content'] ?? '',
            'author_email' => $user->email,
            'author_name' => $user->name,
            'moderator_email' => $user->email,
            'moderator_token_hash' => $tokenPair['hash'],
            'comments' => [],
        ]);

        if ($request->wantsJson()) {
            $section = DiscussionSection::find($id);
            $participant = $this->participants->participantPayload($user);

            return response()->json([
                'message' => 'Đã tạo bảng thảo luận mới',
                'section' => $section ? $this->mapSectionForBoard($section, $user, $participant) : null,
            ]);
        }

        return redirect()->route('discussion.index')->with('success', 'Đã tạo chủ đề thảo luận.');
    }

    public function updateSection(Request $request, DiscussionSection $section): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $participant = $user ? $this->participants->participantPayload($user) : [];
        $role = $participant['role'] ?? 'guest';
        $email = strtolower(trim((string) $user?->email));
        $authorEmail = strtolower(trim((string) $section->author_email));
        $isAuthor = $email !== '' && $email === $authorEmail;
        $isLecturer = in_array($role, ['lecturer', 'admin'], true);

        if (! $isAuthor && ! $isLecturer && ! $this->moderators->canModerate($user, $section, $request)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'student_content' => 'nullable|string',
        ]);

        if (! $isAuthor && ! $this->moderators->canModerate($user, $section, $request)) {
            unset($data['student_content']);
        }

        $section->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật chủ đề.',
                'section' => $user ? $this->mapSectionForBoard($section->fresh(), $user, $participant) : null,
            ]);
        }

        return redirect()->route('discussion.index', $this->redirectParams($section, $request))
            ->with('success', 'Đã cập nhật chủ đề.');
    }

    public function destroySection(Request $request, DiscussionSection $section): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $participant = $user ? $this->participants->participantPayload($user) : [];
        $role = $participant['role'] ?? 'guest';
        $email = strtolower(trim((string) $user?->email));
        $authorEmail = strtolower(trim((string) $section->author_email));
        $isAuthor = $email !== '' && $email === $authorEmail;
        $isLecturer = in_array($role, ['lecturer', 'admin'], true);

        if (! $isAuthor && ! $isLecturer && ! $this->moderators->canModerate($user, $section, $request)) {
            abort(403);
        }

        $section->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa chủ đề.']);
        }

        return redirect()->route('discussion.index')
            ->with('success', 'Đã xóa chủ đề.');
    }

    public function storeComment(Request $request, DiscussionSection $section): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:20000',
            'author_name' => 'nullable|string|max:120',
        ]);

        $user = $request->user();
        $isModerator = $this->moderators->canModerate($user, $section, $request);
        $role = $user ? $this->participants->resolveRole($user) : 'guest';

        if ($isModerator) {
            $authorName = $section->author_name ?: ($user?->name ?? 'Giảng viên');
            $authorEmail = $user?->email ?? '';
            $authorRole = 'lecturer';
        } elseif ($user && in_array($role, ['lecturer', 'admin'], true)) {
            $authorName = $user->name;
            $authorEmail = $user->email;
            $authorRole = 'lecturer';
        } else {
            $authorName = trim((string) ($data['author_name'] ?? ''));
            if ($authorName === '') {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Vui lòng nhập họ tên hoặc mã sinh viên.'], 422);
                }

                return back()->withErrors(['author_name' => 'Vui lòng nhập họ tên hoặc mã sinh viên.'])->withInput();
            }
            $authorEmail = '';
            $authorRole = 'student';
        }

        $comments = $section->commentsList();
        $comments[] = [
            'id' => Str::random(8),
            'text' => $data['content'],
            'authorName' => $authorName,
            'authorEmail' => $authorEmail,
            'authorRole' => $authorRole,
            'createdAt' => now()->toIso8601String(),
        ];
        $section->update(['comments' => $comments]);

        if ($request->wantsJson()) {
            $participant = $user ? $this->participants->participantPayload($user) : [];

            return response()->json([
                'message' => 'Đã gửi nhận xét.',
                'section' => $user ? $this->mapSectionForBoard($section->fresh(), $user, $participant) : null,
            ]);
        }

        return redirect()->route('discussion.index', $this->redirectParams($section, $request))
            ->with('success', 'Đã gửi bình luận.');
    }

    public function destroyComment(Request $request, DiscussionSection $section, string $commentId): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $isModerator = $this->moderators->canModerate($user, $section, $request);
        $participant = $user ? $this->participants->participantPayload($user) : [];
        $role = $participant['role'] ?? 'guest';
        $isLecturer = in_array($role, ['lecturer', 'admin'], true);

        $comments = collect($section->commentsList());
        $target = $comments->firstWhere('id', $commentId);
        if (! $target) {
            abort(404);
        }

        $commentEmail = strtolower(trim((string) ($target['authorEmail'] ?? '')));
        $userEmail = strtolower(trim((string) ($user?->email ?? '')));
        $isCommentAuthor = $commentEmail !== '' && $commentEmail === $userEmail;

        if (! $isModerator && ! $isLecturer && ! $isCommentAuthor) {
            abort(403);
        }

        $section->update([
            'comments' => $comments->reject(fn ($c) => ($c['id'] ?? '') === $commentId)->values()->all(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã xóa bình luận.',
                'section' => $user ? $this->mapSectionForBoard($section->fresh(), $user, $participant) : null,
            ]);
        }

        return redirect()->route('discussion.index', $this->redirectParams($section, $request))
            ->with('success', 'Đã xóa bình luận.');
    }

    /** @return array<string, string> */
    private function redirectParams(DiscussionSection $section, Request $request): array
    {
        $params = ['id' => $section->id];
        if ($token = $this->moderators->tokenFromRequest($request)) {
            $params['key'] = $token;
        }

        return $params;
    }
}
