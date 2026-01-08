<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanController extends Controller
{
    /**
     * جلب خطط الاشتراك (مع إمكانية التصفية حسب النوع)
     * 
     * GET /api/subscription-plans?target_type=clinic
     */
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionPlan::with(['features', 'entitlements'])->active();

        // تصفية حسب نوع الحساب
        if ($request->has('target_type')) {
            $query->forTargetType($request->target_type);
        }

        $plans = $query->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * جلب خطة محددة
     * 
     * GET /api/subscription-plans/{id}
     */
    public function show($id): JsonResponse
    {
        $plan = SubscriptionPlan::with(['features', 'entitlements'])->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'الخطة غير موجودة'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * إنشاء خطة اشتراك جديدة (خاص بالأدمن)
     * 
     * POST /api/admin/subscription-plans
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:clinic,medical_center',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'features' => 'required|array|min:1',
            'features.*' => 'required|string',
            'entitlements' => 'nullable|array',
            'entitlements.*.key' => 'required|string',
            'entitlements.*.value' => 'required|string',
            'entitlements.*.type' => 'required|in:boolean,integer,string,decimal'
        ], [
            'name.required' => 'اسم الخطة مطلوب',
            'target_type.required' => 'نوع الحساب مطلوب',
            'target_type.in' => 'نوع الحساب يجب أن يكون clinic أو medical_center',
            'price.required' => 'السعر مطلوب',
            'duration_days.required' => 'مدة الاشتراك مطلوبة',
            'features.required' => 'الميزات مطلوبة',
            'features.min' => 'يجب إضافة ميزة واحدة على الأقل'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        // التحقق من عدم وجود خطة بنفس الاسم لنفس النوع
        $existingPlan = SubscriptionPlan::where('name', $request->name)
            ->where('target_type', $request->target_type)
            ->first();

        if ($existingPlan) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد خطة بنفس الاسم لهذا النوع من الحسابات',
                'errors' => [
                    'name' => ['اسم الخطة موجود مسبقاً لـ ' . ($request->target_type === 'clinic' ? 'العيادات' : 'المراكز الطبية')]
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {
            // إنشاء الخطة
            $plan = SubscriptionPlan::create([
                'name' => $request->name,
                'target_type' => $request->target_type,
                'price' => $request->price,
                'duration_days' => $request->duration_days,
                'description' => $request->description,
                'is_active' => true
            ]);

            // إضافة الميزات
            foreach ($request->features as $featureText) {
                SubscriptionPlanFeature::create([
                    'subscription_plan_id' => $plan->id,
                    'text' => $featureText
                ]);
            }

            // إضافة الـ Entitlements (القيود)
            if ($request->has('entitlements')) {
                foreach ($request->entitlements as $entitlement) {
                    \App\Models\SubscriptionPlanEntitlement::create([
                        'subscription_plan_id' => $plan->id,
                        'key' => $entitlement['key'],
                        'value' => $entitlement['value'],
                        'type' => $entitlement['type']
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الخطة بنجاح',
                'data' => $plan->load(['features', 'entitlements'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الخطة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * تعديل خطة اشتراك (خاص بالأدمن)
     * 
     * PUT /api/admin/subscription-plans/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $plan = SubscriptionPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'الخطة غير موجودة'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'target_type' => 'sometimes|required|in:clinic,medical_center',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'features' => 'sometimes|array',
            'features.*.id' => 'sometimes|exists:subscription_plan_features,id',
            'features.*.text' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        // التحقق من عدم وجود خطة أخرى بنفس الاسم لنفس النوع (عند تغيير الاسم)
        if ($request->has('name')) {
            $targetType = $request->target_type ?? $plan->target_type;
            
            $existingPlan = SubscriptionPlan::where('name', $request->name)
                ->where('target_type', $targetType)
                ->where('id', '!=', $id)
                ->first();

            if ($existingPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد خطة بنفس الاسم لهذا النوع من الحسابات',
                    'errors' => [
                        'name' => ['اسم الخطة موجود مسبقاً لـ ' . ($targetType === 'clinic' ? 'العيادات' : 'المراكز الطبية')]
                    ]
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // تحديث بيانات الخطة
            $plan->update($request->only([
                'name',
                'target_type',
                'price',
                'duration_days',
                'description',
                'is_active'
            ]));

            // تحديث الميزات إذا كانت موجودة في الطلب
            if ($request->has('features')) {
                foreach ($request->features as $feature) {
                    if (isset($feature['id'])) {
                        // تعديل ميزة موجودة
                        SubscriptionPlanFeature::where('id', $feature['id'])
                            ->where('subscription_plan_id', $plan->id)
                            ->update(['text' => $feature['text']]);
                    } else {
                        // إضافة ميزة جديدة
                        SubscriptionPlanFeature::create([
                            'subscription_plan_id' => $plan->id,
                            'text' => $feature['text']
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخطة بنجاح',
                'data' => $plan->load('features')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الخطة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * حذف خطة اشتراك (خاص بالأدمن)
     * 
     * DELETE /api/admin/subscription-plans/{id}
     */
    public function destroy($id): JsonResponse
    {
        $plan = SubscriptionPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'الخطة غير موجودة'
            ], 404);
        }

        // التحقق من عدم وجود اشتراكات نشطة على هذه الخطة
        $activeSubscriptions = $plan->subscriptions()->active()->count();
        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الخطة لوجود اشتراكات نشطة عليها',
                'active_subscriptions_count' => $activeSubscriptions
            ], 400);
        }

        try {
            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الخطة وجميع الميزات المرتبطة بها بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الخطة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
