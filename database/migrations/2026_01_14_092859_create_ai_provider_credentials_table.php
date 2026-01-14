<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('api_key');
            $table->json('additional_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ai_provider_id', 'user_id', 'is_active'], 'unique_active_credential');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_credentials');
    }
};