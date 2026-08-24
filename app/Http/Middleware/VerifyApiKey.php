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
        $expected = (string) config('services.nttu.api_key', '');

        if ($expected === '' || ! is_string($token) || $token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid API Key.'], 401);
        }

        return $next($request);
    }
}
