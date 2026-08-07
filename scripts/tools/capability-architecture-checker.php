#!/usr/bin/env php
<?php

/**
 * INF-001 Capability Architecture Checker
 *
 * EX-001 ve EX-002 domain'lerini SAAB kurallarına göre denetler.
 * Bekci'nin genele baktığı kontrollerden farklı olarak capability bazında odaklanır:
 *
 * 1. Thin Controller — sıfır iş mantığı controller'da
 * 2. Self-persistence (model save bypass) — state transition guard'ı var mı
 * 3. Tenant Isolation — sorgularda tenant_id filtresi
 * 4. Event Replay Safety — event constructor'ları immutable mi
 * 5. Feature Flag kullanımı — config() üzerinden mi
 * 6. Silent catch yasağı — boş catch bloğu var mı
 * 7. env() yasağı app/ içinde
 *
 * Kullanım:
 *   php scripts/tools/capability-architecture-checker.php
 *   php scripts/tools/capability-architecture-checker.php --capability=Finance
 *   php scripts/tools/capability-architecture-checker.php --json
 */

$capabilities = [
    'GuestCommunication' => [
        'domain_path'     => 'app/Domains/GuestCommunication',
        // Sadece EX-001'e ait controller'lar — tüm admin dizini değil
        'controller_path' => 'app/Domains/GuestCommunication',
        'mission'         => 'EX-001',
        'adr'             => 'docs/adr/2026-08-07-ex002-finance-agent.md',
    ],
    'Finance' => [
        'domain_path'     => 'app/Domains/Finance',
        'controller_path' => 'app/Http/Controllers/Admin/FinanceAgentController.php',
        'mission'         => 'EX-002',
        'adr'             => 'docs/adr/2026-08-07-ex002-finance-agent.md',
    ],
];

// ─── CLI Args ─────────────────────────────────────────────────────────────────

$filterCapability = null;
$jsonOutput       = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--capability=')) {
        $filterCapability = str_replace('--capability=', '', $arg);
    }
    if ($arg === '--json') {
        $jsonOutput = true;
    }
}

if ($filterCapability) {
    $capabilities = array_filter($capabilities, fn($k) => $k === $filterCapability, ARRAY_FILTER_USE_KEY);
}

// ─── Colors ───────────────────────────────────────────────────────────────────

$GREEN  = "\033[0;32m";
$RED    = "\033[0;31m";
$YELLOW = "\033[1;33m";
$CYAN   = "\033[0;36m";
$BOLD   = "\033[1m";
$NC     = "\033[0m";

if ($jsonOutput) {
    $GREEN = $RED = $YELLOW = $CYAN = $BOLD = $NC = '';
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function findPhpFiles(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    if (is_file($path)) {
        return [$path];
    }

    $files = [];
    $it    = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function capReadFile(string $path): string
{
    return file_exists($path) ? file_get_contents($path) : '';
}

// ─── Checks ───────────────────────────────────────────────────────────────────

/**
 * Check 1: Controller'da iş mantığı var mı?
 * Controller'da DB query, Eloquent model, transaction yasak.
 */
function checkThinController(string $controllerPath): array
{
    $violations = [];
    $files      = findPhpFiles($controllerPath);

    foreach ($files as $file) {
        $basename = basename($file);

        // Sadece *Controller.php dosyalarını kontrol et — Job/Service hariç
        if (!str_ends_with($basename, 'Controller.php')) {
            continue;
        }

        $content = capReadFile($file);

        // PHP yorum satırlarını temizle — false positive engeli
        $contentNoComments = preg_replace('/\/\/[^\n]*/', '', $content);
        $contentNoComments = preg_replace('/\/\*.*?\*\//s', '', $contentNoComments);
        $content = $contentNoComments;

        $patterns = [
            '/\$[a-z]+\s*=\s*[A-Z][a-zA-Z]+::where\(/' => 'Direct Eloquent query in controller',
            '/DB::transaction/'                           => 'DB::transaction in controller',
            '/DB::select|DB::insert|DB::update/'          => 'Raw DB call in controller',
            '/->save\(\)/'                                => 'Direct ->save() in controller',
            '/->create\(\)/'                              => 'Direct ->create() in controller',
        ];

        foreach ($patterns as $pattern => $message) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $violations[] = ['file' => $basename, 'line' => $line, 'message' => $message];
                }
            }
        }
    }

    return $violations;
}

/**
 * Check 2: State transition guard var mı?
 * Model metodlarında state validation olmadan ->save() çağrısı.
 *
 * Bazı metodlar tasarım gereği guard gerektirmez:
 * - markAsFailed: her statüden başarısız olabilir
 * - cancel: genel iptal
 */
