<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class InvitationApi
{
    #[OA\Get(
        path: "/api/available-doctors",
        summary: "Search available doctors",
        description: "Searches and lists available doctors based on criteria",
        tags: ["Invitations"],
        parameters: [
            new OA\Parameter(
                name: "query",
                description: "Search query to filter doctors by name, email, or specialization",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "cardiology")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of available doctors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "total_count", type: "integer", example: 10),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                    new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                    new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                    new OA\Property(property: "specialization", type: "string", example: "Cardiology"),
                                    new OA\Property(property: "profile_image", type: "string", example: "path/to/image.jpg", nullable: true),
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
                                    new OA\Property(property: "years_of_experience", type: "integer", example: 5),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "links",
                            properties: [
                                new OA\Property(property: "first", type: "string", example: "http://example.com/api/available-doctors?page=1"),
                                new OA\Property(property: "last", type: "string", example: "http://example.com/api/available-doctors?page=2"),
                                new OA\Property(property: "prev", type: "string", example: null, nullable: true),
                                new OA\Property(property: "next", type: "string", example: "http://example.com/api/available-doctors?page=2"),
                            ]
                        ),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "from", type: "integer", example: 1),
                                new OA\Property(property: "last_page", type: "integer", example: 2),
                                new OA\Property(property: "path", type: "string", example: "http://example.com/api/available-doctors"),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "to", type: "integer", example: 10),
                                new OA\Property(property: "total", type: "integer", example: 10),
                            ]
                        ),
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
        summary: "Send invitation to doctor",
        description: "Sends a job invitation from clinic to a doctor",
        tags: ["Invitations"],
        security: [["jwt" => ["clinic"]]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor to invite"),
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic (auto-filled from authenticated clinic)"),
                        new OA\Property(property: "message", type: "string", example: "We would like to invite you to join our medical team.", description: "Custom message to include in the invitation (optional)"),
                    ],
                    required: ["doctor_id"]
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
                                new OA\Property(property: "status", type: "string", example: "pending"),
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
                description: "Forbidden - not authorized to send invitation"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor or clinic not found"
            ),
            new OA\Response(
                response: 400,
                description: "Invitation already exists"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            ),
            new OA\Response(
                response: 500,
                description: "Server error during invitation"
            )
        ]
    )]
    public function sendInvitation()
    {
    }

    #[OA\Get(
        path: "/api/clinics/invitations",
        summary: "Get invitations sent by clinic",
        description: "Gets all invitations sent by the authenticated clinic",
        tags: ["Invitations"],
        security: [["jwt" => ["clinic"]]],
        parameters: [
            new OA\Parameter(
                name: "status",
                description: "Filter invitations by status (pending, accepted, rejected, cancelled)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "pending")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of invitations sent by clinic",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                    new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                    new OA\Property(property: "message", type: "string", example: "We would like to invite you to join our medical team."),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                    new OA\Property(property: "responded_at", type: "string", format: "date-time", example: null, nullable: true),
                                    new OA\Property(
                                        property: "doctor",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
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
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "clinic",
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
                description: "Forbidden - not authorized to view invitations"
            )
        ]
    )]
    public function getClinicInvitations()
    {
    }

    #[OA\Patch(
        path: "/api/clinics/invitations/{invitation_id}/cancel",
        summary: "Cancel an invitation",
        description: "Cancels an invitation sent by the clinic",
        tags: ["Invitations"],
        security: [["jwt" => ["clinic"]]],
        parameters: [
            new OA\Parameter(
                name: "invitation_id",
                description: "Invitation ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Invitation cancelled successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Invitation cancelled successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "invitation_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "cancelled"),
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
                description: "Forbidden - not authorized to cancel this invitation"
            ),
            new OA\Response(
                response: 404,
                description: "Invitation not found or does not belong to this clinic"
            ),
            new OA\Response(
                response: 400,
                description: "Cannot cancel invitation - it has already been processed"
            )
        ]
    )]
    public function cancelInvitation()
    {
    }

    #[OA\Patch(
        path: "/api/doctor/invitations/{invitation_id}",
        summary: "Respond to invitation",
        description: "Responds to an invitation from a clinic (accept/reject)",
        tags: ["Invitations"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "invitation_id",
                description: "Invitation ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "status", type: "string", enum: ["accepted", "rejected"], example: "accepted", description: "Response to the invitation"),
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
                                new OA\Property(property: "status", type: "string", example: "accepted"),
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
                description: "Forbidden - not authorized to respond to this invitation"
            ),
            new OA\Response(
                response: 404,
                description: "Invitation not found"
            ),
            new OA\Response(
                response: 400,
                description: "Invitation already responded to"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            ),
            new OA\Response(
                response: 500,
                description: "Server error during response processing"
            )
        ]
    )]
    public function respondToInvitation()
    {
    }
}