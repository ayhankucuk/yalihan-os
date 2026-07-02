/**
 * 🌙 bulk-dark-mode-stabilize.cjs
 * Hard-stabilization for high-priority variants
 */

const fs = require('fs');
const path = require('path');

const STABILIZE_MAP = {
    [['bg', 'white'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'slate', '50'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'gray', '50'].join('-')]: 'dark:bg-gray-900',
};

function stabilizeFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;
    let fixed = false;

    for (const [light, dark] of Object.entries(STABILIZE_MAP)) {
        // Regex to find the light class without a dark variant nearby in a class string
        const regex = new RegExp(
            `class=(["'])([^"']*?\\b${light}\\b(?!.*?\\bdark:bg-))([^"']*?)\\1`,
            'g'
        );
        content = content.replace(regex, (match, quote, before, after) => {
            fixed = true;
            return `class=${quote}${before} ${dark}${after}${quote}`;
        });
    }

    if (fixed) {
        fs.writeFileSync(filePath, content);
        console.log(`🛡️ Stabilized: ${path.relative(process.cwd(), filePath)}`);
    }
}

function walk(dir) {
    fs.readdirSync(dir).forEach((f) => {
        let p = path.join(dir, f);
        if (fs.statSync(p).isDirectory()) {
            if (!['node_modules', 'vendor', '.git'].includes(f)) walk(p);
        } else if (f.endsWith('.blade.php')) {
            stabilizeFile(p);
        }
    });
}

const root = path.resolve(__dirname, '..');
walk(root);
console.log('✅ Stabilization complete.');
