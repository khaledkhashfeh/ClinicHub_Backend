<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\MedicalCenter;
use App\Models\User;

final class PublicContactLinks
{
    /**
     * @return list<array{type: string, number?: string, url?: string}>
     */
    public static function forDoctor(User $user, Doctor $doctor): array
    {
        $links = [];
        if (!empty($user->phone)) {
            $links[] = ['type' => 'phone', 'number' => (string) $user->phone];
        }
        if (!empty($doctor->facebook_link)) {
            $links[] = ['type' => 'facebook', 'url' => $doctor->facebook_link];
        }
        if (!empty($doctor->instagram_link)) {
            $links[] = ['type' => 'instagram', 'url' => $doctor->instagram_link];
        }

        return $links;
    }

    /**
     * @return list<array{type: string, number?: string, url?: string}>
     */
    public static function forClinic(Clinic $clinic): array
    {
        $links = [];
        if (!empty($clinic->phone)) {
            $links[] = ['type' => 'phone', 'number' => (string) $clinic->phone];
        }
        if (!empty($clinic->facebook_link)) {
            $links[] = ['type' => 'facebook', 'url' => $clinic->facebook_link];
        }
        if (!empty($clinic->instagram_link)) {
            $links[] = ['type' => 'instagram', 'url' => $clinic->instagram_link];
        }
        if (!empty($clinic->website_link)) {
            $links[] = ['type' => 'website', 'url' => $clinic->website_link];
        }

        return $links;
    }

    /**
     * @return list<array{type: string, number?: string, url?: string}>
     */
    public static function forMedicalCenter(MedicalCenter $center): array
    {
        $links = [];
        if (!empty($center->phone)) {
            $links[] = ['type' => 'phone', 'number' => (string) $center->phone];
        }
        if (!empty($center->facebook_link)) {
            $links[] = ['type' => 'facebook', 'url' => $center->facebook_link];
        }
        if (!empty($center->instagram_link)) {
            $links[] = ['type' => 'instagram', 'url' => $center->instagram_link];
        }
        if (!empty($center->website_link)) {
            $links[] = ['type' => 'website', 'url' => $center->website_link];
        }

        return $links;
    }
}
