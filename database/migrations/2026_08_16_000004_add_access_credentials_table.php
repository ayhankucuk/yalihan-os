<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHECKIN_CHECKOUT Wave 2 — Guest Arrival Readiness
 *
 * Migration: access_credentials table
 * Stores encrypted property access credentials (key, code, lockbox, smart_lock).
 *
 * INV-W2-S2: credential_value stored encrypted via Laravel Crypt
 * INV-W2-S1: credential_value NEVER appears in logs (masked at service layer)
 * INV-W2-T2: tenant_id is the isolation root
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('access_credentials')) {
            Schema::create('access_credentials', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('ilan_id');

                // Credential type
                $table->string('credential_type'); // 'key' | 'code' | 'lockbox' | 'smart_lock'

                // credential_value is encrypted at application layer before storage.
                // The DB column holds the encrypted string. NEVER log this field.
                $table->text('credential_value');

                // credential_location: physical location hint (e.g. "lockbox under mat")
                // Also encrypted since it may reveal security-sensitive information.
                $table->text('credential_location')->nullable();

                // Safety metadata
                $table->boolean('is_active')->default(true);
                $table->boolean('requires_reset')->default(false);
                $table->date('last_reset_at')->nullable();
                $table->date('expires_at')->nullable();

                // Audit
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['tenant_id', 'ilan_id', 'is_active']);

                // Foreign keys
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('cascade');

                $table->foreign('ilan_id')
                    ->references('id')
                    ->on('ilanlar')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('access_credentials');
    }
};
