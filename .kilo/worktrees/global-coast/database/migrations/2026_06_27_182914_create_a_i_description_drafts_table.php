<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AI Description Draft Table
     *
     * Pipeline: Context Builder → LLM → Draft → Owner Review → Accept → Persist
     *
     * AI NEVER writes directly.
     * AI produces Draft only.
     * Owner decides.
     * Owner approves.
     * Only then Persist.
     */
    public function up(): void
    {
        Schema::create('ai_description_drafts', function (Blueprint $table) {
            $table->id();

            // Core Relations
            $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Content Fields
            $table->text('draft_content')->comment('AI tarafından üretilen taslak içerik');
            $table->text('original_content')->nullable()->comment('Orijinal aciklama (reject durumunda geri yükleme için)');

            // Status
            $table->enum('durum', ['taslak', 'onayli', 'uygulandi', 'reddedildi'])
                  ->default('taslak')
                  ->comment('taslak=review bekliyor, onayli=owner onayladı, uygulandi=persist edildi, reddedildi=owner reddetti');

            // AI Metadata
            $table->string('provider')->nullable()->comment('Kullanılan AI provider (deepseek, openai, ollama)');
            $table->string('model')->nullable()->comment('Kullanılan model');
            $table->json('metadata')->nullable()->comment('Token usage, latency vs');

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable()->comment('aciklama alanına yazıldığı tarih');

            // Rejection
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_note')->nullable()->comment('Owner reddetme notu');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['ilan_id', 'durum']);
            $table->index(['ilan_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_description_drafts');
    }
};