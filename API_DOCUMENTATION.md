# ClinicHub Backend API Documentation

## Table of Contents
- [Governorates](#governorates)
- [Medical Specializations](#medical-specializations)
- [Secretaries](#secretaries)
- [Doctors](#doctors)
- [Patients](#patients)
- [Clinics](#clinics)
- [Medical Centers](#medical-centers)
- [Subscriptions](#subscriptions)
- [Admin Management](#admin-management)
- [Notifications](#notifications)
- [Invitations](#invitations)

## Governorates

### GET /api/governorates
**Description:** Retrieves a list of all governorates.

**Request Body:** None

**Response:**
```json
[
  {
    "id": 1,
    "name": "Governorate Name"
  },
  ...
]
```

### GET /api/governorates/{governorate}/districts
**Description:** Retrieves a list of districts (cities) for a specific governorate.

**Request Body:** None

**Response:**
```json
[
  {
    "id": 1,
    "governorate_id": 1,
    "name": "District Name"
  },
  ...
]
```

### POST /api/governorates/{governorate}/districts
**Description:** Adds a new district (city) to a specific governorate.

**Request Body:**
```json
{
  "name": "New District Name"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إضافة المنطقة بنجاح.",
  "data": {
    "id": 1,
    "governorate_id": 1,
    "name": "New District Name"
  }
}
```

## Medical Specializations

### GET /api/medical-specializations
**Description:** Retrieves a list of all medical specializations.

**Request Body:** None

**Response:**
```json
[
  {
    "id": 1,
    "name": "Specialization Name",
    "image_url": "URL to image",
    "is_active": true
  },
  ...
]
```

### POST /api/medical-specializations
**Description:** Creates a new medical specialization.

**Request Body:**
```json
{
  "name": "Specialization Name",
  "image": "file upload (optional)",
  "is_active": true (optional, defaults to true)
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إضافة التخصص الطبي بنجاح.",
  "data": {
    "id": 1,
    "name": "Specialization Name",
    "image_url": "URL to image",
    "is_active": true
  }
}
```

### PUT /api/medical-specializations/{medicalSpecialization}
**Description:** Updates an existing medical specialization.

**Request Body:**
```json
{
  "name": "Updated Specialization Name" (optional),
  "image": "file upload (optional)",
  "is_active": true (optional)
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث التخصص بنجاح.",
  "data": {
    "id": 1,
    "name": "Updated Specialization Name",
    "image_url": "URL to image",
    "is_active": true
  }
}
```

### DELETE /api/medical-specializations/{medicalSpecialization}
**Description:** Deletes a medical specialization.

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم حذف تخصص Specialization Name بنجاح."
}
```

## Secretaries

### POST /api/secretaries
**Description:** Creates a new secretary account.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "username": "johndoe",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "date_of_birth": "1990-01-01",
  "gender": "male",
  "profile_image": "file upload (optional)",
  "entity_id": 1,
  "entity_type": "clinic" (or "medical_center")
}
```

**Response:**
```json
{
  "success": true,
  "message": "secretary account has been created successfully",
  "data": {
    "user": {...},
    "secretary": {...}
  }
}
```

### POST /api/secretaries/login
**Description:** Logs in a secretary.

**Request Body:**
```json
{
  "identifier": "email, phone, or username",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "login successful",
  "token": "jwt_token",
  "token_type": "bearer",
  "expires_in": 3600,
  "user_type": "secretary",
  "user": {
    "id": 1,
    "full_name": "John Doe",
    "status": "pending/approved",
    "entity_type": "clinic"
  }
}
```

### POST /api/secretaries/updateAccounte
**Description:** Updates a secretary account.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "username": "johndoe",
  "password": "new_password",
  "password_confirmation": "new_password",
  "date_of_birth": "1990-01-01",
  "gender": "male",
  "profile_image": "file upload (optional)",
  "entity_id": 1,
  "entity_type": "clinic"
}
```

**Response:**
```json
{
  "success": true,
  "message": "secretary account has been updated successfully",
  "data": {
    "user": {...},
    "secretary": {...}
  }
}
```

### GET /api/secretaries/profile
**Description:** Retrieves the secretary's profile (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "entity_id": 1,
    "entity_type": "clinic",
    "status": "pending/approved",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "phone": "1234567890",
      "email": "john@example.com",
      "username": "johndoe",
      "date_of_birth": "1990-01-01",
      "gender": "male",
      "profile_photo_url": "URL to photo"
    }
  }
}
```

### PUT /api/secretaries/profile
**Description:** Updates the secretary's profile (requires authentication).

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "username": "johndoe",
  "date_of_birth": "1990-01-01",
  "gender": "male",
  "profile_image": "file upload (optional)",
  "entity_id": 1,
  "entity_type": "clinic"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Secretary profile updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "entity_id": 1,
    "entity_type": "clinic",
    "status": "pending/approved",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "phone": "1234567890",
      "email": "john@example.com",
      "username": "johndoe",
      "date_of_birth": "1990-01-01",
      "gender": "male",
      "profile_photo_url": "URL to photo"
    }
  }
}
```

## Doctors

### POST /api/doctor/login
**Description:** Logs in a doctor.

**Request Body:**
```json
{
  "identifier": "email or phone",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "jwt_token",
  "token_type": "bearer",
  "expires_in": 3600,
  "doctor": {
    "id": 1,
    "full_name": "Dr. John Doe",
    "is_approved": true
  }
}
```

### POST /api/doctor/register-request
**Description:** Submits a request to register a new doctor account.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "date_of_birth": "1980-01-01",
  "gender": "male",
  "username": "drjohndoe",
  "license_number": "123456",
  "practicing_profession_date": "2010-01-01",
  "governorate_id": 1,
  "bio": "Brief bio",
  "distinguished_specialities": ["Cardiology"],
  "facebook_link": "https://facebook.com/john",
  "instagram_link": "https://instagram.com/john",
  "specializations_ids": [1, 2],
  "certifications": [
    {
      "name": "Certificate Name",
      "image": "file upload"
    }
  ],
  "image": "file upload (optional)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم استلام طلب إنشاء الحساب بنجاح. سيتم مراجعته والموافقة عليه من قبل الإدارة.",
  "doctor_status": "pending/approved"
}
```

### POST /api/doctor/verify-phone
**Description:** Verifies a doctor's phone number using OTP.

**Request Body:**
```json
{
  "phone": "1234567890",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم التحقق من رقم الهاتف بنجاح."
}
```

### POST /api/doctor/resend-otp
**Description:** Resends OTP to a doctor's phone number.

**Request Body:**
```json
{
  "phone": "1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق الجديد إلى هاتفك."
}
```

### POST /api/doctor/logout
**Description:** Logs out a doctor (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "logged out successfully"
}
```

### PUT /api/doctors/
**Description:** Updates a doctor's profile (requires authentication).

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "username": "drjohndoe",
  "license_number": "123456",
  "practicing_profession_date": "2010-01-01",
  "governorate_id": 1,
  "district_id": 1,
  "bio": "Updated bio",
  "distinguished_specialities": ["Cardiology"],
  "facebook_link": "https://facebook.com/john",
  "instagram_link": "https://instagram.com/john",
  "consultation_price": 100,
  "image": "file upload (optional)",
  "specializations_ids": [1, 2],
  "certifications": [
    {
      "name": "Certificate Name",
      "image": "file upload"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث بيانات الطبيب بنجاح.",
  "data": {
    "id": 1,
    "full_name": "Dr. John Doe",
    "bio": "Updated bio",
    "practicing_profession_date": "2010-01-01",
    "instagram_link": "https://instagram.com/john"
  }
}
```

### GET /api/doctors/profile
**Description:** Retrieves the doctor's profile (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "username": "drjohndoe",
    "license_number": "123456",
    "practicing_profession_date": "2010-01-01",
    "governorate_id": 1,
    "district_id": 1,
    "bio": "Bio",
    "distinguished_specialities": ["Cardiology"],
    "facebook_link": "https://facebook.com/john",
    "instagram_link": "https://instagram.com/john",
    "consultation_price": 100,
    "image": "URL to image",
    "status": "approved",
    "phone_verified": true,
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "phone": "1234567890",
      "email": "john@example.com",
      "username": "drjohndoe",
      "birth_date": "1980-01-01",
      "gender": "male",
      "profile_photo_url": "URL to photo",
      "phone_verified_at": "2023-01-01T00:00:00Z"
    },
    "specializations": [...]
  }
}
```

## Patients

### POST /api/auth/login
**Description:** Traditional login for patients.

**Request Body:**
```json
{
  "phone": "1234567890",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "jwt_token",
  "role": "Patient",
  "data": {
    "patient_id": 1,
    "full_name": "John Doe",
    "phone": "1234567890"
  }
}
```

### POST /api/auth/send-otp
**Description:** Sends OTP to patient's phone number.

**Request Body:**
```json
{
  "phone": "1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق بنجاح."
}
```

### POST /api/auth/verify-otp
**Description:** Verifies OTP sent to patient's phone number.

**Request Body:**
```json
{
  "phone": "1234567890",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "is_registered": false,
  "message": "تم التحقق من الرقم، يرجى إكمال إنشاء حسابك.",
  "token": "jwt_token",
  "role": "Patient",
  "data": {
    "patient_id": null,
    "first_name": null,
    "last_name": null,
    "phone": "1234567890"
  }
}
```

### POST /api/auth/resend-otp
**Description:** Resends OTP to patient's phone number.

**Request Body:**
```json
{
  "phone": "1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق الجديد بنجاح."
}
```

### POST /api/auth/register
**Description:** Completes patient registration after OTP verification.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "1234567890",
  "governorate_id": 1,
  "district_id": 1,
  "gender": "male",
  "date_of_birth": "1990-01-01",
  "image": "file upload (optional)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إنشاء الحساب بنجاح.",
  "token": "jwt_token",
  "role": "Patient",
  "data": {
    "patient_id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "phone": "1234567890"
  }
}
```

### PUT /api/patients/{id}
**Description:** Updates patient information (requires authentication).

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "governorate_id": 1,
  "district_id": 1,
  "gender": "male",
  "date_of_birth": "1990-01-01",
  "image": "file upload (optional)",
  "blood_type": "O+",
  "has_chronic_diseases": false,
  "chronic_diseases_details": "..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث البيانات بنجاح.",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "phone": "1234567890"
  }
}
```

## Clinics

### POST /api/clinic/login
**Description:** Logs in a clinic.

**Request Body:**
```json
{
  "identifyer": "username, email, or phone",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "jwt_token",
  "role": "Clinic",
  "clinic": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "facebook_link": "https://facebook.com/clinic",
    "instagram_link": "https://instagram.com/clinic",
    "website_link": "https://clinic.com"
  }
}
```

### POST /api/clinic/logout
**Description:** Logs out a clinic (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح."
}
```

