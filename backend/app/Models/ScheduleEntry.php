<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEntry extends Model
{
    protected $fillable = ['schedule_proposal_id', 'room_id', 'day_of_week', 'start_time', 'end_time'];
    public function proposal(): BelongsTo { return $this->belongsTo(ScheduleProposal::class, 'schedule_proposal_id'); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
}
