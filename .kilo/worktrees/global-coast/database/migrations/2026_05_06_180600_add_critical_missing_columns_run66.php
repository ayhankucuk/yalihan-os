<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Critical missing columns for Run #66
     */
    public function up(): void
    {
        // ai_provider_decisions table - provider column (NOT NULL)
        if (Schema::hasTable('ai_provider_decisions') && !Schema::hasColumn('ai_provider_decisions', 'provider')) {
            Schema::table('ai_provider_decisions', function (Blueprint $table) {
                $table->string('provider', 50)->nullable();
            });
        }

        // property_reservations table
        if (Schema::hasTable('property_reservations')) {
            if (!Schema::hasColumn('property_reservations', 'created_by_user_id')) {
                Schema::table('property_reservations', function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by_user_id')->nullable();
                });
            }
            if (!Schema::hasColumn('property_reservations', 'confirmed_at')) {
                Schema::table('property_reservations', function (Blueprint $table) {
                    $table->timestamp('confirmed_at')->nullable();
                });
            }
            if (!Schema::hasColumn('property_reservations', 'reservation_state')) {
                Schema::table('property_reservations', function (Blueprint $table) {
                    $table->string('reservation_state', 30)->default('pending');
                });
            }
        }

        // property_seasonal_rates table
        if (Schema::hasTable('property_seasonal_rates')) {
            if (!Schema::hasColumn('property_seasonal_rates', 'currency')) {
                Schema::table('property_seasonal_rates', function (Blueprint $table) {
                    $table->string('currency', 10)->default('TRY');
                });
            }
            if (!Schema::hasColumn('property_seasonal_rates', 'aktiflik_durumu')) {
                Schema::table('property_seasonal_rates', function (Blueprint $table) {
                    $table->boolean('aktiflik_durumu')->default(true);
                });
            }
        }

        // ai_optimization_runs table
        if (Schema::hasTable('ai_optimization_runs')) {
            if (!Schema::hasColumn('ai_optimization_runs', 'window')) {
                Schema::table('ai_optimization_runs', function (Blueprint $table) {
                    $table->string('window', 20)->default('7d');
                });
            }
            if (!Schema::hasColumn('ai_optimization_runs', 'executed_by')) {
                Schema::table('ai_optimization_runs', function (Blueprint $table) {
                    $table->string('executed_by', 50)->default('system');
                });
            }
            if (!Schema::hasColumn('ai_optimization_runs', 'started_at')) {
                Schema::table('ai_optimization_runs', function (Blueprint $table) {
                    $table->timestamp('started_at')->nullable();
                });
            }
            if (!Schema::hasColumn('ai_optimization_runs', 'ended_at')) {
                Schema::table('ai_optimization_runs', function (Blueprint $table) {
                    $table->timestamp('ended_at')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ai_provider_decisions', 'provider')) {
            Schema::table('ai_provider_decisions', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }

        if (Schema::hasTable('property_reservations')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $columns = ['created_by_user_id', 'confirmed_at', 'reservation_state'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('property_reservations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('property_seasonal_rates')) {
            Schema::table('property_seasonal_rates', function (Blueprint $table) {
                $columns = ['currency', 'aktiflik_durumu'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('property_seasonal_rates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('ai_optimization_runs')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $columns = ['window', 'executed_by', 'started_at', 'ended_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ai_optimization_runs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