### POST /api/clinic/send-otp
**Description:** Sends OTP to clinic's phone number.

**Request Body:**
```json
{
  "phone": "1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق بنجاح",
  "data": {
    "phone": "1234567890"
  }
}
```

### POST /api/clinic/verify-otp
**Description:** Verifies OTP sent to clinic's phone number.

**Request Body:**
```json
{
  "phone": "1234567890",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم التحقق من الرقم بنجاح",
  "data": {
    "phone": "1234567890"
  }
}
```

### POST /api/clinics
**Description:** Registers a new clinic.

**Request Body:**
```json
{
  "clinic_name": "Clinic Name",
  "phone": "1234567890",
  "email": "clinic@example.com",
  "specialization_id": 1,
  "governorate_id": 1,
  "city_id": 1,
  "district_id": 1,
  "address": "Clinic Address",
  "detailed_address": "Detailed Address",
  "floor": 1,
  "room_number": 101,
  "consultation_fee": 100,
  "description": "Clinic Description",
  "username": "clinicuser",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "latitude": 12.345,
  "longitude": 67.890,
  "main_image": "file upload (optional)",
  "working_hours": "{\"sunday\":{\"open\":\"09:00\",\"close\":\"17:00\"}}",
  "services": [
    {
      "name": "Service Name",
      "price": 50
    }
  ],
  "gallery_images": ["file uploads (optional)"]
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم استلام طلب تسجيل العيادة بنجاح. في انتظار الموافقة من قبل الإدارة.",
  "data": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "phone": "1234567890",
    "consultation_fee": 100,
    "subscription_plan_id": 1,
    "services": [
      {
        "name": "Service Name",
        "price": 50
      }
    ]
  }
}
```

