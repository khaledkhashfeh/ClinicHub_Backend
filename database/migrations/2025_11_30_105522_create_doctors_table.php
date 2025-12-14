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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained()
                  ->cascadeOnDelete();
    
            $table->string('specialty');
            $table->decimal('consultation_price', 10, 2);
    
            $table->foreignId('governorate_id')
                  ->nullable()
                  ->constrained('governorates')
                  ->nullOnDelete();
            
            $table->foreignId('city_id')
                  ->nullable()
                  ->constrained('cities')
                  ->nullOnDelete();
            
            $table->string('area')->nullable();
            $table->string('address_details')->nullable();
    
            $table->text('bio')->nullable();
    
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('has_secretary_service')->default(false);
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
