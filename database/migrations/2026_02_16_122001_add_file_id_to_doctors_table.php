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
        Schema::table('doctors', function (Blueprint $table) {
            // إضافة عمود image إذا لم يكن موجوداً
            if (!Schema::hasColumn('doctors', 'image')) {
                $table->string('image')->nullable();
            }
            // إضافة file_id
            $table->string('image_file_id')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('image_file_id');
        });
    }
};
