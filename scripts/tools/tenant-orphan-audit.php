#!/usr/bin/env php
<?php
/**
 * Tenant Orphan Audit — PAKET A (Read-Only)
 *
 * Amaç: tenant_id boş/0/1 olmayan kayıtları tespit etmek.
 * MUTATION YAPMAZ — sadece rapor üretir.
 *
 * Kullanım:
 *   php scripts/tools/tenant-orphan-audit.php
 *
 * Çıktı:
 *   1. Her tablo için tenant_id dağılımı
 *   2. Belirsiz kayıtlar (orphan candidates)
 *   3. Potansiyel sahiplik çıkarımı (FK analizi)
 *
 * @author Yalıhan OS Engineering
 * @date 2026-09-09
 */

// ─── 1. Doğrudan PDO — Laravel bootstrap gerektirmez ───────────────
$pdo = new PDO('sqlite:' . __DIR__ . '/../../database/database.sqlite_test_1');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function q($pdo, $sql) {
    return $pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);
}
function q1($pdo, $sql) {
    return $pdo->query($sql)->fetch(PDO::FETCH_OBJ);
}

// ─── 2. Tüm tabloları listele ────────────────────────────────
$allTables = q($pdo,
    "SELECT name FROM sqlite_master
     WHERE type='table'
     AND name NOT LIKE 'sqlite_%'
     AND name NOT LIKE 'migrations%'
     AND name NOT LIKE 'failed_jobs%'
     AND name NOT LIKE 'jobs%'
     AND name NOT LIKE 'cache_%'
     AND name NOT LIKE 'sessions%'
     ORDER BY name"
);

// ─── 3. tenant_id kolonu olan tabloları filtrele ─────────────
$tenantTables = [];
$allTableNames = [];
foreach ($allTables as $t) {
    $name = $t->name;
    $allTableNames[] = $name;
    $cols = q($pdo, "PRAGMA table_info($name)");
    $colNames = array_column($cols, 'name');
    if (in_array('tenant_id', $colNames)) {
        $tenantTables[] = $name;
    }
}

// ─── 4. BelongsToTenant trait kullanan modelleri bul ─────────
$traitModels = [];
$appPath = realpath(__DIR__ . '/../../app/Models');
if ($appPath && is_dir($appPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        if (preg_match('/use.*BelongsToTenant/s', $content)) {
            if (preg_match('/class\s+(\w+)/', $content, $m)) {
                $traitModels[] = [
                    'model' => $m[1],
                    'file'  => 'app/Models/' . $file->getFilename(),
                ];
            }
        }
    }
}

// ─── 5. Her tablo için analiz ──────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           YALIHAN OS — TENANT ORPHAN AUDIT (PAKET A — READ-ONLY)          ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "▶ Veritabanı : database/yalihanai_test.sqlite\n";
echo "▶ Tarama     : " . date('Y-m-d H:i:s') . "\n";
echo "▶ Toplam tablo: " . count($allTables) . " | tenant_id olan: " . count($tenantTables) . "\n";
echo "\n";

$report = [
    'null_tenant'   => [],
    'zero_tenant'   => [],
    'orphan'        => [],
    'clean'         => [],
    'tenantless'    => [],
];
$grandTotal = ['total' => 0, 'null' => 0, 'zero' => 0, 'orphan' => 0];

