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
        if (request()->is('reports/daily/export') && request()->isMethod('POST')) {
            request()->setMethod('GET');
        }

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
            'manual.*',
        ], function ($view) {
            if (auth()->check()) {
                $perms = app(PermissionService::class);
                $view->with('nttuCan', fn (string $module, string $action = 'access') => $perms->can($module, $action));
                $view->with('nttuAllows', fn (string $module, string $action = 'access') => $perms->allows($module, $action));
                $view->with('nttuPage', fn (string $module) => PagePermissionFlags::for($module));
            } else {
                $view->with('nttuCan', fn (string $module, string $action = 'access') => false);
                $view->with('nttuAllows', fn (string $module, string $action = 'access') => false);
                $view->with('nttuPage', fn (string $module) => ['access' => false, 'add' => false, 'edit' => false, 'delete' => false, 'import' => false, 'export' => false]);
                $params = app(SystemParameterService::class)->all();
                $view->with([
                    'bannerUrl' => $params['bannerUrl'] ?: 'https://kiemtranoibo.ntt.edu.vn/wp-content/uploads/2025/09/PHONG-KIEM-TRA-NOI-BO.png',
                    'bannerHeight' => (int) ($params['bannerHeight'] ?: 40),
                    'headerTitleText' => !empty($params['headerTitleText']) ? $params['headerTitleText'] : 'CÔNG CỤ KIỂM SOÁT',
                    'headerTitleColor' => !empty($params['headerTitleColor']) ? $params['headerTitleColor'] : '#111827',
                    'headerTitleFontSize' => !empty($params['headerTitleFontSize']) ? $params['headerTitleFontSize'] : '20',
                    'headerTitleFontWeight' => !empty($params['headerTitleFontWeight']) ? $params['headerTitleFontWeight'] : '700',
                    'headerTitleTransform' => !empty($params['headerTitleTransform']) ? $params['headerTitleTransform'] : 'uppercase',
                    'headerTitleLetterSpacing' => !empty($params['headerTitleLetterSpacing']) ? $params['headerTitleLetterSpacing'] : '0.05em',
                    'headerTitleStyle' => !empty($params['headerTitleStyle']) ? $params['headerTitleStyle'] : 'normal',
                    'employee' => null,
                    'employeePositionName' => null,
                ]);
                return;
            }

            $layoutShellViews = ['layouts.nttu', 'components.nttu-sidebar', 'components.nttu-header'];
            if (in_array($view->name(), $layoutShellViews, true)) {
                $params = app(SystemParameterService::class)->all();
                $bannerUrl = $params['bannerUrl'] ?: 'https://kiemtranoibo.ntt.edu.vn/wp-content/uploads/2025/09/PHONG-KIEM-TRA-NOI-BO.png';
                $bannerHeight = (int) ($params['bannerHeight'] ?: 40);

                $headerTitleText = !empty($params['headerTitleText']) ? $params['headerTitleText'] : 'CÔNG CỤ KIỂM SOÁT';
                $headerTitleColor = !empty($params['headerTitleColor']) ? $params['headerTitleColor'] : '#111827';
                $headerTitleFontSize = !empty($params['headerTitleFontSize']) ? $params['headerTitleFontSize'] : '20';
                $headerTitleFontWeight = !empty($params['headerTitleFontWeight']) ? $params['headerTitleFontWeight'] : '700';
                $headerTitleTransform = !empty($params['headerTitleTransform']) ? $params['headerTitleTransform'] : 'uppercase';
                $headerTitleLetterSpacing = !empty($params['headerTitleLetterSpacing']) ? $params['headerTitleLetterSpacing'] : '0.05em';
                $headerTitleStyle = !empty($params['headerTitleStyle']) ? $params['headerTitleStyle'] : 'normal';

                $employee = Employee::query()->where('user_id', auth()->id())->first()
                    ?? Employee::query()->where('email', auth()->user()->email)->first();

                $employeePositionName = null;
                if ($employee?->position) {
                    $employeePositionName = Position::query()->find($employee->position)?->name ?? $employee->position;
                }

                $view->with(compact(
                    'bannerUrl',
                    'bannerHeight',
                    'headerTitleText',
                    'headerTitleColor',
                    'headerTitleFontSize',
                    'headerTitleFontWeight',
                    'headerTitleTransform',
                    'headerTitleLetterSpacing',
                    'headerTitleStyle',
                    'employee',
                    'employeePositionName'
                ));
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
