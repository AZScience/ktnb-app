<?php

namespace App\Http\Controllers;

use App\Models\AssetReception;
use App\Models\Employee;
use App\Services\BuildingBlockOptionService;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetReceptionController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $excel,
        private BuildingBlockOptionService $buildingBlocks,
    ) {}

    public function index(): View
    {
        return view('monitoring.asset-receptions.index', [
            'items' => AssetReception::orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('monitoring.asset-receptions.form', ['item' => null]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $staffDefault = $this->staffDefault();
        $item = AssetReception::create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'entry_number' => ($data['entry_number'] ?? '') ?: $this->nextReceptionEntryNumber(),
            'building_block' => $this->buildingBlocks->normalizeStoredValue(
                ($data['building_block'] ?? '') ?: ($data['giver_unit'] ?? '')
            ) ?? '',
            'receiving_staff' => $this->normalizeStaff(($data['receiving_staff'] ?? '') ?: $staffDefault),
            'return_staff' => $this->normalizeStaff($data['return_staff'] ?? null),
            'gratitude_staff' => $this->normalizeStaff($data['gratitude_staff'] ?? null),
            'reception_date' => $data['reception_date'] ?? date('d/m/Y'),
            'return_status' => $data['return_status'] ?? 'Chưa trả',
            'is_gratitude' => $request->boolean('is_gratitude'),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã tiếp nhận tài sản.',
                'item' => $item,
            ]);
        }

        return redirect()->route('asset-check.index')->with('success', 'Đã tiếp nhận tài sản.');
    }

    public function edit(AssetReception $asset_reception): View
    {
        return view('monitoring.asset-receptions.form', ['item' => $asset_reception]);
    }

    public function update(Request $request, AssetReception $asset_reception): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $staffDefault = $this->staffDefault();

        $asset_reception->update(array_merge($data, [
            'building_block' => $this->buildingBlocks->normalizeStoredValue(
                ($data['building_block'] ?? '') ?: ($data['giver_unit'] ?? $asset_reception->building_block)
            ) ?? $asset_reception->building_block,
            'receiving_staff' => $this->normalizeStaff($asset_reception->receiving_staff ?: (($data['receiving_staff'] ?? '') ?: $staffDefault)),
            'return_staff' => array_key_exists('return_staff', $data)
                ? $this->normalizeStaff(($data['return_staff'] ?? '') ?: $asset_reception->return_staff)
                : $this->normalizeStaff($asset_reception->return_staff),
            'gratitude_staff' => array_key_exists('gratitude_staff', $data)
                ? $this->normalizeStaff(($data['gratitude_staff'] ?? '') ?: $asset_reception->gratitude_staff)
                : $this->normalizeStaff($asset_reception->gratitude_staff),
            'is_gratitude' => $request->has('is_gratitude')
                ? $request->boolean('is_gratitude')
                : $asset_reception->is_gratitude,
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $request->input('return_status') === 'Đã trả'
                    ? 'Đã giao trả tài sản thành công.'
                    : 'Đã cập nhật.',
                'item' => $asset_reception->fresh(),
            ]);
        }

        return redirect()->route('asset-check.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(Request $request, AssetReception $asset_reception): JsonResponse|RedirectResponse
    {
        $asset_reception->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa.']);
        }

        return redirect()->route('asset-check.index')->with('success', 'Đã xóa.');
    }

    public function export(Request $request): StreamedResponse
    {
        $tab = $this->normalizeTab($request->get('tab', 'reception'));

        return $this->export->download(
            $this->queryForTab($tab)->orderByDesc('created_at')->get(),
            $this->excelFieldLabels($tab),
            $this->exportFilename($tab)
        );
    }

    public function importPreview(Request $request): JsonResponse
    {
        $tab = $this->normalizeTab($request->get('tab', 'reception'));
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([
            'rows' => $this->excel->parseRows(
                $request->file('file')->getPathname(),
                $this->excelFieldLabels($tab)
            ),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $tab = $this->normalizeTab($request->get('tab', 'reception'));
        $rules = ['rows' => 'required|array|min:1'];
        foreach (array_keys($this->excelFieldLabels($tab)) as $field) {
            $rules['rows.*.'.$field] = 'nullable|string';
        }
        if ($tab === 'reception') {
            $rules['rows.*.giver_name'] = 'required|string|max:255';
            $rules['rows.*.content'] = 'required|string';
        }

        $data = $request->validate($rules);
        foreach ($data['rows'] as $row) {
            $this->importRow($row, $tab);
        }

        return response()->json([
            'message' => 'Import thành công.',
            'items' => $this->queryForTab($tab)->orderByDesc('created_at')->get(),
        ]);
    }

    private function normalizeTab(?string $tab): string
    {
        return in_array($tab, ['reception', 'return', 'gratitude'], true) ? $tab : 'reception';
    }

    private function queryForTab(string $tab)
    {
        return match ($tab) {
            'return' => AssetReception::where('return_status', 'Chưa trả'),
            'gratitude' => AssetReception::where('return_status', 'Đã trả'),
            default => AssetReception::query(),
        };
    }

    private function exportFilename(string $tab): string
    {
        $prefix = match ($tab) {
            'return' => 'DS_TraoTraTaiSan_',
            'gratitude' => 'DS_NguoiTotViecTot_',
            default => 'DS_TiepNhanTaiSan_',
        };

        return $prefix.now()->format('Ymd').'.xlsx';
    }

    private function excelFieldLabels(string $tab): array
    {
        return match ($tab) {
            'return' => [
                'entry_number' => 'Số tiếp nhận',
                'reception_date' => 'Ngày tiếp',
                'giver_name' => 'Người giao',
                'content' => 'Nội dung',
                'receiver_name' => 'Người nhận lại',
                'receiver_id' => 'MSSV/CCCD người nhận',
                'receiver_class' => 'Lớp người nhận',
                'receiver_unit' => 'Khoa/đơn vị người nhận',
                'receiver_phone' => 'Điện thoại người nhận',
                'resolution_date' => 'Ngày trả',
                'return_status' => 'Trạng thái trả',
                'return_staff' => 'Cán bộ bàn giao',
                'return_witness' => 'Người chứng kiến',
                'return_asset_state' => 'Tình trạng tài sản',
                'receiver_feedback' => 'Ý kiến người nhận',
                'note' => 'Ghi chú',
            ],
            'gratitude' => [
                'gratitude_number' => 'Số thư tri ân',
                'entry_number' => 'Số KTNB',
                'giver_name' => 'Người nhận quà',
                'giver_id' => 'MSSV/CCCD',
                'giver_class' => 'Lớp',
                'giver_unit' => 'Khoa/đơn vị',
                'giver_phone' => 'Điện thoại',
                'content' => 'Nội dung',
                'gratitude_gift' => 'Quà tặng',
                'gratitude_date' => 'Ngày phát quà',
                'gratitude_staff' => 'Cán bộ phát quà',
                'return_status' => 'Trạng thái',
                'reception_date' => 'Ngày tiếp',
                'note' => 'Ghi chú',
            ],
            default => [
                'entry_number' => 'Số tiếp nhận',
                'reception_date' => 'Ngày tiếp',
                'building_block' => 'Dãy nhà',
                'giver_name' => 'Người giao',
                'giver_employee_code' => 'Mã nhân viên',
                'giver_id' => 'Mã số Sinh viên',
                'giver_class' => 'Lớp',
                'giver_unit' => 'Khoa/đơn vị',
                'giver_phone' => 'Điện thoại',
                'content' => 'Nội dung',
                'asset_state' => 'Tình trạng tài sản',
                'return_status' => 'Trạng thái trả',
                'receiving_staff' => 'Cán bộ tiếp nhận',
                'witness' => 'Người chứng kiến',
                'note' => 'Ghi chú',
            ],
        };
    }

    private function importRow(array $row, string $tab): void
    {
        match ($tab) {
            'return' => $this->importReturnRow($row),
            'gratitude' => $this->importGratitudeRow($row),
            default => $this->importReceptionRow($row),
        };
    }

    private function importReceptionRow(array $row): void
    {
        $giverName = trim($row['giver_name'] ?? '');
        $content = trim($row['content'] ?? '');
        if ($giverName === '' || $content === '') {
            return;
        }

        $entryNumber = trim($row['entry_number'] ?? '');
        $existing = $entryNumber !== ''
            ? AssetReception::where('entry_number', $entryNumber)->where('is_gratitude', false)->first()
            : null;

        $payload = [
            'entry_number' => $entryNumber ?: null,
            'reception_date' => $row['reception_date'] ?? date('d/m/Y'),
            'building_block' => $this->buildingBlocks->normalizeStoredValue(
                $row['building_block'] ?? ($row['giver_unit'] ?? '')
            ) ?? '',
            'giver_name' => $giverName,
            'giver_employee_code' => $row['giver_employee_code'] ?? '',
            'giver_id' => $row['giver_id'] ?? '',
            'giver_class' => $row['giver_class'] ?? '',
            'giver_unit' => $row['giver_unit'] ?? '',
            'giver_phone' => $row['giver_phone'] ?? '',
            'content' => $content,
            'asset_state' => $row['asset_state'] ?? '',
            'return_status' => $row['return_status'] ?? 'Chưa trả',
            'receiving_staff' => $this->normalizeStaff($row['receiving_staff'] ?? $this->staffDefault()),
            'witness' => $row['witness'] ?? '',
            'note' => $row['note'] ?? '',
            'is_gratitude' => false,
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        AssetReception::create(array_merge($payload, [
            'id' => (string) Str::uuid(),
        ]));
    }

    private function importReturnRow(array $row): void
    {
        $entryNumber = trim($row['entry_number'] ?? '');
        if ($entryNumber === '') {
            return;
        }

        $existing = AssetReception::where('entry_number', $entryNumber)->where('is_gratitude', false)->first();
        if (! $existing) {
            return;
        }

        $existing->update([
            'receiver_name' => $row['receiver_name'] ?? $existing->receiver_name,
            'receiver_id' => $row['receiver_id'] ?? $existing->receiver_id,
            'receiver_class' => $row['receiver_class'] ?? $existing->receiver_class,
            'receiver_unit' => $row['receiver_unit'] ?? $existing->receiver_unit,
            'receiver_phone' => $row['receiver_phone'] ?? $existing->receiver_phone,
            'resolution_date' => $row['resolution_date'] ?? $existing->resolution_date,
            'return_status' => $row['return_status'] ?? 'Đã trả',
            'return_staff' => $this->normalizeStaff($row['return_staff'] ?? $existing->return_staff ?: $this->staffDefault()),
            'return_witness' => $row['return_witness'] ?? $existing->return_witness,
            'return_asset_state' => $row['return_asset_state'] ?? $existing->return_asset_state,
            'receiver_feedback' => $row['receiver_feedback'] ?? $existing->receiver_feedback,
            'note' => $row['note'] ?? $existing->note,
        ]);
    }

    private function importGratitudeRow(array $row): void
    {
        $entryNumber = trim($row['entry_number'] ?? '');
        $gratitudeNumber = trim($row['gratitude_number'] ?? '');
        $existing = null;
        if ($gratitudeNumber !== '') {
            $existing = AssetReception::where('gratitude_number', $gratitudeNumber)->where('is_gratitude', true)->first();
        }
        if (! $existing && $entryNumber !== '') {
            $existing = AssetReception::where('entry_number', $entryNumber)->first();
        }
        if (! $existing) {
            return;
        }

        $existing->update([
            'is_gratitude' => true,
            'gratitude_number' => $gratitudeNumber ?: $existing->gratitude_number,
            'gratitude_gift' => $row['gratitude_gift'] ?? $existing->gratitude_gift,
            'gratitude_date' => $row['gratitude_date'] ?? $existing->gratitude_date,
            'gratitude_staff' => $this->normalizeStaff($row['gratitude_staff'] ?? $existing->gratitude_staff ?: $this->staffDefault()),
            'gratitude_status' => $row['gratitude_status'] ?? $existing->gratitude_status,
            'note' => $row['note'] ?? $existing->note,
        ]);
    }

    private function validated(Request $request): array
    {
        $completingReturn = $request->input('asset_action') === 'return';

        return $request->validate([
            'asset_action' => 'nullable|string|max:20',
            'entry_number' => 'nullable|string|max:50',
            'reception_date' => 'nullable|string|max:20',
            'building_block' => 'nullable|string|max:50',
            'giver_name' => $request->isMethod('post') ? 'required|string|max:255' : 'nullable|string|max:255',
            'giver_employee_code' => 'nullable|string|max:50',
            'giver_id' => 'nullable|string|max:50',
            'giver_class' => 'nullable|string|max:50',
            'giver_unit' => 'nullable|string|max:255',
            'giver_phone' => 'nullable|string|max:20',
            'content' => $request->isMethod('post') ? 'required|string' : 'nullable|string',
            'evidence' => 'nullable|string',
            'asset_state' => 'nullable|string|max:255',
            'return_status' => 'nullable|string|max:50',
            'is_gratitude' => 'nullable|boolean',
            'receiving_staff' => 'nullable|string|max:255',
            'witness' => 'nullable|string|max:255',
            'resolution_date' => 'nullable|string|max:20',
            'return_staff' => 'nullable|string|max:255',
            'receiver_name' => $completingReturn ? 'required|string|max:255' : 'nullable|string|max:255',
            'receiver_id' => $completingReturn ? 'required|string|max:50' : 'nullable|string|max:50',
            'receiver_class' => 'nullable|string|max:50',
            'receiver_unit' => $completingReturn ? 'required|string|max:255' : 'nullable|string|max:255',
            'receiver_phone' => $completingReturn ? 'required|string|max:20' : 'nullable|string|max:20',
            'return_asset_state' => 'nullable|string|max:255',
            'receiver_feedback' => 'nullable|string',
            'return_witness' => 'nullable|string|max:255',
            'return_evidence' => 'nullable|string',
            'gratitude_number' => 'nullable|string|max:50',
            'gratitude_gift' => 'nullable|string|max:255',
            'gratitude_date' => 'nullable|string|max:20',
            'gratitude_staff' => 'nullable|string|max:255',
            'gratitude_status' => 'nullable|string|max:50',
            'gratitude_evidence' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
    }

    private function staffDefault(): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::where('email', $user->email)->first();

        return Employee::nicknameFor($employee?->nickname ?: $employee?->name ?: $user->name);
    }

    private function normalizeStaff(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return Employee::nicknameFor($value);
    }

    private function nextReceptionEntryNumber(): string
    {
        $year = date('Y');
        $maxNum = 0;

        AssetReception::query()
            ->where('is_gratitude', false)
            ->whereNotNull('entry_number')
            ->pluck('entry_number')
            ->each(function (string $entry) use ($year, &$maxNum) {
                if (preg_match('/^(?:KTNB-|TT-)(\d+)\/(\d{4})$/i', trim($entry), $matches) && $matches[2] === $year) {
                    $maxNum = max($maxNum, (int) $matches[1]);
                }
            });

        return sprintf('KTNB-%04d/%s', $maxNum + 1, $year);
    }
}