foreach ($tenantTables as $table) {
    $total   = (int) q1($pdo, "SELECT COUNT(*) as cnt FROM $table")->cnt;
    $nullCnt = (int) q1($pdo, "SELECT COUNT(*) as cnt FROM $table WHERE tenant_id IS NULL")->cnt;
    $zeroCnt = (int) q1($pdo, "SELECT COUNT(*) as cnt FROM $table WHERE tenant_id = 0")->cnt;
    $oneCnt  = (int) q1($pdo, "SELECT COUNT(*) as cnt FROM $table WHERE tenant_id = 1")->cnt;
    $orphanCnt = (int) q1($pdo,
        "SELECT COUNT(*) as cnt FROM $table
         WHERE tenant_id IS NOT NULL AND tenant_id != 0 AND tenant_id != 1"
    )->cnt;

    $grandTotal['total']  += $total;
    $grandTotal['null']   += $nullCnt;
    $grandTotal['zero']   += $zeroCnt;
    $grandTotal['orphan'] += $orphanCnt;

    if ($nullCnt > 0) {
        $orphanTenants = [];
        if ($orphanCnt > 0) {
            $rows = q($pdo,
                "SELECT tenant_id, COUNT(*) as cnt FROM $table
                 WHERE tenant_id IS NOT NULL AND tenant_id != 0 AND tenant_id != 1
                 GROUP BY tenant_id"
            );
            foreach ($rows as $r) { $orphanTenants[$r->tenant_id] = (int)$r->cnt; }
        }
        $report['null_tenant'][] = [
            'table'   => $table,
            'total'   => $total,
            'null'    => $nullCnt,
            'zero'    => $zeroCnt,
            'one'     => $oneCnt,
            'orphan'  => $orphanCnt,
            'tenants' => $orphanTenants,
        ];
    } elseif ($zeroCnt > 0) {
        $report['zero_tenant'][] = [
            'table'  => $table,
            'total'  => $total,
            'zero'   => $zeroCnt,
            'one'    => $oneCnt,
            'orphan' => $orphanCnt,
        ];
    } elseif ($orphanCnt > 0) {
        $rows = q($pdo,
            "SELECT tenant_id, COUNT(*) as cnt FROM $table
             WHERE tenant_id IS NOT NULL AND tenant_id != 0 AND tenant_id != 1
             GROUP BY tenant_id"
        );
        $orphanTenants = [];
        foreach ($rows as $r) { $orphanTenants[$r->tenant_id] = (int)$r->cnt; }
        $report['orphan'][] = [
            'table'   => $table,
            'total'   => $total,
            'orphan'  => $orphanCnt,
            'tenants' => $orphanTenants,
        ];
    } elseif ($total > 0) {
        $report['clean'][] = ['table' => $table, 'total' => $total, 'one' => $oneCnt];
    }
}

// ─── 6. tenant_id OLMAYAN ama FK ile bağlanabilen tablolar ─────
$tenantlessWithFk = [];
foreach ($allTableNames as $table) {
    if (in_array($table, $tenantTables)) continue;
    $cols = q($pdo, "PRAGMA table_info($table)");
    $colNames = array_column($cols, 'name');
    $relevantFk = array_filter($colNames, fn($c) =>
        preg_match('/^(ilan_id|kisi_id|user_id|gorev_id|reservation_id|owner_id)$/', $c)
    );
    if (count($relevantFk) > 0) {
        $rowCount = (int) q1($pdo, "SELECT COUNT(*) as cnt FROM $table")->cnt;
        if ($rowCount > 0) {
            $report['tenantless'][] = [
                'table'    => $table,
                'row_count'=> $rowCount,
                'fk_cols'  => array_values($relevantFk),
            ];
        }
    }
}

// ─── 7. NULL tenant_id kayıtlarının FK detayı ───────────────────
$nullFkDetails = [];
foreach ($report['null_tenant'] as $r) {
    $table = $r['table'];
    $fkList = q($pdo, "PRAGMA foreign_key_list($table)");
    $fkDetails = [];
    foreach ($fkList as $fk) {
        $fkDetails[] = [
            'from'  => $fk->from,
            'to_table' => $fk->table,
            'to_col'   => $fk->to,
        ];
    }
    $nullFkDetails[$table] = [
        'fks'      => $fkDetails,
        'has_fk'   => count($fkDetails) > 0,
        'has_ilan' => in_array('ilan_id', array_column($fkDetails, 'from')),
        'has_kisi' => in_array('kisi_id', array_column($fkDetails, 'from')),
        'has_user' => in_array('user_id', array_column($fkDetails, 'from')),
    ];
}

// ═══════════════════════════════════════════════════════════════════
//  Rapor Çıktısı
// ═══════════════════════════════════════════════════════════════════

// 7a: NULL
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  🔴 KATEGORİ 1: tenant_id IS NULL\n";
echo "══════════════════════════════════════════════════════════════════════\n";
if (empty($report['null_tenant'])) {
    echo "  ✅ Hiç NULL tenant_id kaydı yok.\n";
} else {
    echo "  ⚠️  " . count($report['null_tenant']) . " tabloda NULL tenant_id bulundu:\n\n";
    printf("  %-35s %10s %10s %10s %10s\n", "Tablo", "Toplam", "NULL", "Tenant=0", "Tenant=1");
    echo "  " . str_repeat('─', 75) . "\n";
    foreach ($report['null_tenant'] as $r) {
        printf("  %-35s %10d %10d %10d %10d\n",
            $r['table'], $r['total'], $r['null'], $r['zero'], $r['one']);
    }
}
echo "\n";

