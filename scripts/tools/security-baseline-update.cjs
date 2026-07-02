#!/usr/bin/env node
/**
 * Security Baseline Update (MANUAL ONLY)
 *
 * Updates security-audit.baseline.json with current findings.
 *
 * WARNING: This script should NEVER be called by autopilot.
 * Only run manually when you have verified findings are false positives.
 *
 * Usage: npm run security:baseline:update
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASELINE_PATH = path.resolve(__dirname, 'security-audit.baseline.json');
const AUDIT_SCRIPT = path.resolve(__dirname, 'security-audit.sh');

console.log('🔒 Security Baseline Update (MANUAL)\n');
console.log('⚠️  This will accept ALL current findings as baseline');
console.log('⚠️  Only proceed if you have manually verified these are false positives\n');

// Run security audit
let auditOutput = '';
try {
    auditOutput = execSync(`bash ${AUDIT_SCRIPT}`, {
        encoding: 'utf8',
        cwd: process.cwd(),
    });
} catch (err) {
    auditOutput = err.stdout || err.message;
}

// Parse findings (simple pattern matching)
const lines = auditOutput.split('\n');
const findings = [];

lines.forEach((line) => {
    if (line.includes('Potansiyel secret bulundu') || line.match(/\.(php|blade\.php|js):/)) {
        // Extract file and pattern
        const match = line.match(
            /([a-zA-Z0-9/_.-]+\.(php|blade\.php|js)):.*?(password|token|secret|api_key)/i
        );
        if (match) {
            const file = match[1];
            const pattern = match[3].toLowerCase();
            const lineContent = line.split(':')[1] || '';

            findings.push({
                file,
                pattern,
                line_contains: lineContent.trim().substring(0, 80),
                reason: 'Auto-accepted via baseline update - verify manually',
            });
        }
    }
});

// Create or update baseline
const baseline = {
    version: 1,
    generated_at: new Date().toISOString(),
    description: 'Security Audit Baseline - accepted findings (false positives)',
    accepted_findings: findings,
    notes: `Baseline last updated: ${new Date().toISOString().split('T')[0]}. To update: npm run security:baseline:update (manual only)`,
};

fs.writeFileSync(BASELINE_PATH, JSON.stringify(baseline, null, 2));

console.log(`✅ Baseline updated: ${findings.length} finding(s) accepted`);
console.log(`📄 Written to: ${BASELINE_PATH}`);
console.log(`\n⚠️  IMPORTANT: Review accepted findings and add proper reasons!`);

process.exit(0);
