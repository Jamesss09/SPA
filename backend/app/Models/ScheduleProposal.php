<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleProposal extends Model
{
    protected $fillable = ['teaching_assignment_id', 'version_number', 'status', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_comment'];
    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function assignment(): BelongsTo { return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id'); }
    public function entries(): HasMany { return $this->hasMany(ScheduleEntry::class); }
}
