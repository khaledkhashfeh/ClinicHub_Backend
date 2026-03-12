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
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'medical_center_id')) {
                $table->foreignId('medical_center_id')
                    ->nullable()
                    ->constrained('medical_centers')
                    ->nullOnDelete()
                    ->after('clinic_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'medical_center_id')) {
                $table->dropConstrainedForeignId('medical_center_id');
            }
        });
    }
};

