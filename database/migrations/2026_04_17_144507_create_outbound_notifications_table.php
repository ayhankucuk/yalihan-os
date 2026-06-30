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
        Schema::create('outbound_notifications', function (Blueprint $table) {
            $table->id();

            // N1-B Flow Fields
            $table->string('channel', 50)->index(); // email, whatsapp, telegram, webhook
            $table->string('recipient')->index();
            $table->string('template_key')->index();
            $table->json('payload_data')->nullable(); // Only safe template variables

            // Delivery Status
            $table->string('gonderim_durumu', 20)->default('pending')->index(); // pending, sent, failed, queued
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();

            // ✅ CONTEXT7 PERMANENT STANDARD: Display Order
            $table->integer('display_order')->default(0)->comment('Sıralama (Context7: order → display_order)');

            // ✅ CONTEXT7 PERMANENT STANDARD: Status field MUST be TINYINT(1) boolean
            $table->tinyInteger('aktiflik_durumu')->default(1)->comment('0=inactive, 1=active (Context7 canonical)');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_notifications');
    }
};
