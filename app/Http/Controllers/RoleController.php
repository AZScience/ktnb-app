<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    private const SYSTEM_ROLES = ['system', 'controller', 'staff'];

    public function __construct(private ReportExportService $export) {}

    public function index(): View
    {
        return view('personnel.roles.index', ['items' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $item = Role::create([
            'id' => $this->makeUniqueId($data['name']),
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'permissions' => [],
        ]);
        return $this->respond($request, $item, 'Đã thêm vai trò.', 'roles.index');
    }

    public function create(): View { return view('personnel.roles.form', ['item' => null]); }

    public function edit(Role $role): View
    {
        $permissionService = app(\App\Services\PermissionService::class);
        return view('personnel.roles.form', [
            'item' => $role,
            'modules' => collect(config('nttu.module_categories', []))
                ->flatMap(fn (array $category) => $category['modules'] ?? [])
                ->mapWithKeys(fn (array $module) => [$module['id'] => $module['label']])
                ->all(),
            'actions' => config('nttu.actions', []),
            'permissions' => array_merge($permissionService->defaultPermissionsForRole($role->id), $role->permissions ?? []),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);
        $role->update([
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'permissions' => $request->has('permissions') ? ($data['permissions'] ?? []) : $role->permissions,
        ]);
        return $this->respond($request, $role->fresh(), 'Đã cập nhật vai trò.', 'roles.index');
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        if (in_array($role->id, self::SYSTEM_ROLES, true)) {
            if ($request->wantsJson()) return response()->json(['message' => 'Không thể xóa vai trò hệ thống.'], 422);
            return back()->with('error', 'Không thể xóa vai trò hệ thống.');
        }
        $role->delete();
        if ($request->wantsJson()) return response()->json(['message' => 'Đã xóa vai trò.']);
        return redirect()->route('roles.index')->with('success', 'Đã xóa vai trò.');
    }

    public function export(): StreamedResponse
    {
        return $this->export->download(Role::orderBy('name')->get(), ['name' => 'Tên vai trò', 'note' => 'Ghi chú'], 'DS_VaiTro_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        return response()->json(['rows' => $this->parseSpreadsheet($request->file('file')->getPathname())]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate(['rows' => 'required|array|min:1', 'rows.*.name' => 'required|string|max:255', 'rows.*.note' => 'nullable|string']);
        foreach ($data['rows'] as $row) { $this->importRow($row); }
        return response()->json(['message' => 'Import thành công.', 'items' => Role::orderBy('name')->get()]);
    }

    private function importRow(array $row): void
    {
        $name = trim($row['name']);
        if ($name === '') return;
        $existing = Role::where('name', $name)->first();
        if ($existing) { $existing->update(['note' => $row['note'] ?? '']); return; }
        Role::create(['id' => $this->makeUniqueId($name), 'name' => $name, 'note' => $row['note'] ?? '', 'permissions' => []]);
    }

    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        foreach ($sheet->getRowIterator(8) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) { $cells[] = trim((string) $cell->getValue()); }
            $name = $cells[0] ?? '';
            if ($name === '' || in_array(mb_strtolower($name), ['tên vai trò', 'vai trò'], true)) continue;
            $rows[] = ['name' => $name, 'note' => $cells[1] ?? ''];
        }
        if ($rows !== []) return $rows;
        foreach ($sheet->toArray() as $line) {
            $name = trim((string) ($line['Tên vai trò'] ?? $line['Vai trò'] ?? $line[0] ?? ''));
            if ($name === '' || in_array(mb_strtolower($name), ['tên vai trò', 'vai trò'], true)) continue;
            $rows[] = ['name' => $name, 'note' => trim((string) ($line['Ghi chú'] ?? $line[1] ?? ''))];
        }
        return $rows;
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $id = $base; $n = 1;
        while (Role::where('id', $id)->exists()) { $id = $base.'-'.$n; $n++; }
        return $id;
    }

    private function respond(Request $request, Role $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) return response()->json(['message' => $message, 'item' => $item]);
        return redirect()->route($route)->with('success', $message);
    }
}
