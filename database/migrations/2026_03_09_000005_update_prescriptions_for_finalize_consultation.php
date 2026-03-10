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
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'total_quantity')) {
                $table->integer('total_quantity')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'dose_description')) {
                $table->string('dose_description')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'daily_frequency')) {
                $table->integer('daily_frequency')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'hourly_interval')) {
                $table->integer('hourly_interval')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'food_relation')) {
                $table->string('food_relation')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'duration')) {
                $table->string('duration')->nullable();
            }

            if (!Schema::hasColumn('prescriptions', 'special_instructions')) {
                $table->text('special_instructions')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'special_instructions')) {
                $table->dropColumn('special_instructions');
            }

            if (Schema::hasColumn('prescriptions', 'duration')) {
                $table->dropColumn('duration');
            }

            if (Schema::hasColumn('prescriptions', 'food_relation')) {
                $table->dropColumn('food_relation');
            }

            if (Schema::hasColumn('prescriptions', 'hourly_interval')) {
                $table->dropColumn('hourly_interval');
            }

            if (Schema::hasColumn('prescriptions', 'daily_frequency')) {
                $table->dropColumn('daily_frequency');
            }

            if (Schema::hasColumn('prescriptions', 'dose_description')) {
                $table->dropColumn('dose_description');
            }

            if (Schema::hasColumn('prescriptions', 'total_quantity')) {
                $table->dropColumn('total_quantity');
            }
        });
    }
};
