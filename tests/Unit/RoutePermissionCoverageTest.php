<?php

namespace Tests\Unit;

use App\Support\RoutePermissionMap;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoutePermissionCoverageTest extends TestCase
{
    public function test_all_authenticated_web_routes_are_mapped_or_whitelisted(): void
    {
        $unmapped = [];
        $mapped = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null) {
                continue;
            }

            $middleware = collect($route->gatherMiddleware());
            if (! $middleware->contains('route.permission')) {
                continue;
            }

            $moduleParam = null;
            if (str_contains($route->uri(), '{module}')) {
                $moduleParam = 'online';
            }

            $resolved = RoutePermissionMap::resolve($name, $moduleParam);

            if ($resolved === null && ! in_array($name, RoutePermissionMap::whitelistedRouteNames(), true)) {
                $unmapped[] = $name.' ['.implode('|', $route->methods()).'] '.$route->uri();
            } elseif ($resolved !== null) {
                $mapped[$name] = $resolved;
            }
        }

        $this->assertSame(
            [],
            $unmapped,
            "Routes thiếu ánh xạ quyền (cho phép truy cập không kiểm tra):\n".implode("\n", $unmapped)
        );
    }

    public function test_matrix_modules_have_staff_default_or_are_restricted_by_design(): void
    {
        $matrixIds = app(\App\Services\PermissionService::class)->matrixModuleIds();
        $defaults = array_keys(config('nttu.staff_defaults', []));

        $restrictedByDesign = [
            '/personnel/roles',
            '/settings/permissions',
            '/settings/schedule',
            '/settings/parameters',
            '/settings/access-log',
            '/settings/backup',
            '/settings/project-files',
        ];

        $missing = [];
        foreach ($matrixIds as $id) {
            if (! in_array($id, $defaults, true) && ! in_array($id, $restrictedByDesign, true)) {
                $missing[] = $id;
            }
        }

        $this->assertSame([], $missing, 'Module trong ma trận nhưng không có staff_default: '.implode(', ', $missing));
    }
}
