<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sprint 11 M2: Property Runtime — Listing Aggregate
     *
     * Relationship: Property 1:N Listing (NOT 1:1)
     *
     * Composite unique: (property_id, kanal) prevents duplicate channel per Property.
     * Example: same Property can have Yalıhan, Sahibinden, Airbnb listings.
     *
     * Idempotent: safe to run on fresh and existing databases.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ilanlar')) {
            return;
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            // Add columns if missing
            if (! Schema::hasColumn('ilanlar', 'property_id')) {
                $table->unsignedBigInteger('property_id')
                    ->nullable()
                    ->after('id');
            }

            if (! Schema::hasColumn('ilanlar', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')
                    ->nullable()
                    ->after('property_id');
            }

            if (! Schema::hasColumn('ilanlar', 'idempotency_key')) {
                $table->string('idempotency_key', 64)
                    ->nullable()
                    ->unique()
                    ->after('slug');
            }

            if (! Schema::hasColumn('ilanlar', 'kanal')) {
                $table->string('kanal', 32)
                    ->nullable()
                    ->after('yayin_durumu');
            }

            // Indexes
            if (! Schema::hasColumn('ilanlar', 'property_id')
                || ! $this->indexExists('ilanlar', 'ilanlar_property_id_idx')) {
                $table->index(['property_id'], 'ilanlar_property_id_idx');
            }

            if (! Schema::hasIndex('ilanlar', ['tenant_id', 'workspace_id'])) {
                $table->index(['tenant_id', 'workspace_id'], 'ilanlar_tenant_workspace_idx');
            }

            // Remove incorrect UNIQUE(property_id) if it exists and add composite
            $this->dropUniquePropertyIdIfExists();

            if (! Schema::hasIndex('ilanlar', ['property_id', 'kanal'])) {
                $table->unique(['property_id', 'kanal'], 'ilanlar_property_kanal_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ilanlar')) {
            return;
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            if (Schema::hasIndex('ilanlar', ['property_id', 'kanal'])) {
                $table->dropUnique(['property_id', 'kanal']);
            }
            if (Schema::hasIndex('ilanlar', ['tenant_id', 'workspace_id'])) {
                $table->dropIndex(['tenant_id', 'workspace_id']);
            }
            if (Schema::hasIndex('ilanlar', ['property_id'])) {
                $table->dropIndex(['property_id']);
            }
            if (Schema::hasColumn('ilanlar', 'kanal')) {
                $table->dropColumn('kanal');
            }
            if (Schema::hasColumn('ilanlar', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('ilanlar', 'workspace_id')) {
                $table->dropColumn('workspace_id');
            }
            if (Schema::hasColumn('ilanlar', 'property_id')) {
                $table->dropColumn('property_id');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select(
                DB::raw("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?"),
                [$table, $index]
            );
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Drop the incorrect UNIQUE(property_id) index if it exists.
     */
    protected function dropUniquePropertyIdIfExists(): void
    {
        try {
            $indexes = DB::select(
                DB::raw("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ilanlar' AND sql LIKE '%property_id%' AND sql LIKE '%UNIQUE%'")
            );
            foreach ($indexes as $idx) {
                DB::statement("DROP INDEX IF EXISTS {$idx->name}");
            }
        } catch (\Throwable) {
            // SQLite in test — safe to ignore
        }
    }
};
