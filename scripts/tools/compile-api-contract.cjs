const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const SSOT_PATH = path.join(__dirname, '../../docs/API_CONTRACT.json');
const MANIFEST_DIR = path.join(__dirname, '../../storage/app/governance/generated');
const MANIFEST_PATH = path.join(MANIFEST_DIR, 'api_contract.manifest.json');
const DOC_PATH = path.join(__dirname, '../../docs/API_CONTRACT.md');

// Ensure manifest directory exists
if (!fs.existsSync(MANIFEST_DIR)) {
    fs.mkdirSync(MANIFEST_DIR, { recursive: true });
}

try {
    console.log('🔍 Reading API Contract SSOT...');
    const ssotRaw = fs.readFileSync(SSOT_PATH, 'utf8');
    const ssot = JSON.parse(ssotRaw);

    // Calculate Hash
    const hash = crypto.createHash('sha256').update(ssotRaw).digest('hex');

    // Create Manifest
    const manifest = {
        generated_at: new Date().toISOString(),
        source: 'docs/API_CONTRACT.json',
        version: ssot.version,
        hash: hash,
        contract: ssot
    };

    console.log('💾 Writing Manifest...');
    fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 4));

    // Generate Markdown Documentation
    console.log('📝 Generating API_CONTRACT.md...');
    let md = `# API CONTRACT — Uniform Response Standard\n\n`;
    md += `> **Version**: ${ssot.version}\n`;
    md += `> **Hash**: ${hash.substring(0, 8)}\n`;
    md += `> **Generated**: ${new Date().toISOString()}\n\n`;

    md += `## Required Keys (Root)\n`;
    ssot.required_keys.forEach(key => {
        md += `- \`${key}\`\n`;
    });

    md += `\n## Meta Schema\n`;
    md += `Required fields in \`meta\` object:\n`;
    ssot.meta_schema.required.forEach(f => md += `- \`${f}\`\n`);

    if (ssot.meta_schema.optional.length > 0) {
        md += `\nOptional fields:\n`;
        ssot.meta_schema.optional.forEach(f => md += `- \`${f}\`\n`);
    }

    md += `\n## Error Shape\n`;
    md += `Structure of the \`error\` object when \`success: false\`:\n`;
    ssot.error_schema.required.forEach(f => md += `- \`${f}\`\n`);

    md += `\n## Deterministic Rules\n`;
    Object.entries(ssot.rules).forEach(([rule, value]) => {
        md += `- **${rule.replace(/_/g, ' ')}**: \`${value}\`\n`;
    });

    md += `\n---\n<!-- AUTO-GENERATED FROM API_CONTRACT.json -->\n<!-- DO NOT EDIT MANUALLY -->\n`;

    fs.writeFileSync(DOC_PATH, md);

    console.log('✅ API Contract Compiled Successfully!');
    console.log(`   Manifest: ${MANIFEST_PATH}`);
    console.log(`   Documentation: ${DOC_PATH}`);

} catch (error) {
    console.error('❌ Compilation Failed:', error.message);
    process.exit(1);
}