### GET /api/clinics/profile
**Description:** Retrieves clinic profile (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "phone": "1234567890",
    "email": "clinic@example.com",
    "specialization_id": 1,
    "governorate_id": 1,
    "city_id": 1,
    "district_id": 1,
    "address": "Clinic Address",
    "detailed_address": "Detailed Address",
    "floor": 1,
    "room_number": 101,
    "consultation_fee": 100,
    "description": "Clinic Description",
    "username": "clinicuser",
    "latitude": 12.345,
    "longitude": 67.890,
    "main_image": "URL to image",
    "working_hours": {...},
    "status": "pending/approved",
    "specialization": {...},
    "governorate": {...},
    "district": {...},
    "services": [...],
    "galleryImages": [...]
  }
}
```

### PUT /api/clinics/profile
**Description:** Updates clinic profile (requires authentication).

**Request Body:**
```json
{
  "clinic_name": "Updated Clinic Name",
  "phone": "1234567890",
  "specialization_id": 1,
  "governorate_id": 1,
  "city_id": 1,
  "district_id": 1,
  "detailed_address": "Updated Detailed Address",
  "consultation_fee": 120,
  "description": "Updated Description",
  "username": "updateduser",
  "latitude": 12.345,
  "longitude": 67.890,
  "main_image": "file upload (optional)",
  "working_hours": "{\"monday\":{\"open\":\"08:00\",\"close\":\"18:00\"}}",
  "services": [
    {
      "name": "Updated Service Name",
      "price": 60
    }
  ],
  "gallery_images": ["file uploads (optional)"]
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث بيانات العيادة بنجاح.",
  "data": {
    "id": 1,
    "clinic_name": "Updated Clinic Name",
    "phone": "1234567890",
    "consultation_fee": 120
  }
}
```

### DELETE /api/clinics/profile
**Description:** Deletes clinic profile (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم حذف العيادة بنجاح."
}
```

