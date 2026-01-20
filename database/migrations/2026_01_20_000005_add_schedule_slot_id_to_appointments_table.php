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
        Schema::table('appointments', function (Blueprint $table) {
            // Link appointment to schedule slot (for method 1 - fast booking)
            $table->foreignId('schedule_slot_id')
                  ->after('clinic_id')
                  ->nullable()
                  ->constrained('schedule_slots')
                  ->nullOnDelete();
            
            // Reference to schedule template (for traceability)
            $table->foreignId('schedule_id')
                  ->after('schedule_slot_id')
                  ->nullable()
                  ->constrained('doctor_clinic_schedules')
                  ->nullOnDelete();
            
            // Reference to override (if appointment is from exception)
            $table->foreignId('override_id')
                  ->after('schedule_id')
                  ->nullable()
                  ->constrained('schedule_overrides')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['schedule_slot_id']);
            $table->dropForeign(['schedule_id']);
            $table->dropForeign(['override_id']);
            $table->dropColumn(['schedule_slot_id', 'schedule_id', 'override_id']);
        });
    }
};
