#!/usr/bin/env node

/**
 * CI Guard — Wizard Health Gate + SSOT Doc Generator
 *
 * Reads .precheck/wizard-env.latest.json and:
 *   1) Fails (exit 1) if any critical combo falls below thresholds
 *   2) Auto-generates docs/_reports/WIZARD_SYSTEM_STATUS.md (SSOT)
 *   3) Auto-generates docs/_reports/CI_GUARD_WIZARD.md
 *   4) Checks docs/_reports/TECHNICAL_DEBT.md consistency against live data
 *
 * Exit codes:
 *   0  All combos pass + docs consistent
 *   1  One or more combos fail thresholds OR docs claim "ok" but data says "failed"
 *   2  Input file missing
 */

import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { getPresetByKategoriId } from './category-presets.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const ENV_FILE = path.join(__dirname, '../.precheck/wizard-env.latest.json');
const REPORT_FILE = path.join(__dirname, '../docs/_reports/CI_GUARD_WIZARD.md');
const STATUS_FILE = path.join(__dirname, '../docs/_reports/WIZARD_SYSTEM_STATUS.md');
const DEBT_FILE = path.join(__dirname, '../docs/_reports/TECHNICAL_DEBT.md');

// ──────────────── Config ────────────────

const DEFAULTS = {
    min_health_score: 50,
    expected_template_min: 7,
    expected_features_min: 3,
    allowlisted_violations: ['missing_required_keys'],
};

const PRIORITY_IDS = [7, 8, 66, 67, 57, 33, 58, 59];

const INVARIANTS = [
    {
        id: 'priority_gate_100',
        description: 'Priority risk group (8 junctions) must have 100% health_score.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'wizard_step2_fields_non_empty',
        description: 'Wizard Step 2 fields + features must be >= threshold.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'junction_id_is_ssot',
        description: 'WizardContext API must use junction_id as SSOT.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'ui_ipuclari_is_ui_source',
        description: 'UI source must be ui_ipuclari -> template.fields zinciri.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'feature_whitelist_integrity',
        description: 'Features must not be silently dropped by whitelist.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'yazlik_quality_gate',
        description: 'Yazlık Kiralama combos must have health_score >= 60.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'darkmode_compliance',
        description: 'All Blade/JS files must have Context7-compliant dark mode variants.',
        failure_mode: 'CI_FAIL',
    },
    {
        id: 'ranking_integrity',
        description: 'Ranking scores and SEO meta must be valid (Phase 19 Invariants).',
        failure_mode: 'CI_FAIL',
    },
];

// ──────────────── Helpers ────────────────

function ensureDir(filePath) {
    const dir = path.dirname(filePath);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
}

function loadEnvFile() {
    if (fs.existsSync(ENV_FILE)) {
        return JSON.parse(fs.readFileSync(ENV_FILE, 'utf-8'));
    }

    console.log('⚠️  wizard-env.latest.json not found. Running generator...');
    try {
        execSync('node scripts/generate-wizard-env.js', {
            cwd: path.join(__dirname, '..'),
            encoding: 'utf-8',
            stdio: 'inherit',
        });
        if (fs.existsSync(ENV_FILE)) {
            return JSON.parse(fs.readFileSync(ENV_FILE, 'utf-8'));
        }
    } catch {
        // Fall through
    }

    console.error('❌ Cannot load or generate wizard-env.latest.json');
    process.exit(2);
}

// ──────────────── Gate Logic ────────────────

