<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use app\Services\UserService;

class UserController extends Controller
{
    // this function for general user sign up
    public function SignUp(Request $request, UserService $userService) {
        $validatedData = $request->validate([
            'full_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => ['required', 'regex:/^(0|\+963)\d{8}$/'],
            'email'         => ['nullable', 'email'],
            'username'      => ['required', 'string', 'max:255'],
            'password'      => ['required', 'string', 'min:8', 'max:18', 'confirmed'],
            'date_of_birth' => ['required', 'date'],
            'profile_image' => ['nullable', 'string'],
            'gender'        => ['required', 'in:male,female']
        ]);

        if (!$validatedData) {
            return response()->json([
                'success' => false,
                'message' => 'some data missed'
            ], 400);
        }
        try {
            $user = $userService->SignUp($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'the user has been created succefully',
                'data' => $user
            ], 200);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // this function for general user login
    public function Login(Request $request) {
        $validatedData = $request->validate([
            'phone_number' => ['required', 'regex:/^(0|\+963)\d{8}$/'],
            'password' => ['required', 'string', 'min:8', 'max:18']
        ]);

        if (!$token = JWTAuth::attempt([
            'phone' => $validatedData['phone_number'],
            'password' => $validatedData['password']
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'phone number or password is incorrect'
            ], 400);
        }

        $user = JWTAuth::user();

        if (!$user->secretary) {
            JWTAuth::invalidate($token);
            return response()->json([
                'success' => false,
                'message' => 'the user is not a secretary'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'login successful',
            'token' => $token
        ], 200);
    }
}
