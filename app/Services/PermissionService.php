<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    private ?Employee $employee = null;

    private bool $resolved = false;

    private ?bool $superAdminCache = null;

    /** @var array<string, array<string, bool>>|null */
    private ?array $permissionsCache = null;

    public function employee(): ?Employee
    {
        if ($this->resolved) {
            return $this->employee;
        }

        $this->resolved = true;
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $this->employee = Employee::with('role')->where('user_id', $user->id)->first()
            ?? Employee::with('role')->where('email', $user->email)->first();

        return $this->employee;
    }

    public function isSuperAdmin(?User $user = null): bool
    {
        if ($this->superAdminCache !== null) {
            return $this->superAdminCache;
        }

        $user ??= Auth::user();
        if (! $user) {
            return $this->superAdminCache = false;
        }

        $emails = array_map('strtolower', config('nttu.super_admin_emails', []));

        return $this->superAdminCache = in_array(strtolower($user->email), $emails, true)
            || $this->employee()?->role_id === 'system';
    }

    public function permissions(): array
    {
        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        if ($this->isSuperAdmin()) {
            return $this->permissionsCache = $this->fullPermissions();
        }

        $role = $this->employee()?->role;
        if (! $role) {
            return $this->permissionsCache = $this->normalizeStoredPermissions(config('nttu.staff_defaults', []));
        }

        if ($role->id === 'system') {
            return $this->permissionsCache = $this->fullPermissions();
        }

        if (empty($role->permissions)) {
            return $this->permissionsCache = $this->defaultPermissionsForRole($role->id);
        }

        return $this->permissionsCache = $this->normalizeStoredPermissions($role->permissions);
    }

    public function can(string $module, string $action = 'access'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $perms = $this->permissions();

        foreach ($this->resolvePermissionModules($module) as $key) {
            if (! empty($perms[$key][$action])) {
                return true;
            }
        }

        return false;
    }

    public function allows(string $module, string $action = 'access'): bool
    {
        if ($this->can($module, $action)) {
            return true;
        }

        return $action === 'view' && $this->can($module, 'access');
    }

    public function fullPermissions(): array
    {
        $all = [];
        foreach ($this->matrixModuleIds() as $module) {
            $all[$module] = [
                'access' => true, 'view' => true, 'add' => true,
                'edit' => true, 'delete' => true, 'import' => true, 'export' => true,
            ];
        }

        return $all;
    }

    public function defaultPermissionsForRole(string $roleId): array
    {
        if ($roleId === 'system') {
            return $this->fullPermissions();
        }

        if ($roleId === 'controller') {
            $perms = config('nttu.staff_defaults', []);
            foreach ($perms as $module => $actions) {
                $perms[$module]['export'] = true;
            }
            $perms['/settings/access-log'] = ['access' => true, 'view' => true];

            return $this->normalizeStoredPermissions($perms);
        }

        return $this->normalizeStoredPermissions(config('nttu.staff_defaults', []));
    }

    /** @return list<string> */
    public function matrixModuleIds(): array
    {
        return collect(config('nttu.module_categories', []))
            ->flatMap(fn (array $category) => collect($category['modules'] ?? [])->pluck('id'))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function resolvePermissionModules(string $module): array
    {
        $aliases = config('nttu.permission_aliases', []);
        $keys = [$module];

        if (isset($aliases[$module])) {
            $keys[] = $aliases[$module];
        }

        foreach ($aliases as $from => $to) {
            if ($to === $module) {
                $keys[] = $from;
            }
        }

        return array_values(array_unique($keys));
    }

    /** @param  array<string, mixed>  $permissions */
    public function normalizeStoredPermissions(array $permissions): array
    {
        $aliases = config('nttu.permission_aliases', []);
        $matrixIds = array_flip($this->matrixModuleIds());
        $normalized = [];

        foreach ($permissions as $moduleId => $actions) {
            if (! is_array($actions)) {
                continue;
            }

            $canonical = $aliases[$moduleId] ?? $moduleId;
            if (! isset($matrixIds[$canonical])) {
                continue;
            }

            $modulePerms = $normalized[$canonical] ?? [];
            foreach ($actions as $action => $enabled) {
                if ($enabled) {
                    $modulePerms[$action] = true;
                }
            }

            if ($modulePerms !== []) {
                $normalized[$canonical] = $modulePerms;
            }
        }

        return $normalized;
    }
}
