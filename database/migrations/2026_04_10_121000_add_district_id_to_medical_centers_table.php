<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_centers', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_centers', 'district_id')) {
                $table->foreignId('district_id')
                    ->nullable()
                    ->after('city_id')
                    ->constrained('districts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_centers', function (Blueprint $table) {
            if (Schema::hasColumn('medical_centers', 'district_id')) {
                $table->dropForeign(['district_id']);
                $table->dropColumn('district_id');
            }
        });
    }
};
