<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAiServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('services.ai.token');
        $header = (string) $request->bearerToken();

        if ($configured === '' || ! hash_equals($configured, $header)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
