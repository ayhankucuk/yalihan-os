<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'deepseek_model')
            ->where('value', 'deepseek-chat')
            ->update(['value' => 'deepseek-v4-flash']);

        DB::table('settings')
            ->where('key', 'deepseek_model')
            ->where('value', 'deepseek-coder')
            ->update(['value' => 'deepseek-v4-pro']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
