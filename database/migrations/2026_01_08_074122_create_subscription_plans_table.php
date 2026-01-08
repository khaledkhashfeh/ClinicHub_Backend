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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('target_type', ['clinic', 'medical_center'])->comment('نوع الحساب: عيادة أو مركز طبي');
            $table->decimal('price', 10, 2)->comment('السعر');
            $table->integer('duration_days')->comment('مدة الاشتراك بالأيام');
            $table->boolean('is_active')->default(true)->comment('هل الخطة نشطة؟');
            $table->text('description')->nullable()->comment('وصف الخطة');
            $table->timestamps();
            
            // Indexes
            $table->index('target_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
