<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolYearRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('school_year')?->id;
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('school_years', 'name')->ignore($id)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
        ];
    }
}
