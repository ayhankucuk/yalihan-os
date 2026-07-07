const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const MANIFEST_PATH = path.join(__dirname, '../../storage/app/governance/generated/core.manifest.json');
const PROJECT_ROOT = path.join(__dirname, '../..');

const IMMUTABLE_FILES = [
    '.sab/authority.json',
    'docs/DAP_DECISION_TABLE.json',
    'scripts/tools/compile-authority.cjs',
    'scripts/tools/compile-decision-table.cjs',
    'scripts/guards/legacy-guard.cjs',
    'scripts/tools/dap-drift-check.cjs',
    'scripts/guards/quality-gate.sh',
    'docs/API_CONTRACT.json',
    'scripts/tools/compile-api-contract.cjs',
    'scripts/guards/ci-guard-sealed.sh',
    'scripts/guards/sprint-isolation-check.sh',
];

function calculateFileHash(filePath) {
    if (!fs.existsSync(filePath)) {
        console.error(`❌ Missing immutable file: ${filePath}`);
        process.exit(1);
    }
    const fileBuffer = fs.readFileSync(filePath);
    const hashSum = crypto.createHash('sha256');
    hashSum.update(fileBuffer);
    return hashSum.digest('hex');
}

function generateManifest() {
    console.log('🔒 Generating Governance Core Manifest...');
    const manifest = {
        generated_at: new Date().toISOString(),
        files: {},
    };

    IMMUTABLE_FILES.forEach((file) => {
        const fullPath = path.join(PROJECT_ROOT, file);
        const hash = calculateFileHash(fullPath);
        manifest.files[file] = hash;
        console.log(`   - Hashed: ${file}`);
    });

    const governanceDir = path.dirname(MANIFEST_PATH);
    if (!fs.existsSync(governanceDir)) {
        fs.mkdirSync(governanceDir, { recursive: true });
    }

    fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2));
    console.log(`✅ Core Manifest Generated: ${MANIFEST_PATH}`);
}

generateManifest();
