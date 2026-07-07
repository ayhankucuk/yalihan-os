#!/usr/bin/env node
/**
 * .precheck Retention & Rotation Policy
 *
 * Keeps .precheck/ directory healthy:
 * - Removes timestamped snapshots older than 7 days
 * - Keeps *.latest.json files always
 * - Generates retention report
 *
 * Usage:
 *   node scripts/precheck-retention.cjs
 */

const fs = require('fs');
const path = require('path');

const PRECHECK_DIR = path.join(__dirname, '../../.precheck');
const RETENTION_DAYS = 7;
const OVERFLOW_DIR = path.join(PRECHECK_DIR, '.overflow');

console.log('🧹 .precheck Retention Policy Started...');

if (!fs.existsSync(PRECHECK_DIR)) {
    console.log('✅ No .precheck directory found.');
    process.exit(0);
}

fs.mkdirSync(OVERFLOW_DIR, { recursive: true });

const now = Date.now();
const retentionMs = RETENTION_DAYS * 24 * 60 * 60 * 1000;
let movedCount = 0;

const files = fs.readdirSync(PRECHECK_DIR).filter((f) => {
    const fullPath = path.join(PRECHECK_DIR, f);
    return fs.statSync(fullPath).isFile();
});

files.forEach((file) => {
    // Always keep *.latest.json files
    if (file.endsWith('.latest.json')) {
        console.log(`  ✅ Keeping: ${file} (latest)`);
        return;
    }

    const filePath = path.join(PRECHECK_DIR, file);
    const stats = fs.statSync(filePath);
    const age = now - stats.mtimeMs;

    if (age > retentionMs) {
        const destPath = path.join(OVERFLOW_DIR, file);
        fs.renameSync(filePath, destPath);
        console.log(`  📦 Moved to overflow (${Math.floor(age / 86400000)}d old): ${file}`);
        movedCount++;
    } else {
        console.log(`  ✅ Keeping: ${file} (${Math.floor(age / 86400000)}d old)`);
    }
});

console.log(`\n✨ Retention Complete. Moved: ${movedCount} files to .overflow/`);
