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
        Schema::table('lab_results', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_results', 'file_name')) {
                $table->string('file_name')->nullable();
            }

            if (!Schema::hasColumn('lab_results', 'file_url')) {
                $table->string('file_url')->nullable();
            }

            if (!Schema::hasColumn('lab_results', 'file_type')) {
                $table->enum('file_type', ['pdf', 'image'])->nullable();
            }

            if (!Schema::hasColumn('lab_results', 'doctor_comment')) {
                $table->text('doctor_comment')->nullable();
            }
        });

        if (Schema::hasColumn('lab_results', 'test_type')) {
            Schema::table('lab_results', function (Blueprint $table) {
                $table->string('test_type')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('lab_results', 'test_type')) {
            Schema::table('lab_results', function (Blueprint $table) {
                $table->string('test_type')->nullable(false)->change();
            });
        }

        Schema::table('lab_results', function (Blueprint $table) {
            if (Schema::hasColumn('lab_results', 'doctor_comment')) {
                $table->dropColumn('doctor_comment');
            }

            if (Schema::hasColumn('lab_results', 'file_type')) {
                $table->dropColumn('file_type');
            }

            if (Schema::hasColumn('lab_results', 'file_url')) {
                $table->dropColumn('file_url');
            }

            if (Schema::hasColumn('lab_results', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
