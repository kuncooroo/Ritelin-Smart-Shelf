<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('rakku.require_tls') && ! $request->secure()) {
            return response()->json(['message' => 'HTTPS diperlukan.'], 400);
        }

        $configured = (string) config('rakku.pos_token');
        $given = (string) $request->header('X-Pos-Token', '');

        if ($configured === '' || strlen($configured) !== strlen($given) || ! hash_equals($configured, $given)) {
            return response()->json(['message' => 'Token kasir tidak sesuai.'], 401);
        }

        return $next($request);
    }
}
