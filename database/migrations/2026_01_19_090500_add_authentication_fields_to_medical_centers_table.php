<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medical_centers', function (Blueprint $table) {
            // إضافة حقول تسجيل الدخول
            if (!Schema::hasColumn('medical_centers', 'username')) {
                $table->string('username')->unique()->after('name');
            }

            if (!Schema::hasColumn('medical_centers', 'password')) {
                $table->string('password')->after('username');
            }

            // إضافة حقول إضافية حسب المتطلبات
            // ملاحظة: سنستخدم 'name' كـ 'center_name' في الكود

            if (!Schema::hasColumn('medical_centers', 'clinic_count')) {
                $table->integer('clinic_count')->default(0)->after('password');
            }

            if (!Schema::hasColumn('medical_centers', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('location_coords');
            }

            if (!Schema::hasColumn('medical_centers', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }

            if (!Schema::hasColumn('medical_centers', 'facebook_link')) {
                $table->string('facebook_link')->nullable()->after('longitude');
            }

            if (!Schema::hasColumn('medical_centers', 'instagram_link')) {
                $table->string('instagram_link')->nullable()->after('facebook_link');
            }

            if (!Schema::hasColumn('medical_centers', 'website_link')) {
                $table->string('website_link')->nullable()->after('instagram_link');
            }

            // جعل user_id nullable (لأن المركز غير مربوط بـ User)
            if (Schema::hasColumn('medical_centers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_centers', function (Blueprint $table) {
            if (Schema::hasColumn('medical_centers', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('medical_centers', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('medical_centers', 'clinic_count')) {
                $table->dropColumn('clinic_count');
            }

            if (Schema::hasColumn('medical_centers', 'latitude')) {
                $table->dropColumn('latitude');
            }

            if (Schema::hasColumn('medical_centers', 'longitude')) {
                $table->dropColumn('longitude');
            }

            if (Schema::hasColumn('medical_centers', 'facebook_link')) {
                $table->dropColumn('facebook_link');
            }

            if (Schema::hasColumn('medical_centers', 'instagram_link')) {
                $table->dropColumn('instagram_link');
            }

            if (Schema::hasColumn('medical_centers', 'website_link')) {
                $table->dropColumn('website_link');
            }

            if (Schema::hasColumn('medical_centers', 'center_name')) {
                $table->renameColumn('center_name', 'name');
            }
        });
    }
};

