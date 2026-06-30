# 🚀 Antigravity Performance Profiler

**Version:** 1.0.0
**Created:** 2026-05-20
**Author:** WenOX AI (Yalıhan Bekçi Performance Module)

## 📋 Overview

Antigravity Performance Profiler, Yalıhan projesinde performans sorunlarını tespit eden kapsamlı bir analiz aracıdır. N+1 query, slow query ve cache optimization fırsatlarını otomatik olarak bulur.

## 🎯 Features

### 1. N+1 Query Detection
- **foreach loops** içinde eager loading olmadan relationship erişimi
- **Eloquent queries** without `with()` or `load()`
- **Relationship access** inside loops
- AST-based deep analysis (PHP Parser)

### 2. Eager Loading Analysis
- Controller ve Repository eager loading coverage
- Missing eager loading detection
- Coverage percentage calculation

### 3. Cache Optimization Detection
- Static data queries without cache
- Repeated queries in loops
- Cache usage patterns analysis

### 4. Slow Query Pattern Detection
- `SELECT *` queries without `select()` optimization
- `LIKE` queries (may need indexes)
- `->first()` without `orderBy()` (non-deterministic)
- Complex joins (3+ joins)

## 🛠️ Installation

Araçlar zaten kurulu:
```bash
scripts/tools/antigravity-performance-check.sh
scripts/tools/performance-n1-detector.php
```

## 📖 Usage

### Basic Usage

```bash
# Tam performans analizi
./scripts/tools/antigravity-performance-check.sh

# Sadece app/Http/Controllers analizi
./scripts/tools/antigravity-performance-check.sh
```

### PHP N+1 Detector (Standalone)

```bash
# Text output
php scripts/tools/performance-n1-detector.php --path=app/

# JSON output
php scripts/tools/performance-n1-detector.php --path=app/ --format=json
```

### MCP Tool Integration

```javascript
// Roo Code / Cline / Cursor içinden
await mcp.call("bekci.performance", {
  path: "app/",
  format: "json"
});
```

## 📊 Output Format

### Console Output

```
╔════════════════════════════════════════════════════════════╗
║   🚀 Antigravity Performance Profiler v1.0.0             ║
║   N+1 Query • Slow Query • Cache Optimization            ║
╚════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1️⃣  N+1 Query Detection
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔍 Scanning for N+1 query patterns...

  📌 Pattern 1: foreach loops without eager loading
    ✅ No N+1 issues in foreach loops

  📌 Pattern 2: Eloquent queries without eager loading
    ⚠️  Found 15 queries without eager loading

  📌 Pattern 3: Relationship access inside loops
    ❌ Found 8 relationship accesses in loops
```

### JSON Report

```json
{
  "timestamp": "2026-05-20T17:54:00.000Z",
  "version": "1.0.0",
  "summary": {
    "total_issues": 45,
    "n1_issues": 12,
    "cache_issues": 18,
    "slow_query_issues": 15,
    "eager_load_missing": 8
  },
  "eager_loading": {
    "controllers_with_eager": 42,
    "total_controllers": 58,
    "coverage_percentage": 72
  },
  "cache_usage": {
    "cache_remember_calls": 35,
    "cache_get_calls": 12,
    "uncached_static_queries": 8
  },
  "query_patterns": {
    "select_all_queries": 22,
    "like_queries": 5,
    "first_without_order": 3,
    "complex_joins": 2
  },
  "recommendations": [
    "Add eager loading to reduce N+1 queries",
    "Cache static data queries (categories, settings)",
    "Use select() to optimize query payload",
    "Add orderBy() to all first() calls for determinism"
  ]
}
```

## 🔧 Common Issues & Fixes

### Issue 1: N+1 Query in foreach

**❌ Bad:**
```php
$ilanlar = Ilan::where('yayin_durumu', 'Yayında')->get();

foreach ($ilanlar as $ilan) {
    echo $ilan->il->il_adi; // N+1 query!
    echo $ilan->ilce->ilce_adi; // N+1 query!
}
```

**✅ Good:**
```php
$ilanlar = Ilan::with(['il', 'ilce'])
    ->where('yayin_durumu', 'Yayında')
    ->get();

foreach ($ilanlar as $ilan) {
    echo $ilan->il->il_adi; // No extra query
    echo $ilan->ilce->ilce_adi; // No extra query
}
```

### Issue 2: Uncached Static Data

**❌ Bad:**
```php
public function index()
{
    $kategoriler = IlanKategori::all(); // Every request hits DB
    return view('admin.ilanlar.index', compact('kategoriler'));
}
```

**✅ Good:**
```php
public function index()
{
    $kategoriler = Cache::remember('kategoriler', 3600, function () {
        return IlanKategori::all();
    });
    return view('admin.ilanlar.index', compact('kategoriler'));
}
```

### Issue 3: SELECT * Without Optimization

