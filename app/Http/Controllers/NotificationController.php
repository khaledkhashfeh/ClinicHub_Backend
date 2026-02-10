<?php

namespace App\Http\Controllers;

use App\Models\UserFcmToken;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $firebaseService;
    
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);  

        $fcmTokenRecord = UserFcmToken::where('user_id', $request->user_id)->first();
        
        if (!$fcmTokenRecord || !$fcmTokenRecord->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'FCM Token not found for this user'
            ], 404);
        }

        $result = $this->firebaseService->sendNotification(
            $fcmTokenRecord->fcm_token, 
            $request->title, 
            $request->body
        );
        
        // If token is invalid, delete it from database
        if ($result['should_delete_token']) {
            DB::transaction(function () use ($fcmTokenRecord) {
                $fcmTokenRecord->delete();
            });
        }
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'token_deleted' => $result['should_delete_token']
            ], 400);
        }
    }
}
