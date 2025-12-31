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
        Schema::create('response_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('all_case_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('response_type', [
                'complied',
                'partial_compliance',
                'refused',
                'no_response',
                'counter_offer'
            ]);
            $table->text('response_description');
            $table->date('response_date');
            $table->date('action_taken_date')->nullable();
            $table->integer('days_to_response')->nullable();
            $table->boolean('ai_analyzed')->default(false);
            $table->text('ai_analysis')->nullable();
            $table->timestamps();
            
            $table->index(['all_case_id', 'response_date']);
            $table->index('response_type');
            $table->index('ai_analyzed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_feedback');
    }
};
