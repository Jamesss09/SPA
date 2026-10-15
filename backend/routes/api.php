<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GradingPeriodController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
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

// Phase 3 — admin-only school setup + user management. Guests get 401 from
// auth:sanctum, non-admins 403 from admin, deactivated 403 from active.
Route::middleware(['web', 'auth:sanctum', 'active', 'admin'])->group(function () {
    Route::apiResource('school-years', SchoolYearController::class);
    Route::apiResource('grading-periods', GradingPeriodController::class);
    Route::apiResource('grade-levels', GradeLevelController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('sections', SectionController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('users', AdminUserController::class)->except(['destroy']);
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
});
