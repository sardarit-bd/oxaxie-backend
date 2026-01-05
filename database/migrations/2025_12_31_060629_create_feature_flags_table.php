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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('flag_key', 100)->unique();
            $table->string('flag_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('enabled_for_users')->nullable();
            $table->json('enabled_for_plans')->nullable();
            $table->integer('rollout_percentage')->default(0);
            $table->timestamps();
            
            $table->index('flag_key');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
