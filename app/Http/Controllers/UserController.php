<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
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
