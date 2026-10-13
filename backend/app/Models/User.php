<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'role', 'status', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function studentProfile(): HasOne { return $this->hasOne(StudentProfile::class); }
    public function facultyProfile(): HasOne { return $this->hasOne(FacultyProfile::class); }
    public function announcements(): HasMany { return $this->hasMany(Announcement::class, 'created_by'); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }

    public function isActive(): bool { return $this->status === 'active'; }
}
