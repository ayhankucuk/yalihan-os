# Yalıhan Bekçi MCP — Smoke Test Specification

**Kanıt düzeyi:** `REPO_VERIFIED` (kod tarama, `yalihan-bekci-mcp.js` handler mantığı)
**Uçtan uca (E2E):** `BLOCKED_PENDING_PRODUCTION_AUTH` — IDE stdio transport testi kullanıcı onayı bekliyor
**Sahibi:** Cline (uygulama) | Kilo (bağımsız doğrulama) | Codex (kapsam & kabul kararı)

---

## Düzeltme Günlüğü

| Tarih | Yapan | Değişiklik |
|--------|-------|-----------|
| 2026-09-05 | Cline | İlk sürüm — `execute_command`, `read_audit_log`, `list_tools` yanlışlıkla dahil edildi. Düzeltildi. |

---

## Yalıhan Bekçi MCP — Kesin Araç Listesi

`yalihan-bekci-mcp.js` satır 179–363. Bu sunucuda **9 tool** tanımlı. Başka hiçbir sunucunun aracı bu listede değildir.

| # | Tool Adı | Handler Satır | Alt Komut / Kaynak |
|---|----------|--------------|-------------------|
| 1 | `validate_file` | 371–428 | 4 guard script (`SCAN_FILE` env) + inline FORBIDDEN_PATTERNS |
| 2 | `get_canonical` | 432–467 | `authority.json` + sabit fallback map |
| 3 | `check_violation` | 471–533 | Regex + FORBIDDEN_PATTERNS + learnedPatterns (LP-xxx) |
| 4 | `get_project_health` | 537–602 | Guard çalıştırır, guard output parse eder |
| 5 | `get_authority` | 605–646 | `authority.json` okur, string matching ile dallanır |
| 6 | `record_learning` | 650–672 | JSON dosyası yazar (`yalihan-bekci/knowledge/`) |
| 7 | `scan_telescope` | 676–719 | `php artisan bekci:audit` |
| 8 | `get_audit_report` | 723–783 | `storage/logs/bekci-audit-report-*.json` okur |
| 9 | `get_learning_history` | 787–861 | `yalihan-bekci/knowledge/` + `yalihan-bekci/learning/` JSON okur |

### MCP Protokol Mekanizmaları (Araç Değil)

- **`list_tools`**: `ListToolsRequestSchema` handler'ı — MCP'nin araç keşif protokolü. Kendisi bir uygulama aracı değil.
- **`call_tool`**: `CallToolRequestSchema` handler'ı — araç yürütme. Araç adı `name` parametresi ile dispatch edilir.

### mcp-health-bridge.js — Ayrı Süreç, MCP Değil

`mcp-health-bridge.js` bir Express HTTP sunucusudur (`localhost:4001/health`). **MCP server değildir.** Döndürdüğü sabit `{ status: "healthy" }` yanıtı, Bekçi MCP'nin araçlarının çalışıp çalışmadığını doğrulamaz. Bu iki bağımsız süreçtir.

---

## 🔴 Kritik Bulgu: `scan_telescope` Hata Aktarımı (Satır 682–719)

### Bulgu

```javascript
// Satır 684–687
const { stdout, stderr } = await execFileAsync('php', artisanArgs, {
  cwd: PROJECT_ROOT,
  timeout: 60000,
}).catch(err => ({ stdout: err.stdout ?? '', stderr: err.stderr ?? err.message }));
```

`.catch()` hatası `stdout`'a map'liyor. Artisan exit code > 0 döndüğünde `execFileAsync` exception fırlatır; `.catch()` bunu yakalayıp normal çıktı gibi işler. Ardından satır 689–701 devam eder:

```javascript
// Satır 696–701
count === 0
  ? '✅ scan_telescope: 0 ihlal tespit edildi'
  : count !== null
    ? `⚠️  scan_telescope: ${count} ihlal tespit edildi`
    : '🔍 scan_telescope: Tamamlandı',  // ← artisan hatası "Tamamlandı" olarak döner
```

Sonuç: artisan başarısız olduğunda bile "🔍 Tamamlandı" dönebilir — kullanıcı hatanın farkında olmaz.

### Düzeltme