function evaluateCombo(combo) {
    const failures = [];
    const preset = getPresetByKategoriId(combo.kategori_id);

    const expectedTemplateMin = preset?.expected_template_min || DEFAULTS.expected_template_min;
    const expectedFeaturesMin = preset?.expected_features_min || DEFAULTS.expected_features_min;

    if (combo.health_state === 'failed') {
        failures.push({
            check: 'HEALTH_STATE_FAILED',
            detail: `health_score=${combo.health_score}, state=${combo.health_state}`,
        });
    }

    if ((combo.template_fields_count || 0) < expectedTemplateMin) {
        failures.push({
            check: 'TEMPLATE_FIELDS_BELOW_MIN',
            detail: `template_fields=${combo.template_fields_count}, expected>=${expectedTemplateMin}`,
        });
    }

    if ((combo.feature_schema_count || 0) < expectedFeaturesMin) {
        failures.push({
            check: 'FEATURE_SCHEMA_BELOW_MIN',
            detail: `feature_schema=${combo.feature_schema_count}, expected>=${expectedFeaturesMin}`,
        });
    }

    if (combo.violations && combo.violations.includes('feature_whitelist_drop')) {
        failures.push({
            check: 'FEATURE_WHITELIST_DROP',
            detail: 'Features exist in DB but silently dropped by whitelist filter',
        });
    }

    if (combo.violations) {
        const criticalViolations = combo.violations.filter(
            (v) => !DEFAULTS.allowlisted_violations.includes(v)
        );
        for (const v of criticalViolations) {
            if (v === 'feature_whitelist_drop') continue;
            if (
                v === 'insufficient_features' &&
                failures.some((f) => f.check === 'FEATURE_SCHEMA_BELOW_MIN')
            )
                continue;
            failures.push({ check: `VIOLATION_${v.toUpperCase()}`, detail: v });
        }
    }

    return failures;
}

// ──────────────── Invariant Enforcement (Strict Mode) ────────────────

function validateInvariants(wizardEnv) {
    const results = [];
    const combos = wizardEnv.all_combinations || [];

    for (const inv of INVARIANTS) {
        let passed = true;
        const violations = [];

        switch (inv.id) {
            case 'priority_gate_100':
                for (const c of combos) {
                    if (PRIORITY_IDS.includes(c.junction_id) && c.health_score < 100) {
                        passed = false;
                        violations.push(
                            `Priority Junction ${c.junction_id} (${c.name}) health is ${c.health_score}%, expected 100%`
                        );
                    }
                }
                break;

            case 'wizard_step2_fields_non_empty':
                for (const c of combos) {
                    if (
                        c.template_fields_count + c.feature_schema_count === 0 &&
                        c.health_score < 10
                    ) {
                        passed = false;
                        violations.push(`Junction ${c.junction_id} is EMPTY (Step 2 failure)`);
                    }
                }
                break;

            case 'junction_id_is_ssot':
                if (wizardEnv.ssot_param !== 'junction_id') {
                    passed = false;
                    violations.push(
                        `SSOT param is '${wizardEnv.ssot_param}', expected 'junction_id'`
                    );
                }
                break;

            case 'ui_ipuclari_is_ui_source':
                for (const c of combos) {
                    // If we have template fields but no ui_ipuclari was used to build them (heuristic check)
                    if (
                        c.template_fields_count > 0 &&
                        (!c.template_field_keys || c.template_field_keys.length === 0)
                    ) {
                        passed = false;
                        violations.push(
                            `Junction ${c.junction_id} has fields but source chain is broken`
                        );
                    }
                }
                break;

            case 'feature_whitelist_integrity':
                if (wizardEnv.summary?.has_whitelist_drop) {
                    passed = false;
                    const drops = combos.filter((c) =>
                        c.violations?.includes('feature_whitelist_drop')
                    );
                    for (const d of drops) {
                        violations.push(`Junction ${d.junction_id} has FEATURE_WHITELIST_DROP`);
                    }
                }
                break;

            case 'yazlik_quality_gate':
                for (const c of combos) {
                    if (c.kategori_id === 4 && c.health_score < 60) {
                        passed = false;
                        violations.push(
                            `Junction ${c.junction_id} (Yazlık) health is ${c.health_score}%, expected >= 60%`
                        );
                    }
                }
                break;

            case 'darkmode_compliance':
                try {
                    execSync('node scripts/fix-dark-mode.cjs --check', {
                        cwd: path.join(__dirname, '..'),
                        stdio: 'pipe',
                    });
                } catch (error) {
                    passed = false;
                    violations.push(
                        'Dark mode violations detected in codebase. Run: npm run darkmode:fix'
                    );
                }
                break;

            case 'ranking_integrity':
                try {
                    execSync('php artisan ranking:validate-invariants', {
                        cwd: path.join(__dirname, '..'),
                        stdio: 'pipe',
                    });
                } catch (error) {
                    passed = false;
                    violations.push(
                        'Ranking invariants integrity check failed. Run: php artisan ranking:validate-invariants'
                    );
                }
                break;
        }

        results.push({ ...inv, passed, violations });
    }

    return results;
}

