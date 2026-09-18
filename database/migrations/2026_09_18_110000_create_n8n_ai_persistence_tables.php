<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * N8n AI UseCases persistence tables:
     * - ai_messages
     * - ai_contract_drafts
     * - ai_ilan_taslaklari
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_messages')) {
            Schema::create('ai_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('communication_id')->nullable()->index();
                $table->string('conversation_id')->nullable()->index();
                $table->string('channel', 50)->default('web');
                $table->string('role', 50)->default('assistant');
                $table->text('content');
                $table->string('mesaj_durumu', 50)->default('draft');
                $table->string('ai_model_used')->nullable();
                $table->timestamp('ai_generated_at')->nullable();
                $table->integer('tokens_used')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_contract_drafts')) {
            Schema::create('ai_contract_drafts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('contract_type', 50)->nullable();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->unsignedBigInteger('ilan_id')->nullable()->index();
                $table->unsignedBigInteger('kisi_id')->nullable()->index();
                $table->unsignedBigInteger('danisman_id')->nullable()->index();
                $table->text('content')->nullable();
                $table->text('draft_content')->nullable();
                $table->string('yayin_durumu', 50)->default('taslak');
                $table->string('ai_model_used')->nullable();
                $table->timestamp('ai_generated_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_ilan_taslaklari')) {
            Schema::create('ai_ilan_taslaklari', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('danisman_id')->index();
                $table->unsignedBigInteger('ilan_id')->nullable()->index();
                $table->string('yayin_durumu', 50)->default('taslak');
                $table->string('taslak_durumu', 50)->default('taslak');
                $table->json('ai_response');
                $table->string('ai_model_used')->nullable();
                $table->string('ai_prompt_version')->nullable();
                $table->timestamp('ai_generated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_ilan_taslaklari');
        Schema::dropIfExists('ai_contract_drafts');
        Schema::dropIfExists('ai_messages');
    }
};
