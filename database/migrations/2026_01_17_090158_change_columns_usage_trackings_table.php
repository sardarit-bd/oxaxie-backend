<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_trackings', function (Blueprint $table) {
            $table->decimal('ai_cost_accumulated', 10, 6)->default(0.000000)->change();
            $table->decimal('credits_used', 10, 8)->default(0.000000)->change();
        });

        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->decimal('credits_added', 10, 6)->default(0.000000)->change();
        });
    }

    public function down(): void
    {
        Schema::table('usage_trackings', function (Blueprint $table) {
            $table->decimal('ai_cost_accumulated', 10, 4)->default(0.0000)->change();
            $table->decimal('credits_used', 10, 4)->default(0.0000)->change();
        });

        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->decimal('credits_added', 10, 4)->default(0.0000)->change();
        });
    }
};