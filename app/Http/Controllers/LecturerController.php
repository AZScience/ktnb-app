<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCatalogImportJob;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Position;
use App\Services\CatalogExcelService;
use App\Services\ImportProgressService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LecturerController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $catalogExcel,
        private ImportProgressService $importProgress,
    ) {}

    public function index(): View
    {
        return view('personnel.lecturers.index', [
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'items' => $this->hydratedItems(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'id' => 'required|string|max:50|unique:lecturers,id',
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
            'avatar_url' => 'nullable|string',
        ]);

        $lecturer = Lecturer::create([
            'id' => $data['id'],
            'name' => $data['name'],
            'department' => $this->resolveDepartment($data['department'] ?? ''),
            'position' => $this->resolvePosition($data['position'] ?? ''),
            'birth_date' => $data['birth_date'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'note' => $data['note'] ?? '',
            'avatar_url' => $data['avatar_url'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm giảng viên.',
                'item' => $this->hydrateItem($lecturer->fresh()),
            ]);
        }

        return redirect()->route('lecturers.index')->with('success', 'Đã thêm giảng viên.');
    }

    public function create(): View
    {
        return view('personnel.lecturers.form', [
            'item' => null,
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ]);
    }

    public function edit(Lecturer $lecturer): View
    {
        return view('personnel.lecturers.form', [
            'item' => $lecturer,
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lecturer $lecturer): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
            'avatar_url' => 'nullable|string',
        ]);

        $lecturer->update([
            'name' => $data['name'],
            'department' => $this->resolveDepartment($data['department'] ?? ''),
            'position' => $this->resolvePosition($data['position'] ?? ''),
            'birth_date' => $data['birth_date'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'note' => $data['note'] ?? '',
            'avatar_url' => $data['avatar_url'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật giảng viên.',
                'item' => $this->hydrateItem($lecturer->fresh()),
            ]);
        }

        return redirect()->route('lecturers.index')->with('success', 'Đã cập nhật giảng viên.');
    }

    public function destroy(Request $request, Lecturer $lecturer): JsonResponse|RedirectResponse
    {
        $lecturer->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa giảng viên.']);
        }

        return redirect()->route('lecturers.index')->with('success', 'Đã xóa giảng viên.');
    }

    public function export(): StreamedResponse
    {
        $rows = $this->hydratedItems()->map(fn (array $item) => [
            'id' => $item['id'],
            'name' => $item['name'],
            'department_name' => $item['department_name'],
            'position_name' => $item['position_name'],
            'birth_date' => $item['birth_date'],
            'address' => $item['address'],
            'phone' => $item['phone'],
            'email' => $item['email'],
            'note' => $item['note'],
        ]);

        return $this->export->download($rows, [
            'id' => 'Mã GV',
            'name' => 'Họ và tên',
            'department_name' => 'Đơn vị',
            'position_name' => 'Chức vụ',
            'birth_date' => 'Ngày sinh',
            'address' => 'Địa chỉ',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ], 'DS_GiangVien_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        @ini_set('memory_limit', '512M');
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $parsedRows = $this->parseSpreadsheet($request->file('file')->getPathname());
        $validRows = $this->normalizeImportRows($parsedRows);

        return response()->json([
            'rows' => $validRows,
            'total_parsed' => count($parsedRows),
            'valid_count' => count($validRows),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        @ini_set('memory_limit', '512M');
        $request->merge([
            'rows' => $this->normalizeImportRows($request->input('rows', [])),
        ]);

        $data = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.id' => 'required|string|max:50',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.department' => 'nullable|string|max:255',
            'rows.*.position' => 'nullable|string|max:100',
            'rows.*.birth_date' => 'nullable|string|max:20',
            'rows.*.address' => 'nullable|string',
            'rows.*.phone' => 'nullable|string|max:20',
            'rows.*.email' => 'nullable|string|max:255',
            'rows.*.note' => 'nullable|string',
        ]);

        if ($request->boolean('async')) {
            $progress = $this->importProgress->start(count($data['rows']), 'Import giảng viên');
            ProcessCatalogImportJob::dispatch($progress['id'], 'lecturers', $data['rows'])->afterResponse();

            return response()->json([
                'message' => 'Đang import nền...',
                'async' => true,
                'progress_id' => $progress['id'],
                'progress_url' => route('imports.status', ['id' => $progress['id']]),
            ]);
        }

        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        DB::transaction(function () use ($data) {
            foreach ($data['rows'] as $row) {
                $id = trim($row['id']);
                $name = trim($row['name']);
                if ($id === '' || $name === '') {
                    continue;
                }

                $payload = [
                    'name' => $name,
                    'department' => $this->resolveDepartment($row['department'] ?? ''),
                    'position' => $this->resolvePosition($row['position'] ?? ''),
                    'birth_date' => trim((string) ($row['birth_date'] ?? '')),
                    'address' => trim((string) ($row['address'] ?? '')),
                    'phone' => trim((string) ($row['phone'] ?? '')),
                    'email' => trim((string) ($row['email'] ?? '')),
                    'note' => trim((string) ($row['note'] ?? '')),
                ];

                Lecturer::updateOrCreate(['id' => $id], $payload);
            }
        });

        return response()->json([
            'message' => 'Import thành công.',
            'items' => $this->hydratedItems(),
        ]);
    }

    /** @return array<string, string> */
    private function lecturerImportFieldLabels(): array
    {
        return [
            'id' => 'Mã GV',
            'name' => 'Họ và tên',
            'department' => 'Đơn vị',
            'position' => 'Chức vụ',
            'birth_date' => 'Ngày sinh',
            'address' => 'Địa chỉ',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ];
    }

    private function parseSpreadsheet(string $path): array
    {
        @ini_set('memory_limit', '512M');

        return $this->catalogExcel->parseRows($path, $this->lecturerImportFieldLabels(), [
            'aliases' => [
                // Mã giảng viên
                'mã giảng viên' => 'id',
                'ma giang vien' => 'id',
                'mã gv' => 'id',
                'ma gv' => 'id',
                'magv' => 'id',
                'mã số gv' => 'id',
                'ma so gv' => 'id',
                'mã số giảng viên' => 'id',
                'ma so giang vien' => 'id',
                'mã cán bộ' => 'id',
                'ma can bo' => 'id',
                'mã cb' => 'id',
                'ma cb' => 'id',
                'mã nhân viên' => 'id',
                'ma nhan vien' => 'id',
                'mã nv' => 'id',
                'ma nv' => 'id',
                'mã số' => 'id',
                'msgv' => 'id',
                'code' => 'id',
                'lecturer id' => 'id',

                // Họ và tên
                'họ và tên' => 'name',
                'ho va ten' => 'name',
                'họ tên' => 'name',
                'ho ten' => 'name',
                'họ và tên giảng viên' => 'name',
                'tên giảng viên' => 'name',
                'họ tên giảng viên' => 'name',
                'full name' => 'name',
                'fullname' => 'name',

                // Cột họ lót + tên riêng biệt
                'họ lót' => 'last_name',
                'ho lot' => 'last_name',
                'họ và đệm' => 'last_name',
                'ho va dem' => 'last_name',
                'họ và tên đệm' => 'last_name',
                'ho va ten dem' => 'last_name',
                'họ đệm' => 'last_name',
                'ho dem' => 'last_name',
                'họ' => 'last_name',
                'ho' => 'last_name',
                'tên' => 'first_name',
                'ten' => 'first_name',

                // Khoa / Đơn vị
                'khoa' => 'department',
                'viện' => 'department',
                'khoa/viện' => 'department',
                'khoa / viện' => 'department',
                'đơn vị' => 'department',
                'don vi' => 'department',
                'đơn vị công tác' => 'department',
                'don vi cong tac' => 'department',
                'bộ môn' => 'department',
                'bo mon' => 'department',
                'tổ bộ môn' => 'department',
                'phòng ban' => 'department',
                'faculty' => 'department',

                // Chức vụ / Học hàm / Học vị
                'chức vụ' => 'position',
                'chuc vu' => 'position',
                'chức danh' => 'position',
                'chuc danh' => 'position',
                'vị trí' => 'position',
                'học vị' => 'position',
                'học hàm' => 'position',
                'học hàm/học vị' => 'position',
                'học hàm - học vị' => 'position',
                'position' => 'position',

                // Ngày sinh
                'ngày sinh' => 'birth_date',
                'ngay sinh' => 'birth_date',
                'ngaysinh' => 'birth_date',
                'ngày tháng năm sinh' => 'birth_date',
                'ngay thang nam sinh' => 'birth_date',
                'năm sinh' => 'birth_date',
                'nam sinh' => 'birth_date',
                'dob' => 'birth_date',

                // Địa chỉ
                'địa chỉ' => 'address',
                'dia chi' => 'address',
                'quê quán' => 'address',
                'hộ khẩu' => 'address',
                'nơi ở' => 'address',

                // Điện thoại
                'điện thoại' => 'phone',
                'dien thoai' => 'phone',
                'sđt' => 'phone',
                'sdt' => 'phone',
                'số điện thoại' => 'phone',
                'so dien thoai' => 'phone',
                'phone' => 'phone',
                'mobile' => 'phone',
                'di động' => 'phone',
                'di dong' => 'phone',

                // Email
                'email' => 'email',
                'e-mail' => 'email',
                'hộp thư' => 'email',
                'hop thu' => 'email',
                'mail' => 'email',

                // Ghi chú
                'ghi chú' => 'note',
                'ghi chu' => 'note',
                'note' => 'note',
            ],
            'headerRowIndices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'requiredKey' => 'id',
        ]);
    }

    /** @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeImportRows(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row) => $this->isImportableLecturerRow($row),
        ));
    }

    private function isImportableLecturerRow(array $row): bool
    {
        $id = mb_strtolower(trim((string) ($row['id'] ?? '')));
        $name = mb_strtolower(trim((string) ($row['name'] ?? '')));

        if ($id === '' || $name === '') {
            return false;
        }

        if (in_array($id, ['mã gv', 'magv', 'mã giảng viên', 'stt', 'tổng cộng', 'tổng số'], true)) {
            return false;
        }

        if (str_starts_with($id, 'tổng') || str_starts_with($name, 'tổng')) {
            return false;
        }

        if (in_array($name, ['họ và tên', 'họ tên', 'họ và tên giảng viên'], true)) {
            return false;
        }

        return true;
    }

    private function resolveDepartment(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $department = Department::find($value)
            ?? Department::where('department_id', $value)->first()
            ?? Department::where('name', $value)->first();

        return $department?->id ?? $value;
    }

    private function resolvePosition(string $value, $positionNames = null, $positionIdsByName = null): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($positionNames !== null && $positionIdsByName !== null) {
            if ($positionNames->has($value)) {
                return $value;
            }

            return (string) ($positionIdsByName->get($value) ?? $value);
        }

        $position = Position::find($value) ?? Position::where('name', $value)->first();

        return $position?->id ?? $value;
    }

    private function positionName(?string $position, $positionNames = null, $positionIdsByName = null): string
    {
        if (! $position) {
            return '';
        }

        if ($positionNames !== null && $positionIdsByName !== null) {
            if ($positionNames->has($position)) {
                return (string) $positionNames->get($position);
            }

            if ($positionIdsByName->has($position)) {
                return $position;
            }

            return $position;
        }

        $record = Position::find($position) ?? Position::where('name', $position)->first();

        return $record?->name ?? $position;
    }

    private function departmentName(?string $departmentId, $departmentNames = null): string
    {
        if (! $departmentId) {
            return '';
        }

        if ($departmentNames !== null) {
            return (string) ($departmentNames->get($departmentId) ?? $departmentId);
        }

        $department = Department::find($departmentId);

        return $department?->name ?? $departmentId;
    }

    private function hydratedItems()
    {
        $departmentNames = Department::query()->pluck('name', 'id');
        $positions = Position::query()->get(['id', 'name']);
        $positionNames = $positions->pluck('name', 'id');
        $positionIdsByName = $positions->pluck('id', 'name');

        return Lecturer::orderBy('name')->get()
            ->map(fn (Lecturer $lecturer) => $this->hydrateItem(
                $lecturer,
                $departmentNames,
                $positionNames,
                $positionIdsByName,
            ));
    }

    /**
     * @param  Collection<string, string>|null  $departmentNames
     * @param  Collection<string, string>|null  $positionNames
     * @param  Collection<string, string>|null  $positionIdsByName
     */
    private function hydrateItem(
        Lecturer $lecturer,
        $departmentNames = null,
        $positionNames = null,
        $positionIdsByName = null,
    ): array {
        if ($departmentNames === null) {
            $departmentNames = Department::query()->pluck('name', 'id');
        }

        if ($positionNames === null || $positionIdsByName === null) {
            $positions = Position::query()->get(['id', 'name']);
            $positionNames ??= $positions->pluck('name', 'id');
            $positionIdsByName ??= $positions->pluck('id', 'name');
        }

        $data = $lecturer->toArray();
        $data['department_name'] = $this->departmentName($lecturer->department, $departmentNames);
        $data['position_name'] = $this->positionName($lecturer->position, $positionNames, $positionIdsByName);
        $data['position'] = $this->resolvePosition($lecturer->position ?? '', $positionNames, $positionIdsByName);

        return $data;
    }
}
