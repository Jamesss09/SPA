<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    public function rules(): array
    {
        $section = $this->route('section');
        $sy = $this->input('school_year_id', $section?->school_year_id);
        $gl = $this->input('grade_level_id', $section?->grade_level_id);
        return [
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            // App-level scoped uniqueness (no DB unique on sections yet):
            // same name may repeat across years/levels, never within one.
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('sections', 'name')
                    ->where(fn ($q) => $q->where('school_year_id', $sy)->where('grade_level_id', $gl))
                    ->ignore($section?->id),
            ],
            'adviser_faculty_id' => [
                'nullable', 'integer', 'exists:users,id',
                function ($attr, $value, $fail) {
                    $adviser = User::find($value);
                    if (! $adviser || $adviser->role !== 'faculty' || ! $adviser->isActive()) {
                        $fail('The adviser must be an active faculty account.');
                    }
                },
            ],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }
}
