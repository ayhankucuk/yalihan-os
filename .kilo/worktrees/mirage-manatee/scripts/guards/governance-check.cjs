const fs = require('fs');
const path = require('path');

// 🛡️ YALIHAN PLATFORMU - SAB v3 CI GOVERNANCE CHECKER
// Bu script, Ana Sistem ve Danışma CRM katmanlarında SAB v3 "Kesin Yasaklar"ını statik tarayarak ihlal durumunda pipeline'ı keser.

const COLORS = {
    RESET: '\x1b[0m',
    RED: '\x1b[31m',
    GREEN: '\x1b[32m',
    YELLOW: '\x1b[33m',
    BLUE: '\x1b[34m',
    CYAN: '\x1b[36m'
};

const DIRS_TO_SCAN = ['app', 'database', 'tests', 'routes'];

const EXCLUDED_PATHS = [
    '/app/Models/Deprecated/',
    '/database/migrations/',
    '/app/Providers/GovernanceSafeguardServiceProvider.php',
    '/database/seeders/DatabaseSeeder.php', // Eger kod yorumlarinda migrate:fresh varsa ignore edilir
    '/app/Services/Schema/SchemaHelper.php',
    '/app/Console/Commands/Context7GateCommand.php',
    '/app/Console/Commands/Context7RefactorStatus.php',
    '/app/Console/Commands/Ups/AiFeatureMapCommand.php',
    '/app/Console/Commands/UpsAiRescueFeatures.php'
];

const FORBIDDEN_PATTERNS = [
    {
        regex: /migrate:(fresh|refresh|reset)|db:wipe/g,
        message: "Production düzeyinde destructive (yıkıcı) artisan komut tespiti! SAB v3 DLP kuralına aykırıdır.",
        severity: "CRITICAL"
    },
    {
        regex: /TRUNCATE\s+TABLE|DROP\s+(TABLE|DATABASE)/gi,
        message: "Manuel TRUNCATE/DROP DB işlemleri tespiti! Kod içinde yıkıcı raw SQL barındırılamaz.",
        severity: "CRITICAL"
    },
    {
        regex: /\\?DB::(statement|unprepared|insert|update|delete|table)\(/g,
        message: "Raw DB facade erişimi veya sorgu oluşturucu kullanımı! Eloquent ORM ve cast mekanizmaları bypass edilemez.",
        severity: "WARNING", // Otorite onayıyla izin verilebilir yerler (örn Drifter, Seeder) olduğu için uyarır ancak process kesmez (opsiyonel config)
        fileMatch: /app\/Http\/Controllers/ // Sadece Controller'da kesin yasak!
    },
    {
        regex: /->where\(['"]status['"]/g,
        message: "Ghost field 'status' query tespiti! SAB v3 uyarınca 'aktiflik_durumu' mühürlü alanı kullanılmalıdır.",
        severity: "CRITICAL"
    }
];

let hasCriticalViolations = false;
let totalFilesScanned = 0;
let violationsCount = 0;

function scanDirectory(dir) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            scanDirectory(fullPath);
        } else if (stat.isFile() && fullPath.endsWith('.php')) {
            checkFile(fullPath);
        }
    });
}

function checkFile(filePath) {
    // Excluded path kontrolü
    if (EXCLUDED_PATHS.some(excluded => filePath.includes(excluded))) {
        return;
    }

    totalFilesScanned++;
    const content = fs.readFileSync(filePath, 'utf8');

    FORBIDDEN_PATTERNS.forEach(rule => {
        // Kural belirli bir dizin/dosya paterni istiyorsa, eşleşmiyorsa atla
        if (rule.fileMatch && !rule.fileMatch.test(filePath)) {
            return;
        }

        let match;
        while ((match = rule.regex.exec(content)) !== null) {
            violationsCount++;
            // Satır numarasını bul (match index'ine kadar olan newline sayisi)
            const lineNumber = content.substring(0, match.index).split('\n').length;

            const isCritical = rule.severity === "CRITICAL";
            if (isCritical) hasCriticalViolations = true;

            const badgeColor = isCritical ? COLORS.RED : COLORS.YELLOW;

            console.log(`${COLORS.BLUE}[GOV-CHECK]${COLORS.RESET} ${badgeColor}[${rule.severity}]${COLORS.RESET} ${filePath}:${lineNumber}`);
            console.log(`            ${COLORS.CYAN}Bypass detected:${COLORS.RESET} '${match[0]}'`);
            console.log(`            ${COLORS.RED}Violation:${COLORS.RESET} ${rule.message}\n`);
        }
    });
}

console.log(`${COLORS.CYAN}======================================================${COLORS.RESET}`);
console.log(`${COLORS.CYAN}🛡️  SAB v3 Governance CI / Static Analysis Started  🛡️${COLORS.RESET}`);
console.log(`${COLORS.CYAN}======================================================${COLORS.RESET}\n`);

DIRS_TO_SCAN.forEach(dir => {
    const absoluteDir = path.join(__dirname, '..', dir);
    if (fs.existsSync(absoluteDir)) {
        scanDirectory(absoluteDir);
    } else {
        console.log(`${COLORS.YELLOW}[WARNING] Directory not found: ${dir}${COLORS.RESET}`);
    }
});

console.log(`${COLORS.CYAN}======================================================${COLORS.RESET}`);
console.log(`📊 Scan Complete. Files checked: ${totalFilesScanned}`);

if (violationsCount > 0) {
    console.log(`⚠️  Total Violations Found: ${violationsCount}`);
}

if (hasCriticalViolations) {
    console.error(`${COLORS.RED}❌ CRITICAL VIOLATION DETECTED! SAB v3 Governance failed.${COLORS.RESET}`);
    console.error(`${COLORS.RED}Pipeline has been hard-blocked to protect architectural integrity.${COLORS.RESET}`);
    process.exit(1);
} else {
    console.log(`${COLORS.GREEN}✅ SAB v3 Governance approved. Zero-Trust test passed.${COLORS.RESET}`);
    process.exit(0);
}
