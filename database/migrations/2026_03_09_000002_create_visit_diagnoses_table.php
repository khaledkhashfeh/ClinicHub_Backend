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
        Schema::create('visit_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_record_id')
                ->constrained('visit_records')
                ->cascadeOnDelete();
            $table->string('condition_id')->nullable();
            $table->string('condition_name');
            $table->enum('classification', ['acute', 'chronic', 'suspected'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_diagnoses');
    }
};
