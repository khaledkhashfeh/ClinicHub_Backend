<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_type',
        'price',
        'duration_days',
        'is_active',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة: الخطة لها ميزات متعددة
     */
    public function features()
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }

    /**
     * العلاقة: الخطة لها قيود/صلاحيات متعددة (entitlements)
     */
    public function entitlements()
    {
        return $this->hasMany(SubscriptionPlanEntitlement::class);
    }

    /**
     * العلاقة: الخطة لها اشتراكات متعددة
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * جلب قيمة entitlement محدد
     */
    public function getEntitlement(string $key, $default = null)
    {
        $entitlement = $this->entitlements()->where('key', $key)->first();
        return $entitlement ? $entitlement->getValue() : $default;
    }

    /**
     * Scope: جلب الخطط النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: جلب الخطط حسب نوع الحساب
     */
    public function scopeForTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }
}
