<?php

namespace App\Support;

class RoutePermissionMap
{
    /** @var array<string, string> */
    private const RESOURCE_MODULES = [
        'employees' => '/personnel/employees',
        'departments' => '/personnel/departments',
        'positions' => '/personnel/positions',
        'roles' => '/personnel/roles',
        'students' => '/personnel/students',
        'lecturers' => '/personnel/lecturers',
        'building-blocks' => '/personnel/building-blocks',
        'classrooms' => '/personnel/classrooms',
        'gifts' => '/personnel/gifts',
        'recognitions' => '/personnel/recognitions',
        'incident-categories' => '/personnel/incident-categories',
        'document-types' => '/personnel/document-types',
        'student-violations' => '/monitoring/student-violations',
        'requests' => '/monitoring/requests',
        'petitions' => '/monitoring/petitions',
        'asset-receptions' => '/monitoring/asset-check',
        'document-records' => '/monitoring/document-records',
        'external-checkins' => '/monitoring/external-checkins',
        'online-classes' => '/monitoring/online-classes',
        'permissions' => '/settings/permissions',
        'activity-logs' => '/settings/access-log',
        'parameters' => '/settings/parameters',
        'schedules' => '/settings/schedule',
        'backup' => '/settings/backup',
        'project-files' => '/settings/project-files',
        'messaging' => '/messaging',
        'announcements' => '/tools/announcements',
    ];

    /** @var array<string, string> */
    private const CATALOG_MODULES = [
        'service-requests' => '/monitoring/requests',
        'asset-gratitude' => '/monitoring/asset-check',
        'asset-return' => '/monitoring/asset-check',
        'asset-reception' => '/monitoring/asset-check',
    ];

    /** @var list<string> */
    public static function whitelistedRouteNames(): array
    {
        return self::ALWAYS_ALLOWED;
    }

    public static function moduleForCatalog(string $storageKey): ?string
    {
        $key = str_replace('_', '-', $storageKey);

        if (isset(self::CATALOG_MODULES[$key])) {
            return self::CATALOG_MODULES[$key];
        }

        return self::RESOURCE_MODULES[$key] ?? null;
    }

    /** @var array<string, string> */
    private const MONITORING_PAGE_ROUTES = [
        'monitoring.online.index' => '/monitoring/online',
        'monitoring.in-person.index' => '/monitoring/in-person',
        'monitoring.exams.index' => '/monitoring/exams',
        'monitoring.external-practice.index' => '/monitoring/external-practice',
        'monitoring.homeroom.index' => '/monitoring/homeroom',
    ];

    /** @var array<string, string> */
    private const REPORT_ROUTES = [
        'reports.daily' => '/reports/daily',
        'reports.daily.export' => '/reports/daily',
        'reports.daily.google-sheets.tabs' => '/reports/daily',
        'reports.daily.google-sheets.push' => '/reports/daily',
        'reports.comprehensive' => '/reports/comprehensive',
        'reports.comprehensive.export' => '/reports/comprehensive',
        'reports.comprehensive.monthly-report' => '/reports/comprehensive',
        'reports.student-violations' => '/reports/student-violations',
        'reports.student-violations.export' => '/reports/student-violations',
        'reports.good-deeds' => '/reports/good-deeds',
        'reports.good-deeds.export' => '/reports/good-deeds',
        'reports.request-reports' => '/reports/request-reports',
        'reports.request-reports.export' => '/reports/request-reports',
        'reports.incident-reports' => '/reports/incident-reports',
        'reports.incident-reports.export' => '/reports/incident-reports',
        'reports.interactive-data' => '/reports/comprehensive',
    ];

    /** @var list<string> */
    private const ALWAYS_ALLOWED = [
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'settings.index',
        'settings.security',
        'settings.security.sessions.others',
        'settings.security.sessions.destroy',
        'imports.status',
        'ckeditor.upload',
    ];

    /**
     * @return array{0: string, 1: string}|null null = không kiểm tra quyền
     */
    public static function resolve(?string $routeName, ?string $monitoringModule = null): ?array
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        if (in_array($routeName, self::ALWAYS_ALLOWED, true)) {
            return null;
        }

        if (isset(self::MONITORING_PAGE_ROUTES[$routeName])) {
            return [self::MONITORING_PAGE_ROUTES[$routeName], self::actionFromRouteName($routeName)];
        }

        if (isset(self::REPORT_ROUTES[$routeName])) {
            return [self::REPORT_ROUTES[$routeName], self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'monitoring.schedules.')) {
            $moduleKey = $monitoringModule ? '/monitoring/'.$monitoringModule : null;
            if ($moduleKey === null) {
                return null;
            }

            return [$moduleKey, self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'dashboard')) {
            return ['/dashboard', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'feedback.')) {
            return ['/feedback', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'asset-check.')) {
            return ['/monitoring/asset-check', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'monitoring.evidence.')) {
            return ['/monitoring/evidence', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'document-lookup.')) {
            return ['/monitoring/document-lookup', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'ai.')) {
            return ['/ai/assistant', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'api-documentation.')) {
            return ['/tools/api-documentation', self::actionFromRouteName($routeName)];
        }

        if (str_starts_with($routeName, 'discussion.')) {
            return ['/discussion', self::actionFromRouteName($routeName)];
        }

        if ($routeName === 'online-checkins.index') {
            return ['/monitoring/online-classes', 'view'];
        }

        $parts = explode('.', $routeName);
        $prefix = $parts[0] ?? '';

        if (isset(self::RESOURCE_MODULES[$prefix])) {
            return [self::RESOURCE_MODULES[$prefix], self::actionFromRouteName($routeName)];
        }

        return null;
    }

    private static function actionFromRouteName(string $routeName): string
    {
        $name = strtolower($routeName);

        if (str_contains($name, '.export') || str_contains($name, '-export')) {
            return 'export';
        }

        if (str_contains($name, 'import-preview')
            || str_contains($name, 'import-batch')
            || str_contains($name, 'import-template')
            || str_contains($name, '.import')
            || str_contains($name, '-import')) {
            return 'import';
        }

        if (str_contains($name, '.destroy')
            || str_contains($name, 'bulk-delete')
            || str_contains($name, 'bulk-destroy')
            || str_contains($name, 'destroy-by-date')
            || str_contains($name, 'purge-data')) {
            return 'delete';
        }

        if (str_contains($name, 'google-sheets')) {
            return 'export';
        }

        if (str_contains($name, '.store')
            || str_contains($name, '.create')
            || str_contains($name, 'batch-upload')
            || str_ends_with($name, '.push')) {
            return 'add';
        }

        if (str_contains($name, 'shift-schedule.file')) {
            return 'view';
        }

        if (str_contains($name, 'shift-schedule.store')) {
            return 'edit';
        }

        if (str_contains($name, '.update')
            || str_contains($name, '.edit')
            || str_contains($name, '.save')
            || str_contains($name, '.bulk')
            || str_contains($name, 'bulk-update')
            || str_contains($name, 'compare-faces')
            || str_contains($name, '.extract')
            || str_contains($name, '.compress')
            || str_contains($name, '.move')
            || str_contains($name, '.rename')
            || str_contains($name, 'birthdays.send')
            || str_contains($name, 'mark-all-read')
            || str_contains($name, '.trash')
            || str_contains($name, '.restore')
            || str_contains($name, '.verify')
            || str_contains($name, 'upload')) {
            return 'edit';
        }

        return 'view';
    }
}
