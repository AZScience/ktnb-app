<?php

namespace App\Http\Controllers;

use App\Models\ExternalCheckin;
use App\Services\ExternalCheckinReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExternalCheckinController extends Controller
{
    public function __construct(private ExternalCheckinReviewService $review) {}

    public function index(Request $request): View
    {
        $items = ExternalCheckin::query()
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn (ExternalCheckin $item) => $this->formatRow($item));

        return view('monitoring.external-checkins.index', [
            'items' => $items,
        ]);
    }

    public function feed(): JsonResponse
    {
        $items = ExternalCheckin::query()
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn (ExternalCheckin $item) => $this->formatRow($item));

        return response()->json(['items' => $items]);
    }

    public function edit(ExternalCheckin $external_checkin): View
    {
        return view('monitoring.external-checkins.form', ['item' => $external_checkin]);
    }

    public function update(Request $request, ExternalCheckin $external_checkin): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending_review,approved,rejected',
            'actual_student_count' => 'nullable|string|max:20',
            'incident' => 'nullable|string|max:255',
            'incident_detail' => 'nullable|string',
        ]);

        $external_checkin->update($data);

        if ($data['status'] === 'approved') {
            $this->review->syncApprovedToSchedule($external_checkin->fresh());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật check-in.',
                'item' => $this->formatRow($external_checkin->fresh()),
            ]);
        }

        return redirect()->route('external-checkins.index')->with('success', 'Đã cập nhật check-in.');
    }

    public function destroy(Request $request, ExternalCheckin $external_checkin): JsonResponse|RedirectResponse
    {
        $external_checkin->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa.']);
        }

        return redirect()->route('external-checkins.index')->with('success', 'Đã xóa.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'string']);
        ExternalCheckin::query()->whereIn('id', $data['ids'])->delete();

        return response()->json(['message' => 'Đã xóa '.count($data['ids']).' bản ghi.']);
    }

    /** @return array<string, mixed> */
    private function formatRow(ExternalCheckin $item): array
    {
        $location = $item->location ?? [];

        return [
            'id' => $item->id,
            'timestamp' => $item->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'timestampRaw' => $item->created_at?->toIso8601String(),
            'room' => $item->room,
            'period' => $item->period,
            'classId' => $item->class_id,
            'className' => $item->class_name,
            'studentCount' => $item->student_count,
            'actualStudentCount' => $item->actual_student_count,
            'submittedBy' => $item->submitted_by ?: $item->lecturer,
            'submittedByEmail' => $item->submitted_by_email,
            'source' => $item->source ?: 'lecturer_portal',
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'incident' => $item->incident,
            'incidentDetail' => $item->incident_detail,
            'isNotification' => (bool) $item->is_notification,
            'status' => $item->status ?: 'pending_review',
            'photoUrls' => $item->photo_urls ?? [],
            'scheduleDate' => $item->schedule_date,
            'lecturer' => $item->lecturer,
            'building' => $item->building,
        ];
    }
}
