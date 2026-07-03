<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\EvidenceAggregationService;
use App\Services\EvidenceStorageService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidenceController extends Controller
{
    public function __construct(
        private EvidenceStorageService $evidence,
        private EvidenceAggregationService $aggregation,
        private PermissionService $permissions,
        private ActivityLogService $activityLog,
    ) {}

    public function index(): View
    {
        return view('monitoring.evidence.index', [
            'pageConfig' => [
                'can_delete' => $this->permissions->can('/monitoring/evidence', 'delete'),
                'routes' => [
                    'data' => route('monitoring.evidence.data'),
                    'destroy' => url('/monitoring/evidence'),
                ],
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'items' => $this->aggregation->collect(),
            'can_delete' => $this->permissions->can('/monitoring/evidence', 'delete'),
        ]);
    }

    public function destroy(Request $request, string $evidenceId): JsonResponse
    {
        if (! $this->permissions->can('/monitoring/evidence', 'delete')) {
            return response()->json(['message' => 'Bạn không có quyền xóa minh chứng.'], 403);
        }

        if (! $this->aggregation->destroy($evidenceId)) {
            return response()->json(['message' => 'Không thể xóa minh chứng này.'], 422);
        }

        $this->activityLog->log('Xóa minh chứng', 'Evidence', $evidenceId);

        return response()->json([
            'ok' => true,
            'items' => $this->aggregation->collect(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200',
        ]);

        $evidence = $this->evidence->storeFiles([$request->file('file')]);
        $parsed = $this->evidence->parse($evidence);
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
