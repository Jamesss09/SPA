<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Admin user management. Students are created by the Phase 4 import only,
// so store() accepts admin|faculty roles; updates may change role/status.
class AdminUserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');
        $isStore = $this->isMethod('post') && ! $user;
        return [
            'name' => [$isStore ? 'required' : 'sometimes', 'string', 'max:255'],
            'username' => [
                $isStore ? 'required' : 'sometimes', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$isStore ? 'required' : 'sometimes', 'string', 'min:8'],
            'role' => [$isStore ? 'required' : 'sometimes', 'string', Rule::in($isStore ? ['admin', 'faculty'] : ['admin', 'faculty', 'student'])],
            'status' => ['sometimes', 'string', 'in:active,deactivated'],
        ];
    }
}