```javascript
// ── scan_telescope ───────────────────────────────────────────────────────
if (name === 'scan_telescope') {
  const scope = args.scope ?? 'full';
  const sinceHours = args.since_hours ?? 24;

  log('scan_telescope', `Tetikleniyor: scope=${scope}, since=${sinceHours}h`);

  let stdout = '';
  let stderr = '';
  let exitCode = -1;

  try {
    const result = await execFileAsync('php', ['artisan', 'bekci:audit', `--scope=${scope}`, `--hours=${sinceHours}`], {
      cwd: PROJECT_ROOT,
      timeout: 60000,
    });
    stdout = result.stdout ?? '';
    stderr = result.stderr ?? '';
    exitCode = 0;
  } catch (err) {
    stdout = err.stdout ?? '';
    stderr = err.stderr ?? err.message ?? '';
    exitCode = err.code ?? 1;
  }

  const output = (stdout + stderr).trim();
  const lines = output.split('\n').filter(Boolean);

  // İhlal sayısını yakala
  const violationMatch = output.match(/(\d+)\s+ihlal/i) ?? output.match(/violations?:\s*(\d+)/i);
  const count = violationMatch ? parseInt(violationMatch[1]) : null;

  // Hata durumu — artisan çalışmadı veya exit code > 0
  if (exitCode !== 0) {
    log('scan_telescope', `HATA — exit ${exitCode}: ${stderr || stdout}`);
    return {
      content: [{
        type: 'text',
        text: [
          `❌ bekci:audit başarısız (exit ${exitCode})`,
          '',
          'PHP stderr:',
          ...(stderr ? stderr.split('\n').filter(Boolean).slice(0, 10) : ['(boş)']),
          '',
          'PHP stdout (varsa):',
          ...(stdout ? stdout.split('\n').filter(Boolean).slice(0, 10) : ['(boş)']),
          '',
          'Kontrol et: php mevcut mu? Dizin doğru mu? artisan bekci:audit kayıtlı mı?',
        ].join('\n'),
      }],
      isError: true,
    };
  }

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
    'Sonuçlar: storage/logs/bekci-audit-report-*.json',
    'Öğrenmeler: yalihan-bekci/learning/',
  ].filter(l => l !== undefined).join('\n');

  log('scan_telescope', `Tamamlandı — ${count ?? '?'} ihlal`);
  return { content: [{ type: 'text', text: summary }] };
}
```

**Değişiklik özeti:**
- `.catch()` kaldırıldı — hata artık dışarıda yakalanıyor
- `exitCode` değişkeni tutuluyor
- `exitCode !== 0` → `isError: true` + detaylı hata mesajı
- Artisan başarılıysa (exit 0) mevcut raporlama devam ediyor

---

## 🟡 Bulgu: `validate_file` — Varsayılan 4 Guard, "Tüm Aktif Guard" Değil

Satır 382–389:

```javascript
const guardsToRun = args.guards?.length
  ? args.guards
  : [
      'ci-guard-tenant-isolation.sh',
      'check-hardcoded-endpoints.sh',
      'ci-guard-naming-authority.sh',
      'ci-guard-exception-swallow.sh',
    ];
```

Açıklamada "tüm aktif guard'lar" yazıyor, ancak yalnızca **4 guard** çalışıyor. `scripts/guards/` dizininde başka guard dosyası varsa varsayılan kapsama girmez.

**Doğrululacak:** Guard sayısı × varsayılan liste eşleşmesi.

---

## 🟡 Bulgu: `record_learning` — Kayıt → Otomatik Kural Üretimi Zinciri Kopuk

Satır 501–517'de `check_violation`, `learnedPatterns` kullanıyor. Bu liste **yalnızca** `LEARNED_PATTERNS.json`'dan geliyor (satır 59–69). `record_learning` ise dosyayı `yalihan-bekci/knowledge/` dizinine yazıyor — bu iki dizin farklı. Kayıt, otomatik kurala dönüşmüyor.

**Doğrululacak:** `knowledge/` kayıtlarının `LEARNED_PATTERNS.json`'a veya guard script'lerine yazıldığına dair kod.

---

## 🟡 Bulgu: `get_canonical` — Bağlam Bağımsız Mapping

Satır 79: `status: 'yayin_durumu'` — her `status` için geçerli varsayılıyor. Satır 446–448'de uyarı var, ancak kod bağlam zorunluluğu getirmiyor. Örn. `users` tablosunda `status` → `aktiflik_durumu` olmalı.

