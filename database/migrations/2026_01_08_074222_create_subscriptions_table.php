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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->onDelete('restrict')
                ->comment('معرف الخطة');
            
            // Polymorphic relationship
            $table->morphs('subscribable', 'subscriptions_subscribable_index');
            
            $table->dateTime('starts_at')->comment('تاريخ بداية الاشتراك');
            $table->dateTime('ends_at')->comment('تاريخ نهاية الاشتراك');
            $table->enum('status', ['trial', 'active', 'expired', 'canceled'])
                ->default('trial')
                ->comment('حالة الاشتراك');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index(['subscribable_type', 'subscribable_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
