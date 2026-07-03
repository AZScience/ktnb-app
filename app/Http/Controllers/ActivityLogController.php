<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Services\ActivityLogPresenter;
use App\Services\ActivityLogStatisticsService;
use App\Services\ReportExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private const ACTION_LABELS = [
        'LOGIN' => 'Đăng nhập',
        'LOGOUT' => 'Đăng xuất',
        'CREATE' => 'Thêm mới',
        'UPDATE' => 'Cập nhật',
        'DELETE' => 'Xóa',
        'VIEW' => 'Xem',
    ];

    public function __construct(
        private ActivityLogPresenter $presenter,
        private ActivityLogStatisticsService $statistics,
        private ReportExportService $export,
    ) {}

    public function index(): View
    {
        $employees = $this->activityLogEmployees();
        $lookup = $this->presenter->buildEmployeeLookup($employees);
        $logs = ActivityLog::query()
            ->orderByDesc('logged_at')
            ->limit(250)
            ->get()
            ->map(fn (ActivityLog $log) => $this->presenter->presentWithLookup($log, $lookup));

        return view('settings.activity-logs.index', [
            'logs' => $logs,
            'userOptions' => $this->presenter->userFilterOptions($employees),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $employees = $this->activityLogEmployees();
        $lookup = $this->presenter->buildEmployeeLookup($employees);

        $query = ActivityLog::query()->orderByDesc('logged_at');
        $this->applyExportFilters($request, $query, $employees);

        $logs = $query
            ->limit(10000)
            ->get()
            ->map(fn (ActivityLog $log) => $this->presenter->presentWithLookup($log, $lookup));

        return response()->json($this->statistics->build($logs), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function export(Request $request): StreamedResponse
    {
        $employees = $this->activityLogEmployees();
        $lookup = $this->presenter->buildEmployeeLookup($employees);

        $query = ActivityLog::query()->orderByDesc('logged_at');
        $this->applyExportFilters($request, $query, $employees);

        $rows = $query->get()->values()->map(function (ActivityLog $log, int $index) use ($lookup) {
            $presented = $this->presenter->presentWithLookup($log, $lookup);
            $actionKey = $presented['action'];

            return [
                'stt' => $index + 1,
                'formatted_time' => $presented['formattedTime'],
                'user_name' => $presented['userName'],
                'user_email' => $presented['userEmail'],
                'action' => self::ACTION_LABELS[$actionKey] ?? $actionKey,
                'module' => $presented['module'],
                'details' => $presented['details'],
                'ip_address' => $presented['ipAddress'],
            ];
        });

        return $this->export->downloadFromArrays($rows->all(), [
            'stt' => 'STT',
            'formatted_time' => 'Thời gian',
            'user_name' => 'Người dùng',
            'user_email' => 'Email',
            'action' => 'Hành động',
            'module' => 'Chức năng',
            'details' => 'Chi tiết',
            'ip_address' => 'IP',
        ], 'DS_NhatKy_'.now()->format('Ymd').'.xlsx', 'NhatKyTruyCap');
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->delete();

        return response()->json(['message' => 'Đã xóa nhật ký truy cập.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid',
        ]);

        $deleted = ActivityLog::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'message' => "Đã xóa {$deleted} nhật ký truy cập.",
            'deleted' => $deleted,
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, Employee> */
    private function activityLogEmployees(): \Illuminate\Support\Collection
    {
        $cacheKey = 'activity-log:employees:v2';

        $rows = Cache::get($cacheKey);
        if (! is_array($rows)) {
            $rows = $this->refreshActivityLogEmployeesCache($cacheKey);
        }

        return Employee::hydrate($rows);
    }

    /** @return list<array<string, mixed>> */
    private function refreshActivityLogEmployeesCache(string $cacheKey): array
    {
        Cache::forget('activity-log:employees');

        $rows = Employee::query()
            ->orderBy('name')
            ->get(['id', 'user_id', 'employee_id', 'email', 'name', 'nickname'])
            ->map(fn (Employee $employee) => $employee->getAttributes())
            ->all();

        Cache::put($cacheKey, $rows, 3600);

        return $rows;
    }

    /** @param  \Illuminate\Support\Collection<int, Employee>  $employees */
    private function applyExportFilters(Request $request, Builder $query, $employees): void
    {
        if ($from = $request->get('from')) {
            $query->whereDate('logged_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('logged_at', '<=', $to);
        }

        $action = strtoupper(trim((string) $request->get('action', '')));
        if ($action !== '' && $action !== 'ALL') {
            $query->where(function (Builder $inner) use ($action) {
                $inner->where('action', $action)
                    ->orWhere('action', 'like', '%'.$action.'%');
            });
        }

        $users = $request->get('users');
        if (is_array($users) && $users !== []) {
            $emails = $employees
                ->filter(fn (Employee $employee) => in_array($employee->id, $users, true)
                    || in_array($employee->email, $users, true)
                    || in_array($employee->employee_id, $users, true)
                    || ($employee->user_id && in_array($employee->user_id, $users, true)))
                ->pluck('email')
                ->filter()
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values()
                ->all();

            $query->where(function (Builder $inner) use ($users, $emails) {
                $inner->whereIn('user_id', $users);
                if ($emails !== []) {
                    $inner->orWhereIn('user_email', $emails);
                }
            });
        }

        $filters = $request->input('filters', []);
        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            match ($key) {
                'formattedTime' => $query->where('logged_at', 'like', '%'.$value.'%'),
                'userEmail' => $query->where('user_email', 'like', '%'.$value.'%'),
                'action' => $query->where('action', 'like', '%'.$value.'%'),
                'module' => $query->where('target_type', 'like', '%'.$value.'%'),
                'details' => $query->where('details', 'like', '%'.$value.'%'),
                'ipAddress' => $query->where('ip_address', 'like', '%'.$value.'%'),
                'userName' => $query->where(function (Builder $inner) use ($value, $employees) {
                    $inner->where('user_email', 'like', '%'.$value.'%');
                    $employeeIds = $employees
                        ->filter(fn (Employee $employee) => str_contains(
                            mb_strtolower($employee->name.' '.($employee->nickname ?? '')),
                            mb_strtolower($value),
                        ))
                        ->flatMap(fn (Employee $employee) => array_filter([
                            $employee->user_id,
                            $employee->id,
                            $employee->email,
                        ]))
                        ->unique()
                        ->values()
                        ->all();
                    if ($employeeIds !== []) {
                        $inner->orWhereIn('user_id', $employeeIds)
                            ->orWhereIn('user_email', $employeeIds);
                    }
                }),
                default => null,
            };
        }
    }
}
