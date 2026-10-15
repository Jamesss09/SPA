<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradingPeriodRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('grading_period')?->id;
        $sy = $this->input('school_year_id', $this->route('grading_period')?->school_year_id);
        return [
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'quarter_number' => [
                'required', 'integer', 'between:1,4',
                Rule::unique('grading_periods', 'quarter_number')
                    ->where(fn ($q) => $q->where('school_year_id', $sy))
                    ->ignore($id),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
        ];
    }
}