// ──────────────── Docs Consistency ────────────────

function checkDocsConsistency(combos) {
    const warnings = [];
    let docsFail = false;

    if (!fs.existsSync(DEBT_FILE)) {
        warnings.push({
            level: 'INFO',
            message: 'TECHNICAL_DEBT.md not found — skipping consistency check',
        });
        return { warnings, docsFail };
    }

    const debtContent = fs.readFileSync(DEBT_FILE, 'utf-8').toLowerCase();

    const failedCombos = combos.filter((c) => c.health_state === 'failed');
    const partialCombos = combos.filter((c) => c.health_state === 'partial');
    const healthyCombos = combos.filter((c) => c.health_state === 'healthy');

    // CHECK 1: Doc says "everything ok" but data says FAILED → FAIL
    const docClaimsAllOk =
        debtContent.includes('tüm sistemler sağlıklı') ||
        debtContent.includes('all systems healthy') ||
        (debtContent.includes('kritik açık yok') && !debtContent.includes('🔴'));

    if (docClaimsAllOk && failedCombos.length > 0) {
        warnings.push({
            level: 'FAIL',
            message: `TECHNICAL_DEBT.md claims all healthy, but ${failedCombos.length} combos FAILED in wizard-env`,
        });
        docsFail = true;
    }

    // CHECK 2: Doc says "konut eksik" but data is healthy → WARN (stale doc)
    const docClaimsKonutMissing =
        debtContent.includes('konut template') &&
        (debtContent.includes('eksik') || debtContent.includes('missing')) &&
        !debtContent.includes('fixed') &&
        !debtContent.includes('çözüldü');

    const konutCombos = combos.filter((c) => c.kategori_id === 1);
    const konutHealthy = konutCombos.every((c) => c.health_state === 'healthy');

    if (docClaimsKonutMissing && konutHealthy) {
        warnings.push({
            level: 'WARN',
            message:
                'TECHNICAL_DEBT.md says Konut is missing/broken, but wizard-env shows Konut healthy. Doc is STALE.',
        });
    }

    // CHECK 3: Doc says "arsa eksik" but arsa is healthy → WARN
    const docClaimsArsaMissing =
        debtContent.includes('arsa') &&
        (debtContent.includes('eksik') || debtContent.includes('missing')) &&
        !debtContent.includes('fixed') &&
        !debtContent.includes('çözüldü');

    const arsaCombos = combos.filter((c) => c.kategori_id === 3);
    const arsaHealthy = arsaCombos.every((c) => c.health_state === 'healthy');

    if (docClaimsArsaMissing && arsaHealthy) {
        warnings.push({
            level: 'WARN',
            message:
                'TECHNICAL_DEBT.md says Arsa is missing/broken, but wizard-env shows Arsa healthy. Doc is STALE.',
        });
    }

    // CHECK 4: Data has failed combos but doc doesn't mention them → WARN
    for (const fc of failedCombos) {
        const categoryLower = (fc.category_name || fc.name || '').toLowerCase();
        if (categoryLower && !debtContent.includes(categoryLower)) {
            warnings.push({
                level: 'WARN',
                message: `Junction ${fc.junction_id} (${fc.name}) is FAILED but not mentioned in TECHNICAL_DEBT.md`,
            });
        }
    }

    return { warnings, docsFail };
}

// ──────────────── WIZARD_SYSTEM_STATUS.md ────────────────

