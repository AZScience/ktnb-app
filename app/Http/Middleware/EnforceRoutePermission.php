<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use App\Support\RoutePermissionMap;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRoutePermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $monitoringModule = $route?->parameter('module');

        $resolved = RoutePermissionMap::resolve(
            is_string($routeName) ? $routeName : null,
            is_string($monitoringModule) ? $monitoringModule : null,
        );

        if ($resolved === null) {
            return $next($request);
        }

        [$module, $action] = $resolved;

        if ($this->permissions->allows($module, $action)) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập chức năng này.');
    }
}
