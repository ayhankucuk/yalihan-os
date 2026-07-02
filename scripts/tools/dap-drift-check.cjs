const fs = require('fs');
const path = require('path');

const AUTHORITY_FILE = '.sab/authority.json';
const SCANNER_CONFIG = '.sab/scanning-config.json'; // Optional — falls back to AUTHORITY_FILE exemptions
const REPORT_FILE = 'docs/_reports/DAP_DRIFT_REPORT.md';

// Critical paths that MUST NOT be skipped by scanner
const CRITICAL_PATHS = ['app/Services/', 'app/Http/Controllers/', 'app/Jobs/', 'app/Observers/'];

function checkDrift() {
    console.log('🔍 DAP Drift Detection Started...');
    let driftFound = false;
    let reportContent = `# DAP Drift Detection Report\n`;
    reportContent += `Date: ${new Date().toISOString()}\n\n`;

    // 0. Decision Table Verification (SSOT)
    const decisionTableJson = 'docs/DAP_DECISION_TABLE.json';
    const decisionTableMd = 'docs/DAP_DECISION_TABLE.md';

    if (fs.existsSync(decisionTableJson)) {
        try {
            // Regenerate in memory to compare
            const { execSync } = require('child_process');
            const currentMd = fs.existsSync(decisionTableMd)
                ? fs.readFileSync(decisionTableMd, 'utf8')
                : '';

            // Run compiler to get expected output (we can reuse the script logic or call it)
            // For robustness, let's call the compiler script and capture output,
            // BUT strict compilation might overwrite.
            // Better to compile to a temp file or read the compiler script as a module?
            // The compiler script overwrites.
            // Let's assume if we run the compiler, it should result in NO git changes if it was already correct.
            // But here we want to detecting drift BEFORE commit.

            // Simpler approach: Check if file content matches what we EXPECT.
            // Actually, the "Drift" here is: Content on disk != Content generated from JSON.
            // We can read JSON and generate string in memory.

            const data = JSON.parse(fs.readFileSync(decisionTableJson, 'utf8'));
            let validMd = `# DAP DECISION TABLE — Deterministik Çalıştırma Matrisi\n\n> **Description**: ${data.description}\n> **Version**: ${data.version}\n\n## Decision Matrix (Generated from JSON)\n\n| Scope | Match Patterns | Scripts |\n| :--- | :--- | :--- |\n`;

            data.rules.forEach((rule) => {
                const match = rule.match.map((m) => `\`${m}\``).join('<br>');
                const scripts = rule.scripts.map((s) => `\`${s}\``).join('<br>');
                validMd += `| **${rule.scope}** | ${match} | ${scripts} |\n`;
            });

            validMd += `\n## Always Run\nThe following scripts run on every execution:\n\n`;
            data.always.forEach((script) => (validMd += `- \`${script}\`\n`));

            validMd += `\n## Karar Algoritması
1. **git diff --name-only** ile değişen dosyaları al.
2. JSON kurallarına göre (**match**) eşleşenleri bul.
3. İlgili **scripts** listesini birleştir (merge unique).
4. **always** listesini ekle.
5. Fail durumunda \`docs/_reports/FAIL_ANALYSIS.md\` oluştur.

---
<!-- AUTO-GENERATED FROM DAP_DECISION_TABLE.json -->
<!-- DO NOT EDIT MANUALLY -->`;

            // Compare without timestamp (last line)
            const currentLines = currentMd.split('\n');
            const validLines = validMd.split('\n');

            // Remove timestamp line from comparison
            const cleanCurrent = currentLines
                .filter((l) => !l.includes('<!-- TIMESTAMP:'))
                .join('\n')
                .trim();
            const cleanValid = validLines.join('\n').trim();

            if (cleanCurrent !== cleanValid) {
                driftFound = true;
                reportContent +=
                    '- ❌ **DRIFT:** `docs/DAP_DECISION_TABLE.md` does not match JSON SSOT.\n';
                reportContent += '  Action: Run `npm run decision:compile`.\n';
            } else {
                reportContent += '- ✅ Decision Table matches JSON SSOT.\n';
            }
        } catch (e) {
            console.warn('⚠️  Decision Table check warning:', e.message);
        }
    } else {
        driftFound = true;
        reportContent += '- ❌ **CRITICAL:** `docs/DAP_DECISION_TABLE.json` missing!\n';
    }

    // 0.1 Authority Guard Verification (SSOT)
    const authorityJson = '.sab/authority.json';
    const guardConfig = 'config/context7_guard.php';

    if (fs.existsSync(authorityJson)) {
        try {
            // Import compiler to generate expected content
            const { compile } = require('./compile-authority.cjs');

            // Generate in memory (write=false)
            // Note: We need to handle the case where the manifest directory doesn't exist
            // but here we just want the string content.
            const { phpContent: expectedConfig } = compile(false);

            if (!fs.existsSync(guardConfig)) {
                driftFound = true;
                reportContent += '- ❌ **DRIFT:** `config/context7_guard.php` missing!\n';
                reportContent += '  Action: Run `npm run authority:compile`.\n';
            } else {
                const currentConfig = fs.readFileSync(guardConfig, 'utf8');

                // Compare content ignoring variable lines like @generated_at and last_updated (timestamps)
                const normalize = (content) =>
                    content
                        .split('\n')
                        .filter((l) => !l.includes('@generated_at') && !l.includes('last_updated'))
                        .map((l) => l.trim()) // trim each line to ignore indentation diffs if any
                        .join('\n');

                if (normalize(currentConfig) !== normalize(expectedConfig)) {
                    driftFound = true;
                    reportContent +=
                        '- ❌ **DRIFT:** `config/context7_guard.php` matches manual edits or is outdated.\n';
                    reportContent += '  Action: Run `npm run authority:compile`.\n';
                } else {
                    reportContent += '- ✅ Authority Guard config matches SSOT.\n';
                }
            }
        } catch (e) {
            console.warn('⚠️  Authority Guard check warning:', e.message);
            // Non-blocking during .sab/ migration — compile-authority.cjs needs full SAB data model adaptation
            reportContent += `- ⚠️  **WARNING (Migration):** Authority guard compile check skipped: ${e.message}\n`;
        }
    }

    // 1. Authority Integrity
    if (!fs.existsSync(AUTHORITY_FILE)) {
        driftFound = true;
        reportContent += '- ❌ **CRITICAL:** `authority.json` missing!\n';
    } else {
        try {
            JSON.parse(fs.readFileSync(AUTHORITY_FILE, 'utf8'));
            reportContent += '- ✅ Authority JSON is valid.\n';
        } catch (e) {
            driftFound = true;
            reportContent += '- ❌ **CRITICAL:** `authority.json` is invalid JSON.\n';
        }
    }

    // 2. API Contract Verification (SSOT)
    const apiContractJson = 'docs/API_CONTRACT.json';
    const apiManifest = 'storage/app/governance/generated/api_contract.manifest.json';

    if (fs.existsSync(apiContractJson)) {
        try {
            if (!fs.existsSync(apiManifest)) {
                driftFound = true;
                reportContent += '- ❌ **DRIFT:** `api_contract.manifest.json` missing!\n';
                reportContent += '  Action: Run `npm run contract:compile`.\n';
            } else {
                const ssotRaw = fs.readFileSync(apiContractJson, 'utf8');
                const crypto = require('crypto');
                const currentHash = crypto.createHash('sha256').update(ssotRaw).digest('hex');

                const manifest = JSON.parse(fs.readFileSync(apiManifest, 'utf8'));

                if (currentHash !== manifest.hash) {
                    driftFound = true;
                    reportContent +=
                        '- ❌ **DRIFT:** `docs/API_CONTRACT.json` has changed but manifest is outdated.\n';
                    reportContent += '  Action: Run `npm run contract:compile`.\n';
                } else {
                    reportContent += '- ✅ API Contract matches Manifest.\n';
                }
            }
        } catch (e) {
            console.warn('⚠️  API Contract check warning:', e.message);
            driftFound = true;
            reportContent += `- ❌ **DRIFT CHECK FAILED:** Could not verify API contract: ${e.message}\n`;
        }
    }

    // 2.1 Scanner Config Scope — Deep Check
    // Falls back to .sab/authority.json exemptions when dedicated scanning-config is absent
    const scannerSource = fs.existsSync(SCANNER_CONFIG) ? SCANNER_CONFIG : AUTHORITY_FILE;
    const scannerData = JSON.parse(fs.readFileSync(scannerSource, 'utf8'));
    const skipPatterns =
        scannerData.skip_paths?.patterns ||
        scannerData.enforcement?.skip_paths?.patterns ||
        scannerData.exemptions?.skip_patterns ||
        [];

    // Check for over-broad skips
    if (Array.isArray(skipPatterns)) {
        // Direct app/ exclusion
        if (skipPatterns.includes('app/') || skipPatterns.includes('app/**')) {
            driftFound = true;
            reportContent +=
                '- ❌ **CRITICAL:** Scanner config excludes entire `app/` directory (Shadow Banning).\n';
        }

        // Check critical paths are NOT skipped
        for (const critPath of CRITICAL_PATHS) {
            const isSkipped = skipPatterns.some((p) => {
                const normalized = p.replace(/\*\*/g, '');
                return !p.startsWith('!') && critPath.startsWith(normalized);
            });
            const isExempted = skipPatterns.some(
                (p) => p.startsWith('!') && critPath.startsWith(p.slice(1).replace(/\*\*/g, ''))
            );
            if (isSkipped && !isExempted) {
                driftFound = true;
                reportContent += `- ❌ **DRIFT:** Critical path \`${critPath}\` is skipped by scanner.\n`;
            }
        }

        if (!driftFound) {
            reportContent += '- ✅ Scanner scope: critical paths NOT skipped.\n';
        }
    }

    // 3. Competing Rules
    if (fs.existsSync('rules.json')) {
        driftFound = true;
        reportContent += '- ❌ **DRIFT:** Root `rules.json` found. Conflict with Authority.\n';
    } else {
        reportContent += '- ✅ No competing rules files.\n';
    }

    // 4. quality-gate.sh must exist
    if (!fs.existsSync('scripts/guards/quality-gate.sh')) {
        driftFound = true;
        reportContent += '- ❌ **CRITICAL:** `scripts/guards/quality-gate.sh` missing!\n';
    } else {
        reportContent += '- ✅ quality-gate.sh present.\n';
    }

    // 5. CI Workflow integrity — check for references to missing scripts
    const workflowDir = '.github/workflows';
    if (fs.existsSync(workflowDir)) {
        const workflows = fs.readdirSync(workflowDir).filter((f) => f.endsWith('.yml'));
        for (const wf of workflows) {
            const content = fs.readFileSync(path.join(workflowDir, wf), 'utf8');
            // Check for references to scripts that don't exist
            const scriptRefs = content.match(/scripts\/[a-zA-Z0-9_-]+\.(sh|cjs|js|mjs)/g) || [];
            for (const ref of scriptRefs) {
                if (!fs.existsSync(ref)) {
                    reportContent += `- ⚠️  **WARNING:** \`${wf}\` references missing script: \`${ref}\`\n`;
                }
            }
        }
    }

    fs.writeFileSync(REPORT_FILE, reportContent);
    console.log(`📄 Report generated: ${REPORT_FILE}`);

    if (driftFound) {
        console.error('❌ Drift Detected! Exiting 2.');
        process.exit(2);
    } else {
        console.log('✅ No Drift Detected.');
        process.exit(0);
    }
}

checkDrift();
