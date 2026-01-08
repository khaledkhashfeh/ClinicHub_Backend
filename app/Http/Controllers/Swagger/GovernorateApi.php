<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class GovernorateApi
{
    #[OA\Get(
        path: "/api/governorates",
        summary: "Get all governorates",
        description: "Returns a list of all governorates",
        tags: ["Governorates"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of governorates",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Baghdad"),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
    }

    #[OA\Get(
        path: "/api/governorates/{governorate}/districts",
        summary: "Get districts by governorate",
        description: "Returns a list of districts (cities) for a specific governorate",
        tags: ["Governorates"],
        parameters: [
            new OA\Parameter(
                name: "governorate",
                description: "Governorate ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of districts",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "governorate_id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Al-Karkh"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Governorate not found"
            )
        ]
    )]
    public function districts()
    {
    }

    #[OA\Post(
        path: "/api/governorates/{governorate}/districts",
        summary: "Add a new district to a governorate",
        description: "Adds a new district (city) to a specific governorate",
        tags: ["Governorates"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "governorate",
                description: "Governorate ID",
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
                        new OA\Property(property: "name", type: "string", example: "New District Name"),
                    ],
                    required: ["name"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "District created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إضافة المنطقة بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "governorate_id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "New District Name"),
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
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "فشل التحقق من البيانات."),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            )
        ]
    )]
    public function storeDistrict()
    {
    }
}