<?php

namespace App\Services;

use App\Models\ProjectFilePathPermission;
use App\Models\Role;
use Illuminate\Support\Collection;

class ProjectFilePermissionService
{
    public const ACTIONS = ['view', 'add', 'edit', 'delete', 'download'];

    public function __construct(
        private PermissionService $permissions,
    ) {}

    public function canManagePermissions(): bool
    {
        if ($this->permissions->isSuperAdmin()) {
            return true;
        }

        return $this->permissions->can('/settings/project-files', 'edit')
            || $this->permissions->can('/settings/project-files', 'access');
    }

    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');

        return $path === '.' ? '' : $path;
    }

    /** @return list<string> */
    public function ancestorPaths(string $relativePath): array
    {
        $normalized = $this->normalizePath($relativePath);
        $paths = [''];
        if ($normalized === '') {
            return $paths;
        }

        $segments = explode('/', $normalized);
        for ($i = 1, $count = count($segments); $i <= $count; $i++) {
            $paths[] = implode('/', array_slice($segments, 0, $i));
        }

        return $paths;
    }

  public function hasExplicitRules(string $relativePath): bool
  {
      return ProjectFilePathPermission::query()
          ->where('path', $this->normalizePath($relativePath))
          ->exists();
  }

    public function resolveRuleSourcePath(string $relativePath): ?string
    {
        foreach (array_reverse($this->ancestorPaths($relativePath)) as $path) {
            if ($this->hasExplicitRules($path)) {
                return $path;
            }
        }

        return null;
    }

    /** @return Collection<int, ProjectFilePathPermission> */
    public function rulesForSourcePath(?string $sourcePath): Collection
    {
        if ($sourcePath === null) {
            return collect();
        }

        return ProjectFilePathPermission::query()
            ->where('path', $sourcePath)
            ->get();
    }

    public function canPathAction(string $relativePath, string $action): bool
    {
        if ($this->permissions->isSuperAdmin()) {
            return true;
        }

        if (! $this->permissions->can('/settings/project-files', 'access')) {
            return false;
        }

        $sourcePath = $this->resolveRuleSourcePath($relativePath);
        if ($sourcePath === null) {
            return $this->moduleAllowsAction($action);
        }

        $roleId = $this->permissions->employee()?->role_id;
        if (! $roleId) {
            return false;
        }

        $grant = $this->rulesForSourcePath($sourcePath)->firstWhere('role_id', $roleId);
        if (! $grant) {
            return false;
        }

        return ! empty($grant->permissions[$action]);
    }

    public function assertPathAction(string $relativePath, string $action): void
    {
        if (! $this->canPathAction($relativePath, $action)) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này trên mục này.');
        }
    }

    public function assertCanManagePermissions(): void
    {
        if (! $this->canManagePermissions()) {
            abort(403, 'Bạn không có quyền cấu hình phân quyền file.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function filterVisibleItems(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item) => $this->canPathAction((string) ($item['path'] ?? ''), 'view'),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function annotateItems(array $items): array
    {
        return array_map(function (array $item) {
            $path = (string) ($item['path'] ?? '');
            $item['has_custom_permissions'] = $this->hasExplicitRules($path);
            $item['permissions'] = $this->itemCapabilities($path);

            return $item;
        }, $items);
    }

    /**
     * @return array<string, bool>
     */
    public function directoryCapabilities(string $relativePath): array
    {
        return [
            'view' => $this->canPathAction($relativePath, 'view'),
            'add' => $this->canPathAction($relativePath, 'add'),
            'edit' => $this->canPathAction($relativePath, 'edit'),
            'delete' => $this->canPathAction($relativePath, 'delete'),
            'download' => $this->canPathAction($relativePath, 'download'),
            'manage_permissions' => $this->canManagePermissions()
                && $this->canPathAction($relativePath, 'edit'),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function itemCapabilities(string $relativePath): array
    {
        return [
            'view' => $this->canPathAction($relativePath, 'view'),
            'add' => $this->canPathAction($relativePath, 'add'),
            'edit' => $this->canPathAction($relativePath, 'edit'),
            'delete' => $this->canPathAction($relativePath, 'delete'),
            'download' => $this->canPathAction($relativePath, 'download'),
        ];
    }

    /**
     * @return array{
     *     path: string,
     *     inherit: bool,
     *     inherited_from: ?string,
     *     rule_source_path: ?string,
     *     roles: list<array{id: string, name: string}>,
     *     grants: list<array{role_id: string, permissions: array<string, bool>}>,
     *     actions: list<array{key: string, label: string}>
     * }
     */
    public function getPermissionsFormData(string $relativePath): array
    {
        $path = $this->normalizePath($relativePath);
        $explicit = $this->hasExplicitRules($path);
        $inheritedFrom = null;
        $ruleSourcePath = $path;

        if (! $explicit) {
            $ruleSourcePath = $this->resolveRuleSourcePath($path);
            if ($ruleSourcePath !== null && $ruleSourcePath !== $path) {
                $inheritedFrom = $ruleSourcePath;
            }
        }

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $stored = $explicit
            ? ProjectFilePathPermission::query()->where('path', $path)->get()
            : ($ruleSourcePath !== null
                ? ProjectFilePathPermission::query()->where('path', $ruleSourcePath)->get()
                : collect());

        $grants = $roles->map(function (Role $role) use ($stored) {
            $row = $stored->firstWhere('role_id', $role->id);
            $permissions = [];
            foreach (self::ACTIONS as $action) {
                $permissions[$action] = (bool) ($row?->permissions[$action] ?? false);
            }

            return [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $permissions,
            ];
        })->values()->all();

        return [
            'path' => $path,
            'inherit' => ! $explicit,
            'inherited_from' => $inheritedFrom,
            'rule_source_path' => $ruleSourcePath,
            'roles' => $roles->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name])->values()->all(),
            'grants' => $grants,
            'actions' => $this->actionLabels(),
        ];
    }

    /**
     * @param  list<array{role_id: string, permissions: array<string, bool>}>  $grants
     */
    public function savePermissions(string $relativePath, array $grants, bool $inherit = false): void
    {
        $path = $this->normalizePath($relativePath);

        if ($inherit) {
            ProjectFilePathPermission::query()->where('path', $path)->delete();

            return;
        }

        $roleIds = collect($grants)->pluck('role_id')->filter()->unique()->values()->all();
        ProjectFilePathPermission::query()
            ->where('path', $path)
            ->whereNotIn('role_id', $roleIds)
            ->delete();

        foreach ($grants as $grant) {
            $roleId = (string) ($grant['role_id'] ?? '');
            if ($roleId === '') {
                continue;
            }

            $permissions = [];
            foreach (self::ACTIONS as $action) {
                $permissions[$action] = ! empty($grant['permissions'][$action]);
            }

            if (! in_array(true, $permissions, true)) {
                ProjectFilePathPermission::query()
                    ->where('path', $path)
                    ->where('role_id', $roleId)
                    ->delete();

                continue;
            }

            ProjectFilePathPermission::query()->updateOrCreate(
                ['path' => $path, 'role_id' => $roleId],
                ['permissions' => $permissions],
            );
        }
    }

    private function moduleAllowsAction(string $action): bool
    {
        return match ($action) {
            'view' => $this->permissions->can('/settings/project-files', 'view')
                || $this->permissions->can('/settings/project-files', 'access'),
            'add' => $this->permissions->can('/settings/project-files', 'add'),
            'edit' => $this->permissions->can('/settings/project-files', 'edit'),
            'delete' => $this->permissions->can('/settings/project-files', 'delete'),
            'download' => $this->permissions->can('/settings/project-files', 'export')
                || $this->permissions->can('/settings/project-files', 'view')
                || $this->permissions->can('/settings/project-files', 'access'),
            default => false,
        };
    }

    /** @return list<array{key: string, label: string}> */
    private function actionLabels(): array
    {
        return [
            ['key' => 'view', 'label' => 'Xem / liệt kê'],
            ['key' => 'add', 'label' => 'Thêm / upload'],
            ['key' => 'edit', 'label' => 'Sửa / đổi tên / di chuyển'],
            ['key' => 'delete', 'label' => 'Xóa'],
            ['key' => 'download', 'label' => 'Tải xuống'],
        ];
    }
}
