<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SpecializationSeeder::class,
        ]);

        // Create governorates and cities first
        $this->createGovernoratesAndCities();

        // Then create districts
        $this->call([
            DistrictSeeder::class,
        ]);

        // Create sample doctors with associated users
        $this->createDoctorUsers();
    }

    private function createGovernoratesAndCities(): void
    {
        // Create Damascus governorate if it doesn't exist
        $damascusGov = \App\Models\Governorate::firstOrCreate(
            ['name_ar' => 'دمشق'],
            ['name_en' => 'Damascus']
        );

        // Create Damascus city if it doesn't exist
        \App\Models\City::firstOrCreate(
            ['name_ar' => 'دمشق', 'governorate_id' => $damascusGov->id],
            ['name_en' => 'Damascus']
        );
    }

    private function createDoctorUsers(): void
    {
        // Create some sample doctors with associated users
        $doctorsData = [
            [
                'user' => [
                    'first_name' => 'Ahmad',
                    'last_name' => 'Al-Hassan',
                    'phone' => '+963912345678',
                    'email' => 'ahmad.hassan@clinic.com',
                    'password' => 'password',
                    'gender' => 'male',
                    'status' => 'approved',
                ],
                'doctor' => [
                    'username' => 'dr_ahmad_hassan',
                    'license_number' => 'MED001',
                    'practicing_profession_date' => 2010,
                    'governorate_id' => 1, // Assuming Damascus has ID 1

                    'bio' => 'Experienced general medicine doctor with over 10 years of practice.',
                    'distinguished_specialties' => 'Internal Medicine, Family Medicine',
                    'facebook_link' => 'https://facebook.com/dr.ahmadhassan',
                    'instagram_link' => 'https://instagram.com/dr.ahmadhassan',
                    'status' => 'approved',
                    'has_secretary_service' => true,
                ],
                'specialization_ids' => [1], // General Medicine
            ],
            [
                'user' => [
                    'first_name' => 'Lina',
                    'last_name' => 'Al-Rashid',
                    'phone' => '+963912345679',
                    'email' => 'lina.rashid@clinic.com',
                    'password' => 'password',
                    'gender' => 'female',
                    'status' => 'approved',
                ],
                'doctor' => [
                    'username' => 'dr_lina_rashid',
                    'license_number' => 'PED002',
                    'practicing_profession_date' => 2012,
                    'governorate_id' => 1,

                    'bio' => 'Pediatric specialist focusing on child health and development.',
                    'distinguished_specialties' => 'Neonatal Care, Child Development',
                    'facebook_link' => 'https://facebook.com/dr.linarashid',
                    'instagram_link' => 'https://instagram.com/dr.linarashid',
                    'status' => 'approved',
                    'has_secretary_service' => false,
                ],
                'specialization_ids' => [2], // Pediatrics
            ],
            [
                'user' => [
                    'first_name' => 'Omar',
                    'last_name' => 'Al-Masri',
                    'phone' => '+963912345680',
                    'email' => 'omar.masri@clinic.com',
                    'password' => 'password',
                    'gender' => 'male',
                    'status' => 'approved',
                ],
                'doctor' => [
                    'username' => 'dr_omar_masri',
                    'license_number' => 'CARD003',
                    'practicing_profession_date' => 2008,
                    'governorate_id' => 1,

                    'bio' => 'Cardiology expert with specialization in heart diseases and treatments.',
                    'distinguished_specialties' => 'Interventional Cardiology, Echocardiography',
                    'facebook_link' => 'https://facebook.com/dr.omarmasri',
                    'instagram_link' => 'https://instagram.com/dr.omarmasri',
                    'status' => 'approved',
                    'has_secretary_service' => true,
                ],
                'specialization_ids' => [3], // Cardiology
            ],
            [
                'user' => [
                    'first_name' => 'Fatima',
                    'last_name' => 'Al-Zahra',
                    'phone' => '+963912345681',
                    'email' => 'fatima.zahra@clinic.com',
                    'password' => 'password',
                    'gender' => 'female',
                    'status' => 'approved',
                ],
                'doctor' => [
                    'username' => 'dr_fatima_zahra',
                    'license_number' => 'DENT004',
                    'practicing_profession_date' => 2015,
                    'governorate_id' => 1,
                    'bio' => 'Dental surgeon with expertise in cosmetic dentistry and oral surgery.',
                    'distinguished_specialties' => 'Cosmetic Dentistry, Oral Surgery',
                    'facebook_link' => 'https://facebook.com/dr.fatimazahra',
                    'instagram_link' => 'https://instagram.com/dr.fatimazahra',
                    'status' => 'approved',
                    'has_secretary_service' => false,
                ],
                'specialization_ids' => [4], // Dentistry
            ],
            [
                'user' => [
                    'first_name' => 'Khaled',
                    'last_name' => 'Al-Omari',
                    'phone' => '+963912345682',
                    'email' => 'khaled.omari@clinic.com',
                    'password' => 'password',
                    'gender' => 'male',
                    'status' => 'pending',
                ],
                'doctor' => [
                    'username' => 'dr_khaled_omari',
                    'license_number' => 'SURG005',
                    'practicing_profession_date' => 2018,
                    'governorate_id' => 1,
                    'bio' => 'General surgeon with experience in various surgical procedures.',
                    'distinguished_specialties' => 'Minimally Invasive Surgery, Laparoscopic Surgery',
                    'facebook_link' => 'https://facebook.com/dr.khaledomari',
                    'instagram_link' => 'https://instagram.com/dr.khaledomari',
                    'status' => 'pending',
                    'has_secretary_service' => true,
                ],
                'specialization_ids' => [5], // General Surgery
            ],
        ];

        foreach ($doctorsData as $doctorData) {
            // Check if user already exists by email
            $user = User::where('email', $doctorData['user']['email'])->first();

            if (!$user) {
                $user = new User();
                $user->first_name = $doctorData['user']['first_name'];
                $user->last_name = $doctorData['user']['last_name'];
                $user->phone = $doctorData['user']['phone'];
                $user->email = $doctorData['user']['email'];
                $user->password = $doctorData['user']['password'];
                $user->gender = $doctorData['user']['gender'];
                $user->status = $doctorData['user']['status'];
                $user->save();
            }

            // Check if doctor record already exists for this user
            $doctor = Doctor::where('user_id', $user->id)->first();

            if (!$doctor) {
                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'username' => $doctorData['doctor']['username'],
                    'license_number' => $doctorData['doctor']['license_number'],
                    'practicing_profession_date' => $doctorData['doctor']['practicing_profession_date'],
                    'governorate_id' => $doctorData['doctor']['governorate_id'],
                    'bio' => $doctorData['doctor']['bio'],
                    'distinguished_specialties' => $doctorData['doctor']['distinguished_specialties'],
                    'facebook_link' => $doctorData['doctor']['facebook_link'],
                    'instagram_link' => $doctorData['doctor']['instagram_link'],
                    'status' => $doctorData['doctor']['status'],
                    'has_secretary_service' => $doctorData['doctor']['has_secretary_service'],
                ]);

                // Attach specializations
                $doctor->specializations()->attach($doctorData['specialization_ids']);
            }
        }

        // Create some doctors with multiple specializations
        $multiSpecDoctor = [
            'user' => [
                'first_name' => 'Mohammad',
                'last_name' => 'Al-Turk',
                'phone' => '+963912345683',
                'email' => 'mohammad.turk@clinic.com',
                'password' => 'password',
                'gender' => 'male',
                'status' => 'approved',
            ],
            'doctor' => [
                'username' => 'dr_mohammad_turk',
                'license_number' => 'INT006',
                'practicing_profession_date' => 2009,
                'governorate_id' => 1,
                'bio' => 'Internal medicine specialist with expertise in multiple areas.',
                'distinguished_specialties' => 'Endocrinology, Rheumatology',
                'facebook_link' => 'https://facebook.com/dr.mohammadturk',
                'instagram_link' => 'https://instagram.com/dr.mohammadturk',
                'status' => 'approved',
                'has_secretary_service' => true,
            ],
            'specialization_ids' => [1, 10], // General Medicine and Psychiatry
        ];

        $user = User::where('email', $multiSpecDoctor['user']['email'])->first();

        if (!$user) {
            $user = new User();
            $user->first_name = $multiSpecDoctor['user']['first_name'];
            $user->last_name = $multiSpecDoctor['user']['last_name'];
            $user->phone = $multiSpecDoctor['user']['phone'];
            $user->email = $multiSpecDoctor['user']['email'];
            $user->password = $multiSpecDoctor['user']['password'];
            $user->gender = $multiSpecDoctor['user']['gender'];
            $user->status = $multiSpecDoctor['user']['status'];
            $user->save();
        }

        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'username' => $multiSpecDoctor['doctor']['username'],
                'license_number' => $multiSpecDoctor['doctor']['license_number'],
                'practicing_profession_date' => $multiSpecDoctor['doctor']['practicing_profession_date'],
                'governorate_id' => $multiSpecDoctor['doctor']['governorate_id'],
                'bio' => $multiSpecDoctor['doctor']['bio'],
                'distinguished_specialties' => $multiSpecDoctor['doctor']['distinguished_specialties'],
                'facebook_link' => $multiSpecDoctor['doctor']['facebook_link'],
                'instagram_link' => $multiSpecDoctor['doctor']['instagram_link'],
                'status' => $multiSpecDoctor['doctor']['status'],
                'has_secretary_service' => $multiSpecDoctor['doctor']['has_secretary_service'],
            ]);

            $doctor->specializations()->attach($multiSpecDoctor['specialization_ids']);
        }
    }
}

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            ['name_ar' => 'طب عام', 'name_en' => 'General Medicine'],
            ['name_ar' => 'طب الأطفال', 'name_en' => 'Pediatrics'],
            ['name_ar' => 'طب القلب', 'name_en' => 'Cardiology'],
            ['name_ar' => 'طب الأسنان', 'name_en' => 'Dentistry'],
            ['name_ar' => 'الجراحة العامة', 'name_en' => 'General Surgery'],
            ['name_ar' => 'طب العيون', 'name_en' => 'Ophthalmology'],
            ['name_ar' => 'الأمراض الجلدية', 'name_en' => 'Dermatology'],
            ['name_ar' => 'طب النساء والولادة', 'name_en' => 'Obstetrics and Gynecology'],
            ['name_ar' => 'جراحة العظام', 'name_en' => 'Orthopedics'],
            ['name_ar' => 'الطب النفسي', 'name_en' => 'Psychiatry'],
            ['name_ar' => 'الأنف والأذن والحنجرة', 'name_en' => 'ENT'],
            ['name_ar' => 'المسالك البولية', 'name_en' => 'Urology'],
        ];

        foreach ($specializations as $specialization) {
            \App\Models\Specialization::create($specialization);
        }
    }
}

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // مثال: إضافة مناطق لمحافظة دمشق
        $governorate = \App\Models\Governorate::where('name_ar', 'دمشق')->first();

        if ($governorate) {
            $districts = [
                'المزة',
                'المالكي',
                'أبو رمانة',
                'الشعلان',
                'المهاجرين',
                'القصاع',
                'الميدان',
                'باب توما'
            ];

            foreach ($districts as $district) {
                \App\Models\District::create([
                    'governorate_id' => $governorate->id,
                    'name_ar' => $district,
                    'name_en' => $district
                ]);
            }
        }
    }
}
