<?php

namespace App\Http\Controllers;

use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\IncidentCategory;
use App\Models\Recognition;
use App\Models\UserSetting;
use App\Services\ActivityLogService;
use App\Services\EvidenceStorageService;
use App\Services\ScheduleExcelService;
use App\Services\ScheduleLocationService;
use App\Services\ScheduleMasterDataService;
use App\Services\ScheduleRoomChecklistExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringScheduleController extends Controller
{
    public function __construct(
        private ScheduleExcelService $excel,
        private ScheduleRoomChecklistExportService $roomChecklistExport,
        private ActivityLogService $activityLog,
        private EvidenceStorageService $evidenceStorage,
        private ScheduleLocationService $locations,
        private ScheduleMasterDataService $masterDataService,
    ) {}

    public function online(Request $request): View
    {
        return $this->index($request, 'online');
    }

    public function inPerson(Request $request): View
    {
        return $this->index($request, 'in-person');
    }

    public function exams(Request $request): View
    {
        return $this->index($request, 'exams');
    }

    public function externalPractice(Request $request): View
    {
        return $this->index($request, 'external-practice');
    }

    public function homeroom(Request $request): View
    {
        return $this->index($request, 'homeroom');
    }

    public function data(Request $request, string $module): JsonResponse
    {
        [$date, $dateNotice] = $this->resolveScheduleDate(
            $module,
            $request->get('date'),
            allowFallback: ! $request->has('date'),
        );

        $items = DailySchedule::query()
            ->forListTable()
            ->where('date', $date)
            ->forModule($module)
            ->orderBy('period')
            ->get();

        return response()->json([
            'date' => $date,
            'dateNotice' => $dateNotice,
            'items' => DailySchedule::mapListTableCollection($items)->values(),
        ]);
    }

    public function show(string $module, DailySchedule $schedule): JsonResponse
    {
        return response()->json([
            'item' => $schedule->toDetailArray(),
        ]);
    }

    public function edit(string $module, DailySchedule $schedule): View
    {
        return view('monitoring.schedules.monitor-form', [
            'item' => $schedule,
            'module' => $module,
            'title' => DailySchedule::moduleTitle($module),
            'incidentCategories' => $this->incidentCategoriesForModule($module),
        ]);
    }

    public function update(Request $request, string $module, DailySchedule $schedule): JsonResponse|RedirectResponse
    {
        $incidentRule = DailySchedule::requiresIncidentSelection($module)
            ? 'required|string|max:255'
            : 'nullable|string|max:255';

        $data = $request->validate([
            'employee' => 'nullable|string|max:255',
            'attending_students' => 'nullable|integer|min:0',
            'incident' => $incidentRule,
            'incident_detail' => 'nullable|string',
            'evidence' => 'nullable|string',
            'recognition_date' => 'nullable|string|max:20',
            'note' => 'nullable|string',
            'is_notification' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
            // Class fields — editable in add/copy modes
            'date' => 'sometimes|string|max:20',
            'building' => 'sometimes|nullable|string|max:255',
            'room' => 'sometimes|nullable|string|max:255',
            'period' => 'sometimes|nullable|string|max:50',
            'type' => 'sometimes|nullable|string|max:50',
            'department' => 'sometimes|nullable|string|max:255',
            'class' => 'sometimes|nullable|string|max:255',
            'student_count' => 'sometimes|nullable|integer|min:0',
            'lecturer' => 'sometimes|nullable|string|max:255',
            'proctor1' => 'sometimes|nullable|string|max:255',
            'proctor2' => 'sometimes|nullable|string|max:255',
            'proctor3' => 'sometimes|nullable|string|max:255',
            'content' => 'sometimes|nullable|string|max:500',
            'status' => 'sometimes|nullable|string|max:255',
        ]);

        $previous = $schedule->only(['employee', 'incident', 'attending_students', 'evidence']);

        try {
            $evidence = $this->evidenceStorage->normalizeForStorage($data['evidence'] ?? $schedule->evidence);
            if ($request->hasFile('files')) {
                $evidence = $this->evidenceStorage->mergeEvidence($evidence, $request->file('files', []));
            }
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        unset($data['evidence']);

        $classFields = [
            'date', 'building', 'room', 'period', 'type', 'department', 'class',
            'student_count', 'lecturer', 'proctor1', 'proctor2', 'proctor3', 'content', 'status',
        ];
        foreach ($classFields as $field) {
            if (! $request->exists($field)) {
                unset($data[$field]);
            }
        }

        if ($request->exists('building') || $request->exists('room')) {
            $pair = $this->locations->normalizePair(
                $request->input('building'),
                $request->input('room'),
            );
            $data['building'] = $pair['building'];
            $data['room'] = $pair['room'];
        }

        $schedule->update([
            ...$data,
            'evidence' => $evidence,
            'is_notification' => $request->boolean('is_notification'),
            'employee' => $data['employee'] ?? $this->employeeDefault(),
            'recognition_date' => $data['recognition_date'] ?? date('d/m/Y'),
        ]);

        $this->activityLog->log(
            'Ghi nhận giám sát',
            'DailySchedule',
            "Module {$module} · Lớp {$schedule->class} · Tiết {$schedule->period}",
            $previous,
            $schedule->fresh()->only(['employee', 'incident', 'attending_students', 'evidence']),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã lưu ghi nhận.',
                'item' => $schedule->fresh()->toListTableArray(),
            ]);
        }

        return redirect()->route("monitoring.{$module}.index", ['date' => $schedule->date])
            ->with('success', 'Đã cập nhật giám sát.');
    }

    public function store(Request $request, string $module): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'date' => 'required|string|max:20',
            'building' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'student_count' => 'nullable|integer|min:0',
            'lecturer' => 'nullable|string|max:255',
            'proctor1' => 'nullable|string|max:255',
            'proctor2' => 'nullable|string|max:255',
            'proctor3' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:500',
            'status' => 'nullable|string|max:255',
            'employee' => 'nullable|string|max:255',
            'attending_students' => 'nullable|integer|min:0',
            'incident' => 'nullable|string|max:255',
            'incident_detail' => 'nullable|string',
            'evidence' => 'nullable|string',
            'recognition_date' => 'nullable|string|max:20',
            'note' => 'nullable|string',
            'is_notification' => 'nullable|boolean',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ]);

        $id = 'sched-'.str_replace('-', '', $module).'-'.Str::lower(Str::random(8));

        try {
            $evidence = $this->evidenceStorage->normalizeForStorage($data['evidence'] ?? null);
            if ($request->hasFile('files')) {
                $evidence = $this->evidenceStorage->mergeEvidence($evidence, $request->file('files', []));
            }
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        unset($data['evidence']);

        $pair = $this->locations->normalizePair($data['building'] ?? null, $data['room'] ?? null);

        $schedule = DailySchedule::create([
            'id' => $id,
            'date' => $data['date'],
            'building' => $pair['building'],
            'room' => $pair['room'],
            'period' => $data['period'] ?? null,
            'type' => $data['type'] ?? null,
            'department' => $data['department'] ?? null,
            'class' => $data['class'] ?? null,
            'student_count' => $data['student_count'] ?? null,
            'lecturer' => $data['lecturer'] ?? null,
            'proctor1' => $data['proctor1'] ?? null,
            'proctor2' => $data['proctor2'] ?? null,
            'proctor3' => $data['proctor3'] ?? null,
            'content' => $data['content'] ?? null,
            'status' => $data['status'] ?? 'Phòng học',
            'employee' => $data['employee'] ?? $this->employeeDefault(),
            'attending_students' => $data['attending_students'] ?? null,
            'incident' => $data['incident'] ?? null,
            'incident_detail' => $data['incident_detail'] ?? null,
            'evidence' => $evidence,
            'recognition_date' => $data['recognition_date'] ?? date('d/m/Y'),
            'note' => $data['note'] ?? null,
            'is_notification' => $request->boolean('is_notification'),
        ]);

        $this->activityLog->log(
            'Thêm lịch giám sát',
            'DailySchedule',
            "Module {$module} · Lớp {$schedule->class} · Tiết {$schedule->period}",
            null,
            $schedule->only(['id', 'date', 'class', 'period']),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm lịch.',
                'item' => $schedule->fresh()->toListTableArray(),
            ], 201);
        }

        return redirect()->route("monitoring.{$module}.index", ['date' => $schedule->date])
            ->with('success', 'Đã thêm lịch.');
    }

    public function destroy(Request $request, string $module, DailySchedule $schedule): JsonResponse|RedirectResponse
    {
        $id = $schedule->id;
        $date = $schedule->date;
        $label = "Lớp {$schedule->class} · Tiết {$schedule->period}";

        $schedule->delete();

        $this->activityLog->log(
            'Xóa lịch giám sát',
            'DailySchedule',
            "Module {$module} · {$label}",
            ['id' => $id],
            null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa lịch.',
                'id' => $id,
            ]);
        }

        return redirect()->route("monitoring.{$module}.index", ['date' => $date])
            ->with('success', 'Đã xóa lịch.');
    }

    public function export(Request $request, string $module): StreamedResponse
    {
        $date = $this->normalizeScheduleDate($request->get('date')) ?: date('d/m/Y');
        $slug = str_replace('-', '_', $module);
        $safeDate = str_replace('/', '-', $date);

        return $this->roomChecklistExport->download(
            $request,
            fn ($query) => $query->where('date', $date)->forModule($module),
            'LichHoc_'.$slug.'_'.$safeDate.'_'.date('H-i').'.xlsx',
        );
    }

    public function import(Request $request, string $module): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'date' => 'nullable|string|max:20',
        ]);

        $count = $this->excel->import(
            $request->file('file'),
            $request->get('date', date('d/m/Y'))
        );

        $date = $request->get('date', date('d/m/Y'));

        return redirect()->route("monitoring.{$module}.index", ['date' => $date])
            ->with('success', "Đã import {$count} tiết học.");
    }

    private function index(Request $request, string $module): View
    {
        [$date, $dateNotice] = $this->resolveScheduleDate(
            $module,
            $request->get('date'),
            allowFallback: ! $request->has('date'),
        );

        return view('monitoring.schedules.index', [
            'items' => collect(),
            'date' => $date,
            'dateNotice' => $dateNotice,
            'module' => $module,
            'title' => DailySchedule::moduleTitle($module),
            'cardTitle' => DailySchedule::moduleCardTitle($module),
            'modalEntity' => DailySchedule::moduleModalEntity($module),
            'uiConfig' => DailySchedule::moduleUiConfig($module),
            'incidentCategories' => $this->incidentCategoriesForModule($module),
            'masterData' => $this->masterDataService->all(),
            'employeeDefault' => $this->employeeDefault(),
        ]);
    }

    private function employeeDefault(): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::where('email', $user->email)->first();

        return $employee?->nickname ?: $employee?->name ?: $user->name;
    }

    /**
     * @return array{0: string, 1: string|null} [date, notice if auto-fallback]
     */
    private function resolveScheduleDate(string $module, ?string $requested, bool $allowFallback = true): array
    {
        $requested = $this->normalizeScheduleDate($requested) ?: date('d/m/Y');

        $exists = DailySchedule::query()
            ->where('date', $requested)
            ->forModule($module)
            ->exists();

        if ($exists) {
            return [$requested, null];
        }

        if (! $allowFallback) {
            return [$requested, "Không có lịch ngày {$requested}."];
        }

        // Aggregate in PHP to avoid MySQL ONLY_FULL_GROUP_BY issues with ORDER BY expressions.
        $best = DailySchedule::query()
            ->forModule($module)
            ->whereNotNull('date')
            ->where('date', '!=', '')
            ->select('date')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('date')
            ->get()
            ->sortBy([
                fn ($row) => -((int) $row->cnt),
                fn ($row) => -$this->scheduleDateSortKey((string) $row->date),
            ])
            ->first();

        if ($best) {
            return [
                $best->date,
                "Không có lịch ngày {$requested}. Đang hiển thị ngày {$best->date} ({$best->cnt} bản ghi).",
            ];
        }

        return [$requested, null];
    }

    private function normalizeScheduleDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return sprintf('%s/%s/%s', $m[3], $m[2], $m[1]);
        }

        return $date;
    }

    private function scheduleDateSortKey(string $date): int
    {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $m)) {
            return (int) sprintf('%04d%02d%02d', $m[3], $m[2], $m[1]);
        }

        return 0;
    }

    private function incidentCategoriesForModule(string $module)
    {
        if ($module === 'exams') {
            $thiRecIds = Recognition::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%thi%'])
                ->pluck('id');

            if ($thiRecIds->isEmpty()) {
                return IncidentCategory::orderBy('name')->get();
            }

            $filtered = IncidentCategory::whereIn('recognition_id', $thiRecIds)->orderBy('name')->get();

            return $filtered->isNotEmpty() ? $filtered : IncidentCategory::orderBy('name')->get();
        }

        $recognitionName = DailySchedule::moduleRecognitionName($module);
        if (! $recognitionName) {
            return IncidentCategory::orderBy('name')->get();
        }

        $recognition = Recognition::where('name', $recognitionName)->first();
        if (! $recognition) {
            return IncidentCategory::orderBy('name')->get();
        }

        $filtered = IncidentCategory::where('recognition_id', $recognition->id)->orderBy('name')->get();

        return $filtered->isNotEmpty() ? $filtered : IncidentCategory::orderBy('name')->get();
    }

    public function filterPresets(Request $request, string $module): JsonResponse
    {
        $setting = UserSetting::forUser($request->user()->id);

        return response()->json([
            'presets' => $setting->getPresets($module),
        ]);
    }

    public function saveFilterPresets(Request $request, string $module): JsonResponse
    {
        $data = $request->validate([
            'presets' => 'required|array',
            'presets.*.name' => 'required|string|max:120',
            'presets.*.filters' => 'required|array',
        ]);

        $setting = UserSetting::forUser($request->user()->id);
        $setting->setPresets($module, $data['presets']);

        return response()->json([
            'message' => 'Đã lưu bộ lọc',
            'presets' => $data['presets'],
        ]);
    }
}
