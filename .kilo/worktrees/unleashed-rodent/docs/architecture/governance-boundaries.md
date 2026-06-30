# Governance Boundaries

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > `.sab/authority.json` > docs

---

## Principles

1. **Human > Live Code > Living Memory (Learned Patterns) > authority.json > docs**
2. **The Learner (Phase 11):** Mimari hatalar mühürlendiği an "Yaşayan Bellek"e işlenir ve otomatize edilir.
3. **Docs are not authority** — spekülatif iddia yasaktır
4. **Governance dashboards healthy olabilir ama upstream idle olabilir** — dashboard traffic kanıtı değildir
5. **Zero-Guessing** (GOVERNANCE.md) — veri yoksa "bilinmiyor" yazılır, tahmin yapılmaz

---

## Non-bypassable Concepts (EnvDriftGuard v3.2)

Bu kontroller **asla** bypass edilemez:

| Check | Ne Kontrol Eder |
|-------|----------------|
| `policy_lock` | Governance config bütünlüğü |
| `env_testing` | Test ortamı varlığı |
| `db_connectivity` | DB bağlantısı |
| `schema_mysql` | MySQL schema bütünlüğü |
| `schema_diff` | Schema drift tespiti |
| `schema_checksum` | Schema checksum doğrulaması |
| `relation_integrity` | Model ilişki bütünlüğü |

---

## Bypassable Concepts (Token-based, 7 gün süreli)

Bu kontroller kontrollü bypass'a izin verir:

| Check | Ne Kontrol Eder | Bypass Koşulu |
|-------|----------------|---------------|
| `orphan_tables` | Yetim tablolar | Token + audit log |
| `enum_drift` | Enum kayması | Token + audit log |
| `migration_parity` | Migration eşitliği | Token + audit log |
| `fillable_alignment` | Model $fillable uyumu | Token + audit log |
| `schema_testing` | Test schema | Token + audit log |

**Bypass audit trail:** `storage/governance/env-drift-bypass.json`

---

## Governance Decision Lifecycle

```
Karar üretilir (AI veya kural bazlı)
  → GovernanceDecision::create (status: pending)
  → review-queue'ya düşer
  → İnceleme:
    ├── approve → GovernanceAuditLog (uygulanır)
    ├── reject → GovernanceAuditLog (reddedilir)
    ├── rollback → GovernanceRollback (geri alınır)
    ├── suppress → GovernanceSuppression (bastırılır)
    └── override → GovernanceAuditLog (override edilir)
```

---

## Autonomy Levels

| Level | Adı | Davranış |
|-------|-----|---------|
| 0 | MANUAL | Tüm kararlar onay bekler |
| 1 | SUPERVISED | Düşük riskli kararlar otomatik |
| 2 | SEMI-AUTONOMOUS | Orta risk otomatik, yüksek risk onay |
| 3 | AUTONOMOUS | Tüm kararlar bütçe dahilinde otomatik |

**Kontrol mekanizmaları:** Safe Mode, Dry Run, Action Budget, Pause/Resume

---

## Governance Ekranları ve Rolleri

| Ekran | Ne Gösterir | Karar Yetkisi |
|-------|------------|--------------|
| AI Kontrol Merkezi | Multi-agent intelligence | Gözlem |
| Karar Kuyruğu | Bekleyen kararlar | Approve/Reject/Rollback |
| Governance Dashboard | Authority + audit + proposal özeti | Gözlem |
| Özellik Sağlık Matrisi | Feature/template bütünlüğü | AI proposal üretme |
| AI Governance | Prompt compliance telemetry | Gözlem |
| Denetim Kayıtları | Tüm karar geçmişi (append-only) | Gözlem + export |
| Otonom Kontrol | Otonom seviye yönetimi | Seviye değiştirme, pause/resume |
| Aksiyon Döngüsü | Decision → Action → Feedback | Gözlem |
| Yalıhan Bekçi | Sistem integrity izleme | Gözlem + run check |
| MCP Bridge | AI Agent Adapter | Tool Call → CLI Bridge |

---

## Core-Adapter Integration Pattern

Tüm dış entegrasyonlar (AI Ajanları, IDE'ler) şu hiyerarşik akışı izlemelidir:

1. **Strict Forwarding:** Adapter katmanı (MCP/VS Code) asla kural mantığı içermez. Sadece Core CLI'ı çağırır.
2. **Unified Envelope:** Tüm makineler arası iletişim `contractVersion: 1.1.0` zarfı üzerinden yapılır.
3. **Double Parity:** CLI terminal çıktısı ile JSON çıktısı aynı `SabScanRunner` üzerinden beslenmelidir.

---

## Unified JSON Envelope Standard

```json
{
  "ok": true|false,
  "tool": "bekci.name",
  "data": {
    "summary": { ... },
    "violations": [ ... ]
  }
}
```

---

## Known Traps

### 1. "0 requests, 100% compliance" ≠ healthy
```
Interpretation: Muhtemelen no-signal state
Action: Telemetry'de traffic olduğunu doğrula
```

### 2. Governance dashboard healthy ama watcher durmuş
```
Interpretation: sab-watch.sh çalışmıyor olabilir
Action: Process control ile watcher durumunu doğrula
```

### 3. Feature Health Matrix green ama wizard boş
```
Interpretation: Scope mismatch — template var ama resolver eşleşemez
Action: FeatureTemplateResolver fallback zincirini kontrol et
```

### 4. AI monitor 0 gösterir ama sistem "healthy"
```
Interpretation: Provider down ama fallback sessiz
Action: Provider endpoint'i doğrudan test et
```

### 5. Otonom budget aşımı cascade failure
```
Interpretation: Level 3'te bütçe kontrolü atlanmış
Action: Action budget + circuit breaker kontrol et
```

---

## Non-bypassable Concepts (Watchdog v2.1)

Bu kontroller **asla** bypass edilemez:

| Check | Ne Kontrol Eder | Mekanizma |
|-------|----------------|-----------|
| `cognitive_audit` | Anlamsal mimari bütünlük | AST (Abstract Syntax Tree) |
| `policy_lock` | Governance config bütünlüğü | Hash Manifest |
| `leak_prevention` | Hardcoded secrets/tunnels | Living Memory (Learned Patterns) |
| `env_testing` | Test ortamı varlığı | Runtime Probe |
| `schema_mysql` | MySQL schema bütünlüğü | Checksum |
| `relation_integrity` | Model ilişki bütünlüğü | Model Reflection |

---

## The Learner: Living Memory (Phase 11)

Sistem artık sadece statik kuralları değil, çözülen hatalardan çıkardığı dersleri de hatırlar.
- **[LEARNED_PATTERNS.json](../governance/LEARNED_PATTERNS.json):** Regresyon imzalarının saklandığı "Yaşayan Bellek".
- **`bekci:pattern:learn`:** Yeni bir anti-pattern imzasını sisteme öğretme komutu.

---

## CI/CD Pipeline (Gold Line v2.1)

```
PR opened / push
    ↓
Gate 1: sab:integrity-scan (baseline-aware)
    ↓
Gate 2: guard:cqrs + guard:routes:v2
    ↓
Gate 3: php artisan bekci:audit --all (COGNITIVE GATE) 🧠
    ↓
Gate 4: quality:gate + blade-scan
    ↓
Gate 5: system:env-drift-guard --strict
    ↓
Gate 6: php artisan test --compact
    ↓
Gate 7: npm run governance:manifest (SEALING) 🔒
```

Herhangi bir gate fail → **deployment blocked**
