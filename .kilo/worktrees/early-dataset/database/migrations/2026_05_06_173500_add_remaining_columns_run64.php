<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Remaining columns for Run #64
     */
    public function up(): void
    {
        // ilanlar table
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'kategori_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('kategori_id')->nullable()->after('id');
            });
        }

        // talepler table - multiple columns
        if (Schema::hasTable('talepler')) {
            if (!Schema::hasColumn('talepler', 'notlar')) {
                Schema::table('talepler', function (Blueprint $table) {
                    $table->text('notlar')->nullable();
                });
            }
            if (!Schema::hasColumn('talepler', 'ilce_id')) {
                Schema::table('talepler', function (Blueprint $table) {
                    $table->unsignedBigInteger('ilce_id')->nullable();
                });
            }
        }

        // ups_templates table
        if (Schema::hasTable('ups_templates')) {
            if (!Schema::hasColumn('ups_templates', 'sealed_at')) {
                Schema::table('ups_templates', function (Blueprint $table) {
                    $table->timestamp('sealed_at')->nullable();
                });
            }
            if (!Schema::hasColumn('ups_templates', 'sealed_by_user_id')) {
                Schema::table('ups_templates', function (Blueprint $table) {
                    $table->unsignedBigInteger('sealed_by_user_id')->nullable();
                });
            }
        }

        // tenants table
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'durum')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('durum', 50)->default('active');
            });
        }

        // ai_optimization_runs table
        if (Schema::hasTable('ai_optimization_runs') && !Schema::hasColumn('ai_optimization_runs', 'changed_count')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $table->integer('changed_count')->default(0);
            });
        }

        // ai_provider_decisions table
        if (Schema::hasTable('ai_provider_decisions') && !Schema::hasColumn('ai_provider_decisions', 'correlation_id')) {
            Schema::table('ai_provider_decisions', function (Blueprint $table) {
                $table->string('correlation_id', 100)->nullable();
            });
        }

        // saved_searches table
        if (Schema::hasTable('saved_searches') && !Schema::hasColumn('saved_searches', 'notification_frequency')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->string('notification_frequency', 20)->default('off');
            });
        }

        // property_reservations table
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'guest_count')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->integer('guest_count')->default(1);
            });
        }

        // copilot_action_logs table
        if (Schema::hasTable('copilot_action_logs') && !Schema::hasColumn('copilot_action_logs', 'aksiyon_durumu')) {
            Schema::table('copilot_action_logs', function (Blueprint $table) {
                $table->string('aksiyon_durumu', 30)->default('preview');
            });
        }

        // user_devices table - device_token NOT NULL constraint
        if (Schema::hasTable('user_devices') && !Schema::hasColumn('user_devices', 'device_token')) {
            Schema::table('user_devices', function (Blueprint $table) {
                $table->string('device_token')->nullable();
            });
        }

        // ref_sequences table
        if (Schema::hasTable('ref_sequences') && !Schema::hasColumn('ref_sequences', 'last_sequence')) {
            Schema::table('ref_sequences', function (Blueprint $table) {
                $table->integer('last_sequence')->default(0);
            });
        }

        // property_seasonal_rates table
        if (Schema::hasTable('property_seasonal_rates') && !Schema::hasColumn('property_seasonal_rates', 'nightly_rate')) {
            Schema::table('property_seasonal_rates', function (Blueprint $table) {
                $table->decimal('nightly_rate', 10, 2)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ilanlar', 'kategori_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('kategori_id');
            });
        }

        if (Schema::hasTable('talepler')) {
            Schema::table('talepler', function (Blueprint $table) {
                $columns = ['notlar', 'ilce_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('talepler', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('ups_templates')) {
            Schema::table('ups_templates', function (Blueprint $table) {
                $columns = ['sealed_at', 'sealed_by_user_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ups_templates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasColumn('tenants', 'durum')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('durum');
            });
        }

        if (Schema::hasColumn('ai_optimization_runs', 'changed_count')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $table->dropColumn('changed_count');
            });
        }

        if (Schema::hasColumn('ai_provider_decisions', 'correlation_id')) {
            Schema::table('ai_provider_decisions', function (Blueprint $table) {
                $table->dropColumn('correlation_id');
            });
        }

        if (Schema::hasColumn('saved_searches', 'notification_frequency')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->dropColumn('notification_frequency');
            });
        }

        if (Schema::hasColumn('property_reservations', 'guest_count')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropColumn('guest_count');
            });
        }

        if (Schema::hasColumn('copilot_action_logs', 'aksiyon_durumu')) {
            Schema::table('copilot_action_logs', function (Blueprint $table) {
                $table->dropColumn('aksiyon_durumu');
            });
        }

        if (Schema::hasColumn('user_devices', 'device_token')) {
            Schema::table('user_devices', function (Blueprint $table) {
                $table->dropColumn('device_token');
            });
        }

        if (Schema::hasColumn('ref_sequences', 'last_sequence')) {
            Schema::table('ref_sequences', function (Blueprint $table) {
                $table->dropColumn('last_sequence');
            });
        }

        if (Schema::hasColumn('property_seasonal_rates', 'nightly_rate')) {
            Schema::table('property_seasonal_rates', function (Blueprint $table) {
                $table->dropColumn('nightly_rate');
            });
        }
    }
};
