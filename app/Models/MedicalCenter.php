<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'governorate_id',
        'city_id',
        'name',
        'address',
        'phone',
        'email',
        'area',
        'address_details',
        'location_coords',
        'description',
        'logo_url',
        'status',
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
}
