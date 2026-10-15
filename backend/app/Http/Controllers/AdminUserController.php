<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    private function shape(User $user): array
    {
        return [
            'id' => $user->id, 'name' => $user->name, 'username' => $user->username,
            'email' => $user->email, 'role' => $user->role, 'status' => $user->status,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $q = User::query()->orderByDesc('id');
        foreach (['role', 'status'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->string($f));
            }
        }
        if ($request->filled('search')) {
            $s = '%' . $request->string('search') . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('username', 'like', $s));
        }
        return response()->json($q->paginate(20)->through(fn ($u) => $this->shape($u)));
    }

    public function store(AdminUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        return response()->json($this->shape(User::create($data)), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($this->shape($user));
    }

    public function update(AdminUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json($this->shape($user->refresh()));
    }

    public function resetPassword(User $user): JsonResponse
    {
        // Deterministic temporary password; forces change on next login.
        // Never returned in bulk or logged — handed to the user out of band.
        $temporary = 'SPA-' . strtoupper(substr(md5($user->id . now()->timestamp), 0, 8));
        $user->password = Hash::make($temporary);
        $user->must_change_password = true;
        $user->save();
        return response()->json(['temporary_password' => $temporary]);
    }
}
