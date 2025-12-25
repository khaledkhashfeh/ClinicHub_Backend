<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Clinic extends Model implements JWTSubject
{
    protected $fillable = [
        'user_id',
        'clinic_name',
        'phone',
        'specialization_id',
        'governorate_id',
        'city_id',
        'district_id',
        'detailed_address',
        'consultation_fee',
        'description',
        'username',
        'password',
        'main_image',
        'working_hours',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'working_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClinicService::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ClinicGalleryImage::class);
    }

    // Mutator for password hashing
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
