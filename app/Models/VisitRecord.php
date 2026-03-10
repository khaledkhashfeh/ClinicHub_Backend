<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_file_id',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'clinic_id',
        'visit_date',
        'session_start_time',
        'diagnosis',
        'notes',
        'next_visit_date',
    ];

    protected $casts = [
        'visit_date'      => 'date',
        'session_start_time' => 'datetime',
        'next_visit_date' => 'date',
    ];

    public function medicalFile()
    {
        return $this->belongsTo(MedicalFile::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(VisitDiagnosis::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function labResults()
    {
        return $this->hasMany(LabResult::class);
    }
}
