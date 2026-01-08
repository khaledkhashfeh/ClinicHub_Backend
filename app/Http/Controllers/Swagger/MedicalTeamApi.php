<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class MedicalTeamApi
{
    #[OA\Post(
        path: "/api/medical-team/login",
        summary: "Medical Team login",
        description: "Authenticates a doctor or secretary and returns a JWT token",
        tags: ["Medical Team Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "identifier", type: "string", example: "doctor@example.com", description: "Email, username, or phone number"),
                        new OA\Property(property: "password", type: "string", example: "password123", format: "password", minLength: 8, maxLength: 18),
                    ],
                    required: ["identifier", "password"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "login successful"),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "token_type", type: "string", example: "bearer"),
                        new OA\Property(property: "expires_in", type: "integer", example: 3600),
                        new OA\Property(property: "user_type", type: "string", example: "doctor", enum: ["doctor", "secretary"]),
                        new OA\Property(
                            property: "user",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "username", type: "string", example: "johndoe"),
                                new OA\Property(property: "status", type: "string", example: "approved", description: "For secretary only"),
                                new OA\Property(property: "is_approved", type: "boolean", example: true, description: "For doctor only"),
                                new OA\Property(property: "entity_type", type: "string", example: "clinic", description: "For secretary only"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid credentials or user not associated with doctor/secretary"
            ),
            new OA\Response(
                response: 403,
                description: "Doctor account not approved or phone not verified"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: "/api/medical-team/logout",
        summary: "Medical Team logout",
        description: "Logs out the authenticated medical team member (doctor or secretary)",
        tags: ["Medical Team Authentication"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "logged out successfully"),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to logout"
            )
        ]
    )]
    public function logout()
    {
    }
}