### GET /api/clinics
**Description:** Lists all clinics (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "links": {...},
    "meta": {...}
  }
}
```

### GET /api/clinics/{id}
**Description:** Shows a specific clinic by ID.

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "phone": "1234567890",
    "email": "clinic@example.com",
    "specialization_id": 1,
    "governorate_id": 1,
    "city_id": 1,
    "district_id": 1,
    "address": "Clinic Address",
    "detailed_address": "Detailed Address",
    "floor": 1,
    "room_number": 101,
    "consultation_fee": 100,
    "description": "Clinic Description",
    "username": "clinicuser",
    "latitude": 12.345,
    "longitude": 67.890,
    "main_image": "URL to image",
    "working_hours": {...},
    "status": "pending/approved",
    "specialization": {...},
    "governorate": {...},
    "district": {...},
    "services": [...],
    "galleryImages": [...]
  }
}
```

### PATCH /api/clinics/{id}/activate
**Description:** Activates a clinic (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Clinic activated successfully",
  "data": {
    "id": 1,
    "status": "approved"
  }
}
```

### PATCH /api/clinics/{id}/deactivate
**Description:** Deactivates a clinic (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Clinic deactivated successfully",
  "data": {
    "id": 1,
    "status": "inactive"
  }
}
```

### GET /api/clinics/doctors
**Description:** Gets doctors associated with the clinic (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "Dr. John Doe",
      "username": "drjohndoe",
      "is_primary": false,
      "user": {...},
      "specializations": [...]
    }
  ]
}
```

### POST /api/clinics/doctors
**Description:** Adds a doctor to the clinic (requires authentication).

**Request Body:**
```json
{
  "doctor_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Doctor added to clinic successfully",
  "data": {
    "clinic_id": 1,
    "doctor_id": 1
  }
}
```

### DELETE /api/clinics/doctors/{doctorId}
**Description:** Removes a doctor from the clinic (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Doctor removed from clinic successfully"
}
```

