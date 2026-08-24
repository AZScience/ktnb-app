<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $catalogExcel,
    ) {}

    public function index(): View
    {
        return view('personnel.departments.index', [
            'items' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);

        $department = Department::create([
            'id' => $data['department_id'],
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'head' => $data['head'] ?? '',
            'deputy_head' => $data['deputy_head'] ?? '',
            'secretary' => $data['secretary'] ?? '',
            'spokesperson' => $data['spokesperson'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm đơn vị.',
                'item' => $department,
            ]);
        }

        return redirect()->route('departments.index')->with('success', 'Đã thêm đơn vị.');
    }

    public function create(): View
    {
        return view('personnel.departments.form', ['item' => null]);
    }

    public function edit(Department $department): View
    {
        return view('personnel.departments.form', ['item' => $department]);
    }

    public function update(Request $request, Department $department): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request, $department);

        $department->update([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'head' => $data['head'] ?? '',
            'deputy_head' => $data['deputy_head'] ?? '',
            'secretary' => $data['secretary'] ?? '',
            'spokesperson' => $data['spokesperson'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật đơn vị.',
                'item' => $department->fresh(),
            ]);
        }

        return redirect()->route('departments.index')->with('success', 'Đã cập nhật đơn vị.');
    }

    public function destroy(Request $request, Department $department): JsonResponse|RedirectResponse
    {
        $department->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa đơn vị.']);
        }

        return redirect()->route('departments.index')->with('success', 'Đã xóa đơn vị.');
    }

    public function export(): StreamedResponse
    {
        $items = Department::orderBy('name')->get();

        return $this->export->download($items, [
            'department_id' => 'Mã đơn vị',
            'name' => 'Tên đơn vị',
            'head' => 'Trưởng đơn vị',
            'deputy_head' => 'Phó đơn vị',
            'secretary' => 'Thư ký',
            'spokesperson' => 'Phát ngôn',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ], 'DS_DonVi_'.now()->format('Ymd').'.xlsx');
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
            'rows.*.department_id' => 'required|string|max:50',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.head' => 'nullable|string|max:255',
            'rows.*.phone' => 'nullable|string|max:20',
            'rows.*.email' => 'nullable|string|max:255',
            'rows.*.note' => 'nullable|string',
        ]);

        foreach ($data['rows'] as $row) {
            $departmentId = trim($row['department_id']);
            $name = trim($row['name']);
            if ($departmentId === '' || $name === '') {
                continue;
            }

            $payload = [
                'name' => $name,
                'head' => $row['head'] ?? '',
                'phone' => $row['phone'] ?? '',
                'email' => $row['email'] ?? '',
                'note' => $row['note'] ?? '',
            ];

            $existing = Department::where('department_id', $departmentId)->first();
            if ($existing) {
                $existing->update($payload);
            } else {
                Department::create(array_merge($payload, [
                    'id' => $departmentId,
                    'department_id' => $departmentId,
                    'deputy_head' => '',
                    'secretary' => '',
                    'spokesperson' => '',
                ]));
            }
        }

        return response()->json([
            'message' => 'Import thành công.',
            'items' => Department::orderBy('name')->get(),
        ]);
    }

    private function validatedData(Request $request, ?Department $department = null): array
    {
        $uniqueRule = 'required|string|max:50|unique:departments,department_id';
        if ($department) {
            $uniqueRule .= ','.$department->id.',id';
        }

        return $request->validate([
            'department_id' => $uniqueRule,
            'name' => 'required|string|max:255',
            'head' => 'nullable|string|max:255',
            'deputy_head' => 'nullable|string|max:255',
            'secretary' => 'nullable|string|max:255',
            'spokesperson' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
        ]);
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->catalogExcel->parseRows($path, [
            'department_id' => 'Mã đơn vị',
            'name' => 'Tên đơn vị',
            'head' => 'Trưởng đơn vị',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ], [
            'requiredKey' => 'department_id',
            'aliases' => [
                'mã đơn vị' => 'department_id',
                'tên đơn vị' => 'name',
                'trưởng đơn vị' => 'head',
                'điện thoại' => 'phone',
                'email' => 'email',
                'ghi chú' => 'note',
            ],
        ]);
    }
}
