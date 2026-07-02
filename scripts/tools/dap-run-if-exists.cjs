#!/usr/bin/env node
/**
 * DAP Run-If-Exists (Deterministic)
 *
 * Runs an npm script ONLY if it exists in package.json.
 * - If script exists → spawn and forward exit code (FAIL = FAIL)
 * - If script does NOT exist → print SKIPPED and exit 0
 *
 * Usage: node scripts/dap-run-if-exists.cjs <script-name>
 *
 * DAP Policy: No `|| true` fallbacks. This is the deterministic replacement.
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const scriptName = process.argv[2];

if (!scriptName) {
    console.error('❌ Usage: node scripts/dap-run-if-exists.cjs <script-name>');
    process.exit(1);
}

// Read package.json
const pkgPath = path.resolve(process.cwd(), 'package.json');
let pkg;
try {
    pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
} catch (e) {
    console.error(`❌ Cannot read package.json: ${e.message}`);
    process.exit(1);
}

// Check if script exists
if (!pkg.scripts || !pkg.scripts[scriptName]) {
    console.log(`⏭️  SKIPPED: "${scriptName}" not found in package.json scripts`);
    process.exit(0);
}

// Script exists → run it and forward exit code
console.log(`▶️  Running: npm run ${scriptName}`);
try {
    execSync(`npm run ${scriptName}`, { stdio: 'inherit', cwd: process.cwd() });
    process.exit(0);
} catch (err) {
    console.error(`❌ "${scriptName}" failed with exit code ${err.status || 1}`);
    process.exit(err.status || 1);
}
