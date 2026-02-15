<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Schedule overrides for one-off exceptions (sick days, closures, custom schedules).
     */
    public function up(): void
    {
        Schema::create('schedule_overrides', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->cascadeOnDelete();
            
            $table->foreignId('clinic_id')
                  ->constrained('clinics')
                  ->cascadeOnDelete();
            
            // Specific date for this override
            $table->date('date');
            
            // Type: 'closed' = completely closed, 'custom' = custom slots
            $table->enum('type', ['closed', 'custom'])->default('closed');
            
            // Custom slots as JSON (only used when type = 'custom')
            // Format: [{"start": "09:00", "end": "10:00"}, ...]
            $table->json('custom_slots')->nullable();
            
            // Reason for override (e.g., "Doctor sick", "Conference day")
            $table->string('reason')->nullable();
            
            $table->timestamps();
            
            // One override per doctor/clinic/date
            $table->unique(['doctor_id', 'clinic_id', 'date']);
            $table->index(['doctor_id', 'clinic_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_overrides');
    }
};
