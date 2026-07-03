<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SettingsSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsSecurityService $security,
        private ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): View
    {
        return view('settings.index', [
            'securityConfig' => $this->security->buildConfig($request),
        ]);
    }

    public function security(Request $request): JsonResponse
    {
        return response()->json($this->security->buildConfig($request));
    }

    public function destroyOtherSessions(Request $request): JsonResponse
    {
        $removed = $this->security->destroyOtherSessions($request);

        $this->activityLog->log('Thu hồi phiên đăng nhập khác', 'Auth', "Đã đăng xuất {$removed} phiên khác");

        return response()->json([
            'ok' => true,
            'removed' => $removed,
            'security' => $this->security->buildConfig($request),
        ]);
    }

    public function destroySession(Request $request, string $sessionId): JsonResponse
    {
        if (! $this->security->destroySession($request, $sessionId)) {
            return response()->json([
                'message' => 'Không thể thu hồi phiên đăng nhập này.',
            ], 422);
        }

        $this->activityLog->log('Thu hồi phiên đăng nhập', 'Auth', 'Đã đăng xuất một thiết bị');

        return response()->json([
            'ok' => true,
            'security' => $this->security->buildConfig($request),
        ]);
    }
}
