<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicDoctor extends Model
{
    protected $table = 'clinic_doctor';

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'is_primary',
        'method_id',
        'appointment_period',
        'queue',
        'queue_number',
    ];

    public function clinic() {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }

    public function method() {
        return $this->belongsTo(Method::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorClinicSchedule::class, 'doctor_id', 'doctor_id')
                    ->where('clinic_id', $this->clinic_id);
    }

    public function overrides()
    {
        return $this->hasMany(ScheduleOverride::class, 'doctor_id', 'doctor_id')
                    ->where('clinic_id', $this->clinic_id);
    }
}
