<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use app\Services\UserService;

class UserController extends Controller
{
    // this function for general user sign up
    public function SignUp(Request $request, UserService $userService) {
    }

    // this function for medical team login (doctors and secretaries)
    public function Login(Request $request) {
        $validatedData = $request->validate([
            'identifier' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'max:18']
        ]);

        $identifier = $validatedData['identifier'];
        $password = $validatedData['password'];

        // Determine if identifier is email, phone, or username
        $fieldType = $this->getFieldType($identifier);

        // Initialize user variable
        $user = null;

        if ($fieldType === 'email' || $fieldType === 'phone') {
            // For email or phone, look directly in users table
            $user = User::where($fieldType, $identifier)->first();
        } else {
            // For username, check doctor and secretary tables
            // Check if username belongs to a doctor
            $doctor = \App\Models\Doctor::where('username', $identifier)->first();
            if ($doctor) {
                $user = $doctor->user;
            }

            if (!$user) {
                // Check if username belongs to a secretary
                $secretary = \App\Models\Secretary::where('username', $identifier)->first();
                if ($secretary) {
                    $user = $secretary->user;
                }
            }
        }
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'identifier or password is incorrect'
            ], 400);
        }

        // Check if user is a doctor or secretary
        $doctor = $user->doctor;
        $secretary = $user->secretary;

        if (!$doctor && !$secretary) {
            return response()->json([
                'success' => false,
                'message' => 'the user is not a doctor or secretary'
            ], 400);
        }

        // If it's a doctor, check additional conditions
        if ($doctor) {
            if ($doctor->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'doctor account is not approved yet'
                ], 403);
            }

            if ($doctor->phone_verified === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'please verify your phone number before logging in'
                ], 403);
            }
        }

        // Generate JWT token for the user
        $token = JWTAuth::fromUser($user);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'failed to create token'
            ], 400);
        }

        // Determine the user type and return appropriate data
        $userType = null;
        $userData = null;

        if ($doctor) {
            $userType = 'doctor';
            $userData = [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'is_approved' => $doctor->is_approved,
                'username' => $doctor->username
            ];
        } elseif ($secretary) {
            $userType = 'secretary';
            $userData = [
                'id' => $secretary->id,
                'full_name' => $user->full_name,
                'status' => $secretary->status,
                'entity_type' => $secretary->entity_type
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'login successful',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60, // Convert minutes to seconds
            'user_type' => $userType,
            'user' => $userData
        ], 200);
    }

    /**
     * Determine if the identifier is email, phone, or username
     */
    private function getFieldType($identifier)
    {
        // Check if it's an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        // Check if it's a phone number (basic validation)
        // Phone numbers typically start with + or 0 and have 10+ digits
        if (preg_match('/^(\+|0)\d{9,}$/', $identifier)) {
            return 'phone';
        }

        // Otherwise, assume it's a username
        return 'username';
    }

    public function logout(Request $request)
    {
        try {
            \Tymon\JWTAuth\Facades\JWTAuth::invalidate(\Tymon\JWTAuth\Facades\JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'logged out successfully'
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'failed to logout, please try again'
            ], 500);
        }
    }
}
