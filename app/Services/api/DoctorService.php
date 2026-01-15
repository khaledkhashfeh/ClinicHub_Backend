<?php

namespace App\Services;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorAuthService
{
    /**
     * تسجيل دخول الطبيب
     */
    public function login(array $credentials): ?array
    {
        // البحث عن المستخدم بالبريد الإلكتروني أو رقم الهاتف
        $user = User::where(function($query) use ($credentials) {
            $query->where('email', $credentials['identifier'])
                  ->orWhere('phone', $credentials['identifier']);
        })->first();

        // التحقق من وجود المستخدم وصحة كلمة المرور
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // التحقق من أن المستخدم طبيب
        if (!$user->doctor) {
            return null;
        }

        // التحقق من حالة الحساب
        if ($user->status !== 'approved') {
            throw new \Exception('حسابك في انتظار الموافقة من قبل الإدارة');
        }

        // إنشاء Token
        $token = $user->createToken('doctor-auth-token')->plainTextToken;

        return [
            'token' => $token,
            'doctor' => $user->doctor->load('user')
        ];
    }

    /**
     * طلب إنشاء حساب طبيب جديد
     */
    public function registerRequest(array $data, array $files): Doctor
    {
        return DB::transaction(function () use ($data, $files) {
            // إنشاء حساب المستخدم
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'],
                'birth_date' => $data['date_of_birth'],
                'status' => 'pending' // في انتظار الموافقة
            ]);

            // رفع الصورة الشخصية إن وجدت
            if (isset($files['image'])) {
                $imagePath = $files['image']->store('doctors/profiles', 'public');
                $user->update(['profile_photo_url' => $imagePath]);
            }

            // إنشاء ملف الطبيب
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'username' => $data['username'],
                'license_number' => $data['license_number'],
                'governorate_id' => $data['governorate_id'],
                'district_id' => $data['district_id'],
                'practicing_profession_date' => $data['practicing_profession_date'],
                'bio' => $data['bio'],
                'distinguished_specialties' => $data['distinguished_specialties'] ?? null,
                'facebook_link' => $data['facebook_link'] ?? null,
                'instagram_link' => $data['instagram_link'] ?? null,
                'status' => 'pending'
            ]);

            // ربط التخصصات
            if (isset($data['specializations_ids'])) {
                $doctor->specializations()->sync($data['specializations_ids']);
            }

            // إضافة الشهادات
            if (isset($data['certifications']) && is_array($data['certifications'])) {
                foreach ($data['certifications'] as $index => $certification) {
                    $certData = [
                        'doctor_id' => $doctor->id,
                        'name' => $certification['name']
                    ];

                    // رفع صورة الشهادة إن وجدت
                    if (isset($files["certifications.{$index}.image"])) {
                        $certImagePath = $files["certifications.{$index}.image"]
                            ->store("doctors/{$doctor->id}/certifications", 'public');
                        $certData['image_url'] = $certImagePath;
                    }

                    $doctor->certifications()->create($certData);
                }
            }

            // تعيين دور الطبيب
            $user->assignRole('doctor');

            return $doctor->load(['user', 'specializations', 'certifications']);
        });
    }
}

class DoctorProfileService
{
    /**
     * تحديث معلومات الطبيب
     */
    public function update(Doctor $doctor, array $data, array $files): Doctor
    {
        return DB::transaction(function () use ($doctor, $data, $files) {
            // تحديث بيانات المستخدم
            $userData = [];
            foreach (['first_name', 'last_name', 'phone', 'email', 'gender', 'date_of_birth'] as $field) {
                if (isset($data[$field])) {
                    $userData[$field] = $data[$field];
                }
            }

            if (!empty($userData)) {
                $doctor->user->update($userData);
            }

            // رفع الصورة الشخصية الجديدة
            if (isset($files['image'])) {
                // حذف الصورة القديمة
                if ($doctor->user->profile_photo_url) {
                    Storage::disk('public')->delete($doctor->user->profile_photo_url);
                }

                $imagePath = $files['image']->store('doctors/profiles', 'public');
                $doctor->user->update(['profile_photo_url' => $imagePath]);
            }

            // تحديث بيانات الطبيب
            $doctorData = [];
            $allowedFields = [
                'username',
                'license_number',
                'governorate_id',
                'district_id',
                'practicing_profession_date',
                'bio',
                'distinguished_specialties',
                'facebook_link',
                'instagram_link',
                'consultation_price'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $doctorData[$field] = $data[$field];
                }
            }

            if (!empty($doctorData)) {
                $doctor->update($doctorData);
            }

            // تحديث التخصصات
            if (isset($data['specializations_ids'])) {
                $doctor->specializations()->sync($data['specializations_ids']);
            }

            // إضافة شهادات جديدة
            if (isset($data['certifications']) && is_array($data['certifications'])) {
                foreach ($data['certifications'] as $index => $certification) {
                    $certData = [
                        'doctor_id' => $doctor->id,
                        'name' => $certification['name']
                    ];

                    // رفع صورة الشهادة إن وجدت
                    if (isset($files["certifications.{$index}.image"])) {
                        $certImagePath = $files["certifications.{$index}.image"]
                            ->store("doctors/{$doctor->id}/certifications", 'public');
                        $certData['image_url'] = $certImagePath;
                    }

                    $doctor->certifications()->create($certData);
                }
            }

            return $doctor->fresh([
                'user',
                'specializations',
                'certifications',
                'governorate',
                'district',
                'city'
            ]);
        });
    }
}