function generateSystemStatus(wizardEnv, results, docChecks) {
    const now = new Date().toISOString();
    const combos = wizardEnv.all_combinations || [];
    const summary = wizardEnv.summary || {};
    const failCount = results.filter((r) => !r.passed).length;

    const lines = [
        '# Wizard System Summary (SSOT)',
        '',
        '> [!IMPORTANT]',
        '> Bu dosya `ci-guard-wizard.js` tarafından otomatik oluşturulur. Manuel düzenleme YAPMAYIN.',
        '> Son güncelleme için: `npm run e2e:ci-guard`',
        '',
        `**Last Run**: ${now}`,
        `**Command**: \`npm run e2e:wizard:smart\``,
        `**Source**: \`.precheck/wizard-env.latest.json\``,
        `**Generator**: ${wizardEnv.generator_version || 'unknown'}`,
        '',
        '## Health Summary',
        '',
        '| Junction ID | Publish Type ID | Category | Yayın Tipi | Health | Fields | Features | Violations | Result |',
        '|------------|----------------|----------|-----------|--------|--------|----------|------------|--------|',
    ];

    for (const r of results) {
        const c = r.combo;
        const icon = r.passed ? '✅' : '❌';
        const violationCount = c.violations ? c.violations.length : 0;
        lines.push(
            `| ${c.junction_id} | ${c.publish_type_id ?? '—'} | ${c.category_name || '?'} | ${c.yayin_tipi_name || '?'} | ${c.health_score}% | ${c.template_fields_count} | ${c.feature_schema_count} | ${violationCount} | ${icon} |`
        );
    }

    lines.push('');
    lines.push(`**Gate**: ${failCount === 0 ? 'PASS ✅' : 'FAIL ❌'}`);
    lines.push(
        `**Totals**: ${summary.healthy || 0} healthy, ${summary.partial || 0} partial, ${summary.failed || 0} failed`
    );
    lines.push('');

    // Critical risks
    const failedResults = results.filter((r) => !r.passed);
    if (failedResults.length > 0) {
        lines.push('## Critical Risks (CI Guard Failures)');
        lines.push('');
        for (const r of failedResults) {
            lines.push(`### ${r.combo.name} (junction ${r.combo.junction_id})`);
            for (const f of r.failures) {
                lines.push(`- **${f.check}**: ${f.detail}`);
            }
            lines.push('');
        }
    }

    // Docs consistency
    if (docChecks.warnings.length > 0) {
        lines.push('## Docs Consistency Checks');
        lines.push('');
        for (const w of docChecks.warnings) {
            const icon = w.level === 'FAIL' ? '🔴' : w.level === 'WARN' ? '🟡' : 'ℹ️';
            lines.push(`- ${icon} **${w.level}**: ${w.message}`);
        }
        lines.push('');
    } else {
        lines.push('## Docs Consistency');
        lines.push('');
        lines.push('> [!TIP]');
        lines.push('> TECHNICAL_DEBT.md is consistent with live data.');
        lines.push('');
    }

    // Feature detail
    lines.push('## Feature Detail');
    lines.push('');
    for (const c of combos) {
        lines.push(`### ${c.name} (junction ${c.junction_id})`);
        lines.push(`- **Template fields**: ${(c.template_field_keys || []).join(', ') || '—'}`);
        lines.push(`- **Feature keys**: ${(c.feature_schema_keys || []).join(', ') || '—'}`);
        lines.push(`- **Violations**: ${(c.violations || []).join(', ') || 'none'}`);
        lines.push('');
    }

    // Invariant Enforcement
    const invariantResults = docChecks.invariants || [];
    if (invariantResults.length > 0) {
        lines.push('## Policy Invariants (Strict Mode)');
        lines.push('');
        lines.push('| Invariant ID | Status | Description |');
        lines.push('|--------------|--------|-------------|');
        for (const ir of invariantResults) {
            const icon = ir.passed ? '✅' : '🔴';
            lines.push(`| ${ir.id} | ${icon} | ${ir.description} |`);
        }
        lines.push('');

        const failingInvariants = invariantResults.filter((ir) => !ir.passed);
        if (failingInvariants.length > 0) {
            lines.push('### Invariant Violations');
            lines.push('');
            for (const fi of failingInvariants) {
                lines.push(`#### 🔴 ${fi.id}`);
                for (const v of fi.violations) {
                    lines.push(`- ${v}`);
                }
                lines.push('');
            }
        }
    }

    // References
    lines.push('## References');
    lines.push('');
    lines.push('- **Source JSON**: `.precheck/wizard-env.latest.json`');
    lines.push('- **CI Guard Report**: `docs/_reports/CI_GUARD_WIZARD.md`');
    lines.push('- **Auto-Heal Report**: `docs/_reports/AUTO_HEAL_REPORT.md`');
    lines.push('- **Technical Debt**: `docs/_reports/TECHNICAL_DEBT.md`');
    lines.push('- **Diagnosis Workflow**: `.agent/workflows/wizard-step2-diagnosis.md`');

    return lines.join('\n');
}

