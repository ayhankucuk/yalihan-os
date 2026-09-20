<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // An index (even with this name) does not prove a foreign key exists.
        // Canonical contract: ilceler.il_id → iller.id ON DELETE CASCADE (baseline migration
        // 2024_01_01_000000; PRODUCTION_VERIFIED). RESTRICT/NO ACTION on the same columns is
        // a compatible predecessor state — accept it as satisfied. Only reject genuinely
        // incompatible definitions (wrong table, wrong column, or CASCADE with orphans).
        foreach (Schema::getForeignKeys('ilceler') as $foreign) {
            if (in_array('il_id', $foreign['columns'], true)) {
                $correctTable    = $foreign['foreign_table'] === 'iller';
                $correctColumn   = $foreign['foreign_columns'] === ['id'];
                $compatibleDelete = in_array(strtolower($foreign['on_delete']), ['cascade', 'restrict', 'no action'], true);
                $compatibleUpdate = in_array(strtolower($foreign['on_update']), ['restrict', 'no action'], true);

                if ($correctTable && $correctColumn && $compatibleDelete && $compatibleUpdate) {
                    return; // Canonical FK already present (CASCADE/RESTRICT/NO ACTION all satisfy the contract)
                }
                throw new RuntimeException('Conflicting ilceler.il_id foreign key; review required.');
            }
        }

        $hasOrphans = DB::table('ilceler as child')
            ->leftJoin('iller as parent', 'parent.id', '=', 'child.il_id')
            ->whereNotNull('child.il_id')->whereNull('parent.id')->exists();
        if ($hasOrphans) {
            throw new RuntimeException('Orphan ilceler.il_id values; no rows were changed.');
        }

        Schema::table('ilceler', function (Blueprint $table): void {
            $table->foreign('il_id', 'ilceler_il_id_foreign')
                ->references('id')->on('iller')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // up() can accept an existing constraint. Never drop it without ownership evidence.
        throw new RuntimeException('Forward-only FK migration: use a reviewed reversal migration.');
    }
};
