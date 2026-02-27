<?php

namespace App\Services\Api;

use App\Models\User;
use App\Models\Doctor;
use App\Services\ImageKitService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DoctorAuthService
{
    protected ImageKitService $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    public function login(array $credentials): ?array
    {
        $user = User::where(function($query) use ($credentials) {
            $query->where('email', $credentials['identifier'])
                  ->orWhere('phone', $credentials['identifier']);
        })->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        if (!$user->doctor) {
            return null;
        }

        if ($user->status !== 'approved') {
            throw new \Exception('Ø­Ø³Ø§Ø¨Ùƒ ÙÙŠ Ø§Ù†ØªØ¸Ø§Ø± Ø§Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ù…Ù† Ù‚Ø¨Ù„ Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©');
        }

        $token = $user->createToken('doctor-auth-token')->plainTextToken;

        return [
            'token' => $token,
            'doctor' => $user->doctor->load('user')
        ];
    }

    public function registerRequest(array $data, array $files): Doctor
    {
        return DB::transaction(function () use ($data, $files) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'],
                'birth_date' => $data['date_of_birth'],
                'status' => 'pending'
            ]);

            if (isset($files['image'])) {
                $uploadResult = $this->imageKitService->upload($files['image'], 'doctors/profiles');
                $user->update([
                    'profile_photo_url' => $uploadResult['url'],
                    'profile_photo_file_id' => $uploadResult['fileId']
                ]);
            }

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

            $user->assignRole('doctor');

            return $doctor->load(['user', 'specializations', 'certifications']);
        });
    }
}
