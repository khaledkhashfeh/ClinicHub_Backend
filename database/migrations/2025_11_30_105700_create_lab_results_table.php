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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('visit_record_id')
                  ->constrained('visit_records')
                  ->cascadeOnDelete();
    
            $table->string('test_type');
            $table->text('result_data')->nullable();
            $table->string('attachment_url')->nullable();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
