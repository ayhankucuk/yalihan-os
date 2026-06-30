#!/usr/bin/env node
/**
 * DAP Decision Engine (Deterministic)
 *
 * Analyzes git diff and determines which scripts to run based on DAP_DECISION_TABLE.md
 * NO || true fallbacks - every decision is explicit and logged.
 *
 * Exit codes:
 * 0 = All required scripts passed
 * 1 = One or more scripts failed
 * 2 = Decision engine error (drift, config issue)
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🧠 DAP Decision Engine Started...');

// 🛡️ DRIFT PROTECTION: Ensure DAP_DECISION_TABLE.md is unique
try {
    const decisionTablePath = path.join(process.cwd(), 'docs', 'DAP_DECISION_TABLE.md');
    if (fs.existsSync(decisionTablePath)) {
        const content = fs.readFileSync(decisionTablePath, 'utf8');
        // Count occurrences of the main header
        const headerMatch = content.match(/^# DAP DECISION TABLE/gm);
        if (headerMatch && headerMatch.length > 1) {
            console.error('\n🚨 DRIFT DETECTED: docs/DAP_DECISION_TABLE.md has duplicate sections!');
            console.error(`Found ${headerMatch.length} occurrences of "# DAP DECISION TABLE".`);
            console.error('Action: Please deduplicate the file to a single source of truth.');

            // Write report
            const reportDir = path.join(process.cwd(), 'docs', '_reports');
            if (!fs.existsSync(reportDir)) fs.mkdirSync(reportDir, { recursive: true });
            fs.writeFileSync(
                path.join(reportDir, 'DRIFT_DAP_DECISION_TABLE.md'),
                `# DRIFT DETECTED\n\nDate: ${new Date().toISOString()}\nFile: docs/DAP_DECISION_TABLE.md\nIssue: Multiple H1 headers found (${headerMatch.length})\nUser Action Required: Deduplicate immediately.`
            );

            process.exit(2);
        }
    }
} catch (e) {
    console.warn('⚠️  Drift check warning:', e.message);
}

// Get changed files (git diff)
let changedFiles = [];
try {
    const diff = execSync('git diff --name-only HEAD~1 2>/dev/null || echo ""', {
        encoding: 'utf8',
        cwd: process.cwd(),
    }).trim();

    if (!diff) {
        console.log('⚠️  No git history or changes detected. Running minimum set.');
    } else {
        changedFiles = diff.split('\n').filter((f) => f.trim());
        console.log(`📄 ${changedFiles.length} file(s) changed`);
    }
} catch (e) {
    console.warn('⚠️  Git diff failed, running minimum set');
}

// Decision rules (from DAP_DECISION_TABLE.md)
const rules = {
    'app/Models/V2/': ['context7', 'gate'],
    'app/Http/Controllers/Api/V2/': ['context7', 'e2e:wizard:smart', 'gate'],
    'app/Jobs/': ['context7', 'gate'],
    'app/Observers/': ['context7', 'gate'],
    'resources/views/wizard/': ['e2e:wizard:smart', 'gate'],
    'docs/': ['dap:docs'],
    'scripts/': ['dap:drift', 'gate'],
    'package.json': ['dap:drift', 'gate'],
    '.context7/': ['dap:drift', 'gate'],
    '.css': ['check:darkmode', 'gate'],
    'tests/': ['gate'],
};

// Minimum set (always runs)
const minimumSet = ['context7', 'dap:drift', 'dap:docs', 'gate'];
const scriptsToRun = new Set(minimumSet);

// Analyze changed files
changedFiles.forEach((file) => {
    Object.keys(rules).forEach((pattern) => {
        if (file.includes(pattern) || file.endsWith(pattern)) {
            rules[pattern].forEach((script) => scriptsToRun.add(script));
        }
    });
});

console.log(`📋 Scripts to run: ${Array.from(scriptsToRun).join(', ')}`);

// Map script names to commands
const scriptCommands = {
    context7: 'php artisan sab:integrity-scan',
    'dap:drift': 'node ./scripts/dap-drift-check.cjs',
    'dap:docs': 'node ./scripts/dap-map-check.cjs && node ./scripts/dap-docs-governance.cjs',
    'e2e:wizard:smart': 'node scripts/dap-run-if-exists.cjs e2e:wizard:smart',
    'check:darkmode': 'node scripts/fix-dark-mode.cjs --dry',
    gate: './scripts/quality-gate.sh',
};

// Execute scripts in order
const failed = [];
for (const script of scriptsToRun) {
    const cmd = scriptCommands[script];
    if (!cmd) {
        console.warn(`⚠️  Unknown script: ${script} - SKIP`);
        continue;
    }

    console.log(`\n▶️  Running: ${script}`);
    try {
        execSync(cmd, { stdio: 'inherit', cwd: process.cwd() });
        console.log(`✅ ${script} PASSED`);
    } catch (err) {
        console.error(`❌ ${script} FAILED (exit ${err.status || 1})`);
        failed.push(script);

        // Stop on first failure (fail-fast)
        console.error(`\n🛑 Decision Engine: FAIL (${script} failed)`);
        process.exit(1);
    }
}

if (failed.length === 0) {
    console.log('\n✅ Decision Engine: ALL PASSED');
    process.exit(0);
} else {
    console.error(`\n❌ Decision Engine: ${failed.length} FAILED`);
    process.exit(1);
}
