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
        Schema::create('appointments', function (Blueprint $table) {
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
    
            $table->date('date');

            $table->time('start_time');
            $table->time('end_time');
    
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('type', ['consultation', 'checkup'])->default('consultation');
    
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'online', 'insurance'])->nullable();
            $table->decimal('price_at_booking', 10, 2)->nullable();
    
            $table->enum('source', ['patient_app', 'doctor_app', 'secretary_panel', 'website'])->default('patient_app');
            $table->text('cancellation_reason')->nullable();
            $table->boolean('no_show')->default(false);
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
