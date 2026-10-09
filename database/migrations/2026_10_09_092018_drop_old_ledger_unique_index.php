<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ledger_balances')) {
            return;
        }

        // Sadece eski tenant-siz unique index'i kaldir
        // Yeni tenant-aware index zaten mevcut olabilir
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS ledger_balances_account_id_currency_unique');
            } else {
                Schema::table('ledger_balances', function (Blueprint $table) {
                    $table->dropUnique('ledger_balances_account_id_currency_unique');
                });
            }
        } catch (\Exception $e) {
            // Index yoksa hata yoksay
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ledger_balances')) {
            return;
        }

        Schema::table('ledger_balances', function (Blueprint $table) {
            $table->unique(['account_id', 'currency'], 'ledger_balances_account_id_currency_unique');
        });
    }
};
