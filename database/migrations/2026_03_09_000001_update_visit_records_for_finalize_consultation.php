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
        Schema::table('visit_records', function (Blueprint $table) {
            if (!Schema::hasColumn('visit_records', 'doctor_id')) {
                $table->foreignId('doctor_id')
                    ->nullable()
                    ->constrained('doctors')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('visit_records', 'clinic_id')) {
                $table->foreignId('clinic_id')
                    ->nullable()
                    ->constrained('clinics')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('visit_records', 'patient_id')) {
                $table->foreignId('patient_id')
                    ->nullable()
                    ->constrained('patients')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('visit_records', 'session_start_time')) {
                $table->timestamp('session_start_time')->nullable();
            }
        });

        if (Schema::hasColumn('visit_records', 'diagnosis')) {
            Schema::table('visit_records', function (Blueprint $table) {
                $table->text('diagnosis')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('visit_records', 'diagnosis')) {
            Schema::table('visit_records', function (Blueprint $table) {
                $table->text('diagnosis')->nullable(false)->change();
            });
        }

        Schema::table('visit_records', function (Blueprint $table) {
            if (Schema::hasColumn('visit_records', 'session_start_time')) {
                $table->dropColumn('session_start_time');
            }

            if (Schema::hasColumn('visit_records', 'patient_id')) {
                $table->dropConstrainedForeignId('patient_id');
            }

            if (Schema::hasColumn('visit_records', 'clinic_id')) {
                $table->dropConstrainedForeignId('clinic_id');
            }

            if (Schema::hasColumn('visit_records', 'doctor_id')) {
                $table->dropConstrainedForeignId('doctor_id');
            }
        });
    }
};
