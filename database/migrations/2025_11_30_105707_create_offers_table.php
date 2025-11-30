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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('doctor_id')
                  ->nullable()
                  ->constrained('doctors')
                  ->nullOnDelete();
    
            $table->foreignId('clinic_id')
                  ->nullable()
                  ->constrained('clinics')
                  ->nullOnDelete();
    
            $table->string('title');
            $table->text('description')->nullable();
    
            $table->date('start_date')->nullable();
            $table->date('valid_until')->nullable();
    
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_visit'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
    
            $table->boolean('is_active')->default(true);
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
