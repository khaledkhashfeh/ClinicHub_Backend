<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;    public function __construct()
    {
        $this->messaging = (new Factory)->withServiceAccount(config('firebase.credentials'))->createMessaging();
    }    public function sendNotification($token, $title, $body)
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body));
        
        return $this->messaging->send($message);
    }
}