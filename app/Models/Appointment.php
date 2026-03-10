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
        'schedule_id',
        'override_id',
        'start_time',
        'end_time',
        'date',
        'status',
        'type',
        'payment_status',
        'payment_method',
        'price_at_booking',
        'source',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_by_comment',
        'no_show',
    ];

    protected $casts = [
        'date'            => 'date',
        'price_at_booking'=> 'float',
        'no_show'         => 'boolean',
    ];

    // Constants for appointment statuses
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_BOOKED = 'booked';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_FINAL_CONFIRMATION = 'final_confirmation';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

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

    public function schedule()
    {
        return $this->belongsTo(DoctorClinicSchedule::class, 'schedule_id');
    }

    public function override()
    {
        return $this->belongsTo(ScheduleOverride::class, 'override_id');
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function visitRecord()
    {
        return $this->hasOne(VisitRecord::class);
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeBooked($query)
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeFinalConfirmation($query)
    {
        return $query->where('status', self::STATUS_FINAL_CONFIRMATION);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helper methods
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isFinalConfirmation(): bool
    {
        return $this->status === self::STATUS_FINAL_CONFIRMATION;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_BOOKED,
            self::STATUS_CONFIRMED,
            self::STATUS_FINAL_CONFIRMATION
        ]);
    }
}
