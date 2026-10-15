<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $fillable = ['subject_code', 'subject_name', 'grade_level_id', 'status'];
    protected $attributes = ['status' => 'active'];
    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
}
