<?php

namespace App\Support;

use Illuminate\Http\Request;

class SidebarMenuState
{
    /** @return array<string, bool> */
    public static function groupActiveFlags(?Request $request = null): array
    {
        $request = $request ?? request();
        $active = fn (string $pattern) => $request->routeIs($pattern);

        $flags = [
            'catalog' => $active('positions.*')
                || $active('building-blocks.*')
                || $active('departments.*')
                || $active('classrooms.*')
                || $active('lecturers.*')
                || $active('employees.*')
                || $active('students.*')
                || $active('gifts.*')
                || $active('roles.*')
                || $active('recognitions.*')
                || $active('incident-categories.*')
                || $active('document-types.*'),
            'monitor' => $active('monitoring.homeroom.*')
                || $active('monitoring.online.*')
                || $active('monitoring.in-person.*')
                || $active('monitoring.exams.*')
                || $active('monitoring.external-practice.*')
                || $active('student-violations.*')
                || $active('asset-check.*')
                || $active('requests.*')
                || $active('petitions.*')
                || $active('document-records.*')
                || ($active('monitoring.schedules.*') && in_array($request->route('module'), [
                    'homeroom', 'online', 'in-person', 'exams', 'external-practice',
                ], true)),
            'reports' => $active('reports.daily')
                || $active('reports.comprehensive')
                || $active('reports.student-violations')
                || $active('reports.good-deeds')
                || $active('reports.request-reports')
                || $active('reports.incident-reports')
                || $active('feedback.*'),
            'system' => $active('schedules.*')
                || $active('parameters.*')
                || $active('permissions.*')
                || $active('activity-logs.*')
                || $active('backup.*')
                || $active('project-files.*'),
            'tools' => $active('external-checkins.*')
                || $active('online-classes.*')
                || $active('online-checkins.*')
                || $active('ai.*')
                || $active('api-documentation.*')
                || $active('announcements.*')
                || $active('document-lookup.*')
                || $active('discussion.*')
                || $active('messaging.*')
                || $active('lecturer-portal.*'),
        ];

        return $flags;
    }

    public static function initialOpenMenu(?Request $request = null): ?string
    {
        foreach (self::groupActiveFlags($request) as $section => $isActive) {
            if ($isActive) {
                return $section;
            }
        }

        return null;
    }
}
