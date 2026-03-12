<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_terms', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // chronic_disease, medication, surgery
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->timestamps();
        });

        Schema::table('medical_terms', function (Blueprint $table) {
            $table->index(['category', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_terms');
    }
};
