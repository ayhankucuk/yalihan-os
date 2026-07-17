<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 12C: Workspace → Property Canonical Ownership
 *
 * SAAB Resolution: S12C-001
 *
 * This migration:
 * 1. Adds property_id column (if not exists)
 * 2. Adds UNIQUE constraint for 1:1 cardinality
 * 3. Adds FK constraint (ON DELETE RESTRICT)
 * 4. Removes ilan_id column (legacy cleanup)
 *
 * IMPORTANT: Run AFTER create_properties_table migration
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('property_workspaces')) {
            return;
        }

        // Step 1: Add property_id column (if not exists)
        if (!Schema::hasColumn('property_workspaces', 'property_id')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->unsignedBigInteger('property_id')->nullable()->after('tenant_id');
            });
            $this->log('Added property_id column');
        }

        // Step 2: Add UNIQUE constraint (only if empty or safe)
        if (Schema::hasColumn('property_workspaces', 'property_id')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                if (!$this->indexExists('property_workspaces', 'property_workspaces_property_id_unique')) {
                    $table->unique('property_id', 'property_workspaces_property_id_unique');
                }
            });
            $this->log('Added UNIQUE constraint');
        }

        // Step 3: Add FK constraint
        Schema::table('property_workspaces', function (Blueprint $table) {
            if (!$this->fkExists('property_workspaces', 'property_workspaces_property_id_foreign')) {
                $table->foreign('property_id')
                    ->references('id')
                    ->on('properties')
                    ->onDelete('restrict');
            }
        });
        $this->log('Added FK constraint');

        // Step 4: Remove ilan_id column (legacy cleanup)
        if (Schema::hasColumn('property_workspaces', 'ilan_id')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->dropColumn('ilan_id');
            });
            $this->log('Removed ilan_id column (legacy cleanup)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('property_workspaces')) {
            return;
        }

        // Re-add ilan_id for rollback
        if (!Schema::hasColumn('property_workspaces', 'ilan_id')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->unsignedBigInteger('ilan_id')->nullable()->after('tenant_id');
            });
        }

        // Remove FK
        if ($this->fkExists('property_workspaces', 'property_workspaces_property_id_foreign')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->dropForeign(['property_id']);
            });
        }

        // Remove UNIQUE
        if ($this->indexExists('property_workspaces', 'property_workspaces_property_id_unique')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->dropUnique('property_workspaces_property_id_unique');
            });
        }

        // Remove column
        if (Schema::hasColumn('property_workspaces', 'property_id')) {
            Schema::table('property_workspaces', function (Blueprint $table) {
                $table->dropColumn('property_id');
            });
        }
    }

    /**
     * Check if an index exists.
     */
    protected function indexExists(string $table, string $name): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$name]);
            return count($result) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if a foreign key exists.
     */
    protected function fkExists(string $table, string $name): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = 'property_id'");
            return count($result) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Log a message.
     */
    protected function log(string $message): void
    {
        \Illuminate\Support\Facades\Log::info("[Sprint12C] {$message}");
    }
};
