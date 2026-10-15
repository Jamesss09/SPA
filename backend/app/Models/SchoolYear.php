<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'status'];
    protected $attributes = ['status' => 'active'];
    public function gradingPeriods(): HasMany { return $this->hasMany(GradingPeriod::class); }
    public function sections(): HasMany { return $this->hasMany(Section::class); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
}
