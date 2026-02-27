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
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->string('currency')->default('syp')->after('price');
            $table->text('description')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->dropColumn(['currency', 'description']);
        });
    }
};
