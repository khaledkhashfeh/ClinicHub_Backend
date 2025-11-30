<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'clinic_id',
        'schedule_slot_id',
        'date_time',
        'status',
        'type',
        'payment_status',
        'payment_method',
        'price_at_booking',
        'source',
        'cancellation_reason',
        'no_show',
    ];

    protected $casts = [
        'date_time'       => 'datetime',
        'price_at_booking'=> 'float',
        'no_show'         => 'boolean',
    ];

    // Relations
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scheduleSlot()
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
