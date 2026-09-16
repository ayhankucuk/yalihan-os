<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite CI Schema Gap Fix — ModelSchemaContractTest drift
 *
 * Bu migration SQLite test ortamında eksik olan kolonları ekler.
 * Production MySQL'de bu kolonlar zaten mevcut (koşullu kontrol).
 *
 * Etkilenen modeller:
 * - Ozellik: aciklama, veri_secenekleri
 * - FeaturePack (ups_feature_packs): display_order
 * - Feature: deprecated_at
 *
 * ⚠️ ROLLBACK NOT: down() yalnızca 3 ilanlar kolonu düşürür
 * (display_order, kategori, imar_durumu). up() 70+ kolon eklediğinden
 * tam rollback mümkün değildir. Bu migrationı tek başına geri almak
 * tabloyu tutarsız bırakır. Rollback gerekirse tüm eklenen kolonlar
 * down()'a eklenmeli veya migration fitch/reset ile ele alınmalı.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ozellikler.aciklama
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'aciklama')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->text('aciklama')->nullable();
            });
        }

        // ozellikler.veri_secenekleri
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'veri_secenekleri')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->json('veri_secenekleri')->nullable();
            });
        }

        // ups_feature_packs.display_order
        if (Schema::hasTable('ups_feature_packs') && !Schema::hasColumn('ups_feature_packs', 'display_order')) {
            Schema::table('ups_feature_packs', function (Blueprint $table) {
                $table->integer('display_order')->default(0);
            });
        }

        // features.deprecated_at
        if (Schema::hasTable('features') && !Schema::hasColumn('features', 'deprecated_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->timestamp('deprecated_at')->nullable();
            });
        }

        // features.archived_at
        if (Schema::hasTable('features') && !Schema::hasColumn('features', 'archived_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable();
            });
        }

        // ilanlar.one_cikan
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'one_cikan')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->boolean('one_cikan')->default(false);
            });
        }

        // ilanlar.parent_kategori_id
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'parent_kategori_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_kategori_id')->nullable();
            });
        }

        // ozellikler.veri_tipi
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'veri_tipi')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->string('veri_tipi')->default('text');
            });
        }

        // ozellikler.zorunlu
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'zorunlu')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->boolean('zorunlu')->default(false);
            });
        }

        // ozellikler.birim
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'birim')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->string('birim')->nullable();
            });
        }

        // ozellikler.arama_filtresi
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'arama_filtresi')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->boolean('arama_filtresi')->default(true);
            });
        }

        // ozellikler.ilan_kartinda_goster
        if (Schema::hasTable('ozellikler') && !Schema::hasColumn('ozellikler', 'ilan_kartinda_goster')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->boolean('ilan_kartinda_goster')->default(false);
            });
        }

        // features.last_used_at
        if (Schema::hasTable('features') && !Schema::hasColumn('features', 'last_used_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->timestamp('last_used_at')->nullable();
            });
        }

        // ilanlar — ALL missing columns (production MySQL has these, SQLite CI does not)
        // Comprehensive list generated from Ilan model $fillable + $casts
        $ilanlarColumns = [
            // Fillable fields (string by default)
            'tapu_id' => 'string',
            'display_order' => 'integer',
            'kategori' => 'string',
            'alt_kategori' => 'string',
            'yayin_tipi' => 'string',
            'il' => 'string',
            'ilce' => 'string',
            'mahalle' => 'string',
            'external_ref' => 'string',
            'isinma_tipi' => 'string',
            'site_ozellikleri' => 'json',
            'isyeri_tipi' => 'string',
            'kira_bilgisi' => 'string',
            'ciro_bilgisi' => 'float',
            'personel_kapasitesi' => 'integer',
            'isyeri_cephesi' => 'integer',
            'management_model' => 'string',
            'custom_commission_rate' => 'float',
            'geometry_type' => 'string',
            'geometry' => 'json',
            'portal_pricing' => 'json',
            'anahtar_kimde' => 'string',
            'anahtar_turu' => 'string',
            'anahtar_notlari' => 'string',
            'anahtar_ulasilabilirlik' => 'string',
            'anahtar_ek_bilgi' => 'string',
            'ekstra_ozellikler' => 'json',
            // Cast-only fields
            'quality_score' => 'float',
            'imar_durumu' => 'string',
            'lansman_fiyati' => 'float',
            'lansman_bitis_tarihi' => 'timestamp',
            'lansman_kotasi' => 'integer',
            'toplam_hisseli' => 'boolean',
            'manzara_tipleri' => 'json',
            'havuz_isitmali' => 'boolean',
            'bahce_var' => 'boolean',
            'bahce_masasi_var' => 'boolean',
            'barbeku_var' => 'boolean',
            'sezlong_var' => 'boolean',
            'deniz_manzarali' => 'boolean',
            'doga_manzarali' => 'boolean',
            'dag_manzarali' => 'boolean',
            'mutfak_tam_donanmli' => 'boolean',
            'mutfak_bulasik_makinesi' => 'boolean',
            'mutfak_kahve_makinesi' => 'boolean',
            'isitma_var' => 'boolean',
            'evcil_hayvan_uygun' => 'boolean',
            'sigara_icilmez' => 'boolean',
            'kadastral_yol' => 'boolean',
            'depozito' => 'float',
            'ruhsat_durumu' => 'string',
            'environment_pois' => 'json',
            'environment_tags' => 'json',
            'youtube_video_url' => 'string',
            'sanal_tur_url' => 'string',
            'video_url' => 'string',
            'video_isleme_durumu' => 'string',
            'video_last_frame' => 'integer',
            'location_type' => 'string',
            'location_data' => 'json',
            'nearby_places' => 'json',
            'environmental_scores' => 'json',
            'portal_senkronizasyon_durumu' => 'json',
            'ai_metadata' => 'json',
        ];

        if (Schema::hasTable('ilanlar')) {
            foreach ($ilanlarColumns as $col => $type) {
                if (!Schema::hasColumn('ilanlar', $col)) {
                    Schema::table('ilanlar', function (Blueprint $table) use ($col, $type) {
                        switch ($type) {
                            case 'boolean':
                                $table->boolean($col)->default(false);
                                break;
                            case 'integer':
                                $table->integer($col)->nullable();
                                break;
                            case 'float':
                                $table->float($col)->nullable();
                                break;
                            case 'json':
                                $table->json($col)->nullable();
                                break;
                            case 'timestamp':
                                $table->timestamp($col)->nullable();
                                break;
                            default:
                                $table->string($col)->nullable();
                        }
                    });
                }
            }

            // Legacy columns with hyphens (l-atitude, l-ongitude)
            if (!Schema::hasColumn('ilanlar', 'l-atitude')) {
                Schema::table('ilanlar', function (Blueprint $table) {
                    $table->float('l-atitude')->nullable();
                });
            }
            if (!Schema::hasColumn('ilanlar', 'l-ongitude')) {
                Schema::table('ilanlar', function (Blueprint $table) {
                    $table->float('l-ongitude')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('features') && Schema::hasColumn('features', 'deprecated_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('deprecated_at');
            });
        }

        if (Schema::hasTable('ups_feature_packs') && Schema::hasColumn('ups_feature_packs', 'display_order')) {
            Schema::table('ups_feature_packs', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }

        if (Schema::hasTable('ozellikler') && Schema::hasColumn('ozellikler', 'veri_secenekleri')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->dropColumn('veri_secenekleri');
            });
        }

        if (Schema::hasTable('ozellikler') && Schema::hasColumn('ozellikler', 'aciklama')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->dropColumn('aciklama');
            });
        }

        if (Schema::hasTable('ozellikler') && Schema::hasColumn('ozellikler', 'birim')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->dropColumn('birim');
            });
        }

        if (Schema::hasTable('ozellikler') && Schema::hasColumn('ozellikler', 'arama_filtresi')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->dropColumn('arama_filtresi');
            });
        }

        if (Schema::hasTable('ozellikler') && Schema::hasColumn('ozellikler', 'ilan_kartinda_goster')) {
            Schema::table('ozellikler', function (Blueprint $table) {
                $table->dropColumn('ilan_kartinda_goster');
            });
        }

        if (Schema::hasTable('features') && Schema::hasColumn('features', 'last_used_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('last_used_at');
            });
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'display_order')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'kategori')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('kategori');
            });
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'imar_durumu')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('imar_durumu');
            });
        }
    }
};
