<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleOverride extends Model
{
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'date',
        'type',
        'custom_slots',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'custom_slots' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'override_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'override_id');
    }

    /**
     * Check if this override closes the day completely
     */
    public function isClosed(): bool
    {
        return $this->type === 'closed';
    }

    /**
     * Check if this override has custom slots
     */
    public function hasCustomSlots(): bool
    {
        return $this->type === 'custom' && !empty($this->custom_slots);
    }
}
