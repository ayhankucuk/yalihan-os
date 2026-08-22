<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C3.4: Production Schema Convergence for property_reservations.
     *
     * Reconciles Money Core canonical financial fields from mysql-schema.sql
     * into environments provisioned via standard migration history.
     *
     * All column additions are guarded with !Schema::hasColumn() for idempotency.
     * Does NOT touch, rename, or alias existing operational status/reservation_state columns.
     */
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('property_reservations', 'finansal_durum')) {
                $table->string('finansal_durum', 30)->default('pending');
                $table->index('finansal_durum', 'idx_reservations_finansal');
            }

            if (! Schema::hasColumn('property_reservations', 'currency')) {
                $table->string('currency', 3)->default('TRY');
            }

            if (! Schema::hasColumn('property_reservations', 'depozito_tutari')) {
                $table->decimal('depozito_tutari', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('property_reservations', 'depozito_durumu')) {
                $table->string('depozito_durumu', 30)->nullable();
            }

            if (! Schema::hasColumn('property_reservations', 'locked_nightly_rate')) {
                $table->decimal('locked_nightly_rate', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('property_reservations', 'booking_currency')) {
                $table->string('booking_currency', 3)->default('TRY');
            }

            if (! Schema::hasColumn('property_reservations', 'booking_fx_rate')) {
                $table->decimal('booking_fx_rate', 15, 6)->nullable();
            }

            if (! Schema::hasColumn('property_reservations', 'booking_country_code')) {
                $table->string('booking_country_code', 2)->default('TR');
            }

            if (! Schema::hasColumn('property_reservations', 'ulke_id')) {
                $table->unsignedBigInteger('ulke_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('property_reservations', 'finansal_durum')) {
                $table->dropIndex('idx_reservations_finansal');
                $table->dropColumn('finansal_durum');
            }

            $columns = [
                'currency',
                'depozito_tutari',
                'depozito_durumu',
                'locked_nightly_rate',
                'booking_currency',
                'booking_fx_rate',
                'booking_country_code',
                'ulke_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('property_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
