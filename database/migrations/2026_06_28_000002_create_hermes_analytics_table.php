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
        Schema::create('hermes_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 100)->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->date('date')->index();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->double('avg_duration_ms', 8, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Composite indexes for analytics queries
            $table->index(['event_name', 'tenant_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hermes_analytics');
    }
};
