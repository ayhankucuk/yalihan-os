const fs = require('fs');
const path = require('path');

const DOCS_ROOT = 'docs';
const ARCHIVE_ROOT = 'docs/archive';
const REPORT_FILE = 'docs/_reports/DOCS_GOVERNANCE_REPORT.md';
const WHITELIST = [
    'index.md',
    'master_ssot.md',
    'readme.md',
    'dap_map.md',
    'governance.md',
    'api_contract.md',
    'dap_decision_table.md',
    'fazlar_gecmis_raporlar.md',
    'yapilacaklar.md',
    'g1_command_registry_guard.md',
    'g1_test_results.md',
    'known-debt.md',
    'ai_learning_loop.md',
    'architecture-lite.md',
];
const SHOULD_APPLY = process.argv.includes('--apply');

// Determine current archive target (e.g. 2026_02)
const date = new Date();
const archiveFolder = `${date.getFullYear()}_${String(date.getMonth() + 1).padStart(2, '0')}`;
const targetArchive = path.join(ARCHIVE_ROOT, archiveFolder);

if (!fs.existsSync(path.dirname(REPORT_FILE))) {
    fs.mkdirSync(path.dirname(REPORT_FILE), { recursive: true });
}

function governDocs() {
    const mode = SHOULD_APPLY ? 'apply' : 'check';
    console.log(`📚 Docs Governance Started (${mode})...`);
    let reportContent = '# Docs Governance Report\n\n';
    let movedCount = 0;
    let candidateCount = 0;

    const files = fs.readdirSync(DOCS_ROOT);

    files.forEach((file) => {
        const filePath = path.join(DOCS_ROOT, file);
        const stats = fs.statSync(filePath);

        if (stats.isFile() && file.endsWith('.md') && !WHITELIST.includes(file.toLowerCase())) {
            candidateCount++;
            if (SHOULD_APPLY) {
                if (!fs.existsSync(targetArchive)) {
                    fs.mkdirSync(targetArchive, { recursive: true });
                }
                const targetPath = path.join(targetArchive, file);
                fs.renameSync(filePath, targetPath);
                console.log(`📦 Archived: ${file} -> ${archiveFolder}/`);
                reportContent += `- Archived: \`${file}\` to \`${archiveFolder}/\`\n`;
                movedCount++;
            } else {
                console.log(`🔎 Candidate: ${file}`);
                reportContent += `- Candidate: \`${file}\` (would archive to \`${archiveFolder}/\`)\n`;
            }
        }
    });

    if (SHOULD_APPLY && movedCount === 0) {
        reportContent += '- ✅ No root artifacts needed archiving.\n';
    }

    if (!SHOULD_APPLY && candidateCount === 0) {
        reportContent += '- ✅ No root archive candidates found.\n';
    }

    if (!SHOULD_APPLY && candidateCount > 0) {
        reportContent += `\n- Summary: ${candidateCount} candidate file(s) found. Re-run with \`--apply\` to archive.\n`;
    }

    if (SHOULD_APPLY) {
        fs.writeFileSync(REPORT_FILE, reportContent);
        console.log(`📄 Report generated: ${REPORT_FILE}`);
    } else {
        console.log('🧾 Check mode completed (no files moved, no report file written).');
    }
}

governDocs();