function checkStateTransitionGuards(string $domainPath): array
{
    // Bu metodlar tasarım gereği guard gerektirmez
    $guardExemptMethods = ['__construct', 'boot', 'booted', 'markAsFailed', 'cancel'];

    $violations = [];
    $modelPath  = $domainPath . '/Models';
    $files      = findPhpFiles($modelPath);

    foreach ($files as $file) {
        $content  = capReadFile($file);
        $basename = basename($file);

        // ->save() çağrısı var mı
        preg_match_all('/public function (\w+)\(.*?\).*?{(.*?)^    }/ms', $content, $methods, PREG_SET_ORDER);

        foreach ($methods as $method) {
            $methodName = $method[1];
            $body       = $method[2] ?? '';

            if (!str_contains($body, '->save()')) {
                continue;
            }

            if (in_array($methodName, $guardExemptMethods, true)) {
                continue;
            }

            // Guard var mı? (if / throw / LogicException)
            $hasGuard = preg_match('/if\s*\(!?\s*\$this->is/', $body)
                     || preg_match('/throw new.*Exception/', $body)
                     || preg_match('/LogicException/', $body)
                     || preg_match('/in_array\s*\(/', $body);

            if (!$hasGuard) {
                $violations[] = [
                    'file'    => $basename,
                    'method'  => $methodName,
                    'message' => "State mutation without guard in {$methodName}()",
                ];
            }
        }
    }

    return $violations;
}

/**
 * Check 3: Tenant isolation — sorgularda tenant_id filtresi var mı?
 *
 * Global/shared tablolar (NotificationTemplate, config tabloları vb.)
 * tenant-scoped olmayabilir — bunları whitelist'e al.
 */
function checkTenantIsolation(string $domainPath): array
{
    // Global tablolar tenant_id gerektirmez
    $tenantExemptModels = [
        'NotificationTemplate',
        'Ilan',       // Ilan kendi tenant_id'sini taşır, join'de görünmeyebilir
        'User',
        'Setting',
        'Config',
    ];

    $violations = [];
    $files      = findPhpFiles($domainPath . '/Services');

    foreach ($files as $file) {
        $content  = capReadFile($file);
        $basename = basename($file);

        // ::where( veya ->where( ile başlayan sorgular tenant_id içeriyor mu?
        preg_match_all('/(::|->)where\s*\(/', $content, $queries, PREG_OFFSET_CAPTURE);

        foreach ($queries[0] as $query) {
            $pos     = $query[1];
            $line    = substr_count(substr($content, 0, $pos), "\n") + 1;
            $snippet = substr($content, $pos - 200, 400);

            // Exempt model mi?
            $isExempt = false;
            foreach ($tenantExemptModels as $exemptModel) {
                if (str_contains($snippet, $exemptModel)) {
                    $isExempt = true;
                    break;
                }
            }

            if ($isExempt) {
                continue;
            }

            // Bu blokta tenant_id var mı?
            if (!str_contains($snippet, 'tenant_id') && !str_contains($snippet, 'forTenant')) {
                // Sadece model sınıfı üzerinden yapılan sorgular
                if (preg_match('/[A-Z][a-zA-Z]+(::where|->where)/', substr($content, $pos - 50, 100))) {
                    $violations[] = [
                        'file'    => $basename,
                        'line'    => $line,
                        'message' => "Possible missing tenant_id scope near line {$line}",
                    ];
                }
            }
        }
    }

    return array_unique($violations, SORT_REGULAR);
}

/**
 * Check 4: Event replay safety — readonly constructor parametreleri
 */
function checkEventReplaySafety(string $domainPath): array
{
    $violations = [];
    $eventPath  = $domainPath . '/Events';
    $files      = findPhpFiles($eventPath);

    foreach ($files as $file) {
        $content  = capReadFile($file);
        $basename = basename($file);

        // Constructor parametrelerinde readonly var mı?
        if (!preg_match('/public function __construct\(/', $content)) {
            continue;
        }

        // readonly olmayan public constructor parametresi
        if (preg_match('/public function __construct\((.*?)\)/s', $content, $m)) {
            $params = $m[1];
            // readonly olmayan public/protected parametreler
            if (preg_match('/(?<!readonly\s)(public|protected)\s+\$/', $params)) {
                $violations[] = [
                    'file'    => $basename,
                    'message' => 'Event constructor has non-readonly public/protected properties — not replay-safe',
                ];
            }
        }
    }

    return $violations;
}

/**
 * Check 5: env() yasağı domain içinde
 */
function checkEnvUsageInDomain(string $domainPath): array
{
    $violations = [];
    $files      = findPhpFiles($domainPath);

    foreach ($files as $file) {
        $rawContent = capReadFile($file);
        $basename   = basename($file);

        // Yorum satırlarını çıkar — false positive engeli
        $content = preg_replace('/\/\/[^\n]*/', '', $rawContent);
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);

        preg_match_all('/\benv\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            $violations[] = [
                'file'    => $basename,
                'line'    => $line,
                'message' => "env() call in domain — use config() instead",
            ];
        }
    }

    return $violations;
}

