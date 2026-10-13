<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeEntry extends Model
{
    protected $fillable = ['grade_submission_id', 'enrollment_id', 'grade_value', 'remarks', 'updated_by'];
    public function submission(): BelongsTo { return $this->belongsTo(GradeSubmission::class, 'grade_submission_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
}
