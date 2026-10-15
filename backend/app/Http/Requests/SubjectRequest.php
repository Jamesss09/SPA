<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('subject')?->id;
        return [
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'subject_code')->ignore($id)],
            'subject_name' => ['required', 'string', 'max:255'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
