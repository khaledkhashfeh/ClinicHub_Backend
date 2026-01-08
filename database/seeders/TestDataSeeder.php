<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\MedicalCenter;
use App\Models\User;
use App\Models\Governorate;
use App\Models\City;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏥 إنشاء بيانات تجريبية...\n\n";

        // 1. التأكد من وجود governorate و city (أو إنشاءهم)
        $governorate = Governorate::firstOrCreate(
            ['name_ar' => 'دمشق'],
            ['name_en' => 'Damascus']
        );

        $city = City::firstOrCreate(
            [
                'governorate_id' => $governorate->id,
                'name_ar' => 'دمشق'
            ],
            ['name_en' => 'Damascus']
        );

        echo "✅ المحافظة والمدينة جاهزة\n\n";

        // 2. إنشاء عيادات تجريبية
        echo "🏥 إنشاء العيادات...\n";

        $clinics = [
            [
                'clinic_name' => 'عيادة الشفاء',
                'username' => 'clinic_shifa',
                'phone' => '963991111111',
                'email' => 'shifa@clinic.test',
                'password' => 'password123',
                'description' => 'عيادة متخصصة في الطب العام',
                'status' => 'approved',
            ],
            [
                'clinic_name' => 'عيادة الأمل',
                'username' => 'clinic_amal',
                'phone' => '963992222222',
                'email' => 'amal@clinic.test',
                'password' => 'password123',
                'description' => 'عيادة أسنان متقدمة',
                'status' => 'approved',
            ],
            [
                'clinic_name' => 'عيادة النور',
                'username' => 'clinic_noor',
                'phone' => '963993333333',
                'email' => 'noor@clinic.test',
                'password' => 'password123',
                'description' => 'عيادة طب الأطفال',
                'status' => 'approved',
            ],
        ];

        foreach ($clinics as $clinicData) {
            $clinic = Clinic::firstOrCreate(
                ['username' => $clinicData['username']],
                array_merge($clinicData, [
                    'governorate_id' => $governorate->id,
                    'city_id' => $city->id,
                    'address' => 'شارع المزة',
                    'detailed_address' => 'بناء رقم 10، الطابق الثاني',
                    'consultation_fee' => 50000
                ])
            );

            echo "  ✅ {$clinic->clinic_name} (ID: {$clinic->id})\n";
            echo "     Username: {$clinic->username}\n";
            echo "     Password: password123\n\n";
        }

        // 3. إنشاء مراكز طبية تجريبية
        echo "\n🏨 إنشاء المراكز الطبية...\n";

        $centers = [
            [
                'name' => 'مركز دمشق الطبي',
                'phone' => '963994444444',
                'email' => 'damascus@center.test',
                'description' => 'مركز طبي شامل',
            ],
            [
                'name' => 'مركز الياسمين الصحي',
                'phone' => '963995555555',
                'email' => 'yasmin@center.test',
                'description' => 'مركز متخصص في الجراحة',
            ],
        ];

        foreach ($centers as $centerData) {
            // إنشاء مستخدم للمركز
            $user = User::firstOrCreate(
                ['email' => $centerData['email']],
                [
                    'first_name' => explode(' ', $centerData['name'])[0],
                    'last_name' => 'الطبي',
                    'phone' => $centerData['phone'],
                    'password' => 'password123',
                    'gender' => 'male',
                    'status' => 'approved'
                ]
            );

            // إنشاء المركز
            $center = MedicalCenter::firstOrCreate(
                ['name' => $centerData['name']],
                [
                    'user_id' => $user->id,
                    'governorate_id' => $governorate->id,
                    'city_id' => $city->id,
                    'name' => $centerData['name'],
                    'description' => $centerData['description'],
                    'status' => 'approved'
                ]
            );

            echo "  ✅ {$center->name} (ID: {$center->id})\n";
            echo "     User ID: {$user->id}\n";
            echo "     Email: {$user->email}\n";
            echo "     Password: password123\n\n";
        }

        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🎉 تم إنشاء البيانات التجريبية بنجاح!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📋 ملخص البيانات:\n";
        echo "  ✅ " . Clinic::count() . " عيادات\n";
        echo "  ✅ " . MedicalCenter::count() . " مراكز طبية\n\n";

        echo "🔑 بيانات تسجيل الدخول:\n\n";
        
        echo "العيادات:\n";
        $clinics = Clinic::all();
        foreach ($clinics as $clinic) {
            echo "  - {$clinic->clinic_name}\n";
            echo "    Username: {$clinic->username}\n";
            echo "    Password: password123\n";
            echo "    ID: {$clinic->id}\n\n";
        }

        echo "\nالمراكز الطبية:\n";
        $centers = MedicalCenter::with('user')->get();
        foreach ($centers as $center) {
            echo "  - {$center->name}\n";
            echo "    Email: {$center->user->email}\n";
            echo "    Password: password123\n";
            echo "    Center ID: {$center->id}\n";
            echo "    User ID: {$center->user->id}\n\n";
        }

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
