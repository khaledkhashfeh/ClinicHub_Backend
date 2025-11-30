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
        Schema::create('secretaries', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained()
                  ->cascadeOnDelete();
    
            $table->foreignId('medical_center_id')
                  ->nullable()
                  ->constrained('medical_centers')
                  ->nullOnDelete();
    
            $table->foreignId('clinic_id')
                  ->nullable()
                  ->constrained('clinics')
                  ->nullOnDelete();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secretaries');
    }
};
