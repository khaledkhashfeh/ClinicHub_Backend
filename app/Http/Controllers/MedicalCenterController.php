<?php

namespace App\Http\Controllers;

use App\Models\MedicalCenter;
use App\Models\SubscriptionPlan;
use App\Services\EvolutionApiService;
use App\Helpers\PhoneHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalCenterController extends Controller
{
    private $evolutionApiService;

    public function __construct(EvolutionApiService $evolutionApiService)
    {
        $this->evolutionApiService = $evolutionApiService;
    }
    /**
     * تسجيل دخول المركز الطبي
     * POST /api/center/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'اسم المستخدم مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $center = MedicalCenter::where('username', $request->username)->first();

        // التحقق من وجود المركز وصحة كلمة المرور
        if (!$center || !Hash::check($request->password, $center->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات تسجيل الدخول غير صحيحة'
            ], 401);
        }

        // التحقق من حالة الموافقة
        if ($center->status !== 'approved') {
            $statusMessage = match($center->status) {
                'pending' => 'حسابك قيد المراجعة. سيتم إشعارك عند الموافقة على طلبك.',
                'rejected' => 'تم رفض طلب تسجيلك. يرجى التواصل مع الإدارة.',
                default => 'حسابك غير مفعّل حالياً.'
            };
            
            return response()->json([
                'success' => false,
                'message' => $statusMessage,
                'status' => $center->status
            ], 403);
        }

        // Generate JWT token
        $token = JWTAuth::fromSubject($center);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء التوكن'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'role' => 'Medical_Center',
            'center' => [
                'id' => $center->id,
                'center_name' => $center->center_name,
                'is_approved' => $center->status === 'approved',
            ]
        ], 200);
    }

    /**
     * طلب إنشاء حساب مركز طبي جديد
     * POST /api/center/register-request
     */
    public function registerRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'center_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{10}$/',
            'governorate_id' => 'required|integer|exists:governorates,id',
            'district_id' => 'required_without:city_id|integer|exists:cities,id',
            'city_id' => 'required_without:district_id|integer|exists:cities,id',
            'clinic_count' => 'required|integer|min:1',
            'username' => 'required|string|max:255|unique:medical_centers,username',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
            'detailed_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'working_hours' => 'required|json',
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string|max:255',
            'services.*.price' => 'required_with:services|numeric|min:0',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'website_link' => 'nullable|url|max:255',
            'plan_id' => [
                'nullable',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(function ($query) {
                    return $query->where('target_type', 'medical_center');
                }),
            ],
        ], [
            'center_name.required' => 'اسم المركز الطبي مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'يجب أن يكون رقم الهاتف 10 أرقام.',
            'governorate_id.required' => 'المحافظة مطلوبة.',
            'governorate_id.exists' => 'المحافظة المحددة غير صحيحة.',
            'district_id.required_without' => 'المدينة مطلوبة.',
            'district_id.exists' => 'المدينة المحددة غير صحيحة.',
            'city_id.required_without' => 'المدينة مطلوبة.',
            'city_id.exists' => 'المدينة المحددة غير صحيحة.',
            'clinic_count.required' => 'عدد العيادات مطلوب.',
            'clinic_count.min' => 'يجب أن يكون عدد العيادات على الأقل 1.',
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'working_hours.required' => 'أوقات الدوام مطلوبة.',
            'working_hours.json' => 'أوقات الدوام يجب أن تكون بصيغة JSON صحيحة.',
            'services.*.name.required_with' => 'اسم الخدمة مطلوب.',
            'services.*.price.required_with' => 'سعر الخدمة مطلوب.',
            'services.*.price.numeric' => 'سعر الخدمة يجب أن يكون رقماً.',
            'services.*.price.min' => 'سعر الخدمة يجب أن يكون أكبر من أو يساوي 0.',
            'plan_id.exists' => 'الخطة المحددة غير صحيحة أو غير مخصصة للمراكز الطبية.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // إنشاء المركز الطبي
            // قبول district_id أو city_id (district_id هو في الواقع city_id حسب التسمية المستخدمة)
            $cityId = $request->city_id ?? $request->district_id;
            
            // معالجة working_hours
            $workingHours = null;
            if ($request->filled('working_hours')) {
                $workingHours = json_decode($request->working_hours, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'أوقات الدوام غير صحيحة. يجب أن تكون بصيغة JSON صحيحة.',
                    ], 400);
                }
            }

            $center = MedicalCenter::create([
                'center_name' => $request->center_name,
                'phone' => $request->phone,
                'governorate_id' => $request->governorate_id,
                'city_id' => $cityId,
                'clinic_count' => $request->clinic_count,
                'username' => $request->username,
                'password' => $request->password, // سيتم hash تلقائياً بواسطة mutator
                'detailed_address' => $request->detailed_address,
                'address_details' => $request->detailed_address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'facebook_link' => $request->facebook_link,
                'instagram_link' => $request->instagram_link,
                'website_link' => $request->website_link,
                'working_hours' => $workingHours,
                'status' => 'pending', // يبدأ كـ pending حتى يتم الموافقة
            ]);

            // رفع صورة المركز الرئيسية
            if ($request->hasFile('image')) {
                $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
                $path = $request->image->storeAs('images/medical_centers', $imageName, 'public');
                $center->update(['logo_url' => Storage::disk('public')->url($path)]);
            }

            // معالجة الخدمات
            if ($request->has('services')) {
                foreach ($request->services as $service) {
                    if (isset($service['name']) && isset($service['price'])) {
                        $center->services()->create([
                            'name' => $service['name'],
                            'price' => $service['price'],
                        ]);
                    }
                }
            }

            // رفع صور المعرض
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->extension();
                    $path = $image->storeAs('images/medical_centers/gallery', $imageName, 'public');
                    $center->galleryImages()->create([
                        'image_path' => Storage::disk('public')->url($path),
                    ]);
                }
            }

            // إرسال رسالة على الواتساب مع زر
            $formattedPhone = PhoneHelper::normalize($request->phone);
            $message = "شكراً لتسجيلك في ClinicHub!\n\n";
            $message .= "تم استلام طلب تسجيل المركز الطبي ({$request->center_name}) بنجاح.\n";
            $message .= "سيتم مراجعة البيانات والرد عليك قريباً.\n\n";
            if ($request->plan_id) {
                $plan = SubscriptionPlan::find($request->plan_id);
                if ($plan) {
                    $message .= "الخطة المختارة: {$plan->name}\n";
                }
            }
            $message .= "\nنتمنى لك تجربة ممتعة معنا! 🏥";
            
            // رابط الزر (يمكن تعديله حسب الحاجة)
            $buttonUrl = 'https://clinichub.space'; // الرابط المطلوب
            $buttonText = "تسجيل الدخول"; // نص الزر
            
            // إرسال الرسالة مع زر على الواتساب (لا نوقف العملية إذا فشل الإرسال)
            $whatsappResponse = $this->evolutionApiService->sendMessageWithButton(
                $formattedPhone,
                $message,
                $buttonText,
                $buttonUrl
            );
            if (!$whatsappResponse['success']) {
                \Log::warning('Failed to send WhatsApp message with button to medical center', [
                    'phone' => $formattedPhone,
                    'center_id' => $center->id,
                    'error' => $whatsappResponse['message'] ?? 'Unknown error'
                ]);
            }

            // إرجاع plan_id إذا كان موجوداً
            $planId = $request->plan_id;

            return response()->json([
                'success' => true,
                'message' => 'تم استلام طلب تسجيل المركز الطبي بنجاح. سيتم مراجعة البيانات والرد عليك عبر الواتساب.',
                'plan_id' => $planId,
            ], 202);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء طلب التسجيل.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * تحديث بيانات المركز الطبي
     * PUT /api/centers/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $center = MedicalCenter::findOrFail($id);

        // التحقق من أن المستخدم المصرح به هو صاحب المركز
        $authenticatedCenter = auth('medical_center')->user();
        if (!$authenticatedCenter || $authenticatedCenter->id !== $center->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل هذه البيانات.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'center_name' => 'sometimes|required|string|max:255',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('medical_centers', 'username')->ignore($center->id),
            ],
            'clinic_count' => 'sometimes|required|integer|min:1',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'detailed_address' => 'sometimes|nullable|string|max:500',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery_images.*' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'working_hours' => 'sometimes|nullable|json',
            'services' => 'sometimes|nullable|array',
            'services.*.name' => 'required_with:services|string|max:255',
            'services.*.price' => 'required_with:services|numeric|min:0',
            'facebook_link' => 'sometimes|nullable|url|max:255',
            'instagram_link' => 'sometimes|nullable|url|max:255',
            'website_link' => 'sometimes|nullable|url|max:255',
        ], [
            'center_name.required' => 'اسم المركز الطبي مطلوب.',
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقاً.',
            'clinic_count.required' => 'عدد العيادات مطلوب.',
            'clinic_count.min' => 'يجب أن يكون عدد العيادات على الأقل 1.',
            'working_hours.json' => 'أوقات الدوام يجب أن تكون بصيغة JSON صحيحة.',
            'services.*.name.required_with' => 'اسم الخدمة مطلوب.',
            'services.*.price.required_with' => 'سعر الخدمة مطلوب.',
            'services.*.price.numeric' => 'سعر الخدمة يجب أن يكون رقماً.',
            'services.*.price.min' => 'سعر الخدمة يجب أن يكون أكبر من أو يساوي 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $updateData = [];

            if ($request->has('center_name')) {
                $updateData['center_name'] = $request->center_name;
            }

            if ($request->has('username')) {
                $updateData['username'] = $request->username;
            }

            if ($request->has('clinic_count')) {
                $updateData['clinic_count'] = $request->clinic_count;
            }

            if ($request->has('latitude')) {
                $updateData['latitude'] = $request->latitude;
            }

            if ($request->has('longitude')) {
                $updateData['longitude'] = $request->longitude;
            }

            if ($request->has('detailed_address')) {
                $updateData['detailed_address'] = $request->detailed_address;
                $updateData['address_details'] = $request->detailed_address;
            }

            if ($request->has('facebook_link')) {
                $updateData['facebook_link'] = $request->facebook_link;
            }

            if ($request->has('instagram_link')) {
                $updateData['instagram_link'] = $request->instagram_link;
            }

            if ($request->has('website_link')) {
                $updateData['website_link'] = $request->website_link;
            }

            // معالجة working_hours
            if ($request->filled('working_hours')) {
                $workingHours = json_decode($request->working_hours, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'أوقات الدوام غير صحيحة. يجب أن تكون بصيغة JSON صحيحة.',
                    ], 400);
                }
                $updateData['working_hours'] = $workingHours;
            }

            $center->update($updateData);

            // معالجة الخدمات
            if ($request->has('services')) {
                // حذف الخدمات القديمة
                $center->services()->delete();

                // إضافة الخدمات الجديدة
                foreach ($request->services as $service) {
                    if (isset($service['name']) && isset($service['price'])) {
                        $center->services()->create([
                            'name' => $service['name'],
                            'price' => $service['price'],
                        ]);
                    }
                }
            }

            // معالجة صور المعرض
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->extension();
                    $path = $image->storeAs('images/medical_centers/gallery', $imageName, 'public');
                    $center->galleryImages()->create([
                        'image_path' => Storage::disk('public')->url($path),
                    ]);
                }
            }

            // تحديث الصورة إذا كانت موجودة
            if ($request->hasFile('image')) {
                // حذف الصورة القديمة إن وجدت
                if ($center->logo_url) {
                    $oldPath = str_replace(Storage::disk('public')->url(''), '', $center->logo_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
                $path = $request->image->storeAs('images/medical_centers', $imageName, 'public');
                $center->update(['logo_url' => Storage::disk('public')->url($path)]);
            }

            $center->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المركز الطبي بنجاح.',
                'data' => [
                    'id' => $center->id,
                    'center_name' => $center->center_name,
                    'username' => $center->username,
                    'clinic_count' => $center->clinic_count,
                    'latitude' => $center->latitude,
                    'longitude' => $center->longitude,
                    'phone' => $center->phone,
                    'status' => $center->status,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * حذف المركز الطبي
     * DELETE /api/centers/{id}
     */
    public function destroy($id): JsonResponse
    {
        $center = MedicalCenter::findOrFail($id);

        // التحقق من أن المستخدم المصرح به هو صاحب المركز
        $authenticatedCenter = auth('medical_center')->user();
        if (!$authenticatedCenter || $authenticatedCenter->id !== $center->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذا المركز.',
            ], 403);
        }

        try {
            // حذف الصورة إن وجدت
            if ($center->logo_url) {
                $oldPath = str_replace(Storage::disk('public')->url(''), '', $center->logo_url);
                Storage::disk('public')->delete($oldPath);
            }

            $center->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المركز الطبي بنجاح من النظام.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المركز.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
