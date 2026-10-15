<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeLevelRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('grade_level')?->id;
        return [
            'name' => ['required', 'string', 'max:255'],
            'level_number' => ['required', 'integer', 'between:7,12', Rule::unique('grade_levels', 'level_number')->ignore($id)],
        ];
    }
}
