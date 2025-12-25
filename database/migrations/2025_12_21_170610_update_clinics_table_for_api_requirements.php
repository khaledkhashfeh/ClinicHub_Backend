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
        Schema::table('clinics', function (Blueprint $table) {
            // Rename the original 'name' column to 'clinic_name' first
            if (Schema::hasColumn('clinics', 'name') && !Schema::hasColumn('clinics', 'clinic_name')) {
                $table->renameColumn('name', 'clinic_name');
            }

            // Add required fields for API (only if they don't exist)
            if (!Schema::hasColumn('clinics', 'phone')) {
                $table->string('phone')->after('clinic_name')->unique();
            }
            if (!Schema::hasColumn('clinics', 'specialization_id')) {
                $table->foreignId('specialization_id')->after('phone')->nullable()->constrained('specializations')->nullOnDelete();
            }
            if (!Schema::hasColumn('clinics', 'district_id')) {
                $table->foreignId('district_id')->after('specialization_id')->nullable()->constrained('districts')->nullOnDelete();
            }
            if (!Schema::hasColumn('clinics', 'detailed_address')) {
                $table->string('detailed_address')->after('district_id');
            }
            if (!Schema::hasColumn('clinics', 'consultation_fee')) {
                $table->decimal('consultation_fee', 10, 2)->after('detailed_address');
            }
            if (!Schema::hasColumn('clinics', 'description')) {
                $table->text('description')->after('consultation_fee');
            }
            if (!Schema::hasColumn('clinics', 'username')) {
                $table->string('username')->after('description')->unique();
            }
            if (!Schema::hasColumn('clinics', 'password')) {
                $table->string('password')->after('username');
            }
            if (!Schema::hasColumn('clinics', 'main_image')) {
                $table->string('main_image')->after('password')->nullable();
            }
            if (!Schema::hasColumn('clinics', 'working_hours')) {
                $table->json('working_hours')->after('main_image')->nullable();
            }
            if (!Schema::hasColumn('clinics', 'latitude')) {
                $table->decimal('latitude', 10, 8)->after('working_hours')->nullable();
            }
            if (!Schema::hasColumn('clinics', 'longitude')) {
                $table->decimal('longitude', 11, 8)->after('latitude')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // Remove new columns
            $columnsToRemove = [
                'phone',
                'specialization_id',
                'district_id',
                'detailed_address',
                'consultation_fee',
                'description',
                'username',
                'password',
                'main_image',
                'working_hours',
                'latitude',
                'longitude'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('clinics', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Rename back only if we had renamed it
            if (Schema::hasColumn('clinics', 'clinic_name') && !Schema::hasColumn('clinics', 'name')) {
                $table->renameColumn('clinic_name', 'name');
            }
        });
    }
};
