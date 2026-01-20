<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class ClinicApi
{
    #[OA\Post(
        path: "/api/clinic/login",
        summary: "Clinic login",
        description: "Authenticates a clinic and returns a JWT token",
        tags: ["Clinic Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "identifyer", type: "string", example: "clinic@example.com", description: "Email, username, or phone number"),
                        new OA\Property(property: "password", type: "string", example: "password123", format: "password"),
                    ],
                    required: ["identifyer", "password"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الدخول بنجاح."),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "role", type: "string", example: "Clinic"),
                        new OA\Property(
                            property: "clinic",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                new OA\Property(property: "facebook_link", type: "string", example: "https://www.facebook.com/clinic", nullable: true),
                                new OA\Property(property: "instagram_link", type: "string", example: "https://www.instagram.com/clinic", nullable: true),
                                new OA\Property(property: "website_link", type: "string", example: "https://www.clinic.com", nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials"
            ),
            new OA\Response(
                response: 403,
                description: "Account not approved"
            )
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: "/api/clinic/logout",
        summary: "Clinic logout",
        description: "Logs out the authenticated clinic",
        tags: ["Clinic Authentication"],
        security: [["jwt" => ["clinic"]]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الخروج بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "No token provided"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]
    public function logout()
    {
    }

    #[OA\Post(
        path: "/api/clinic/send-otp",
        summary: "Send OTP to clinic phone",
        description: "Sends an OTP code to the clinic's phone number",
        tags: ["Clinic Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567"),
                    ],
                    required: ["phone"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP sent successfully to your phone number."),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Phone number already exists"
            ),
            new OA\Response(
                response: 500,
                description: "Failed to send OTP"
            )
        ]
    )]
    public function sendOtp()
    {
    }

    #[OA\Post(
        path: "/api/clinic/verify-otp",
        summary: "Verify clinic's phone number",
        description: "Verifies a clinic's phone number using OTP",
        tags: ["Clinic Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "07771234567"),
                        new OA\Property(property: "otp", type: "string", example: "123456", description: "6-digit OTP code"),
                    ],
                    required: ["phone", "otp"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP verified successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP verified successfully."),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid or expired OTP"
            )
        ]
    )]
    public function verifyOtp()
    {
    }

    #[OA\Post(
        path: "/api/clinics",
        summary: "Register a new clinic",
        description: "Registers a new clinic account",
        tags: ["Clinic Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_name", type: "string", example: "عيادة الدكتور علي لطب الأسنان", description: "اسم العيادة"),
                        new OA\Property(property: "phone", type: "string", example: "0912345678", description: "رقم الهاتف"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "clinic@example.com", description: "البريد الإلكتروني للعيادة (اختياري)"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "ClinicSecurePassword", description: "كلمة المرور"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "ClinicSecurePassword", description: "تأكيد كلمة المرور"),
                        new OA\Property(property: "specialization_id", type: "integer", example: 4, description: "معرّف التخصص (ID يتم جلبه من API التخصصات)"),
                        new OA\Property(property: "governorate_id", type: "integer", example: 2, description: "معرّف المحافظة (ID يتم جلبه من API المحافظات)"),
                        new OA\Property(property: "city_id", type: "integer", example: 10, description: "معرّف المدينة (ID يتم جلبه من API المدن)"),
                        new OA\Property(property: "district_id", type: "integer", example: 205, description: "معرّف المنطقة (ID يتم جلبه من API المناطق)"),
                        new OA\Property(property: "address", type: "string", example: "شارع بغداد، مقابل مدرسة اليرموك", description: "العنوان (اختياري)"),
                        new OA\Property(property: "detailed_address", type: "string", example: "حلب - شارع الكواكبي - الطابق الأول", description: "العنوان التفصيلي"),
                        new OA\Property(property: "floor", type: "integer", example: 1, description: "الدور (اختياري)"),
                        new OA\Property(property: "room_number", type: "integer", example: 3, description: "رقم الغرفة (اختياري)"),
                        new OA\Property(property: "consultation_fee", type: "number", example: 15000, description: "رسوم الاستشارة"),
                        new OA\Property(property: "description", type: "string", example: "عيادة متخصصة في تجميل وزراعة الأسنان.", description: "وصف العيادة"),
                        new OA\Property(property: "username", type: "string", example: "ali_dental_clinic", description: "اسم المستخدم"),
                        new OA\Property(property: "main_image", type: "string", format: "binary", description: "صورة العيادة الرئيسية (يتم إرسال ملف الصورة)"),
                        new OA\Property(property: "working_hours", type: "string", example: "{\"saturday\":{\"open\":\"09:00\",\"close\":\"18:00\"}}", description: "ساعات العمل بصيغة JSON"),
                        new OA\Property(property: "latitude", type: "number", example: 33.5138, description: "خط العرض (اختياري)"),
                        new OA\Property(property: "longitude", type: "number", example: 36.2765, description: "خط الطول (اختياري)"),
                        new OA\Property(
                            property: "services[0][name]",
                            type: "string",
                            example: "تنظيف الأسنان",
                            description: "اسم الخدمة الأولى - استخدم services[0][name], services[0][price], services[1][name], services[1][price], إلخ"
                        ),
                        new OA\Property(
                            property: "services[0][price]",
                            type: "number",
                            example: 25000,
                            description: "سعر الخدمة الأولى"
                        ),
                        new OA\Property(
                            property: "gallery_images[0]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الأولى (اختياري)"
                        ),
                        new OA\Property(
                            property: "gallery_images[1]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الثانية (اختياري)"
                        ),
                        new OA\Property(
                            property: "gallery_images[2]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الثالثة (اختياري)"
                        ),
                    ],
                    required: ["clinic_name", "phone", "password", "password_confirmation", "specialization_id", "governorate_id", "city_id", "district_id", "detailed_address", "consultation_fee", "description", "username"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Clinic registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إنشاء العيادة بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "عيادة الدكتور علي لطب الأسنان"),
                                new OA\Property(property: "phone", type: "string", example: "0912345678"),
                                new OA\Property(property: "consultation_fee", type: "number", example: 15000),
                                new OA\Property(property: "subscription_plan_id", type: "integer", example: 1, description: "معرف خطة الاشتراك المعينة للعيادة"),
                                new OA\Property(
                                    property: "services",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "name", type: "string", example: "تنظيف الأسنان"),
                                            new OA\Property(property: "price", type: "number", example: 25000),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "phone",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The phone has already been taken.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function register()
    {
    }

    #[OA\Get(
        path: "/api/clinics/profile",
        summary: "Get clinic profile",
        description: "Returns the authenticated clinic's profile information",
        tags: ["Clinic Profile"],
        security: [["jwt" => ["clinic"]]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Clinic profile retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "Al-Rashid Clinic"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                new OA\Property(property: "specialization_id", type: "integer", example: 1),
                                new OA\Property(property: "governorate_id", type: "integer", example: 1),
                                new OA\Property(property: "city_id", type: "integer", example: 1),
                                new OA\Property(property: "district_id", type: "integer", example: 1),
                                new OA\Property(property: "detailed_address", type: "string", example: "123 Main Street, Baghdad"),
                                new OA\Property(property: "consultation_fee", type: "number", example: 25.0),
                                new OA\Property(property: "description", type: "string", example: "A modern clinic providing quality healthcare services"),
                                new OA\Property(property: "username", type: "string", example: "alrashid_clinic"),
                                new OA\Property(property: "main_image", type: "string", example: "images/clinics/main_image.jpg"),
                                new OA\Property(property: "working_hours", type: "object", example: '{"saturday": {"open": "09:00", "close": "18:00"}}'),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                                new OA\Property(property: "phone_verified_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(
                                    property: "specialization",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "Cardiology"),
                                    ]
                                ),
                                new OA\Property(
                                    property: "governorate",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name_ar", type: "string", example: "بغداد"),
                                    ]
                                ),
                                new OA\Property(
                                    property: "district",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name_ar", type: "string", example: "الكرخ"),
                                    ]
                                ),
                                new OA\Property(
                                    property: "services",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "General Consultation"),
                                            new OA\Property(property: "price", type: "number", example: 25.0),
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: "galleryImages",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "image_path", type: "string", example: "images/clinics/gallery/image.jpg"),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]
    public function show()
    {
    }

    #[OA\Put(
        path: "/api/clinics/profile",
        summary: "Update clinic profile",
        description: "Updates the authenticated clinic's profile information",
        tags: ["Clinic Profile"],
        security: [["jwt" => ["clinic"]]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clinic_name", type: "string", example: "Updated Clinic Name", description: "اسم العيادة"),
                        new OA\Property(property: "phone", type: "string", example: "0912345678", description: "رقم الهاتف"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "clinic@example.com", description: "البريد الإلكتروني للعيادة (اختياري)"),
                        new OA\Property(property: "specialization_id", type: "integer", example: 4, description: "معرّف التخصص (ID يتم جلبه من API التخصصات)"),
                        new OA\Property(property: "governorate_id", type: "integer", example: 2, description: "معرّف المحافظة (ID يتم جلبه من API المحافظات)"),
                        new OA\Property(property: "city_id", type: "integer", example: 10, description: "معرّف المدينة (ID يتم جلبه من API المدن)"),
                        new OA\Property(property: "district_id", type: "integer", example: 205, description: "معرّف المنطقة (ID يتم جلبه من API المناطق)"),
                        new OA\Property(property: "address", type: "string", example: "شارع بغداد، مقابل مدرسة اليرموك", description: "العنوان (اختياري)"),
                        new OA\Property(property: "detailed_address", type: "string", example: "حلب - شارع الكواكبي - الطابق الأول", description: "العنوان التفصيلي"),
                        new OA\Property(property: "floor", type: "integer", example: 1, description: "الدور (اختياري)"),
                        new OA\Property(property: "room_number", type: "integer", example: 3, description: "رقم الغرفة (اختياري)"),
                        new OA\Property(property: "consultation_fee", type: "number", example: 15000, description: "رسوم الاستشارة"),
                        new OA\Property(property: "description", type: "string", example: "عيادة متخصصة في تجميل وزراعة الأسنان.", description: "وصف العيادة"),
                        new OA\Property(property: "username", type: "string", example: "ali_dental_clinic", description: "اسم المستخدم"),
                        new OA\Property(property: "main_image", type: "string", format: "binary", description: "صورة العيادة الرئيسية (يتم إرسال ملف الصورة)"),
                        new OA\Property(property: "working_hours", type: "string", example: "{\"saturday\":{\"open\":\"09:00\",\"close\":\"18:00\"}}", description: "ساعات العمل بصيغة JSON"),
                        new OA\Property(property: "latitude", type: "number", example: 33.5138, description: "خط العرض (اختياري)"),
                        new OA\Property(property: "longitude", type: "number", example: 36.2765, description: "خط الطول (اختياري)"),
                        new OA\Property(
                            property: "services[0][name]",
                            type: "string",
                            example: "تنظيف الأسنان",
                            description: "اسم الخدمة الأولى - استخدم services[0][name], services[0][price], services[1][name], services[1][price], إلخ"
                        ),
                        new OA\Property(
                            property: "services[0][price]",
                            type: "number",
                            example: 25000,
                            description: "سعر الخدمة الأولى"
                        ),
                        new OA\Property(
                            property: "gallery_images[0]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الأولى (اختياري)"
                        ),
                        new OA\Property(
                            property: "gallery_images[1]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الثانية (اختياري)"
                        ),
                        new OA\Property(
                            property: "gallery_images[2]",
                            type: "string",
                            format: "binary",
                            description: "صورة المعرض الثالثة (اختياري)"
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Clinic profile updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث بيانات العيادة بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "clinic_name", type: "string", example: "Updated Clinic Name"),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                new OA\Property(property: "consultation_fee", type: "number", example: 30.0),
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
    public function update()
    {
    }

    #[OA\Delete(
        path: "/api/clinics/profile",
        summary: "Delete clinic profile",
        description: "Deletes the authenticated clinic's profile",
        tags: ["Clinic Profile"],
        security: [["jwt" => ["clinic"]]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Clinic profile deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم حذف العيادة بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]
    public function destroy()
    {
    }
}
