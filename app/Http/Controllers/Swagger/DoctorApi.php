<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

class DoctorApi
{
    #[OA\Post(
        path: "/api/doctor/login",
        summary: "Doctor login",
        description: "Authenticates a doctor and returns a JWT token",
        tags: ["Doctor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "identifier", type: "string", example: "doctor@example.com", description: "Email or phone number"),
                        new OA\Property(property: "password", type: "string", example: "password123", format: "password"),
                    ],
                    required: ["identifier", "password"]
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
                        new OA\Property(property: "token_type", type: "string", example: "bearer"),
                        new OA\Property(property: "expires_in", type: "integer", example: 3600),
                        new OA\Property(
                            property: "doctor",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "is_approved", type: "boolean", example: true),
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
                description: "Account not approved or phone not verified"
            ),
            new OA\Response(
                response: 404,
                description: "No doctor account associated with user"
            )
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: "/api/doctor/register-request",
        summary: "Doctor registration request",
        description: "Submits a request to register a new doctor account",
        tags: ["Doctor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "first_name", type: "string", example: "John", description: "الاسم الأول"),
                        new OA\Property(property: "last_name", type: "string", example: "Doe", description: "الاسم الأخير"),
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "رقم الهاتف"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", description: "البريد الإلكتروني"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "كلمة المرور"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "تأكيد كلمة المرور"),
                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1985-01-01", description: "تاريخ الميلاد بصيغة YYYY-MM-DD"),
                        new OA\Property(property: "gender", type: "string", enum: ["male", "female"], example: "male", description: "الجنس (male أو female)"),
                        new OA\Property(property: "governorate_id", type: "integer", example: 1, description: "معرّف المحافظة (ID يتم جلبه من API المحافظات)"),
                        new OA\Property(
                            property: "specializations_ids[0]",
                            type: "integer",
                            example: 1,
                            description: "معرّف التخصص الأول - يمكن إضافة المزيد باستخدام specializations_ids[1], specializations_ids[2], إلخ"
                        ),
                        new OA\Property(
                            property: "specializations_ids[1]",
                            type: "integer",
                            example: 2,
                            description: "معرّف التخصص الثاني (اختياري)"
                        ),
                        new OA\Property(property: "practicing_profession_date", type: "integer", example: 2010, description: "عدد سنوات الخبرة العملية (قيمة عددية صحيحة للسنة التي بدأ فيها الطبيب بمزاولة المهنة)"),
                        new OA\Property(property: "bio", type: "string", example: "Experienced cardiologist with 10 years of practice", description: "نبذة تعريفية عن الطبيب"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "الصورة الشخصية (يتم إرسال ملف الصورة)"),
                        new OA\Property(property: "distinguished_specialties", type: "string", example: "Heart Surgery", description: "التخصصات الفرعية أو المميزة (نص String)"),
                        new OA\Property(
                            property: "certifications[0][name]",
                            type: "string",
                            example: "MD Degree",
                            description: "اسم الشهادة الأولى - استخدم certifications[0][name], certifications[0][image], certifications[1][name], certifications[1][image], إلخ"
                        ),
                        new OA\Property(
                            property: "certifications[0][image]",
                            type: "string",
                            format: "binary",
                            description: "صورة الشهادة الأولى"
                        ),
                        new OA\Property(
                            property: "certifications[1][name]",
                            type: "string",
                            example: "Board Certification",
                            description: "اسم الشهادة الثانية (اختياري)"
                        ),
                        new OA\Property(
                            property: "certifications[1][image]",
                            type: "string",
                            format: "binary",
                            description: "صورة الشهادة الثانية (اختياري)"
                        ),
                        new OA\Property(property: "facebook_link", type: "string", format: "uri", example: "https://facebook.com/doctor", description: "رابط صفحة فيسبوك"),
                        new OA\Property(property: "instagram_link", type: "string", format: "uri", example: "https://instagram.com/doctor", description: "رابط حساب إنستقرام"),
                    ],
                    required: ["first_name", "last_name", "phone", "email", "password", "password_confirmation", "date_of_birth", "gender", "governorate_id", "specializations_ids[0]", "practicing_profession_date", "bio"]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: "Registration request submitted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم استلام طلب إنشاء الحساب بنجاح. سيتم مراجعته والموافقة عليه من قبل الإدارة."),
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
                                    property: "email",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The email has already been taken.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function registerRequest()
    {
    }

    #[OA\Post(
        path: "/api/doctor/verify-phone",
        summary: "Verify doctor's phone number",
        description: "Verifies a doctor's phone number using OTP",
        tags: ["Doctor Authentication"],
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
                description: "Phone verified successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم التحقق من رقم الهاتف بنجاح."),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid OTP"
            )
        ]
    )]
    public function verifyPhone()
    {
    }

    #[OA\Post(
        path: "/api/doctor/resend-otp",
        summary: "Resend OTP to doctor's phone",
        description: "Sends a new OTP code to the doctor's phone number",
        tags: ["Doctor Authentication"],
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
                description: "OTP resent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم إرسال رمز التحقق الجديد إلى هاتفك."),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to send OTP"
            )
        ]
    )]
    public function resendOtp()
    {
    }

    #[OA\Post(
        path: "/api/doctor/logout",
        summary: "Doctor logout",
        description: "Logs out the authenticated doctor",
        tags: ["Doctor Authentication"],
        security: [["jwt" => []]],
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
                response: 401,
                description: "Unauthorized"
            )
        ]
    )]
    public function logout()
    {
    }

    #[OA\Put(  // Changed from Put to Post
        path: "/api/doctors",
        summary: "Update doctor profile",
        description: "Updates the authenticated doctor's profile information. Only send the fields you want to update - leave other fields empty. This endpoint uses POST with _method=PUT for multipart/form-data support.",
        tags: ["Doctor Profile"],
        security: [["jwt" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        // new OA\Property(
                        //     property: "_method",
                        //     type: "string",
                        //     example: "PUT",
                        //     description: "HTTP method override for Laravel (required for multipart/form-data PUT requests)"
                        // ),
                        new OA\Property(property: "first_name", type: "string", example: "John", description: "الاسم الأول (اختياري)"),
                        new OA\Property(property: "last_name", type: "string", example: "Doe", description: "الاسم الأخير (اختياري)"),
                        new OA\Property(property: "phone", type: "string", example: "07771234567", description: "رقم الهاتف (اختياري)"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", description: "البريد الإلكتروني (اختياري)"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "كلمة المرور الجديدة (اختياري)"),
                        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123", description: "تأكيد كلمة المرور (مطلوب فقط عند تغيير كلمة المرور)"),
                        new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "1985-01-01", description: "تاريخ الميلاد بصيغة YYYY-MM-DD (اختياري)"),
                        new OA\Property(property: "gender", type: "string", enum: ["male", "female"], example: "male", description: "الجنس (اختياري)"),
                        new OA\Property(property: "governorate_id", type: "integer", example: 1, description: "معرّف المحافظة (اختياري)"),
                        new OA\Property(
                            property: "specializations_ids[0]",
                            type: "integer",
                            example: 1,
                            description: "معرّف التخصص الأول (اختياري) - يمكن إضافة المزيد"
                        ),
                        new OA\Property(
                            property: "specializations_ids[1]",
                            type: "integer",
                            example: 2,
                            description: "معرّف التخصص الثاني (اختياري)"
                        ),
                        new OA\Property(property: "practicing_profession_date", type: "integer", example: 2010, description: "سنة بداية ممارسة المهنة (اختياري)"),
                        new OA\Property(property: "bio", type: "string", example: "Experienced cardiologist with 10 years of practice", description: "نبذة تعريفية (اختياري)"),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "الصورة الشخصية (اختياري)"),
                        new OA\Property(property: "distinguished_specialties", type: "string", example: "Heart Surgery", description: "التخصصات المميزة (اختياري)"),
                        new OA\Property(
                            property: "certifications[0][name]",
                            type: "string",
                            example: "MD Degree",
                            description: "اسم الشهادة الأولى (اختياري)"
                        ),
                        new OA\Property(
                            property: "certifications[0][image]",
                            type: "string",
                            format: "binary",
                            description: "صورة الشهادة الأولى (اختياري)"
                        ),
                        new OA\Property(
                            property: "certifications[1][name]",
                            type: "string",
                            example: "Board Certification",
                            description: "اسم الشهادة الثانية (اختياري)"
                        ),
                        new OA\Property(
                            property: "certifications[1][image]",
                            type: "string",
                            format: "binary",
                            description: "صورة الشهادة الثانية (اختياري)"
                        ),
                        new OA\Property(property: "facebook_link", type: "string", format: "uri", example: "https://facebook.com/doctor", description: "رابط فيسبوك (اختياري)"),
                        new OA\Property(property: "instagram_link", type: "string", format: "uri", example: "https://instagram.com/doctor", description: "رابط إنستقرام (اختياري)"),
                    ],
                    required: ["_method"]  // Only _method is required
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profile updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "تم تحديث بيانات الطبيب بنجاح."),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "bio", type: "string", example: "Experienced cardiologist"),
                                new OA\Property(property: "practicing_profession_date", type: "string", format: "date", example: "2020-01-01"),
                                new OA\Property(property: "instagram_link", type: "string", example: "https://instagram.com/doctor"),
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
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "email",
                                    type: "array",
                                    items: new OA\Items(type: "string", example: "The email has already been taken.")
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function update()
    {
    }

    #[OA\Get(
        path: "/api/doctors/profile",
        summary: "Get doctor profile",
        description: "Returns the authenticated doctor's profile information",
        tags: ["Doctor Profile"],
        security: [["jwt" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Doctor profile retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "user_id", type: "integer", example: 1),
                                new OA\Property(property: "full_name", type: "string", example: "Dr. John Doe"),
                                new OA\Property(property: "bio", type: "string", example: "Experienced cardiologist"),
                                new OA\Property(property: "practicing_profession_date", type: "string", format: "date", example: "2020-01-01"),
                                new OA\Property(property: "facebook_link", type: "string", example: "https://facebook.com/doctor"),
                                new OA\Property(property: "instagram_link", type: "string", example: "https://instagram.com/doctor"),
                                new OA\Property(property: "consultation_price", type: "number", example: 50.0),
                                new OA\Property(property: "image", type: "string", example: "http://example.com/storage/images/doctors/image.jpg"),
                                new OA\Property(property: "status", type: "string", example: "approved"),
                                new OA\Property(property: "phone_verified", type: "boolean", example: true),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                new OA\Property(
                                    property: "user",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "first_name", type: "string", example: "John"),
                                        new OA\Property(property: "last_name", type: "string", example: "Doe"),
                                        new OA\Property(property: "phone", type: "string", example: "07771234567"),
                                        new OA\Property(property: "email", type: "string", example: "john.doe@example.com"),
                                        new OA\Property(property: "phone_verified_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00.000000Z"),
                                    ]
                                ),
                                new OA\Property(
                                    property: "specializations",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Cardiology"),
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
            ),
            new OA\Response(
                response: 404,
                description: "No doctor account associated with user"
            )
        ]
    )]
    public function profile()
    {
    }

}
