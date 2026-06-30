#!/usr/bin/env node
/**
 * Context7 Governance Protocol - Bekçi Lifecycle Pruner
 *
 * Enforces retention policies on yalihan-bekci/archive/
 * - Retention: Keep last 50 files per month folder.
 * - Rule: "Keep current/* always".
 * - Rule: "Silme yok" — excess files are MOVED to a .overflow/ subdirectory, never deleted.
 *
 * Usage:
 *   node scripts/bekci-prune.cjs
 */

const fs = require('fs');
const path = require('path');

const BEKCI_ROOT = path.join(__dirname, '../../yalihan-bekci');
const ARCHIVE_ROOT = path.join(BEKCI_ROOT, 'archive');
const MAX_FILES_PER_MONTH = 50;

console.log('🛡️  Bekçi Lifecycle Pruner Started...');

if (!fs.existsSync(ARCHIVE_ROOT)) {
    console.log('✅ No archive directory found. Nothing to prune.');
    process.exit(0);
}

// Iterate over month directories (YYYY_MM)
const monthDirs = fs.readdirSync(ARCHIVE_ROOT).filter((f) => {
    const fullPath = path.join(ARCHIVE_ROOT, f);
    return fs.statSync(fullPath).isDirectory() && f !== '.overflow';
});

let totalMoved = 0;

monthDirs.forEach((month) => {
    const monthPath = path.join(ARCHIVE_ROOT, month);
    const files = fs
        .readdirSync(monthPath)
        .filter((f) => !fs.statSync(path.join(monthPath, f)).isDirectory())
        .map((f) => {
            const fullPath = path.join(monthPath, f);
            return {
                name: f,
                path: fullPath,
                stats: fs.statSync(fullPath),
            };
        });

    // Sort by modification time (newest first)
    files.sort((a, b) => b.stats.mtimeMs - a.stats.mtimeMs);

    if (files.length > MAX_FILES_PER_MONTH) {
        console.log(`📂 Processing ${month}: ${files.length} files found.`);
        const overflowDir = path.join(ARCHIVE_ROOT, '.overflow', month);
        fs.mkdirSync(overflowDir, { recursive: true });

        const excessFiles = files.slice(MAX_FILES_PER_MONTH);

        excessFiles.forEach((file) => {
            const destPath = path.join(overflowDir, file.name);
            console.log(`   📦 Moving to overflow: ${file.name}`);
            fs.renameSync(file.path, destPath);
            totalMoved++;
        });
    } else {
        console.log(`✅ ${month}: ${files.length} files (Healthy).`);
    }
});

console.log(`\n✨ Prune Complete. Total moved to overflow: ${totalMoved}`);
