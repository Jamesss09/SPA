<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingPeriod extends Model
{
    protected $fillable = ['school_year_id', 'quarter_number', 'start_date', 'end_date', 'status'];
    protected $attributes = ['status' => 'active'];
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
}
