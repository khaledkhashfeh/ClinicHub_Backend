<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty',
        'consultation_price',
        'city',
        'area',
        'address_details',
        'bio',
        'status',
        'has_secretary_service',
    ];

    protected $casts = [
        'consultation_price' => 'float',
        'has_secretary_service' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class)
                    ->withTimestamps()
                    ->withPivot('is_primary');
    }

    public function scheduleSlots()
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function visitRecords()
    {
        return $this->hasMany(VisitRecord::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
