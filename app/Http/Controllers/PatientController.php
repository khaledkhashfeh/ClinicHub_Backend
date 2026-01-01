<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\MedicalFile;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

class PatientController extends Controller
{
    private $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    /**
     * تسجيل الدخول التقليدي للمريض
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'password' => ['required', 'string'],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'يجب أن يكون رقم الهاتف 10 أرقام.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        // البحث عن المستخدم برقم الهاتف
        $user = User::where('phone', $request->input('phone'))->first();

        // التحقق من وجود الحساب
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب غير موجود، يرجى إنشاء حساب جديد.',
            ], 404);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->input('password'), $user->password)) {
            // Log للتحقق من المشكلة (فقط في وضع التطوير)
            if (config('app.debug')) {
                \Log::info('Password check failed', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'password_hash_length' => strlen($user->password),
                    'password_starts_with' => substr($user->password, 0, 7)
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'عذراً، رقم الهاتف أو كلمة المرور غير صحيحة.',
            ], 401);
        }

        // التحقق من وجود حساب مريض
        $patient = $user->patient;
        if (!$patient) {
            // التحقق من أن المستخدم أكمل التسجيل (لديه first_name و last_name)
            if (empty($user->first_name) || empty($user->last_name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إكمال التسجيل. يرجى إكمال بيانات التسجيل أولاً.',
                    'needs_registration' => true,
                ], 404);
            }
            
            // إذا كان المستخدم لديه بيانات لكن لا يوجد patient، إنشاء patient تلقائياً
            $patient = Patient::create([
                'user_id' => $user->id,
            ]);
        }

        // التحقق من حالة الحساب
        if ($user->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'حسابك في انتظار الموافقة من قبل الإدارة.',
            ], 403);
        }

        // إنشاء JWT Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'role' => 'Patient',
            'data' => [
                'patient_id' => $patient->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
            ],
        ], 200);
    }

    /**
     * إرسال رمز التحقق (OTP)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'يجب أن يكون رقم الهاتف 10 أرقام.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $phone = $request->input('phone');

        // البحث عن المستخدم أو إنشاء مستخدم جديد
        $user = User::firstOrNew(['phone' => $phone]);

        // التحقق من عدد المحاولات (محدد بـ 5 محاولات في 15 دقيقة)
        if ($user->exists && $user->otp_attempts >= 5) {
            $lastSent = $user->otp_last_sent_at;
            if ($lastSent && now()->diffInMinutes($lastSent) < 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم تجاوز عدد المحاولات المسموحة. يرجى المحاولة بعد 15 دقيقة.',
                ], 429);
            }
            // إعادة تعيين عدد المحاولات بعد 15 دقيقة
            $user->otp_attempts = 0;
        }

        // توليد OTP باستخدام OtpService
        $otpCode = $this->otpService->generateOtp();

        // حفظ OTP في قاعدة البيانات
        $user->otp_code = $otpCode;
        $user->otp_expires_at = now()->addMinutes(10); // صلاحية 10 دقائق
        $user->otp_attempts = 0;
        $user->otp_last_sent_at = now();
        
        if (!$user->exists) {
            $user->first_name = '';
            $user->last_name = '';
            $user->password = Hash::make(str()->random(32)); // كلمة مرور مؤقتة
            $user->status = 'pending';
        }
        
        $user->save();

        // إرسال OTP عبر Evolution API (نمرر OTP المولد لضمان التطابق)
        $otpResponse = $this->otpService->sendOtp($phone, $otpCode);
        
        if (!$otpResponse['success']) {
            // في حالة فشل الإرسال، نرجع رسالة خطأ
            \Log::error('Failed to send OTP to patient: ' . $phone . ', Error: ' . ($otpResponse['message'] ?? 'Unknown error'));
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
                'error' => config('app.debug') ? ($otpResponse['message'] ?? 'Unknown error') : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق بنجاح.',
        ], 200);
    }

    /**
     * التحقق من رمز OTP وتسجيل الدخول/التسجيل
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'otp.required' => 'رمز التحقق مطلوب.',
            'otp.size' => 'رمز التحقق يجب أن يكون 6 أرقام.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $phone = $request->input('phone');
        $otp = $request->input('otp');

        // البحث عن المستخدم
        $user = User::where('phone', $phone)->first();

        if (!$user || !$user->otp_code) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو انتهت صلاحيته.',
            ], 400);
        }

        // التحقق من عدد المحاولات
        if ($user->otp_attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز عدد محاولات التحقق المسموحة.',
            ], 400);
        }

        // التحقق من انتهاء صلاحية OTP
        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو انتهت صلاحيته.',
            ], 400);
        }

        // التحقق من OTP باستخدام OtpService (التحقق من Cache أولاً)
        $otpVerified = $this->otpService->verifyOtp($phone, $otp);
        
        // التحقق أيضاً من قاعدة البيانات (للتوافق مع النظام الحالي)
        // نتحقق من قاعدة البيانات إذا فشل التحقق من Cache
        $dbOtpMatch = false;
        if (!$otpVerified) {
            $dbOtpMatch = $user->otp_code === $otp;
        }
        
        // إذا لم ينجح التحقق من أي من المصدرين
        if (!$otpVerified && !$dbOtpMatch) {
            $user->otp_attempts += 1;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح أو انتهت صلاحيته.',
            ], 400);
        }

        // التحقق من وجود حساب مريض
        $patient = $user->patient;
        // التحقق من أن المستخدم مسجل (لديه patient و first_name و last_name ليسا فارغين)
        $isRegistered = $patient !== null && !empty($user->first_name) && !empty($user->last_name);

        // مسح OTP بعد التحقق الناجح
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->otp_attempts = 0;
        $user->phone_verified_at = now();
        $user->save();

        // إذا كان مسجلاً، تسجيل الدخول وإرجاع Token
        if ($isRegistered) {
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'is_registered' => true,
                'message' => 'تم التحقق بنجاح. تسجيل دخول.',
                'token' => $token,
                'patient_id' => $patient->id,
            ], 200);
        }

        // إذا لم يكن مسجلاً، إرجاع رسالة لإكمال التسجيل
        return response()->json([
            'success' => true,
            'is_registered' => false,
            'message' => 'تم التحقق بنجاح. يرجى إكمال بيانات التسجيل.',
        ], 200);
    }

    /**
     * إعادة إرسال رمز التحقق (OTP)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.regex' => 'يجب أن يكون رقم الهاتف 10 أرقام.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $phone = $request->input('phone');

        // البحث عن المستخدم
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف غير مسجل.',
            ], 404);
        }

        // التحقق من عدد المحاولات (محدد بـ 5 محاولات في 15 دقيقة)
        if ($user->otp_attempts >= 5) {
            $lastSent = $user->otp_last_sent_at;
            if ($lastSent && now()->diffInMinutes($lastSent) < 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم تجاوز عدد المحاولات المسموحة. يرجى المحاولة بعد 15 دقيقة.',
                ], 429);
            }
            // إعادة تعيين عدد المحاولات بعد 15 دقيقة
            $user->otp_attempts = 0;
        }

        // توليد OTP جديد باستخدام OtpService
        $otpCode = $this->otpService->generateOtp();

        // حفظ OTP في قاعدة البيانات
        $user->otp_code = $otpCode;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->otp_attempts = 0;
        $user->otp_last_sent_at = now();
        $user->save();

        // إرسال OTP عبر Evolution API (نمرر OTP المولد لضمان التطابق)
        $otpResponse = $this->otpService->sendOtp($phone, $otpCode);

        if (!$otpResponse['success']) {
            \Log::error('Failed to resend OTP to patient: ' . $phone . ', Error: ' . ($otpResponse['message'] ?? 'Unknown error'));
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
                'error' => config('app.debug') ? ($otpResponse['message'] ?? 'Unknown error') : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق الجديد بنجاح.',
        ], 200);
    }

    /**
     * إنشاء حساب مريض جديد
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/', 'exists:users,phone'],
            'governorate' => ['required', 'integer', 'exists:governorates,id'],
            'district' => ['required', 'integer', 'exists:cities,id'],
            'gender' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['nullable', 'email', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'has_chronic_diseases' => ['nullable', 'boolean'],
            'chronic_diseases_details' => ['required_if:has_chronic_diseases,true', 'nullable', 'string', 'max:1000'],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.exists' => 'لم يتم التحقق من رقم الهاتف.',
            'governorate.required' => 'المحافظة مطلوبة.',
            'governorate.exists' => 'المحافظة المحددة غير صحيحة.',
            'district.required' => 'المنطقة مطلوبة.',
            'district.exists' => 'المنطقة المحددة غير صحيحة.',
            'gender.required' => 'الجنس مطلوب.',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'chronic_diseases_details.required_if' => 'تفاصيل الأمراض المزمنة مطلوبة عند تحديد وجود أمراض مزمنة.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        // البحث عن المستخدم
        $user = User::where('phone', $request->input('phone'))->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم التحقق من رقم الهاتف.',
            ], 400);
        }

        // التحقق من أنه لم يسجل من قبل (يجب أن يكون phone_verified_at موجود لكن لا يوجد patient أو password مؤقت)
        if ($user->patient) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب مسجل مسبقاً.',
            ], 400);
        }

        // تقسيم الاسم الكامل
        $nameParts = explode(' ', trim($request->input('full_name')), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // التحقق من أن البريد الإلكتروني غير مستخدم من قبل مستخدم آخر
        if ($request->filled('email')) {
            $existingUser = User::where('email', $request->input('email'))
                ->where('id', '!=', $user->id)
                ->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'البريد الإلكتروني مستخدم مسبقاً.',
                    'errors' => ['email' => ['البريد الإلكتروني مستخدم مسبقاً.']],
                ], 400);
            }
        }

        // تحديث بيانات المستخدم
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $request->input('email');
        $user->gender = $request->input('gender');
        $user->birth_date = $request->input('date_of_birth');
        // استخدام الـ mutator في User model (لا نستخدم Hash::make لأن الـ mutator يقوم بالـ hash تلقائياً)
        $user->password = $request->input('password');
        $user->status = 'approved';
        
        try {
            $user->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // معالجة خطأ duplicate key
            if ($e->getCode() == '23505') {
                return response()->json([
                    'success' => false,
                    'message' => 'البريد الإلكتروني مستخدم مسبقاً.',
                    'errors' => ['email' => ['البريد الإلكتروني مستخدم مسبقاً.']],
                ], 400);
            }
            throw $e;
        }

        // رفع صورة الملف الشخصي إن وجدت
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('profiles/patients', 'public');
            $user->profile_photo_url = Storage::disk('public')->url($path);
            $user->save();
        }

        // إنشاء أو تحديث بيانات المريض
        $patient = Patient::updateOrCreate(
            ['user_id' => $user->id],
            [
                'governorate_id' => $request->input('governorate'),
                'city_id' => $request->input('district'),
                'occupation' => $request->input('occupation'),
            ]
        );

        // تعيين دور المريض (إنشاء الـ role إذا لم يكن موجوداً)
        // التأكد من وجود الـ role مع guard 'api'
        $role = Role::firstOrCreate(
            ['name' => 'patient', 'guard_name' => 'api']
        );
        
        // تعيين الـ role للمستخدم
        if (!$user->hasRole('patient', 'api')) {
            $user->assignRole($role);
        }

        // إنشاء أو تحديث الملف الطبي
        $medicalFile = MedicalFile::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'blood_type' => $request->input('blood_type'),
                'has_chronic_diseases' => $request->boolean('has_chronic_diseases', false),
                'past_medical_history' => $request->input('chronic_diseases_details'),
            ]
        );

        // تسجيل الدخول وإنشاء Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح وتسجيل الدخول.',
            'token' => $token,
            'role' => 'Patient',
            'data' => [
                'patient_id' => $patient->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
            ],
        ], 201);
    }

    /**
     * تحديث بيانات المريض
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);

        // التحقق من أن المستخدم المصرح به هو صاحب الحساب
        if (auth('api')->id() !== $patient->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل هذه البيانات.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,' . $patient->user_id],
            'governorate' => ['sometimes', 'nullable', 'integer', 'exists:governorates,id'],
            'district' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'gender' => ['sometimes', 'nullable', 'in:male,female'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blood_type' => ['sometimes', 'nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'has_chronic_diseases' => ['sometimes', 'nullable', 'boolean'],
            'chronic_diseases_details' => ['required_if:has_chronic_diseases,true', 'nullable', 'string', 'max:1000'],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'governorate.exists' => 'المحافظة المحددة غير صحيحة.',
            'district.exists' => 'المنطقة المحددة غير صحيحة.',
            'gender.in' => 'الجنس غير صحيح.',
            'date_of_birth.date' => 'تاريخ الميلاد غير صحيح.',
            'chronic_diseases_details.required_if' => 'تفاصيل الأمراض المزمنة مطلوبة عند تحديد وجود أمراض مزمنة.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $user = $patient->user;
            $userData = [];
            $patientData = [];
            $medicalFileData = [];
            $hasUpdates = false;

            // تحديث بيانات المستخدم
            // استخدام input() مباشرة للتحقق من multipart/form-data
            $fullName = $request->input('full_name');
            if ($fullName !== null && trim($fullName) !== '') {
                $nameParts = explode(' ', trim($fullName), 2);
                $userData['first_name'] = $nameParts[0];
                $userData['last_name'] = $nameParts[1] ?? '';
                $hasUpdates = true;
            }

            $email = $request->input('email');
            if ($email !== null) {
                $userData['email'] = $email ?: null;
                $hasUpdates = true;
            }

            $gender = $request->input('gender');
            if ($gender !== null && $gender !== '') {
                $userData['gender'] = $gender;
                $hasUpdates = true;
            }

            $dateOfBirth = $request->input('date_of_birth');
            if ($dateOfBirth !== null && $dateOfBirth !== '') {
                $userData['birth_date'] = $dateOfBirth;
                $hasUpdates = true;
            }

            // تحديث صورة الملف الشخصي
            if ($request->hasFile('image')) {
                // حذف الصورة القديمة إن وجدت
                if ($user->profile_photo_url) {
                    $oldPath = str_replace(Storage::disk('public')->url(''), '', $user->profile_photo_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $image = $request->file('image');
                $path = $image->store('profiles/patients', 'public');
                $userData['profile_photo_url'] = Storage::disk('public')->url($path);
                $hasUpdates = true;
            }

            // تحديث بيانات المستخدم
            if (!empty($userData)) {
                $user->update($userData);
            }

            // تحديث بيانات المريض
            $governorate = $request->input('governorate');
            if ($governorate !== null) {
                $patientData['governorate_id'] = !empty($governorate) ? (int)$governorate : null;
                $hasUpdates = true;
            }

            $district = $request->input('district');
            if ($district !== null) {
                $patientData['city_id'] = !empty($district) ? (int)$district : null;
                $hasUpdates = true;
            }

            $occupation = $request->input('occupation');
            if ($occupation !== null && $occupation !== '') {
                $patientData['occupation'] = $occupation;
                $hasUpdates = true;
            }

            // تحديث بيانات المريض
            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            // تحديث الملف الطبي
            $medicalFile = $patient->medicalFile;
            
            if (!$medicalFile) {
                $medicalFile = new MedicalFile();
                $medicalFile->patient_id = $patient->id;
            }

            $bloodType = $request->input('blood_type');
            if ($bloodType !== null) {
                // لا تستخدم ?: لأن القيمة قد تكون 'O' وهي falsy
                $medicalFileData['blood_type'] = ($bloodType === '' || $bloodType === null) ? null : $bloodType;
                $hasUpdates = true;
            }

            $hasChronicDiseases = $request->input('has_chronic_diseases');
            if ($hasChronicDiseases !== null) {
                $medicalFileData['has_chronic_diseases'] = $request->boolean('has_chronic_diseases', false);
                $hasUpdates = true;
            }

            $chronicDiseasesDetails = $request->input('chronic_diseases_details');
            if ($chronicDiseasesDetails !== null && $chronicDiseasesDetails !== '') {
                $medicalFileData['past_medical_history'] = $chronicDiseasesDetails;
                $hasUpdates = true;
            }

            // تحديث الملف الطبي
            if (!empty($medicalFileData)) {
                if ($medicalFile->exists) {
                    $medicalFile->update($medicalFileData);
                } else {
                    $medicalFile->fill($medicalFileData);
                    $medicalFile->save();
                }
            } elseif (!$medicalFile->exists) {
                // إذا كان الملف الطبي جديد ولم تكن هناك بيانات، احفظه على أي حال
                $medicalFile->save();
            }

            // التحقق من وجود تحديثات فعلية
            if (!$hasUpdates) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إرسال بيانات صحيحة للتحديث.',
                ], 400);
            }

            DB::commit();

            // إعادة تحميل البيانات من قاعدة البيانات
            $patient->refresh();
            $user->refresh();
            if ($medicalFile && $medicalFile->exists) {
                $medicalFile->refresh();
            }
            $patient->load(['user', 'medicalFile', 'governorate', 'city']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المريض بنجاح.',
                'data' => [
                    'id' => $patient->id,
                    'full_name' => $patient->user->full_name,
                    'first_name' => $patient->user->first_name,
                    'last_name' => $patient->user->last_name,
                    'phone' => $patient->user->phone,
                    'email' => $patient->user->email,
                    'gender' => $patient->user->gender,
                    'date_of_birth' => $patient->user->birth_date?->format('Y-m-d'),
                    'profile_photo_url' => $patient->user->profile_photo_url,
                    'governorate_id' => $patient->governorate_id,
                    'city_id' => $patient->city_id,
                    'occupation' => $patient->occupation,
                    'blood_type' => $patient->medicalFile?->blood_type,
                    'has_chronic_diseases' => $patient->medicalFile?->has_chronic_diseases,
                    'chronic_diseases_details' => $patient->medicalFile?->past_medical_history,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}