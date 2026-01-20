<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add status field to schedule_slots for better tracking.
     * Status: 'available' = free slot, 'booked' = has appointment, 'blocked' = manually blocked
     */
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->enum('status', ['available', 'booked', 'blocked'])
                  ->after('is_available')
                  ->default('available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
