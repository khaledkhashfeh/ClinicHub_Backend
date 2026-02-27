<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clinic\ClinicLoginRequest;
use App\Http\Requests\Clinic\ClinicRegisterRequest;
use App\Http\Requests\Clinic\UpdateClinicRequest;
use App\Models\Clinic;
use App\Models\User;
use App\Models\ClinicGalleryImage;
use App\Models\ClinicService;
use App\Services\OtpService;
use App\Services\FirebaseService;
use App\Services\EvolutionApiService;
use App\Services\ImageKitService;
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
    private $imageKitService;

    public function __construct(OtpService $otpService, FirebaseService $firebaseService, EvolutionApiService $evolutionApiService, ImageKitService $imageKitService)
    {
        $this->otpService = $otpService;
        $this->firebaseService = $firebaseService;
        $this->evolutionApiService = $evolutionApiService;
        $this->imageKitService = $imageKitService;
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
            'facebook_link' => $request->facebook_link,
            'instagram_link' => $request->instagram_link,
            'website_link' => $request->website_link,
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            // حذف الصورة القديمة إن وجدت
            if ($clinic->main_image_file_id) {
                $this->imageKitService->delete($clinic->main_image_file_id);
            }
            
            $uploadResult = $this->imageKitService->upload($request->main_image, 'clinics/main');
            $clinic->update([
                'main_image' => $uploadResult['url'],
                'main_image_file_id' => $uploadResult['fileId']
            ]);
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
                $uploadResult = $this->imageKitService->upload($image, 'clinics/gallery');
                $clinic->galleryImages()->create([
                    'image_path' => $uploadResult['url'],
                    'file_id' => $uploadResult['fileId']
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
            // حذف الصورة القديمة إن وجدت
            if ($clinic->main_image_file_id) {
                $this->imageKitService->delete($clinic->main_image_file_id);
            }
            
            $uploadResult = $this->imageKitService->upload($request->main_image, 'clinics/main');
            $clinicUpdateData['main_image'] = $uploadResult['url'];
            $clinicUpdateData['main_image_file_id'] = $uploadResult['fileId'];
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
                $uploadResult = $this->imageKitService->upload($image, 'clinics/gallery');
                $clinic->galleryImages()->create([
                    'image_path' => $uploadResult['url'],
                    'file_id' => $uploadResult['fileId']
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

        // حذف الصور من ImageKit قبل حذف السجلات
        if ($clinic->main_image_file_id) {
            $this->imageKitService->delete($clinic->main_image_file_id);
        }

        // حذف صور المعرض من ImageKit
        foreach ($clinic->galleryImages as $galleryImage) {
            if ($galleryImage->file_id) {
                $this->imageKitService->delete($galleryImage->file_id);
            }
        }

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

    /**
     * List all clinics (for admin or authorized users)
     */
    public function index(): JsonResponse
    {
        try {
            // Check if user is admin or has appropriate permissions
            $user = auth()->user();

            // For now, restrict to admin users only
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $clinics = \App\Models\Clinic::with(['specialization', 'governorate', 'district', 'services', 'galleryImages'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $clinics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clinics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Show a specific clinic by ID
     */
    public function showById($id): JsonResponse
    {
        try {
            $clinic = \App\Models\Clinic::with(['specialization', 'governorate', 'district', 'services', 'galleryImages'])
                ->find($id);

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clinic not found'
                ], 404);
            }

            // Check if user has permission to view this clinic
            $authenticatedClinic = auth()->guard('clinic')->user();
            $user = auth()->user();

            if (!$authenticatedClinic || $authenticatedClinic->id !== $clinic->id) {
                // If not the clinic owner, check if user is admin
                if (!$user || !$user->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $clinic
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clinic',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Activate a clinic
     */
    public function activate($id): JsonResponse
    {
        try {
            // Check if user is admin
            $user = auth()->user();
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $clinic = \App\Models\Clinic::find($id);
            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clinic not found'
                ], 404);
            }

            $clinic->update(['status' => 'approved']);

            return response()->json([
                'success' => true,
                'message' => 'Clinic activated successfully',
                'data' => [
                    'id' => $clinic->id,
                    'status' => $clinic->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error activating clinic',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Deactivate a clinic
     */
    public function deactivate($id): JsonResponse
    {
        try {
            // Check if user is admin
            $user = auth()->user();
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $clinic = \App\Models\Clinic::find($id);
            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clinic not found'
                ], 404);
            }

            $clinic->update(['status' => 'inactive']); // Assuming 'inactive' status exists

            return response()->json([
                'success' => true,
                'message' => 'Clinic deactivated successfully',
                'data' => [
                    'id' => $clinic->id,
                    'status' => $clinic->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deactivating clinic',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get doctors associated with the clinic
     */
    public function getDoctors(Request $request): JsonResponse
    {
        try {
            $clinic = $request->user(); // Since middleware is auth:clinic, this should be the clinic

            // If the authenticated user is not a clinic, check if they're an admin
            $user = auth()->user();
            if (!$clinic || get_class($clinic) !== \App\Models\Clinic::class) {
                if (!$user || !$user->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
                // If admin, they can view any clinic's doctors if they provide clinic_id
                $clinicId = $request->query('clinic_id');
                if (!$clinicId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'clinic_id is required for admin access'
                    ], 400);
                }
                $clinic = \App\Models\Clinic::find($clinicId);
                if (!$clinic) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Clinic not found'
                    ], 404);
                }
            } else {
                // Authenticated as clinic, so use the authenticated clinic
                $clinic = auth()->guard('clinic')->user();
            }

            $doctors = $clinic->doctors()->with(['user', 'specializations'])->get();

            return response()->json([
                'success' => true,
                'data' => $doctors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving doctors',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Add a doctor to the clinic
     */
    public function addDoctor(Request $request): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            // Check if authenticated user is a clinic
            if (!$clinic || get_class($clinic) !== \App\Models\Clinic::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - must be authenticated as clinic'
                ], 403);
            }

            $request->validate([
                'doctor_id' => 'required|exists:doctors,id'
            ]);

            $doctor = \App\Models\Doctor::find($request->doctor_id);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            // Check if doctor is already associated with this clinic
            $existingAssociation = DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->first();

            if ($existingAssociation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is already associated with this clinic'
                ], 400);
            }

            // Add doctor to clinic
            DB::table('clinic_doctor')->insert([
                'clinic_id' => $clinic->id,
                'doctor_id' => $doctor->id,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor added to clinic successfully',
                'data' => [
                    'clinic_id' => $clinic->id,
                    'doctor_id' => $doctor->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding doctor to clinic',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove a doctor from the clinic
     */
    public function removeDoctor($doctorId): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            // Check if authenticated user is a clinic
            if (!$clinic || get_class($clinic) !== \App\Models\Clinic::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - must be authenticated as clinic'
                ], 403);
            }

            $doctor = \App\Models\Doctor::find($doctorId);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            // Check if doctor is associated with this clinic
            $association = DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->first();

            if (!$association) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with this clinic'
                ], 400);
            }

            // Remove doctor from clinic
            DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Doctor removed from clinic successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing doctor from clinic',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Set a doctor as primary doctor for the clinic
     */
    public function setPrimaryDoctor($doctorId): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            // Check if authenticated user is a clinic
            if (!$clinic || get_class($clinic) !== \App\Models\Clinic::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - must be authenticated as clinic'
                ], 403);
            }

            $doctor = \App\Models\Doctor::find($doctorId);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            // Check if doctor is associated with this clinic
            $association = DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->first();

            if (!$association) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with this clinic'
                ], 400);
            }

            // First, unset any existing primary doctor
            DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->update(['is_primary' => false]);

            // Set this doctor as primary
            DB::table('clinic_doctor')
                ->where('clinic_id', $clinic->id)
                ->where('doctor_id', $doctor->id)
                ->update([
                    'is_primary' => true,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Primary doctor set successfully',
                'data' => [
                    'clinic_id' => $clinic->id,
                    'doctor_id' => $doctor->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error setting primary doctor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // ============================================
    // Gallery Images Methods
    // ============================================

    /**
     * Upload gallery images
     * POST /api/v1/clinic/gallery
     */
    public function uploadGalleryImages(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
                'category' => 'nullable|string|max:255',
            ], [
                'images.required' => 'يجب إرفاق صورة واحدة على الأقل',
                'images.*.image' => 'يجب أن يكون الملف صورة',
                'images.*.mimes' => 'نوع الصورة يجب أن يكون: jpeg, png, jpg, webp',
                'images.*.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت',
            ]);

            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                $uploadResult = $this->imageKitService->upload($image, 'clinics/gallery');
                
                $galleryImage = ClinicGalleryImage::create([
                    'clinic_id' => $clinic->id,
                    'image_path' => $uploadResult['url'],
                    'file_id' => $uploadResult['fileId'],
                    'category' => $request->input('category'),
                ]);

                $uploadedImages[] = [
                    'id' => $galleryImage->id,
                    'url' => $galleryImage->image_path,
                    'category' => $galleryImage->category,
                    'created_at' => $galleryImage->created_at->format('Y-m-d'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصور بنجاح',
                'data' => $uploadedImages
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الصور',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get gallery images
     * GET /api/v1/clinic/gallery
     */
    public function getGalleryImages(): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            $galleryImages = ClinicGalleryImage::where('clinic_id', $clinic->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_path,
                        'category' => $image->category,
                        'created_at' => $image->created_at->format('Y-m-d'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $galleryImages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الصور',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete gallery image
     * DELETE /api/v1/clinic/gallery/{image_id}
     */
    public function deleteGalleryImage($imageId): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            // التحقق من أن الصورة تخص العيادة
            $image = ClinicGalleryImage::where('id', $imageId)
                ->where('clinic_id', $clinic->id)
                ->first();

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'الصورة غير موجودة أو لا تخص عيادتك'
                ], 404);
            }

            // حذف الصورة من ImageKit
            if ($image->file_id) {
                $this->imageKitService->delete($image->file_id);
            }

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الصورة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الصورة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // ============================================
    // Services Methods
    // ============================================

    /**
     * Create service
     * POST /api/v1/clinic/services
     */
    public function createService(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:10|in:syp,$,usd,eur',
                'description' => 'nullable|string',
            ], [
                'name.required' => 'اسم الخدمة مطلوب',
                'price.required' => 'السعر مطلوب',
                'price.numeric' => 'السعر يجب أن يكون رقماً',
                'price.min' => 'السعر يجب أن يكون أكبر من أو يساوي صفر',
                'currency.in' => 'العملة يجب أن تكون: syp, $, usd, eur',
            ]);

            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            $service = ClinicService::create([
                'clinic_id' => $clinic->id,
                'name' => $request->name,
                'price' => $request->price,
                'currency' => $request->currency ?? 'syp',
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الخدمة بنجاح',
                'data' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'currency' => $service->currency,
                    'description' => $service->description,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الخدمة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update service
     * PATCH /api/v1/clinic/services/{service_id}
     */
    public function updateService(Request $request, $serviceId): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'sometimes|string|max:10|in:syp,$,usd,eur',
                'description' => 'nullable|string',
            ], [
                'price.numeric' => 'السعر يجب أن يكون رقماً',
                'price.min' => 'السعر يجب أن يكون أكبر من أو يساوي صفر',
                'currency.in' => 'العملة يجب أن تكون: syp, $, usd, eur',
            ]);

            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            // التحقق من أن الخدمة تخص العيادة
            $service = ClinicService::where('id', $serviceId)
                ->where('clinic_id', $clinic->id)
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخدمة غير موجودة أو لا تخص عيادتك'
                ], 404);
            }

            // تحديث الحقول المرسلة فقط
            if ($request->has('name')) {
                $service->name = $request->name;
            }
            if ($request->has('price')) {
                $service->price = $request->price;
            }
            if ($request->has('currency')) {
                $service->currency = $request->currency;
            }
            if ($request->has('description')) {
                $service->description = $request->description;
            }

            $service->save();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخدمة بنجاح',
                'data' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'currency' => $service->currency,
                    'description' => $service->description,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الخدمة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete service
     * DELETE /api/v1/clinic/services/{service_id}
     */
    public function deleteService($serviceId): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            // التحقق من أن الخدمة تخص العيادة
            $service = ClinicService::where('id', $serviceId)
                ->where('clinic_id', $clinic->id)
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخدمة غير موجودة أو لا تخص عيادتك'
                ], 404);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الخدمة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الخدمة',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get services
     * GET /api/v1/clinic/services
     */
    public function getServices(): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح'
                ], 401);
            }

            $services = ClinicService::where('clinic_id', $clinic->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => $service->price,
                        'currency' => $service->currency,
                        'description' => $service->description,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الخدمات',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
