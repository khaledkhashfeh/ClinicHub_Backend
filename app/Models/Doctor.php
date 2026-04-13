<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'license_number',
        'specialty',
        'consultation_price',
        'practicing_profession_date',
        'governorate_id',
        'city_id',
        'district_id',
        'area',
        'address_details',
        'bio',
        'distinguished_specialties',
        'facebook_link',
        'instagram_link',
        'status',
        'phone_verified',
        'has_secretary_service',
        'image',
        'image_file_id'
    ];

    protected $casts = [
        'consultation_price' => 'decimal:2',
        'has_secretary_service' => 'boolean',
        'phone_verified' => 'boolean',
        'practicing_profession_date' => 'integer'
    ];

    protected $appends = ['full_name', 'is_approved'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(Specialization::class, 'doctor_specialization');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->user->first_name . ' ' . $this->user->last_name;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicationTrackers(): HasMany
    {
        return $this->hasMany(MedicationTracker::class);
    }

    public function complianceAlerts(): HasMany
    {
        return $this->hasMany(PatientComplianceAlert::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoredByPatients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_doctor_favorites')->withTimestamps();
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_doctor')
            ->withTimestamps()
            ->withPivot(['is_primary', 'method_id', 'appointment_period', 'queue', 'queue_number']);
    }
}
