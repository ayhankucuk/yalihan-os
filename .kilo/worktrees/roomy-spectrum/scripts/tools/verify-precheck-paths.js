#!/usr/bin/env node

/**
 * Verify Precheck Paths - Minimum Acceptance Test
 *
 * Ensures critical output files exist before running tests
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const REQUIRED_PATHS = ['docs/_reports', '.precheck'];

const EXPECTED_FILES = [
    'docs/_reports/ARSA_WIZARD_FIX_SUMMARY.md',
    '.precheck/wizard-env.latest.json',
];

let exitCode = 0;

console.log('🔍 Verifying Precheck Paths...\n');

// Check directories
REQUIRED_PATHS.forEach((dir) => {
    const exists = fs.existsSync(dir);
    console.log(`${exists ? '✅' : '❌'} Directory: ${dir}`);
    if (!exists) exitCode = 1;
});

console.log('');

// Check files
EXPECTED_FILES.forEach((file) => {
    const exists = fs.existsSync(file);
    const checkResult = exists ? '✅' : '⚠️';
    const size = exists ? `(${fs.statSync(file).size} bytes)` : '(missing)';
    console.log(`${checkResult} File: ${file} ${size}`);

    // Only fail if wizard-env.json is missing in CI
    if (!exists && file.includes('wizard-env') && process.env.CI === 'true') {
        exitCode = 1;
    }
});

console.log('');

if (exitCode === 0) {
    console.log('✅ All critical paths verified');
} else {
    console.log('❌ Path verification failed');
    if (process.env.CI === 'true') {
        console.log('💡 Hint: Run `npm run e2e:precheck` to generate missing files');
    }
}

process.exit(exitCode);
