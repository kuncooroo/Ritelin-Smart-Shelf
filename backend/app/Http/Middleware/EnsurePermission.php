<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $name = PermissionName::tryFrom($permission);

        if (! $user instanceof User || $name === null || ! $user->hasPermission($name)) {
            abort(403);
        }

        return $next($request);
    }
}
