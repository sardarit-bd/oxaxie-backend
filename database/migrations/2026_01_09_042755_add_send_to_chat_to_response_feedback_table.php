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
        Schema::table('response_feedback', function (Blueprint $table) {
            $table->boolean('sent_to_chat')->default(false)->after('response_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('response_feedback', function (Blueprint $table) {
            $table->dropColumn('sent_to_chat');
        });
    }
};
