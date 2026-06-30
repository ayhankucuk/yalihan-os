#!/usr/bin/env node
/**
 * DAP Protocol — Dark Mode CI Guard (Context7 Compliant)
 *
 * CI gate that:
 * - Runs dark mode checker in dry mode
 * - Fails if violations detected
 * - Generates GITHUB_STEP_SUMMARY for CI visibility
 *
 * Usage:
 *   node scripts/ci-guard-dark-mode.js
 *
 * @context7 Dark Mode Hardening Protocol
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const JSON_REPORT_PATH = path.join(__dirname, '../.precheck/dark-mode.latest.json');
const MD_REPORT_PATH = path.join(__dirname, '../docs/_reports/DARK_MODE_REPORT.md');

console.log('🛡️  CI Guard — Dark Mode Check\n');

try {
    // Run checker in dry mode
    execSync('node scripts/fix-dark-mode.cjs --dry', {
        cwd: path.join(__dirname, '..'),
        stdio: 'inherit',
    });

    console.log('\n✅ CI Guard PASSED — No dark mode violations detected\n');

    // Generate GITHUB_STEP_SUMMARY if in CI
    if (process.env.GITHUB_STEP_SUMMARY) {
        try {
            const summary = [
                '## 🛡️ Dark Mode CI Guard Summary',
                '',
                '**Status**: ✅ PASS',
                '**Violations**: 0',
                '',
                '### Details',
                'All Blade and JS files are Context7 dark mode compliant.',
                '',
                '### Artifacts',
                `- [Dark Mode Report](${MD_REPORT_PATH})`,
                `- [JSON Report](${JSON_REPORT_PATH})`,
            ].join('\n');

            fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, summary, 'utf-8');
            console.log('📝 Written GITHUB_STEP_SUMMARY');
        } catch (e) {
            console.warn('⚠️ Could not write GITHUB_STEP_SUMMARY:', e.message);
        }
    }

    process.exit(0);
} catch (error) {
    // Violations detected
    console.log('\n❌ CI Guard FAILED — Dark mode violations detected\n');
    console.log('Run locally: npm run fix:darkmode\n');

    // Read JSON report for details
    let report = null;
    try {
        if (fs.existsSync(JSON_REPORT_PATH)) {
            report = JSON.parse(fs.readFileSync(JSON_REPORT_PATH, 'utf-8'));
        }
    } catch (e) {
        console.warn('⚠️ Could not read JSON report');
    }

    // Generate GITHUB_STEP_SUMMARY if in CI
    if (process.env.GITHUB_STEP_SUMMARY && report) {
        try {
            const topFiles = report.modifiedFiles.slice(0, 5);

            const summary = [
                '## 🛡️ Dark Mode CI Guard Summary',
                '',
                '**Status**: ❌ FAIL',
                `**Violations**: ${report.stats.filesModified} files need fixes`,
                '',
                '### Stats',
                `- Files Scanned: ${report.stats.filesScanned}`,
                `- Files with Violations: ${report.stats.filesModified}`,
                `- Dark Variants Needed: ${report.stats.injectionsCount}`,
                `- Redundant Variants: ${report.stats.normalizationsCount}`,
                '',
                '### Top 5 Files',
                '| File | Injections | Normalizations |',
                '|------|------------|----------------|',
            ];

            topFiles.forEach((file) => {
                summary.push(`| \`${file.path}\` | ${file.injections} | ${file.normalizations} |`);
            });

            summary.push('');
            summary.push('### Fix Command');
            summary.push('```bash');
            summary.push('npm run fix:darkmode');
            summary.push('```');
            summary.push('');
            summary.push('### Artifacts');
            summary.push(`- [Full Report](${MD_REPORT_PATH})`);
            summary.push(`- [JSON Data](${JSON_REPORT_PATH})`);

            fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, summary.join('\n'), 'utf-8');
            console.log('📝 Written GITHUB_STEP_SUMMARY with violation details');
        } catch (e) {
            console.warn('⚠️ Could not write GITHUB_STEP_SUMMARY:', e.message);
        }
    }

    process.exit(1);
}