// 7b: tenant_id = 0
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  🟠 KATEGORİ 2: tenant_id = 0\n";
echo "══════════════════════════════════════════════════════════════════════\n";
if (empty($report['zero_tenant'])) {
    echo "  ✅ Hiç tenant_id=0 kaydı yok.\n";
} else {
    echo "  ⚠️  " . count($report['zero_tenant']) . " tabloda tenant_id=0 bulundu:\n\n";
    printf("  %-35s %10s %10s %10s\n", "Tablo", "Toplam", "Tenant=0", "Tenant=1");
    echo "  " . str_repeat('─', 65) . "\n";
    foreach ($report['zero_tenant'] as $r) {
        printf("  %-35s %10d %10d %10d\n", $r['table'], $r['total'], $r['zero'], $r['one']);
    }
}
echo "\n";

// 7c: Orphan
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  🔴 KATEGORİ 3: tenant_id ≠ 0, ≠ 1 (CROSS-TENANT RİSK)\n";
echo "══════════════════════════════════════════════════════════════════════\n";
if (empty($report['orphan'])) {
    echo "  ✅ Hiç orphan tenant_id kaydı yok.\n";
} else {
    echo "  🔴 " . count($report['orphan']) . " tabloda cross-tenant kayıt bulundu:\n\n";
    foreach ($report['orphan'] as $r) {
        echo "  ─ {$r['table']} ({$r['orphan']} kayıt)\n";
        foreach ($r['tenants'] as $tid => $cnt) {
            echo "    → tenant_id = $tid : $cnt kayıt\n";
        }
    }
    echo "\n";
}
echo "\n";

// 7d: Tenantless + FK
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  🟡 KATEGORİ 4: tenant_id YOK + FK VAR (İNDİREKT İZOLASYON)\n";
echo "══════════════════════════════════════════════════════════════════════\n";
if (empty($report['tenantless'])) {
    echo "  ✅ Tespit edilen yok.\n";
} else {
    echo "  ⚠️  " . count($report['tenantless']) . " tablo tenant_id yok ama FK var:\n\n";
    printf("  %-35s %10s  %-25s\n", "Tablo", "Kayıt", "FK Kolonları");
    echo "  " . str_repeat('─', 72) . "\n";
    foreach ($report['tenantless'] as $r) {
        printf("  %-35s %10d  %-25s\n",
            $r['table'], $r['row_count'], implode(', ', $r['fk_cols']));
    }
    echo "\n";
    echo "  ℹ️  Bu tablolarda tenant izolasyonu FK→ana_tablo üzerinden sağlanır.\n";
}
echo "\n";

// 7e: Temiz
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  ✅ KATEGORİ 5: TEMİZ (sadece tenant_id = 1)\n";
echo "══════════════════════════════════════════════════════════════════════\n";
if (empty($report['clean'])) {
    echo "  (yok)\n";
} else {
    printf("  %-35s %10s\n", "Tablo", "Kayıt");
    echo "  " . str_repeat('─', 50) . "\n";
    $shown = array_slice($report['clean'], 0, 20);
    foreach ($shown as $r) {
        printf("  %-35s %10d\n", $r['table'], $r['total']);
    }
    if (count($report['clean']) > 20) {
        echo "  ... ve " . (count($report['clean']) - 20) . " tablo daha (temiz)\n";
    }
}
echo "\n";

// ─── 8. Özet ───────────────────────────────────────────────────
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  📊 ÖZET\n";
echo "══════════════════════════════════════════════════════════════════════\n";
echo "  Tarama yapılan tablo (tenant_id'li) : " . count($tenantTables) . "\n";
echo "  tenant_id yok + FK var (indirekt)   : " . count($report['tenantless']) . "\n";
echo "  ─────────────────────────────────────────────────────────────\n";
echo "  Toplam kayıt                       : " . number_format($grandTotal['total']) . "\n";
echo "  ▸ NULL tenant_id                    : " . number_format($grandTotal['null']) . "\n";
echo "  ▸ tenant_id = 0                     : " . number_format($grandTotal['zero']) . "\n";
echo "  ▸ Orphan (≠0,≠1)                    : " . number_format($grandTotal['orphan']) . "\n";
echo "  Temiz tablo                         : " . count($report['clean']) . "\n";
echo "\n";
echo "  BelongsToTenant trait kullanan model sayısı: " . count($traitModels) . "\n";
echo "\n";

