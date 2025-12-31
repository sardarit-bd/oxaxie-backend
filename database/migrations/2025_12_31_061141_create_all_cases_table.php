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
        Schema::create('all_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid ('user_id')->constrained()->onDelete('cascade');
            $table->enum('issue_type', [
                'landlord_tenant',
                'employment',
                'contracts',
                'consumer_rights',
                'family',
                'other'
            ]);
            $table->string('location_city')->nullable();
            $table->string('location_state', 100);
            $table->string('location_country', 2); // ISO 3166-1 alpha-2
            $table->text('situation_description');
            $table->enum('status', ['active', 'resolved', 'archived'])->default('active');
            $table->enum('resolution_type', ['won', 'settled', 'lost', 'dropped'])->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index('issue_type');
            $table->index('created_at');
            $table->index('resolved_at');
            $table->fullText('situation_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_cases');
    }
};
