<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "ClinicHub API",
    version: "1.0.0",
    description: "API documentation for ClinicHub application"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Development server"
)]
#[OA\Server(
    url: "http://72.62.80.169:8000",
    description: "Production server"
)]
#[OA\Tag(
    name: "Admin Authentication",
    description: "Endpoints for admin authentication"
)]
#[OA\Tag(
    name: "Admin Management",
    description: "Endpoints for managing doctors, clinics, and secretaries"
)]
#[OA\Tag(
    name: "Clinic Authentication",
    description: "Endpoints for clinic authentication"
)]
#[OA\Tag(
    name: "Clinic Profile",
    description: "Endpoints for managing clinic profile"
)]
#[OA\Tag(
    name: "Clinic Doctors",
    description: "Endpoints for managing doctors associated with clinics"
)]
#[OA\Tag(
    name: "Doctor Authentication",
    description: "Endpoints for doctor authentication"
)]
#[OA\Tag(
    name: "Doctor Profile",
    description: "Endpoints for managing doctor profile"
)]
#[OA\Tag(
    name: "Secretary Authentication",
    description: "Endpoints for secretary authentication"
)]
#[OA\Tag(
    name: "Secretary Profile",
    description: "Endpoints for managing secretary profile"
)]
#[OA\Tag(
    name: "Patient Authentication",
    description: "Endpoints for patient authentication"
)]
#[OA\Tag(
    name: "Patient Profile",
    description: "Endpoints for managing patient profile"
)]
#[OA\Tag(
    name: "Governorates",
    description: "Endpoints for managing governorates and districts"
)]
#[OA\Tag(
    name: "Medical Specializations",
    description: "Endpoints for managing medical specializations"
)]
#[OA\Tag(
    name: "Medical Centers",
    description: "Endpoints for managing medical centers"
)]
#[OA\Tag(
    name: "Medical Team",
    description: "Endpoints for medical team authentication"
)]
#[OA\Tag(
    name: "Subscriptions",
    description: "Endpoints for managing subscriptions"
)]
#[OA\Tag(
    name: "Notifications",
    description: "Endpoints for managing notifications"
)]
#[OA\Tag(
    name: "Invitations",
    description: "Endpoints for managing invitations"
)]
#[OA\Tag(
    name: "Appointments",
    description: "Endpoints for managing appointments and schedules"
)]
class ApiDocumentation
{
    // This class is used to provide global OpenAPI documentation information
}
