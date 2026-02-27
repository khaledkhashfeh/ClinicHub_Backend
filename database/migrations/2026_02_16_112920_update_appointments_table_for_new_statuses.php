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
        // Add the new columns first
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('cancelled_by')->nullable()->after('cancellation_reason');
            $table->text('cancelled_by_comment')->nullable()->after('cancelled_by');
        });
        
        // Temporarily rename the status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('status', 'status_old');
        });
        
        // Add the new status column with expanded enum options
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', [
                'pending_approval',
                'booked',
                'confirmed',
                'final_confirmation',
                'cancelled',
                'completed'
            ])->default('booked')->after('date');
        });
        
        // Copy data from old to new column, mapping old values to new ones
        \DB::statement("
            UPDATE appointments 
            SET status = CASE 
                WHEN status_old = 'scheduled' THEN 'booked'
                WHEN status_old = 'completed' THEN 'completed'
                WHEN status_old = 'cancelled' THEN 'cancelled'
                ELSE 'booked'
            END
        ");
        
        // Drop the old status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status_old');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Temporarily rename the status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->renameColumn('status', 'status_new');
        });
        
        // Add back the old status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled')->after('date');
        });
        
        // Map new values back to old ones
        \DB::statement("
            UPDATE appointments 
            SET status = CASE 
                WHEN status_new IN ('pending_approval', 'booked', 'confirmed', 'final_confirmation') THEN 'scheduled'
                WHEN status_new = 'completed' THEN 'completed'
                WHEN status_new = 'cancelled' THEN 'cancelled'
                ELSE 'scheduled'
            END
        ");
        
        // Drop the new status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status_new');
        });
        
        // Remove the added columns
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancelled_by_comment']);
        });
    }
};
