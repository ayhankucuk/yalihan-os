<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('governance_events', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('metric')->index();
            $blueprint->json('tags')->nullable();
            $blueprint->unsignedBigInteger('tenant_id')->nullable()->index();
            $blueprint->string('operation_type')->nullable();
            $blueprint->string('severity')->default('info');
            $blueprint->boolean('is_violation')->default(false)->index();
            $blueprint->timestamp('occurred_at')->useCurrent()->index();
            $blueprint->timestamps();

            // Composite index for quick violation scanning
            $blueprint->index(['is_violation', 'occurred_at']);
            $blueprint->index(['tenant_id', 'is_violation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governance_events');
    }
};
