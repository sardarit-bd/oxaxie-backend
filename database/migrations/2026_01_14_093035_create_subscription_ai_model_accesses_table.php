<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_ai_model_access', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_plan_tier');
            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->boolean('is_allowed')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();


            $table->unique(
                ['subscription_plan_tier', 'ai_model_id'], 
                'pricing_tier_model_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_ai_model_access');
    }
};