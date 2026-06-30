/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🛡️ Yalıhan Bekçi — MCP Server
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * "herzaman uyanık" — Always-on governance guardian.
 *
 * IDE'lere (Claude, Cursor, Windsurf) gerçek zamanlı Bekçi yetkinliği sağlar:
 *   - Kod yazılmadan önce ihlal tespiti
 *   - Context7 canonical isim sorgusu
 *   - Proje sağlık durumu
 *   - Öğrenme kaydı (knowledge base)
 *
 * Transport: stdio (Claude Desktop, Cursor ile doğrudan entegrasyon)
 * Protocol: MCP v0.5.0
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';

import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile, writeFile, readdir, mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const execFileAsync = promisify(execFile);
const __dirname = dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = resolve(__dirname, '..');
const GUARDS_DIR = join(PROJECT_ROOT, 'scripts', 'guards');
const AUTHORITY_PATH = join(PROJECT_ROOT, '.sab', 'authority.json');
const LEARNED_PATTERNS_PATH = join(PROJECT_ROOT, 'docs', 'governance', 'LEARNED_PATTERNS.json');
const KNOWLEDGE_DIR = join(PROJECT_ROOT, 'yalihan-bekci', 'knowledge');
const LEARNING_DIR = join(PROJECT_ROOT, 'yalihan-bekci', 'learning'); // PHP AuditMcpServer output
const AUDIT_REPORTS_DIR = join(PROJECT_ROOT, 'storage', 'logs');
const LOG_PATH = join(PROJECT_ROOT, 'logs', 'mcp', 'yalihan-bekci.log');

// ─── Authority yükle (startup + hot-reload) ────────────────────────────────
let authority = {};
let learnedPatterns = []; // PHP bekci:pattern:learn çıktısı (LP-xxx)

async function loadAuthority() {
  try {
    const raw = await readFile(AUTHORITY_PATH, 'utf8');
    authority = JSON.parse(raw);
    log('authority', `Loaded v${authority.version}`);
  } catch (e) {
    log('warn', `authority.json okunamadı: ${e.message}`);
  }
}

async function loadLearnedPatterns() {
  try {
    const raw = await readFile(LEARNED_PATTERNS_PATH, 'utf8');
    const data = JSON.parse(raw);
    // LEARNED_PATTERNS.json formatı: { "patterns": [ { id, name, signature, description }, ... ] }
    learnedPatterns = data.patterns ?? [];
    log('authority', `Loaded ${learnedPatterns.length} learned patterns (LP-xxx)`);
  } catch (e) {
    log('warn', `LEARNED_PATTERNS.json okunamadı: ${e.message}`);
    learnedPatterns = [];
  }
}

// Context7 canonical field map — authority.json'dan + sabit genişletme
function getCanonicalMap() {
  const fromAuthority = authority?.context7_standards?.naming_conventions?.fields ?? {};
  return {
    // authority.json'dan gelenler
    ...fromAuthority,
    // Sabit Context7 standartları (authority eksikse fallback)
    status: 'yayin_durumu',
    active: 'aktiflik_durumu',
    is_active: 'aktiflik_durumu',
    featured: 'one_cikan',
    priority: 'one_cikan',
    order: 'display_order',
    sort_order: 'display_order',
    city: 'il',
    sehir: 'il',
    latitude: 'lat',
    longitude: 'lng',
    enlem: 'lat',
    boylam: 'lng',
    featured_image: 'kapak_resmi',
    location_id: 'il_id',
    musteriler: 'kisiler',
    customers: 'kisiler',
    type: 'tip / tur / kategori',
    category: 'kategori',
    title: 'baslik',
    description: 'aciklama',
    name: 'ad',
    price: 'fiyat',
  };
}