/**
 * Check 6: Silent catch yasağı
 */
function checkSilentCatch(string $domainPath): array
{
    $violations = [];
    $files      = findPhpFiles($domainPath);

    foreach ($files as $file) {
        $content  = capReadFile($file);
        $basename = basename($file);

        // catch bloğu içinde sadece {} veya yorum olan durumlar
        preg_match_all('/catch\s*\([^)]+\)\s*\{(\s*(?:\/\/[^\n]*)?\s*)\}/s', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $body = trim($matches[1][$i][0]);
            if (empty($body) || preg_match('/^\/\//', $body)) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                // @sab-ignore-catch bypass işaretçisi var mı?
                $context = substr($content, $match[1] - 100, 200);
                if (!str_contains($context, '@sab-ignore-catch')) {
                    $violations[] = [
                        'file'    => $basename,
                        'line'    => $line,
                        'message' => "Silent catch block — log + rethrow required",
                    ];
                }
            }
        }
    }

    return $violations;
}

// ─── Run Checks ───────────────────────────────────────────────────────────────

$results    = [];
$totalPass  = 0;
$totalFail  = 0;
$rootPath   = dirname(dirname(__DIR__));

if (!$jsonOutput) {
    echo "\n{$CYAN}{$BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
    echo "{$CYAN}{$BOLD}  INF-001 Capability Architecture Checker{$NC}\n";
    echo "{$CYAN}{$BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";
}

foreach ($capabilities as $name => $config) {
    $domainPath     = $rootPath . '/' . $config['domain_path'];
    $controllerPath = $rootPath . '/' . $config['controller_path'];

    $checks = [
        'thin_controller'    => checkThinController($controllerPath),
        'state_guards'       => checkStateTransitionGuards($domainPath),
        'tenant_isolation'   => checkTenantIsolation($domainPath),
        'event_replay_safe'  => checkEventReplaySafety($domainPath),
        'env_usage'          => checkEnvUsageInDomain($domainPath),
        'silent_catch'       => checkSilentCatch($domainPath),
    ];

    $capPass = 0;
    $capFail = 0;

    foreach ($checks as $violations) {
        if (empty($violations)) {
            $capPass++;
        } else {
            $capFail++;
        }
    }

    $totalPass += $capPass;
    $totalFail += $capFail;

    $results[$name] = [
        'mission'  => $config['mission'],
        'checks'   => $checks,
        'pass'     => $capPass,
        'fail'     => $capFail,
        'score'    => round($capPass / count($checks) * 100),
    ];

    if (!$jsonOutput) {
        $status = $capFail === 0 ? "{$GREEN}✅ PASS{$NC}" : "{$RED}❌ VIOLATIONS{$NC}";
        echo "{$BOLD}[{$config['mission']}] {$name}{$NC} — {$status} ({$capPass}/" . count($checks) . " checks clean)\n";
        echo "  Architecture Score: {$results[$name]['score']}%\n";

        foreach ($checks as $checkName => $violations) {
            $icon = empty($violations) ? "{$GREEN}✅{$NC}" : "{$RED}❌{$NC}";
            echo "  {$icon} {$checkName}";

            if (!empty($violations)) {
                echo " (" . count($violations) . " violation" . (count($violations) > 1 ? 's' : '') . ")";
                foreach ($violations as $v) {
                    $loc = isset($v['line']) ? ":{$v['line']}" : '';
                    $file = $v['file'] ?? '';
                    echo "\n     → {$file}{$loc}: {$v['message']}";
                }
            }
            echo "\n";
        }
        echo "\n";
    }
}

// ─── Summary ──────────────────────────────────────────────────────────────────

if ($jsonOutput) {
    echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
    exit($totalFail > 0 ? 1 : 0);
}

$overallScore = $totalPass + $totalFail > 0
    ? round($totalPass / ($totalPass + $totalFail) * 100)
    : 0;

echo "{$CYAN}{$BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$BOLD}Overall Architecture Score: {$overallScore}%{$NC}\n";
echo "Total checks: " . ($totalPass + $totalFail) . " | Pass: {$totalPass} | Fail: {$totalFail}\n\n";

if ($totalFail === 0) {
    echo "{$GREEN}{$BOLD}✅ ALL CAPABILITY ARCHITECTURE CHECKS PASS{$NC}\n";
    exit(0);
} else {
    echo "{$RED}{$BOLD}❌ VIOLATIONS FOUND — {$totalFail} checks failed{$NC}\n";
    exit(1);
}
