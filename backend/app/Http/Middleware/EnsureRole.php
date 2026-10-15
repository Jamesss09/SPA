<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Phase 2+: role gate for later phases (e.g. ->middleware('role:admin')).
// Usage: Route::...->middleware(['auth:sanctum', 'active', 'role:admin']);
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
