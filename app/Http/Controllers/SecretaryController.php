<?php

namespace App\Http\Controllers;

use App\Models\Secretary;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class SecretaryController extends Controller
{
    // this function for creating secretary account
    public function createSecretary(Request $request, UserService $userService) {
        $validatedData = $request->validate([
            'full_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => ['required', 'regex:/^(0|\+963)\d{8}$/'],
            'email'         => ['nullable', 'email'],
            'username'      => ['required', 'string', 'max:255'],
            'password'      => ['required', 'string', 'min:8', 'max:18', 'confirmed'],
            'date_of_birth' => ['required', 'date'],
            'profile_image' => ['nullable', 'string'],
            'gender'        => ['required', 'in:male,female'],
            'entity_id' => ['required', 'integer'],
            'entity_type' => ['required', 'string', 'in:clinic,medical_center']
        ]);

        try {
            $result = DB::transaction(function () use ($validatedData, $userService) {
                $user = $userService->SignUp($validatedData);

                $secretary = Secretary::create([
                    'user_id' => $user->id,
                    'entity_id' => $validatedData['entity_id'],
                    'entity_type' => $validatedData['entity_type']
                ]);

                return compact('user', 'secretary');
            });

            return response()->json([
                'success' => true,
                'message' => 'secretary account has been created successfully',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}