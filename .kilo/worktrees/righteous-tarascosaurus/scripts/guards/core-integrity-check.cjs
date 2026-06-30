const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const MANIFEST_PATH = path.join(__dirname, '../../storage/app/governance/generated/core.manifest.json');
const PROJECT_ROOT = path.join(__dirname, '../..');

function calculateFileHash(filePath) {
    if (!fs.existsSync(filePath)) {
        return null; // Return null if file is missing
    }
    const fileBuffer = fs.readFileSync(filePath);
    const hashSum = crypto.createHash('sha256');
    hashSum.update(fileBuffer);
    return hashSum.digest('hex');
}

function verifyIntegrity() {
    console.log('🛡️  Governance Core Integrity Check...');

    if (!fs.existsSync(MANIFEST_PATH)) {
        console.error('❌ CRITICAL: Core Manifest NOT FOUND!');
        console.error('   Run `npm run governance:manifest` to establish the baseline.');
        process.exit(2);
    }

    const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'));
    const files = manifest.files;
    let errors = 0;

    console.log(`   Verifying ${Object.keys(files).length} immutable files...`);

    for (const [file, expectedHash] of Object.entries(files)) {
        const fullPath = path.join(PROJECT_ROOT, file);
        const currentHash = calculateFileHash(fullPath);

        if (currentHash === null) {
            console.error(`❌ MISSING: ${file}`);
            errors++;
            continue;
        }

        if (currentHash !== expectedHash) {
            console.error(`❌ DRIFT DETECTED: ${file}`);
            console.error(`   Expected: ${expectedHash}`);
            console.error(`   Actual:   ${currentHash}`);
            errors++;
        }
    }

    if (errors > 0) {
        console.error(`\n🚨 GOVERNANCE CORE INTEGRITY BREACHED (${errors} violations)`);
        console.error(
            '   The system is in an invalid state. Revert changes or update the manifest.'
        );
        process.exit(2);
    }

    console.log('✅ Governance Core Intact.');
}

verifyIntegrity();
