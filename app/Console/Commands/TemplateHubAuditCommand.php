<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TemplateHubAuditCommand extends Command
{
    protected $signature = 'audit:template-hub {--matrix : Full alt×yayin matrix}';

    protected $description = 'Template Hub ilişki denetimi — yayin_tipi_sablonlari / feature_assignments / izin matrisi';

    public function handle(): int
    {
        $this->info('=== TEMPLATE HUB İLİŞKİ DENETİMİ ===');

        // ── 1. Ana kategoriler (parent_id IS NULL) ─────────────────────────
        $kats = DB::select(
            "SELECT id, name, slug FROM ilan_kategorileri
             WHERE parent_id IS NULL AND aktiflik_durumu = 1
             ORDER BY display_order"
        );
        $anaMap = [];
        foreach ($kats as $k) {
            $anaMap[$k->id] = ['name' => $k->name, 'slug' => $k->slug];
            $this->line(sprintf('  [%d] %s (%s)', $k->id, $k->name, $k->slug));
        }

        // ── 2. Alt kategoriler (parent_id IS NOT NULL) ─────────────────────
        $altlars = DB::select(
            "SELECT id, parent_id, name, slug FROM ilan_kategorileri
             WHERE parent_id IS NOT NULL AND aktiflik_durumu = 1
             ORDER BY parent_id, display_order"
        );
        $altMap = [];
        foreach ($altlars as $a) {
            $altMap[$a->id] = [
                'ana'  => $a->parent_id,
                'name' => $a->name,
                'slug' => $a->slug,
            ];
            $anaName = $anaMap[$a->parent_id]['name'] ?? '?';
            $this->line(sprintf('  [%d] ana=[%d] %s / %s', $a->id, $a->parent_id, $anaName, $a->name));
        }

        // ── 3. Yayın tipleri ──────────────────────────────────────────────
        $yayinlar = DB::select(
            "SELECT id, name, slug FROM yayin_tipleri
             WHERE aktiflik_durumu = 1 ORDER BY display_order"
        );
        $yayinMap = [];
        foreach ($yayinlar as $y) {
            $yayinMap[$y->id] = ['name' => $y->name, 'slug' => $y->slug];
            $this->line(sprintf('  [%d] %s (%s)', $y->id, $y->name, $y->slug));
        }

        // ── 4. İzin matrisi ─────────────────────────────────────────────────
        $this->line("\n--- 4. İZİN MATRİSİ: alt_kategori_yayin_tipi ---");
        $izin = DB::select(
            "SELECT alt_kategori_id, yayin_tipi_id
             FROM alt_kategori_yayin_tipi WHERE aktiflik_durumu = 1"
        );
        $izinKey = [];
        foreach ($izin as $i) {
            $izinKey[$i->alt_kategori_id . '-' . $i->yayin_tipi_id] = true;
        }
        $this->info(sprintf('  Toplam aktif izin: %d', count($izin)));

        // ── 5. Template Hub ─────────────────────────────────────────────────
        // yayin_tipi_sablonlari.kategori_id → ilan_kategorileri.id
        //   Eğer ilan_kategorileri.parent_id IS NULL → ana kategoridir (main)
        //   Eğer parent_id IS NOT NULL → alt kategoridir → parent_id = main_category
        $this->line("\n--- 5. TEMPLATE HUB: yayin_tipi_sablonlari ---");
        $sablonlar = DB::select(
            "SELECT id, kategori_id, yayin_tipi_id, ad, tenant_id
             FROM yayin_tipi_sablonlari
             WHERE aktiflik_durumu = 1
             ORDER BY kategori_id, yayin_tipi_id"
        );
        $this->info(sprintf('  Toplam aktif template: %d', count($sablonlar)));

        // kategori_id → [main_category_id, sub_category_id, kat_tipi]
        // kat_tipi = 'ANA' | 'ALT'
        $resolveKategori = function ($katId) use ($anaMap, $altMap): array {
            if ($katId === null) return ['main' => 0, 'sub' => 0, 'tip' => 'NULL'];
            if (isset($anaMap[$katId])) return ['main' => $katId, 'sub' => $katId, 'tip' => 'ANA'];
            if (isset($altMap[$katId]))  return ['main' => $altMap[$katId]['ana'], 'sub' => $katId, 'tip' => 'ALT'];
            return ['main' => (int) $katId, 'sub' => (int) $katId, 'tip' => 'UNKNOWN'];
        };

        $hubAnaMap    = []; // "main_category_id-yayin_tipi_id" → sablon
        $hubExactMap  = []; // "kategori_id-yayin_tipi_id"     → sablon
        foreach ($sablonlar as $s) {
            $resolved = $resolveKategori($s->kategori_id);
            $mainKey  = $resolved['main'] . '-' . $s->yayin_tipi_id;
            $excKey   = $s->kategori_id . '-' . $s->yayin_tipi_id;

            $sablon = [
                'id'       => $s->id,
                'ad'       => $s->ad,
                'main'     => $resolved['main'],
                'sub'      => $resolved['sub'],
                'kat_tip'  => $resolved['tip'],
                'kategori_id' => $s->kategori_id,
                'tenant'   => $s->tenant_id,
            ];

            $hubExactMap[$excKey] = $sablon;
            if (!isset($hubAnaMap[$mainKey])) {
                $hubAnaMap[$mainKey] = $sablon;
            }

            $katName = ($anaMap[$s->kategori_id]['name'] ?? null)
                ?: ($altMap[$s->kategori_id]['name'] ?? 'id=' . $s->kategori_id);
            $yayName = $yayinMap[$s->yayin_tipi_id]['name'] ?? 'id=' . $s->yayin_tipi_id;
            $this->line(sprintf(
                '  [id=%3d] kat=[%d,%s] %-15s tip=[%d] %-8s → "%s" [%s] (tenant=%s)',
                $s->id, $s->kategori_id, $resolved['tip'],
                substr($katName, 0, 15),
                $s->yayin_tipi_id, substr($yayName, 0, 8),
                $s->ad, $resolved['tip'], $s->tenant_id ?: 'NULL'
            ));
        }

        // ── 6. Feature count per template (3-way join) ──────────────────────
        // feature_assignments: main_category_id + sub_category_id + listing_type_id
        $this->line("\n--- 6. FEATURE ASSIGNMENT counts (canonical, tenant_id NULL/0) ---");
        $faByTpl = DB::select(
            "SELECT yts.id as sablon_id, yts.ad as sablon_adi,
                    yts.kategori_id, yts.yayin_tipi_id,
                    COUNT(fa.id) as feature_count
             FROM yayin_tipi_sablonlari yts
             LEFT JOIN feature_assignments fa
               ON fa.main_category_id = (
                    CASE
                        WHEN yts.kategori_id IN (SELECT id FROM ilan_kategorileri WHERE parent_id IS NULL)
                         THEN yts.kategori_id
                        ELSE (SELECT parent_id FROM ilan_kategorileri WHERE id = yts.kategori_id)
                    END
                  )
               AND fa.sub_category_id = yts.kategori_id
               AND fa.listing_type_id = yts.yayin_tipi_id
               AND fa.aktiflik_durumu = 1
               AND (fa.tenant_id IS NULL OR fa.tenant_id = 0 OR fa.tenant_id = '')
             WHERE yts.aktiflik_durumu = 1
             GROUP BY yts.id, yts.ad, yts.kategori_id, yts.yayin_tipi_id
             ORDER BY feature_count DESC"
        );

        $faByTplMap = [];
        foreach ($faByTpl as $row) {
            $faByTplMap[$row->sablon_id] = (int) $row->feature_count;
            $mark = $row->feature_count > 0 ? '<fggreen>✓</>' : '<fgred>✗</>';
            $this->line(sprintf(
                '  %s [id=%3d] %-45s → %3d features',
                $mark, $row->sablon_id, substr($row->sablon_adi, 0, 45), $row->feature_count
            ));
        }

        // ── 7. Kayıt sayıları ──────────────────────────────────────────────
        $this->line("\n--- 7. KAYIT SAYILARI ---");
        $fdCount = DB::selectOne("SELECT COUNT(*) as cnt FROM kategori_yayin_tipi_field_dependencies WHERE aktiflik_durumu = 1");
        $fActive = DB::selectOne("SELECT COUNT(*) as cnt FROM features WHERE aktiflik_durumu = 1");
        $fTotal  = DB::selectOne("SELECT COUNT(*) as cnt FROM features");
        $faCanon = DB::selectOne("SELECT COUNT(*) as cnt FROM feature_assignments WHERE aktiflik_durumu = 1 AND (tenant_id IS NULL OR tenant_id = 0 OR tenant_id = '')");
        $faTotal = DB::selectOne("SELECT COUNT(*) as cnt FROM feature_assignments WHERE aktiflik_durumu = 1");
        $this->table(
            ['Kaynak', 'Aktif', 'Toplam'],
            [
                ['kategori_yayin_tipi_field_dependencies', $fdCount->cnt, '—'],
                ['features',                              $fActive->cnt, $fTotal->cnt],
                ['feature_assignments (canonical)',          $faCanon->cnt, $faTotal->cnt],
            ]
        );

        // ── 8. Legacy field_dependencies ────────────────────────────────────
        $this->line("\n--- 8. LEGACY: kategori_yayin_tipi_field_dependencies örnek ---");
        $fdSample = DB::select(
            "SELECT id, kategori_slug, yayin_tipi, field_slug, field_name,
                    field_type, required, aktiflik_durumu
             FROM kategori_yayin_tipi_field_dependencies
             WHERE aktiflik_durumu = 1
             ORDER BY kategori_slug, yayin_tipi
             LIMIT 8"
        );
        $this->table(
            ['id', 'kategori_slug', 'yayin_tipi', 'field_slug', 'field_name', 'req'],
            array_map(fn($r) => [
                $r->id,
                $r->kategori_slug ?: 'null',
                $r->yayin_tipi,
                $r->field_slug,
                substr($r->field_name, 0, 20),
                $r->required ? 'YES' : 'no',
            ], $fdSample)
        );

        // ── 9. Kombinasyon analizi ─────────────────────────────────────────
        $this->line("\n--- 9. KOMBİNASYON ANALİZİ ---");
        $teorikAnaTip = count($kats) * count($yayinlar);
        $izinAnaTip = [];
        foreach ($izin as $i) {
            $alt = $altMap[$i->alt_kategori_id] ?? null;
            if ($alt) {
                $k = $alt['ana'] . '-' . $i->yayin_tipi_id;
                $izinAnaTip[$k] = true;
            }
        }
        $this->table(
            ['Metrik', 'Değer', 'Açıklama'],
            [
                ['Teorik (6 ana × 8 yayın tipi)',   $teorikAnaTip,  count($kats) . ' ana kat × ' . count($yayinlar) . ' yayın tipi'],
                ['Gerçek ana kategoriler (aktif)',   count($kats),   'parent_id IS NULL'],
                ['Gerçek alt kategoriler (aktif)',   count($altlars), 'parent_id IS NOT NULL'],
                ['Gerçek yayın tipleri (aktif)',      count($yayinlar), ''],
                ['Template Hub kaydı (aktif)',        count($sablonlar), 'yayin_tipi_sablonlari'],
                ['İzin verilen ana×tip komb.',       count($izinAnaTip), 'alt_kategori_yayin_tipi → unique ana×tip'],
            ]
        );

        // ── 10. Template Hub + Feature matrix per ana_kategori × yayin_tipi ──
        $this->line("\n--- 10. TEMPLATE HUB × FEATURE MATRIX (ana × yayin_tipi) ---");
        $this->warn('  Sembol: ✓=CANONICAL_ACTIVE  ◐=TEMPLATE_NO_FEATURES  ✗=NO_TEMPLATE  ⊘=YASAK');
        $matrixRows = [];
        $statCounts = ['CANONICAL_ACTIVE' => 0, 'TEMPLATE_NO_FEATURES' => 0, 'NO_TEMPLATE' => 0, 'YASAK' => 0];
        foreach ($kats as $kat) {
            foreach ($yayinlar as $yayin) {
                $key = $kat->id . '-' . $yayin->id;
                $izin_varmi = isset($izinAnaTip[$key]);

                $tpl = $hubAnaMap[$key] ?? null;
                $tpl_varmi = (bool) $tpl;
                $fa_cnt = $tpl ? ($faByTplMap[$tpl['id']] ?? 0) : 0;

                if (!$izin_varmi) {
                    $sym = '⊘'; $col = 'fgred'; $status = 'YASAK';
                } elseif ($tpl_varmi && $fa_cnt > 0) {
                    $sym = '✓'; $col = 'fggreen'; $status = 'CANONICAL_ACTIVE';
                } elseif ($tpl_varmi && $fa_cnt === 0) {
                    $sym = '◐'; $col = 'fgyellow'; $status = 'TEMPLATE_NO_FEATURES';
                } else {
                    $sym = '✗'; $col = 'fgred'; $status = 'NO_TEMPLATE';
                }
                $statCounts[$status]++;
                $tplAd = $tpl ? substr($tpl['ad'], 0, 20) : '—';
                $matrixRows[] = [
                    sprintf('<%s>%s</>', $col, $sym),
                    $kat->id,
                    substr($kat->name, 0, 14),
                    $yayin->id,
                    substr($yayin->name, 0, 10),
                    $tpl_varmi ? 'EVET' : 'HAYIR',
                    $tplAd,
                    (string) $fa_cnt,
                    $status,
                ];
            }
        }
        $this->table(
            ['S', 'AnaID', 'Ana Kat', 'TipID', 'Yayın Tipi', 'T?', 'Tmpl Ad', 'FA#', 'Status'],
            $matrixRows
        );

        $this->line("\n--- MATRİS ÖZETİ ---");
        $this->table(
            ['Status', 'Sayı', 'Oran'],
            [
                ['CANONICAL_ACTIVE',       $statCounts['CANONICAL_ACTIVE'],       sprintf('%d/%d', $statCounts['CANONICAL_ACTIVE'], count($matrixRows))],
                ['TEMPLATE_NO_FEATURES',   $statCounts['TEMPLATE_NO_FEATURES'],   sprintf('%d/%d', $statCounts['TEMPLATE_NO_FEATURES'], count($matrixRows))],
                ['NO_TEMPLATE',            $statCounts['NO_TEMPLATE'],            sprintf('%d/%d', $statCounts['NO_TEMPLATE'], count($matrixRows))],
                ['YASAK',                  $statCounts['YASAK'],                  sprintf('%d/%d', $statCounts['YASAK'], count($matrixRows))],
            ]
        );

        // ── 11. Alt kategori × yayin_tipi full matrix ───────────────────────
        if ($this->option('matrix')) {
            $this->newLine();
            $this->warn('--- 11. FULL MATRIX: Alt Kategori × Yayın Tipi (tüm alt kategoriler) ---');
            $altRows = [];
            foreach ($altlars as $alt) {
                foreach ($yayinlar as $yayin) {
                    $izinkey = $alt->id . '-' . $yayin->id;
                    $izin_varmi = isset($izinKey[$izinkey]);

                    // Template: önce exact alt, yoksa ana template (inheritance)
                    $excKey = $alt->id . '-' . $yayin->id;
                    $tpl = $hubExactMap[$excKey] ?? null;
                    if (!$tpl) {
                        $tpl = $hubAnaMap[$alt->parent_id . '-' . $yayin->id] ?? null;
                    }
                    $tpl_varmi = (bool) $tpl;
                    $fa_cnt = $tpl ? ($faByTplMap[$tpl['id']] ?? 0) : 0;

                    if (!$izin_varmi) {
                        $sym = '⊘'; $col = 'fgred'; $status = 'YASAK';
                    } elseif ($tpl_varmi && $fa_cnt > 0) {
                        $sym = '✓'; $col = 'fggreen'; $status = 'CANONICAL_ACTIVE';
                    } elseif ($tpl_varmi && $fa_cnt === 0) {
                        $sym = '◐'; $col = 'fgyellow'; $status = 'TEMPLATE_NO_FEATURES';
                    } else {
                        $sym = '✗'; $col = 'fgred'; $status = 'NO_TEMPLATE';
                    }
                    $tplAd = $tpl ? substr($tpl['ad'], 0, 18) : '—';
                    $altRows[] = [
                        sprintf('<%s>%s</>', $col, $sym),
                        $alt->id,
                        substr($anaMap[$alt->parent_id]['name'] ?? '?', 0, 10),
                        substr($alt->name, 0, 15),
                        $yayin->id,
                        substr($yayin->name, 0, 10),
                        $tpl_varmi ? 'EVET' : 'HAYIR',
                        $tplAd,
                        (string) $fa_cnt,
                        $status,
                    ];
                }
            }
            $this->table(
                ['S', 'AltID', 'Ana', 'Alt Kat', 'TipID', 'Yayın Tipi', 'T?', 'Tmpl Ad', 'FA#', 'Status'],
                $altRows
            );
        }

        $this->newLine();
        $this->info('✓ Full alt×yayın matrix: php artisan audit:template-hub --matrix');

        return Command::SUCCESS;
    }
}
