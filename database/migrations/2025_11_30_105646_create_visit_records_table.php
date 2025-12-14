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
        Schema::create('visit_records', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('medical_file_id')
                  ->constrained('medical_files')
                  ->cascadeOnDelete();
    
                  
            $table->foreignId('appointment_id')
                  ->nullable()
                  ->constrained('appointments')
                  ->nullOnDelete();

    
            $table->date('visit_date');
            $table->text('diagnosis');
            $table->text('notes')->nullable();
            $table->date('next_visit_date')->nullable();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_records');
    }
};
