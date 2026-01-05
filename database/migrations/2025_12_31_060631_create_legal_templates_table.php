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
        Schema::create('legal_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template_name');
            $table->enum('document_type', [
                'demand_letter',
                'notice_to_cure',
                'complaint_letter',
                'cease_and_desist',
                'custom'
            ]);
            $table->enum('issue_type', [
                'landlord_tenant',
                'employment',
                'contracts',
                'consumer_rights',
                'family',
                'other',
                'general'
            ]);
            $table->string('jurisdiction_state', 100)->nullable();
            $table->string('jurisdiction_country', 2);
            $table->text('template_content');
            $table->json('required_fields');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['document_type', 'issue_type']);
            $table->index(['jurisdiction_state', 'jurisdiction_country']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_templates');
    }
};
