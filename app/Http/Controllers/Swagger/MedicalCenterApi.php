<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class MedicalCenterApi
{
    #[OA\Post(
        path: "/api/center/login",
        summary: "Medical Center login",
        description: "Authenticates a medical center and returns a JWT token",
        tags: ["Medical Center Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "username", type: "string", example: "medical_center", description: "Username of the medical center"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password"),
                    ],
                    required: ["username", "password"]
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
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الدخول بنجاح."),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "role", type: "string", example: "Medical_Center"),
                        new OA\Property(
                            property: "center",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "center_name", type: "string", example: "Al-Rashid Medical Center"),
                                new OA\Property(property: "is_approved", type: "boolean", example: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data"
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials"
            ),
            new OA\Response(
                response: 403,
                description: "Account not approved"
            )
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: "/api/center/register-request",
        summary: "Medical Center registration request",
        description: "Submits a request to register a new medical center account",
        tags: ["Medical Center Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "center_name", type: "string", example: "Medical Center Name", description: "Name of the medical center"),
                        new OA\Property(property: "phone", type: "string", example: "0912345678", description: "Phone number"),
                        new OA\Property(property: "governorate_id", type: "integer", example: 1, description: "Governorate ID"),
                        new OA\Property(property: "district_id", type: "integer", example: 1, description: "District ID (required if city_id not provided)"),
                        new OA\Property(property: "city_id", type: "integer", example: 1, description: "City ID (required if district_id not provided)"),
                        new OA\Property(property: "clinic_count", type: "integer", example: 5, description: "Number of clinics in the center"),
                        new OA\Property(property: "username", type: "string", example: "medical_center_username", description: "Username for the medical center"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "Password confirmation"),
                        new OA\Property(property: "detailed_address", type: "string", example: "Detailed address", description: "Detailed address (optional)"),
                        new OA\Property(property: "latitude", type: "number", example: 33.5138, description: "Latitude (optional)"),
                        new OA\Property(property: "longitude", type: "number", example: 36.2765, description: "Longitude (optional)"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Logo image file (optional)"),
                        new OA\Property(property: "gallery_images.*", type: "string", format: "binary", description: "Gallery images (optional)"),
                        new OA\Property(property: "working_hours", type: "string", example: "{\"saturday\":{\"open\":\"09:00\",\"close\":\"18:00\"}}", description: "Working hours in JSON format"),
                        new OA\Property(
                            property: "services.*.name",
                            type: "string",
                            example: "General Consultation",
                            description: "Service name - use services[0].name, services[0].price, services[1].name, services[1].price, etc."
                        ),
                        new OA\Property(
                            property: "services.*.price",
                            type: "number",
                            example: 25000,
                            description: "Service price"
                        ),
                        new OA\Property(property: "facebook_link", type: "string", format: "uri", example: "https://facebook.com/medicalcenter", description: "Facebook link (optional)"),
                        new OA\Property(property: "instagram_link", type: "string", format: "uri", example: "https://instagram.com/medicalcenter", description: "Instagram link (optional)"),
                        new OA\Property(property: "website_link", type: "string", format: "uri", example: "https://medicalcenter.com", description: "Website link (optional)"),
                        new OA\Property(property: "plan_id", type: "integer", example: 1, description: "Subscription plan ID (optional)"),
                    ],
                    required: ["center_name", "phone", "governorate_id", "clinic_count", "username", "password", "password_confirmation", "working_hours"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: "Registration request submitted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم استلام طلب تسجيل المركز الطبي بنجاح. سيتم مراجعة البيانات والرد عليك عبر الواتساب."),
                        new OA\Property(property: "plan_id", type: "integer", example: 1, description: "Subscription plan ID if provided"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function registerRequest()
    {
    }

    #[OA\Put(
        path: "/api/centers/{id}",
        summary: "Update medical center profile",
        description: "Updates the profile information of the authenticated medical center",
        tags: ["Medical Center Profile"],
        security: [["jwt" => ["medical_center"]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Medical Center ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "center_name", type: "string", example: "Updated Medical Center Name", description: "Name of the medical center (optional)"),
                        new OA\Property(property: "username", type: "string", example: "updated_username", description: "Username for the medical center (optional)"),
                        new OA\Property(property: "clinic_count", type: "integer", example: 10, description: "Number of clinics in the center (optional)"),
                        new OA\Property(property: "latitude", type: "number", example: 33.5138, description: "Latitude (optional)"),
                        new OA\Property(property: "longitude", type: "number", example: 36.2765, description: "Longitude (optional)"),
                        new OA\Property(property: "detailed_address", type: "string", example: "Updated detailed address", description: "Detailed address (optional)"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Logo image file (optional)"),
                        new OA\Property(property: "gallery_images.*", type: "string", format: "binary", description: "Gallery images (optional)"),
                        new OA\Property(property: "working_hours", type: "string", example: "{\"saturday\":{\"open\":\"09:00\",\"close\":\"18:00\"}}", description: "Working hours in JSON format (optional)"),
                        new OA\Property(
                            property: "services.*.name",
                            type: "string",
                            example: "Updated Service",
                            description: "Service name - use services[0].name, services[0].price, services[1].name, services[1].price, etc. (optional)"
                        ),
                        new OA\Property(
                            property: "services.*.price",
                            type: "number",
                            example: 30000,
                            description: "Service price (optional)"
                        ),
                        new OA\Property(property: "facebook_link", type: "string", format: "uri", example: "https://facebook.com/updatedmedicalcenter", description: "Facebook link (optional)"),
                        new OA\Property(property: "instagram_link", type: "string", format: "uri", example: "https://instagram.com/updatedmedicalcenter", description: "Instagram link (optional)"),
                        new OA\Property(property: "website_link", type: "string", format: "uri", example: "https://updatedmedicalcenter.com", description: "Website link (optional)"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Medical center profile updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث بيانات المركز الطبي بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "center_name", type: "string", example: "Updated Medical Center Name"),
                                new OA\Property(property: "username", type: "string", example: "updated_username"),
                                new OA\Property(property: "clinic_count", type: "integer", example: 10),
                                new OA\Property(property: "latitude", type: "number", example: 33.5138),
                                new OA\Property(property: "longitude", type: "number", example: 36.2765),
                                new OA\Property(property: "phone", type: "string", example: "0912345678"),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Not authorized to update this profile"
            ),
            new OA\Response(
                response: 404,
                description: "Medical center not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function update()
    {
    }

    #[OA\Delete(
        path: "/api/centers/{id}",
        summary: "Delete medical center profile",
        description: "Deletes the profile of the authenticated medical center",
        tags: ["Medical Center Profile"],
        security: [["jwt" => ["medical_center"]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Medical Center ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Medical center profile deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم حذف المركز الطبي بنجاح من النظام."),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Not authorized to delete this profile"
            ),
            new OA\Response(
                response: 404,
                description: "Medical center not found"
            )
        ]
    )]
    public function destroy()
    {
    }
}