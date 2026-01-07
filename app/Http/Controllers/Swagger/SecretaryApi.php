<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class SecretaryApi
{
    #[OA\Post(
        path: "/api/secretaries",
        summary: "Create a new secretary account",
        description: "Creates a new secretary account and associates it with a clinic or medical center",
        tags: ["Secretary Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "first_name", type: "string", example: "Ahmad", description: "First name of the secretary"),
                        new OA\Property(property: "last_name", type: "string", example: "Al-Hassan", description: "Last name of the secretary"),
                        new OA\Property(property: "phone_number", type: "string", example: "0912345678", description: "Phone number in Syrian format (09xxxxxxxx or +963xxxxxxxxx)"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "ahmad.secretary@example.com", description: "Email address (optional)"),
                        new OA\Property(property: "username", type: "string", example: "ahmad_secretary", description: "Unique username for the secretary"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password (minimum 8 characters)"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "Password confirmation"),
                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1990-01-01", description: "Date of birth in YYYY-MM-DD format"),
                        new OA\Property(property: "profile_image", type: "string", example: "path/to/image.jpg", description: "Profile image URL (optional)"),
                        new OA\Property(property: "gender", type: "string", enum: ["male", "female"], example: "male", description: "Gender of the secretary"),
                        new OA\Property(property: "entity_id", type: "integer", example: 1, description: "ID of the clinic or medical center to associate with"),
                        new OA\Property(property: "entity_type", type: "string", enum: ["clinic", "medical_center"], example: "clinic", description: "Type of entity (clinic or medical_center)"),
                    ],
                    required: ["first_name", "last_name", "phone_number", "username", "password", "password_confirmation", "date_of_birth", "gender", "entity_id", "entity_type"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Secretary account created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "secretary account has been created successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "first_name", type: "string", example: "Ahmad"),
                                        new OA\Property(property: "last_name", type: "string", example: "Al-Hassan"),
                                        new OA\Property(property: "phone", type: "string", example: "0912345678"),
                                        new OA\Property(property: "email", type: "string", format: "email", example: "ahmad.secretary@example.com"),
                                        new OA\Property(property: "username", type: "string", example: "ahmad_secretary"),
                                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1990-01-01"),
                                        new OA\Property(property: "gender", type: "string", example: "male"),
                                        new OA\Property(property: "profile_photo_url", type: "string", example: "path/to/image.jpg"),
                                    ]
                                ),
                                new OA\Property(
                                    property: "secretary",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "user_id", type: "integer", example: 1),
                                        new OA\Property(property: "entity_type", type: "string", example: "clinic"),
                                        new OA\Property(property: "entity_id", type: "integer", example: 1),
                                        new OA\Property(property: "status", type: "string", example: "active"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "field_name",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The field is required.")
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "An error occurred while processing your request.")
                    ]
                )
            )
        ]
    )]
    public function createSecretary()
    {
    }

    #[OA\Post(
        path: "/api/secretaries/login",
        summary: "Secretary login",
        description: "Authenticates a secretary and returns a JWT token",
        tags: ["Secretary Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "identifier", type: "string", example: "ahmad.secretary@example.com", description: "Email, phone number, or username"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password"),
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
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(
                            property: "user",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "first_name", type: "string", example: "Ahmad"),
                                new OA\Property(property: "last_name", type: "string", example: "Al-Hassan"),
                                new OA\Property(property: "phone", type: "string", example: "0912345678"),
                                new OA\Property(property: "email", type: "string", format: "email", example: "ahmad.secretary@example.com"),
                                new OA\Property(property: "username", type: "string", example: "ahmad_secretary"),
                                new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1990-01-01"),
                                new OA\Property(property: "gender", type: "string", example: "male"),
                                new OA\Property(property: "profile_photo_url", type: "string", example: "path/to/image.jpg"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Invalid credentials")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "field_name",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The field is required.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function login()
    {
    }
}
