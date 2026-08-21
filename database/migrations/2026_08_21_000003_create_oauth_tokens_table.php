<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50)->comment('gmail, google-drive, vs.');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('encrypted_token')->comment('Crypt::encryptString ile sifreli');
            $table->string('token_type', 20)->default('refresh_token');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['service', 'tenant_id'], 'oauth_tokens_service_tenant_unique');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
