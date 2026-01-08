<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'subscribable_type',
        'subscribable_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = ['is_active', 'days_remaining'];

    /**
     * العلاقة: الاشتراك ينتمي لخطة واحدة
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * العلاقة Polymorphic: الاشتراك يمكن أن يكون لـ Clinic أو MedicalCenter
     */
    public function subscribable()
    {
        return $this->morphTo();
    }

    /**
     * Accessor: هل الاشتراك نشط؟
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' 
            && $this->ends_at 
            && Carbon::parse($this->ends_at)->isFuture();
    }

    /**
     * Accessor: عدد الأيام المتبقية
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->ends_at) {
            return 0;
        }

        $days = Carbon::now()->diffInDays(Carbon::parse($this->ends_at), false);
        return max(0, (int) $days);
    }

    /**
     * Scope: جلب الاشتراكات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    /**
     * Scope: جلب الاشتراكات المنتهية
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '<=', now());
    }

    /**
     * Scope: جلب الاشتراكات التجريبية
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    /**
     * Method: تحديث حالة الاشتراك تلقائياً بناءً على التاريخ
     */
    public function updateStatusIfExpired()
    {
        if ($this->status === 'active' && $this->ends_at && Carbon::parse($this->ends_at)->isPast()) {
            $this->update(['status' => 'expired']);
            return true;
        }
        return false;
    }
}
