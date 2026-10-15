<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Phase 2 — Sanctum SPA cookie-session auth. These routes need the `web`
// middleware group (session + CSRF + cookies); `auth:sanctum` then reads
// the session via the `web` guard. Frontend must call
// GET /sanctum/csrf-cookie first (axios sends X-XSRF-TOKEN automatically).
Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware(['auth:sanctum', 'active']);
    Route::post('/password/change', [AuthController::class, 'changePassword'])->middleware(['auth:sanctum', 'active']);
});
