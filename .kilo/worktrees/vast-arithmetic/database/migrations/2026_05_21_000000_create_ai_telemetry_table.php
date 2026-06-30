<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AI Telemetry table for performance monitoring and observability.
     *
     * SAB Compliance:
     * - Tenant Isolation: tenant_id indexed for cross-tenant data segregation
     * - Context7 Naming: aktiflik_kodu (not status_code)
     * - Observability: Tracks response times, token usage, costs
     *
     * Created: 2026-05-21 (Oturum 30 - SEAL BREAK Remediation)
     * Authority: Mimar (SEAL BREAK PROTOCOL)
     */
    public function up(): void
    {
        if (!Schema::hasTable('ai_telemetry')) {
            Schema::create('ai_telemetry', function (Blueprint $table) {
                $table->id();

                // Tenant Isolation (SAB Madde 1)
                $table->unsignedBigInteger('tenant_id')->index()->comment('Tenant ID for data isolation');

                // AI Provider & Model Info
                $table->string('provider', 50)->index()->comment('AI provider: openai, deepseek, anthropic');
                $table->string('model_name', 100)->comment('Model name: gpt-4, deepseek-chat, etc.');
                $table->string('feature', 100)->index()->comment('Feature key: vision_analysis, smart_fields, etc.');

                // Performance Metrics
                $table->unsignedInteger('response_time_ms')->index()->comment('Response time in milliseconds');
                $table->unsignedInteger('tokens_used')->default(0)->comment('Total tokens consumed');
                $table->unsignedInteger('prompt_tokens')->default(0)->comment('Prompt tokens');
                $table->unsignedInteger('completion_tokens')->default(0)->comment('Completion tokens');

                // Cost Tracking
                $table->decimal('cost_usd', 10, 8)->default(0)->comment('Cost in USD');

                // Request Metadata
                $table->json('prompt_metadata')->nullable()->comment('Prompt metadata (sanitized)');
                $table->json('response_metadata')->nullable()->comment('Response metadata');

                // Status & Error Tracking (Context7: aktiflik_kodu)
                $table->unsignedSmallInteger('aktiflik_kodu')->default(200)->index()->comment('HTTP status code (200, 429, 500, etc.)');
                $table->text('hata_mesaji')->nullable()->comment('Error message if aktiflik_kodu >= 400');

                // Timestamps
                $table->timestamp('created_at')->useCurrent()->index();
                $table->timestamp('updated_at')->nullable();

                // Indexes for performance queries
                $table->index(['tenant_id', 'created_at'], 'idx_tenant_created');
                $table->index(['provider', 'created_at'], 'idx_provider_created');
                $table->index(['feature', 'created_at'], 'idx_feature_created');
                $table->index(['response_time_ms', 'created_at'], 'idx_latency_created');
                $table->index(['aktiflik_kodu', 'created_at'], 'idx_status_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_telemetry');
    }
};
