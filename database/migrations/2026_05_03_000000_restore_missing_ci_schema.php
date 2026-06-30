<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CI/CD Schema Restoration Migration
 *
 * Purpose: Restores missing columns and tables identified during CI test failures.
 * This ensures the test suite matches the application's domain expectations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Restore 'il' and 'ilce' columns to 'ilanlar' table if missing
        if (Schema::hasTable('ilanlar')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                if (!Schema::hasColumn('ilanlar', 'il')) {
                    $table->unsignedBigInteger('il')->nullable()->after('ana_kategori_id');
                }
                if (!Schema::hasColumn('ilanlar', 'ilce')) {
                    $table->string('ilce')->nullable()->after('ilce_id');
                }
                if (!Schema::hasColumn('ilanlar', 'yayin_durumu')) {
                    $table->string('yayin_durumu', 20)->default('taslak')->after('para_birimi');
                }
            });
        }

        // Restore 'copilot_action_logs' table if missing
        if (!Schema::hasTable('copilot_action_logs')) {
            Schema::create('copilot_action_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action_type')->nullable();
                $table->string('user_id')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'optimizer_suggestions' table if missing
        if (!Schema::hasTable('optimizer_suggestions')) {
            Schema::create('optimizer_suggestions', function (Blueprint $table) {
                $table->id();
                $table->string('suggestion_type');
                $table->string('target_rule');
                $table->string('status')->default('pending'); // ✅ Orthodox: status (was oneri_durumu)
                $table->float('confidence')->default(0);
                $table->text('reason')->nullable();
                $table->json('evidence')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'agent_memory' table if missing
        if (!Schema::hasTable('agent_memory')) {
            Schema::create('agent_memory', function (Blueprint $table) {
                $table->id();
                $table->string('agent_name');
                $table->string('memory_key');
                $table->string('memory_type');
                $table->json('memory_value');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['agent_name', 'memory_key']);
            });
        }

        // Restore 'governance_suppressions' table if missing
        if (!Schema::hasTable('governance_suppressions')) {
            Schema::create('governance_suppressions', function (Blueprint $table) {
                $table->id();
                $table->string('rule_key');
                $table->string('scope')->default('global');
                $table->string('source')->nullable();
                $table->string('domain')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('suppressed_by')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Restore 'ups_templates' table if missing
        if (!Schema::hasTable('ups_templates')) {
            Schema::create('ups_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('yayin_tipi_sablonu_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->unsignedBigInteger('active_junction_id')->nullable(); // Added for CI
                $table->json('template_json')->nullable();
                $table->integer('template_version')->default(1);
                $table->string('template_hash')->nullable();
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true); // ✅ Orthodox: is_active (was aktiflik_durumu)
                $table->timestamps();
                $table->softDeletes(); // ✅ Added missing soft deletes for Eloquent compatibility
            });
        }

        // Restore 'template_change_logs' table if missing
        if (!Schema::hasTable('template_change_logs')) {
            Schema::create('template_change_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ups_template_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('aksiyon_tipi');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('aciklama')->nullable();
                $table->unsignedBigInteger('feature_id')->nullable();
                $table->json('eski_degerler')->nullable();
                $table->json('yeni_degerler')->nullable();
                $table->integer('versiyon_numarasi')->default(0);
                $table->unsignedBigInteger('yayin_tipi_sablonu_id')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'ai_threshold_overrides' table if missing
        if (!Schema::hasTable('ai_threshold_overrides')) {
            Schema::create('ai_threshold_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->decimal('auto_apply_threshold', 4, 3);
                $table->decimal('suggest_threshold', 4, 3);
                $table->string('source')->default('continuous_optimization');
                $table->unsignedBigInteger('run_id')->nullable();
                $table->timestamp('calculated_at')->useCurrent();
                $table->timestamps();
            });
        }

        // Restore 'ai_esik_profilleri' table if missing
        if (!Schema::hasTable('ai_esik_profilleri')) {
            Schema::create('ai_esik_profilleri', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->string('saglayici', 40)->nullable();
                $table->unsignedInteger('min_ornek_sayisi')->default(50);
                $table->decimal('auto_apply_esigi', 4, 3);
                $table->decimal('suggest_esigi', 4, 3);
                $table->timestamps();
            });
        }

        // Restore 'ai_prompt_logs' table if missing
        if (!Schema::hasTable('ai_prompt_logs')) {
            Schema::create('ai_prompt_logs', function (Blueprint $table) {
                $table->id();
                $table->string('prompt_hash', 64)->unique();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('provider');
                $table->string('model');
                $table->integer('governance_score')->default(0);
                $table->boolean('has_violation')->default(false);
                $table->json('violations')->nullable();
                $table->longText('prompt_text');
                $table->longText('response_text')->nullable();
                $table->integer('duration_ms')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'ai_logs' missing column if exists
        if (Schema::hasTable('ai_logs') && !Schema::hasColumn('ai_logs', 'error_message')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('calisma_durumu');
            });
        }

        // Restore 'advisor_photos' table
        if (!Schema::hasTable('advisor_photos')) {
            Schema::create('advisor_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('path');
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Restore 'saved_searches' table
        if (!Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('name');
                $table->json('criteria');
                $table->string('notification_frequency')->default('instant');
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('last_notified_at')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('saved_searches', 'criteria')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->json('criteria')->after('name');
            });
        }

        // Restore projection tables for CQRS
        if (!Schema::hasTable('proj_listings')) {
            Schema::create('proj_listings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id')->index();
                $table->string('baslik');
                $table->string('yayin_durumu', 20)->default('Aktif');
                $table->tinyInteger('aktiflik_durumu')->default(1);
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->string('para_birimi', 10)->nullable();
                $table->unsignedBigInteger('danisman_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('il_id')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->integer('goruntulenme_sayisi')->default(0);
                $table->integer('favoriye_alinma_sayisi')->default(0);
                $table->integer('gecen_gun_sayisi')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('proj_event_offsets')) {
            Schema::create('proj_event_offsets', function (Blueprint $table) {
                $table->id();
                $table->string('projector_name');
                $table->string('event_id');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('proj_activity_stream')) {
            Schema::create('proj_activity_stream', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->timestamp('occurred_at')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->unsignedBigInteger('listing_id')->nullable();
                $table->string('type');
                $table->text('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('proj_agent_performance')) {
            Schema::create('proj_agent_performance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('danisman_id');
                $table->string('donem');
                $table->integer('basari_puani')->default(0);
                $table->integer('kapatilan_islem_sayisi')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('proj_kpi_snapshots')) {
            Schema::create('proj_kpi_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('danisman_id')->nullable();
                $table->date('tarih');
                $table->integer('aktif_ilan_sayisi')->default(0);
                $table->decimal('toplam_portfoy_degeri', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // Restore remaining missing columns and tables
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'mahalle')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->string('mahalle')->nullable()->after('ilce');
            });
        }

        if (!Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('device_id')->nullable(); // Added for CI
                $table->string('device_token');
                $table->string('fcm_token')->nullable(); // Added for CI
                $table->string('platform')->nullable(); // Added for CI
                $table->string('device_type')->nullable();
                $table->timestamp('last_active_at')->nullable(); // Added for CI
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('property_availabilities')) {
            Schema::create('property_availabilities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->date('date');
                $table->boolean('is_available')->default(true);
                $table->string('source_system')->default('internal'); // Added for CI
                $table->string('external_ref')->nullable(); // Added for CI
                $table->string('block_reason')->nullable(); // Added for CI
                $table->decimal('price', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('property_reservations')) {
            Schema::create('property_reservations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->unsignedBigInteger('guest_id')->nullable();
                $table->string('guest_name')->nullable();
                $table->string('guest_phone')->nullable(); // Added for CI
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('nights')->nullable();
                $table->string('reservation_state')->default('pending');
                $table->string('status')->default('pending');
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->decimal('total_price', 10, 2)->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (!Schema::hasColumn('property_reservations', 'guest_phone')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->string('guest_phone')->nullable()->after('guest_name');
            });
        }

        if (!Schema::hasTable('property_config_versions')) {
            Schema::create('property_config_versions', function (Blueprint $table) {
                $table->id();
                $table->string('version_hash')->nullable(); // Added for CI
                $table->text('description')->nullable(); // Added for CI
                $table->string('governance_state')->nullable(); // Added for CI
                $table->json('snapshot_json')->nullable(); // Added for CI
                $table->string('signature')->nullable(); // Added for CI
                $table->unsignedBigInteger('created_by')->nullable(); // Added for CI
                $table->unsignedBigInteger('property_id')->nullable();
                $table->integer('version')->default(1);
                $table->json('config_data')->nullable();
                $table->timestamps();
            });
        }

        // Restore missing columns to 'user_devices'
        if (Schema::hasTable('user_devices') && !Schema::hasColumn('user_devices', 'device_id')) {
            Schema::table('user_devices', function (Blueprint $table) {
                $table->string('device_id')->nullable()->after('device_token');
            });
        }

        if (!Schema::hasTable('ai_optimization_runs')) {
            Schema::create('ai_optimization_runs', function (Blueprint $table) {
                $table->id();
                $table->string('window')->default('daily');
                $table->string('status')->default('pending');
                $table->json('metrics')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('ai_optimization_runs', 'window')) {
            Schema::table('ai_optimization_runs', function (Blueprint $table) {
                $table->string('window')->default('daily')->after('id');
            });
        }

        if (!Schema::hasTable('prediction_snapshots')) {
            Schema::create('prediction_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('listing_id');
                $table->string('pricing_position')->nullable();
                $table->unsignedSmallInteger('pricing_score')->default(0);
                $table->unsignedSmallInteger('demand_score')->default(0);
                $table->string('demand_label')->nullable();
                $table->unsignedSmallInteger('confidence_score')->default(0);
                $table->string('confidence_label')->nullable();
                $table->string('opportunity_action')->nullable();
                $table->unsignedSmallInteger('opportunity_score')->default(0);
                $table->unsignedSmallInteger('priority_score')->default(0);
                $table->string('priority_label')->nullable();
                $table->decimal('current_price', 15, 2)->nullable();
                $table->decimal('benchmark_price', 15, 2)->nullable();
                $table->timestamp('snapshot_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('domain')->unique()->nullable();
                $table->string('status')->default('active'); // ✅ Orthodox: status (was durum)
                $table->uuid('uuid')->nullable(); // Added for CI
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes(); // Added for CI parity
            });
        } elseif (!Schema::hasColumn('tenants', 'deleted_at')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('ai_workspace_wallets')) {
            Schema::create('ai_workspace_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable();
                $table->decimal('balance', 15, 4)->default(0.0000);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('ai_provider_decisions')) {
            Schema::create('ai_provider_decisions', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->nullable();
                $table->string('decision')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('property_seasonal_rates')) {
            Schema::create('property_seasonal_rates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('rate', 10, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pipeline_runs')) {
            Schema::create('pipeline_runs', function (Blueprint $table) {
                $table->id();
                $table->uuid('run_uuid')->unique();
                $table->string('pipeline_type', 50);
                $table->string('module', 80)->nullable();
                $table->string('pipeline_durumu', 30)->default('queued');
                $table->string('mevcut_asama', 30)->nullable();
                $table->json('input_payload')->nullable();
                $table->json('normalized_payload')->nullable();
                $table->json('final_output')->nullable();
                $table->string('karar_aksiyonu', 30)->nullable();
                $table->text('karar_gerekcesi')->nullable();
                $table->unsignedInteger('total_steps')->default(0);
                $table->unsignedInteger('completed_steps')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pipeline_steps')) {
            Schema::create('pipeline_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pipeline_run_id')->nullable();
                $table->string('adim_adi', 50);
                $table->string('shard_key', 50)->nullable();
                $table->string('agent_adi', 80)->nullable();
                $table->string('adim_durumu', 30)->default('pending');
                $table->string('queue_name', 50)->nullable();
                $table->json('input_payload')->nullable();
                $table->json('output_payload')->nullable();
                $table->text('hata_mesaji')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedSmallInteger('deneme_sayisi')->default(0);
                $table->unsignedInteger('duration_ms')->nullable();
                $table->string('worker_node', 100)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        // ========================================
        // CATEGORY A: CRITICAL TABLES (15 tables)
        // Test-driven schema parity for Run #42
        // ========================================

        // Core Domain Tables
        if (!Schema::hasTable('ilanlar')) {
            Schema::create('ilanlar', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->nullable();
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->string('para_birimi', 10)->default('TRY');
                $table->string('yayin_durumu', 20)->default('taslak');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('danisman_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable(); // Added for CI
                $table->unsignedBigInteger('ana_kategori_id')->nullable();
                $table->unsignedBigInteger('alt_kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->unsignedBigInteger('il_id')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable();
                $table->unsignedBigInteger('mahalle_id')->nullable();
                $table->string('adres')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->integer('m2_brut')->nullable();
                $table->integer('m2_net')->nullable();
                $table->integer('oda_sayisi')->nullable();
                $table->integer('salon_sayisi')->nullable();
                $table->integer('banyo_sayisi')->nullable();
                $table->integer('kat')->nullable();
                $table->integer('bina_kati')->nullable();
                $table->integer('bina_yasi')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->integer('goruntulenme')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('kisiler')) {
            Schema::create('kisiler', function (Blueprint $table) {
                $table->id();
                $table->string('ad')->nullable();
                $table->string('soyad')->nullable();
                $table->string('email')->nullable();
                $table->string('telefon')->nullable();
                $table->string('telefon_2')->nullable();
                $table->string('tc_kimlik')->nullable(); // Context7: Model uses tc_kimlik
                $table->string('meslek')->nullable();
                $table->text('notlar')->nullable();
                $table->unsignedBigInteger('user_id')->nullable(); // Context7: Added user_id
                $table->string('kisi_tipi')->default('musteri');
                $table->unsignedBigInteger('danisman_id')->nullable();
                $table->unsignedBigInteger('ulke_id')->nullable();
                $table->unsignedBigInteger('il_id')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable();
                $table->unsignedBigInteger('mahalle_id')->nullable();
                $table->text('adres')->nullable();
                $table->string('crm_surec_asamasi')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->boolean('sesli_onay_verildi')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('telefon')->nullable();
                $table->string('profil_fotografi')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('iller')) {
            Schema::create('iller', function (Blueprint $table) {
                $table->id();
                $table->string('il_adi');
                $table->string('plaka_kodu', 3)->nullable();
                $table->integer('api_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('ilceler')) {
            Schema::create('ilceler', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('il_id');
                $table->string('ilce_adi');
                $table->string('ilce_kodu', 10)->nullable();
                $table->integer('api_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('ilan_kategorileri')) {
            Schema::create('ilan_kategorileri', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('yayin_tipi_sablonlari')) {
            Schema::create('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->id();
                $table->string('ad');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('kategori_id')->nullable(); // Added for CI - foreign key reference
                $table->text('aciklama')->nullable();
                $table->json('sablonlar')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // RBAC Tables
        if (!Schema::hasTable('etiketler')) {
            Schema::create('etiketler', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('color')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        // Domain Logic Tables
        if (!Schema::hasTable('talepler')) {
            Schema::create('talepler', function (Blueprint $table) {
                $table->id();
                $table->string('talep_tipi')->nullable();
                $table->string('emlak_tipi')->nullable(); // Added for CI
                $table->unsignedBigInteger('kisi_id')->nullable();
                $table->unsignedBigInteger('danisman_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('alt_kategori_id')->nullable(); // Added for CI
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->string('il')->nullable();
                $table->unsignedBigInteger('il_id')->nullable(); // Added for CI
                $table->string('ilce')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable(); // Added for CI
                $table->string('mahalle')->nullable();
                $table->unsignedBigInteger('mahalle_id')->nullable(); // Added for CI
                $table->decimal('min_fiyat', 15, 2)->nullable();
                $table->decimal('max_fiyat', 15, 2)->nullable();
                $table->string('para_birimi', 10)->default('TRY');
                $table->integer('min_m2')->nullable();
                $table->integer('max_m2')->nullable();
                $table->integer('min_oda')->nullable();
                $table->integer('max_oda')->nullable();
                $table->text('notlar')->nullable();
                $table->string('talep_durumu')->default('Aktif'); // Added for CI
                $table->string('oncelik')->nullable(); // Added for CI
                $table->string('durum')->default('aktif');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (!Schema::hasColumn('talepler', 'il_id')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->unsignedBigInteger('il_id')->nullable()->after('il');
            });
        }

        if (!Schema::hasTable('eslesmeler')) {
            Schema::create('eslesmeler', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('talep_id')->nullable();
                $table->unsignedBigInteger('ilan_id')->nullable();
                $table->unsignedBigInteger('kisi_id')->nullable(); // Added for CI
                $table->integer('skor')->default(0); // Added for CI
                $table->integer('eslesme_skoru')->default(0);
                $table->json('eslesme_detaylari')->nullable();
                $table->string('eslesme_durumu')->default('beklemede'); // Added for CI
                $table->string('durum')->default('beklemede');
                $table->timestamp('gosterildi_at')->nullable();
                $table->timestamp('begenildi_at')->nullable();
                $table->timestamps();
            });
        }

        // AI Infrastructure Tables
        if (!Schema::hasTable('ai_feature_usages')) {
            Schema::create('ai_feature_usages', function (Blueprint $table) {
                $table->id();
                $table->string('istek_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->string('source_tipi')->nullable();
                $table->string('saglayici')->nullable();
                $table->string('model')->nullable();
                $table->integer('input_token_sayisi')->default(0);
                $table->integer('output_token_sayisi')->default(0);
                $table->decimal('maliyet', 10, 4)->default(0);
                $table->string('calisma_durumu')->default('success');
                $table->text('error_message')->nullable();
                $table->integer('latency_ms')->nullable();
                $table->text('neden_detay')->nullable();
                $table->json('explainability_v2_json')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_tenant_quotas')) {
            Schema::create('ai_tenant_quotas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->decimal('monthly_limit', 10, 2)->default(0);
                $table->decimal('current_usage', 10, 2)->default(0);
                $table->string('policy')->default('allow');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_tenant_settings')) {
            Schema::create('ai_tenant_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->boolean('vision_enabled')->default(true);
                $table->decimal('custom_threshold', 5, 3)->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_deneyler')) {
            Schema::create('ai_deneyler', function (Blueprint $table) {
                $table->id();
                $table->string('deney_adi');
                $table->string('deney_tipi')->nullable();
                $table->text('aciklama')->nullable();
                $table->string('durum')->default('aktif');
                $table->json('parametreler')->nullable();
                $table->json('sonuclar')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'feedback_results' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('feedback_results')) {
            Schema::create('feedback_results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('listing_id')->nullable();
                $table->unsignedBigInteger('snapshot_id')->nullable();
                $table->unsignedBigInteger('outcome_id')->nullable();
                $table->boolean('pricing_correct')->default(false);
                $table->boolean('demand_correct')->default(false);
                $table->boolean('opportunity_correct')->default(false);
                $table->text('feedback_reason')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('feedback_type')->nullable();
                $table->text('feedback_content')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'listing_outcomes' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('listing_outcomes')) {
            Schema::create('listing_outcomes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('listing_id')->nullable();
                $table->string('outcome_type')->nullable();
                $table->integer('days_to_close')->nullable();
                $table->decimal('final_price', 15, 2)->nullable();
                $table->integer('price_changes_count')->default(0);
                $table->integer('lead_count')->default(0);
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'ups_feature_packs' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ups_feature_packs')) {
            Schema::create('ups_feature_packs', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->text('description')->nullable(); // Added for CI
                $table->boolean('aktiflik_durumu')->default(true); // Added for CI
                $table->timestamps();
                $table->softDeletes(); // Added for CI
            });
        }

        // Restore 'ups_feature_pack_items' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ups_feature_pack_items')) {
            Schema::create('ups_feature_pack_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('feature_pack_id')->nullable();
                $table->unsignedBigInteger('feature_id')->nullable();
                $table->unsignedInteger('display_order')->default(0); // Added for CI
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('ups_feature_pack_items', 'display_order')) {
            Schema::table('ups_feature_pack_items', function (Blueprint $table) {
                $table->unsignedInteger('display_order')->default(0)->after('feature_id');
            });
        }

        // Restore 'ai_ogrenme_sinyalleri' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ai_ogrenme_sinyalleri')) {
            Schema::create('ai_ogrenme_sinyalleri', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->unsignedBigInteger('ai_feature_usage_id')->nullable();
                $table->string('feature_slug')->nullable();
                $table->decimal('confidence', 5, 2)->nullable();
                $table->string('karar_tipi')->nullable();
                $table->integer('skor')->nullable();
                $table->string('context_hash')->nullable();
                $table->json('sinyaller_json')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // Restore 'ai_pricing_plans' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ai_pricing_plans')) {
            Schema::create('ai_pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'market_valuation_reports' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('market_valuation_reports')) {
            Schema::create('market_valuation_reports', function (Blueprint $table) {
                $table->id();
                $table->string('location_il')->nullable();
                $table->string('location_ilce')->nullable();
                $table->string('location_mahalle')->nullable();
                $table->string('asset_type')->nullable();
                $table->integer('m2')->nullable();
                $table->decimal('median_m2_price', 15, 2)->nullable();
                $table->decimal('estimated_value', 15, 2)->nullable();
                $table->decimal('price_range_low', 15, 2)->nullable();
                $table->decimal('price_range_high', 15, 2)->nullable();
                $table->decimal('market_trend', 5, 2)->nullable();
                $table->string('liquidity_score')->nullable();
                $table->integer('confidence_score')->nullable();
                $table->integer('comparable_count')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'ai_feature_prices' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ai_feature_prices')) {
            Schema::create('ai_feature_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('feature_slug')->nullable();
                $table->integer('base_cost_credits')->nullable();
                $table->boolean('is_dynamic')->default(false);
                $table->decimal('multiplier', 5, 2)->nullable(); // Added for CI
                $table->timestamps();
            });
        }

        // Restore 'property_calendar_feeds' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('property_calendar_feeds')) {
            Schema::create('property_calendar_feeds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable();
                $table->string('provider')->nullable();
                $table->string('ical_url')->nullable();
                $table->boolean('sync_enabled')->default(true);
                $table->integer('sync_frequency_minutes')->default(60);
                $table->timestamps();
            });
        }

        // Restore 'listing_search_projection' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('listing_search_projection')) {
            Schema::create('listing_search_projection', function (Blueprint $table) {
                $table->unsignedBigInteger('listing_id')->primary();
                $table->string('title')->nullable();
                $table->decimal('price', 15, 2)->nullable();
                $table->string('city')->nullable();
                $table->string('district')->nullable();
                $table->string('property_type')->nullable();
                $table->integer('portfolio_health')->nullable();
                $table->integer('seo_score')->nullable();
            });
        }

        // Restore missing 'aktiflik_durumu' column to 'ups_templates' table if missing (CI test bootstrap parity)
        if (Schema::hasTable('ups_templates') && !Schema::hasColumn('ups_templates', 'aktiflik_durumu')) {
            Schema::table('ups_templates', function (Blueprint $table) {
                $table->boolean('aktiflik_durumu')->default(true)->after('id');
            });
        }

        // Restore 'listing_state_transitions' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('listing_state_transitions')) {
            Schema::create('listing_state_transitions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id')->nullable();
                $table->string('from_state')->nullable();
                $table->string('to_state')->nullable();
                $table->unsignedBigInteger('aktan_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // Restore 'country_financial_rules' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('country_financial_rules')) {
            Schema::create('country_financial_rules', function (Blueprint $table) {
                $table->id();
                $table->string('country_code', 2);
                $table->string('country_name');
                $table->decimal('rental_commission_rate', 5, 4);
                $table->decimal('sales_commission_rate', 5, 4);
                $table->decimal('advisory_fee_rate', 5, 4)->default(0.0000);
                $table->decimal('tax_rate', 5, 4)->default(0.0000);
                $table->string('default_currency', 3)->default('TRY');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // Restore 'governance_incidents' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('governance_incidents')) {
            Schema::create('governance_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->default('SYSTEM');
                $table->string('olay_tipi');
                $table->string('kaynak');
                $table->unsignedBigInteger('snapshot_id')->nullable();
                $table->string('imza_hash')->nullable();
                $table->string('risk_seviyesi');
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        // Restore 'ilan_price_history' table if missing (CI test bootstrap parity)
        if (!Schema::hasTable('ilan_price_history')) {
            Schema::create('ilan_price_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id');
                $table->decimal('old_price', 15, 2);
                $table->decimal('new_price', 15, 2);
                $table->string('currency', 3)->default('TRY');
                $table->string('change_reason')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->json('additional_data')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('copilot_action_logs')) {
            Schema::dropIfExists('copilot_action_logs');
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'il')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('il');
            });
        }
    }
};