// Forbidden pattern listesi
const FORBIDDEN_PATTERNS = [
  { pattern: /tenant_id\s*\?\?\s*0/, rule: 'RULE-T1-A', message: 'tenant_id ?? 0 silent fallback', fix: "$this->tenantResolver->resolve()->tenantId" },
  { pattern: /tenant_id\s*\?:\s*0/, rule: 'RULE-T1-B', message: 'tenant_id ?: 0 silent fallback', fix: "$this->tenantResolver->resolve()->tenantId" },
  { pattern: /->user\(\)->tenant_id(?!\s*\?)/, rule: 'RULE-T1-C', message: 'Non-null-safe auth()->user()->tenant_id', fix: "auth()->user()?->tenant_id" },
  { pattern: /\\\\DB::|\\\\Cache::|\\\\Log::/, rule: 'RULE-F1', message: 'Backslash facade (\\DB::)', fix: "use Illuminate\\Support\\Facades\\DB; → DB::" },
  { pattern: /['"]\/admin\//, rule: 'RULE-U1', message: "Hardcoded '/admin/' URL", fix: "route('admin.xxx')" },
  { pattern: /['"]\/api\/v\d/, rule: 'RULE-U2', message: "Hardcoded '/api/vN' URL", fix: "route('api.xxx')" },
  { pattern: /\bstatus\b(?!.*context7-ignore)/, rule: 'RULE-N1', message: "Forbidden field 'status'", fix: "yayin_durumu / aktiflik_durumu" },
  { pattern: /\bis_active\b(?!.*context7-ignore)/, rule: 'RULE-N2', message: "Forbidden field 'is_active'", fix: "aktiflik_durumu" },
  { pattern: /Illuminate\\Database\\Eloquent\\Model(?!\s*\/\/\s*allowed)/, rule: 'RULE-M1', message: 'Direct Model extend — use BaseModel', fix: "extends BaseModel" },
  { pattern: /response\(\)->json\(/, rule: 'RULE-R1', message: 'response()->json() yasak', fix: "ResponseService::success() veya ResponseService::error()" },
];

// ─── Log ───────────────────────────────────────────────────────────────────
function log(level, message) {
  const line = `[${new Date().toISOString()}] [${level.toUpperCase()}] ${message}\n`;
  process.stderr.write(line);
  // Async log dosyasına yaz (fire-and-forget)
  writeFile(LOG_PATH, line, { flag: 'a' }).catch(() => {});
}

// ─── Guard script çalıştır ─────────────────────────────────────────────────
async function runGuard(guardName, extraEnv = {}) {
  const guardPath = join(GUARDS_DIR, guardName);
  if (!existsSync(guardPath)) {
    return { success: false, output: `Guard bulunamadı: ${guardName}`, exitCode: -1 };
  }
  try {
    const { stdout, stderr } = await execFileAsync('bash', [guardPath], {
      env: { ...process.env, CI_GUARD_BASE_DIR: PROJECT_ROOT, ...extraEnv },
      timeout: 30000,
    });
    return { success: true, output: stdout + stderr, exitCode: 0 };
  } catch (err) {
    return {
      success: false,
      output: err.stdout + err.stderr,
      exitCode: err.code ?? 1,
    };
  }
}

// ─── Knowledge base'e kaydet ───────────────────────────────────────────────
async function recordKnowledge(actionType, context, details = {}) {
  await mkdir(KNOWLEDGE_DIR, { recursive: true });
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const filename = `learning_${actionType}_${timestamp}.json`;
  const payload = {
    action_type: actionType,
    context,
    timestamp: new Date().toISOString(),
    project_root: PROJECT_ROOT,
    ...details,
  };
  await writeFile(join(KNOWLEDGE_DIR, filename), JSON.stringify(payload, null, 2));
  return filename;
}

// ═══════════════════════════════════════════════════════════════════════════
// MCP Server
// ═══════════════════════════════════════════════════════════════════════════

const server = new Server(
  {
    name: 'yalihan-bekci',
    version: authority?.version ?? '6.1.2',
  },
  {
    capabilities: { tools: {} },
  }
);

// ─── Tool listesi ──────────────────────────────────────────────────────────
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'validate_file',
      description:
        'Bir PHP dosyasını tüm aktif Bekçi guard\'larından geçirir. ' +
        'Tenant isolation, hardcoded URL, naming, exception swallow kontrolü yapar. ' +
        'Commit öncesi veya kod yazarken çağır.',
      inputSchema: {
        type: 'object',
        properties: {
          file_path: {
            type: 'string',
            description: 'Kontrol edilecek dosyanın proje kök\'üne göre yolu (ör: app/Services/Finance/BonusCalculator.php)',
          },
          guards: {
            type: 'array',
            items: { type: 'string' },
            description: 'Çalıştırılacak guard\'lar (boş = tümü). Ör: ["ci-guard-tenant-isolation.sh"]',
          },
        },
        required: ['file_path'],
      },
    },
    {
      name: 'get_canonical',
      description:
        'Context7 standartlarına göre canonical alan adını döndürür. ' +
        '"status" → "yayin_durumu", "active" → "aktiflik_durumu" gibi. ' +
        'Yeni bir alan yazarken doğru ismi bulmak için kullan.',
      inputSchema: {
        type: 'object',
        properties: {
          field: {
            type: 'string',
            description: 'Sorgulanacak alan adı (ör: "status", "featured", "city")',
          },
        },
        required: ['field'],
      },
    },
    {
      name: 'check_violation',
      description:
        'Bir kod parçasında Bekçi kural ihlali var mı kontrol eder. ' +
        'File path gerektirmez — direkt kod snippet\'i gönder. ' +
        'IDE\'de yazarken anlık kontrol için idealdir.',
      inputSchema: {
        type: 'object',
        properties: {
          code: {
            type: 'string',
            description: 'Kontrol edilecek PHP kod parçası',
          },
          context: {
            type: 'string',
            description: 'İsteğe bağlı: dosya adı veya bağlam bilgisi',
          },
        },
        required: ['code'],
      },
    },
    {
      name: 'get_project_health',
      description:
        'Projenin güncel sağlık durumunu döndürür: ' +
        'tenant isolation skoru, naming violation sayısı, uncommitted dosyalar, ' +
        'Finance domain durumu, CI gate\'leri. Dashboard veya karar öncesi kullan.',
      inputSchema: {
        type: 'object',
        properties: {
          scope: {
            type: 'string',
            enum: ['full', 'finance', 'naming', 'tenant'],
            description: 'Hangi alan (boş = full)',
          },
        },
      },
    },
    {
      name: 'get_authority',
      description:
        'authority.json\'dan kural, canonical isim veya governance bilgisi sorgular. ' +
        'Hangi field\'ın yasak olduğunu, hangi guard\'ın hangi rule\'u uyguladığını öğrenmek için kullan.',
      inputSchema: {
        type: 'object',
        properties: {
          query: {
            type: 'string',
            description: 'Sorgu: "forbidden_fields", "ci_pipeline", "context7_standards", "naming", "rules" veya serbest metin',
          },
        },
        required: ['query'],
      },
    },
    {
      name: 'record_learning',
      description:
        'Bir mimari kararı veya düzeltmeyi Bekçi\'nin knowledge base\'ine kaydeder. ' +
        'Bekçi bu kayıtlardan öğrenerek ileride benzer durumları proaktif yakalar. ' +
        'Önemli bir değişiklik yaptığında mutlaka çağır.',
      inputSchema: {
        type: 'object',
        properties: {
          action_type: {
            type: 'string',
            description: 'Aksiyon tipi: "context7_fix", "tenant_fix", "naming_fix", "architecture_decision", "security_fix"',
          },
          context: {
            type: 'string',
            description: 'Ne yaptın ve neden? (kısa ama net)',
          },
          files_changed: {
            type: 'array',
            items: { type: 'string' },
            description: 'Değiştirilen dosyalar',
          },
          rule_violated: {
            type: 'string',
            description: 'İhlal edilen kural (ör: RULE-T1, RULE-N1)',
          },
        },
        required: ['action_type', 'context'],
      },
    },
    {
      name: 'scan_telescope',
      description:
        'PHP AuditMcpServer\'ı tetikler (php artisan bekci:audit). ' +
        'Telescope DB kayıtlarını ve exception\'ları Context7 ihlalleri için tarar. ' +
        'Sonuçlar hem storage/logs/ hem yalihan-bekci/learning/ dizinine yazılır. ' +
        'Runtime ihlalleri yakalamak için — CI değil, canlı sistem denetimi.',
      inputSchema: {
        type: 'object',
        properties: {
          scope: {
            type: 'string',
            enum: ['telescope', 'code', 'full'],
            description: 'Tarama kapsamı (varsayılan: full)',
          },
          since_hours: {
            type: 'number',
            description: 'Son N saatin kayıtlarını tara (varsayılan: 24)',
          },
        },
      },
    },
    {
      name: 'get_audit_report',
      description:
        'En son Bekçi audit raporunu döndürür. ' +
        'PHP AuditMcpServer\'ın ürettiği bekci-audit-report-*.json dosyasını okur. ' +
        'scan_telescope çalıştırıldıktan sonra sonuçları görmek için kullan.',
      inputSchema: {
        type: 'object',
        properties: {
          limit: {
            type: 'number',
            description: 'Döndürülecek maksimum ihlal sayısı (varsayılan: 20)',
          },
        },
      },
    },
    {
      name: 'get_learning_history',
      description:
        'Bekçi\'nin birleşik öğrenme geçmişini döndürür. ' +
        'Hem Node MCP knowledge/ hem PHP AuditMcpServer learning/ dizinlerini okur. ' +
        'İki tarafın öğrendiklerini tek görünümde sunar.',
      inputSchema: {
        type: 'object',
        properties: {
          days: {
            type: 'number',
            description: 'Son N günün kayıtları (varsayılan: 7)',
          },
          source: {
            type: 'string',
            enum: ['all', 'node', 'php'],
            description: 'Kaynak filtresi (varsayılan: all)',
          },
        },
      },
    },
  ],
}));

