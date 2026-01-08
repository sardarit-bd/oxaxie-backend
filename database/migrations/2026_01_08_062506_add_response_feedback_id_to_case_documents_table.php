<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_documents', function (Blueprint $table) {
            $table->foreignUuid('response_feedback_id')
                ->nullable()
                ->after('all_case_id')
                ->constrained('response_feedback')
                ->onDelete('cascade');
            
            $table->index('response_feedback_id');
        });
    }

    public function down(): void
    {
        Schema::table('case_documents', function (Blueprint $table) {
            $table->dropForeign(['response_feedback_id']);
            $table->dropColumn('response_feedback_id');
        });
    }
};