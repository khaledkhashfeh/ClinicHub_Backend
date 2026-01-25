<?php

namespace App\Http\Controllers;

use App\Http\Requests\Secretary\SecretaryCreateRequest;
use App\Http\Requests\Secretary\SecretaryEditRequest;
use App\Models\Secretary;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SecretaryController extends Controller
{
    // this function for creating secretary account
    public function createSecretary(SecretaryCreateRequest $request, UserService $userService) {
        $validatedData = $request->validate();

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

    //this function for editing secretary account
    public function editSecretary(SecretaryEditRequest $request, UserService $service) {
        $validatedData = $request->validated();

        try {
            $result = DB::transaction(function () use ($validatedData, $service) {
                $user = $service->UpdateUser($validatedData);

                $secretary = $user->secretary()->firstOrFail();

                $secretary->update([
                    'entity_id' => $validatedData['entity_id'] ?? $secretary->entity_id,
                    'entity_type' => $validatedData['entity_type'] ?? $secretary->entity_type
                ]);

                return compact('user', 'secretary');
            });

            return response()->json([
                'success' => true,
                'message' => 'secretary account has been updated successfully',
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

    // Get secretary profile
    public function getProfile(Request $request) {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $secretary = $user->secretary;

            if (!$secretary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Secretary not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $secretary->id,
                    'user_id' => $secretary->user_id,
                    'entity_id' => $secretary->entity_id,
                    'entity_type' => $secretary->entity_type,
                    'status' => $secretary->status,
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'username' => $user->username,
                        'date_of_birth' => $user->birth_date,
                        'gender' => $user->gender,
                        'profile_photo_url' => $user->profile_photo_url,
                    ]
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Update secretary profile
    public function updateProfile(Request $request) {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $secretary = $user->secretary;

            if (!$secretary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Secretary not found'
                ], 404);
            }

            $validatedData = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
                'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|in:male,female',
                'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'entity_id' => 'sometimes|integer',
                'entity_type' => 'sometimes|in:clinic,medical_center'
            ]);

            // Update user data if provided
            if (isset($validatedData['first_name'])) {
                $user->first_name = $validatedData['first_name'];
            }

            if (isset($validatedData['last_name'])) {
                $user->last_name = $validatedData['last_name'];
            }

            if (isset($validatedData['phone'])) {
                $user->phone = $validatedData['phone'];
            }

            if (isset($validatedData['email'])) {
                $user->email = $validatedData['email'];
            }

            if (isset($validatedData['username'])) {
                $user->username = $validatedData['username'];
            }

            if (isset($validatedData['date_of_birth'])) {
                $user->birth_date = $validatedData['date_of_birth'];
            }

            if (isset($validatedData['gender'])) {
                $user->gender = $validatedData['gender'];
            }

            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profiles/secretaries', 'public');
                $user->profile_photo_url = $imagePath;
            }

            $user->save();

            // Update secretary data if provided
            if (isset($validatedData['entity_id'])) {
                $secretary->entity_id = $validatedData['entity_id'];
            }

            if (isset($validatedData['entity_type'])) {
                $secretary->entity_type = $validatedData['entity_type'];
            }

            $secretary->save();

            return response()->json([
                'success' => true,
                'message' => 'Secretary profile updated successfully',
                'data' => [
                    'id' => $secretary->id,
                    'user_id' => $secretary->user_id,
                    'entity_id' => $secretary->entity_id,
                    'entity_type' => $secretary->entity_type,
                    'status' => $secretary->status,
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'username' => $user->username,
                        'date_of_birth' => $user->birth_date,
                        'gender' => $user->gender,
                        'profile_photo_url' => $user->profile_photo_url,
                    ]
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Error updating profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}