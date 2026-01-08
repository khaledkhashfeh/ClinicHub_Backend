<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class SubscriptionApi
{
    #[OA\Get(
        path: "/api/subscription-plans",
        summary: "Get all subscription plans",
        description: "Returns a list of all active subscription plans",
        tags: ["Subscription Plans"],
        parameters: [
            new OA\Parameter(
                name: "target_type",
                description: "Filter plans by target type (clinic or medical_center)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["clinic", "medical_center"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of subscription plans",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Basic Plan"),
                                    new OA\Property(property: "target_type", type: "string", example: "clinic"),
                                    new OA\Property(property: "price", type: "number", example: 100.00),
                                    new OA\Property(property: "duration_days", type: "integer", example: 30),
                                    new OA\Property(property: "description", type: "string", example: "Basic subscription plan"),
                                    new OA\Property(property: "is_active", type: "boolean", example: true),
                                    new OA\Property(
                                        property: "features",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "text", type: "string", example: "Up to 5 doctors"),
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: "entitlements",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "key", type: "string", example: "enable_secretary"),
                                                new OA\Property(property: "value", type: "string", example: "true"),
                                                new OA\Property(property: "type", type: "string", example: "boolean"),
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            )
        ]
    )]
    public function indexPlans()
    {
    }

    #[OA\Get(
        path: "/api/subscription-plans/{id}",
        summary: "Get a specific subscription plan",
        description: "Returns details of a specific subscription plan",
        tags: ["Subscription Plans"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Subscription plan ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Subscription plan details",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Basic Plan"),
                                new OA\Property(property: "target_type", type: "string", example: "clinic"),
                                new OA\Property(property: "price", type: "number", example: 100.00),
                                new OA\Property(property: "duration_days", type: "integer", example: 30),
                                new OA\Property(property: "description", type: "string", example: "Basic subscription plan"),
                                new OA\Property(property: "is_active", type: "boolean", example: true),
                                new OA\Property(
                                    property: "features",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "text", type: "string", example: "Up to 5 doctors"),
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: "entitlements",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "key", type: "string", example: "enable_secretary"),
                                            new OA\Property(property: "value", type: "string", example: "true"),
                                            new OA\Property(property: "type", type: "string", example: "boolean"),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Subscription plan not found"
            )
        ]
    )]
    public function showPlan()
    {
    }

    #[OA\Get(
        path: "/api/my-subscription",
        summary: "Get current user's subscription",
        description: "Returns the current subscription of the authenticated user",
        tags: ["Subscriptions"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Current subscription details",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "subscription",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(
                                            property: "plan",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "name", type: "string", example: "Basic Plan"),
                                                new OA\Property(property: "target_type", type: "string", example: "clinic"),
                                                new OA\Property(property: "price", type: "number", example: 100.00),
                                                new OA\Property(property: "duration_days", type: "integer", example: 30),
                                                new OA\Property(
                                                    property: "features",
                                                    type: "array",
                                                    items: new OA\Items(
                                                        properties: [
                                                            new OA\Property(property: "id", type: "integer", example: 1),
                                                            new OA\Property(property: "text", type: "string", example: "Up to 5 doctors"),
                                                        ]
                                                    )
                                                ),
                                            ]
                                        ),
                                        new OA\Property(property: "starts_at", type: "string", format: "date", example: "2023-01-01"),
                                        new OA\Property(property: "ends_at", type: "string", format: "date", example: "2023-01-31"),
                                        new OA\Property(property: "status", type: "string", example: "active"),
                                        new OA\Property(property: "is_active", type: "boolean", example: true),
                                        new OA\Property(property: "days_remaining", type: "integer", example: 15),
                                    ]
                                ),
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
                description: "No subscription found"
            )
        ]
    )]
    public function mySubscription()
    {
    }

    #[OA\Get(
        path: "/api/admin/subscriptions",
        summary: "Get all subscriptions (Admin)",
        description: "Returns a list of all subscriptions (Admin only)",
        tags: ["Admin Subscriptions"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "status",
                description: "Filter subscriptions by status",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["trial", "active", "expired", "canceled"])
            ),
            new OA\Parameter(
                name: "subscribable_type",
                description: "Filter subscriptions by subscribable type",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["clinic", "medical_center"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of subscriptions",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(
                                        property: "plan",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Basic Plan"),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: "subscribable",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "clinic_name", type: "string", example: "Sample Clinic"),
                                        ]
                                    ),
                                    new OA\Property(property: "starts_at", type: "string", format: "date", example: "2023-01-01"),
                                    new OA\Property(property: "ends_at", type: "string", format: "date", example: "2023-01-31"),
                                    new OA\Property(property: "status", type: "string", example: "active"),
                                    new OA\Property(property: "is_active", type: "boolean", example: true),
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
    public function index()
    {
    }

    #[OA\Post(
        path: "/api/admin/subscription-plans",
        summary: "Create a new subscription plan (Admin)",
        description: "Creates a new subscription plan (Admin only)",
        tags: ["Admin Subscription Plans"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Premium Plan"),
                        new OA\Property(property: "target_type", type: "string", enum: ["clinic", "medical_center"], example: "clinic"),
                        new OA\Property(property: "price", type: "number", example: 200.00),
                        new OA\Property(property: "duration_days", type: "integer", example: 30),
                        new OA\Property(property: "description", type: "string", example: "Premium subscription plan"),
                        new OA\Property(
                            property: "features",
                            type: "array",
                            items: new OA\Items(type: "string", example: "Unlimited appointments")
                        ),
                        new OA\Property(
                            property: "entitlements",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "key", type: "string", example: "enable_secretary"),
                                    new OA\Property(property: "value", type: "string", example: "true"),
                                    new OA\Property(property: "type", type: "string", enum: ["boolean", "integer", "string", "decimal"], example: "boolean"),
                                ]
                            )
                        ),
                    ],
                    required: ["name", "target_type", "price", "duration_days", "features"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Subscription plan created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إنشاء الخطة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Premium Plan"),
                                new OA\Property(property: "target_type", type: "string", example: "clinic"),
                                new OA\Property(property: "price", type: "number", example: 200.00),
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
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function storePlan()
    {
    }

    #[OA\Put(
        path: "/api/admin/subscription-plans/{id}",
        summary: "Update a subscription plan (Admin)",
        description: "Updates an existing subscription plan (Admin only)",
        tags: ["Admin Subscription Plans"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Subscription plan ID",
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
                        new OA\Property(property: "name", type: "string", example: "Updated Premium Plan"),
                        new OA\Property(property: "target_type", type: "string", enum: ["clinic", "medical_center"], example: "clinic"),
                        new OA\Property(property: "price", type: "number", example: 250.00),
                        new OA\Property(property: "duration_days", type: "integer", example: 45),
                        new OA\Property(property: "description", type: "string", example: "Updated premium subscription plan"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(
                            property: "features",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "text", type: "string", example: "Updated feature"),
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
                description: "Subscription plan updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث الخطة بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Updated Premium Plan"),
                                new OA\Property(property: "target_type", type: "string", example: "clinic"),
                                new OA\Property(property: "price", type: "number", example: 250.00),
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
                description: "Subscription plan not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function updatePlan()
    {
    }

    #[OA\Delete(
        path: "/api/admin/subscription-plans/{id}",
        summary: "Delete a subscription plan (Admin)",
        description: "Deletes an existing subscription plan (Admin only)",
        tags: ["Admin Subscription Plans"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Subscription plan ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Subscription plan deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم حذف الخطة وجميع الميزات المرتبطة بها بنجاح"),
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
                description: "Subscription plan not found"
            ),
            new OA\Response(
                response: 400,
                description: "Cannot delete plan with active subscriptions"
            )
        ]
    )]
    public function destroyPlan()
    {
    }

    #[OA\Post(
        path: "/api/admin/subscriptions",
        summary: "Create a new subscription (Admin)",
        description: "Creates a new subscription for a clinic or medical center (Admin only)",
        tags: ["Admin Subscriptions"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "subscribable_type", type: "string", enum: ["clinic", "medical_center"], example: "clinic"),
                        new OA\Property(property: "subscribable_id", type: "integer", example: 1),
                        new OA\Property(property: "plan_id", type: "integer", example: 1),
                        new OA\Property(property: "starts_at", type: "string", format: "date", example: "2023-01-01"),
                        new OA\Property(property: "status", type: "string", enum: ["trial", "active", "expired", "canceled"], example: "active"),
                        new OA\Property(property: "notes", type: "string", example: "Initial subscription"),
                    ],
                    required: ["subscribable_type", "subscribable_id", "plan_id"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Subscription created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تعيين الاشتراك بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "active"),
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
                description: "Clinic/medical center or plan not found"
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

    #[OA\Patch(
        path: "/api/admin/subscriptions/{id}",
        summary: "Update a subscription (Admin)",
        description: "Updates an existing subscription (Admin only)",
        tags: ["Admin Subscriptions"],
        security: [["jwt" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Subscription ID",
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
                        new OA\Property(property: "status", type: "string", enum: ["trial", "active", "expired", "canceled"], example: "canceled"),
                        new OA\Property(property: "notes", type: "string", example: "Subscription canceled due to non-payment"),
                    ],
                    required: ["status"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Subscription updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث الاشتراك بنجاح"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "status", type: "string", example: "canceled"),
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
                description: "Subscription not found"
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
}