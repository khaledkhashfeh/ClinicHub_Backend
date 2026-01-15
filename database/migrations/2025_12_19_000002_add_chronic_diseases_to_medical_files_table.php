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
        Schema::table('medical_files', function (Blueprint $table) {
            // Add has_chronic_diseases if it doesn't exist
            if (!Schema::hasColumn('medical_files', 'has_chronic_diseases')) {
                $table->boolean('has_chronic_diseases')->default(false)->after('blood_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_files', function (Blueprint $table) {
            if (Schema::hasColumn('medical_files', 'has_chronic_diseases')) {
                $table->dropColumn('has_chronic_diseases');
            }
        });
    }
};

