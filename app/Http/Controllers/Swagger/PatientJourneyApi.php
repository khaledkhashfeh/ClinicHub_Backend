<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class PatientJourneyApi
{
    #[OA\Get(
        path: "/api/v1/patient/lab-tests",
        summary: "Get patient lab tests",
        description: "Returns actionable lab tests requested for the authenticated patient",
        tags: ["Patient Journey"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lab tests retrieved successfully",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "test_id", type: "string", example: "LAB-772"),
                            new OA\Property(property: "test_name", type: "string", example: "Vitamin D Test"),
                            new OA\Property(property: "status", type: "string", example: "pending"),
                            new OA\Property(property: "instructions", type: "string", nullable: true, example: "Fast for 12 hours"),
                            new OA\Property(property: "is_urgent", type: "boolean", example: false),
                            new OA\Property(property: "request_date", type: "string", format: "date", example: "2026-02-01"),
                            new OA\Property(property: "reminders_sent", type: "integer", example: 1),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 403, description: "Only patients can access this endpoint"),
        ]
    )]
    public function getLabTests()
    {
    }

    #[OA\Post(
        path: "/api/v1/patient/lab-tests/{id}/upload",
        summary: "Upload lab test result",
        description: "Uploads a lab result and marks the test request as completed",
        tags: ["Patient Journey"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1),
                description: "Lab request ID"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["file_url", "file_type", "completion_date"],
                    properties: [
                        new OA\Property(property: "file_url", type: "string", format: "uri", example: "https://storage.clinic.com/results/user1_lab_abc.jpg"),
                        new OA\Property(property: "file_type", type: "string", example: "image"),
                        new OA\Property(property: "completion_date", type: "string", format: "date-time", example: "2026-02-01T22:00:00Z"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Upload accepted"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Lab request not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function uploadLabTestResult()
    {
    }

    #[OA\Get(
        path: "/api/v1/patient/medications",
        summary: "Get medication tracker list",
        description: "Returns actionable medication trackers for the authenticated patient",
        tags: ["Patient Journey"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Medication trackers retrieved successfully"
            ),
            new OA\Response(response: 403, description: "Only patients can access this endpoint"),
        ]
    )]
    public function getMedications()
    {
    }

    #[OA\Patch(
        path: "/api/v1/patient/medications/{id}/activate",
        summary: "Activate medication tracking",
        description: "Marks medication as purchased and generates dose schedule from start date",
        tags: ["Patient Journey"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1),
                description: "Medication tracker ID"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["start_date"],
                    properties: [
                        new OA\Property(property: "start_date", type: "string", format: "date-time", example: "2026-02-01T23:00:00Z"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Medication activated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Medication tracker not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function activateMedication()
    {
    }

    #[OA\Post(
        path: "/api/v1/patient/medications/{id}/track-dose",
        summary: "Track taken medication dose",
        description: "Marks one pending dose as taken and recalculates adherence progress",
        tags: ["Patient Journey"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1),
                description: "Medication tracker ID"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dose tracked successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "new_progress_percentage", type: "number", format: "float", example: 15.5),
                        new OA\Property(property: "doses_remaining", type: "integer", example: 12),
                        new OA\Property(property: "next_dose_at", type: "string", format: "date-time", nullable: true, example: "2026-02-02T11:00:00Z"),
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Medication tracker not found"),
            new OA\Response(response: 400, description: "No pending doses or medication not active"),
        ]
    )]
    public function trackDose()
    {
    }
}
