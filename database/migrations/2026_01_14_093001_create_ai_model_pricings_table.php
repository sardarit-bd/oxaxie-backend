<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->string('subscription_plan_tier')->nullable();
            $table->decimal('input_cost_per_1m_tokens', 10, 6);
            $table->decimal('output_cost_per_1m_tokens', 10, 6);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(
                ['ai_model_id', 'subscription_plan_tier', 'is_active'], 
                'pricing_model_tier_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_pricing');
    }
};