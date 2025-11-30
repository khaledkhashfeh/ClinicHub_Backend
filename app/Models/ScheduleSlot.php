<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'start_time'   => 'datetime:H:i:s',
        'end_time'     => 'datetime:H:i:s',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
