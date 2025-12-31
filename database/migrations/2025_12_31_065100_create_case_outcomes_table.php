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
        Schema::create('case_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('all_case_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('outcome_type', ['won', 'settled', 'lost', 'dropped']);
            $table->text('outcome_summary');
            $table->decimal('money_saved', 10, 2)->nullable();
            $table->decimal('money_recovered', 10, 2)->nullable();
            $table->boolean('court_avoided')->default(false);
            $table->boolean('hired_attorney')->default(false);
            $table->unsignedTinyInteger('ai_helpfulness_rating')->nullable();
            $table->text('feedback_text')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->boolean('testimonial_consent')->default(false);
            $table->boolean('testimonial_published')->default(false);
            $table->integer('days_to_resolution')->nullable();
            $table->timestamps();
            
            $table->unique('all_case_id');
            $table->index('outcome_type');
            $table->index(['testimonial_consent', 'testimonial_published']);
            $table->index('ai_helpfulness_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_outcomes');
    }
};
