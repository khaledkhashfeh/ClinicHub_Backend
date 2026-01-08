<?php

namespace App\Http\Controllers;

use App\Http\Requests\SecretaryCreateRequest;
use App\Http\Requests\SecretaryEditRequest;
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
    public function createSecretary(SecretaryCreateRequest $request, UserService $userService) {
        $validatedData = $request->validated();

        try {
            // ✅ التحقق من أن الخطة تدعم السكرتيرة
            $entityType = $validatedData['entity_type'];
            $entityId = $validatedData['entity_id'];
            
            $entity = $entityType::find($entityId);
            
            if (!$entity) {
                return response()->json([
                    'success' => false,
                    'message' => 'العيادة أو المركز غير موجود'
                ], 404);
            }

            // جلب الاشتراك الفعّال
            $subscription = $entity->subscriptions()
                ->where('status', 'active')
                ->with('plan')
                ->latest()
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد اشتراك نشط. يرجى الترقية للخطة المناسبة'
                ], 403);
            }

            // التحقق من صلاحية إضافة سكرتيرة
            $enableSecretary = $subscription->plan->getEntitlement('enable_secretary', false);
            
            if (!$enableSecretary) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطتك الحالية لا تدعم إضافة سكرتيرة. يرجى الترقية'
                ], 403);
            }

            $result = DB::transaction(function () use ($validatedData, $userService) {
                $user = $userService->SignUp($validatedData);

                $secretary = Secretary::create([
                    'user_id' => $user->id,
                    'username' => $validatedData['username'],
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

                $secretary = $secretary = $user->secretary()->firstOrFail();

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
}