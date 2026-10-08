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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('il_id')->nullable()->constrained('iller')->nullOnDelete();
            $table->foreignId('ilce_id')->nullable()->constrained('ilceler')->nullOnDelete();
            $table->foreignId('mahalle_id')->nullable()->constrained('mahalleler')->nullOnDelete();
            $table->string('address', 500)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('aktiflik_durumu')->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'il_id']);
            $table->index(['tenant_id', 'ilce_id']);
            $table->index(['tenant_id', 'aktiflik_durumu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