// ──────────────── CI Guard Report ────────────────

function generateReport(results, wizardEnv, docChecks) {
    const now = new Date().toISOString();
    const lines = [
        '# CI Guard — Wizard Health Gate Report',
        '',
        `**Generated**: ${now}`,
        `**Source**: \`.precheck/wizard-env.latest.json\``,
        '',
        '## Results',
        '',
        '| Junction | Category | Publish Type | Health | Template | Features | Result |',
        '|----------|----------|-------------|--------|----------|----------|--------|',
    ];

    for (const r of results) {
        const icon = r.passed ? '✅' : '❌';
        lines.push(
            `| ${r.combo.junction_id} | ${r.combo.category_name || '?'} | ${r.combo.yayin_tipi_name || '?'} | ${r.combo.health_score}% | ${r.combo.template_fields_count} | ${r.combo.feature_schema_count} | ${icon} |`
        );
    }

    lines.push('');

    const failing = results.filter((r) => !r.passed);
    if (failing.length > 0) {
        lines.push('## Failures');
        lines.push('');
        for (const r of failing) {
            lines.push(`### ${r.combo.name} (junction ${r.combo.junction_id})`);
            lines.push('');
            for (const f of r.failures) {
                lines.push(`- **${f.check}**: ${f.detail}`);
            }
            lines.push('');
        }
    } else {
        lines.push('> [!TIP]');
        lines.push('> All wizard combinations passed CI gate.');
        lines.push('');
    }

    // Invariants section
    const invariantResults = docChecks.invariants || [];
    if (invariantResults.length > 0) {
        lines.push('## Policy Invariants');
        lines.push('');
        lines.push('| ID | Status | Description |');
        lines.push('|----|--------|-------------|');
        for (const ir of invariantResults) {
            lines.push(`| ${ir.id} | ${ir.passed ? '✅' : '🔴'} | ${ir.description} |`);
        }
        lines.push('');
    }

    // Docs consistency section
    if (docChecks.warnings.length > 0) {
        lines.push('## Docs Consistency');
        lines.push('');
        for (const w of docChecks.warnings) {
            const icon = w.level === 'FAIL' ? '🔴' : w.level === 'WARN' ? '🟡' : 'ℹ️';
            lines.push(`- ${icon} **${w.level}**: ${w.message}`);
        }
        lines.push('');
    }

    const passCount = results.filter((r) => r.passed).length;
    lines.push('## Summary');
    lines.push('');
    lines.push(`- **Total**: ${results.length}`);
    lines.push(`- **Passed**: ${passCount}`);
    lines.push(`- **Failed**: ${failing.length}`);
    lines.push(
        `- **Docs Consistent**: ${docChecks.warnings.length === 0 ? 'YES ✅' : `${docChecks.warnings.length} issue(s)`}`
    );
    lines.push(
        `- **Gate**: ${failing.length === 0 && !docChecks.docsFail ? 'PASS ✅' : 'FAIL ❌'}`
    );

    return lines.join('\n');
}

// ──────────────── Main ────────────────

