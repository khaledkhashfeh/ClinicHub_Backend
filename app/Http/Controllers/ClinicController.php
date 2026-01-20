<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clinic\ClinicLoginRequest;
use App\Http\Requests\Clinic\ClinicRegisterRequest;
use App\Http\Requests\Clinic\UpdateClinicRequest;
use App\Models\Clinic;
use App\Models\User;
use App\Services\OtpService;
use App\Services\FirebaseService;
use App\Services\EvolutionApiService;
use App\Helpers\PhoneHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ClinicController extends Controller
{
    private $otpService;
    private $firebaseService;
    private $evolutionApiService;

    public function __construct(OtpService $otpService, FirebaseService $firebaseService, EvolutionApiService $evolutionApiService)
    {
        $this->otpService = $otpService;
        $this->firebaseService = $firebaseService;
        $this->evolutionApiService = $evolutionApiService;
    }

    // Authentication Methods
    public function login(ClinicLoginRequest $request): JsonResponse
    {
        $request->validated();

        // Determine if identifyer is username or phone
        $fieldType = filter_var($request->identifyer, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if ($fieldType !== 'email') {
            // Check if it's a phone number
            $fieldType = is_numeric($request->identifyer) ? 'phone' : 'username';
        }

        $clinic = Clinic::where($fieldType, $request->identifyer)->first();

        if (!$clinic || !Hash::check($request->password, $clinic->password) || $clinic->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'بيانات تسجيل الدخول غير صحيحة'
            ], 401);
        }

        // Generate JWT token for the clinic
        $token = JWTAuth::fromSubject($clinic);

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
            'role' => 'Clinic',
            'clinic' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
                'facebook_link' => $clinic->facebook_link,
                'instagram_link' => $clinic->instagram_link,
                'website_link' => $clinic->website_link
            ]
        ]);
    }

    public function register(ClinicRegisterRequest $request)
    {
        $request->validated();
        // return response()->json($request->);
        // Create clinic
        $clinic = Clinic::create([
            'clinic_name' => $request->clinic_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'specialization_id' => $request->specialization_id,
            'governorate_id' => $request->governorate_id,
            'city_id' => $request->city_id,
            'district_id' => $request->district_id,
            'address' => $request->address,
            'detailed_address' => $request->detailed_address,
            'floor' => $request->floor,
            'room_number' => $request->room_number,
            'consultation_fee' => $request->consultation_fee,
            'description' => $request->description,
            'username' => $request->username,
            'password' => $request->password, // This will be hashed by the mutator
            'status' => 'pending', // New clinics start as pending
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $imageName = time() . '_' . uniqid() . '.' . $request->main_image->extension();
            $path = $request->main_image->storeAs('images/clinics', $imageName, 'public');
            $clinic->update([ 'main_image' => $path]);
        }

        // Handle working hours
        if ($request->filled('working_hours')) {
            $workingHours = json_decode($request->working_hours, true);
            $clinic->update([ 'working_hours' => $workingHours]);
        }

        // Handle services
        if ($request->has('services')) {
            foreach ($request->services as $service) {
                if (isset($service['name']) && isset($service['price'])) {
                    $clinic->services()->create([
                        'name' => $service['name'],
                        'price' => $service['price'],
                    ]);
                }
            }
        }
        // Handle gallery images
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->extension();
                $path = $image->storeAs('images/clinics/gallery', $imageName, 'public');
                $clinic->galleryImages()->create([
                    'image_path' => $path
                ]);
            }
        }

        // Assign default subscription to the clinic
        $defaultPlan = \App\Models\SubscriptionPlan::where('target_type', 'clinic')
            ->where('is_active', true)
            ->orderBy('price', 'asc') // Get the lowest priced plan (usually free trial)
            ->first();

        if ($defaultPlan) {
            $startsAt = now();
            $endsAt = $startsAt->copy()->addDays($defaultPlan->duration_days);

            $subscription = \App\Models\Subscription::create([
                'subscription_plan_id' => $defaultPlan->id,
                'subscribable_type' => \App\Models\Clinic::class,
                'subscribable_id' => $clinic->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active', // Default to active for new clinics
                'notes' => 'Subscription assigned during clinic registration'
            ]);
        }

        // Send notification to admin users about the new clinic registration
        $this->notifyAdminsOfNewClinic($clinic);

        // Send WhatsApp message to the clinic
        $this->sendWhatsAppConfirmationToClinic($clinic, $defaultPlan);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام طلب تسجيل العيادة بنجاح. في انتظار الموافقة من قبل الإدارة.',
            'data' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
                'phone' => $clinic->phone,
                'consultation_fee' => $clinic->consultation_fee,
                'subscription_plan_id' => $defaultPlan ? $defaultPlan->id : null, // Return the plan ID
                'services' => $clinic->services->map(function ($service) {
                    return [
                        'name' => $service->name,
                        'price' => $service->price
                    ];
                })
            ]
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            // Get the token from the request
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد توken للتسجيل الخروج.'
                ], 400);
            }

            // Invalidate the token
            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح.'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تسجيل الخروج، يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }

    // OTP Methods
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^[\+]?[0-9\s\-\(\)]+$/'
        ]);

        $phone = $request->phone;

        // Check if clinic exists with this phone number
        $clinic = Clinic::where('phone', $phone)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد عيادة مسجلة بهذا الرقم'
            ], 404);
        }

        // Send OTP via service
        $otpResponse = $this->otpService->sendOtp($phone);

        if (isset($otpResponse['success']) && $otpResponse['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال رمز التحقق بنجاح',
                'data' => [
                    'phone' => $phone
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال رمز التحقق'
            ], 500);
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'otp' => 'required|string|size:6'
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        // Verify OTP
        $isValid = $this->otpService->verifyOtp($phone, $otp);

        if ($isValid) {
            // Find the clinic and update verification status if needed
            $clinic = Clinic::where('phone', $phone)->first();

            if ($clinic) {
                // You can update phone verification status here if you have such field
                // $clinic->update(['phone_verified_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من الرقم بنجاح',
                'data' => [
                    'phone' => $phone
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'
            ], 400);
        }
    }

    // Management Methods
    public function update(UpdateClinicRequest $request): JsonResponse
    {
        $request->validated();

        $clinic = auth()->guard('clinic')->user();

        // Update clinic information
        $clinicUpdateData = $request->only([
            'clinic_name',
            'phone',
            'specialization_id',
            'governorate_id',
            'city_id',
            'district_id',
            'detailed_address',
            'consultation_fee',
            'description',
            'username',
            'latitude',
            'longitude'
        ]);

        // Handle main image upload if provided
        if ($request->hasFile('main_image')) {
            $imageName = time() . '_' . uniqid() . '.' . $request->main_image->extension();
            $path = $request->main_image->storeAs('images/clinics', $imageName, 'public');
            $clinicUpdateData['main_image'] = $path;
        }

        // Handle working hours if provided
        if ($request->filled('working_hours')) {
            $workingHours = json_decode($request->working_hours, true);
            $clinicUpdateData['working_hours'] = $workingHours;
        }

        $clinic->update($clinicUpdateData);

        // Handle services if provided
        if ($request->has('services')) {
            // Delete existing services
            $clinic->services()->delete();

            // Add new services
            foreach ($request->services as $service) {
                if (isset($service['name']) && isset($service['price'])) {
                    $clinic->services()->create([
                        'name' => $service['name'],
                        'price' => $service['price'],
                    ]);
                }
            }
        }

        // Handle gallery images if provided
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->extension();
                $path = $image->storeAs('images/clinics/gallery', $imageName, 'public');
                $clinic->galleryImages()->create([
                    'image_path' => $path
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات العيادة بنجاح.',
            'data' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
                'phone' => $clinic->phone,
                'consultation_fee' => $clinic->consultation_fee,
                // Include other relevant clinic data as needed
            ]
        ]);
    }

    public function destroy(): JsonResponse
    {
        $clinic = auth()->guard('clinic')->user();

        // Delete related records first
        $clinic->services()->delete();
        $clinic->galleryImages()->delete();

        // Delete the clinic itself
        $clinic->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العيادة بنجاح.'
        ]);
    }

    public function show(): JsonResponse
    {
        $clinic = auth()->guard('clinic')->user();

        $clinic = Clinic::with([ 'specialization', 'governorate', 'district', 'services', 'galleryImages'])->find($clinic->id);

        return response()->json([
            'success' => true,
            'data' => $clinic
        ]);
    }

    /**
     * Notify admin users about a new clinic registration
     */
    private function notifyAdminsOfNewClinic($clinic)
    {
        try {
            // Get all admin users
            $adminRole = Role::findByName('admin');
            $adminUsers = $adminRole ? $adminRole->users : collect();

            foreach ($adminUsers as $admin) {
                // Get admin's FCM token
                $token = \App\Models\UserFcmToken::where('user_id', $admin->id)->value('fcm_token');

                if ($token) {
                    // Send notification via Firebase
                    $this->firebaseService->sendNotification(
                        $token,
                        'طلب تسجيل عيادة جديد',
                        "تم استلام طلب تسجيل لعيادة جديدة: {$clinic->clinic_name}. الرجاء مراجعة لوحة التحكم للموافقة."
                    );
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the registration process
            \Log::error('Failed to send notification to admins about new clinic registration: ' . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp confirmation message to the clinic
     */
    private function sendWhatsAppConfirmationToClinic($clinic, $defaultPlan = null)
    {
        try {
            // إرسال رسالة على الواتساب مع زر (same as medical center)
            $formattedPhone = PhoneHelper::normalize($clinic->phone);
            $message = "شكراً لتسجيلك في ClinicHub!\n\n";
            $message .= "تم استلام طلب تسجيل العيادة ({$clinic->clinic_name}) بنجاح.\n";
            $message .= "سيتم مراجعة البيانات والرد عليك قريباً.\n\n";
            if ($defaultPlan) {
                $message .= "الخطة المختارة: {$defaultPlan->name}\n";
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
                \Log::warning('Failed to send WhatsApp message with button to clinic', [
                    'phone' => $formattedPhone,
                    'clinic_id' => $clinic->id,
                    'error' => $whatsappResponse['message'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the registration process
            \Log::error('Failed to send WhatsApp confirmation to clinic: ' . $e->getMessage());
        }
    }
}
