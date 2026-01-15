<?php

namespace App\Services;

use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Send push notification to doctor
     */
    public function sendToDoctor($doctorId, $title, $body)
    {
        return $this->sendNotification($doctorId, 'doctor', $title, $body);
    }

    /**
     * Send push notification to clinic
     */
    public function sendToClinic($clinicId, $title, $body)
    {
        return $this->sendNotification($clinicId, 'clinic', $title, $body);
    }

    /**
     * Generic method to send push notification
     */
    private function sendNotification($entityId, $entityType, $title, $body)
    {
        try {
            // Get FCM tokens for the entity
            $fcmTokens = $this->getFcmTokens($entityId, $entityType);

            if (empty($fcmTokens)) {
                Log::info("No FCM tokens found for {$entityType} ID: {$entityId}");
                return false;
            }

            // Prepare the notification payload
            $payload = [
                'registration_ids' => $fcmTokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default'
                ],
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ],
                'priority' => 'high'
            ];

            // Send the notification to Firebase
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info("Push notification sent successfully to {$entityType} ID: {$entityId}", [
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error("Failed to send push notification to {$entityType} ID: {$entityId}", [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception occurred while sending push notification to {$entityType} ID: {$entityId}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get FCM tokens for the specified entity
     */
    private function getFcmTokens($entityId, $entityType)
    {
        $query = UserFcmToken::whereHas('user', function ($q) use ($entityId, $entityType) {
            if ($entityType === 'doctor') {
                $q->whereHas('doctor', function ($doctorQuery) use ($entityId) {
                    $doctorQuery->where('id', $entityId);
                });
            } elseif ($entityType === 'clinic') {
                $q->whereHas('clinic', function ($clinicQuery) use ($entityId) {
                    $clinicQuery->where('id', $entityId);
                });
            }
        });

        return $query->pluck('fcm_token')->toArray();
    }
}