<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = ['student_profile_id', 'school_year_id', 'section_id', 'status', 'enrolled_at'];
    protected $casts = ['enrolled_at' => 'datetime'];
    public function student(): BelongsTo { return $this->belongsTo(StudentProfile::class, 'student_profile_id'); }
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
    public function gradeEntries(): HasMany { return $this->hasMany(GradeEntry::class); }
}
