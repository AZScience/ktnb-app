<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $module, string $action = 'access'): Response
    {
        if ($this->permissions->allows($module, $action)) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập chức năng này.');
    }
}
