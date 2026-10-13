<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    protected $fillable = ['faculty_profile_id', 'subject_id', 'section_id', 'school_year_id', 'status'];
    public function faculty(): BelongsTo { return $this->belongsTo(FacultyProfile::class, 'faculty_profile_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
    public function schedules(): HasMany { return $this->hasMany(ScheduleProposal::class); }
    public function gradeSubmissions(): HasMany { return $this->hasMany(GradeSubmission::class); }
}
