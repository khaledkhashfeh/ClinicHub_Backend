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
class ApiDocumentation
{
    // This class is used to provide global OpenAPI documentation information
}
