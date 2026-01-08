<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminManagementController extends Controller
{
    /**
     * تسجيل دخول Admin
     * 
     * POST /api/admin/login
     */
    public function login(Request $request): JsonResponse
    {
        // قبول identifier أو phone أو email
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8'
        ], [
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        // قبول أي من: identifier, phone, email
        $identifier = $request->identifier ?? $request->phone ?? $request->email;
        
        if (!$identifier) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة',
                'errors' => [
                    'identifier' => ['البريد الإلكتروني أو رقم الهاتف مطلوب']
                ]
            ], 400);
        }

        // تحديد نوع المُعرّف (email أو phone)
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // إذا كان رقم هاتف، طبّعه إلى الصيغة الدولية
        if ($fieldType === 'phone') {
            $identifier = PhoneHelper::normalize($identifier);
        }

        // البحث عن المستخدم
        $user = User::where($fieldType, $identifier)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني/رقم الهاتف أو كلمة المرور غير صحيحة'
            ], 401);
        }

        // التحقق من أن المستخدم لديه Role admin
        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات للوصول'
            ], 403);
        }

        // التحقق من حالة الحساب
        if ($user->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'حسابك غير مفعّل حالياً'
            ], 403);
        }

        // إنشاء JWT Token
        $token = \JWTAuth::fromUser($user);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء التوكن'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => \JWTAuth::factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => 'admin'
            ]
        ]);
    }

}
