<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\AssetReception;
use App\Models\BuildingBlock;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Petition;
use App\Models\StudentViolation;
use App\Models\SystemParameter;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\ActivityLogService;
use App\Services\ScheduleRoomChecklistExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private ScheduleRoomChecklistExportService $roomChecklistExport,
    ) {}

    public function index(Request $request): View
    {
        $request->user()?->forceFill(['last_seen_at' => now()])->saveQuietly();

        $today = now()->format('d/m/Y');
        $todayMd = now()->format('m-d');

        $modules = [
            'online' => ['label' => 'Lớp học Online', 'icon_color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'border' => 'border-t-blue-500', 'text' => 'text-blue-700'],
            'in-person' => ['label' => 'Lớp học Trực tiếp', 'icon_color' => 'text-green-500', 'bg' => 'bg-green-50', 'border' => 'border-t-green-500', 'text' => 'text-green-700'],
            'exams' => ['label' => 'Lớp thi', 'icon_color' => 'text-purple-500', 'bg' => 'bg-purple-50', 'border' => 'border-t-purple-500', 'text' => 'text-purple-700'],
            'homeroom' => ['label' => 'Sinh hoạt CN/CVHT', 'icon_color' => 'text-orange-500', 'bg' => 'bg-orange-50', 'border' => 'border-t-orange-500', 'text' => 'text-orange-700'],
            'external-practice' => ['label' => 'Thực hành ngoài', 'icon_color' => 'text-amber-500', 'bg' => 'bg-amber-50', 'border' => 'border-t-amber-500', 'text' => 'text-amber-700'],
        ];

        $moduleCounts = [];
        $moduleHandled = [];
        $moduleBreakdowns = [];

        $todaySchedules = DailySchedule::query()
            ->forListTable()
            ->where('date', $today)
            ->orderBy('period')
            ->get();

        foreach (array_keys($modules) as $key) {
            $items = $todaySchedules->filter(fn ($item) => DailySchedule::itemMatchesModule($item, $key));
            $moduleCounts[$key] = $items->count();
            $moduleHandled[$key] = $items->filter(fn ($item) => DailySchedule::isRecorded($item))->count();
            $moduleBreakdowns[$key] = $this->buildBreakdown($items);
        }

        $scheduleLookupRows = $todaySchedules->map(fn ($item) => $this->mapScheduleRow($item))->values();
        $scheduleMasterData = [
            'buildings' => $this->scheduleBuildingOptions($todaySchedules),
        ];
        $todayIncidents = $todaySchedules
            ->filter(fn ($item) => trim((string) ($item->incident ?? '')) !== '');

        $birthdays = Employee::with(['user', 'positionRecord'])
            ->whereRaw('substr(birth_date, 6, 5) = ?', [$todayMd])
            ->orderBy('name')
            ->get();

        $announcements = Announcement::query()
            ->visibleOnDashboard()
            ->limit(10)
            ->get();

        $recentLogs = ActivityLog::orderByDesc('logged_at')->limit(10)->get();

        $visitStats = Cache::remember('dashboard:visit_stats', 60, fn () => [
            'online' => User::whereNotNull('last_seen_at')
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->count(),
            'today' => ActivityLog::whereDate('logged_at', today())->count(),
            'weekly' => ActivityLog::whereBetween('logged_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'monthly' => ActivityLog::whereBetween('logged_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ]);

        $incidentByLecturer = $todayIncidents->groupBy(fn ($item) => $item->lecturer ?: 'Chưa rõ')->map->count()->sortDesc()->take(5);
        $incidentByClass = $todayIncidents->groupBy(fn ($item) => $item->class ?: 'Chưa rõ')->map->count()->sortDesc()->take(5);

        $shiftSchedule = $this->getShiftSchedule();
        $periodSchedule = $this->getPeriodSchedule();
        $systemOverviewCharts = Cache::remember('dashboard:overview_charts', 300, fn () => $this->buildSystemOverviewCharts());
        $totals = Cache::remember('dashboard:totals', 300, fn () => [
            ['Sinh viên vi phạm', StudentViolation::count(), route('student-violations.index'), 'text-rose-600'],
            ['Giảng viên vi phạm (Việc phát sinh)', DailySchedule::whereNotNull('incident')->where('incident', '!=', '')->count(), route('reports.comprehensive'), 'text-orange-600'],
            ['Tiếp nhận tài sản', AssetReception::where('is_gratitude', false)->count(), route('asset-check.index'), 'text-pink-600'],
            ['Tiếp nhận đơn thư', Petition::count(), route('petitions.index'), 'text-red-600'],
        ]);

        return view('dashboard', [
            'today' => $today,
            'modules' => $modules,
            'moduleCounts' => $moduleCounts,
            'moduleHandled' => $moduleHandled,
            'moduleBreakdowns' => $moduleBreakdowns,
            'scheduleLookupRows' => $scheduleLookupRows,
            'scheduleMasterData' => $scheduleMasterData,
            'birthdays' => $birthdays,
            'announcements' => $announcements,
            'recentLogs' => $recentLogs,
            'visitStats' => $visitStats,
            'todayIncidents' => $todayIncidents,
            'incidentByLecturer' => $incidentByLecturer,
            'incidentByClass' => $incidentByClass,
            'maxLecturer' => max(1, (int) $incidentByLecturer->max()),
            'maxClass' => max(1, (int) $incidentByClass->max()),
            'totals' => $totals,
            'shiftSchedule' => $shiftSchedule,
            'periodSchedule' => $periodSchedule,
            'defaultPeriodScheduleUrl' => asset('images/reference/bang-tiet-hoc.png'),
            'systemOverviewCharts' => $systemOverviewCharts,
        ]);
    }

    public function scheduleLookup(Request $request): JsonResponse
    {
        $date = $this->normalizeScheduleDate($request->get('date', now()->format('d/m/Y')));
        $items = DailySchedule::query()
            ->forListTable()
            ->where('date', $date)
            ->orderBy('period')
            ->get();
        $rows = $items
            ->map(fn ($item) => $this->mapScheduleRow($item))
            ->values();

        return response()->json([
            'date' => $date,
            'rows' => $rows,
            'buildings' => $this->scheduleBuildingOptions($items),
        ]);
    }

    public function exportSchedule(Request $request): StreamedResponse
    {
        $displayDate = $this->normalizeScheduleDate($request->get('date')) ?: now()->format('d/m/Y');
        $safeDate = str_replace('/', '-', $displayDate);

        return $this->roomChecklistExport->download(
            $request,
            null,
            'LichHoc_TraCuu_'.$safeDate.'_'.date('H-i').'.xlsx',
        );
    }

    public function filterPresets(Request $request): JsonResponse
    {
        $setting = UserSetting::forUser($request->user()->id);

        return response()->json([
            'presets' => $setting->getPresets('dashboard-schedule'),
        ]);
    }

    public function saveFilterPresets(Request $request): JsonResponse
    {
        $data = $request->validate([
            'presets' => 'required|array',
            'presets.*.name' => 'required|string|max:120',
            'presets.*.filters' => 'required|array',
        ]);

        $setting = UserSetting::forUser($request->user()->id);
        $setting->setPresets('dashboard-schedule', $data['presets']);

        return response()->json([
            'message' => 'Đã lưu bộ lọc',
            'presets' => $data['presets'],
        ]);
    }

    private function mapScheduleRow(DailySchedule $item): array
    {
        return [
            'id' => $item->id,
            'date' => $item->date,
            'period' => $item->period,
            'type' => $item->type,
            'department' => $item->department,
            'class' => $item->class,
            'student_count' => $item->student_count,
            'content' => $item->content,
            'status' => $item->status,
            'note' => $item->note,
            'building' => $item->building,
            'room' => $item->room,
            'lecturer' => $item->lecturer,
            'incident' => $item->incident,
        ];
    }

    /**
     * @return list<string>
     */
    private function scheduleBuildingOptions(Collection $schedules): array
    {
        $fromCatalog = BuildingBlock::query()->orderBy('name')->pluck('name')->all();
        $fromRows = $schedules
            ->pluck('building')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->values()
            ->all();

        return collect([...$fromCatalog, ...$fromRows])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values()
            ->all();
    }

    private function normalizeScheduleDate(?string $date): string
    {
        if (! $date) {
            return now()->format('d/m/Y');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            [$y, $m, $d] = explode('-', $date);

            return sprintf('%s/%s/%s', $d, $m, $y);
        }

        return $date;
    }

    public function uploadShiftSchedule(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'shift_schedule' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, $value, $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('File không hợp lệ.');

                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension() ?: '');
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if (! in_array($extension, $allowed, true)) {
                        $fail('Chỉ hỗ trợ file ảnh hoặc PDF.');
                    }
                },
            ],
        ]);

        $file = $data['shift_schedule'];
        $storedPath = $file->store('dashboard/shift-schedules', 'local');

        $existing = $this->getShiftSchedule();
        if ($existing && ! empty($existing['path']) && Storage::disk('local')->exists($existing['path'])) {
            Storage::disk('local')->delete($existing['path']);
        }

        SystemParameter::updateOrCreate(
            ['key' => 'dashboard_shift_schedule'],
            ['value' => json_encode([
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'updated_at' => now()->toIso8601String(),
                'updated_by_user_id' => $request->user()?->id,
                'updated_by_name' => $request->user()?->name,
            ], JSON_UNESCAPED_UNICODE)]
        );

        $this->activityLog->log('Cập nhật lịch trực', 'Dashboard', $file->getClientOriginalName());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật lịch trực.',
                'shiftSchedule' => $this->getShiftSchedule(),
            ]);
        }

        return back()->with('success', 'Đã cập nhật lịch trực.');
    }

    public function uploadPeriodSchedule(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'period_schedule' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, $value, $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('File không hợp lệ.');

                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension() ?: '');
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if (! in_array($extension, $allowed, true)) {
                        $fail('Chỉ hỗ trợ file ảnh (JPG, PNG, WEBP, GIF).');
                    }
                },
            ],
        ]);

        $file = $data['period_schedule'];
        $storedPath = $file->store('dashboard/period-schedules', 'local');

        $existing = $this->getPeriodSchedule();
        if ($existing && ! empty($existing['path']) && Storage::disk('local')->exists($existing['path'])) {
            Storage::disk('local')->delete($existing['path']);
        }

        SystemParameter::updateOrCreate(
            ['key' => 'dashboard_period_schedule'],
            ['value' => json_encode([
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'updated_at' => now()->toIso8601String(),
                'updated_by_user_id' => $request->user()?->id,
                'updated_by_name' => $request->user()?->name,
            ], JSON_UNESCAPED_UNICODE)]
        );

        $this->activityLog->log('Cập nhật bảng tiết học', 'Dashboard', $file->getClientOriginalName());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật bảng tiết học.',
                'periodSchedule' => $this->getPeriodSchedule(),
            ]);
        }

        return back()->with('success', 'Đã cập nhật bảng tiết học.');
    }

    public function showPeriodSchedule(Request $request): BinaryFileResponse
    {
        $periodSchedule = $this->getPeriodSchedule();
        if (! $periodSchedule || empty($periodSchedule['path']) || ! Storage::disk('local')->exists($periodSchedule['path'])) {
            abort(404, 'Không tìm thấy file bảng tiết học.');
        }

        $path = Storage::disk('local')->path($periodSchedule['path']);
        $filename = $periodSchedule['name'] ?? basename($path);

        if ($request->boolean('download')) {
            return response()->download($path, $filename);
        }

        $mimeType = $this->resolveShiftScheduleMimeType($periodSchedule, $path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function showShiftSchedule(Request $request): BinaryFileResponse
    {
        $shiftSchedule = $this->getShiftSchedule();
        if (! $shiftSchedule || empty($shiftSchedule['path']) || ! Storage::disk('local')->exists($shiftSchedule['path'])) {
            abort(404, 'Không tìm thấy file lịch trực.');
        }

        $path = Storage::disk('local')->path($shiftSchedule['path']);
        $filename = $shiftSchedule['name'] ?? basename($path);

        if ($request->boolean('download')) {
            return response()->download($path, $filename);
        }

        $mimeType = $this->resolveShiftScheduleMimeType($shiftSchedule, $path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function sendBirthdayGreeting(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string',
        ]);

        if (! $employee->user_id) {
            return back()->with('error', 'Nhân viên này chưa có tài khoản nhận tin nhắn.');
        }

        Message::create([
            'id' => (string) Str::uuid(),
            'sender_user_id' => $request->user()->id,
            'recipient_user_ids' => [(int) $employee->user_id],
            'subject' => 'Chúc mừng sinh nhật',
            'body' => $data['body'],
            'attachments' => [],
            'is_read' => false,
            'trash_by_user_ids' => [],
            'sent_at' => now(),
        ]);

        $this->activityLog->log('Gửi lời chúc sinh nhật', 'Message', $employee->name);

        return back()->with('success', 'Đã gửi lời chúc sinh nhật.');
    }

    private function buildBreakdown(Collection $items): Collection
    {
        return $items
            ->groupBy(fn ($item) => $item->department ?: 'Chưa xác định')
            ->map(function (Collection $departmentItems) {
                return [
                    'total' => $departmentItems->count(),
                    'buildings' => $departmentItems
                        ->groupBy(fn ($item) => $item->building ?: 'Chưa xác định')
                        ->map->count()
                        ->sortDesc(),
                ];
            })
            ->sortByDesc('total');
    }

    private function buildSystemOverviewCharts(): array
    {
        $metrics = [
            'studentViolations' => ['label' => 'Sinh viên vi phạm', 'color' => '#e11d48'],
            'lecturerIncidents' => ['label' => 'Việc phát sinh (GV)', 'color' => '#ea580c'],
            'assetReceptions' => ['label' => 'Tiếp nhận tài sản', 'color' => '#db2777'],
            'petitions' => ['label' => 'Tiếp nhận đơn thư', 'color' => '#dc2626'],
        ];

        $studentByDept = $this->countGrouped(StudentViolation::query(), 'department');
        $incidentsByDept = $this->countGrouped(
            DailySchedule::query()->whereNotNull('incident')->where('incident', '!=', ''),
            'department'
        );

        $assetTotal = AssetReception::where('is_gratitude', false)->count();
        $petitionTotal = Petition::count();
        $assetsByDept = $assetTotal > 0 ? collect(['Không theo khoa' => $assetTotal]) : collect();
        $petitionsByDept = $petitionTotal > 0 ? collect(['Không theo khoa' => $petitionTotal]) : collect();

        $studentByBuilding = $this->countGrouped(StudentViolation::query(), 'building');
        $incidentsByBuilding = $this->countGrouped(
            DailySchedule::query()->whereNotNull('incident')->where('incident', '!=', ''),
            'building'
        );
        $assetsByBuilding = $this->countGrouped(
            AssetReception::query()->where('is_gratitude', false),
            'building_block'
        );
        $petitionsByBuilding = $this->countGrouped(Petition::query(), 'building_block');

        return [
            'metrics' => $metrics,
            'byDepartment' => $this->mergeOverviewChartData(
                $metrics,
                [
                    'studentViolations' => $studentByDept,
                    'lecturerIncidents' => $incidentsByDept,
                    'assetReceptions' => $assetsByDept,
                    'petitions' => $petitionsByDept,
                ]
            ),
            'byBuilding' => $this->mergeOverviewChartData(
                $metrics,
                [
                    'studentViolations' => $studentByBuilding,
                    'lecturerIncidents' => $incidentsByBuilding,
                    'assetReceptions' => $assetsByBuilding,
                    'petitions' => $petitionsByBuilding,
                ],
                12
            ),
        ];
    }

    private function countGrouped($query, string $column): Collection
    {
        // Group by bare column so MySQL ONLY_FULL_GROUP_BY (common on hosting) accepts the query.
        // Empty/null labels are normalized in PHP afterward.
        return $query
            ->selectRaw("`{$column}` as label, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->reduce(function (Collection $carry, object $row) {
                $label = trim((string) ($row->label ?? ''));
                if ($label === '') {
                    $label = 'Chưa xác định';
                }

                $carry[$label] = (int) ($carry[$label] ?? 0) + (int) $row->total;

                return $carry;
            }, collect())
            ->sortDesc();
    }

    private function mergeOverviewChartData(array $metrics, array $groups, int $limit = 10): array
    {
        $rankedLabels = collect($groups)
            ->flatMap(fn (Collection $group) => $group->keys())
            ->unique()
            ->map(function (string $label) use ($groups) {
                $total = collect($groups)->sum(fn (Collection $group) => (int) ($group[$label] ?? 0));

                return ['label' => $label, 'total' => $total];
            })
            ->sortByDesc('total')
            ->take($limit)
            ->pluck('label')
            ->values();

        $datasets = collect($metrics)->map(function (array $config, string $key) use ($groups, $rankedLabels) {
            return [
                'label' => $config['label'],
                'color' => $config['color'],
                'data' => $rankedLabels->map(fn (string $label) => (int) ($groups[$key][$label] ?? 0))->values()->all(),
            ];
        })->values()->all();

        return [
            'labels' => $rankedLabels->all(),
            'datasets' => $datasets,
        ];
    }

    private function getShiftSchedule(): ?array
    {
        return $this->getDashboardMediaParameter('dashboard_shift_schedule');
    }

    private function getPeriodSchedule(): ?array
    {
        return $this->getDashboardMediaParameter('dashboard_period_schedule');
    }

    private function getDashboardMediaParameter(string $key): ?array
    {
        $parameter = SystemParameter::where('key', $key)->first();
        if (! $parameter?->value) {
            return null;
        }

        $decoded = json_decode($parameter->value, true);
        if (! is_array($decoded) || empty($decoded['path'])) {
            return null;
        }

        if (! Storage::disk('local')->exists($decoded['path'])) {
            return null;
        }

        $decoded['preview_kind'] = $this->resolveShiftSchedulePreviewKind($decoded);

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $shiftSchedule
     */
    private function resolveShiftSchedulePreviewKind(array $shiftSchedule): string
    {
        $mime = strtolower((string) ($shiftSchedule['mime_type'] ?? ''));
        $name = strtolower((string) ($shiftSchedule['name'] ?? ''));
        $path = strtolower((string) ($shiftSchedule['path'] ?? ''));

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            return 'pdf';
        }

        if (str_ends_with($name, '.pdf') || str_ends_with($path, '.pdf')) {
            return 'pdf';
        }

        if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $name) || preg_match('/\.(jpe?g|png|gif|webp)$/i', $path)) {
            return 'image';
        }

        return 'download';
    }

    /**
     * @param  array<string, mixed>  $shiftSchedule
     */
    private function resolveShiftScheduleMimeType(array $shiftSchedule, string $path): string
    {
        $stored = strtolower(trim((string) ($shiftSchedule['mime_type'] ?? '')));
        if ($stored !== '' && $stored !== 'application/octet-stream') {
            return $stored;
        }

        $detected = mime_content_type($path);
        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        return match ($this->resolveShiftSchedulePreviewKind($shiftSchedule)) {
            'pdf' => 'application/pdf',
            'image' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
