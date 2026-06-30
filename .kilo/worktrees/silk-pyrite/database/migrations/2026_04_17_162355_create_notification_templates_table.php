<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration Template
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - Pre-commit hook will BLOCK migrations with forbidden patterns
 * - This is a PERMANENT STANDARD - NO EXCEPTIONS
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('channel')->index(); // email, whatsapp, telegram, instagram, webhook
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->string('provider_template_id')->nullable(); // For Meta/WhatsApp Official Templates
            $table->string('language', 10)->default('tr');
            
            // ✅ CONTEXT7 PERMANENT STANDARD: Display Order
            $table->integer('display_order')->default(0)->comment('Sıralama (Context7: order → display_order)');

            // ✅ CONTEXT7 PERMANENT STANDARD: Status field
            $table->tinyInteger('aktiflik_durumu')->default(1)->comment('0=inactive, 1=active (Context7 canonical)');

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['key', 'channel', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
