<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Final missing columns for Run #65
     */
    public function up(): void
    {
        // yayin_tipi_sablonlari table
        if (Schema::hasTable('yayin_tipi_sablonlari') && !Schema::hasColumn('yayin_tipi_sablonlari', 'ups_template_id')) {
            Schema::table('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->unsignedBigInteger('ups_template_id')->nullable();
            });
        }

        // talepler table
        if (Schema::hasTable('talepler') && !Schema::hasColumn('talepler', 'oncelik')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->string('oncelik', 20)->default('normal');
            });
        }

        // ref_sequences table
        if (Schema::hasTable('ref_sequences') && !Schema::hasColumn('ref_sequences', 'year')) {
            Schema::table('ref_sequences', function (Blueprint $table) {
                $table->integer('year')->nullable();
            });
        }

        // property_reservations table
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'notes')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        // property_seasonal_rates table
        if (Schema::hasTable('property_seasonal_rates')) {
            if (!Schema::hasColumn('property_seasonal_rates', 'season_label')) {
                Schema::table('property_seasonal_rates', function (Blueprint $table) {
                    $table->string('season_label', 100)->nullable();
                });
            }
            if (!Schema::hasColumn('property_seasonal_rates', 'min_stay_override')) {
                Schema::table('property_seasonal_rates', function (Blueprint $table) {
                    $table->integer('min_stay_override')->nullable();
                });
            }
        }

        // ai_optimization_runs table
        if (Schema::hasTable('ai_optimization_runs') && !Schema::hasColumn('ai_optimization_runs', 'diff_json')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $table->json('diff_json')->nullable();
            });
        }

        // ai_provider_decisions table
        if (Schema::hasTable('ai_provider_decisions')) {
            if (!Schema::hasColumn('ai_provider_decisions', 'kategori_id')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->unsignedBigInteger('kategori_id')->nullable();
                });
            }
            if (!Schema::hasColumn('ai_provider_decisions', 'yayin_tipi_id')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                });
            }
            if (!Schema::hasColumn('ai_provider_decisions', 'chosen_provider')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->string('chosen_provider', 50)->nullable();
                });
            }
            if (!Schema::hasColumn('ai_provider_decisions', 'scores_json')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->json('scores_json')->nullable();
                });
            }
            if (!Schema::hasColumn('ai_provider_decisions', 'reason_json')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->json('reason_json')->nullable();
                });
            }
            if (!Schema::hasColumn('ai_provider_decisions', 'debug_metadata')) {
                Schema::table('ai_provider_decisions', function (Blueprint $table) {
                    $table->json('debug_metadata')->nullable();
                });
            }
        }

        // copilot_action_logs table
        if (Schema::hasTable('copilot_action_logs')) {
            if (!Schema::hasColumn('copilot_action_logs', 'applied_at')) {
                Schema::table('copilot_action_logs', function (Blueprint $table) {
                    $table->timestamp('applied_at')->nullable();
                });
            }
            if (!Schema::hasColumn('copilot_action_logs', 'diff_snapshot')) {
                Schema::table('copilot_action_logs', function (Blueprint $table) {
                    $table->json('diff_snapshot')->nullable();
                });
            }
        }

        // saved_searches table - filters column (NOT NULL constraint)
        if (Schema::hasTable('saved_searches') && !Schema::hasColumn('saved_searches', 'filters')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->json('filters')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('yayin_tipi_sablonlari', 'ups_template_id')) {
            Schema::table('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->dropColumn('ups_template_id');
            });
        }

        if (Schema::hasColumn('talepler', 'oncelik')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->dropColumn('oncelik');
            });
        }

        if (Schema::hasColumn('ref_sequences', 'year')) {
            Schema::table('ref_sequences', function (Blueprint $table) {
                $table->dropColumn('year');
            });
        }

        if (Schema::hasColumn('property_reservations', 'notes')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }

        if (Schema::hasTable('property_seasonal_rates')) {
            Schema::table('property_seasonal_rates', function (Blueprint $table) {
                $columns = ['season_label', 'min_stay_override'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('property_seasonal_rates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasColumn('ai_optimization_runs', 'diff_json')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $table->dropColumn('diff_json');
            });
        }

        if (Schema::hasTable('ai_provider_decisions')) {
            Schema::table('ai_provider_decisions', function (Blueprint $table) {
                $columns = ['kategori_id', 'yayin_tipi_id', 'chosen_provider', 'scores_json', 'reason_json', 'debug_metadata'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ai_provider_decisions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('copilot_action_logs')) {
            Schema::table('copilot_action_logs', function (Blueprint $table) {
                $columns = ['applied_at', 'diff_snapshot'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('copilot_action_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasColumn('saved_searches', 'filters')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->dropColumn('filters');
            });
        }
    }
};
