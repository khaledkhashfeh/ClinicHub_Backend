<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enterprise-grade schedule templates with versioning support.
     * Defines what the normal week looks like for a doctor at a clinic.
     */
    public function up(): void
    {
        Schema::create('doctor_clinic_schedules', function (Blueprint $table) {
            $table->id();
            
            // Link to doctor and clinic (can also use clinic_doctor_id, but direct is clearer)
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->cascadeOnDelete();
            
            $table->foreignId('clinic_id')
                  ->constrained('clinics')
                  ->cascadeOnDelete();
            
            // Day of week (1=Monday, 2=Tuesday, ..., 7=Sunday)
            $table->tinyInteger('day_of_week');
            
            // Work hours for this day
            $table->time('start_time');
            $table->time('end_time');
            
            // Appointment duration in minutes (from clinic_doctor.appointment_period)
            $table->integer('appointment_duration');
            
            // Breaks stored as JSON array: [{"start": "12:00", "end": "13:00"}, ...]
            $table->json('breaks')->nullable();
            
            // Versioning support - when this schedule is effective
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            // Version number for this schedule (increments when updated)
            $table->integer('version')->default(1);
            
            // Active status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index(['doctor_id', 'clinic_id', 'day_of_week', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_clinic_schedules');
    }
};
