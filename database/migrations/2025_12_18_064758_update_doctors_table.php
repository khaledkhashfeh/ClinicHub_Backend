<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->integer('practicing_profession_date')->nullable()->after('consultation_price');
            $table->text('distinguished_specialties')->nullable()->after('bio');
            $table->string('facebook_link')->nullable();
            $table->string('instagram_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'practicing_profession_date',
                'distinguished_specialties',
                'facebook_link',
                'instagram_link'
            ]);
        });
    }
};
