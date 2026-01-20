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
        Schema::create('clinic_doctor', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('clinic_id')
                  ->constrained('clinics')
                  ->cascadeOnDelete();
    
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->cascadeOnDelete();
    
            $table->boolean('is_primary')->default(false);

            $table->foreignId('method_id')
                  ->constrained('methods')
                  ->nullOnDelete();
            $table->integer('appointment_period');
            $table->boolean('queue')->default(false);
            $table->integer('queue_number')->nullable();
    
            $table->timestamps();
    
            $table->unique(['clinic_id', 'doctor_id']);
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_doctor');
    }
};
