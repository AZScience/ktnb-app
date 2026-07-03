<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCatalogImportJob;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Services\AuthLoginService;
use App\Services\CatalogTableQueryService;
use App\Services\ImportProgressService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private AuthLoginService $authLogin,
        private CatalogTableQueryService $catalogQuery,
        private ImportProgressService $importProgress,
    ) {}

    public function index(): View
    {
        return view('personnel.employees.index', [
            'roles' => Role::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'items' => collect(),
            'serverPaginated' => true,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $positions = Position::query()->get(['id', 'name'])->keyBy('id');
        $positionsByName = $positions->keyBy(fn (Position $p) => mb_strtolower(trim($p->name)));

        return $this->catalogQuery->paginate(
            $request,
            $this->visibleEmployeesQuery(),
            ['name', 'employee_id', 'email', 'nickname', 'phone'],
            fn (Employee $employee) => $this->hydrateItem($employee, $positions, $positionsByName),
        );
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);

        $employee = Employee::create([
            'id' => (string) Str::uuid(),
            'employee_id' => $data['employee_id'],
            'name' => $data['name'],
            'nickname' => $data['nickname'] ?? '',
            'position' => $this->resolvePosition($data['position'] ?? ''),
            'birth_date' => $data['birth_date'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'role_id' => $data['role_id'] ?: null,
            'email' => strtolower(trim($data['email'])),
            'note' => $data['note'] ?? '',
            'avatar_url' => $data['avatar_url'] ?? '',
        ]);

        $this->syncLoginAccount($employee);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm nhân viên.',
                'item' => $this->hydrateItem($employee->fresh('role')),
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Đã thêm nhân viên.');
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('personnel.employees.create', compact('roles'));
    }

    public function edit(Employee $employee): View
    {
        $this->assertVisibleInCatalog($employee);

        $roles = Role::orderBy('name')->get();

        return view('personnel.employees.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $this->assertVisibleInCatalog($employee);

        $data = $this->validatedData($request, $employee);

        $employee->update([
            'employee_id' => $data['employee_id'],
            'name' => $data['name'],
            'nickname' => $data['nickname'] ?? '',
            'position' => $this->resolvePosition($data['position'] ?? ''),
            'birth_date' => $data['birth_date'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'role_id' => $data['role_id'] ?: null,
            'email' => strtolower(trim($data['email'])),
            'note' => $data['note'] ?? '',
            'avatar_url' => $data['avatar_url'] ?? '',
        ]);

        $this->syncLoginAccount($employee->fresh());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật nhân viên.',
                'item' => $this->hydrateItem($employee->fresh('role')),
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Đã cập nhật nhân viên.');
    }

    public function destroy(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $this->assertVisibleInCatalog($employee);

        $employee->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa nhân viên.']);
        }

        return redirect()->route('employees.index')->with('success', 'Đã xóa nhân viên.');
    }

    public function export(Request $request): StreamedResponse
    {
        $positions = Position::query()->get(['id', 'name'])->keyBy('id');
        $positionsByName = $positions->keyBy(fn (Position $p) => mb_strtolower(trim($p->name)));

        $rows = $this->catalogQuery->collect(
            $request,
            $this->visibleEmployeesQuery(),
            ['name', 'employee_id', 'email', 'nickname', 'phone'],
            fn (Employee $employee) => [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'nickname' => $employee->nickname,
                'position_name' => $this->hydrateItem($employee, $positions, $positionsByName)['position_name'] ?? '',
                'role_name' => $employee->role?->name ?? '',
                'birth_date' => $employee->birth_date,
                'address' => $employee->address,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'note' => $employee->note,
            ],
        );

        return $this->export->download(collect($rows), [
            'employee_id' => 'Mã số',
            'name' => 'Họ và tên',
            'nickname' => 'Biệt danh',
            'position_name' => 'Chức vụ',
            'role_name' => 'Vai trò',
            'birth_date' => 'Ngày sinh',
            'address' => 'Địa chỉ',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ], 'DS_NhanVien_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([
            'rows' => $this->parseSpreadsheet($request->file('file')->getPathname()),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|string|max:50',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.email' => 'nullable|string|max:255',
            'rows.*.note' => 'nullable|string',
            'async' => 'sometimes|boolean',
        ]);

        if ($request->boolean('async') || count($data['rows']) > 25) {
            $progress = $this->importProgress->start(count($data['rows']), 'Import nhân viên');
            ProcessCatalogImportJob::dispatch($progress['id'], 'employees', $data['rows'])->afterResponse();

            return response()->json([
                'message' => 'Đang import nền...',
                'async' => true,
                'progress_id' => $progress['id'],
                'progress_url' => route('imports.status', ['id' => $progress['id']]),
            ]);
        }

        $defaultRoleId = Role::where('name', 'Nhân viên')->value('id') ?? 'staff';

        foreach ($data['rows'] as $row) {
            $employeeId = trim($row['employee_id']);
            $name = trim($row['name']);
            if ($employeeId === '' || $name === '') {
                continue;
            }

            $payload = [
                'name' => $name,
                'email' => strtolower(trim((string) ($row['email'] ?? $this->fallbackEmail($employeeId)))),
                'note' => $row['note'] ?? '',
                'role_id' => $defaultRoleId,
            ];

            $existing = Employee::where('employee_id', $employeeId)->first();
            if ($existing) {
                if ($existing->isHiddenSuperAdminAccount()) {
                    continue;
                }

                $existing->update($payload);
                $this->syncLoginAccount($existing->fresh());
            } else {
                $employee = Employee::create(array_merge($payload, [
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employeeId,
                    'nickname' => '',
                    'position' => '',
                    'birth_date' => '',
                    'address' => '',
                    'phone' => '',
                    'avatar_url' => '',
                ]));
                $this->syncLoginAccount($employee);
            }
        }

        return response()->json([
            'message' => 'Import thành công.',
            'async' => false,
        ]);
    }

    private function validatedData(Request $request, ?Employee $employee = null): array
    {
        $employeeIdRule = 'required|string|max:50|unique:employees,employee_id';
        $emailRule = 'required|email|max:255|unique:employees,email';

        if ($employee) {
            $employeeIdRule .= ','.$employee->id.',id';
            $emailRule .= ','.$employee->id.',id';
        }

        return $request->validate([
            'employee_id' => $employeeIdRule,
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'nullable|exists:roles,id',
            'email' => $emailRule,
            'note' => 'nullable|string',
            'avatar_url' => 'nullable|string',
        ]);
    }

    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        $startRow = 8;

        foreach ($sheet->getRowIterator($startRow) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            $employeeId = $cells[0] ?? '';
            if ($employeeId === '' || mb_strtolower($employeeId) === 'mã số') {
                continue;
            }

            $rows[] = [
                'employee_id' => $employeeId,
                'name' => $cells[1] ?? '',
                'email' => $cells[2] ?? '',
                'note' => $cells[3] ?? '',
            ];
        }

        if ($rows === []) {
            foreach ($sheet->toArray() as $line) {
                $employeeId = trim((string) ($line['Mã số'] ?? $line[0] ?? ''));
                if ($employeeId === '' || mb_strtolower($employeeId) === 'mã số') {
                    continue;
                }
                $rows[] = [
                    'employee_id' => $employeeId,
                    'name' => trim((string) ($line['Họ và tên'] ?? $line[1] ?? '')),
                    'email' => trim((string) ($line['Email'] ?? $line[2] ?? '')),
                    'note' => trim((string) ($line['Ghi chú'] ?? $line[3] ?? '')),
                ];
            }
        }

        return $rows;
    }

    private function resolvePosition(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $position = Position::find($value) ?? Position::where('name', $value)->first();

        return $position?->id ?? $value;
    }

    private function positionName(?string $position, $positions = null, $positionsByName = null): string
    {
        if (! $position) {
            return '';
        }

        if ($positions !== null) {
            $record = $positions->get($position)
                ?? $positionsByName?->get(mb_strtolower(trim($position)));

            return $record?->name ?? $position;
        }

        $record = Position::find($position) ?? Position::where('name', $position)->first();

        return $record?->name ?? $position;
    }

    private function fallbackEmail(string $employeeId): string
    {
        $slug = Str::slug($employeeId) ?: 'employee';

        return $slug.'@ntt.local';
    }

    private function hydratedItems()
    {
        $positions = Position::query()->get(['id', 'name'])->keyBy('id');
        $positionsByName = $positions->keyBy(fn (Position $p) => mb_strtolower(trim($p->name)));

        return $this->visibleEmployeesQuery()->get()
            ->map(fn (Employee $employee) => $this->hydrateItem($employee, $positions, $positionsByName));
    }

    private function visibleEmployeesQuery()
    {
        return Employee::query()
            ->visibleInCatalog()
            ->with('role')
            ->orderBy('name');
    }

    private function assertVisibleInCatalog(Employee $employee): void
    {
        if ($employee->isHiddenSuperAdminAccount()) {
            abort(404);
        }
    }

    private function hydrateItem(Employee $employee, $positions = null, $positionsByName = null): array
    {
        $data = $employee->toArray();
        $data['role_name'] = $employee->role?->name ?? '';
        $data['position_name'] = $this->positionName($employee->position, $positions, $positionsByName);

        return $data;
    }

    private function syncLoginAccount(?Employee $employee): void
    {
        if (! $employee || trim((string) $employee->email) === '' || trim((string) $employee->employee_id) === '') {
            return;
        }

        if ($this->authLogin->isSuperAdminEmail($employee->email)) {
            return;
        }

        $this->authLogin->provisionUserFromEmployee($employee);
    }
}
