<?php

namespace App\Http\Controllers;

use App\Models\DailySchedule;
use App\Models\ExternalCheckin;
use App\Models\IncidentCategory;
use App\Models\Recognition;
use App\Services\EvidenceStorageService;
use App\Services\LecturerPortalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LecturerPortalController extends Controller
{
    public function __construct(
        private EvidenceStorageService $storage,
        private LecturerPortalAuthService $auth,
    ) {}

    public function index(): View
    {
        $recognition = Recognition::where('name', 'like', '%Thực hành ngoài%')->first();
        $incidents = IncidentCategory::query()
            ->when($recognition, fn ($q) => $q->where('recognition_id', $recognition->id))
            ->orderBy('name')
            ->get();

        return view('lecturer-portal.index', [
            'incidents' => $incidents,
            'googleClientId' => $this->auth->clientId(),
            'googleUser' => $this->auth->publicPayload(),
            'appUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function authGoogle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'credential' => 'required|string',
        ]);

        try {
            $user = $this->auth->verifyIdToken($data['credential']);
            $this->auth->login($user);

            return response()->json([
                'success' => true,
                'user' => $this->auth->publicPayload($user),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('lecturer-portal.index');
    }

    public function me(): JsonResponse
    {
        $user = $this->auth->publicPayload();

        return response()->json([
            'success' => (bool) $user,
            'user' => $user,
        ]);
    }

    public function searchClass(Request $request): JsonResponse
    {
        $this->auth->requireUser();

        $date = $request->get('date');
        $classCode = strtoupper(trim((string) $request->get('class')));

        if (! $date || ! $classCode) {
            return response()->json(['success' => false, 'message' => 'Nhập ngày và mã lớp']);
        }

        [$y, $m, $d] = explode('-', $date);
        $formats = [$date, "{$d}/{$m}/{$y}", "{$d}-{$m}-{$y}"];

        $schedule = DailySchedule::query()
            ->where('class', $classCode)
            ->whereIn('date', $formats)
            ->first();

        if (! $schedule) {
            $mock = match ($classCode) {
                'L01' => [
                    'Class' => 'L01',
                    'Course' => 'Thực hành ngoài (Demo L01)',
                    'Lecturer' => 'GV Demo',
                    'Date' => $date,
                    'Building' => 'Ngoài trường',
                    'Room' => '—',
                    'Period' => '1-3',
                    'StudentCount' => '30',
                ],
                'L02' => [
                    'Class' => 'L02',
                    'Course' => 'Thực hành ngoài (Demo L02)',
                    'Lecturer' => 'GV Demo',
                    'Date' => $date,
                    'Building' => 'Ngoài trường',
                    'Room' => '—',
                    'Period' => '4-6',
                    'StudentCount' => '28',
                ],
                default => null,
            };

            if ($mock) {
                return response()->json(['success' => true, 'data' => $mock]);
            }

            return response()->json(['success' => false, 'message' => 'Không tìm thấy lớp vào ngày đã chọn']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'Class' => $schedule->class,
                'Course' => $schedule->content,
                'Lecturer' => $schedule->lecturer,
                'Date' => $schedule->date,
                'Building' => $schedule->building,
                'Room' => $schedule->room,
                'Period' => $schedule->period,
                'StudentCount' => $schedule->student_count,
            ],
        ]);
    }

    public function submit(Request $request): RedirectResponse|JsonResponse
    {
        $googleUser = $this->auth->requireUser();

        $data = $request->validate([
            'class_id' => 'required|string',
            'class_name' => 'nullable|string',
            'class' => 'nullable|string',
            'schedule_date' => 'required|string',
            'lecturer' => 'nullable|string',
            'building' => 'nullable|string',
            'room' => 'nullable|string',
            'period' => 'nullable|string',
            'student_count' => 'nullable|string',
            'actual_student_count' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'incident' => 'nullable|string',
            'incident_detail' => 'nullable|string',
            'is_notification' => 'nullable|boolean',
            'evidence' => 'required|string',
        ]);

        $parsed = $this->storage->parse($data['evidence']);
        if ($parsed === []) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng cung cấp ít nhất 1 minh chứng.'], 422);
            }

            return back()->withErrors(['evidence' => 'Vui lòng cung cấp ít nhất 1 minh chứng.'])->withInput();
        }

        $urls = array_values(array_filter(array_map(
            fn (array $item) => $item['url'] ?? null,
            $parsed,
        )));

        ExternalCheckin::create([
            'id' => (string) Str::uuid(),
            'class_id' => strtoupper($data['class_id']),
            'class_name' => $data['class_name'] ?? null,
            'class' => $data['class'] ?? $data['class_id'],
            'schedule_date' => $data['schedule_date'],
            'lecturer' => $data['lecturer'] ?? null,
            'building' => $data['building'] ?? null,
            'room' => $data['room'] ?? null,
            'period' => $data['period'] ?? null,
            'student_count' => $data['student_count'] ?? null,
            'actual_student_count' => $data['actual_student_count'] ?? null,
            'photo_urls' => $urls,
            'location' => ['latitude' => $data['latitude'], 'longitude' => $data['longitude']],
            'incident' => $data['incident'] ?? 'none',
            'incident_detail' => $data['incident_detail'] ?? null,
            'is_notification' => $request->boolean('is_notification'),
            'status' => 'pending_review',
            'submitted_by' => $googleUser['name'],
            'submitted_by_email' => $googleUser['email'],
            'source' => 'lecturer_portal',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Check-in thực hành ngoài đã gửi thành công!',
            ]);
        }

        return redirect()->route('lecturer-portal.index')->with('success', 'Check-in thực hành ngoài đã gửi thành công!');
    }

    public function uploadEvidence(Request $request): JsonResponse
    {
        $this->auth->requireUser();

        $request->validate([
            'file' => 'required|file|max:51200',
        ]);

        $evidence = $this->storage->storeFiles([$request->file('file')]);
        $parsed = $this->storage->parse($evidence);
        $first = $parsed[0] ?? null;

        if (! $first) {
            return response()->json(['message' => 'Không tải được tệp.'], 422);
        }

        return response()->json([
            'success' => true,
            'item' => $first['name'].':::'.$first['url'],
            'name' => $first['name'],
            'url' => $first['url'],
        ]);
    }
}
