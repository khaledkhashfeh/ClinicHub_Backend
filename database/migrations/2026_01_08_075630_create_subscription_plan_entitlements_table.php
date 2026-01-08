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
        Schema::create('subscription_plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->onDelete('cascade')
                ->comment('معرف الخطة');
            
            $table->string('key')->comment('اسم القيد/الميزة مثل: enable_secretary, max_patients');
            $table->string('value')->comment('قيمة القيد');
            $table->enum('type', ['boolean', 'integer', 'string', 'decimal'])
                ->default('boolean')
                ->comment('نوع البيانات');
            
            $table->timestamps();
            
            // Index
            $table->index('subscription_plan_id');
            $table->unique(['subscription_plan_id', 'key'], 'plan_entitlement_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_entitlements');
    }
};
