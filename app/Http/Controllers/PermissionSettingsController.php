<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\ActivityLogService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PermissionSettingsController extends Controller
{
    public function __construct(
        private PermissionService $permissions,
        private ActivityLogService $activityLog,
    ) {}

    public function index(): View
    {
        return view('settings.permissions.index', [
            'moduleCategories' => config('nttu.module_categories', []),
            'actions' => config('nttu.actions', []),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'id' => $this->makeUniqueId($data['name']),
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'permissions' => $this->normalizePermissionsInput($data['permissions'] ?? []),
        ]);

        $this->activityLog->log('Thêm vai trò', 'Role', "Vai trò: {$role->name}");

        return response()->json([
            'message' => 'Đã lưu thông tin vai trò.',
            'item' => $role,
        ], 201);
    }

    public function edit(Role $role): RedirectResponse
    {
        return redirect()->route('permissions.index');
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
            'permissions' => $this->normalizePermissionsInput($data['permissions'] ?? []),
        ]);

        $this->activityLog->log('Cập nhật phân quyền', 'Role', "Vai trò: {$role->name}");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã lưu thông tin vai trò.',
                'item' => $role->fresh(),
            ]);
        }

        return redirect()->route('permissions.index')->with('success', 'Đã lưu phân quyền.');
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $name = $role->name;
        $role->delete();

        $this->activityLog->log('Xóa vai trò', 'Role', "Vai trò: {$name}");

        return response()->json(['message' => 'Đã xóa vai trò.']);
    }

    public function defaults(Role $role): JsonResponse
    {
        return response()->json([
            'permissions' => $this->permissions->defaultPermissionsForRole($role->id),
        ]);
    }

    /** @param  array<string, mixed>  $input */
    private function normalizePermissionsInput(array $input): array
    {
        return $this->permissions->normalizeStoredPermissions($input);
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $id = $base;
        $n = 1;

        while (Role::where('id', $id)->exists()) {
            $id = $base.'-'.$n;
            $n++;
        }

        return $id;
    }
}
