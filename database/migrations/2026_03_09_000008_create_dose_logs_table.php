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
        Schema::create('dose_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_tracker_id')
                ->constrained('medication_trackers')
                ->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->timestamp('taken_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('action_source')->nullable();
            $table->timestamps();

            $table->index(['medication_tracker_id', 'status', 'scheduled_at'], 'dose_logs_tracker_status_scheduled_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dose_logs');
    }
};
