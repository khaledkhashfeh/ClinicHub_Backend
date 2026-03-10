<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationTracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'patient_id',
        'doctor_id',
        'status',
        'total_doses',
        'taken_doses',
        'start_at',
        'next_dose_at',
        'consecutive_missed_doses',
        'non_compliant_at',
    ];

    protected $casts = [
        'total_doses' => 'integer',
        'taken_doses' => 'integer',
        'consecutive_missed_doses' => 'integer',
        'start_at' => 'datetime',
        'next_dose_at' => 'datetime',
        'non_compliant_at' => 'datetime',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function doseLogs()
    {
        return $this->hasMany(DoseLog::class);
    }

    public function complianceAlerts()
    {
        return $this->hasMany(PatientComplianceAlert::class);
    }
}
