<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscussionSection;
use App\Models\Exam;
use App\Models\Poll;
use App\Services\DiscussionModeratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExtensionApiController extends Controller
{
    public function __construct(private DiscussionModeratorService $moderators) {}
    public function createPoll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2', 'max:20'],
            'options.*' => ['string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'attendanceList' => ['nullable', 'array', 'max:500'],
            'classId' => ['nullable', 'string', 'max:120'],
            'lecturer' => ['nullable', 'string', 'max:200'],
        ]);

        $duration = (int) ($data['duration'] ?? 10);

        $poll = Poll::create([
            'id' => (string) Str::uuid(),
            'question' => $data['question'],
            'options' => $data['options'],
            'duration' => $duration,
            'attendance_list' => $data['attendanceList'] ?? [],
            'class_id' => $data['classId'] ?? null,
            'lecturer' => $data['lecturer'] ?? null,
            'voters' => [],
            'status' => 'active',
            'end_time' => now()->addMinutes(max($duration, 1)),
        ]);

        return response()->json(['success' => true, 'id' => $poll->id])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function showPoll(string $poll): JsonResponse
    {
        $model = Poll::find($poll);
        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy bình chọn với ID: {$poll}",
                'id_checked' => $poll,
            ], 404)->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['success' => true, 'data' => $model->toApiFormat()])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function votePoll(Request $request, string $poll): JsonResponse
    {
        $data = $request->validate([
            'optionIndex' => ['required', 'integer', 'min:0', 'max:50'],
            'voterEmail' => ['nullable', 'email', 'max:255'],
            'voterName' => ['nullable', 'string', 'max:200'],
        ]);

        $model = Poll::findOrFail($poll);
        $optionIndex = (int) $data['optionIndex'];
        $voterEmail = $data['voterEmail'] ?? null;
        $voterName = $data['voterName'] ?? 'Người dùng';

        $voters = $model->voters ?? [];
        if ($voterEmail) {
            $key = str_replace('.', '_', $voterEmail);
            $voters[$key] = [
                'name' => $voterName,
                'index' => (int) $optionIndex,
                'timestamp' => now()->toIso8601String(),
            ];
            $model->update(['voters' => $voters]);
        }

        return response()->json(['success' => true])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function createExam(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'classId' => ['nullable', 'string', 'max:120'],
            'courseName' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:600'],
            'questions' => ['nullable', 'array', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $exam = Exam::create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'] ?? 'Bài kiểm tra',
            'class_id' => $data['classId'] ?? null,
            'course_name' => $data['courseName'] ?? null,
            'type' => $data['type'] ?? null,
            'duration' => (int) ($data['duration'] ?? 15),
            'questions' => $data['questions'] ?? [],
            'source' => $data['source'] ?? null,
            'active' => true,
        ]);

        return response()->json(['success' => true, 'id' => $exam->id])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function showExam(string $exam): JsonResponse
    {
        $model = Exam::find($exam);
        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài kiểm tra',
            ], 404)->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['success' => true, 'data' => $model->toApiFormat()])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function listDiscussions(): JsonResponse
    {
        $sections = DiscussionSection::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => $s->toApiFormat());

        return response()->json(['success' => true, 'data' => $sections])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function createDiscussion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sectionId' => ['nullable', 'string', 'max:64'],
            'moderatorToken' => ['nullable', 'string', 'max:255'],
            'moderator_token' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'authorName' => ['nullable', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:200'],
            'authorEmail' => ['nullable', 'email', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'studentContent' => ['nullable', 'string', 'max:20000'],
            'classId' => ['nullable', 'string', 'max:120'],
            'class' => ['nullable', 'string', 'max:120'],
        ]);

        if (! empty($data['sectionId'])) {
            $section = DiscussionSection::findOrFail($data['sectionId']);
            $token = $data['moderatorToken'] ?? $data['moderator_token'] ?? null;
            $isModerator = $token && $this->moderators->tokenIsValid($section, $token);

            $comments = $section->commentsList();
            $comment = [
                'id' => Str::random(8),
                'text' => $data['content'] ?? '',
                'authorName' => $data['authorName'] ?? $data['author'] ?? ($isModerator ? ($section->author_name ?: 'Giảng viên') : 'Sinh viên'),
                'authorEmail' => $data['authorEmail'] ?? '',
                'authorRole' => $isModerator ? 'lecturer' : 'student',
                'createdAt' => now()->toIso8601String(),
            ];
            $comments[] = $comment;
            $section->update(['comments' => $comments]);

            return response()->json(['success' => true, 'data' => $comment])
                ->header('Access-Control-Allow-Origin', '*');
        }

        $tokenPair = $this->moderators->generateTokenPair();

        $section = DiscussionSection::create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'] ?? 'Thảo luận mới',
            'student_content' => $data['studentContent'] ?? $data['content'] ?? '',
            'author_email' => '',
            'author_name' => $data['authorName'] ?? $data['author'] ?? 'Giảng viên',
            'moderator_email' => null,
            'class_id' => $data['classId'] ?? $data['class'] ?? null,
            'moderator_token_hash' => $tokenPair['hash'],
            'comments' => [],
        ]);

        return response()->json([
            'success' => true,
            'id' => $section->id,
            'moderatorToken' => $tokenPair['token'],
        ])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function generateTest(Request $request): JsonResponse
    {
        $content = $request->input('content', '');
        $count = min((int) $request->input('count', 5), 10);
        $type = $request->input('type', 'mc');

        if (! $content && ! $request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung hoặc tải file.',
            ], 422)->header('Access-Control-Allow-Origin', '*');
        }

        $snippet = Str::limit(strip_tags($content ?: 'Kiến thức giảng dạy'), 120);
        $questions = [];
        for ($i = 0; $i < max($count, 3); $i++) {
            $questions[] = [
                'question' => "Câu ".($i + 1).": Theo nội dung bài học, phát biểu nào đúng nhất? ({$snippet})",
                'options' => ['Phát biểu A', 'Phát biểu B', 'Phát biểu C', 'Phát biểu D'],
                'correct' => $i % 4,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $questions,
            'message' => 'Đề mẫu AI (stub) — tích hợp Azure OpenAI sau.',
        ])->header('Access-Control-Allow-Origin', '*');
    }
}
