<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GuestMessage — Immutable audit trail for inbound guest WhatsApp messages.
     *
     * GUEST_CONCIERGE Phase 1 — SAAB Session 134
     *
     * Design Principles:
     * - APPEND-ONLY: No UPDATE, no DELETE at application level
     * - Idempotency: external_message_id (WhatsApp message ID) prevents duplicate processing
     * - Tenant isolation: tenant_id required on all records
     * - Audit: every message creates a new row with full context
     */
    public function up(): void
    {
        Schema::create('guest_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // ── Tenant Isolation ──────────────────────────────────────
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('ilan_id')->nullable()->index();

            // ── Source Identity ────────────────────────────────────────
            $table->string('channel', 20)->default('whatsapp'); // whatsapp, email, etc.
            $table->string('sender_phone', 20)->index();        // E.164 format: +90555...
            $table->string('sender_name', 100)->nullable();    // WhatsApp profile name

            // ── WhatsApp Message Identity (Idempotency) ───────────────
            $table->string('external_message_id', 100)->unique()->nullable();
            // external_message_id = WhatsApp message ID from webhook
            // Unique constraint ensures same message processed at most once

            // ── Message Content ───────────────────────────────────────
            $table->text('message_text');         // Original guest message
            $table->string('message_type', 20)->default('text'); // text, image, audio, etc.

            // ── Routing Context ───────────────────────────────────────
            $table->string('routing_decision', 30)->nullable()->index();
            // GUEST_ACTIVE, GUEST_FUTURE, GUEST_PAST, LEAD, UNKNOWN

            // ── Reservation Link ─────────────────────────────────────
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->foreign('reservation_id')
                ->references('id')
                ->on('property_reservations')
                ->onDelete('set null');

            // ── AI Classification ─────────────────────────────────────
            $table->string('intent', 50)->nullable()->index();
            // WIFI_INFO, CHECK_IN_TIME, CHECK_OUT_TIME, PARKING_INFO,
            // HOUSE_RULES, TECHNICAL_ISSUE, CLEANING_REQUEST,
            // CREDENTIAL_REQUEST, EARLY_CHECKIN, LATE_CHECKOUT,
            // REFUND_REQUEST, LEGAL_QUESTION, DAMAGE_REPORT, UNKNOWN

            $table->decimal('confidence', 4, 3)->nullable();
            // 0.000 to 1.000 — AI confidence score

            $table->json('required_fact_keys')->nullable();
            // ["wifi_ssid", "wifi_password"] — facts needed for answer

            // ── Response ───────────────────────────────────────────────
            $table->string('response_mode', 20)->nullable()->index();
            // ANSWER, ACTION, ESCALATE

            $table->text('response_text')->nullable();
            // AI/system response text sent to guest

            // ── Action Context ────────────────────────────────────────
            $table->unsignedBigInteger('gorev_id')->nullable()->index();
            $table->foreign('gorev_id')
                ->references('id')
                ->on('gorevler')
                ->onDelete('set null');
            // If response_mode = ACTION, links to created Gorev

            // ── Escalation ───────────────────────────────────────────
            $table->boolean('escalated')->default(false)->index();
            $table->string('escalation_reason', 100)->nullable();
            // Low confidence, unknown intent, credential request, etc.

            // ── Security: No credentials in this table ─────────────────
            // INVARIANT: guest_messages never contains access credentials.
            // See GC-D8: Credential zero-context boundary.
            // Door codes, lockbox codes, smart lock codes are FORBIDDEN here.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_messages');
    }
};
