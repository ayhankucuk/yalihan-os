#!/usr/bin/env node
/**
 * DAP Map Check (Deterministic)
 *
 * Validates that docs/DAP_MAP.md exists and contains required sections.
 * Reports PASS/FAIL. Integrated into dap:docs pipeline.
 */
const fs = require('fs');
const path = require('path');

const MAP_PATH = path.resolve(process.cwd(), 'docs/DAP_MAP.md');
const REQUIRED_SECTIONS = [
    'Tek Kaynaklar',
    'Yürütme Sırası',
    'Yazma İzinleri',
    'Yasaklar',
    'Arşiv Kuralları',
    'Eksik Script',
];

console.log('🗺️  DAP Map Check Started...');
console.log(`📄 Map path: ${MAP_PATH}`);

// 1. Existence
if (!fs.existsSync(MAP_PATH)) {
    console.error('❌ docs/DAP_MAP.md NOT FOUND — navigation map is missing!');
    process.exit(2); // drift
}

// 2. Content validation
const content = fs.readFileSync(MAP_PATH, 'utf8');
const missing = [];

for (const section of REQUIRED_SECTIONS) {
    if (!content.includes(section)) {
        missing.push(section);
    }
}

if (missing.length > 0) {
    console.error(`❌ DAP_MAP.md is missing ${missing.length} required section(s):`);
    missing.forEach((s) => console.error(`   - ${s}`));
    process.exit(2); // drift
}

// 3. index.md / INDEX.md link check
const indexCandidates = ['docs/index.md', 'docs/INDEX.md'];
const indexPath = indexCandidates
    .map((p) => path.resolve(process.cwd(), p))
    .find((p) => fs.existsSync(p));

if (indexPath) {
    const indexContent = fs.readFileSync(indexPath, 'utf8');
    if (!indexContent.includes('DAP_MAP')) {
        console.warn('⚠️  docs/index.md does not reference DAP_MAP.md — add link!');
    } else {
        console.log('✅ docs/index.md references DAP_MAP.md');
    }
}

console.log(`✅ DAP Map validated (${REQUIRED_SECTIONS.length} sections present)`);
process.exit(0);
