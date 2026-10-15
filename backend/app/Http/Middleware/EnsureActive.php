<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Phase 2+: deactivated accounts cannot use protected endpoints.
// Backend is the authority — frontend role routing is UX only.
class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->isActive()) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }
        return $next($request);
    }
}
