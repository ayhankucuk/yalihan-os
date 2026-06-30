<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_learning_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->string('transaction_type')->index();
            $table->string('module')->index();
            $table->string('action');
            // Polymorphic relation
            $table->string('related_type')->nullable()->index();
            $table->unsignedBigInteger('related_id')->nullable()->index();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->json('context')->nullable();
            $table->boolean('success')->default(true)->index();
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->json('learned_patterns')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('executed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module', 'action', 'success']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_learning_transactions');
    }
};
