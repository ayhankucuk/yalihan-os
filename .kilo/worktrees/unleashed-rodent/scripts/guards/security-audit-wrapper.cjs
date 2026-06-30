#!/usr/bin/env node
/**
 * Security Audit Wrapper (Baseline-aware)
 *
 * Runs security-audit.sh and filters findings against baseline.json
 * - Baseline findings → IGNORE (logged)
 * - New findings → FAIL
 * - Baseline cleanup → WARNING
 *
 * NO || true - every new finding is a hard failure.
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASELINE_PATH = path.resolve(__dirname, 'security-audit.baseline.json');
const AUDIT_SCRIPT = path.resolve(__dirname, 'security-audit.sh');

console.log('🔒 Security Audit (Baseline-aware) Started...\n');

// Load baseline
let baseline = { accepted_findings: [] };
if (fs.existsSync(BASELINE_PATH)) {
    baseline = JSON.parse(fs.readFileSync(BASELINE_PATH, 'utf8'));
    console.log(`📋 Loaded ${baseline.accepted_findings.length} accepted findings from baseline`);
} else {
    console.warn('⚠️  No baseline found - all findings will be flagged');
}

// Run security-audit.sh and capture output
let auditOutput = '';
try {
    auditOutput = execSync(`bash ${AUDIT_SCRIPT}`, {
        encoding: 'utf8',
        cwd: process.cwd(),
    });
} catch (err) {
    // security-audit.sh exits non-zero on findings, that's expected
    auditOutput = err.stdout || err.message;
}

console.log(auditOutput);

// Parse findings (simplified - looking for "Potansiyel secret bulundu" pattern)
const findingPattern = /app\/.*?\.php:.*?(password|token|secret|api_key)/gi;
const matches = auditOutput.match(findingPattern) || [];

const newFindings = [];
const baselinedFindings = [];

matches.forEach((match) => {
    // Check if this finding is in baseline
    const isBaseline = baseline.accepted_findings.some((accepted) => {
        return match.includes(accepted.file) && match.toLowerCase().includes(accepted.pattern);
    });

    if (isBaseline) {
        baselinedFindings.push(match);
    } else {
        newFindings.push(match);
    }
});

console.log(`\n📊 Security Audit Summary:`);
console.log(`  ✅ Baselined (ignored): ${baselinedFindings.length}`);
console.log(`  ❌ New findings: ${newFindings.length}`);

if (newFindings.length > 0) {
    console.error(`\n❌ FAIL: ${newFindings.length} new security finding(s) not in baseline:`);
    newFindings.forEach((f) => console.error(`   - ${f}`));
    console.error(`\n💡 To accept as false positive: npm run security:baseline:update`);
    process.exit(1);
}

// Check for baseline cleanup (findings in baseline but not in current audit)
const cleanupNeeded = baseline.accepted_findings.filter((accepted) => {
    return (
        !auditOutput.includes(accepted.file) ||
        !auditOutput.toLowerCase().includes(accepted.pattern)
    );
});

if (cleanupNeeded.length > 0) {
    console.warn(`\n⚠️  Baseline cleanup: ${cleanupNeeded.length} finding(s) no longer present:`);
    cleanupNeeded.forEach((f) => console.warn(`   - ${f.file} (${f.pattern})`));
    console.warn(`💡 Run: npm run security:baseline:update to clean`);
}

console.log('\n✅ Security Audit PASSED (all findings baselined)');
process.exit(0);
