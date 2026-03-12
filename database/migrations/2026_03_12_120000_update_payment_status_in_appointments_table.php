<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Update ENUM values for MySQL
            DB::statement("
                ALTER TABLE appointments
                MODIFY COLUMN payment_status ENUM('unpaid', 'partial_paid', 'full_paid', 'refunded')
                NOT NULL DEFAULT 'unpaid'
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: drop existing CHECK constraint (created by enum) and add a new one
            // Default Laravel naming convention: appointments_payment_status_check
            DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_payment_status_check');

            DB::statement("
                ALTER TABLE appointments
                ADD CONSTRAINT appointments_payment_status_check
                CHECK (payment_status IN ('unpaid', 'partial_paid', 'full_paid', 'refunded'))
            ");
        } elseif ($driver === 'sqlite') {
            // For sqlite (mainly tests), change to string if needed
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('payment_status', 50)->default('unpaid')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE appointments
                MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'refunded')
                NOT NULL DEFAULT 'unpaid'
            ");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_payment_status_check');

            DB::statement("
                ALTER TABLE appointments
                ADD CONSTRAINT appointments_payment_status_check
                CHECK (payment_status IN ('unpaid', 'paid', 'refunded'))
            ");
        } elseif ($driver === 'sqlite') {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('payment_status', 50)->default('unpaid')->change();
            });
        }
    }
};