### PUT /api/clinics/doctors/{doctorId}/primary
**Description:** Sets a doctor as primary doctor for the clinic (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Primary doctor set successfully",
  "data": {
    "clinic_id": 1,
    "doctor_id": 1
  }
}
```

## Medical Centers

### POST /api/center/login
**Description:** Logs in a medical center.

**Request Body:**
```json
{
  "username": "centeruser",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "jwt_token",
  "role": "Medical_Center",
  "center": {
    "id": 1,
    "center_name": "Medical Center Name",
    "is_approved": true
  }
}
```

### POST /api/center/register-request
**Description:** Requests to register a new medical center.

**Request Body:**
```json
{
  "center_name": "Medical Center Name",
  "phone": "1234567890",
  "governorate_id": 1,
  "district_id": 1,
  "city_id": 1,
  "clinic_count": 5,
  "username": "centeruser",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "detailed_address": "Detailed Address",
  "latitude": 12.345,
  "longitude": 67.890,
  "image": "file upload (optional)",
  "gallery_images": ["file uploads (optional)"],
  "working_hours": "{\"sunday\":{\"open\":\"09:00\",\"close\":\"17:00\"}}",
  "services": [
    {
      "name": "Service Name",
      "price": 50
    }
  ],
  "facebook_link": "https://facebook.com/center",
  "instagram_link": "https://instagram.com/center",
  "website_link": "https://center.com",
  "plan_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم استلام طلب تسجيل المركز الطبي بنجاح. سيتم مراجعة البيانات والرد عليك عبر الواتساب.",
  "plan_id": 1
}
```

### PUT /api/centers/{id}
**Description:** Updates medical center information (requires authentication).

**Request Body:**
```json
{
  "center_name": "Updated Medical Center Name",
  "username": "updateduser",
  "clinic_count": 6,
  "latitude": 12.345,
  "longitude": 67.890,
  "detailed_address": "Updated Detailed Address",
  "image": "file upload (optional)",
  "gallery_images": ["file uploads (optional)"],
  "working_hours": "{\"monday\":{\"open\":\"08:00\",\"close\":\"18:00\"}}",
  "services": [
    {
      "name": "Updated Service Name",
      "price": 60
    }
  ],
  "facebook_link": "https://facebook.com/updated-center",
  "instagram_link": "https://instagram.com/updated-center",
  "website_link": "https://updated-center.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث بيانات المركز الطبي بنجاح.",
  "data": {
    "id": 1,
    "center_name": "Updated Medical Center Name",
    "username": "updateduser",
    "clinic_count": 6,
    "latitude": 12.345,
    "longitude": 67.890,
    "phone": "1234567890",
    "status": "pending/approved"
  }
}
```

### DELETE /api/centers/{id}
**Description:** Deletes a medical center (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم حذف المركز الطبي بنجاح من النظام."
}
```

## Subscriptions

### GET /api/subscription-plans
**Description:** Retrieves a list of subscription plans.

**Request Query Parameters:**
- `target_type` (optional): Filter by target type (clinic or medical_center)

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Plan Name",
      "target_type": "clinic",
      "price": 100,
      "duration_days": 30,
      "description": "Plan Description",
      "is_active": true,
      "features": [...],
      "entitlements": [...]
    }
  ]
}
```

### GET /api/subscription-plans/{id}
**Description:** Retrieves a specific subscription plan.

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Plan Name",
    "target_type": "clinic",
    "price": 100,
    "duration_days": 30,
    "description": "Plan Description",
    "is_active": true,
    "features": [...],
    "entitlements": [...]
  }
}
```

