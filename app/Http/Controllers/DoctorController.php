<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorLoginRequest;
use App\Http\Requests\Doctor\DoctorRegisterRequest;
use App\Http\Requests\Doctor\UpdateDoctorProfileRequest;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Certification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class DoctorController extends Controller
{
    private $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function login(DoctorLoginRequest $request): JsonResponse
    {
        $request->validated();

        // Determine if identifier is email or phone
        $fieldType = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($fieldType, $request->identifier)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات تسجيل الدخول غير صحيحة'
            ], 401);
        }

        // Check if user has a doctor account
        $doctor = $user->doctor;
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد حساب طبيب مرتبط بهذا المستخدم'
            ], 404);
        }

        if ($doctor->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'حساب الطبيب غير معتمد حالياً.'
            ], 403);
        }

        if ($doctor->phone_verified === false) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى التحقق من رقم هاتفك قبل تسجيل الدخول.'
            ], 403);
        }

        // Generate JWT token for the user
        $token = JWTAuth::fromUser($user);

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
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60, // Convert minutes to seconds
            'doctor' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'is_approved' => $doctor->is_approved
            ]
        ]);
    }

    public function registerRequest(DoctorRegisterRequest $request)
    {
        $request->validated();

        // Create user first - using model to ensure mutator is called
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password; // Don't hash here - let the mutator handle it
        $user->status = 'approved'; // Users created via doctor registration are auto-approved
        $user->save();

        // Create doctor profile with pending status
        $doctor = Doctor::create([
            'user_id' => $user->id,
            'practicing_profession_date' => $request->practicing_profession_date,
            'governorate_id' => $request->governorate_id,
            'bio' => $request->bio,
            'distinguished_specialties' => $request->distinguished_specialties,
            'facebook_link' => $request->facebook_link,
            'instagram_link' => $request->instagram_link,
            'status' => 'pending', // New registrations start as pending
        ]);

        // Attach specializations
        if ($request->has('specializations_ids')) {
            $doctor->specializations()->attach($request->specializations_ids);
        }

        // Handle certifications
        if ($request->hasFile('certifications')) {
            $this->handleCertificationsUpload($request->file('certifications'), $doctor->id);
        }

        // Handle profile image upload
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/images/doctors'), $imageName);
            $doctor->update(['image' => 'storage/images/doctors/' . $imageName]);
        }

        // Send OTP to doctor's phone number for verification
        $otpResponse = $this->otpService->sendOtp($request->phone);
        if (!$otpResponse['success']) {
            // If OTP sending fails, log the error but still return success for registration
            \Log::error('Failed to send OTP to doctor: ' . $request->phone . ', Error: ' . $otpResponse['message']);
        }

        // Return accepted response (registration request submitted for review)
        $response = [
            'success' => true,
            'message' => 'تم استلام طلب إنشاء الحساب بنجاح. سيتم مراجعته والموافقة عليه من قبل الإدارة.'
        ];

        // If OTP was sent successfully, include a success message about verification
        if ($otpResponse['success']) {
            $response['message'] = 'تم استلام طلب إنشاء الحساب بنجاح. تم إرسال رمز تحقق إلى هاتفك. سيتم مراجعة الطلب والموافقة عليه من قبل الإدارة.';
        }

        return response()->json($response, 202);
    }

    private function handleCertificationsUpload($certifications, $doctorId)
    {
        if (is_array($certifications)) {
            foreach ($certifications as $certificationData) {
                if (isset($certificationData['name']) && isset($certificationData['image'])) {
                    $imageName = time() . '_' . uniqid() . '.' . $certificationData['image']->extension();
                    $certificationData['image']->move(public_path('storage/certifications'), $imageName);

                    Certification::create([
                        'doctor_id' => $doctorId,
                        'name' => $certificationData['name'],
                        'image_url' => 'storage/certifications/' . $imageName
                    ]);
                }
            }
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
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

    /**
     * Verify doctor's phone number using OTP
     */
    public function verifyPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6'
        ]);

        $verificationResult = $this->otpService->verifyOtp($request->phone, $request->otp);

        if ($verificationResult) {
            // Find the user by phone number and update verification status
            $user = User::where('phone', $request->phone)->first();

            if ($user) {
                // Update user's phone verification status
                $user->update([
                    'phone_verified_at' => now()
                ]);

                // If the user has a doctor account, update the doctor's verification status too
                if ($user->doctor) {
                    $user->doctor->update([
                        'phone_verified' => true
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من رقم الهاتف بنجاح.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.'
            ], 400);
        }
    }

    /**
     * Resend OTP to doctor's phone number
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $otpResponse = $this->otpService->resendOtp($request->phone);

        if ($otpResponse['success']) {
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال رمز التحقق الجديد إلى هاتفك.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
                'error' => $otpResponse['message']
            ], 500);
        }
    }

    public function update(UpdateDoctorProfileRequest $request): JsonResponse
    {
        $request->validated();

        $doctor = $request->user()->doctor;

        // Update user information
        if ($request->hasAny(["first_name", "last_name", "phone", "email"])) {
            $user = $doctor->user;
            $user->update($request->only(["first_name", "last_name", "phone", "email"]));
        }

        // Update doctor profile
        $doctorUpdateData = $request->only([
            'practicing_profession_date',
            'governorate_id',
            'district_id',
            'bio',
            'distinguished_specialties',
            'facebook_link',
            'instagram_link',
            'consultation_price'
        ]);

        // Handle profile image upload
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/images/doctors'), $imageName);
            $doctorUpdateData['image'] = 'storage/images/doctors/' . $imageName;
        }

        $doctor->update($doctorUpdateData);

        // Update specializations if provided
        if ($request->has('specializations_ids')) {
            $doctor->specializations()->sync($request->specializations_ids);
        }

        // Handle certifications
        if ($request->hasFile('certifications')) {
            $this->handleCertificationsUpload($request->file('certifications'), $doctor->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الطبيب بنجاح.',
            'data' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'bio' => $doctor->bio,
                'practicing_profession_date' => $doctor->practicing_profession_date,
                'instagram_link' => $doctor->instagram_link,
            ]
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد حساب طبيب مرتبط بك'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $doctor
        ]);
    }
}
