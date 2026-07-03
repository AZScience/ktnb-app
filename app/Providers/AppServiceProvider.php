<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Employee;
use App\Models\Position;
use App\Services\PermissionService;
use App\Services\ScheduleLocationService;
use App\Services\SystemParameterService;
use App\Support\HttpSslConfigurator;
use App\Support\PagePermissionFlags;
use App\Support\SidebarMenuState;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScheduleLocationService::class);
    }

    public function boot(): void
    {
        date_default_timezone_set((string) config('app.timezone'));

        HttpSslConfigurator::apply();

        Schema::defaultStringLength(191);

        View::composer([
            'layouts.nttu',
            'components.*',
            'dashboard',
            'feedback.*',
            'messaging.*',
            'monitoring.*',
            'reports.*',
            'settings.*',
            'personnel.*',
        ], function ($view) {
            if (! auth()->check()) {
                return;
            }

            $perms = app(PermissionService::class);
            $view->with('nttuCan', fn (string $module, string $action = 'access') => $perms->can($module, $action));
            $view->with('nttuAllows', fn (string $module, string $action = 'access') => $perms->allows($module, $action));
            $view->with('nttuPage', fn (string $module) => PagePermissionFlags::for($module));

            $layoutShellViews = ['layouts.nttu', 'components.nttu-sidebar', 'components.nttu-header'];
            if (in_array($view->name(), $layoutShellViews, true)) {
                $params = app(SystemParameterService::class)->all();
                $bannerUrl = $params['bannerUrl'] ?: 'https://kiemtranoibo.ntt.edu.vn/wp-content/uploads/2025/09/PHONG-KIEM-TRA-NOI-BO.png';
                $bannerHeight = (int) ($params['bannerHeight'] ?: 40);

                $employee = Employee::query()->where('user_id', auth()->id())->first()
                    ?? Employee::query()->where('email', auth()->user()->email)->first();

                $employeePositionName = null;
                if ($employee?->position) {
                    $employeePositionName = Position::query()->find($employee->position)?->name ?? $employee->position;
                }

                $view->with(compact('bannerUrl', 'bannerHeight', 'employee', 'employeePositionName'));
            }

            if (in_array($view->name(), ['layouts.nttu', 'components.nttu-header'], true)) {
                $view->with([
                    'headerUnreadCount' => 0,
                    'headerNotifications' => [],
                ]);
            }

            if (in_array($view->name(), ['layouts.nttu', 'components.nttu-sidebar'], true)) {
                $view->with('sidebarGroupActive', SidebarMenuState::groupActiveFlags());
                $view->with('sidebarInitialMenu', SidebarMenuState::initialOpenMenu());
            }
        });
    }
}
