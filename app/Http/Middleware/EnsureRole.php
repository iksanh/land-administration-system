<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ports FastAPI's get_current_admin dependency. Usage: ->middleware('role:admin').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== $role) {
            abort(403, "Akses ditolak, hanya {$role}");
        }

        return $next($request);
    }
}
