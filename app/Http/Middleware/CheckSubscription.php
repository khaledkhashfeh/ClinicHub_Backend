<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        // التحقق من نوع المستخدم ووجود اشتراك
        $subscribable = null;
        $subscribableType = null;

        if ($user->clinic) {
            $subscribable = $user->clinic;
            $subscribableType = 'App\Models\Clinic';
        } elseif ($user->medicalCenter) {
            $subscribable = $user->medicalCenter;
            $subscribableType = 'App\Models\MedicalCenter';
        }

        if (!$subscribable) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد عيادة أو مركز طبي مرتبط بحسابك'
            ], 403);
        }

        // جلب الاشتراك النشط
        $subscription = Subscription::where('subscribable_type', $subscribableType)
            ->where('subscribable_id', $subscribable->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد اشتراك نشط. يرجى الاشتراك في إحدى الخطط للمتابعة.',
                'requires_subscription' => true
            ], 403);
        }

        // إضافة الاشتراك إلى الـ request ليكون متاحاً في الـ controller
        $request->merge(['active_subscription' => $subscription]);

        return $next($request);
    }
}
