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
        Schema::table('usage_trackings', function (Blueprint $table) {
            $table->decimal('credits_used', 10, 4)->default(0.0000)->after('ai_cost_accumulated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_trackings', function (Blueprint $table) {
            $table->dropColumn('credits_used');
        });
    }
};
