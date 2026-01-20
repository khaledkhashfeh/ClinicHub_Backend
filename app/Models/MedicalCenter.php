<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class MedicalCenter extends Model implements JWTSubject, AuthenticatableContract
{
    use HasFactory, Authenticatable;

    protected $fillable = [
        'user_id',
        'governorate_id',
        'city_id',
        'center_name',
        'address',
        'phone',
        'email',
        'area',
        'address_details',
        'location_coords',
        'description',
        'logo_url',
        'status',
        'username',
        'password',
        'clinic_count',
        'latitude',
        'longitude',
        'facebook_link',
        'instagram_link',
        'website_link',
        'working_hours',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'clinic_count' => 'integer',
        'working_hours' => 'array',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Mutator for password hashing
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }


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

    public function clinics()
    {
        return $this->hasMany(Clinic::class);
    }

    public function secretaries()
    {
        return $this->morphMany(Secretary::class, 'entity');
    }

    /**
     * العلاقة: الاشتراك الخاص بالمركز الطبي
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
     * Method: هل لدى المركز اشتراك نشط؟
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * العلاقة: خدمات المركز الطبي
     */
    public function services()
    {
        return $this->hasMany(MedicalCenterService::class);
    }

    /**
     * العلاقة: صور المعرض للمركز الطبي
     */
    public function galleryImages()
    {
        return $this->hasMany(MedicalCenterGalleryImage::class);
    }
}
