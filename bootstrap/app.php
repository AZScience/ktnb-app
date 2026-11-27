<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\VerifyApiKey::class,
            'permission' => \App\Http\Middleware\CheckModulePermission::class,
            'route.permission' => \App\Http\Middleware\EnforceRoutePermission::class,
            'password.force' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            $msg = 'Dung lượng file hoặc dữ liệu gửi lên quá lớn (vượt quá giới hạn của máy chủ).';
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['message' => $msg], 413);
            }
            return back()->with('error', $msg);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->expectsJson()
                || $request->ajax(),
        );
    })->create();

