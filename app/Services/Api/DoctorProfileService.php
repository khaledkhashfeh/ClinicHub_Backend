<?php

namespace App\Services\Api;

use App\Models\Doctor;
use App\Services\ImageKitService;
use Illuminate\Support\Facades\DB;

class DoctorProfileService
{
    protected ImageKitService $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    public function update(Doctor $doctor, array $data, array $files): Doctor
    {
        return DB::transaction(function () use ($doctor, $data, $files) {
            $userData = [];
            foreach (['first_name', 'last_name', 'phone', 'email', 'gender', 'date_of_birth'] as $field) {
                if (isset($data[$field])) {
                    $userData[$field] = $data[$field];
                }
            }

            if (!empty($userData)) {
                $doctor->user->update($userData);
            }

            if (isset($files['image'])) {
                // حذف الصورة القديمة إن وجدت
                if ($doctor->user->profile_photo_file_id) {
                    $this->imageKitService->delete($doctor->user->profile_photo_file_id);
                }
                
                $uploadResult = $this->imageKitService->upload($files['image'], 'doctors/profiles');
                $doctor->user->update([
                    'profile_photo_url' => $uploadResult['url'],
                    'profile_photo_file_id' => $uploadResult['fileId']
                ]);
            }

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

            if (isset($data['specializations_ids'])) {
                $doctor->specializations()->sync($data['specializations_ids']);
            }

            if (isset($data['certifications']) && is_array($data['certifications'])) {
                foreach ($data['certifications'] as $index => $certification) {
                    $certData = [
                        'doctor_id' => $doctor->id,
                        'name' => $certification['name']
                    ];

                    if (isset($files["certifications.$index.image"])) {
                        $uploadResult = $this->imageKitService->upload(
                            $files["certifications.$index.image"],
                            "doctors/$doctor->id/certifications"
                        );
                        $certData['image_url'] = $uploadResult['url'];
                        $certData['image_file_id'] = $uploadResult['fileId'];
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
