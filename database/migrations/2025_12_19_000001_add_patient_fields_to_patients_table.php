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
        Schema::table('patients', function (Blueprint $table) {
            // Add governorate_id and city_id if they don't exist
            if (!Schema::hasColumn('patients', 'governorate_id')) {
                $table->foreignId('governorate_id')
                      ->nullable()
                      ->after('user_id')
                      ->constrained('governorates')
                      ->nullOnDelete();
            }
            
            if (!Schema::hasColumn('patients', 'city_id')) {
                $table->foreignId('city_id')
                      ->nullable()
                      ->after('governorate_id')
                      ->constrained('cities')
                      ->nullOnDelete();
            }
            
            // Add occupation if it doesn't exist
            if (!Schema::hasColumn('patients', 'occupation')) {
                $table->string('occupation')->nullable()->after('city_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'governorate_id')) {
                $table->dropForeign(['governorate_id']);
                $table->dropColumn('governorate_id');
            }
            
            if (Schema::hasColumn('patients', 'city_id')) {
                $table->dropForeign(['city_id']);
                $table->dropColumn('city_id');
            }
            
            if (Schema::hasColumn('patients', 'occupation')) {
                $table->dropColumn('occupation');
            }
        });
    }
};