---

## Smoke Test Scenarios

### Tool 1 — `validate_file`

```
Girdi:  file_path="app/Services/Finance/BonusCalculator.php", guards=[]
Beklenen: 4 guard çalışır, her biri için pass/fail döner, FORBIDDEN_PATTERNS taraması yapılır

Girdi:  file_path="app/Services/Finance/BonusCalculator.php", guards=["ci-guard-tenant-isolation.sh"]
Beklenen: Yalnızca 1 guard çalışır

Girdi:  file_path="nonexistent.php"
Beklenen: isError=true, "❌ Dosya bulunamadı"
```

### Tool 2 — `get_canonical`

```
Girdi:  field="status"
Beklenen: "yayin_durumu" + ⚠️ uyarı

Girdi:  field="city"
Beklenen: "il"

Girdi:  field="nonexistent_field"
Beklenen: ⚠️ veya ❓ mesajı, isError=false
```

### Tool 3 — `check_violation`

```
Girdi:  code="Model::where('status', 1)->first();"
Beklenen: RULE-N1 ihlali, "yayin_durumu" önerisi

Girdi:  code="$tenant_id = $request->tenant_id ?? 0;"
Beklenen: RULE-T1-A ihlali

Girdi:  code="public function index() { return view('hello'); }"
Beklenen: "✅ İhlal bulunamadı"
```

### Tool 4 — `get_project_health`

```
Girdi:  scope="full"
Beklenen: Tenant, naming, guard çıktıları içeren metin raporu
```

### Tool 5 — `get_authority`

```
Girdi:  query="forbidden_fields"
Beklenen: canonical_fields + forbidden_patterns döner

Girdi:  query="garbage_query"
Beklenen: authority özeti (fallback) — isError=false
```

### Tool 6 — `record_learning`

```
Girdi:  action_type="context7_fix", context="status→yayin_durumu", files_changed=["app/Models/Ilan.php"]
Beklenen: ✅ mesajı, yalihan-bekci/knowledge/learning_*.json oluşur
```

### Tool 7 — `scan_telescope` ⚠️ (düzeltme aşamasında)

```
Girdi:  scope="full", since_hours=24 (artisan mevcut ve başarılı)
Beklenen: ✅ veya ⚠️ + artisan çıktısı, isError=false

Girdi:  (artisan mevcut değil veya hatalı)
Beklenen: isError=true, "❌ bekci:audit başarısız (exit N)", stderr detayı

Girdi:  (artisan başarılı ama ihlal sayısı yakalanamıyor)
Beklenen: "🔍 scan_telescope: Tamamlandı", isError=false
```

### Tool 8 — `get_audit_report`

```
Girdi:  limit=20 (rapor mevcut)
Beklenen: Son rapordaki ihlaller listesi

Girdi:  (rapor yok)
Beklenen: "⚠️  Henüz audit raporu yok. Önce scan_telescope çalıştır." — isError=false
```

### Tool 9 — `get_learning_history`

```
Girdi:  days=7, source="all"
Beklenen: Node + PHP kayıtları

Girdi:  (kayıt yok)
Beklenen: "Henüz öğrenme kaydı yok."
```

---

## Yapılacaklar

| Öncelik | Görev | Sahibi | Durum |
|---------|-------|--------|-------|
| **P1** | `scan_telescope` hata aktarımı düzeltmesi (yukarıda kod) | Cline | `BLOCKED_PENDING_PRODUCTION_AUTH` |
| P1 | Health doğrulaması (gerçek MCP araç smoke test) | Cline | `UNVERIFIED` |
| P2 | `validate_file` varsayılan guard listesi açıklamasını düzelt | Cline | `UNVERIFIED` |
| P2 | `record_learning` → `LEARNED_PATTERNS.json` zinciri doğrulaması | Kilo | `UNVERIFIED` |
| P3 | 9 tool E2E smoke test CI entegrasyonu | Kilo + Codex | `UNVERIFIED` |
| P3 | Release kararı (gerçek test + çalışma zamanı kanıtı) | Codex | `PENDING` |

---

*Oluşturuldu: 2026-09-05 | Güncellendi: 2026-09-05 (araç listesi düzeltildi) | Kaynak: REPO_VERIFIED (yalihan-bekci-mcp.js)*
