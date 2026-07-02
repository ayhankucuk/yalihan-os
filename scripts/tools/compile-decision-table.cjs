#!/usr/bin/env node

/**
 * DAP Decision Table Compiler
 *
 * Source: docs/DAP_DECISION_TABLE.json
 * Target: docs/DAP_DECISION_TABLE.md
 *
 * Responsibilities:
 * 1. Read JSON SSOT
 * 2. Generate Markdown Table
 * 3. Overwrite target file
 * 4. Append auto-generation footer
 */

const fs = require('fs');
const path = require('path');

const JSON_PATH = path.join(process.cwd(), 'docs', 'DAP_DECISION_TABLE.json');
const MD_PATH = path.join(process.cwd(), 'docs', 'DAP_DECISION_TABLE.md');

function compile() {
    console.log('🔄 Compiling DAP Decision Table...');

    if (!fs.existsSync(JSON_PATH)) {
        console.error('❌ Missing JSON SSOT:', JSON_PATH);
        process.exit(1);
    }

    const data = JSON.parse(fs.readFileSync(JSON_PATH, 'utf8'));

    let md = `# DAP DECISION TABLE — Deterministik Çalıştırma Matrisi

> **Description**: ${data.description}
> **Version**: ${data.version}

## Decision Matrix (Generated from JSON)

| Scope | Match Patterns | Scripts |
| :--- | :--- | :--- |
`;

    data.rules.forEach(rule => {
        const match = rule.match.map(m => `\`${m}\``).join('<br>');
        const scripts = rule.scripts.map(s => `\`${s}\``).join('<br>');
        md += `| **${rule.scope}** | ${match} | ${scripts} |\n`;
    });

    md += `
## Always Run
The following scripts run on every execution:

`;

    data.always.forEach(script => {
        md += `- \`${script}\`\n`;
    });

    md += `
## Karar Algoritması
1. **git diff --name-only** ile değişen dosyaları al.
2. JSON kurallarına göre (**match**) eşleşenleri bul.
3. İlgili **scripts** listesini birleştir (merge unique).
4. **always** listesini ekle.
5. Fail durumunda \`docs/_reports/FAIL_ANALYSIS.md\` oluştur.

---
<!-- AUTO-GENERATED FROM DAP_DECISION_TABLE.json -->
<!-- DO NOT EDIT MANUALLY -->
<!-- TIMESTAMP: ${new Date().toISOString()} -->
`;

    fs.writeFileSync(MD_PATH, md);
    console.log('✅ Compiled to:', MD_PATH);
}

compile();
