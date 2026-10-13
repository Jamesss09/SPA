<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeSubmission extends Model
{
    protected $fillable = ['teaching_assignment_id', 'grading_period_id', 'status', 'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_comment'];
    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function entries(): HasMany { return $this->hasMany(GradeEntry::class); }
}
