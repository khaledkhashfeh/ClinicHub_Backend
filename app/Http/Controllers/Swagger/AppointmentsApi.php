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

    #[OA\Get(
        path: "/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/booking-info",
        summary: "Get doctor booking info",
        description: "Returns booking information for a specific doctor at a clinic",
        tags: ["Appointments"],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "doctor_id",
                description: "Doctor ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Booking info retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "doctor_id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_id", type: "integer", example: 1),
                                new OA\Property(property: "appointment_period", type: "integer", example: 30),
                                new OA\Property(property: "queue_enabled", type: "boolean", example: true),
                                new OA\Property(property: "consultation_fee", type: "number", example: 50000),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Doctor or clinic not found"
            )
        ]
    )]
    public function bookingInfo()
    {
    }

    #[OA\Get(
        path: "/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/available-appointments",
        summary: "Get available appointments",
        description: "Returns available appointment slots for a specific doctor at a clinic",
        tags: ["Appointments"],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "doctor_id",
                description: "Doctor ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "date",
                description: "Filter by date (optional)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2026-01-15")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Available appointments retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "date", type: "string", format: "date", example: "2026-01-15"),
                                    new OA\Property(property: "start_time", type: "string", example: "09:00:00"),
                                    new OA\Property(property: "end_time", type: "string", example: "09:30:00"),
                                    new OA\Property(property: "status", type: "string", example: "available"),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Doctor or clinic not found"
            )
        ]
    )]
    public function availableAppointments()
    {
    }

    #[OA\Post(
        path: "/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/submit",
        summary: "Submit appointment booking",
        description: "Books an appointment for a patient with a specific doctor at a clinic",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "doctor_id",
                description: "Doctor ID",
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
                        new OA\Property(property: "patient_id", type: "integer", example: 1, description: "ID of the patient"),
                        new OA\Property(property: "slot_id", type: "integer", example: 1, description: "ID of the selected slot"),
                        new OA\Property(property: "notes", type: "string", example: "ملاحظات", description: "Optional notes for the appointment"),
                    ],
                    required: ["patient_id", "slot_id"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Appointment booked successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم حجز الموعد بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "pending"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid slot or patient data"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor, clinic, or slot not found"
            )
        ]
    )]
    public function submitAppointment()
    {
    }

    #[OA\Post(
        path: "/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/waiting-list",
        summary: "Join waiting list",
        description: "Adds a patient to the waiting list for a doctor at a clinic",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "doctor_id",
                description: "Doctor ID",
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
                        new OA\Property(property: "patient_id", type: "integer", example: 1, description: "ID of the patient"),
                        new OA\Property(property: "preferred_date", type: "string", format: "date", example: "2026-01-15", description: "Preferred appointment date"),
                    ],
                    required: ["patient_id", "preferred_date"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Added to waiting list successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تمت الإضافة إلى قائمة الانتظار بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "waiting_list_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "pending"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid patient data"
            ),
            new OA\Response(
                response: 404,
                description: "Doctor or clinic not found"
            )
        ]
    )]
    public function waitingList()
    {
    }

    #[OA\Post(
        path: "/api/v1/appointments/{appointment_id}/cancel",
        summary: "Cancel appointment",
        description: "Cancels an existing appointment",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "appointment_id",
                description: "Appointment ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Appointment cancelled successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إلغاء الموعد بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "cancelled"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Cannot cancel this appointment"
            ),
            new OA\Response(
                response: 404,
                description: "Appointment not found"
            )
        ]
    )]
    public function cancelAppointment()
    {
    }

    #[OA\Post(
        path: "/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/mark-attended",
        summary: "Mark appointment as attended",
        description: "Marks an appointment as attended (clinic only)",
        tags: ["Appointments"],
        security: [["jwt" => ["clinic"]]],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "appointment_id",
                description: "Appointment ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Appointment marked as attended",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تعليم الموعد كحضور"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "attended"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Cannot mark this appointment"
            ),
            new OA\Response(
                response: 404,
                description: "Appointment not found"
            )
        ]
    )]
    public function markAttended()
    {
    }

    #[OA\Post(
        path: "/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-initial",
        summary: "Confirm appointment initial",
        description: "Confirms the initial stage of an appointment (clinic only)",
        tags: ["Appointments"],
        security: [["jwt" => ["clinic"]]],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "appointment_id",
                description: "Appointment ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Appointment initial confirmed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تأكيد الموعد مبدئياً"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "initial_confirmed"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Cannot confirm this appointment"
            ),
            new OA\Response(
                response: 404,
                description: "Appointment not found"
            )
        ]
    )]
    public function confirmInitial()
    {
    }

    #[OA\Post(
        path: "/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-final",
        summary: "Confirm appointment final",
        description: "Confirms the final stage of an appointment (clinic only)",
        tags: ["Appointments"],
        security: [["jwt" => ["clinic"]]],
        parameters: [
            new OA\Parameter(
                name: "clinic_id",
                description: "Clinic ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "appointment_id",
                description: "Appointment ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Appointment final confirmed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تأكيد الموعد نهائياً"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "confirmed"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Cannot confirm this appointment"
            ),
            new OA\Response(
                response: 404,
                description: "Appointment not found"
            )
        ]
    )]
    public function confirmFinal()
    {
    }

    #[OA\Get(
        path: "/api/v1/appointments/{appointment_id}/consultation",
        summary: "Get consultation details",
        description: "Returns aggregated consultation details for an appointment if consultation has been issued",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "appointment_id",
                description: "Appointment ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 12345)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Consultation details retrieved successfully"
            ),
            new OA\Response(
                response: 204,
                description: "Consultation is not issued yet"
            ),
            new OA\Response(
                response: 403,
                description: "Unauthorized to access this consultation"
            ),
            new OA\Response(
                response: 404,
                description: "Appointment not found"
            )
        ]
    )]
    public function getConsultationDetails()
    {
    }

    #[OA\Post(
        path: "/api/v1/doctor/consultations/submit",
        summary: "Finalize consultation",
        description: "Finalize a medical consultation by saving diagnoses, prescriptions, and investigations, then mark the appointment as completed",
        tags: ["Appointments"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["metadata"],
                    properties: [
                        new OA\Property(
                            property: "metadata",
                            required: ["appointment_id", "patient_id", "doctor_id", "clinic_id"],
                            properties: [
                                new OA\Property(property: "appointment_id", type: "integer", example: 12345),
                                new OA\Property(property: "patient_id", type: "integer", example: 6789),
                                new OA\Property(property: "doctor_id", type: "integer", example: 101),
                                new OA\Property(property: "clinic_id", type: "integer", example: 50),
                                new OA\Property(property: "session_start_time", type: "string", format: "date-time", nullable: true, example: "2026-02-01T21:00:00Z"),
                            ],
                            type: "object"
                        ),
                        new OA\Property(property: "general_notes", type: "string", nullable: true, example: "Patient is stable and currently does not need medication."),
                        new OA\Property(
                            property: "diagnoses",
                            type: "array",
                            nullable: true,
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "condition_id", type: "string", nullable: true, example: "ICD10-I10"),
                                    new OA\Property(property: "condition_name", type: "string", example: "Essential (primary) hypertension"),
                                    new OA\Property(property: "classification", type: "string", nullable: true, example: "chronic"),
                                    new OA\Property(property: "notes", type: "string", nullable: true, example: "Slightly elevated blood pressure"),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "investigations",
                            type: "object",
                            nullable: true,
                            properties: [
                                new OA\Property(
                                    property: "requests",
                                    type: "array",
                                    nullable: true,
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "test_id", type: "string", nullable: true, example: "LAB-101"),
                                            new OA\Property(property: "test_name", type: "string", example: "Complete Blood Count (CBC)"),
                                            new OA\Property(property: "priority", type: "string", nullable: true, example: "normal"),
                                            new OA\Property(property: "instructions", type: "string", nullable: true, example: "Fasting required"),
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: "uploads",
                                    type: "array",
                                    nullable: true,
                                    items: new OA\Items(
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "file_name", type: "string", example: "kidney_function_test.pdf"),
                                            new OA\Property(property: "file_url", type: "string", format: "uri", example: "https://storage.clinic.com/uploads/2026/file_778.pdf"),
                                            new OA\Property(property: "file_type", type: "string", example: "pdf"),
                                            new OA\Property(property: "doctor_comment", type: "string", nullable: true, example: "Kidney function is within normal range"),
                                        ]
                                    )
                                ),
                            ]
                        ),
                        new OA\Property(
                            property: "prescriptions",
                            type: "array",
                            nullable: true,
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "medicine_name", type: "string", example: "Panadol 500mg"),
                                    new OA\Property(property: "total_quantity", type: "integer", nullable: true, example: 1),
                                    new OA\Property(property: "dose_description", type: "string", nullable: true, example: "One tablet"),
                                    new OA\Property(property: "daily_frequency", type: "integer", nullable: true, example: 3),
                                    new OA\Property(property: "hourly_interval", type: "integer", nullable: true, example: 8),
                                    new OA\Property(property: "food_relation", type: "string", nullable: true, example: "after_meal"),
                                    new OA\Property(property: "duration", type: "string", nullable: true, example: "7 days"),
                                    new OA\Property(property: "special_instructions", type: "string", nullable: true, example: "When needed"),
                                ]
                            )
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Consultation finalized successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم اصدار المعاينة للمريض احمد بنجاح"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Appointment cannot be finalized"
            ),
            new OA\Response(
                response: 403,
                description: "Unauthorized (doctor or metadata mismatch)"
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed"
            )
        ]
    )]
    public function finalizeConsultation()
    {
    }
}
