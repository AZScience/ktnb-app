<?php

namespace App\Http\Controllers;

use App\Models\DailySchedule;
use App\Models\UserSetting;
use App\Services\ScheduleLocationService;
use App\Services\ScheduleExcelService;
use App\Services\ScheduleMasterDataService;
use App\Services\ScheduleRoomChecklistExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleController extends Controller
{
    private const PRESET_MODULE = 'schedule-settings';

    public function __construct(
        private ScheduleExcelService $excel,
        private ScheduleRoomChecklistExportService $roomChecklistExport,
        private ScheduleLocationService $locations,
        private ScheduleMasterDataService $masterDataService,
    ) {}

    public function index(): View
    {
        return view('settings.schedules.index', [
            'masterData' => $this->masterDataService->all(),
            'todayDate' => date('d/m/Y'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json(['items' => $this->listTableItems($request)]);
    }

    public function importTemplate(): StreamedResponse
    {
        return $this->excel->downloadImportTemplate('Mau_Import_LichHoc.xlsx');
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function listTableItems(?Request $request = null)
    {
        $query = DailySchedule::query()->forListTable();

        if ($request) {
            if ($date = $request->get('date')) {
                $query->where('date', $this->normalizeScheduleDate($date));
            } elseif ($request->boolean('all')) {
                $query->orderByRaw('STR_TO_DATE(date, "%d/%m/%Y") DESC')->limit(5000);
            } else {
                $query->where('date', date('d/m/Y'));
            }
        }

        return DailySchedule::sortForListTable($query->get())
            ->pipe(fn ($items) => DailySchedule::mapListTableCollection($items)->values());
    }

    public function create(): View
    {
        return view('settings.schedules.form', ['item' => null]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $schedule = DailySchedule::create(array_merge($data, ['id' => (string) Str::uuid()]));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm tiết học.',
                'item' => $schedule,
            ], 201);
        }

        return redirect()->route('schedules.index', ['date' => $data['date']])->with('success', 'Đã thêm tiết học.');
    }

    public function edit(DailySchedule $schedule): View
    {
        return view('settings.schedules.form', ['item' => $schedule]);
    }

    public function update(Request $request, DailySchedule $schedule): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $schedule->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật.',
                'item' => $schedule->fresh(),
            ]);
        }

        return redirect()->route('schedules.index', ['date' => $data['date']])->with('success', 'Đã cập nhật.');
    }

    public function destroy(Request $request, DailySchedule $schedule): JsonResponse|RedirectResponse
    {
        $date = $schedule->date;
        $id = $schedule->id;
        $schedule->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa.',
                'id' => $id,
            ]);
        }

        return redirect()->route('schedules.index', ['date' => $date])->with('success', 'Đã xóa.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $count = DailySchedule::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$count} bản ghi.",
            'count' => $count,
        ]);
    }

    public function destroyByDate(Request $request): JsonResponse
    {
        $date = $this->normalizeScheduleDate($request->validate([
            'date' => 'required|string|max:20',
        ])['date']);

        $count = DailySchedule::where('date', $date)->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$count} bản ghi ngày {$date}.",
            'count' => $count,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $displayDate = $this->normalizeScheduleDate($request->get('date')) ?: date('d/m/Y');
        $safeDate = str_replace('/', '-', $displayDate);

        return $this->roomChecklistExport->download(
            $request,
            null,
            'LichHoc_'.$safeDate.'_'.date('H-i').'.xlsx',
        );
    }

    public function importBatch(Request $request): JsonResponse
    {
        $items = collect($request->input('items', []))
            ->filter(fn (mixed $item) => is_array($item) && trim((string) ($item['date'] ?? '')) !== '')
            ->values()
            ->all();

        if ($items === []) {
            return response()->json([
                'success' => false,
                'message' => 'Không có bản ghi hợp lệ để import.',
                'imported' => 0,
                'skipped' => 0,
            ], 422);
        }

        $request->merge(['items' => $items]);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.date' => 'required|string|max:20',
            'items.*.building' => 'nullable|string|max:255',
            'items.*.room' => 'nullable|string|max:255',
            'items.*.period' => 'nullable|string|max:50',
            'items.*.type' => 'nullable|string|max:50',
            'items.*.department' => 'nullable|string|max:255',
            'items.*.class' => 'nullable|string|max:255',
            'items.*.student_count' => 'nullable|integer|min:0',
            'items.*.lecturer' => 'nullable|string|max:255',
            'items.*.proctor1' => 'nullable|string|max:255',
            'items.*.proctor2' => 'nullable|string|max:255',
            'items.*.proctor3' => 'nullable|string|max:255',
            'items.*.content' => 'nullable|string',
            'items.*.status' => 'nullable|string|max:255',
            'items.*.note' => 'nullable|string',
        ]);

        $newItems = $this->excel->filterNewImportItems($data['items']);
        if ($newItems === []) {
            return response()->json([
                'success' => false,
                'message' => 'Không có bản ghi mới để thêm.',
                'imported' => 0,
                'skipped' => count($data['items']),
            ], 422);
        }

        $created = $this->excel->createImportItems($newItems);
        $imported = count($created);
        $skipped = count($data['items']) - $imported;

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$imported} bản ghi mới.".($skipped > 0 ? " (Bỏ qua {$skipped} bản ghi trùng.)" : ''),
            'imported' => $imported,
            'skipped' => $skipped,
            'items' => $created,
        ], 201);
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|max:15360',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls.',
            'file.max' => 'File không được lớn hơn 15MB.',
        ]);

        try {
            $parsed = $this->excel->importPreview($request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Không đọc được file Excel: '.$e->getMessage(),
            ], 422);
        }

        if ($parsed['rawCount'] === 0) {
            return response()->json([
                'message' => 'Không tìm thấy dữ liệu lịch học trong file. Kiểm tra file có dòng dữ liệu bên dưới tiêu đề (cột "Ngày") từ dòng 8 trở đi.',
            ], 422);
        }

        $newItems = $this->excel->filterNewImportItems($parsed['merged']);

        return response()->json([
            'rawCount' => $parsed['rawCount'],
            'preview' => $parsed['preview'],
            'mergedCount' => count($parsed['merged']),
            'newItems' => $newItems,
            'newCount' => count($newItems),
        ]);
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'date' => 'nullable|string|max:20',
        ]);

        $date = $request->get('date', date('d/m/Y'));
        $count = $this->excel->import($request->file('file'), $date);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã import {$count} tiết học.",
                'count' => $count,
            ]);
        }

        return redirect()->route('schedules.index')
            ->with('success', "Đã import {$count} tiết học.");
    }

    public function filterPresets(Request $request): JsonResponse
    {
        $setting = UserSetting::forUser($request->user()->id);

        return response()->json([
            'presets' => $setting->getPresets(self::PRESET_MODULE),
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
        $setting->setPresets(self::PRESET_MODULE, $data['presets']);

        return response()->json([
            'message' => 'Đã lưu bộ lọc',
            'presets' => $data['presets'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
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
            'content' => 'nullable|string',
            'status' => 'nullable|string|max:255',
            'incident' => 'nullable|string|max:255',
            'incident_detail' => 'nullable|string',
            'time' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $pair = $this->locations->normalizePair($data['building'] ?? null, $data['room'] ?? null);
        $data['building'] = $pair['building'];
        $data['room'] = $pair['room'];

        return $data;
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

    /** @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function excelImportPayload(array $item): array
    {
        return [
            'date' => trim((string) ($item['date'] ?? '')),
            'building' => $this->nullable($item['building'] ?? null),
            'room' => $this->nullable($item['room'] ?? null),
            'period' => $this->nullable($item['period'] ?? null),
            'type' => $this->nullable($item['type'] ?? null),
            'department' => $this->nullable($item['department'] ?? null),
            'class' => $this->nullable($item['class'] ?? null),
            'student_count' => isset($item['student_count']) && $item['student_count'] !== '' ? (int) $item['student_count'] : null,
            'lecturer' => $this->nullable($item['lecturer'] ?? null),
            'proctor1' => $this->nullable($item['proctor1'] ?? null),
            'proctor2' => $this->nullable($item['proctor2'] ?? null),
            'proctor3' => $this->nullable($item['proctor3'] ?? null),
            'content' => $this->nullable($item['content'] ?? null),
            'status' => $this->nullable($item['status'] ?? null) ?: 'Phòng học',
            'note' => $this->nullable($item['note'] ?? null),
        ];
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
