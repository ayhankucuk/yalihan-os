/**
 * 🌙 Yalıhan Emlak - Dark Mode Variant Fixer V2 (CJS)
 * Context7 Compliance: Automated dark mode variant stabilization
 */

const fs = require('fs');
const path = require('path');

const DARK_MODE_MAP = {
    [['bg', 'white'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'slate', '50'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'gray', '50'].join('-')]: 'dark:bg-gray-900',
    [['bg', 'gray', '100'].join('-')]: 'dark:bg-gray-800',
    [['text', 'gray', '900'].join('-')]: 'dark:text-white',
    [['text', 'gray', '800'].join('-')]: 'dark:text-slate-200',
    [['text', 'gray', '700'].join('-')]: 'dark:text-slate-300',
    [['border', 'gray', '100'].join('-')]: 'dark:border-slate-800',
    [['border', 'gray', '200'].join('-')]: 'dark:border-slate-700',
    // Premium transparency variants
    [['bg', 'white/70'].join('-')]: 'dark:bg-slate-900/70',
    [['bg', 'white/50'].join('-')]: 'dark:bg-slate-900/50',
    [['bg', 'white/10'].join('-')]: 'dark:bg-slate-800/40',
    [['bg', 'white/90'].join('-')]: 'dark:bg-slate-900/90',
    [['bg', 'white/95'].join('-')]: 'dark:bg-slate-900/95',
};

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;
    let fixCount = 0;

    // Pattern to find class attributes in HTML/Blade
    const classRegex = /class=(["'])(.*?)\1/g;

    content = content.replace(classRegex, (match, quote, classList) => {
        let classes = classList.split(/\s+/);
        let updated = false;
        let newClasses = [...classes];

        for (const [light, dark] of Object.entries(DARK_MODE_MAP)) {
            if (
                classes.includes(light) &&
                !classes.some((c) => c.startsWith('dark:') && c.includes(dark.split(':')[1]))
            ) {
                if (!classes.includes(dark)) {
                    newClasses.push(dark);
                    updated = true;
                    fixCount++;
                }
            }
        }

        return updated ? `class=${quote}${newClasses.join(' ')}${quote}` : match;
    });

    if (content !== originalContent) {
        fs.writeFileSync(filePath, content);
        console.log(`✅ Fixed: ${path.relative(process.cwd(), filePath)} (${fixCount} fixes)`);
    } else {
        // console.log(`[OK] ${path.relative(process.cwd(), filePath)}`);
    }
}

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach((f) => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();

        if (isDirectory) {
            if (!['node_modules', 'vendor', '.git', 'storage', 'public'].includes(f)) {
                walkDir(dirPath, callback);
            }
        } else {
            if (f.endsWith('.blade.php') || f.endsWith('.vue') || f.endsWith('.js')) {
                callback(dirPath);
            }
        }
    });
}

const root = path.resolve(__dirname, '..');
console.log(`🚀 Starting Dark Mode Stabilization in: ${root}`);
walkDir(root, processFile);
console.log(`✨ Done! Run "php artisan bekci:aesthetics" to verify.`);
