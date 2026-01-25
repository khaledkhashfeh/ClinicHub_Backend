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

    #[OA\Get(
        path: "/api/admin/doctors/pending",
        summary: "Get pending doctors",
        description: "Returns a list of doctors awaiting approval",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of pending doctors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                    new OA\Property(property: "username", type: "string", example: "johndoe"),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(
                                        property: "user",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "first_name", type: "string", example: "John"),
                                            new OA\Property(property: "last_name", type: "string", example: "Doe"),
                                            new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                            new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "specializations",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "name", type: "string", example: "Cardiology"),
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: "governorate",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Baghdad"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "city",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Al-Karkh"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "district",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Rasheed"),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            )
        ]
    )]
    public function getPendingDoctors()
    {
    }

    #[OA\Put(
        path: "/api/admin/doctors/{id}/approve",
        summary: "Approve a doctor",
        description: "Approves a doctor registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Doctor ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Doctor approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم قبول تسجيل الدكتور بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor not found"
            ),
            new OA\Response(
                response: 400,
                description: "Doctor already approved"
            )
        ]
    )]
    public function approveDoctor()
    {
    }

    #[OA\Put(
        path: "/api/admin/doctors/{id}/reject",
        summary: "Reject a doctor",
        description: "Rejects a doctor registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Doctor ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Doctor rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم رفض تسجيل الدكتور بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "status", type: "string", example: "rejected"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor not found"
            ),
            new OA\Response(
                response: 400,
                description: "Doctor already rejected"
            )
        ]
    )]
    public function rejectDoctor()
    {
    }

    #[OA\Get(
        path: "/api/admin/clinics/pending",
        summary: "Get pending clinics",
        description: "Returns a list of clinics awaiting approval",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of pending clinics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                    new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(
                                        property: "specialization",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Cardiology"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "governorate",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Baghdad"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "city",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Al-Karkh"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "district",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Rasheed"),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            )
        ]
    )]
    public function getPendingClinics()
    {
    }

    #[OA\Put(
        path: "/api/admin/clinics/{id}/approve",
        summary: "Approve a clinic",
        description: "Approves a clinic registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Clinic approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم قبول تسجيل العيادة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Clinic not found"
            ),
            new OA\Response(
                response: 400,
                description: "Clinic already approved"
            )
        ]
    )]
    public function approveClinic()
    {
    }

    #[OA\Put(
        path: "/api/admin/clinics/{id}/reject",
        summary: "Reject a clinic",
        description: "Rejects a clinic registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Clinic rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم رفض تسجيل العيادة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                new OA\Property(property: "status", type: "string", example: "rejected"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Clinic not found"
            ),
            new OA\Response(
                response: 400,
                description: "Clinic already rejected"
            )
        ]
    )]
    public function rejectClinic()
    {
    }

    #[OA\Get(
        path: "/api/admin/secretaries/pending",
        summary: "Get pending secretaries",
        description: "Returns a list of secretaries awaiting approval",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of pending secretaries",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "user_id", type: "integer", example: 1),
                                    new OA\Property(property: "entity_type", type: "string", example: "clinic"),
                                    new OA\Property(property: "entity_id", type: "integer", example: 1),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(
                                        property: "user",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "first_name", type: "string", example: "Ahmad"),
                                            new OA\Property(property: "last_name", type: "string", example: "Al-Hassan"),
                                            new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                            new OA\Property(property: "email", type: "string", example: "ahmad@example.com"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "entity",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            )
        ]
    )]
    public function getPendingSecretaries()
    {
    }

    #[OA\Put(
        path: "/api/admin/secretaries/{id}/approve",
        summary: "Approve a secretary",
        description: "Approves a secretary registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Secretary ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Secretary approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم قبول تسجيل السكرتيرة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "username", type: "string", example: "ahmad_secretary"),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                                new OA\Property(property: "entity_type", type: "string", example: "clinic"),
                                new OA\Property(property: "entity_name", type: "string", example: "Al-Rashid Clinic"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Secretary not found"
            ),
            new OA\Response(
                response: 400,
                description: "Secretary already approved"
            )
        ]
    )]
    public function approveSecretary()
    {
    }

    #[OA\Put(
        path: "/api/admin/secretaries/{id}/reject",
        summary: "Reject a secretary",
        description: "Rejects a secretary registration request",
        tags: ["Admin Management"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Secretary ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Secretary rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم رفض تسجيل السكرتيرة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "username", type: "string", example: "ahmad_secretary"),
                                new OA\Property(property: "status", type: "string", example: "rejected"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - not an admin"
            ),
            new OA\Response(
                response: 404,
                description: "Secretary not found"
            ),
            new OA\Response(
                response: 400,
                description: "Secretary already rejected"
            )
        ]
    )]
    public function rejectSecretary()
    {
    }
}