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
        Schema::table('lab_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_requests', 'status')) {
                $table->string('status')->default('pending')->after('instructions');
            }

            if (!Schema::hasColumn('lab_requests', 'reminders_sent')) {
                $table->unsignedInteger('reminders_sent')->default(0)->after('status');
            }

            if (!Schema::hasColumn('lab_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('reminders_sent');
            }

            if (!Schema::hasColumn('lab_requests', 'result_file_url')) {
                $table->string('result_file_url')->nullable()->after('completed_at');
            }

            if (!Schema::hasColumn('lab_requests', 'result_file_type')) {
                $table->enum('result_file_type', ['image', 'pdf'])->nullable()->after('result_file_url');
            }
        });

        DB::table('lab_requests')
            ->whereNull('status')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            if (Schema::hasColumn('lab_requests', 'result_file_type')) {
                $table->dropColumn('result_file_type');
            }

            if (Schema::hasColumn('lab_requests', 'result_file_url')) {
                $table->dropColumn('result_file_url');
            }

            if (Schema::hasColumn('lab_requests', 'completed_at')) {
                $table->dropColumn('completed_at');
            }

            if (Schema::hasColumn('lab_requests', 'reminders_sent')) {
                $table->dropColumn('reminders_sent');
            }

            if (Schema::hasColumn('lab_requests', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
