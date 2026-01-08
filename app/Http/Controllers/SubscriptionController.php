<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Clinic;
use App\Models\MedicalCenter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * جلب اشتراكي الحالي (للمستخدم المصادق)
     * 
     * GET /api/my-subscription
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $authenticatable = $request->user();
        
        if (!$authenticatable) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        $subscribable = null;
        $subscribableType = null;

        // حالة 1: المستخدم هو Clinic (يسجل دخول مباشرة)
        if ($authenticatable instanceof \App\Models\Clinic) {
            $subscribable = $authenticatable;
            $subscribableType = \App\Models\Clinic::class;
        }
        // حالة 2: المستخدم هو User وعنده MedicalCenter
        elseif ($authenticatable instanceof \App\Models\User) {
            $medicalCenter = $authenticatable->medicalCenter;
            if ($medicalCenter) {
                $subscribable = $medicalCenter;
                $subscribableType = \App\Models\MedicalCenter::class;
            }
        }

        if (!$subscribable) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد عيادة أو مركز طبي مرتبط بحسابك'
            ], 404);
        }

        // جلب الاشتراك النشط
        $subscription = Subscription::where('subscribable_type', $subscribableType)
            ->where('subscribable_id', $subscribable->id)
            ->with('plan.features')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد اشتراك',
                'has_subscription' => false
            ]);
        }

        // تحديث حالة الاشتراك إذا انتهى
        $subscription->updateStatusIfExpired();
        $subscription->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan,
                    'starts_at' => $subscription->starts_at->format('Y-m-d'),
                    'ends_at' => $subscription->ends_at->format('Y-m-d'),
                    'status' => $subscription->status,
                    'is_active' => $subscription->is_active,
                    'days_remaining' => $subscription->days_remaining
                ]
            ]
        ]);
    }

    /**
     * تعيين اشتراك لعيادة/مركز (خاص بالأدمن)
     * 
     * POST /api/admin/subscriptions
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subscribable_type' => 'required|in:clinic,medical_center',
            'subscribable_id' => 'required|integer',
            'plan_id' => 'required|exists:subscription_plans,id',
            'starts_at' => 'nullable|date',
            'status' => 'nullable|in:trial,active,expired,canceled',
            'notes' => 'nullable|string'
        ], [
            'subscribable_type.required' => 'نوع الحساب مطلوب',
            'subscribable_type.in' => 'نوع الحساب يجب أن يكون clinic أو medical_center',
            'subscribable_id.required' => 'معرف الحساب مطلوب',
            'plan_id.required' => 'الخطة مطلوبة',
            'plan_id.exists' => 'الخطة غير موجودة'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        // التحقق من وجود العيادة/المركز
        $subscribableType = $request->subscribable_type === 'clinic' ? Clinic::class : MedicalCenter::class;
        $subscribable = $subscribableType::find($request->subscribable_id);

        if (!$subscribable) {
            return response()->json([
                'success' => false,
                'message' => $request->subscribable_type === 'clinic' ? 'العيادة غير موجودة' : 'المركز الطبي غير موجود'
            ], 404);
        }

        // جلب الخطة
        $plan = SubscriptionPlan::find($request->plan_id);

        // التحقق من توافق نوع الخطة مع نوع الحساب
        $targetType = $request->subscribable_type === 'clinic' ? 'clinic' : 'medical_center';
        if ($plan->target_type !== $targetType) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخطة غير متوافقة مع نوع الحساب'
            ], 400);
        }

        // حساب تاريخ البداية والنهاية
        $startsAt = $request->starts_at ? Carbon::parse($request->starts_at) : now();
        $endsAt = $startsAt->copy()->addDays($plan->duration_days);

        try {
            // إلغاء أي اشتراكات سابقة نشطة
            Subscription::where('subscribable_type', $subscribableType)
                ->where('subscribable_id', $subscribable->id)
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            // إنشاء الاشتراك الجديد
            $subscription = Subscription::create([
                'subscription_plan_id' => $plan->id,
                'subscribable_type' => $subscribableType,
                'subscribable_id' => $subscribable->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $request->status ?? 'active',
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الاشتراك بنجاح',
                'data' => $subscription->load('plan.features')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعيين الاشتراك',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * تحديث حالة اشتراك (إلغاء/تجديد) (خاص بالأدمن)
     * 
     * PATCH /api/admin/subscriptions/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'الاشتراك غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:trial,active,expired,canceled',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $subscription->update([
                'status' => $request->status,
                'notes' => $request->notes ?? $subscription->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الاشتراك بنجاح',
                'data' => $subscription->load('plan.features')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الاشتراك',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * جلب كل الاشتراكات (خاص بالأدمن)
     * 
     * GET /api/admin/subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['plan.features', 'subscribable']);

        // تصفية حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // تصفية حسب نوع الحساب
        if ($request->has('subscribable_type')) {
            $type = $request->subscribable_type === 'clinic' ? Clinic::class : MedicalCenter::class;
            $query->where('subscribable_type', $type);
        }

        $subscriptions = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }
}
