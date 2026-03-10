<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_tracker_id',
        'scheduled_at',
        'taken_at',
        'status',
        'action_source',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'taken_at' => 'datetime',
    ];

    public function medicationTracker()
    {
        return $this->belongsTo(MedicationTracker::class);
    }
}
