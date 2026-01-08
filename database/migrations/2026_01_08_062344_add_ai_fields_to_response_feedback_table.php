<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('response_feedback', function (Blueprint $table) {
            $table->text('ai_next_steps')->nullable()->after('ai_analysis');
            $table->json('escalation_options')->nullable()->after('ai_next_steps');
            $table->enum('urgency_level', ['low', 'medium', 'high', 'critical'])->default('medium')->after('escalation_options');
            $table->date('recommended_deadline')->nullable()->after('urgency_level');
            
     
            $table->enum('status', ['active', 'resolved', 'escalated', 'closed'])->default('active')->after('recommended_deadline');
            
  
            $table->softDeletes();
            
      
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('response_feedback', function (Blueprint $table) {
            $table->dropColumn([
                'ai_next_steps',
                'escalation_options',
                'urgency_level',
                'recommended_deadline',
                'status'
            ]);
            $table->dropSoftDeletes();
            $table->dropIndex(['status']);
        });
    }
};