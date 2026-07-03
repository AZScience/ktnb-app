<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /** @var list<string> */
    private array $exceptRouteNames = [
        'password.force',
        'password.force.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs($this->exceptRouteNames)) {
            return $next($request);
        }

        return redirect()->route('password.force');
    }
}
