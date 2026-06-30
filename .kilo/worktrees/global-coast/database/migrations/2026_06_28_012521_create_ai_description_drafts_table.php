<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AI Description Drafts table
     *
     * Pipeline: ContextBuilder → LLM → Draft → Owner Review → Accept → Persist
     */
    public function up(): void
    {
        Schema::create('ai_description_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ilan_id')->constrained()->cascadeOnDelete();

            // Owner/User who requested the draft
            $table->foreignId('user_id')->nullable()->constrained('users');

            // Content
            $table->text('draft_content'); // AI-generated description
            $table->text('original_content')->nullable(); // Original ilan.aciklama for rollback

            // AI Metadata
            $table->string('provider', 50)->nullable(); // cortex, ollama, openai, etc.
            $table->string('model', 100)->nullable();
            $table->json('metadata')->nullable(); // tokens, request_id, duration_ms

            // Pipeline Status
            $table->enum('durum', [
                'taslak',
                'onayli',
                'uygulandi',
                'reddedildi',
            ])->default('taslak');

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();

            // Rejection
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_note')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['ilan_id', 'durum']);
            $table->index(['user_id', 'durum']);
            $table->index(['durum', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_description_drafts');
    }
};