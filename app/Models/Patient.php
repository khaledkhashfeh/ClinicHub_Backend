<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'governorate_id',
        'city_id',
        'occupation',
        'city',
        'area',
        'loyalty_points_balance',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function medicalFile()
    {
        return $this->hasOne(MedicalFile::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function medicationTrackers()
    {
        return $this->hasMany(MedicationTracker::class);
    }

    public function complianceAlerts()
    {
        return $this->hasMany(PatientComplianceAlert::class);
    }

    public function favoriteClinics()
    {
        return $this->belongsToMany(Clinic::class, 'patient_clinic_favorites')->withTimestamps();
    }

    public function favoriteDoctors()
    {
        return $this->belongsToMany(Doctor::class, 'patient_doctor_favorites')->withTimestamps();
    }

    public function favoriteMedicalCenters()
    {
        return $this->belongsToMany(MedicalCenter::class, 'patient_medical_center_favorites')->withTimestamps();
    }
}
