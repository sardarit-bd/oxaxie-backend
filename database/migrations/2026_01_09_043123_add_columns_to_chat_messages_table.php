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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->uuid('response_feedback_id')->nullable()->after('all_case_id');
            
            $table->foreign('response_feedback_id')
                  ->references('id')
                  ->on('response_feedback')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['response_feedback_id']);
            $table->dropColumn('response_feedback_id');
        });
    }
};
