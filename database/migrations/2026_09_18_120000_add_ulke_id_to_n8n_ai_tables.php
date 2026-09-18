<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REMEDIATION_N8N_AI_COUNTRY_OWNERSHIP_01
 *
 * Adds nullable ulke_id to the three N8n AI persistence tables.
 *
 * Authority chain for ulke_id ownership:
 *   - ilanTaslagi:    User(danisman_id).ulke_id OR Ilan(ilan_id).ulke_id
 *   - sozlesmeTaslagi: Ilan(property_id).ulke_id OR Kisi(kisi_id).ulke_id
 *   - mesajTaslagi:  Communication.communicable.ulke_id (polymorphic)
 *
 * HasCountryScope::creating() requires Auth::check() → fails for n8n webhooks.
 * Therefore these UseCases must pass ulke_id explicitly.
 *
 * Migration is additive-only. All three columns default NULL.
 * Fail-closed: UseCases throw CountryOwnershipUnresolvableException if
 * canonical country ownership cannot be resolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ai_messages — ulke_id nullable
        // Note: hasTable() may return false in SQLite when tables are loaded
        // from mysql-schema.sql. We guard only on hasColumn() for idempotency.
        if (!Schema::hasColumn('ai_messages', 'ulke_id')) {
            Schema::table('ai_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('ulke_id')->nullable()
                    ->index()
                    ->after('tenant_id');
            });
        }

        // ai_contract_drafts — ulke_id nullable
        if (!Schema::hasColumn('ai_contract_drafts', 'ulke_id')) {
            Schema::table('ai_contract_drafts', function (Blueprint $table) {
                $table->unsignedBigInteger('ulke_id')->nullable()
                    ->index()
                    ->after('tenant_id');
            });
        }

        // ai_ilan_taslaklari — ulke_id nullable
        if (!Schema::hasColumn('ai_ilan_taslaklari', 'ulke_id')) {
            Schema::table('ai_ilan_taslaklari', function (Blueprint $table) {
                $table->unsignedBigInteger('ulke_id')->nullable()
                    ->index()
                    ->after('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ai_ilan_taslaklari', function (Blueprint $table) {
            $table->dropIndex(['ulke_id']);
            $table->dropColumn('ulke_id');
        });

        Schema::table('ai_contract_drafts', function (Blueprint $table) {
            $table->dropIndex(['ulke_id']);
            $table->dropColumn('ulke_id');
        });

        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropIndex(['ulke_id']);
            $table->dropColumn('ulke_id');
        });
    }
};
