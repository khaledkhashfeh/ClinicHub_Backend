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
        Schema::table('medical_centers', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_centers', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('website_link');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_centers', function (Blueprint $table) {
            if (Schema::hasColumn('medical_centers', 'working_hours')) {
                $table->dropColumn('working_hours');
            }
        });
    }
};