function main() {
    console.log('🛡️  CI Guard — Wizard Health Gate + SSOT');
    console.log('');

    const wizardEnv = loadEnvFile();
    const combos = wizardEnv.all_combinations || [];

    if (combos.length === 0) {
        console.error('❌ No combinations found in wizard-env.latest.json');
        process.exit(2);
    }

    console.log(`📊 Evaluating ${combos.length} combinations\n`);

    // 1) Health gate
    const results = [];
    for (const combo of combos) {
        const failures = evaluateCombo(combo);
        const passed = failures.length === 0;
        results.push({ combo, failures, passed });

        if (passed) {
            console.log(
                `  ✅ ${combo.name} (junction ${combo.junction_id}) — ${combo.health_score}%`
            );
        } else {
            console.log(
                `  ❌ ${combo.name} (junction ${combo.junction_id}) — ${combo.health_score}%`
            );
            for (const f of failures) {
                console.log(`     ↳ ${f.check}: ${f.detail}`);
            }
        }
    }

    // 2) Invariant enforcement (Strict Mode)
    console.log('\n🛡️  Checking Policy Invariants...');
    const invariantResults = validateInvariants(wizardEnv);
    for (const ir of invariantResults) {
        console.log(`  ${ir.passed ? '✅' : '🔴'} ${ir.id}: ${ir.description}`);
        if (!ir.passed) {
            for (const v of ir.violations) {
                console.log(`     ↳ ${v}`);
            }
        }
    }

    // 3) Docs consistency check...
    console.log('\n📄 Docs consistency check...');
    const docChecks = checkDocsConsistency(combos);
    docChecks.invariants = invariantResults; // Pass to generators

    for (const w of docChecks.warnings) {
        const icon = w.level === 'FAIL' ? '🔴' : w.level === 'WARN' ? '🟡' : 'ℹ️';
        console.log(`  ${icon} ${w.level}: ${w.message}`);
    }
    if (docChecks.warnings.length === 0) {
        console.log('  ✅ TECHNICAL_DEBT.md consistent with live data');
    }

    // 4) Generate WIZARD_SYSTEM_STATUS.md (SSOT)
    ensureDir(STATUS_FILE);
    fs.writeFileSync(STATUS_FILE, generateSystemStatus(wizardEnv, results, docChecks), 'utf-8');
    console.log(`\n📊 SSOT: ${STATUS_FILE}`);

    // 5) Generate CI Guard report
    ensureDir(REPORT_FILE);
    fs.writeFileSync(REPORT_FILE, generateReport(results, wizardEnv, docChecks), 'utf-8');
    console.log(`📋 Report: ${REPORT_FILE}`);

    // 6) Gate decision (compute before summary)
    const failCount = results.filter((r) => !r.passed).length;
    const invFailCount = invariantResults.filter(
        (ir) => !ir.passed && ir.failure_mode === 'CI_FAIL'
    ).length;
    const passCount = results.filter((r) => r.passed).length;
    const gatePass = failCount === 0 && invFailCount === 0 && !docChecks.docsFail;

    // 7) Write GITHUB_STEP_SUMMARY (CI only)
    if (process.env.GITHUB_STEP_SUMMARY) {
        try {
            // DAP Protocol Risk Buckets
            const risks = {
                CRITICAL_TEMPLATE_MISSING: [],
                PARTIAL_UI_IPUCLARI_EMPTY: [],
                WHITELIST_DROP: [],
                OTHER: [],
            };

            const failedResults = results.filter((r) => !r.passed);

            for (const r of failedResults) {
                const c = r.combo;
                if (!c.template_id) {
                    risks.CRITICAL_TEMPLATE_MISSING.push(r);
                } else if (c.template_fields_count === 0) {
                    risks.PARTIAL_UI_IPUCLARI_EMPTY.push(r);
                } else if (c.feature_schema_count === 0) {
                    risks.WHITELIST_DROP.push(r);
                } else {
                    risks.OTHER.push(r);
                }
            }

            const summaryLines = [
                '## 🛡️ Wizard CI Guard Summary',
                '',
                `**Health Gate**: ${gatePass ? '✅ PASS' : '❌ FAIL'}`,
                `**Docs Consistency**: ${docChecks.warnings.length === 0 ? '✅ Valid' : `⚠️ ${docChecks.warnings.length} Issues`}`,
                '',
            ];

            if (failedResults.length > 0) {
                summaryLines.push('### 📊 Risk Buckets (Root Cause)');
                summaryLines.push('| Bucket | Count | Description |');
                summaryLines.push('|---|---|---|');
                summaryLines.push(
                    `| 🔴 CRITICAL_TEMPLATE_MISSING | ${risks.CRITICAL_TEMPLATE_MISSING.length} | Missing valid UPS Template ID |`
                );
                summaryLines.push(
                    `| 🟠 PARTIAL_UI_IPUCLARI_EMPTY | ${risks.PARTIAL_UI_IPUCLARI_EMPTY.length} | Template exists but UI tips empty |`
                );
                summaryLines.push(
                    `| 🟡 WHITELIST_DROP | ${risks.WHITELIST_DROP.length} | Zero features (Check whitelist/schema) |`
                );
                summaryLines.push(
                    `| ⚪ OTHER | ${risks.OTHER.length} | Other threshold failures |`
                );
                summaryLines.push('');

                summaryLines.push('### ❌ Critical Failures Detail');
                summaryLines.push('| Junction | Name | Risk | Health |');
                summaryLines.push('|---|---|---|---|');

                // Prioritize listing order by risk severity
                const allRisks = [
                    ...risks.CRITICAL_TEMPLATE_MISSING.map((r) => ({
                        ...r,
                        risk: 'CRITICAL_TEMPLATE_MISSING',
                    })),
                    ...risks.PARTIAL_UI_IPUCLARI_EMPTY.map((r) => ({
                        ...r,
                        risk: 'PARTIAL_UI_IPUCLARI_EMPTY',
                    })),
                    ...risks.WHITELIST_DROP.map((r) => ({ ...r, risk: 'WHITELIST_DROP' })),
                    ...risks.OTHER.map((r) => ({ ...r, risk: 'OTHER' })),
                ];

                allRisks.forEach((r) => {
                    summaryLines.push(
                        `| ${r.combo.junction_id} | ${r.combo.name} | ${r.risk} | ${r.combo.health_score}% |`
                    );
                });
                summaryLines.push('');
            }

            // Collapsible Detail Table
            summaryLines.push(
                '<details><summary><strong>Click to see full Health Matrix (68 Junctions)</strong></summary>'
            );
            summaryLines.push('');
            summaryLines.push('| Junction | Category | Health | Templ | Feat | Status |');
            summaryLines.push('|---|---|---|---|---|---|');
            for (const r of results) {
                summaryLines.push(
                    `| ${r.combo.junction_id} | ${r.combo.name} | ${r.combo.health_score}% | ${r.combo.template_fields_count} | ${r.combo.feature_schema_count} | ${r.passed ? '✅' : '❌'} |`
                );
            }
            summaryLines.push('');
            summaryLines.push('</details>');
            summaryLines.push('');

            // Invariant Violations
            const invFailures = invariantResults.filter((ir) => !ir.passed);
            if (invFailures.length > 0) {
                summaryLines.push('### 🛡️ Policy Violations');
                invFailures.forEach((ir) => {
                    summaryLines.push(`- 🔴 **${ir.id}**: ${ir.description}`);
                });
                summaryLines.push('');
            }

            // Artifact Links
            summaryLines.push('### 📚 Artifacts & Reports');
            summaryLines.push(
                '- [📄 Full Report (CI_GUARD_WIZARD.md)](docs/_reports/CI_GUARD_WIZARD.md)'
            );
            summaryLines.push(
                '- [📊 System Status (WIZARD_SYSTEM_STATUS.md)](docs/_reports/WIZARD_SYSTEM_STATUS.md)'
            );
            summaryLines.push(
                '- [🛠️ Auto-Heal Report (AUTO_HEAL_REPORT.md)](docs/_reports/AUTO_HEAL_REPORT.md)'
            );

            fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, summaryLines.join('\n'), 'utf-8');
            console.log('📝 Written detailed GITHUB_STEP_SUMMARY');
        } catch (e) {
            console.warn('⚠️ Could not write GITHUB_STEP_SUMMARY:', e.message);
        }
    }

    console.log('');
    console.log('═'.repeat(50));
    console.log('🛡️  CI Guard Result');
    console.log(`   Combos: ${passCount}/${results.length} passed`);
    console.log(
        `   Docs: ${docChecks.warnings.length === 0 ? 'consistent ✅' : `${docChecks.warnings.length} issue(s)`}`
    );
    console.log(`   Gate: ${gatePass ? 'PASS ✅' : 'FAIL ❌'}`);
    console.log('═'.repeat(50));

    if (!gatePass) {
        process.exit(1);
    }
}

main();
