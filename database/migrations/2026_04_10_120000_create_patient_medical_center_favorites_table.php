<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_center_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_center_id')->constrained('medical_centers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'medical_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_center_favorites');
    }
};