**❌ Bad:**
```php
$users = User::all(); // Fetches all columns
```

**✅ Good:**
```php
$users = User::select(['id', 'name', 'email'])->get(); // Only needed columns
```

### Issue 4: Non-Deterministic first()

**❌ Bad (SAB Violation):**
```php
$ilan = Ilan::where('aktiflik_durumu', 1)->first(); // Random order
```

**✅ Good:**
```php
$ilan = Ilan::where('aktiflik_durumu', 1)
    ->orderBy('id')
    ->first(); // Deterministic
```

## 📈 Performance Metrics

### Severity Levels

| Level | Total Issues | Action |
|-------|-------------|--------|
| **Excellent** | 0 | ✅ No action needed |
| **Good** | 1-9 | ⚠️ Minor optimizations |
| **Moderate** | 10-49 | ⚠️ Improvements recommended |
| **Critical** | 50+ | ❌ Immediate action required |

### Exit Codes

- `0` - No issues or minor issues (< 10)
- `1` - Moderate or critical issues (≥ 10)

## 🔗 Integration

### Pre-commit Hook

```bash
# .git/hooks/pre-commit
#!/bin/bash
./scripts/tools/antigravity-performance-check.sh
if [ $? -ne 0 ]; then
    echo "❌ Performance issues detected. Fix before committing."
    exit 1
fi
```

### CI/CD Pipeline

```yaml
# .github/workflows/performance.yml
- name: Performance Check
  run: ./scripts/tools/antigravity-performance-check.sh
```

### Antigravity Full Gate

```bash
# Tüm kontroller (performance dahil)
./scripts/tools/antigravity-full-gate.sh
```

## 🧠 AI Learning Integration

Performance Profiler, Yalıhan Bekçi knowledge base'e otomatik olarak öğrenir:

```bash
# Manuel öğrenme
php artisan bekci:learn performance_optimization \
  "N+1 query fixed in IlanController by adding eager loading"
```

## 📚 Related Tools

- [`antigravity-preflight.sh`](antigravity-preflight.sh) - 10 Altın Kural
- [`antigravity-schema-check.sh`](antigravity-schema-check.sh) - DB kolon kontrolü
- [`antigravity-full-gate.sh`](antigravity-full-gate.sh) - Tam kalite kapısı
- [`perf-baseline-wizard.sh`](perf-baseline-wizard.sh) - API latency baseline
- [`perf-gate.sh`](perf-gate.sh) - Performance regression gate

## 🎯 Best Practices

1. **Eager Loading First**: Her zaman relationship'leri eager load et
2. **Cache Static Data**: Kategoriler, ayarlar gibi statik verileri cache'le
3. **Select Optimization**: Sadece gerekli kolonları çek
4. **Deterministic Queries**: `->first()` her zaman `->orderBy()` ile kullan
5. **Avoid Queries in Loops**: Loop içinde query çalıştırma
6. **Index LIKE Queries**: LIKE sorguları için index kullan
7. **Pagination**: Büyük veri setleri için pagination kullan

## 🔍 Advanced Usage

### Custom Path Scan

```bash
# Sadece Controllers
./scripts/tools/antigravity-performance-check.sh app/Http/Controllers

# Sadece Repositories
./scripts/tools/antigravity-performance-check.sh app/Repositories
```

### Report Analysis

```bash
# Son raporu görüntüle
cat reports/performance/performance_report_*.json | jq '.'

# Tüm raporları listele
ls -lh reports/performance/
```

## 🐛 Troubleshooting

### Script Permission Error

```bash
chmod +x scripts/tools/antigravity-performance-check.sh
```

### PHP Parser Not Found

```bash
composer require nikic/php-parser
```

### Report Directory Missing

```bash
mkdir -p reports/performance
```

## 📝 Changelog

### v1.0.0 (2026-05-20)
- ✨ Initial release
- 🔍 N+1 query detection
- 📊 Eager loading analysis
- 💾 Cache optimization detection
- 🐌 Slow query pattern detection
- 🔗 MCP tool integration
- 📄 JSON report generation

## 🤝 Contributing

Bu araç, Yalıhan Bekçi ekosisteminin bir parçasıdır. Yeni pattern'lar veya iyileştirmeler için:

1. Pattern'i [`yalihan-bekci/knowledge/`](../../yalihan-bekci/knowledge/) dizinine ekle
2. [`performance-n1-detector.php`](../../scripts/tools/performance-n1-detector.php) güncelle
3. Test et ve dokümante et

## 📞 Support

- **Documentation**: [`docs/SAB.md`](SAB.md)
- **Onboarding**: [`.sab/ONBOARDING_AGENTS.md`](../.sab/ONBOARDING_AGENTS.md)
- **MCP Server**: [`mcp/src/index.ts`](../../mcp/src/index.ts)

---

**Yalıhan AI OS** - Performance Excellence Through Automation 🚀
