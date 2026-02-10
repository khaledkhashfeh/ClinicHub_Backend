<?php

namespace App\Services;

use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;    
    
    public function __construct() {
        $this->messaging = (new Factory)->withServiceAccount(config('firebase.credentials'))->createMessaging();
    }    
    
    /**
     * Send notification to a single FCM token
     * 
     * @param string $token FCM token
     * @param string $title Notification title
     * @param string $body Notification body
     * @return array ['success' => bool, 'message' => string, 'should_delete_token' => bool]
     */
    public function sendNotification($token, $title, $body) {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body));
            
            $this->messaging->send($message);
            
            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'should_delete_token' => false
            ];
            
        } catch (NotFound $e) {
            // Token is not found/invalid - should be deleted
            Log::warning('FCM token not found', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'FCM token is invalid or has been unregistered',
                'should_delete_token' => true
            ];
            
        } catch (InvalidArgument $e) {
            // Invalid token format
            Log::error('Invalid FCM token format', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Invalid FCM token format',
                'should_delete_token' => true
            ];
            
        } catch (\Exception $e) {
            // Other Firebase errors
            Log::error('Firebase notification error', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'should_delete_token' => false
            ];
        }
    }
}