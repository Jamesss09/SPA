<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('room')?->id;
        return [
            'room_name' => ['required', 'string', 'max:255', Rule::unique('rooms', 'room_name')->ignore($id)],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
