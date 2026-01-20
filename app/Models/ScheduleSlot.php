<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'day_of_week',
        'date',
        'start_time',
        'end_time',
        'is_available',
        'status',
        'slot_type',
        'creation_method',
        'schedule_id',
        'override_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'date' => 'date',
    ];

    /**
     * Scope: Get available slots
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('is_available', true);
    }

    /**
     * Scope: Get booked slots
     */
    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    /**
     * Check if slot is available for booking
     */
    public function isAvailableForBooking(): bool
    {
        return $this->status === 'available' && $this->is_available;
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function schedule()
    {
        return $this->belongsTo(DoctorClinicSchedule::class, 'schedule_id');
    }

    public function override()
    {
        return $this->belongsTo(ScheduleOverride::class, 'override_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
