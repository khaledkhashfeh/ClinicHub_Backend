<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class AdminApi
{
    #[OA\Post(
        path: "/api/admin/login",
        summary: "Admin login",
        description: "Authenticates an admin user and returns a JWT token",
        tags: ["Admin Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "identifier", type: "string", example: "admin@example.com", description: "Email or phone number"),
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
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الدخول بنجاح"),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "token_type", type: "string", example: "bearer"),
                        new OA\Property(property: "expires_in", type: "integer", example: 3600),
                        new OA\Property(
                            property: "user",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "first_name", type: "string", example: "Admin"),
                                new OA\Property(property: "last_name", type: "string", example: "User"),
                                new OA\Property(property: "email", type: "string", example: "admin@example.com"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                new OA\Property(property: "role", type: "string", example: "admin"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data or credentials"
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials"
            ),
            new OA\Response(
                response: 403,
                description: "Access denied - not an admin"
            )
        ]
    )]
    public function login()
    {
    }
}