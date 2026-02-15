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
        Schema::table('schedule_slots', function (Blueprint $table) {
            // Add clinic_id to link slots to specific clinic
            $table->foreignId('clinic_id')
                  ->after('doctor_id')
                  ->nullable()
                  ->constrained('clinics')
                  ->nullOnDelete();
            
            // Add date field for manual appointments (nullable for template-based slots)
            $table->date('date')
                  ->after('day_of_week')
                  ->nullable();
            
            // Add creation method to distinguish manual vs auto-generated slots
            $table->enum('creation_method', ['manual', 'auto'])
                  ->after('slot_type')
                  ->default('manual');
            
            // Reference to schedule template (if auto-generated)
            $table->foreignId('schedule_id')
                  ->after('creation_method')
                  ->nullable()
                  ->constrained('doctor_clinic_schedules')
                  ->nullOnDelete();
            
            // Reference to override (if from exception)
            $table->foreignId('override_id')
                  ->after('schedule_id')
                  ->nullable()
                  ->constrained('schedule_overrides')
                  ->nullOnDelete();
            
            // Make day_of_week nullable (only needed for template-based, not manual with date)
            $table->tinyInteger('day_of_week')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropForeign(['schedule_id']);
            $table->dropForeign(['override_id']);
            $table->dropColumn(['clinic_id', 'date', 'creation_method', 'schedule_id', 'override_id']);
            $table->tinyInteger('day_of_week')->nullable(false)->change();
        });
    }
};
