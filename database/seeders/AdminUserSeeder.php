<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. إنشاء Role admin إذا لم يكن موجود
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);

        echo "✅ Role 'admin' جاهز\n";

        // 2. التحقق من وجود Admin مسبقاً
        $existingAdmin = User::where('email', 'admin@clinichub.com')->first();

        if ($existingAdmin) {
            echo "⚠️ مستخدم Admin موجود مسبقاً: {$existingAdmin->email}\n";

            // التأكد من أن لديه Role admin
            if (!$existingAdmin->hasRole($adminRole)) {
                $existingAdmin->assignRole($adminRole);
                echo "✅ تم تعيين Role admin للمستخدم الموجود\n";
            }

            return;
        }

        // 3. إنشاء مستخدم Admin جديد
        $admin = User::create([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@clinichub.com',
            'password' => Hash::make('Admin@12345'), // غير كلمة المرور في الإنتاج!
            'phone' => '963999999999',
            'gender' => 'male',
            'status' => 'approved'
        ]);

        // 4. تعيين Role admin (مع تحديد guard api)
        $admin->assignRole($adminRole);

        echo "✅ تم إنشاء مستخدم Admin بنجاح!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📧 Email: admin@clinichub.com\n";
        echo "🔑 Password: Admin@12345\n";
        echo "⚠️ تذكر: غيّر كلمة المرور في الإنتاج!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
