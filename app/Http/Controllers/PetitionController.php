<?php

namespace App\Http\Controllers;

use App\Models\BuildingBlock;
use App\Models\Employee;
use App\Models\Petition;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PetitionController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $excel,
    ) {}
    public function index(): View
    {
        $buildingNames = BuildingBlock::orderBy('name')->pluck('name')->values()->all();

        return view('monitoring.petitions.index', [
            'items' => Petition::orderByDesc('created_at')->get(),
            'buildingOptions' => collect($buildingNames)
                ->map(fn ($name) => ['value' => $name, 'label' => $name])
                ->values()
                ->all(),
            'staffDefault' => $this->staffDefault(),
            'assetAdvancedFilterOptions' => [
                'buildings' => $buildingNames,
                'dateLabel' => 'Ngày tiếp nhận',
                'dateField' => 'reception_date',
                'buildingFilterField' => 'building_block',
            ],
        ]);
    }

    public function create(): View
    {
        return view('monitoring.petitions.form', ['item' => null]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $petition = Petition::create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'recipient' => Employee::nicknameFor($data['recipient'] ?? $this->staffDefault()),
            'reception_date' => $data['reception_date'] ?? date('d/m/Y'),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã tiếp nhận đơn thư.',
                'item' => $petition,
            ]);
        }

        return redirect()->route('petitions.index')->with('success', 'Đã tiếp nhận đơn thư.');
    }

    public function edit(Petition $petition): View
    {
        return view('monitoring.petitions.form', ['item' => $petition]);
    }

    public function update(Request $request, Petition $petition): JsonResponse|RedirectResponse
    {
        $petition->update(array_merge($this->validated($request), [
            'recipient' => Employee::nicknameFor($request->input('recipient') ?: $petition->recipient ?: $this->staffDefault()),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật.',
                'item' => $petition->fresh(),
            ]);
        }

        return redirect()->route('petitions.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(Request $request, Petition $petition): JsonResponse|RedirectResponse
    {
        $petition->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa.']);
        }

        return redirect()->route('petitions.index')->with('success', 'Đã xóa.');
    }

    public function export(): StreamedResponse
    {
        return $this->export->download(
            Petition::orderByDesc('created_at')->get(),
            $this->excelFieldLabels(),
            'DS_TiepNhanDonThu_'.now()->format('Ymd').'.xlsx'
        );
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([
            'rows' => $this->excel->parseRows($request->file('file')->getPathname(), $this->excelFieldLabels()),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $rules = ['rows' => 'required|array|min:1', 'rows.*.citizen_name' => 'required|string|max:255'];
        foreach (array_keys($this->excelFieldLabels()) as $field) {
            if ($field !== 'citizen_name') {
                $rules['rows.*.'.$field] = 'nullable|string';
            }
        }
        $data = $request->validate($rules);
        foreach ($data['rows'] as $row) {
            $this->importRow($row);
        }

        return response()->json([
            'message' => 'Import thành công.',
            'items' => Petition::orderByDesc('created_at')->get(),
        ]);
    }

    private function excelFieldLabels(): array
    {
        return [
            'reception_date' => 'Ngày tiếp',
            'building_block' => 'Dãy nhà',
            'recipient' => 'Người tiếp',
            'citizen_name' => 'Tên công dân',
            'citizen_id' => 'CCCD',
            'citizen_address' => 'Địa chỉ',
            'citizen_phone' => 'Điện thoại',
            'summary' => 'Nội dung tóm tắt',
            'petition_type' => 'Loại đơn',
            'number_of_people' => 'Số người',
            'previous_authority' => 'Cơ quan đã giải quyết',
            'is_accepted' => 'Tiếp nhận',
            'is_returned' => 'Trả lại',
            'is_forwarded' => 'Chuyển đơn',
            'resolution_follow_up' => 'Theo dõi giải quyết',
            'note' => 'Ghi chú',
        ];
    }

    private function importRow(array $row): void
    {
        $citizenName = trim($row['citizen_name'] ?? '');
        if ($citizenName === '') {
            return;
        }

        $receptionDate = trim($row['reception_date'] ?? '') ?: date('d/m/Y');
        $existing = Petition::query()
            ->where('citizen_name', $citizenName)
            ->where('reception_date', $receptionDate)
            ->first();

        $payload = [
            'reception_date' => $receptionDate,
            'building_block' => $row['building_block'] ?? '',
            'recipient' => Employee::nicknameFor($row['recipient'] ?? $this->staffDefault()),
            'citizen_name' => $citizenName,
            'citizen_id' => $row['citizen_id'] ?? '',
            'citizen_address' => $row['citizen_address'] ?? '',
            'citizen_phone' => $row['citizen_phone'] ?? '',
            'summary' => $row['summary'] ?? '',
            'petition_type' => $row['petition_type'] ?? 'Kiến nghị',
            'number_of_people' => max(1, (int) ($row['number_of_people'] ?? 1)),
            'previous_authority' => $row['previous_authority'] ?? '',
            'is_accepted' => CatalogExcelService::toBool($row['is_accepted'] ?? ''),
            'is_returned' => CatalogExcelService::toBool($row['is_returned'] ?? ''),
            'is_forwarded' => CatalogExcelService::toBool($row['is_forwarded'] ?? ''),
            'resolution_follow_up' => $row['resolution_follow_up'] ?? '',
            'note' => $row['note'] ?? '',
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        Petition::create(array_merge($payload, [
            'id' => (string) Str::uuid(),
        ]));
    }

    private function staffDefault(): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::where('email', $user->email)->first();

        return $employee?->nickname ?: $employee?->name ?: $user->name;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'reception_date' => 'nullable|string|max:20',
            'building_block' => 'nullable|string|max:50',
            'recipient' => 'nullable|string|max:255',
            'citizen_name' => 'required|string|max:255',
            'citizen_id' => 'nullable|string|max:20',
            'citizen_address' => 'nullable|string',
            'citizen_phone' => 'nullable|string|max:20',
            'summary' => 'required|string',
            'petition_type' => 'nullable|string|max:50',
            'number_of_people' => 'nullable|integer|min:1',
            'previous_authority' => 'nullable|string|max:255',
            'is_accepted' => 'nullable|boolean',
            'is_returned' => 'nullable|boolean',
            'is_forwarded' => 'nullable|boolean',
            'resolution_follow_up' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        foreach (['is_accepted', 'is_returned', 'is_forwarded'] as $flag) {
            if ($request->has($flag)) {
                $data[$flag] = $request->boolean($flag);
            }
        }

        return $data;
    }
}
