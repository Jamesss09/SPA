<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'created_by', 'published_at', 'expires_at', 'status'];
    protected $casts = ['published_at' => 'datetime', 'expires_at' => 'datetime'];
    public function audiences(): HasMany { return $this->hasMany(AnnouncementAudience::class); }
}
