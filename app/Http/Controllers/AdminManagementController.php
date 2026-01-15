<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminManagementController extends Controller
{
    /**
     * Admin login method (already exists)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // For now, we'll use a simple approach to authenticate admin users
        // In a real application, you'd have a separate admin table or role-based authentication
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات تسجيل الدخول غير صحيحة'
            ], 401);
        }

        // Check if user has admin privileges (you can customize this logic)
        // For now, assuming any user can act as admin - in real app you'd check roles
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
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Approve a doctor registration
     */
    public function approveDoctor(Request $request, $id): JsonResponse
    {
        $doctor = Doctor::with('user')->find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'الدكتور غير موجود'
            ], 404);
        }

        if ($doctor->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'الدكتور مُوافَق عليه بالفعل'
            ], 400);
        }

        $doctor->update(['status' => 'approved']);

        // Also update the associated user's status if needed
        if ($doctor->user) {
            $doctor->user->update(['status' => 'approved']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم قبول تسجيل الدكتور بنجاح',
            'data' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'status' => $doctor->status
            ]
        ]);
    }

    /**
     * Reject a doctor registration
     */
    public function rejectDoctor(Request $request, $id): JsonResponse
    {
        $doctor = Doctor::with('user')->find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'الدكتور غير موجود'
            ], 404);
        }

        if ($doctor->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'تم رفض تسجيل الدكتور بالفعل'
            ], 400);
        }

        $doctor->update(['status' => 'rejected']);

        // Also update the associated user's status if needed
        if ($doctor->user) {
            $doctor->user->update(['status' => 'rejected']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض تسجيل الدكتور بنجاح',
            'data' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'status' => $doctor->status
            ]
        ]);
    }

    /**
     * Get all pending doctors
     */
    public function getPendingDoctors(): JsonResponse
    {
        $doctors = Doctor::with('user', 'specializations', 'governorate', 'city', 'district')
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $doctors
        ]);
    }

    /**
     * Approve a clinic registration
     */
    public function approveClinic(Request $request, $id): JsonResponse
    {
        $clinic = Clinic::find($id);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'العيادة غير موجودة'
            ], 404);
        }

        if ($clinic->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'العيادة مُوافَقة عليها بالفعل'
            ], 400);
        }

        $clinic->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'تم قبول تسجيل العيادة بنجاح',
            'data' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
                'status' => $clinic->status
            ]
        ]);
    }

    /**
     * Reject a clinic registration
     */
    public function rejectClinic(Request $request, $id): JsonResponse
    {
        $clinic = Clinic::find($id);

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'العيادة غير موجودة'
            ], 404);
        }

        if ($clinic->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'تم رفض تسجيل العيادة بالفعل'
            ], 400);
        }

        $clinic->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض تسجيل العيادة بنجاح',
            'data' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
                'status' => $clinic->status
            ]
        ]);
    }

    /**
     * Get all pending clinics
     */
    public function getPendingClinics(): JsonResponse
    {
        $clinics = Clinic::with('specialization', 'governorate', 'city', 'district')
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clinics
        ]);
    }

    /**
     * Approve a secretary registration
     */
    public function approveSecretary(Request $request, $id): JsonResponse
    {
        $secretary = Secretary::with('user', 'entity')->find($id);

        if (!$secretary) {
            return response()->json([
                'success' => false,
                'message' => 'السكرتيرة غير موجودة'
            ], 404);
        }

        if ($secretary->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'السكرتيرة مُوافَقة عليها بالفعل'
            ], 400);
        }

        $secretary->update(['status' => 'approved']);

        // Also update the associated user's status if needed
        if ($secretary->user) {
            $secretary->user->update(['status' => 'approved']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم قبول تسجيل السكرتيرة بنجاح',
            'data' => [
                'id' => $secretary->id,
                'username' => $secretary->username,
                'status' => $secretary->status,
                'entity_type' => $secretary->entity_type,
                'entity_name' => $secretary->entity ? ($secretary->entity->clinic_name ?? $secretary->entity->name ?? 'Entity') : 'Unknown'
            ]
        ]);
    }

    /**
     * Reject a secretary registration
     */
    public function rejectSecretary(Request $request, $id): JsonResponse
    {
        $secretary = Secretary::with('user', 'entity')->find($id);

        if (!$secretary) {
            return response()->json([
                'success' => false,
                'message' => 'السكرتيرة غير موجودة'
            ], 404);
        }

        if ($secretary->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'تم رفض تسجيل السكرتيرة بالفعل'
            ], 400);
        }

        $secretary->update(['status' => 'rejected']);

        // Also update the associated user's status if needed
        if ($secretary->user) {
            $secretary->user->update(['status' => 'rejected']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض تسجيل السكرتيرة بنجاح',
            'data' => [
                'id' => $secretary->id,
                'username' => $secretary->username,
                'status' => $secretary->status
            ]
        ]);
    }

    /**
     * Get all pending secretaries
     */
    public function getPendingSecretaries(): JsonResponse
    {
        $secretaries = Secretary::with('user', 'entity')
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $secretaries
        ]);
    }
}