<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyProfile extends Model
{
    protected $fillable = ['user_id', 'employee_number', 'contact_number', 'profile_photo_path'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
}
