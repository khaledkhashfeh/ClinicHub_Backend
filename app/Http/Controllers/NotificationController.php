<?php

namespace App\Http\Controllers;

use App\Models\UserFcmToken;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $firebaseService;    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);        $token = UserFcmToken::where('user_id', $request->user_id)->value('fcm_token');        if (!$token) {
            return response()->json(['message' => 'FCM Token not found'], 404);
        }        $this->firebaseService->sendNotification($token, $request->title, $request->body);
        
        return response()->json(['message' => 'Notification sent successfully']);
    }
}
