<?php

namespace App\Support;

use App\Services\PermissionService;

class PagePermissionFlags
{
    /** @return array{access: bool, view: bool, add: bool, edit: bool, delete: bool, import: bool, export: bool} */
    public static function for(string $module, ?PermissionService $permissions = null): array
    {
        $permissions ??= app(PermissionService::class);

        return [
            'access' => $permissions->allows($module, 'access'),
            'view' => $permissions->allows($module, 'view'),
            'add' => $permissions->allows($module, 'add'),
            'edit' => $permissions->allows($module, 'edit'),
            'delete' => $permissions->allows($module, 'delete'),
            'import' => $permissions->allows($module, 'import'),
            'export' => $permissions->allows($module, 'export'),
        ];
    }

    public static function monitoringModule(string $moduleSlug): string
    {
        return '/monitoring/'.$moduleSlug;
    }

    public static function reportModule(string $variant): string
    {
        return match ($variant) {
            'student-violations' => '/reports/student-violations',
            'good-deeds' => '/reports/good-deeds',
            'request-reports' => '/reports/request-reports',
            'incident-reports' => '/reports/incident-reports',
            default => '/reports/comprehensive',
        };
    }

    public static function checkinModule(string $mode): string
    {
        return $mode === 'online'
            ? '/monitoring/online-classes'
            : '/monitoring/external-checkins';
    }

    public static function dashboardMonitoringModule(string $key): string
    {
        return match ($key) {
            'online' => '/monitoring/online',
            'in-person' => '/monitoring/in-person',
            'exams' => '/monitoring/exams',
            'homeroom' => '/monitoring/homeroom',
            'external-practice' => '/monitoring/external-practice',
            default => '/monitoring/'.$key,
        };
    }
}
