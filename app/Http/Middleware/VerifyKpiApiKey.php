<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyKpiApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $validKey = config('services.kpi.api_key');

        // Cek API Key dari Header (atau bearer token)
        $providedKey = $request->header('X-API-KEY') ?? $request->bearerToken();

        if (! is_string($validKey)
            || $validKey === ''
            || ! is_string($providedKey)
            || ! hash_equals($validKey, $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. API Key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
