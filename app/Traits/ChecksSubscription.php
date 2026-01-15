<?php

namespace App\Traits;

use App\Models\Subscription;

trait ChecksSubscription
{
    /**
     * التحقق من صلاحية محددة في الاشتراك
     * 
     * @param mixed $entity (Clinic أو MedicalCenter)
     * @param string $key (مثل: enable_secretary, max_patients)
     * @param mixed $default القيمة الافتراضية إذا لم يوجد entitlement
     * @return mixed
     */
    protected function checkEntitlement($entity, string $key, $default = null)
    {
        if (!$entity) {
            return $default;
        }

        // جلب الاشتراك النشط
        $subscription = $entity->subscriptions()
            ->where('status', 'active')
            ->with('plan.entitlements')
            ->latest()
            ->first();

        if (!$subscription) {
            return $default;
        }

        return $subscription->plan->getEntitlement($key, $default);
    }

    /**
     * التحقق من أن الخطة تسمح بميزة معينة (boolean)
     */
    protected function hasFeatureAccess($entity, string $featureKey): bool
    {
        return (bool) $this->checkEntitlement($entity, $featureKey, false);
    }

    /**
     * التحقق من أن العدد لم يتجاوز الحد المسموح
     * 
     * @param mixed $entity
     * @param string $limitKey (مثل: max_patients_per_month)
     * @param int $currentCount العدد الحالي
     * @return bool
     */
    protected function isWithinLimit($entity, string $limitKey, int $currentCount): bool
    {
        $limit = (int) $this->checkEntitlement($entity, $limitKey, PHP_INT_MAX);
        return $currentCount < $limit;
    }

    /**
     * إرجاع رسالة خطأ موحدة للاشتراك
     */
    protected function subscriptionErrorResponse(string $message = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? 'خطتك الحالية لا تدعم هذه الميزة. يرجى الترقية'
        ], 403);
    }
}

