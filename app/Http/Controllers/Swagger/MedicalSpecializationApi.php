<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class MedicalSpecializationApi
{
    #[OA\Get(
        path: "/api/medical-specializations",
        summary: "Get all medical specializations",
        description: "Returns a list of all medical specializations",
        tags: ["Medical Specializations"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of medical specializations",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Cardiology"),
                            new OA\Property(property: "image_url", type: "string", example: "http://example.com/images/cardiology.jpg"),
                            new OA\Property(property: "is_active", type: "boolean", example: true),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
    }

    #[OA\Post(
        path: "/api/medical-specializations",
        summary: "Create a new medical specialization",
        description: "Creates a new medical specialization",
        tags: ["Medical Specializations"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Dermatology"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Specialization image file"),
                        new OA\Property(property: "is_active", type: "string", description: "Set to '1' for active, '0' for inactive", example: "1"),
                    ],
                    required: ["name"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Medical specialization created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إضافة التخصص الطبي بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Dermatology"),
                                new OA\Property(property: "image_url", type: "string", example: "http://example.com/storage/medical_specializations/image.jpg"),
                                new OA\Property(property: "is_active", type: "boolean", example: true),
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
                description: "Validation error"
            )
        ]
    )]
    public function store()
    {
    }

    #[OA\Put(
        path: "/api/medical-specializations/{medicalSpecialization}",
        summary: "Update an existing medical specialization",
        description: "Updates an existing medical specialization",
        tags: ["Medical Specializations"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "medicalSpecialization",
                description: "Medical Specialization ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Updated Dermatology"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Specialization image file"),
                        new OA\Property(property: "is_active", type: "string", description: "Set to '1' for active, '0' for inactive", example: "0"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Medical specialization updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث التخصص بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Updated Dermatology"),
                                new OA\Property(property: "image_url", type: "string", example: "http://example.com/storage/medical_specializations/updated_image.jpg"),
                                new OA\Property(property: "is_active", type: "boolean", example: false),
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
                response: 404,
                description: "Medical specialization not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function update()
    {
    }

    #[OA\Delete(
        path: "/api/medical-specializations/{medicalSpecialization}",
        summary: "Delete a medical specialization",
        description: "Deletes a medical specialization",
        tags: ["Medical Specializations"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "medicalSpecialization",
                description: "Medical Specialization ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Medical specialization deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم حذف تخصص Cardiology بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: 404,
                description: "Medical specialization not found"
            )
        ]
    )]
    public function destroy()
    {
    }
}
