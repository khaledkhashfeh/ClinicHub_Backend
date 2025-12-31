<?php

namespace App\Http\Controllers;

use App\Models\Secretary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class SecretaryController extends Controller
{
    // this function for creating secretary account
    public function createSecretary(Request $request) {
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

        if (!$validatedData) {
            return response()->json([
                'success' => false,
                'message' => 'some data missed'
            ], 400);
        }

        $fullname = trim(preg_replace('/\s+/', ' ', $validatedData['full_name']));
        $parts = explode(' ', $fullname);

        if (count($parts) != 3) {
            return response()->json([
                'success' => false,
                'message' => 'full name must contain first, middle and last name'
            ], 400);
        }

        [$first, $mid, $last] = $parts;

        $user = User::where('phone', $validatedData['phone_number'])->first();
    
        if ($user) {
            return response()->json([
                    'success' => false,
                    'message' => 'there are already user with that phone number'
            ], 400);
        }

        try {
            DB::transaction(function () use ($validatedData, $parts, $first, $mid, $last, $user) {
                $user = User::create([
                    'first_name' => $first,
                    'mid_name' => $mid,
                    'last_name' => $last,
                    'phone' => $validatedData['phone_number'],
                    'email' => $validatedData['email'],
                    'username' => $validatedData['username'],
                    'password' => $validatedData['password'],
                    'gender' => $validatedData['gender'],
                    'birth_date' => $validatedData['date_of_birth'],
                    'profile_photo_url' => $validatedData['profile_image'],
                ]);

                $secretary = Secretary::create([
                    'user_id' => $user->id,
                    'entity_id' => $validatedData['entity_id'],
                    'entity_type' => $validatedData['entity_type']
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'the secretary user has been created succefully'
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