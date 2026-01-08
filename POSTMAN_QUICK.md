# 🚀 Postman APIs - مختصر

## ⚙️ Setup
```
base_url = http://localhost:8000
admin_token = (بعد تسجيل الدخول)
```

---

# 1. Admin Login

## 1.1 بـ Email
```
POST {{base_url}}/api/admin/login
Content-Type: application/json
```
```json
{
  "email": "admin@clinichub.com",
  "password": "Admin@12345"
}
```

## 1.2 برقم الهاتف
```
POST {{base_url}}/api/admin/login
Content-Type: application/json
```
```json
{
  "phone": "0999999999",
  "password": "Admin@12345"
}
```

---

# 2. Subscription Plans (Public)

## 3.1 جلب الخطط
```
GET {{base_url}}/api/subscription-plans
```

## 2.2 جلب خطط العيادات
```
GET {{base_url}}/api/subscription-plans?target_type=clinic
```

## 2.3 جلب خطة محددة
```
GET {{base_url}}/api/subscription-plans/1
```

---

# 3. Subscription Plans (Admin)

## 3.1 إنشاء خطة للعيادات
```
POST {{base_url}}/api/admin/subscription-plans
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "name": "الخطة الفضية",
  "target_type": "clinic",
  "price": 5000,
  "duration_days": 30,
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

## 3.2 إنشاء خطة ذهبية
```
POST {{base_url}}/api/admin/subscription-plans
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "name": "الخطة الذهبية",
  "target_type": "clinic",
  "price": 10000,
  "duration_days": 30,
  "features": [
    "200 مريض شهرياً",
    "سكرتيرة",
    "إشعارات"
  ],
  "entitlements": [
    {
      "key": "enable_secretary",
      "value": "true",
      "type": "boolean"
    },
    {
      "key": "max_patients_per_month",
      "value": "200",
      "type": "integer"
    }
  ]
}
```

## 3.3 خطة للمراكز
```
POST {{base_url}}/api/admin/subscription-plans
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "name": "باقة المراكز",
  "target_type": "medical_center",
  "price": 40000,
  "duration_days": 90,
  "features": [
    "15 عيادة",
    "50 طبيب",
    "نظام محاسبي"
  ],
  "entitlements": [
    {
      "key": "max_clinics",
      "value": "15",
      "type": "integer"
    },
    {
      "key": "max_doctors",
      "value": "50",
      "type": "integer"
    }
  ]
}
```

## 3.4 تعديل خطة
```
PUT {{base_url}}/api/admin/subscription-plans/1
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "name": "الخطة الذهبية (محدثة)",
  "price": 12000
}
```

## 3.5 حذف خطة
```
DELETE {{base_url}}/api/admin/subscription-plans/1
Authorization: Bearer {{admin_token}}
```

---

# 4. Subscriptions

## 4.1 جلب الاشتراكات
```
GET {{base_url}}/api/admin/subscriptions
Authorization: Bearer {{admin_token}}
```

## 4.2 جلب النشطة
```
GET {{base_url}}/api/admin/subscriptions?status=active
Authorization: Bearer {{admin_token}}
```

## 4.3 تعيين اشتراك لعيادة
```
POST {{base_url}}/api/admin/subscriptions
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "subscription_plan_id": 1,
  "subscribable_type": "clinic",
  "subscribable_id": 2,
  "starts_at": "2026-01-08",
  "status": "trial"
}
```

**بيانات تجريبية:**
- Clinic ID: 2 (عيادة الشفاء)
- Clinic ID: 3 (عيادة الأمل)
- Clinic ID: 4 (عيادة النور)

## 4.4 تعيين لمركز
```
POST {{base_url}}/api/admin/subscriptions
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "subscription_plan_id": 3,
  "subscribable_type": "medical_center",
  "subscribable_id": 1,
  "starts_at": "2026-01-08",
  "status": "active"
}
```

**بيانات تجريبية:**
- Medical Center ID: 1 (مركز دمشق الطبي)
- Medical Center ID: 2 (مركز الياسمين الصحي)

## 4.5 تفعيل اشتراك
```
PATCH {{base_url}}/api/admin/subscriptions/1
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "status": "active"
}
```

## 4.6 تمديد اشتراك
```
PATCH {{base_url}}/api/admin/subscriptions/1
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "ends_at": "2027-01-31"
}
```

## 4.7 إلغاء اشتراك
```
PATCH {{base_url}}/api/admin/subscriptions/1
Authorization: Bearer {{admin_token}}
Content-Type: application/json
```
```json
{
  "status": "canceled"
}
```

---

# 5. My Subscription

## 5.1 اشتراكي
```
GET {{base_url}}/api/my-subscription
Authorization: Bearer {{user_token}}
```

---

# 📝 ملاحظات

## ❌ الأخطاء الشائعة

### خطأ: خطة بنفس الاسم
```json
{
  "success": false,
  "message": "يوجد خطة بنفس الاسم لهذا النوع من الحسابات",
  "errors": {
    "name": ["اسم الخطة موجود مسبقاً لـ العيادات"]
  }
}
```

**الحل:** غير اسم الخطة أو اختر نوع مختلف

---

# 🧪 بيانات تجريبية

## العيادات (Clinics):
| ID | الاسم | Username | Password |
|----|-------|----------|----------|
| 2 | عيادة الشفاء | clinic_shifa | password123 |
| 3 | عيادة الأمل | clinic_amal | password123 |
| 4 | عيادة النور | clinic_noor | password123 |

## المراكز الطبية (Medical Centers):
| Center ID | User ID | الاسم | Email | Password |
|-----------|---------|-------|-------|----------|
| 1 | 2 | مركز دمشق الطبي | damascus@center.test | password123 |
| 2 | 3 | مركز الياسمين الصحي | yasmin@center.test | password123 |

---

# ✅ خلص!

