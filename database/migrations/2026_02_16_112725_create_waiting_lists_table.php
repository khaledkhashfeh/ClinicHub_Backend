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
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->cascadeOnDelete();
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->cascadeOnDelete();
            $table->foreignId('clinic_id')
                  ->constrained('clinics')
                  ->cascadeOnDelete();
            $table->date('target_date');
            $table->text('patient_note')->nullable();
            $table->integer('position_in_queue')->nullable(); // Position in the waiting list for this date
            $table->enum('status', ['active', 'notified', 'booked', 'expired'])->default('active');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // When the waiting list entry expires
            $table->timestamps();
            
            // Ensure a patient can only be in the waiting list once per doctor per date
            $table->unique(['patient_id', 'doctor_id', 'clinic_id', 'target_date'], 'unique_patient_doctor_clinic_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
