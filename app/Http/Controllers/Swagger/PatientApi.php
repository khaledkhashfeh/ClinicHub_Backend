<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class PatientApi
{
    #[OA\Post(
        path: "/api/auth/login",
        summary: "Patient login",
        description: "Authenticates a patient and returns a JWT token",
        tags: ["Patient Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "Phone number"),
                        new OA\Property(property: "password", type: "string", example: "password123", format: "password", description: "Password"),
                    ],
                    required: ["phone", "password"]
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
                        new OA\Property(property: "role", type: "string", example: "Patient"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "patient_id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Ahmad Ali"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
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
                description: "Account pending approval"
            ),
            new OA\Response(
                response: 404,
                description: "Account not found or registration incomplete"
            )
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: "/api/auth/send-otp",
        summary: "Send OTP to patient phone",
        description: "Sends an OTP code to the patient's phone number for verification",
        tags: ["Patient Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "Phone number to send OTP to"),
                    ],
                    required: ["phone"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إرسال رمز التحقق بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data"
            ),
            new OA\Response(
                response: 429,
                description: "Too many attempts"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to send OTP"
            )
        ]
    )]
    public function sendOtp()
    {
    }

    #[OA\Post(
        path: "/api/auth/verify-otp",
        summary: "Verify patient phone with OTP",
        description: "Verifies a patient's phone number using the OTP code received",
        tags: ["Patient Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "Phone number"),
                        new OA\Property(property: "otp", type: "string", example: "123456", description: "6-digit OTP code"),
                    ],
                    required: ["phone", "otp"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP verified successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "is_registered", type: "boolean", example: false, description: "Whether the patient is already registered"),
                        new OA\Property(property: "message", type: "string", example: "تم التحقق بنجاح. يرجى إكمال بيانات التسجيل."),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", description: "JWT token if already registered"),
                        new OA\Property(property: "patient_id", type: "integer", example: 1, description: "Patient ID if already registered"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data or OTP"
            )
        ]
    )]
    public function verifyOtp()
    {
    }

    #[OA\Post(
        path: "/api/auth/resend-otp",
        summary: "Resend OTP to patient phone",
        description: "Resends an OTP code to the patient's phone number",
        tags: ["Patient Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "Phone number to resend OTP to"),
                    ],
                    required: ["phone"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP resent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إرسال رمز التحقق الجديد بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data"
            ),
            new OA\Response(
                response: 404,
                description: "Phone number not found"
            ),
            new OA\Response(
                response: 429,
                description: "Too many attempts"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to resend OTP"
            )
        ]
    )]
    public function resendOtp()
    {
    }

    #[OA\Post(
        path: "/api/auth/register",
        summary: "Register a new patient",
        description: "Registers a new patient account after OTP verification",
        tags: ["Patient Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "full_name", type: "string", example: "Ahmad Ali", description: "Full name"),
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "Phone number (must be verified with OTP)"),
                        new OA\Property(property: "governorate", type: "integer", example: 1, description: "Governorate ID"),
                        new OA\Property(property: "district", type: "integer", example: 1, description: "District ID"),
                        new OA\Property(property: "gender", type: "string", enum: ["male", "female"], example: "male", description: "Gender"),
                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1990-01-01", description: "Date of birth"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Password"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "Password confirmation"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "patient@example.com", description: "Email (optional)"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Profile image (optional)"),
                        new OA\Property(property: "occupation", type: "string", example: "Engineer", description: "Occupation (optional)"),
                        new OA\Property(property: "blood_type", type: "string", enum: ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], example: "A+", description: "Blood type (optional)"),
                        new OA\Property(property: "has_chronic_diseases", type: "boolean", example: false, description: "Whether patient has chronic diseases (optional)"),
                        new OA\Property(property: "chronic_diseases_details", type: "string", example: "Diabetes", description: "Details of chronic diseases (required if has_chronic_diseases is true)"),
                    ],
                    required: ["full_name", "phone", "governorate", "district", "gender", "date_of_birth", "password", "password_confirmation"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Registration successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إنشاء الحساب بنجاح وتسجيل الدخول."),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "role", type: "string", example: "Patient"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "patient_id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Ahmad Ali"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data or phone not verified"
            ),
            new OA\Response(
                response: 409,
                description: "Account already exists"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "البيانات غير صحيحة."),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            )
        ]
    )]
    public function register()
    {
    }

    #[OA\Put(
        path: "/api/patients/{id}",
        summary: "Update patient profile",
        description: "Updates the profile information of the authenticated patient",
        tags: ["Patient Profile"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Patient ID",
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
                        new OA\Property(property: "full_name", type: "string", example: "Ahmad Ali", description: "Full name (optional)"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "patient@example.com", description: "Email (optional)"),
                        new OA\Property(property: "governorate", type: "integer", example: 1, description: "Governorate ID (optional)"),
                        new OA\Property(property: "district", type: "integer", example: 1, description: "District ID (optional)"),
                        new OA\Property(property: "gender", type: "string", enum: ["male", "female"], example: "male", description: "Gender (optional)"),
                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1990-01-01", description: "Date of birth (optional)"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Profile image (optional)"),
                        new OA\Property(property: "occupation", type: "string", example: "Engineer", description: "Occupation (optional)"),
                        new OA\Property(property: "blood_type", type: "string", enum: ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], example: "A+", description: "Blood type (optional)"),
                        new OA\Property(property: "has_chronic_diseases", type: "boolean", example: false, description: "Whether patient has chronic diseases (optional)"),
                        new OA\Property(property: "chronic_diseases_details", type: "string", example: "Diabetes", description: "Details of chronic diseases (optional)"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profile updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث بيانات المريض بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Ahmad Ali"),
                                new OA\Property(property: "first_name", type: "string", example: "Ahmad"),
                                new OA\Property(property: "last_name", type: "string", example: "Ali"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                new OA\Property(property: "email", type: "string", example: "patient@example.com"),
                                new OA\Property(property: "gender", type: "string", example: "male"),
                                new OA\Property(property: "date_of_birth", type: "string", example: "1990-01-01"),
                                new OA\Property(property: "profile_photo_url", type: "string", example: "http://localhost:8000/storage/profiles/patients/image.jpg"),
                                new OA\Property(property: "governorate_id", type: "integer", example: 1),
                                new OA\Property(property: "city_id", type: "integer", example: 1),
                                new OA\Property(property: "occupation", type: "string", example: "Engineer"),
                                new OA\Property(property: "blood_type", type: "string", example: "A+"),
                                new OA\Property(property: "has_chronic_diseases", type: "boolean", example: false),
                                new OA\Property(property: "chronic_diseases_details", type: "string", example: "Diabetes"),
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
                response: 403,
                description: "Not authorized to update this profile"
            ),
            new OA\Response(
                response: 404,
                description: "Patient not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "البيانات غير صحيحة."),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error during update"
            )
        ]
    )]
    public function update()
    {
    }
}
