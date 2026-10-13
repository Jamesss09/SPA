<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementAudience extends Model
{
    protected $fillable = ['announcement_id', 'audience_type', 'grade_level_id', 'section_id'];
}
