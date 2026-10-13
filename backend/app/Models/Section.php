<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['school_year_id', 'grade_level_id', 'name', 'adviser_faculty_id', 'room_id'];
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
    public function adviser(): BelongsTo { return $this->belongsTo(User::class, 'adviser_faculty_id'); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
}