### GET /api/my-subscription
**Description:** Retrieves the current user's subscription (requires authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": {
    "subscription": {
      "id": 1,
      "plan": {...},
      "starts_at": "2023-01-01",
      "ends_at": "2023-02-01",
      "status": "active",
      "is_active": true,
      "days_remaining": 15
    }
  }
}
```

### POST /api/admin/subscription-plans
**Description:** Creates a new subscription plan (requires admin authentication).

**Request Body:**
```json
{
  "name": "Plan Name",
  "target_type": "clinic",
  "price": 100,
  "duration_days": 30,
  "description": "Plan Description",
  "features": ["Feature 1", "Feature 2"],
  "entitlements": [
    {
      "key": "feature_key",
      "value": "feature_value",
      "type": "string"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إنشاء الخطة بنجاح",
  "data": {
    "id": 1,
    "name": "Plan Name",
    "target_type": "clinic",
    "price": 100,
    "duration_days": 30,
    "description": "Plan Description",
    "is_active": true,
    "features": [...],
    "entitlements": [...]
  }
}
```

### PUT /api/admin/subscription-plans/{id}
**Description:** Updates a subscription plan (requires admin authentication).

**Request Body:**
```json
{
  "name": "Updated Plan Name",
  "target_type": "medical_center",
  "price": 200,
  "duration_days": 60,
  "description": "Updated Plan Description",
  "is_active": false,
  "features": [
    {
      "id": 1,
      "text": "Updated Feature 1"
    },
    {
      "text": "New Feature 2"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث الخطة بنجاح",
  "data": {
    "id": 1,
    "name": "Updated Plan Name",
    "target_type": "medical_center",
    "price": 200,
    "duration_days": 60,
    "description": "Updated Plan Description",
    "is_active": false,
    "features": [...]
  }
}
```

### DELETE /api/admin/subscription-plans/{id}
**Description:** Deletes a subscription plan (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم حذف الخطة وجميع الميزات المرتبطة بها بنجاح"
}
```

### POST /api/admin/subscriptions
**Description:** Assigns a subscription to a clinic or medical center (requires admin authentication).

**Request Body:**
```json
{
  "subscribable_type": "clinic",
  "subscribable_id": 1,
  "plan_id": 1,
  "starts_at": "2023-01-01",
  "status": "active",
  "notes": "Subscription notes"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تعيين الاشتراك بنجاح",
  "data": {
    "id": 1,
    "subscription_plan_id": 1,
    "subscribable_type": "App\\Models\\Clinic",
    "subscribable_id": 1,
    "starts_at": "2023-01-01T00:00:00.000000Z",
    "ends_at": "2023-02-01T00:00:00.000000Z",
    "status": "active",
    "notes": "Subscription notes",
    "plan": {...}
  }
}
```

### PATCH /api/admin/subscriptions/{id}
**Description:** Updates a subscription status (requires admin authentication).

**Request Body:**
```json
{
  "status": "canceled",
  "notes": "Cancellation notes"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث الاشتراك بنجاح",
  "data": {
    "id": 1,
    "subscription_plan_id": 1,
    "subscribable_type": "App\\Models\\Clinic",
    "subscribable_id": 1,
    "starts_at": "2023-01-01T00:00:00.000000Z",
    "ends_at": "2023-02-01T00:00:00.000000Z",
    "status": "canceled",
    "notes": "Cancellation notes",
    "plan": {...}
  }
}
```

### GET /api/admin/subscriptions
**Description:** Retrieves all subscriptions (requires admin authentication).

**Request Query Parameters:**
- `status` (optional): Filter by status
- `subscribable_type` (optional): Filter by subscribable type

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "subscription_plan_id": 1,
      "subscribable_type": "App\\Models\\Clinic",
      "subscribable_id": 1,
      "starts_at": "2023-01-01T00:00:00.000000Z",
      "ends_at": "2023-02-01T00:00:00.000000Z",
      "status": "active",
      "notes": "Notes",
      "plan": {...},
      "subscribable": {...}
    }
  ]
}
```

## Admin Management

### POST /api/admin/login
**Description:** Logs in an admin user.

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "jwt_token",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### GET /api/admin/doctors/pending
**Description:** Retrieves all pending doctors (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "username": "drjohndoe",
      "license_number": "123456",
      "practicing_profession_date": "2010-01-01",
      "governorate_id": 1,
      "district_id": 1,
      "bio": "Bio",
      "distinguished_specialities": ["Cardiology"],
      "facebook_link": "https://facebook.com/john",
      "instagram_link": "https://instagram.com/john",
      "consultation_price": 100,
      "image": "URL to image",
      "status": "pending",
      "phone_verified": true,
      "user": {...},
      "specializations": [...],
      "governorate": {...},
      "city": {...},
      "district": {...}
    }
  ]
}
```

### PUT /api/admin/doctors/{id}/approve
**Description:** Approves a doctor registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم قبول تسجيل الدكتور بنجاح",
  "data": {
    "id": 1,
    "full_name": "Dr. John Doe",
    "status": "approved"
  }
}
```

### PUT /api/admin/doctors/{id}/reject
**Description:** Rejects a doctor registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم رفض تسجيل الدكتور بنجاح",
  "data": {
    "id": 1,
    "full_name": "Dr. John Doe",
    "status": "rejected"
  }
}
```

