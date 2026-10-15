<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function userShape($user): array
    {
        return ['user' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'status' => $user->status,
            'must_change_password' => (bool) $user->must_change_password,
        ]];
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => ['These credentials do not match our records.'],
            ]);
        }

        $request->session()->regenerate();

        // Explicit web guard: the default guard may be stale (sanctum) when
        // requests share an app instance (tests, Octane).
        $user = Auth::guard('web')->user();
        if (! $user->isActive()) {
            // Explicit web guard: after auth:sanctum the default guard is
            // sanctum (RequestGuard), which has no logout() method.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        return response()->json($this->userShape($user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // Drop resolved guard instances so no stale user survives in
        // long-running runtimes (Octane) or back-to-back test requests.
        Auth::forgetGuards();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userShape($request->user()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->string('password'));
        $user->must_change_password = false;
        $user->save();

        return response()->json(['message' => 'Password changed.']);
    }
}
