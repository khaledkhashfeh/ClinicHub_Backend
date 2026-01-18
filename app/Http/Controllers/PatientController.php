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
            $user->gender = 'male'; // قيمة افتراضية (سيتم تحديثها عند التسجيل)
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
        $user->save();

        // إنشاء Token دائماً (سواء كان مسجلاً أم لا)
        $token = JWTAuth::fromUser($user);

        // إعداد البيانات للاستجابة
        $responseData = [
            'patient_id' => $patient ? $patient->id : null,
            'first_name' => $user->first_name ?: null,
            'last_name' => $user->last_name ?: null,
            'phone' => $user->phone,
        ];

        // إذا كان مسجلاً
        if ($isRegistered) {
            $message = 'تم التحقق بنجاح، مرحباً بعودتك' . ($user->first_name ? ' يا ' . $user->first_name : '') . '.';
            
            return response()->json([
                'success' => true,
                'is_registered' => true,
                'message' => $message,
                'token' => $token,
                'role' => 'Patient',
                'data' => $responseData,
            ], 200);
        }

        // إذا لم يكن مسجلاً
        return response()->json([
            'success' => true,
            'is_registered' => false,
            'message' => 'تم التحقق من الرقم، يرجى إكمال إنشاء حسابك.',
            'token' => $token,
            'role' => 'Patient',
            'data' => $responseData,
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/', 'exists:users,phone'],
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'district_id' => ['required', 'integer', 'exists:cities,id'],
            'gender' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required' => 'الاسم الأخير مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.exists' => 'لم يتم التحقق من رقم الهاتف.',
            'governorate_id.required' => 'المحافظة مطلوبة.',
            'governorate_id.exists' => 'المحافظة المحددة غير صحيحة.',
            'district_id.required' => 'المنطقة مطلوبة.',
            'district_id.exists' => 'المنطقة المحددة غير صحيحة.',
            'gender.required' => 'الجنس مطلوب.',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 400);
        }

        // التحقق من Token (مطلوب - من verify-otp)
        $authenticatedUser = auth('api')->user();

        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التحقق من رقم الهاتف أولاً.',
            ], 401);
        }

        // البحث عن المستخدم
        $user = User::where('phone', $request->input('phone'))->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم التحقق من رقم الهاتف.',
            ], 400);
        }

        // التحقق من أن Token يخص نفس المستخدم
        if ($authenticatedUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Token غير صحيح لهذا المستخدم.',
            ], 403);
        }

        // التحقق من أنه لم يسجل من قبل (يجب أن يكون phone verified لكن لا يوجد patient أو first_name/last_name فارغين)
        if ($user->patient && !empty($user->first_name) && !empty($user->last_name)) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب مسجل مسبقاً.',
            ], 400);
        }

        // تحديث بيانات المستخدم
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->gender = $request->input('gender');
        $user->birth_date = $request->input('date_of_birth');
        // لا نغير password - تم إنشاؤه عند send-otp
        $user->status = 'approved';
        
        $user->save();

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
                'governorate_id' => $request->input('governorate_id'),
                'city_id' => $request->input('district_id'),
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

        // تسجيل الدخول وإنشاء Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح.',
            'token' => $token,
            'role' => 'Patient',
            'data' => [
                'patient_id' => $patient->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
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
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,' . $patient->user_id],
            'governorate_id' => ['sometimes', 'nullable', 'integer', 'exists:governorates,id'],
            'district_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'gender' => ['sometimes', 'nullable', 'in:male,female'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required' => 'الاسم الأخير مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'governorate_id.exists' => 'المحافظة المحددة غير صحيحة.',
            'district_id.exists' => 'المنطقة المحددة غير صحيحة.',
            'gender.in' => 'الجنس غير صحيح.',
            'date_of_birth.date' => 'تاريخ الميلاد غير صحيح.',
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
            $firstName = $request->input('first_name');
            if ($firstName !== null && trim($firstName) !== '') {
                $userData['first_name'] = trim($firstName);
                $hasUpdates = true;
            }

            $lastName = $request->input('last_name');
            if ($lastName !== null && trim($lastName) !== '') {
                $userData['last_name'] = trim($lastName);
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
            $governorateId = $request->input('governorate_id');
            if ($governorateId !== null) {
                $patientData['governorate_id'] = !empty($governorateId) ? (int)$governorateId : null;
                $hasUpdates = true;
            }

            $districtId = $request->input('district_id');
            if ($districtId !== null) {
                $patientData['city_id'] = !empty($districtId) ? (int)$districtId : null;
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
                'message' => 'تم تحديث البيانات بنجاح.',
                'data' => [
                    'id' => $patient->id,
                    'first_name' => $patient->user->first_name,
                    'last_name' => $patient->user->last_name,
                    'phone' => $patient->user->phone,
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