<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_file_id',
        'doctor_id',
        'clinic_id',
        'visit_date',
        'diagnosis',
        'notes',
        'next_visit_date',
    ];

    protected $casts = [
        'visit_date'      => 'date',
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

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
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