### GET /api/admin/clinics/pending
**Description:** Retrieves all pending clinics (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "clinic_name": "Clinic Name",
      "phone": "1234567890",
      "email": "clinic@example.com",
      "specialization_id": 1,
      "governorate_id": 1,
      "city_id": 1,
      "district_id": 1,
      "address": "Clinic Address",
      "detailed_address": "Detailed Address",
      "floor": 1,
      "room_number": 101,
      "consultation_fee": 100,
      "description": "Clinic Description",
      "username": "clinicuser",
      "latitude": 12.345,
      "longitude": 67.890,
      "main_image": "URL to image",
      "working_hours": {...},
      "status": "pending",
      "specialization": {...},
      "governorate": {...},
      "city": {...},
      "district": {...}
    }
  ]
}
```

### PUT /api/admin/clinics/{id}/approve
**Description:** Approves a clinic registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم قبول تسجيل العيادة بنجاح",
  "data": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "status": "approved"
  }
}
```

### PUT /api/admin/clinics/{id}/reject
**Description:** Rejects a clinic registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم رفض تسجيل العيادة بنجاح",
  "data": {
    "id": 1,
    "clinic_name": "Clinic Name",
    "status": "rejected"
  }
}
```

### GET /api/admin/secretaries/pending
**Description:** Retrieves all pending secretaries (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "entity_id": 1,
      "entity_type": "clinic",
      "status": "pending",
      "user": {...},
      "entity": {...}
    }
  ]
}
```

### PUT /api/admin/secretaries/{id}/approve
**Description:** Approves a secretary registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم قبول تسجيل السكرتيرة بنجاح",
  "data": {
    "id": 1,
    "username": "secretaryuser",
    "status": "approved",
    "entity_type": "clinic",
    "entity_name": "Entity Name"
  }
}
```

### PUT /api/admin/secretaries/{id}/reject
**Description:** Rejects a secretary registration (requires admin authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "تم رفض تسجيل السكرتيرة بنجاح",
  "data": {
    "id": 1,
    "username": "secretaryuser",
    "status": "rejected"
  }
}
```

## Notifications

### POST /api/notification/send-notification
**Description:** Sends a notification to a user.

**Request Body:**
```json
{
  "user_id": 1,
  "title": "Notification Title",
  "body": "Notification Body"
}
```

**Response:**
```json
{
  "message": "Notification sent successfully"
}
```

## Invitations

### GET /api/available-doctors
**Description:** Searches and lists available doctors.

**Request Query Parameters:**
- `query` (optional): Search query to filter doctors

**Request Body:** None

**Response:**
```json
{
  "status": "success",
  "total_count": 10,
  "data": [
    {
      "id": 1,
      "full_name": "Dr. John Doe",
      "email": "john@example.com",
      "phone": "1234567890",
      "specialization": "Cardiology",
      "profile_image": "URL to image",
      "governorate": {
        "id": 1,
        "name": "Governorate Name"
      },
      "city": {
        "id": 1,
        "name": "City Name"
      },
      "years_of_experience": 10
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### POST /api/clinics/invitations/send
**Description:** Sends a job invitation to a doctor (requires clinic authentication).

**Request Body:**
```json
{
  "doctor_id": 1,
  "clinic_id": 1,
  "message": "Invitation message (optional)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إرسال الدعوة بنجاح",
  "data": {
    "invitation_id": 1,
    "status": "pending"
  }
}
```

### PATCH /api/doctor/invitations/{invitation_id}
**Description:** Responds to a job invitation (requires doctor authentication).

**Request Body:**
```json
{
  "status": "accepted" (or "rejected")
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تحديث حالة الدعوة بنجاح",
  "data": {
    "invitation_id": 1,
    "status": "accepted"
  }
}
```

### GET /api/clinics/invitations
**Description:** Gets invitations sent by the authenticated clinic (requires clinic authentication).

**Request Query Parameters:**
- `status` (optional): Filter by invitation status

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "doctor_id": 1,
      "clinic_id": 1,
      "message": "Invitation message",
      "status": "pending",
      "responded_at": null,
      "created_at": "2023-01-01T00:00:00Z",
      "updated_at": "2023-01-01T00:00:00Z",
      "doctor": {...},
      "clinic": {...}
    }
  ]
}
```

### PATCH /api/clinics/invitations/{invitation_id}/cancel
**Description:** Cancels an invitation (requires clinic authentication).

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Invitation cancelled successfully",
  "data": {
    "invitation_id": 1,
    "status": "cancelled"
  }
}
```