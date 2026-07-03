<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('x-api-key');
        $expected = config('services.nttu.api_key', 'kiemtranoibo_default_secret_key_2026');

        if (! $token || ! hash_equals($expected, $token)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid API Key.'], 401);
        }

        return $next($request);
    }
}
