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
        foreach (Schema::getForeignKeys('ilceler') as $foreign) {
            if (in_array('il_id', $foreign['columns'], true)) {
                if ($foreign['columns'] === ['il_id']
                    && $foreign['foreign_table'] === 'iller'
                    && $foreign['foreign_columns'] === ['id']
                    && in_array(strtolower($foreign['on_delete']), ['restrict', 'no action'], true)
                    && in_array(strtolower($foreign['on_update']), ['restrict', 'no action'], true)) {
                    return;
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
