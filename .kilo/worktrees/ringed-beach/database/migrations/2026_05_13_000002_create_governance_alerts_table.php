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
        Schema::create('governance_alerts', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('tip')->index(); // Context7: type → tip (canonical naming)
            $blueprint->json('data')->nullable();
            $blueprint->string('severity')->default('medium');
            $blueprint->boolean('acknowledged')->default(false)->index();
            $blueprint->timestamp('acknowledged_at')->nullable();
            $blueprint->unsignedBigInteger('acknowledged_by')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['tip', 'created_at']); // Context7: type → tip
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governance_alerts');
    }
};
