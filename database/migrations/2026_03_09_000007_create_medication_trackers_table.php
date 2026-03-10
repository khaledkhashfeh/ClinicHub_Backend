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
        Schema::create('medication_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')
                ->constrained('prescriptions')
                ->cascadeOnDelete();
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();
            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('doctors')
                ->nullOnDelete();
            $table->string('status')->default('waiting_purchase');
            $table->unsignedInteger('total_doses')->default(0);
            $table->unsignedInteger('taken_doses')->default(0);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('next_dose_at')->nullable();
            $table->unsignedInteger('consecutive_missed_doses')->default(0);
            $table->timestamp('non_compliant_at')->nullable();
            $table->timestamps();

            $table->unique('prescription_id');
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_trackers');
    }
};
