const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const BASELINE_FILE = '.sab/canonical_seeders.json';
const CANONICAL_SEEDERS = [
    'database/seeders/PropertyHubOzelliklerSeeder.php',
    'database/seeders/KategoriYayinTipiPivotSeeder.php',
    'database/seeders/SmartFormsCanonicalSeeder.php',
];

function calculateFileHash(filePath) {
    if (!fs.existsSync(filePath)) {
        return null;
    }
    const fileBuffer = fs.readFileSync(filePath);
    const hashSum = crypto.createHash('sha256');
    hashSum.update(fileBuffer);
    return hashSum.digest('hex');
}

function verifySeeders(writeMode = false) {
    console.log('🛡️  Canonical Seeder Hash Check (SAB v3+ Enforcement)...');

    let baseline = {};
    if (fs.existsSync(BASELINE_FILE)) {
        baseline = JSON.parse(fs.readFileSync(BASELINE_FILE, 'utf8'));
    }

    let driftFound = false;
    const currentSeeders = {};

    CANONICAL_SEEDERS.forEach((file) => {
        const hash = calculateFileHash(file);
        if (!hash) {
            console.error(`❌ MISSING: ${file}`);
            driftFound = true;
            return;
        }
        currentSeeders[file] = hash;

        if (!writeMode) {
            if (!baseline[file]) {
                console.error(`❌ UNTRACKED SEEDER: ${file} is not in baseline.`);
                driftFound = true;
            } else if (baseline[file] !== hash) {
                console.error(`❌ DRIFT DETECTED: ${file} has been modified.`);
                console.error(`   Expected: ${baseline[file]}`);
                console.error(`   Actual:   ${hash}`);
                driftFound = true;
            } else {
                console.log(`✅ ${file} matches baseline.`);
            }
        }
    });

    if (writeMode) {
        fs.writeFileSync(BASELINE_FILE, JSON.stringify(currentSeeders, null, 2));
        console.log(`✅ Baseline established in ${BASELINE_FILE}.`);
        process.exit(0);
    }

    if (driftFound) {
        console.error('\n🚨 CANONICAL SEEDER DRIFT DETECTED!');
        console.error(
            '   SAB v3+ Protocol: If this was intentional, run: `npm run seeder:baseline`'
        );
        process.exit(2);
    }

    console.log('✅ All canonical seeders are intact.');
}

const args = process.argv.slice(2);
const isBaseline = args.includes('--baseline');

verifySeeders(isBaseline);
