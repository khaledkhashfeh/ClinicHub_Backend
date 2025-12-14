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
        Schema::create('medical_centers', function (Blueprint $table) {
            $table->id();
        

            $table->foreignId('user_id')
                  ->unique()
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();

                  
        
            $table->string('name');
        
            $table->foreignId('governorate_id')->nullable()->constrained('governorates')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
        
            $table->string('address_details')->nullable();
            $table->string('location_coords')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
        
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        
            $table->timestamps();
        });
        
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_centers');
    }
};
