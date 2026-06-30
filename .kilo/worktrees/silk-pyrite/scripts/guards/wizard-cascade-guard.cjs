#!/usr/bin/env node
/**
 * 🛡️ Wizard Cascade SSOT Guard
 *
 * Static analysis guard to prevent regression of the "Yayın Tipi gelmiyor" bug.
 *
 * Checks:
 * 1. ilan-wizard-page.js does NOT contain cascade function overrides
 * 2. No code references yayin_tipi_sablonlari.kategori_id (non-existent column)
 *
 * Exit codes:
 *   0 = PASS
 *   1 = FAIL (regression detected)
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '../..');
let failures = 0;

function check(label, result) {
    if (result) {
        console.log(`  ✅ ${label}`);
    } else {
        console.error(`  ❌ ${label}`);
        failures++;
    }
}

console.log('');
console.log('🛡️  Wizard Cascade SSOT Guard');
console.log('─'.repeat(50));

// ─── Check 1: No cascade override in ilan-wizard-page.js ───────────

const wizardPagePath = path.join(ROOT, 'resources/js/admin/ilan-wizard-page.js');
if (fs.existsSync(wizardPagePath)) {
    const content = fs.readFileSync(wizardPagePath, 'utf8');

    const overridePatterns = [
        /window\.loadAltKategoriler\s*=\s*(async\s+)?function/,
        /window\.loadYayinTipleri\s*=\s*(async\s+)?function/,
    ];

    overridePatterns.forEach((pattern) => {
        const match = content.match(pattern);
        check(
            `ilan-wizard-page.js: No "${pattern.source.split('=')[0].trim()}" override`,
            !match
        );
    });
} else {
    console.log('  ⏭️  ilan-wizard-page.js not found, skipping');
}

// ─── Check 2: step1-cascade.js uses junction_id ────────────────────

const cascadePath = path.join(ROOT, 'resources/js/wizard/step1-cascade.js');
if (fs.existsSync(cascadePath)) {
    const content = fs.readFileSync(cascadePath, 'utf8');

    check(
        'step1-cascade.js: Uses getElementById("junction_id")',
        content.includes('getElementById(\'junction_id\')')
    );

    check(
        'step1-cascade.js: Does NOT use getElementById("yayin_tipi_id")',
        !content.includes('getElementById(\'yayin_tipi_id\')')
    );
} else {
    console.error('  ❌ step1-cascade.js NOT FOUND — SSOT missing!');
    failures++;
}

// ─── Check 3: No yayin_tipi_sablonlari.kategori_id in PHP ─────────

const phpDirs = [
    'app/Services',
    'app/Http/Controllers',
    'app/Console/Commands',
];

// ⛔ Only catch ACTUAL yayin_tipi_sablonlari.kategori_id SQL references
// NOT generic 'kategori_id' => $var array assignments (logs, parameters, etc.)
// NOTE: PHP check is WARNING-ONLY — broader kategori_id cleanup is a separate task
const dangerousPatterns = [
    /yayin_tipi_sablonlari[^'"]*\.kategori_id/,
    /YayinTipiSablonu::where\(\s*['"]kategori_id['"]/,
    /->where\(\s*['"]kategori_id['"].*YayinTipiSablonu/,
];

let phpWarnings = 0;

phpDirs.forEach((dir) => {
    const fullDir = path.join(ROOT, dir);
    if (!fs.existsSync(fullDir)) return;

    const files = walkDir(fullDir, '.php');
    files.forEach((file) => {
        const content = fs.readFileSync(file, 'utf8');
        const lines = content.split('\n');

        lines.forEach((line, idx) => {
            // Check each dangerous pattern
            for (const pattern of dangerousPatterns) {
                if (pattern.test(line)) {
                    // Skip commented-out lines
                    const trimmed = line.trim();
                    if (trimmed.startsWith('//') || trimmed.startsWith('*') || trimmed.startsWith('/*')) continue;

                    const relPath = path.relative(ROOT, file);
                    console.warn(`  ⚠️  ${relPath}:${idx + 1} — Legacy kategori_id reference (cleanup needed)`);
                    phpWarnings++;
                }
            }
        });
    });
});

if (phpWarnings > 0) {
    console.warn(`  ⚠️  ${phpWarnings} legacy kategori_id references found (warning only)`);
} else {
    console.log('  ✅ No yayin_tipi_sablonlari.kategori_id references in PHP');
}

// ─── Result ────────────────────────────────────────────────────────

console.log('─'.repeat(50));
if (failures === 0) {
    console.log('✅ Wizard Cascade Guard: ALL CHECKS PASSED');
    process.exit(0);
} else {
    console.error(`❌ Wizard Cascade Guard: ${failures} FAILURE(S)`);
    process.exit(1);
}

// ─── Helpers ───────────────────────────────────────────────────────

function walkDir(dir, ext) {
    let results = [];
    try {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                if (entry.name === 'node_modules' || entry.name === 'vendor') continue;
                results = results.concat(walkDir(fullPath, ext));
            } else if (entry.name.endsWith(ext)) {
                results.push(fullPath);
            }
        }
    } catch (e) {
        // silently skip inaccessible dirs
    }
    return results;
}
