<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type')->comment('İlgili model sınıfı (örn. App\Models\PropertyConfigVersion)');
            $table->unsignedBigInteger('entity_id')->comment('Etkilenen modelin ID\'si');

            $table->string('action_type', 100)->comment('Örn. DRAFT_CREATED, PROMOTED');
            $table->string('from_state', 50)->nullable()->comment('Önceki durumu');
            $table->string('to_state', 50)->nullable()->comment('Yeni durumu');

            $table->unsignedBigInteger('actor_id')->nullable()->comment('İşlemi yapan User ID, sistem işlemleri için NULL');
            $table->string('correlation_id')->nullable()->comment('İzlenebilirlik için istek/process ID');

            $table->text('reason')->nullable()->comment('Görevin nedeni veya açıklaması');
            $table->json('payload_snapshot')->nullable()->comment('Değişiklik anındaki metadata / diff base');

            $table->timestamps();

            // Indexes for performance
            $table->index(['entity_type', 'entity_id'], 'idx_gov_audit_entity');
            $table->index('correlation_id', 'idx_gov_audit_corr_id');
            $table->index('actor_id', 'idx_gov_audit_actor_id');
            $table->index('created_at', 'idx_gov_audit_created_at');

            // Foreign key
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_audit_logs');
    }
};
