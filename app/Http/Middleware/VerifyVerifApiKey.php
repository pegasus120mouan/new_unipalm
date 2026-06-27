<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyVerifApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('verif.api_key');

        if ($expected === null || $expected === '') {
            return $next($request);
        }

        $provided = $request->header('X-Verif-Api-Key')
            ?? $request->header('Authorization')
            ?? $request->query('api_key');

        if (is_string($provided) && str_starts_with($provided, 'Bearer ')) {
            $provided = substr($provided, 7);
        }

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'error' => 'Clé API invalide ou manquante.',
            ], 401);
        }

        return $next($request);
    }
}
