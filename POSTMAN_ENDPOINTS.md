# 📋 ClinicHub API - Complete Postman Endpoints

## ⚙️ Global Setup

### Base URL
```
http://localhost:8000
```

### Common Headers (for authenticated requests)
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

### Environment Variables (Postman)
```
base_url = http://localhost:8000
admin_token = (get from admin login response)
user_token = (get from doctor/secretary login response)
clinic_token = (get from clinic login response)
center_token = (get from medical center login response)
patient_token = (get from patient login response)
```

---

## 🔐 Table of Contents

1. [Admin Authentication](#1-admin-authentication)
2. [Admin Management](#2-admin-management)
3. [Subscription Plans](#3-subscription-plans)
4. [Subscriptions](#4-subscriptions)
5. [Clinic Authentication](#5-clinic-authentication)
6. [Clinic Management](#6-clinic-management)
7. [Doctor Authentication](#7-doctor-authentication)
8. [Doctor Management](#8-doctor-management)
9. [Medical Center Authentication](#9-medical-center-authentication)
10. [Medical Center Management](#10-medical-center-management)
11. [Medical Team Login](#11-medical-team-login)
12. [Secretary Management](#12-secretary-management)
13. [Patient Authentication](#13-patient-authentication)
14. [Patient Management](#14-patient-management)
15. [Appointments](#15-appointments)
16. [Invitations](#16-invitations)
17. [Public Endpoints](#17-public-endpoints)
18. [Notifications](#18-notifications)

---

## 1. Admin Authentication

### 1.1 Admin Login
```http
POST {{base_url}}/api/admin/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "admin@clinichub.com",
  "password": "Admin@12345"
}
```

**Response:** Save `token` to `admin_token` variable

---

## 2. Admin Management

### 2.1 Get Pending Doctors
```http
GET {{base_url}}/api/admin/doctors/pending
Authorization: Bearer {{admin_token}}
```

### 2.2 Approve Doctor
```http
PUT {{base_url}}/api/admin/doctors/{id}/approve
Authorization: Bearer {{admin_token}}
```

### 2.3 Reject Doctor
```http
PUT {{base_url}}/api/admin/doctors/{id}/reject
Authorization: Bearer {{admin_token}}
```

### 2.4 Get Pending Clinics
```http
GET {{base_url}}/api/admin/clinics/pending
Authorization: Bearer {{admin_token}}
```

### 2.5 Approve Clinic
```http
PUT {{base_url}}/api/admin/clinics/{id}/approve
Authorization: Bearer {{admin_token}}
```

### 2.6 Reject Clinic
```http
PUT {{base_url}}/api/admin/clinics/{id}/reject
Authorization: Bearer {{admin_token}}
```

### 2.7 Get Pending Secretaries
```http
GET {{base_url}}/api/admin/secretaries/pending
Authorization: Bearer {{admin_token}}
```

### 2.8 Approve Secretary
```http
PUT {{base_url}}/api/admin/secretaries/{id}/approve
Authorization: Bearer {{admin_token}}
```

### 2.9 Reject Secretary
```http
PUT {{base_url}}/api/admin/secretaries/{id}/reject
Authorization: Bearer {{admin_token}}
```

---

## 3. Subscription Plans

### 3.1 Get All Plans (Public)
```http
GET {{base_url}}/api/subscription-plans
```

### 3.2 Filter by Target Type
```http
GET {{base_url}}/api/subscription-plans?target_type=clinic
```

### 3.3 Get Specific Plan
```http
GET {{base_url}}/api/subscription-plans/{id}
```

### 3.4 Create Plan (Admin Only)
```http
POST {{base_url}}/api/admin/subscription-plans
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "الخطة الفضية",
  "target_type": "clinic",
  "price": 5000,
  "duration_days": 30,
  "description": "وصف الخطة",
  "features": [
    "50 مريض شهرياً",
    "دعم فني"
  ],
  "entitlements": [
    {
      "key": "max_patients_per_month",
      "value": "50",
      "type": "integer"
    }
  ]
}
```

### 3.5 Update Plan (Admin Only)
```http
PUT {{base_url}}/api/admin/subscription-plans/{id}
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "الخطة الذهبية (محدثة)",
  "price": 12000
}
```

### 3.6 Delete Plan (Admin Only)
```http
DELETE {{base_url}}/api/admin/subscription-plans/{id}
Authorization: Bearer {{admin_token}}
```

---

## 4. Subscriptions

### 4.1 Get All Subscriptions (Admin Only)
```http
GET {{base_url}}/api/admin/subscriptions
Authorization: Bearer {{admin_token}}
```

### 4.2 Filter by Status
```http
GET {{base_url}}/api/admin/subscriptions?status=active
Authorization: Bearer {{admin_token}}
```

### 4.3 Create Subscription (Admin Only)
```http
POST {{base_url}}/api/admin/subscriptions
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "subscribable_type": "clinic",
  "subscribable_id": 2,
  "plan_id": 1,
  "starts_at": "2026-01-08",
  "status": "trial",
  "notes": "ملاحظات اختيارية"
}
```

**Test Data:**
- Clinic IDs: 2, 3, 4
- Medical Center IDs: 1, 2

### 4.4 Update Subscription (Admin Only)
```http
PATCH {{base_url}}/api/admin/subscriptions/{id}
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "active"
}
```

### 4.5 Get My Subscription (Protected)
```http
GET {{base_url}}/api/my-subscription
Authorization: Bearer {{user_token}}
```

---

## 5. Clinic Authentication

### 5.1 Clinic Login
```http
POST {{base_url}}/api/clinic/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "identifyer": "clinic_shifa",
  "password": "password123"
}
```

**Response:** Save `token` to `clinic_token` variable

### 5.2 Clinic Logout
```http
POST {{base_url}}/api/clinic/logout
Authorization: Bearer {{clinic_token}}
```

### 5.3 Send OTP
```http
POST {{base_url}}/api/clinic/send-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567"
}
```

### 5.4 Verify OTP
```http
POST {{base_url}}/api/clinic/verify-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567",
  "otp": "123456"
}
```

---

## 6. Clinic Management

### 6.1 Register Clinic (Public)
```http
POST {{base_url}}/api/clinics
Content-Type: multipart/form-data
```

**Form Data:**
```
clinic_name: عيادة الشفاء
phone: 07771234567
email: clinic@example.com
specialization_id: 1
governorate_id: 1
city_id: 1
district_id: 1
address: شارع رئيسي
detailed_address: مبنى رقم 5
floor: 3
room_number: 301
consultation_fee: 50000
description: وصف العيادة
username: clinic_shifa
password: password123
latitude: 33.3152
longitude: 44.3661
main_image: [file]
gallery_images[]: [file1], [file2]
working_hours: {"sunday": [{"start": "09:00", "end": "17:00"}]}
services: [{"name": "كشف", "price": 50000}]
```

### 6.2 Get Clinic Profile
```http
GET {{base_url}}/api/clinics/profile
Authorization: Bearer {{clinic_token}}
```

### 6.3 Update Clinic Profile
```http
PUT {{base_url}}/api/clinics/profile
Authorization: Bearer {{clinic_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
clinic_name: عيادة الشفاء (محدثة)
phone: 07771234567
specialization_id: 1
governorate_id: 1
city_id: 1
district_id: 1
detailed_address: مبنى رقم 5 - طابق 3
consultation_fee: 60000
description: وصف محدث
username: clinic_shifa
latitude: 33.3152
longitude: 44.3661
main_image: [file]
gallery_images[]: [file1], [file2]
working_hours: {"sunday": [{"start": "09:00", "end": "17:00"}]}
services: [{"name": "كشف", "price": 60000}]
```

### 6.4 Delete Clinic
```http
DELETE {{base_url}}/api/clinics/profile
Authorization: Bearer {{clinic_token}}
```

### 6.5 List All Clinics (Admin Only)
```http
GET {{base_url}}/api/clinics
Authorization: Bearer {{admin_token}}
```

### 6.6 Get Clinic by ID
```http
GET {{base_url}}/api/clinics/{id}
Authorization: Bearer {{admin_token}}
```

### 6.7 Activate Clinic (Admin Only)
```http
PATCH {{base_url}}/api/clinics/{id}/activate
Authorization: Bearer {{admin_token}}
```

### 6.8 Deactivate Clinic (Admin Only)
```http
PATCH {{base_url}}/api/clinics/{id}/deactivate
Authorization: Bearer {{admin_token}}
```

### 6.9 Get Clinic Doctors
```http
GET {{base_url}}/api/clinics/doctors
Authorization: Bearer {{clinic_token}}
```

### 6.10 Add Doctor to Clinic
```http
POST {{base_url}}/api/clinics/doctors
Authorization: Bearer {{clinic_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "doctor_id": 1
}
```

### 6.11 Remove Doctor from Clinic
```http
DELETE {{base_url}}/api/clinics/doctors/{doctorId}
Authorization: Bearer {{clinic_token}}
```

### 6.12 Set Primary Doctor
```http
PUT {{base_url}}/api/clinics/doctors/{doctorId}/primary
Authorization: Bearer {{clinic_token}}
```

### 6.13 Get Clinic Invitations
```http
GET {{base_url}}/api/clinics/invitations
Authorization: Bearer {{clinic_token}}
```

### 6.14 Send Invitation to Doctor
```http
POST {{base_url}}/api/clinics/invitations/send
Authorization: Bearer {{clinic_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "doctor_id": 1,
  "clinic_id": 1,
  "message": "ندعوك للانضمام إلى فريقنا الطبي"
}
```

### 6.15 Cancel Invitation
```http
PATCH {{base_url}}/api/clinics/invitations/{invitation_id}/cancel
Authorization: Bearer {{clinic_token}}
```

---

## 7. Doctor Authentication

### 7.1 Doctor Login
```http
POST {{base_url}}/api/doctor/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "identifier": "doctor@example.com",
  "password": "password123"
}
```

**Response:** Save `token` to `user_token` variable

### 7.2 Doctor Register Request
```http
POST {{base_url}}/api/doctor/register-request
Content-Type: multipart/form-data
```

**Form Data:**
```
first_name: أحمد
last_name: محمد
phone: 07771234567
email: doctor@example.com
password: password123
password_confirmation: password123
date_of_birth: 1990-01-01
gender: male
username: dr_ahmed
license_number: 12345
practicing_profession_date: 2015-01-01
governorate_id: 1
bio: طبيب اختصاصي
distinguished_specialties: جراحة، قلب
facebook_link: https://facebook.com/dr
instagram_link: https://instagram.com/dr
specializations_ids: [1, 2]
image: [file]
certifications[]: [{"name": "شهادة 1", "image": [file]}]
```

### 7.3 Verify Phone
```http
POST {{base_url}}/api/doctor/verify-phone
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567",
  "otp": "123456"
}
```

### 7.4 Resend OTP
```http
POST {{base_url}}/api/doctor/resend-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567"
}
```

### 7.5 Doctor Logout
```http
POST {{base_url}}/api/doctor/logout
Authorization: Bearer {{user_token}}
```

---

## 8. Doctor Management

### 8.1 Update Doctor Profile
```http
PUT {{base_url}}/api/doctors
Authorization: Bearer {{user_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
first_name: أحمد
last_name: محمد
phone: 07771234567
email: doctor@example.com
username: dr_ahmed
license_number: 12345
practicing_profession_date: 2015-01-01
governorate_id: 1
district_id: 1
bio: وصف محدث
distinguished_specialties: جراحة
facebook_link: https://facebook.com/dr
instagram_link: https://instagram.com/dr
consultation_price: 50000
image: [file]
specializations_ids: [1, 2]
certifications[]: [{"name": "شهادة جديدة", "image": [file]}]
```

### 8.2 Get Doctor Profile
```http
GET {{base_url}}/api/doctors/profile
Authorization: Bearer {{user_token}}
```

---

## 9. Medical Center Authentication

### 9.1 Medical Center Login
```http
POST {{base_url}}/api/center/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "center_damascus",
  "password": "password123"
}
```

**Response:** Save `token` to `center_token` variable

### 9.2 Medical Center Register Request
```http
POST {{base_url}}/api/center/register-request
Content-Type: multipart/form-data
```

**Form Data:**
```
center_name: مركز دمشق الطبي
phone: 07771234567
governorate_id: 1
district_id: 1
city_id: 1
clinic_count: 10
username: center_damascus
password: password123
password_confirmation: password123
detailed_address: شارع رئيسي
latitude: 33.5138
longitude: 36.2765
image: [file]
gallery_images[]: [file1], [file2]
working_hours: {"sunday": [{"start": "08:00", "end": "20:00"}]}
services: [{"name": "استشارة", "price": 50000}]
facebook_link: https://facebook.com/center
instagram_link: https://instagram.com/center
website_link: https://center.com
plan_id: 1
```

---

## 10. Medical Center Management

### 10.1 Update Medical Center
```http
PUT {{base_url}}/api/centers/{id}
Authorization: Bearer {{center_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
center_name: مركز دمشق الطبي (محدث)
username: center_damascus
clinic_count: 15
latitude: 33.5138
longitude: 36.2765
detailed_address: عنوان محدث
facebook_link: https://facebook.com/center
instagram_link: https://instagram.com/center
website_link: https://center.com
working_hours: {"sunday": [{"start": "08:00", "end": "20:00"}]}
services: [{"name": "استشارة", "price": 60000}]
image: [file]
gallery_images[]: [file1], [file2]
```

### 10.2 Delete Medical Center
```http
DELETE {{base_url}}/api/centers/{id}
Authorization: Bearer {{center_token}}
```

---

## 11. Medical Team Login

### 11.1 Medical Team Login (Doctors & Secretaries)
```http
POST {{base_url}}/api/medical-team/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "identifier": "dr_ahmed",
  "password": "password123"
}
```

**Response:** Save `token` to `user_token` variable

### 11.2 Medical Team Logout
```http
POST {{base_url}}/api/medical-team/logout
Authorization: Bearer {{user_token}}
```

---

## 12. Secretary Management

### 12.1 Create Secretary
```http
POST {{base_url}}/api/secretaries
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "فاطمة",
  "last_name": "أحمد",
  "phone": "07779876543",
  "email": "fatima@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "date_of_birth": "1995-01-01",
  "gender": "female",
  "username": "fatima_sec",
  "entity_id": 1,
  "entity_type": "clinic"
}
```

### 12.2 Update Secretary Account
```http
POST {{base_url}}/api/secretaries/updateAccounte
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "فاطمة",
  "last_name": "أحمد",
  "phone": "07779876543",
  "email": "fatima@example.com",
  "username": "fatima_sec",
  "entity_id": 1,
  "entity_type": "medical_center"
}
```

### 12.3 Get Secretary Profile
```http
GET {{base_url}}/api/secretaries/profile
Authorization: Bearer {{user_token}}
```

### 12.4 Update Secretary Profile
```http
PUT {{base_url}}/api/secretaries/profile
Authorization: Bearer {{user_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
first_name: فاطمة
last_name: أحمد
phone: 07779876543
email: fatima@example.com
username: fatima_sec
date_of_birth: 1995-01-01
gender: female
profile_image: [file]
entity_id: 1
entity_type: clinic
```

---

## 13. Patient Authentication

### 13.1 Patient Login
```http
POST {{base_url}}/api/auth/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567",
  "password": "password123"
}
```

### 13.2 Send OTP
```http
POST {{base_url}}/api/auth/send-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567"
}
```

### 13.3 Verify OTP
```http
POST {{base_url}}/api/auth/verify-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567",
  "otp": "123456"
}
```

**Response:** Save `token` to `patient_token` variable

### 13.4 Resend OTP
```http
POST {{base_url}}/api/auth/resend-otp
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "07771234567"
}
```

### 13.5 Patient Register
```http
POST {{base_url}}/api/auth/register
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
first_name: محمد
last_name: علي
phone: 07771234567
governorate_id: 1
district_id: 1
gender: male
date_of_birth: 1990-01-01
image: [file]
```

---

## 14. Patient Management

### 14.1 Update Patient
```http
PUT {{base_url}}/api/patients/{id}
Authorization: Bearer {{patient_token}}
Content-Type: multipart/form-data
```

**Form Data:**
```
first_name: محمد
last_name: علي
email: mohamed@example.com
governorate_id: 1
district_id: 1
gender: male
date_of_birth: 1990-01-01
image: [file]
```

---

## 15. Appointments

### 15.1 Set Doctor Work Settings
```http
POST {{base_url}}/api/appointments/set-doctor-work-settings
Content-Type: application/json
```

**Request Body:**
```json
{
  "clinic_id": 1,
  "doctor_id": 1,
  "method_id": 1,
  "appointment_period": 30,
  "queue": true,
  "queue_number": 10
}
```

### 15.2 Set Weekly Schedule
```http
POST {{base_url}}/api/appointments/set-weekly-schedule
Content-Type: application/json
```

**Request Body:**
```json
{
  "clinic_id": 1,
  "doctor_id": 1,
  "effective_from": "2026-01-01",
  "effective_to": "2026-12-31",
  "weekly_schedule": [
    {
      "day_of_week": 0,
      "start_time": "09:00",
      "end_time": "17:00",
      "breaks": [
        {
          "start": "13:00",
          "end": "14:00"
        }
      ]
    }
  ]
}
```

### 15.3 Generate Slots
```http
POST {{base_url}}/api/appointments/generate-slots
Content-Type: application/json
```

**Request Body:**
```json
{
  "clinic_id": 1,
  "doctor_id": 1,
  "start_date": "2026-01-01",
  "end_date": "2026-01-31"
}
```

### 15.4 Create Manual Slots
```http
POST {{base_url}}/api/appointments/create-manual-slots
Content-Type: application/json
```

**Request Body:**
```json
{
  "clinic_id": 1,
  "doctor_id": 1,
  "slots": [
    {
      "date": "2026-01-15",
      "start_time": "10:00",
      "end_time": "10:30"
    }
  ]
}
```

### 15.5 Add Override
```http
POST {{base_url}}/api/appointments/add-override
Content-Type: application/json
```

**Request Body:**
```json
{
  "clinic_id": 1,
  "doctor_id": 1,
  "date": "2026-01-15",
  "start_time": "08:00",
  "end_time": "12:00",
  "type": "working"
}
```

### 15.6 Get Booking Info
```http
GET {{base_url}}/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/booking-info
```

### 15.7 Get Available Appointments
```http
GET {{base_url}}/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/available-appointments
```

### 15.8 Submit Appointment
```http
POST {{base_url}}/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/submit
Content-Type: application/json
```

**Request Body:**
```json
{
  "patient_id": 1,
  "slot_id": 1,
  "notes": "ملاحظات"
}
```

### 15.9 Join Waiting List
```http
POST {{base_url}}/api/appointments/{clinic_id}/patients/doctors/{doctor_id}/waiting-list
Content-Type: application/json
```

**Request Body:**
```json
{
  "patient_id": 1,
  "preferred_date": "2026-01-15"
}
```

### 15.10 Cancel Appointment
```http
POST {{base_url}}/api/v1/appointments/{appointment_id}/cancel
Authorization: Bearer {{user_token}}
```

### 15.11 Mark Appointment as Attended
```http
POST {{base_url}}/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/mark-attended
Authorization: Bearer {{clinic_token}}
```

### 15.12 Confirm Appointment Initial
```http
POST {{base_url}}/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-initial
Authorization: Bearer {{clinic_token}}
```

### 15.13 Confirm Appointment Final
```http
POST {{base_url}}/api/v1/clinics/{clinic_id}/appointments/{appointment_id}/confirm-final
Authorization: Bearer {{clinic_token}}
```

---

## 16. Invitations

### 16.1 Get Available Doctors
```http
GET {{base_url}}/api/available-doctors?query=أحمد
```

### 16.2 Send Invitation (Clinic)
```http
POST {{base_url}}/api/clinics/invitations/send
Authorization: Bearer {{clinic_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "doctor_id": 1,
  "clinic_id": 1,
  "message": "دعوة للانضمام"
}
```

### 16.3 Respond to Invitation (Doctor)
```http
PATCH {{base_url}}/api/doctor/invitations/{invitation_id}
Authorization: Bearer {{user_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "accepted"
}
```

---

## 17. Public Endpoints

### 17.1 Get Governorates
```http
GET {{base_url}}/api/governorates
```

### 17.2 Get Districts by Governorate
```http
GET {{base_url}}/api/governorates/{governorate}/districts
```

### 17.3 Store District
```http
POST {{base_url}}/api/governorates/{governorate}/districts
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "المنطقة الأولى"
}
```

### 17.4 Get Medical Specializations
```http
GET {{base_url}}/api/medical-specializations
```

### 17.5 Create Medical Specialization (Admin)
```http
POST {{base_url}}/api/medical-specializations
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name_ar": "أمراض القلب",
  "name_en": "Cardiology"
}
```

### 17.6 Update Medical Specialization
```http
PUT {{base_url}}/api/medical-specializations/{id}
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name_ar": "أمراض القلب والشرايين",
  "name_en": "Cardiology & Vascular"
}
```

### 17.7 Delete Medical Specialization
```http
DELETE {{base_url}}/api/medical-specializations/{id}
Authorization: Bearer {{admin_token}}
```

---

## 18. Notifications

### 18.1 Send Notification
```http
POST {{base_url}}/api/notification/send-notification
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 1,
  "title": "عنوان الإشعار",
  "message": "محتوى الإشعار"
}
```

---

## 🧪 Test Data

### Admin Credentials
```
Email: admin@clinichub.com
Password: Admin@12345
```

### Clinic Test Data
| ID | Username | Password | Status |
|----|----------|----------|--------|
| 2 | clinic_shifa | password123 | approved |
| 3 | clinic_amal | password123 | approved |
| 4 | clinic_noor | password123 | approved |

### Medical Center Test Data
| Center ID | User ID | Username | Email | Password |
|-----------|---------|----------|-------|----------|
| 1 | 2 | center_damascus | damascus@center.test | password123 |
| 2 | 3 | center_yasmin | yasmin@center.test | password123 |

---

## ✅ Quick Start

1. **Login as Admin** → Get `admin_token`
2. **Create Subscription Plans** → Use admin token
3. **Register/Login as Clinic/Doctor/Center** → Get respective tokens
4. **Assign Subscriptions** → Use admin token with clinic/center IDs
5. **Test Protected Endpoints** → Use appropriate tokens

---

## 📝 Notes

### Token Types
- `admin_token` - For admin operations
- `user_token` - For doctors/secretaries
- `clinic_token` - For clinics
- `center_token` - For medical centers
- `patient_token` - For patients

### Common Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

# ✅ Done!
