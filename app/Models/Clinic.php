<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Clinic extends Model implements JWTSubject, AuthenticatableContract
{
    use Authenticatable, HasFactory;

    protected $fillable = [
        'clinic_name',
        'phone',
        'email',
        'specialization_id',
        'governorate_id',
        'city_id',
        'district_id',
        'address',
        'detailed_address',
        'floor',
        'room_number',
        'consultation_fee',
        'description',
        'username',
        'password',
        'main_image',
        'main_image_file_id',
        'working_hours',
        'latitude',
        'longitude',
        'status',
        'otp_code',
        'otp_expires_at',
        'phone_verified_at',
        'facebook_link',
        'instagram_link',
        'website_link',
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
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
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function visitRecords()
    {
        return $this->hasMany(VisitRecord::class);
    }

    public function secretaries()
    {
        return $this->morphMany(Secretary::class, 'entity');
    }

    /**
     * العلاقة: الاشتراك الخاص بالعيادة
     */
    public function subscription()
    {
        return $this->morphOne(Subscription::class, 'subscribable')->latest();
    }

    /**
     * العلاقة: كل الاشتراكات (تاريخ الاشتراكات)
     */
    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * Method: الحصول على الاشتراك النشط
     */
    public function activeSubscription()
    {
        return $this->morphOne(Subscription::class, 'subscribable')
            ->active()
            ->with('plan.features');
    }

    /**
     * Method: هل لدى العيادة اشتراك نشط؟
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
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
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favoredByPatients()
    {
        return $this->belongsToMany(Patient::class, 'patient_clinic_favorites')->withTimestamps();
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'clinic_doctor')
            ->withTimestamps()
            ->withPivot(['is_primary', 'method_id', 'appointment_period', 'queue', 'queue_number']);
    }
}
