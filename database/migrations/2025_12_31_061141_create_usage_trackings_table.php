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
        Schema::create('usage_trackings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->date('billing_cycle_date');
            $table->integer('messages_used')->default(0);
            $table->integer('documents_generated')->default(0);
            $table->integer('cases_created')->default(0);
            $table->decimal('ai_cost_accumulated', 10, 4)->default(0.0000);
            $table->integer('input_tokens_used')->default(0);
            $table->integer('output_tokens_used')->default(0);
            $table->boolean('cost_threshold_reached')->default(false);
            $table->timestamp('threshold_reached_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'billing_cycle_date']);
            $table->index('billing_cycle_date');
            $table->index(['user_id', 'cost_threshold_reached']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_trackings');
    }
};
