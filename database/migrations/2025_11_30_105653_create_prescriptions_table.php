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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('visit_record_id')
                  ->constrained('visit_records')
                  ->cascadeOnDelete();
    
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('instructions')->nullable();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
