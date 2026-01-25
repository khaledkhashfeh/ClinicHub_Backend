<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class NotificationApi
{
    #[OA\Post(
        path: "/api/notification/send-notification",
        summary: "Send notification to user",
        description: "Sends a push notification to a specific user",
        tags: ["Notifications"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "user_id", type: "integer", example: 1, description: "ID of the user to send notification to"),
                        new OA\Property(property: "title", type: "string", example: "Notification Title", description: "Title of the notification"),
                        new OA\Property(property: "body", type: "string", example: "Notification body content", description: "Body content of the notification"),
                    ],
                    required: ["user_id", "title", "body"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Notification sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Notification sent successfully"),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not authorized to send notifications"
            ),
            new OA\Response(
                response: 404,
                description: "User FCM token not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function sendNotification()
    {
    }
}