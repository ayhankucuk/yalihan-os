const fs = require('fs');
const path = require('path');

const FORBIDDEN_TERMS = [
    'ilan_kategori_yayin_tipleri',
    'ilan_kategori_yayin_tipi_id'
];

const SCAN_DIRS = [
    'app',
    'config',
    'resources',
    'routes'
];

const IGNORE_DIRS = [
    'node_modules',
    'vendor',
    'storage',
    'public',
    'tests',
    'database'
];

// Extensions to scan
const EXTENSIONS = ['.php', '.js', '.ts', '.vue', '.blade.php'];

let violations = 0;

function scanFile(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');

        FORBIDDEN_TERMS.forEach(term => {
            if (content.includes(term)) {
                // Allow explicit ignore comments
                // Implementation: split by newline, check if line contains term, checks if previous line has ignore
                const lines = content.split('\n');
                lines.forEach((line, index) => {
                    if (line.includes(term)) {
                        const prevLine = index > 0 ? lines[index - 1] : '';
                        if (!prevLine.includes('context7-ignore-next-line')) {
                            console.error(`❌ VIOLATION: Found forbidden term '${term}' in ${filePath}:${index + 1}`);
                            console.error(`   Line: ${line.trim()}`);
                            violations++;
                        }
                    }
                });
            }
        });
    } catch (e) {
        console.warn(`⚠️  Could not read ${filePath}: ${e.message}`);
    }
}

function scanDir(dir) {
    if (!fs.existsSync(dir)) return;

    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            if (!IGNORE_DIRS.includes(file)) {
                scanDir(fullPath);
            }
        } else {
            if (EXTENSIONS.some(ext => file.endsWith(ext))) {
                scanFile(fullPath);
            }
        }
    });
}

console.log('🛡️  Legacy Guard: Scanning for forbidden/dead terms...');
console.log(`   Terms: ${FORBIDDEN_TERMS.join(', ')}`);

SCAN_DIRS.forEach(dir => {
    scanDir(path.join(process.cwd(), dir));
});

if (violations > 0) {
    console.error(`\n❌ Legacy Guard Failed: ${violations} violations found.`);
    process.exit(2);
} else {
    console.log('\n✅ Legacy Guard Passed: No violations found.');
    process.exit(0);
}
