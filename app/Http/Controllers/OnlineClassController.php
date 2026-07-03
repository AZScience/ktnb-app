<?php

namespace App\Http\Controllers;

use App\Models\OnlineCheckin;
use App\Services\OnlineCheckinReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineClassController extends Controller
{
    public function __construct(private OnlineCheckinReviewService $review) {}

    public function index(Request $request): View
    {
        $items = OnlineCheckin::query()
            ->orderByDesc('server_timestamp')
            ->limit(500)
            ->get()
            ->map(fn (OnlineCheckin $c) => $this->review->normalize($c))
            ->filter(function (array $item) {
                $name = strtoupper($item['className'] ?? '');

                return ! str_contains($name, 'SHCN') && ! str_contains($name, 'SINH HOẠT');
            })
            ->values();

        return view('monitoring.online-classes.index', [
            'items' => $items,
        ]);
    }

    public function feed(): JsonResponse
    {
        $items = OnlineCheckin::query()
            ->orderByDesc('server_timestamp')
            ->limit(500)
            ->get()
            ->map(fn (OnlineCheckin $c) => $this->review->normalize($c))
            ->filter(function (array $item) {
                $name = strtoupper($item['className'] ?? '');

                return ! str_contains($name, 'SHCN') && ! str_contains($name, 'SINH HOẠT');
            })
            ->values();

        return response()->json(['items' => $items]);
    }

    public function update(Request $request, OnlineCheckin $online_checkin): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending_review,approved,rejected,completed',
            'actual_student_count' => 'nullable|string|max:20',
            'incident' => 'nullable|string|max:255',
            'incident_detail' => 'nullable|string',
        ]);

        $payload = array_merge($online_checkin->payload ?? [], [
            'status' => $data['status'],
            'actualStudentCount' => $data['actual_student_count'] ?? ($online_checkin->payload['actualStudentCount'] ?? null),
            'incident' => $data['incident'] ?? ($online_checkin->payload['incident'] ?? null),
            'incidentDetail' => $data['incident_detail'] ?? ($online_checkin->payload['incidentDetail'] ?? null),
        ]);

        $online_checkin->update(['payload' => $payload, 'server_timestamp' => now()]);

        if ($data['status'] === 'approved') {
            $this->review->syncApprovedToSchedule($online_checkin->fresh());
        }

        return response()->json([
            'message' => 'Đã cập nhật báo cáo online.',
            'item' => $this->review->normalize($online_checkin->fresh()),
        ]);
    }

    public function destroy(OnlineCheckin $online_checkin): JsonResponse
    {
        $online_checkin->delete();

        return response()->json(['message' => 'Đã xóa báo cáo.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'string']);
        OnlineCheckin::query()->whereIn('id', $data['ids'])->delete();

        return response()->json(['message' => 'Đã xóa '.count($data['ids']).' báo cáo.']);
    }
}
