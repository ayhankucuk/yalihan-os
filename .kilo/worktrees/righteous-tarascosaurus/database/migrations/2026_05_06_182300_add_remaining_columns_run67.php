<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Remaining columns for Run #67
     */
    public function up(): void
    {
        // talepler table
        if (Schema::hasTable('talepler')) {
            if (!Schema::hasColumn('talepler', 'alt_kategori_id')) {
                Schema::table('talepler', function (Blueprint $table) {
                    $table->unsignedBigInteger('alt_kategori_id')->nullable();
                });
            }
            if (!Schema::hasColumn('talepler', 'mahalle_id')) {
                Schema::table('talepler', function (Blueprint $table) {
                    $table->unsignedBigInteger('mahalle_id')->nullable();
                });
            }
        }

        // property_reservations - guest_count NOT NULL issue
        // This column was added in previous migration but seems to have NOT NULL constraint
        // Let's ensure it's nullable
        if (Schema::hasTable('property_reservations') && Schema::hasColumn('property_reservations', 'guest_count')) {
            // Column exists, check if we need to modify it
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->integer('guest_count')->nullable()->default(1)->change();
            });
        }

        // saved_searches - filters NOT NULL issue
        // This column was added in previous migration but has NOT NULL constraint
        if (Schema::hasTable('saved_searches') && Schema::hasColumn('saved_searches', 'filters')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->json('filters')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('talepler')) {
            Schema::table('talepler', function (Blueprint $table) {
                $columns = ['alt_kategori_id', 'mahalle_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('talepler', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Note: We don't reverse the nullable changes as they fix NOT NULL constraints
    }
};