// ─── Tool işleyiciler ──────────────────────────────────────────────────────
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  // ── validate_file ────────────────────────────────────────────────────────
  if (name === 'validate_file') {
    const filePath = args.file_path;
    const absolutePath = join(PROJECT_ROOT, filePath);

    if (!existsSync(absolutePath)) {
      return {
        content: [{ type: 'text', text: `❌ Dosya bulunamadı: ${filePath}` }],
        isError: true,
      };
    }

    const guardsToRun = args.guards?.length
      ? args.guards
      : [
          'ci-guard-tenant-isolation.sh',
          'check-hardcoded-endpoints.sh',
          'ci-guard-naming-authority.sh',
          'ci-guard-exception-swallow.sh',
        ];

    const results = [];
    let hasViolation = false;

    for (const guard of guardsToRun) {
      const result = await runGuard(guard, { SCAN_FILE: absolutePath });
      if (!result.success) hasViolation = true;
      results.push({
        guard,
        passed: result.success,
        output: result.output.trim().slice(0, 500),
      });
    }

    // Inline pattern check da ekle
    let fileContent = '';
    try { fileContent = await readFile(absolutePath, 'utf8'); } catch {}
    const inlineViolations = [];
    for (const rule of FORBIDDEN_PATTERNS) {
      if (rule.pattern.test(fileContent)) {
        inlineViolations.push({ rule: rule.rule, message: rule.message, fix: rule.fix });
        hasViolation = true;
      }
    }

    log('validate_file', `${filePath} → ${hasViolation ? 'VIOLATION' : 'CLEAN'}`);

    const summary = [
      hasViolation ? `❌ İHLAL BULUNDU — ${filePath}` : `✅ TEMİZ — ${filePath}`,
      '',
      ...results.map(r => `${r.passed ? '✅' : '❌'} ${r.guard}: ${r.passed ? 'Geçti' : 'BAŞARISIZ'}`),
      ...(inlineViolations.length ? [
        '',
        '🔍 Pattern ihlalleri:',
        ...inlineViolations.map(v => `  [${v.rule}] ${v.message}\n  → FIX: ${v.fix}`),
      ] : []),
    ].join('\n');

    return { content: [{ type: 'text', text: summary }] };
  }

  // ── get_canonical ────────────────────────────────────────────────────────
  if (name === 'get_canonical') {
    const canonicalMap = getCanonicalMap();
    const field = args.field.toLowerCase().trim();
    const canonical = canonicalMap[field];

    if (canonical) {
      return {
        content: [{
          type: 'text',
          text: [
            `✅ Context7 Canonical: **${field}** → **${canonical}**`,
            '',
            'authority.json kaynağı: context7_standards.naming_conventions.fields',
            '',
            field === 'status'
              ? '⚠️  NOT: "status" çok yaygın ihlal. yayin_durumu (ilan) veya aktiflik_durumu (kullanıcı/genel) kullan.'
              : '',
          ].join('\n').trim(),
        }],
      };
    }

    // Benzer isim öner
    const similar = Object.entries(canonicalMap)
      .filter(([k]) => k.includes(field) || field.includes(k))
      .map(([k, v]) => `  ${k} → ${v}`)
      .join('\n');

    return {
      content: [{
        type: 'text',
        text: similar
          ? `⚠️  "${field}" canonical map'te yok. Benzer:\n${similar}`
          : `❓ "${field}" için canonical tanım bulunamadı. authority.json'a eklemek ister misin?`,
      }],
    };
  }

  // ── check_violation ──────────────────────────────────────────────────────
  if (name === 'check_violation') {
    const code = args.code;
    const violations = [];

    for (const rule of FORBIDDEN_PATTERNS) {
      const matches = code.match(new RegExp(rule.pattern.source, 'gm'));
      if (matches) {
        violations.push({
          rule: rule.rule,
          message: rule.message,
          fix: rule.fix,
          occurrences: matches.length,
        });
      }
    }

    // Context7 field check
    const canonicalMap = getCanonicalMap();
    for (const [forbidden, canonical] of Object.entries(canonicalMap)) {
      const fieldRegex = new RegExp(`['"\`]${forbidden}['"\`]|->\\s*${forbidden}\\b`, 'g');
      if (fieldRegex.test(code) && !code.includes('context7-ignore')) {
        violations.push({
          rule: 'RULE-N0',
          message: `Forbidden field '${forbidden}'`,
          fix: `Kullan: '${canonical}'`,
          occurrences: 1,
        });
      }
    }

    // LP-xxx: PHP bekci:pattern:learn ile kaydedilen öğrenilmiş pattern'ler
    for (const lp of learnedPatterns) {
      if (!lp.signature) continue;
      try {
        const lpRegex = new RegExp(lp.signature, 'gm');
        const lpMatches = code.match(lpRegex);
        if (lpMatches) {
          violations.push({
            rule: lp.id ?? 'LP-???',
            message: lp.name ?? lp.signature,
            fix: lp.description ?? 'Bekçi öğrenilmiş pattern — detay için bekci:audit çalıştır',
            occurrences: lpMatches.length,
          });
        }
      } catch (_) {
        // Geçersiz regex ise atla (intentional — pattern hatası guard'ı kesmemeli)
      }
    }

    if (violations.length === 0) {
      return { content: [{ type: 'text', text: '✅ İhlal bulunamadı. Kod Bekçi kurallarına uygun.' }] };
    }

    const report = [
      `❌ ${violations.length} ihlal bulundu:`,
      '',
      ...violations.map(v =>
        `[${v.rule}] ${v.message} (${v.occurrences}x)\n  → FIX: ${v.fix}`
      ),
    ].join('\n');

    log('check_violation', `${violations.length} ihlal — context: ${args.context ?? 'inline'}`);
    return { content: [{ type: 'text', text: report }] };
  }

  // ── get_project_health ───────────────────────────────────────────────────
  if (name === 'get_project_health') {
    const scope = args.scope ?? 'full';
    const lines = [
      `🛡️ Yalıhan Bekçi — Proje Sağlık Raporu`,
      `   Zaman: ${new Date().toLocaleString('tr-TR')}`,
      `   Kapsam: ${scope}`,
      '',
    ];

    // Tenant isolation
    if (scope === 'full' || scope === 'tenant') {
      const tenantResult = await runGuard('ci-guard-tenant-isolation.sh');
      lines.push(`Tenant Isolation [RULE-T1]: ${tenantResult.success ? '✅ TEMİZ' : '❌ İHLAL'}`);
      if (!tenantResult.success) {
        lines.push('  ' + tenantResult.output.split('\n').slice(0, 3).join('\n  '));
      }
    }

    // Finance authority
    if (scope === 'full' || scope === 'finance') {
      const financeResult = await runGuard('ci-guard-finance-authority.sh');
      lines.push(`Finance Authority: ${financeResult.success ? '✅ TEMİZ' : '❌ İHLAL'}`);
    }

    // Hardcoded endpoints
    if (scope === 'full') {
      const endpointResult = await runGuard('check-hardcoded-endpoints.sh');
      lines.push(`Hardcoded Endpoints: ${endpointResult.success ? '✅ TEMİZ' : '⚠️  UYARI'}`);
    }

    // Naming violations (hızlı tarama)
    if (scope === 'full' || scope === 'naming') {
      try {
        const { stdout } = await execFileAsync('grep', [
          '-rn', '--include=*.php', '-l',
          'tenant_id ?? 0',
          join(PROJECT_ROOT, 'app'),
        ]).catch(() => ({ stdout: '' }));
        const count = stdout.trim().split('\n').filter(Boolean).length;
        lines.push(`tenant_id ?? 0 kalan: ${count === 0 ? '✅ 0' : `❌ ${count} dosya`}`);
      } catch {}
    }

    // Git durumu
    if (scope === 'full') {
      try {
        const { stdout: gitStatus } = await execFileAsync('git', [
          '-C', PROJECT_ROOT, 'diff', '--name-only',
        ]);
        const uncommitted = gitStatus.trim().split('\n').filter(Boolean).length;
        lines.push(`Uncommitted dosyalar: ${uncommitted === 0 ? '✅ 0' : `⚠️  ${uncommitted}`}`);
      } catch {}
    }

    // Knowledge base
    try {
      const knowledgeFiles = await readdir(KNOWLEDGE_DIR).catch(() => []);
      lines.push(`Knowledge base kayıtları: ${knowledgeFiles.length}`);
    } catch {}

    lines.push('');
    lines.push(`Authority version: ${authority?.version ?? 'bilinmiyor'}`);
    lines.push(`Proje: ${authority?.project?.name ?? 'YalihanAI EmlakPro'}`);

    return { content: [{ type: 'text', text: lines.join('\n') }] };
  }

  // ── get_authority ─────────────────────────────────────────────────────────
  if (name === 'get_authority') {
    const query = args.query.toLowerCase();

    let result;

    if (query.includes('forbidden') || query.includes('field') || query.includes('naming')) {
      result = {
        canonical_fields: getCanonicalMap(),
        forbidden_patterns: FORBIDDEN_PATTERNS.map(p => ({
          rule: p.rule,
          description: p.message,
          fix: p.fix,
        })),
        source: 'authority.json → context7_standards.naming_conventions',
      };
    } else if (query.includes('ci') || query.includes('pipeline') || query.includes('gate')) {
      result = authority?.governance?.ci_pipeline ?? {};
    } else if (query.includes('context7')) {
      result = authority?.context7_standards ?? {};
    } else if (query.includes('mcp') || query.includes('server')) {
      result = authority?.mcp_server_ecosystem ?? {};
    } else if (query.includes('compliance') || query.includes('metric')) {
      result = authority?.compliance_metrics ?? {};
    } else if (query.includes('command') || query.includes('artisan')) {
      result = authority?.laravel_commands ?? {};
    } else {
      // Tüm authority'yi kısaltılmış döndür
      result = {
        version: authority?.version,
        project: authority?.project,
        enforcement_level: authority?.context7_standards?.enforcement_level,
        active_ci: authority?.governance?.ci_pipeline?.active_workflow,
        compliance_targets: authority?.compliance_metrics?.target_scores,
      };
    }

    return {
      content: [{
        type: 'text',
        text: `🛡️ Authority [${args.query}]:\n\n${JSON.stringify(result, null, 2)}`,
      }],
    };
  }

  // ── record_learning ──────────────────────────────────────────────────────
  if (name === 'record_learning') {
    const filename = await recordKnowledge(
      args.action_type,
      args.context,
      {
        files_changed: args.files_changed ?? [],
        rule_violated: args.rule_violated ?? null,
      }
    );

    log('learning', `Recorded: ${filename}`);

    return {
      content: [{
        type: 'text',
        text: [
          `✅ Öğrenme kaydedildi: ${filename}`,
          `   Tip: ${args.action_type}`,
          `   Bağlam: ${args.context}`,
          `   Bekçi bu pattern'i bir sonraki benzer durumda hatırlayacak.`,
        ].join('\n'),
      }],
    };
  }

  // ── scan_telescope ───────────────────────────────────────────────────────
  if (name === 'scan_telescope') {
    const scope = args.scope ?? 'full';
    const sinceHours = args.since_hours ?? 24;

    log('scan_telescope', `Tetikleniyor: scope=${scope}, since=${sinceHours}h`);

    try {
      const artisanArgs = ['artisan', 'bekci:audit', `--scope=${scope}`, `--hours=${sinceHours}`];
      const { stdout, stderr } = await execFileAsync('php', artisanArgs, {
        cwd: PROJECT_ROOT,
        timeout: 60000,
      }).catch(err => ({ stdout: err.stdout ?? '', stderr: err.stderr ?? err.message }));

      const output = (stdout + stderr).trim();
      const lines = output.split('\n').filter(Boolean);

      // İhlal sayısını yakala
      const violationMatch = output.match(/(\d+)\s+ihlal/i) ?? output.match(/violations?:\s*(\d+)/i);
      const count = violationMatch ? parseInt(violationMatch[1]) : null;

      const summary = [
        count === 0
          ? '✅ scan_telescope: 0 ihlal tespit edildi'
          : count !== null
            ? `⚠️  scan_telescope: ${count} ihlal tespit edildi`
            : '🔍 scan_telescope: Tamamlandı',
        '',
        'Artisan çıktısı:',
        ...lines.slice(0, 30),
        lines.length > 30 ? `... ve ${lines.length - 30} satır daha` : '',
        '',
        `Sonuçlar: storage/logs/bekci-audit-report-*.json`,
        `Öğrenmeler: yalihan-bekci/learning/`,
      ].filter(l => l !== undefined).join('\n');

      log('scan_telescope', `Tamamlandı — ${count ?? '?'} ihlal`);
      return { content: [{ type: 'text', text: summary }] };

    } catch (err) {
      return {
        content: [{ type: 'text', text: `❌ bekci:audit çalıştırılamadı: ${err.message}\n\nPHP mevcut mu? Doğru dizinde mi? (${PROJECT_ROOT})` }],
        isError: true,
      };
    }
  }

  // ── get_audit_report ─────────────────────────────────────────────────────
  if (name === 'get_audit_report') {
    const limit = args.limit ?? 20;

    try {
      // En son bekci-audit-report-*.json dosyasını bul
      const files = await readdir(AUDIT_REPORTS_DIR).catch(() => []);
      const reportFiles = files
        .filter(f => f.startsWith('bekci-audit-report-') && f.endsWith('.json'))
        .sort()
        .reverse();

      if (reportFiles.length === 0) {
        return {
          content: [{ type: 'text', text: '⚠️  Henüz audit raporu yok. Önce scan_telescope çalıştır.' }],
        };
      }

      const latestFile = reportFiles[0];
      const raw = await readFile(join(AUDIT_REPORTS_DIR, latestFile), 'utf8');
      const report = JSON.parse(raw);

      const violations = [
        ...(report.violations?.telescope ?? []),
        ...(report.violations?.code ?? []),
      ].slice(0, limit);

      const lines = [
        `📊 Bekçi Audit Raporu — ${latestFile}`,
        `   Zaman: ${report.timestamp}`,
        `   Telescope ihlalleri: ${report.telescope_violations ?? 0}`,
        `   Kod ihlalleri: ${report.code_violations ?? 0}`,
        `   Toplam: ${report.total_violations ?? 0}`,
        '',
      ];

      if (violations.length > 0) {
        lines.push(`Son ${violations.length} ihlal:`);
        violations.forEach((v, i) => {
          lines.push(`  ${i + 1}. [${v.type}] ${v.forbidden ?? v.message ?? 'Bilinmiyor'}`);
          if (v.file) lines.push(`     📁 ${v.file}:${v.line ?? ''}`);
          if (v.sealed_alternative) {
            const alt = Array.isArray(v.sealed_alternative) ? v.sealed_alternative[0] : v.sealed_alternative;
            lines.push(`     → Canonical: ${alt}`);
          }
        });
      } else {
        lines.push('✅ İhlal yok.');
      }

      if (reportFiles.length > 1) {
        lines.push('', `Geçmiş raporlar: ${reportFiles.length} dosya mevcut`);
      }

      return { content: [{ type: 'text', text: lines.join('\n') }] };

    } catch (err) {
      return {
        content: [{ type: 'text', text: `❌ Rapor okunamadı: ${err.message}` }],
        isError: true,
      };
    }
  }

  // ── get_learning_history ─────────────────────────────────────────────────
  if (name === 'get_learning_history') {
    const days = args.days ?? 7;
    const source = args.source ?? 'all';
    const cutoff = Date.now() - days * 24 * 60 * 60 * 1000;

    const nodeEntries = [];
    const phpEntries = [];

    // Node MCP knowledge/
    if (source === 'all' || source === 'node') {
      try {
        const files = await readdir(KNOWLEDGE_DIR).catch(() => []);
        for (const f of files.filter(f => f.endsWith('.json'))) {
          try {
            const raw = await readFile(join(KNOWLEDGE_DIR, f), 'utf8');
            const entry = JSON.parse(raw);
            const ts = new Date(entry.timestamp).getTime();
            if (ts >= cutoff) nodeEntries.push({ source: 'node', file: f, ...entry });
          } catch {}
        }
      } catch {}
    }

    // PHP AuditMcpServer learning/
    if (source === 'all' || source === 'php') {
      try {
        const files = await readdir(LEARNING_DIR).catch(() => []);
        for (const f of files.filter(f => f.endsWith('.json'))) {
          try {
            const raw = await readFile(join(LEARNING_DIR, f), 'utf8');
            const entry = JSON.parse(raw);
            const ts = new Date(entry.date ?? entry.timestamp).getTime();
            if (ts >= cutoff) phpEntries.push({ source: 'php', file: f, ...entry });
          } catch {}
        }
      } catch {}
    }

    const total = nodeEntries.length + phpEntries.length;
    const lines = [
      `🧠 Bekçi Öğrenme Geçmişi — Son ${days} gün`,
      `   Node MCP kayıtları (knowledge/): ${nodeEntries.length}`,
      `   PHP Audit kayıtları (learning/):  ${phpEntries.length}`,
      `   Toplam: ${total}`,
      '',
    ];

    if (total === 0) {
      lines.push('Henüz öğrenme kaydı yok.');
    } else {
      // Node kayıtları
      if (nodeEntries.length > 0) {
        lines.push('── Node MCP (IDE kararları):');
        nodeEntries.slice(-5).forEach(e => {
          lines.push(`  [${e.action_type}] ${e.context?.slice(0, 80) ?? ''}`);;
          lines.push(`  ${e.timestamp?.slice(0, 10) ?? ''} — ${e.file}`);
        });
        lines.push('');
      }

      // PHP kayıtları
      if (phpEntries.length > 0) {
        lines.push('── PHP Audit (Telescope ihlalleri):');
        phpEntries.slice(-5).forEach(e => {
          const count = e.total_violations ?? e.violations?.length ?? '?';
          lines.push(`  ${e.date?.slice(0, 10) ?? ''} — ${count} ihlal — ${e.file}`);
          if (e.patterns_detected?.length > 0) {
            const top = e.patterns_detected.slice(0, 2).map(p => p.forbidden).join(', ');
            lines.push(`    Patterns: ${top}`);
          }
        });
      }
    }

    return { content: [{ type: 'text', text: lines.join('\n') }] };
  }

  return {
    content: [{ type: 'text', text: `❌ Bilinmeyen araç: ${name}` }],
    isError: true,
  };
});

// ─── Başlat ────────────────────────────────────────────────────────────────
async function main() {
  await mkdir(join(PROJECT_ROOT, 'logs', 'mcp'), { recursive: true });
  await loadAuthority();

  // LEARNED_PATTERNS.json yükle (PHP bekci:pattern:learn çıktısı)
  await loadLearnedPatterns();

  // authority.json + learned patterns saatte bir yenile (hot-reload)
  setInterval(loadAuthority, 60 * 60 * 1000);
  setInterval(loadLearnedPatterns, 60 * 60 * 1000);

  const transport = new StdioServerTransport();
  await server.connect(transport);

  log('startup', `🛡️ Yalıhan Bekçi MCP v${authority?.version ?? '6.1.2'} — herzaman uyanık`);
  log('startup', `📁 Proje: ${PROJECT_ROOT}`);
  log('startup', `🔧 Guards: ${GUARDS_DIR}`);
  log('startup', `🧠 Knowledge: ${KNOWLEDGE_DIR}`);
}

main().catch((err) => {
  process.stderr.write(`FATAL: ${err.message}\n`);
  process.exit(1);
});
