<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class AppointmentsApi
{
    #[OA\Post(
        path: "/api/appointments/set-doctor-work-settings",
        summary: "Set doctor's work settings",
        description: "Updates the work settings for a doctor at a specific clinic, including method selection, appointment period, and queue settings",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic"),
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor"),
                        new OA\Property(property: "method_id", type: "integer", example: 1, description: "Method ID (1 for Auto scheduling, 2 for Manual scheduling)"),
                        new OA\Property(property: "appointment_period", type: "integer", example: 30, description: "Default appointment period in minutes"),
                        new OA\Property(property: "queue", type: "boolean", example: true, description: "Whether to enable queue system"),
                        new OA\Property(property: "queue_number", type: "integer", example: 1, description: "Queue number (required if queue is enabled)", nullable: true),
                    ],
                    required: ["clinic_id", "doctor_id", "method_id", "appointment_period", "queue"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Doctor work settings updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Doctor work settings updated successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "method_id", type: "integer", example: 1),
                                new OA\Property(property: "appointment_period", type: "integer", example: 30),
                                new OA\Property(property: "queue", type: "boolean", example: true),
                                new OA\Property(property: "queue_number", type: "integer", example: 1),
                                new OA\Property(property: "clinic", type: "object", example: "{}"),
                                new OA\Property(property: "doctor", type: "object", example: "{}"),
                                new OA\Property(property: "method", type: "object", example: "{}"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Weekly schedules are only allowed for method 1 (Auto scheduling)"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor is not associated with the specified clinic"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to update doctor work settings"
            )
        ]
    )]
    public function setDoctorWorkSettings()
    {
    }

    #[OA\Post(
        path: "/api/appointments/set-weekly-schedule",
        summary: "Set weekly schedule for doctor",
        description: "Create or update a weekly schedule template for a doctor in a clinic",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic"),
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor"),
                        new OA\Property(property: "appointment_duration", type: "integer", example: 30, description: "Duration of each appointment in minutes"),
                        new OA\Property(property: "effective_from", type: "string", format: "date", example: "2023-01-01", description: "Date when schedule becomes effective"),
                        new OA\Property(property: "effective_to", type: "string", format: "date", example: "2023-12-31", description: "Date when schedule ends (optional)", nullable: true),
                        new OA\Property(
                            property: "weekly_schedule",
                            type: "array",
                            description: "Array of weekly schedule days",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "day_of_week", type: "integer", example: 1, description: "Day of week (1=Monday, 7=Sunday)"),
                                    new OA\Property(property: "start_time", type: "string", example: "09:00:00", description: "Start time of the day"),
                                    new OA\Property(property: "end_time", type: "string", example: "17:00:00", description: "End time of the day"),
                                    new OA\Property(
                                        property: "breaks",
                                        type: "array",
                                        description: "Array of break periods (optional)",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "start", type: "string", example: "12:00:00", description: "Break start time"),
                                                new OA\Property(property: "end", type: "string", example: "13:00:00", description: "Break end time"),
                                            ]
                                        ),
                                        nullable: true
                                    ),
                                ]
                            )
                        ),
                    ],
                    required: ["clinic_id", "doctor_id", "appointment_duration", "effective_from", "weekly_schedule"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Weekly schedule created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Weekly schedule created successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "appointment_duration", type: "integer", example: 30),
                                new OA\Property(property: "effective_from", type: "string", format: "date", example: "2023-01-01"),
                                new OA\Property(property: "effective_to", type: "string", format: "date", example: "2023-12-31", nullable: true),
                                new OA\Property(property: "week_days", type: "integer", example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Doctor is not associated with the specified clinic"
            ),
            new OA\Response(
                response: 422,
                description: "Validation errors in the request data"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to create weekly schedule"
            )
        ]
    )]
    public function setWeeklySchedule()
    {
    }

    #[OA\Post(
        path: "/api/appointments/generate-slots",
        summary: "Generate appointment slots",
        description: "Generate appointment slots from schedule templates for a date range",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic"),
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor"),
                        new OA\Property(property: "start_date", type: "string", format: "date", example: "2023-01-01", description: "Start date for slot generation"),
                        new OA\Property(property: "end_date", type: "string", format: "date", example: "2023-01-31", description: "End date for slot generation"),
                    ],
                    required: ["clinic_id", "doctor_id", "start_date", "end_date"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Slots generated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Slots generated successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "start_date", type: "string", format: "date", example: "2023-01-01"),
                                new OA\Property(property: "end_date", type: "string", format: "date", example: "2023-01-31"),
                                new OA\Property(property: "slots_created", type: "integer", example: 20),
                                new OA\Property(property: "slots_skipped", type: "integer", example: 5),
                                new OA\Property(property: "dates_processed", type: "integer", example: 15),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Slot generation is only allowed for method 1 (Auto scheduling)"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor is not associated with the specified clinic or no active schedule templates found"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to generate slots"
            )
        ]
    )]
    public function generateSlots()
    {
    }

    #[OA\Post(
        path: "/api/appointments/create-manual-slots",
        summary: "Create manual appointment slots",
        description: "Create manual appointment slots for a specific date (method 2 - Manual scheduling)",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic"),
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor"),
                        new OA\Property(property: "date", type: "string", format: "date", example: "2023-01-01", description: "Date for slot creation"),
                        new OA\Property(
                            property: "slots",
                            type: "array",
                            description: "Array of manual slots to create",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "start", type: "string", example: "09:00:00", description: "Slot start time"),
                                    new OA\Property(property: "end", type: "string", example: "09:30:00", description: "Slot end time"),
                                ]
                            )
                        ),
                        new OA\Property(property: "replace_existing", type: "boolean", example: false, description: "Whether to replace existing manual slots", nullable: true),
                    ],
                    required: ["clinic_id", "doctor_id", "date", "slots"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Manual slots created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Manual slots created successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "date", type: "string", format: "date", example: "2023-01-01"),
                                new OA\Property(property: "slots_created", type: "integer", example: 5),
                                new OA\Property(
                                    property: "slots",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "date", type: "string", format: "date", example: "2023-01-01"),
                                            new OA\Property(property: "start_time", type: "string", example: "09:00:00"),
                                            new OA\Property(property: "end_time", type: "string", example: "09:30:00"),
                                            new OA\Property(property: "status", type: "string", example: "available"),
                                            new OA\Property(property: "creation_method", type: "string", example: "manual"),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Manual slot creation is only allowed for method 2 (Manual scheduling)"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor is not associated with the specified clinic"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to create manual slots"
            )
        ]
    )]
    public function createManualSlots()
    {
    }

    #[OA\Post(
        path: "/api/appointments/add-override",
        summary: "Add schedule override",
        description: "Add an override for a specific date (closed or custom slots)",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_id", type: "integer", example: 1, description: "ID of the clinic"),
                        new OA\Property(property: "doctor_id", type: "integer", example: 1, description: "ID of the doctor"),
                        new OA\Property(property: "date", type: "string", format: "date", example: "2023-01-01", description: "Date for the override"),
                        new OA\Property(property: "type", type: "string", example: "closed", enum: ["closed", "custom"], description: "Type of override"),
                        new OA\Property(
                            property: "custom_slots",
                            type: "array",
                            description: "Custom slots for the day (required if type is 'custom')",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "start", type: "string", example: "10:00:00", description: "Slot start time"),
                                    new OA\Property(property: "end", type: "string", example: "11:00:00", description: "Slot end time"),
                                ]
                            ),
                            nullable: true
                        ),
                        new OA\Property(property: "reason", type: "string", example: "Holiday", description: "Reason for the override", nullable: true),
                    ],
                    required: ["clinic_id", "doctor_id", "date", "type"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Override added successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Override added successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "date", type: "string", format: "date", example: "2023-01-01"),
                                new OA\Property(property: "type", type: "string", example: "closed"),
                                new OA\Property(
                                    property: "custom_slots",
                                    type: "array",
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "start", type: "string", example: "10:00:00", description: "Slot start time"),
                                            new OA\Property(property: "end", type: "string", example: "11:00:00", description: "Slot end time"),
                                        ]
                                    ),
                                    nullable: true
                                ),
                                new OA\Property(property: "reason", type: "string", example: "Holiday", nullable: true),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Doctor is not associated with the specified clinic"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to add/update override"
            )
        ]
    )]
    public function addOverride()
    {
    }
}