$critical = $grandTotal['null'] + $grandTotal['orphan'];
if ($critical > 0) {
    echo "  ⚠️  DİKKAT: $critical kayıt MÜDAHALE GEREKTİRİYOR.\n";
    echo "  ⚠️  tenant_id=1'e zorla atama YAPILMAMALI.\n";
    echo "\n";
    echo "  Sonraki adımlar:\n";
    echo "  1. NULL/orphan listeyi incele\n";
    echo "  2. FK ilişkileri üzerinden sahiplik çıkar\n";
    echo "  3. Belirsizleri quarantine tablosuna al\n";
    echo "  4. Operatör onayı al\n";
    echo "  5. Backfill migration uygula\n";
} else {
    echo "  ✅ Sistem temiz. Kritik orphan kayıt yok.\n";
}
echo "\n";

// ─── 9. Detay: NULL kayıtların FK yapısı ───────────────────────
if (!empty($report['null_tenant'])) {
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "  📋 DETAY: NULL tenant_id KAYITLARININ SAHİPLİK ANALİZİ\n";
    echo "══════════════════════════════════════════════════════════════════════\n";
    foreach ($report['null_tenant'] as $r) {
        if ($r['null'] == 0) continue;
        echo "\n  ─ {$r['table']} ({$r['null']} NULL kayıt) ─\n";
        $detail = $nullFkDetails[$r['table']] ?? null;
        if (!$detail || !$detail['has_fk']) {
            echo "    FK yok — KAYITLAR TAMAMEN SAHİPSİZ\n";
            echo "    → Karar: Sil veya manuel atama\n";
        } else {
            foreach ($detail['fks'] as $fk) {
                echo "    FK: {$fk['from']} → {$fk['to_table']}.{$fk['to_col']}\n";
            }
            $paths = [];
            if ($detail['has_ilan']) $paths[] = '→ ilan_id → ilanlar.tenant_id';
            if ($detail['has_kisi']) $paths[] = '→ kisi_id → kisiler.tenant_id';
            if ($detail['has_user']) $paths[] = '→ user_id → users.tenant_id';
            if ($paths) {
                echo "    Potansiyel sahiplik:\n";
                foreach ($paths as $p) { echo "      $p\n"; }
            } else {
                echo "    ⚠️  FK var ama tenant ilişkili değil — manuel incele\n";
            }
        }
    }
    echo "\n";
}

// ─── 10. BelongsToTenant Trait Özeti ───────────────────────────
if (!empty($traitModels)) {
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "  🏷️  BELONGSTOTENANT TRAIT KULLANAN MODELLER\n";
    echo "══════════════════════════════════════════════════════════════════════\n";
    foreach ($traitModels as $m) {
        echo "  • {$m['model']}\n";
    }
    echo "\n";
}

// ─── 11. JSON Rapor ────────────────────────────────────────────
$jsonReport = [
    'generated_at'          => date('Y-m-d H:i:s'),
    'db'                   => 'yalihanai_test.sqlite',
    'summary'              => [
        'total_tables_scanned' => count($tenantTables),
        'total_records'        => $grandTotal['total'],
        'null_tenant_count'  => $grandTotal['null'],
        'zero_tenant_count'  => $grandTotal['zero'],
        'orphan_count'        => $grandTotal['orphan'],
        'clean_table_count'   => count($report['clean']),
        'tenantless_fk_count' => count($report['tenantless']),
    ],
    'null_tenant_tables'   => array_map(fn($r) => [
        'table'   => $r['table'],
        'null'    => $r['null'],
        'total'   => $r['total'],
    ], $report['null_tenant']),
    'zero_tenant_tables'   => array_map(fn($r) => $r['table'], $report['zero_tenant']),
    'orphan_tables'        => $report['orphan'],
    'tenantless_with_fk'   => $report['tenantless'],
    'belongstotenant_models'=> $traitModels,
    'fk_detail'            => $nullFkDetails,
];

$reportsDir = __DIR__ . '/../../reports';
if (!is_dir($reportsDir)) { mkdir($reportsDir, 0755, true); }
$jsonPath = $reportsDir . '/tenant-orphan-audit-' . date('Y-m-d') . '.json';
file_put_contents($jsonPath, json_encode($jsonReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  📄 JSON rapor: $jsonPath\n";
echo "\n";
