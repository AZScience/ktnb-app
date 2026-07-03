<?php

namespace Tests\Unit;

use App\Support\RoutePermissionMap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoutePermissionMapTest extends TestCase
{
    #[DataProvider('routeCases')]
    public function test_resolves_module_and_action(?string $routeName, ?string $moduleParam, ?array $expected): void
    {
        $this->assertSame($expected, RoutePermissionMap::resolve($routeName, $moduleParam));
    }

    public static function routeCases(): array
    {
        return [
            'profile is open' => ['profile.edit', null, null],
            'dashboard view' => ['dashboard', null, ['/dashboard', 'view']],
            'employees export' => ['employees.export', null, ['/personnel/employees', 'export']],
            'monitoring schedule data' => ['monitoring.schedules.data', 'online', ['/monitoring/online', 'view']],
            'reports daily export' => ['reports.daily.export', null, ['/reports/daily', 'export']],
            'filter presets save' => ['dashboard.filter-presets.save', null, ['/dashboard', 'edit']],
            'schedule import template' => ['schedules.import-template', null, ['/settings/schedule', 'import']],
            'unknown route' => ['some.unknown.route', null, null],
        ];
    }

    public function test_module_for_catalog_maps_storage_keys(): void
    {
        $this->assertSame('/personnel/building-blocks', RoutePermissionMap::moduleForCatalog('building_blocks'));
        $this->assertSame('/monitoring/requests', RoutePermissionMap::moduleForCatalog('service-requests'));
        $this->assertSame('/monitoring/asset-check', RoutePermissionMap::moduleForCatalog('asset-reception'));
    }
}
