<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('ritelin.require_tls') && ! $request->secure()) {
            return response()->json(['message' => 'HTTPS diperlukan.'], 400);
        }

        $plain = $request->bearerToken();
        if (! is_string($plain) || $plain === '') {
            return response()->json(['message' => 'Token perangkat wajib diisi.'], 401);
        }

        $device = Device::query()->where('token_hash', hash('sha256', $plain))->first();
        if ($device === null) {
            return response()->json(['message' => 'Perangkat tidak dikenal.'], 401);
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
