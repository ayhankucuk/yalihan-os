<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('communication_id')
                ->unique()
                ->comment('Canonical identity: unique per communication thread');
            $table->string('channel', 50)
                ->comment('telegram, whatsapp, instagram, email, web');
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index()
                ->comment('Owner tenant — resolved from Communication.communicable');
            $table->unsignedBigInteger('ulke_id')
                ->nullable()
                ->index()
                ->comment('Owner country — resolved from Communication.communicable');
            $table->boolean('aktiflik_durumu')
                ->default(true)
                ->comment('Conversation active status');
            $table->json('metadata')
                ->nullable()
                ->comment('Additional conversation metadata');
            $table->timestamps();

            // FK to canonical communication — cascade delete keeps referential integrity
            $table->foreign('communication_id')
                ->references('id')
                ->on('communications')
                ->onDelete('cascade');

            // Indexes for polymorphic lookups by tenant/country
            $table->index(['tenant_id', 'aktiflik_durumu']);
            $table->index(['ulke_id', 'aktiflik_durumu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
