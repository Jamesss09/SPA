<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Phase 3: admin-only resources (school setup, user management).
// Faculty/student get 403, guests 401 (via auth:sanctum first).
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
