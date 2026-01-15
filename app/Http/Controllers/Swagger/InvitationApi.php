<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class InvitationApi
{
    #[OA\Get(
        path: "/api/available-doctors",
        summary: "Search & List Available doctors",
        description: "This endpoint is dedicated to exploring available medical professionals for contracting. If you send a query, it will search in (name, email, or specialization). If you don't send a query, it will display a list of all available doctors with pagination.",
        tags: ["Doctor Invitations"],
        parameters: [
            new OA\Parameter(
                name: "query",
                description: "Search query to filter doctors by name, email, or specialization",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of available doctors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "total_count", type: "integer", example: 120),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 101),
                                    new OA\Property(property: "full_name", type: "string", example: "د. أحمد محمد"),
                                    new OA\Property(property: "email", type: "string", format: "email", example: "dr.ahmed@example.com"),
                                    new OA\Property(property: "phone", type: "string", example: "+966500000000"),
                                    new OA\Property(property: "specialization", type: "string", example: "طب وجراحة العظام"),
                                    new OA\Property(property: "profile_image", type: "string", example: "https://cdn.example.com/images/dr101.jpg"),
                                    new OA\Property(
                                        property: "governorate",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "دمشق")
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "city",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 5),
                                            new OA\Property(property: "name", type: "string", example: "برامكة")
                                        ]
                                    ),
                                    new OA\Property(property: "years_of_experience", type: "integer", example: 10)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "links",
                            properties: [
                                new OA\Property(property: "first", type: "string", example: "http://example.com/api/available-doctors?page=1"),
                                new OA\Property(property: "last", type: "string", example: "http://example.com/api/available-doctors?page=10"),
                                new OA\Property(property: "prev", type: "string", example: null),
                                new OA\Property(property: "next", type: "string", example: "http://example.com/api/available-doctors?page=2")
                            ]
                        ),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "from", type: "integer", example: 1),
                                new OA\Property(property: "last_page", type: "integer", example: 10),
                                new OA\Property(property: "path", type: "string", example: "http://example.com/api/available-doctors"),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "to", type: "integer", example: 15),
                                new OA\Property(property: "total", type: "integer", example: 150)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function availableDoctors()
    {
    }

    #[OA\Post(
        path: "/api/clinics/invitations/send",
        summary: "Send Job Invitation",
        description: "When the clinic finds the right doctor, they send a formal invitation that arrives as an instant notification on the doctor's phone.",
        tags: ["Doctor Invitations"],
        security: [["jwt" => ["clinic"]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "doctor_id", type: "integer", example: 101),
                        new OA\Property(property: "clinic_id", type: "integer", example: 45),
                        new OA\Property(property: "message", type: "string", example: "دعوة للانضمام إلى الطاقم الطبي في المركز الحديث")
                    ],
                    required: ["doctor_id", "clinic_id"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Invitation sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إرسال الدعوة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "invitation_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "pending")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor or clinic not found"
            ),
            new OA\Response(
                response: 400,
                description: "Duplicate invitation"
            )
        ]
    )]
    public function sendInvitation()
    {
    }

    #[OA\Patch(
        path: "/api/doctor/invitations/{invitation_id}",
        summary: "Respond to Job Invitation",
        description: "Here the doctor makes a decision, and the system automatically completes the procedures based on their response.",
        tags: ["Doctor Invitations"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "invitation_id",
                description: "ID of the invitation",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "status", type: "string", enum: ["accepted", "rejected"], example: "accepted")
                    ],
                    required: ["status"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Invitation response processed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث حالة الدعوة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "invitation_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "accepted")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 403,
                description: "Not authorized to respond to this invitation"
            ),
            new OA\Response(
                response: 404,
                description: "Invitation not found"
            ),
            new OA\Response(
                response: 400,
                description: "Invitation already responded to"
            )
        ]
    )]
    public function respondToInvitation()
    {
    }
}