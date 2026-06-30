const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Ensure husky is installed
try {
    console.log('🐶 Installing Husky...');
    execSync('npx husky install', { stdio: 'inherit' });
} catch (e) {
    console.error('❌ Failed to install husky:', e);
    process.exit(1);
}

// Create pre-commit hook
const hookPath = path.join(__dirname, '../../.husky/pre-commit');
const hookContent = `#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

echo "🛡️  Yalıhan Bekçi: Running Governance Checks..."

# Check 1: Governance Core Integrity
if ! npm run governance:verify; then
    echo "❌ Governance Core Integrity Failed!"
    echo "   Run 'npm run governance:manifest' to update the manifest if changes are intentional."
    exit 1
fi

# Check 2: DAP Drift
if ! npm run dap:drift; then
    echo "❌ DAP Drift Detected!"
    echo "   Fix the drift before committing."
    exit 1
fi

echo "✅ Governance Checks Passed."
`;

try {
    fs.writeFileSync(hookPath, hookContent);
    fs.chmodSync(hookPath, '755');
    console.log('✅ Pre-commit hook installed.');
} catch (e) {
    console.error('❌ Failed to create hook:', e);
    process.exit(1);
}
