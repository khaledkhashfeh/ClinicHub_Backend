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
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('user_id')
                  ->unique()
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
    
            $table->foreignId('medical_center_id')
                  ->nullable()
                  ->constrained('medical_centers')
                  ->nullOnDelete();
    
            $table->string('name');
            $table->string('floor')->nullable();
            $table->string('room_number')->nullable();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
