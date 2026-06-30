# IMPLEMENTATION ROADMAP — SAB v6.1
## Yalıhan Emlak AI OS — Chief Enterprise Architect
**Tarih:** 2026-06-29
**Versiyon:** v1.0
**Kapsam:** Phase 1+2+3 tüm bulgular
**Toplam iş:** ~64 saat | 27 bulgu | 4 sprint

---

## ROADMAP ÖZETI

```
2026-06-29  ═══════════════════════════════════════════════
            │  SPRINT 3.1 EXTENDED                        │
            │  P0-G01: after_commit:true         [1h]     │
            │  P0-G02: Sanctum expiration       [30m]     │
            │  P0-G03: V2 IlanController IDOR   [2h]     │
            │  P1-G08: FavoriService tenant     [2h]     │
            │  P1-G05: ListingProjector try/catch[2h]     │
            │  P1-G06: LeadProjector try/catch  [2h]     │
            │  P1-G07: PhotoService file/DB     [3h]     │
            ╠══════════════════════════════════════════════╣
2026-07-05  │  Sprint 3.1 KAPANIŞ                       │
            ╪══════════════════════════════════════════════╡
            │  SPRINT 4 — Reliability + AI Safety          │
            │  P1-G04: Transactional Outbox       [8h]     │
            │  P1-G09: Token revocation           [3h]     │
            │  P2-G03: Chat API auth:sanctum     [1h]     │
            │  P2-G01: Dual AiBudgetGuard        [3h]     │
            │  P2-G05: FinanceProcessor timeout   [4h]     │
            │  P2-G06: CircuitBreaker durability [4h]     │
            │  P2-G07: SyncListingProjectionJob   [2h]     │
            │  P2-G08: CQRS drift detection       [4h]     │
            │  P2-G10: DLQ routing               [2h]     │
            ╠══════════════════════════════════════════════╣
2026-07-20  │  Sprint 4 KAPANIŞ                           │
            ╪══════════════════════════════════════════════╡
            │  SPRINT 5 — Security + Compliance              │
            │  P1-G03: IG/FB token refresh       [2h]     │
            │  P2-G04: AuthController log mask    [30m]    │
            │  P2-G11: TKGM per-tenant auth        [3h]     │
            │  P2-G09: OwnerMesajController tenant[1h]     │
            │  P2-G12: WizardFeatureController    [1h]     │
            │  S5-CR01: CRM V1+V2 konsolidasyon  [TBD]    │
            │  S5-FI01: Finance modül çakışması  [TBD]    │
            ╠══════════════════════════════════════════════╣
2026-08-01  │  Sprint 5 KAPANIŞ                           │
            ╪══════════════════════════════════════════════╡
            │  SPRINT 6 — Mimari Olgunluk                  │
            │  P3-G01: PropertyPricingService    [4h]     │
            │  P3-G02: UPS log masking            [2h]     │
            │  P3-G03: V2 IlanController index    [1h]     │
            │  P3-G04: IlanCrudService assert     [2h]     │
            │  S5-TM01: 11 controller refactor   [TBD]    │
            │  S5-API01: API namespace migration  [TBD]    │
            ╠══════════════════════════════════════════════╣
2026-08-15  │  Sprint 6 KAPANIŞ + Architecture Score: 80+  │
            ╚══════════════════════════════════════════════╝
```

---

## SPRINT 3.1 EXTENDED — Acil Müdahale
**Tarih:** 2026-06-29 — 2026-07-05
**Hedef:** Tüm P0 + P1 bulgularını üretim riski altında kapatmak
**Toplam iş:** ~12.5 saat (mevcut sprint işlerine ek)
**Yeni item:** 9
**Agent:** Kilo (primary), Cline (SEC-G01/G02)

### Sprint 3.1 Extended — Genişletilmiş Görev Listesi

#### Hafta 1, Gün 1 — P0 Bulgu Kapatma (2026-06-29)

| # | ID | Görev | Agent | Süre | Doğrulama |
|---|----|-------|-------|------|-----------|
| 1 | P0-G01 | `config/queue.php` — Tüm 4 bağlantıda `after_commit: true` | Kilo | 1h | `grep -n "after_commit" config/queue.php` → 4/4 `true` |
| 2 | P0-G02 | `config/sanctum.php` — `'expiration' => 10080` | Kilo | 30m | `php -l config/sanctum.php` + `artisan config:cache` |
| 3 | P0-G03 | `V2/IlanController.php:87` — `show()` tenant scope + `IlanPolicy` | Kilo | 2h | Integration test: Tenant A'nın Tenant B ilanına erişimi 403 dönmeli |

#### Hafta 1, Gün 2-3 — P1 Güvenlik (2026-07-01)

| # | ID | Görev | Agent | Süre | Doğrulama |
|---|----|-------|-------|------|-----------|
| 4 | P1-G08 | `FavoriService.php` — `toggleFavori()` tenant_id guard | Kilo | 2h | Unit test: Cross-tenant favori toggle 403 |
| 5 | P1-G05 | `ListingProjector.php` — try/catch + $tries + $backoff | Kilo | 2h | `grep -n "tries\|backoff" app/Listeners/ListingProjector.php` |
| 6 | P1-G06 | `LeadProjector.php` — try/catch + $tries + $backoff | Kilo | 2h | `grep -n "tries\|backoff" app/Listeners/LeadProjector.php` |

#### Hafta 1, Gün 4-5 — P1 Reliability (2026-07-02)

| # | ID | Görev | Agent | Süre | Doğrulama |
|---|----|-------|------|------|-----------|
| 7 | P1-G07 | `PhotoService.php` — `deletePhoto()` + `bulkDelete()` DB/Storage sırası | Kilo | 3h | Unit test: DB rollback → dosyalar hala mevcut |

#### Hafta 2, Gün 6-7 — Sprint 3.1 Kapanış + Mevcut İşler (2026-07-03)

| # | ID | Görev | Agent | Süre | Doğrulama |
|---|----|-------|-------|------|-----------|
| 8 | S3.1-N01 | `app/Services/Wizard/` type→tip cleanup | Kilo | — | `php artisan sab:integrity-scan` → 0 Wizard violations |
| 9 | S3.1-N02 | `app/Traits/Filterable.php` active field fix | Kilo | — | `php artisan sab:integrity-scan` → 0 Trait violations |

**Sprint 3.1 Extended Başarı Kriterleri:**

| Metrik | Başlangıç | Hedef | Durum |
|--------|-----------|-------|-------|
| P0 bulgu | 3 | 0 | 🔄 |
| P1 bulgu | 6 | 1 (Outbox + Token Revocation Sprint 4'e) | 🔄 |
| after_commit | FAIL | PASS | 🔄 |
| Sanctum expiration | FAIL | PASS | 🔄 |
| V2 IDOR | FAIL | PASS | 🔄 |
| Architecture Score | 65 | 72 | 🔄 |

---

## SPRINT 4 — Reliability Foundation + AI Safety
**Tarih:** 2026-07-06 — 2026-07-20
**Hedef:** Transactional Outbox, Circuit Breaker durability, AI cascading failure koruması
**Toplam iş:** ~31 saat
**Yeni item:** 10
**Agent:** Kilo (primary), 2 Worker Agents (parallel waves)
**Bloker:** Sprint 3.1 Extended tamamlanması (P0-G01 önkoşul)

### Sprint 4 — Görev Listesi

#### Wave A — Financial Integrity (2026-07-06 — 2026-07-09)

| # | ID | Görev | Agent | Süre | Bağımlılık | Doğrulama |
|---|----|-------|-------|------|------------|-----------|
| 10 | P1-G04 | Transactional Outbox — `FinancialLedgerService` → `outbox_events` tablo | Kilo | 8h | P0-G01 | `php artisan test --filter=LedgerTest` → PASS |
| 11 | P1-G09 | Token revocation — `revokeOtherDevices()` + `revokeCurrentDevice()` | Kilo | 3h | P0-G02 | API test: logout → eski token 401 |
| 12 | P2-G05 | `FinanceProcessor` → YalihanCortex üzerinden AI + timeout(10) | Kilo | 4h | — | Telegram test: GPT-4o timeout → graceful fallback |
| 13 | P2-G01 | Dual `AiBudgetGuard` konsolidasyon — tek impl. + shared state | Kilo | 3h | — | Integration test: Her iki path aynı budget görüyor |

#### Wave B — Projector Resilience (2026-07-10 — 2026-07-14)

| # | ID | Görev | Agent | Süre | Bağımlılık | Doğrulama |
|---|----|-------|-------|------|------------|-----------|
| 14 | P2-G07 | `CircuitBreaker` → DB-backed atomic counter veya Redis INCR+EXPIRE | Kilo | 4h | — | Chaos test: Redis flush → circuit state korunmalı |
| 15 | P2-G08 | `SyncListingProjectionJob` — $tries=3, $backoff=[5,15,30], failed() | Kilo | 2h | — | `grep -n "tries\|backoff\|failed" app/Jobs/SyncListingProjectionJob.php` |
| 16 | P2-G09 | `IlanProjectionHandler` — firstOrCreate + drift detection log | Kilo | 4h | P0-G01 | Projection test: Lost IlanOlusturuldu → warning log + skip |
| 17 | P2-G10 | `ProcessProjectionJob` failed() → `proj_dlq` tablo routing | Kilo | 2h | — | `php artisan queue:retry all` → DLQ dolu → replay başarılı |

#### Wave C — AI Safety (2026-07-15 — 2026-07-18)

| # | ID | Görev | Agent | Süre | Bağımlılık | Doğrulama |
|---|----|-------|-------|------|------------|-----------|
| 18 | S4-AI05 | `FinanceProcessor` OpenAI dependency kaldır — YalihanCortex'e yönlendir | Kilo | — | P2-G05 | `#16` tamam |
| 19 | S4-AI01 | `AIController` → `AICrmGatewayService` (FIX-06) | Kilo | — | — | `#16` tamam |

#### Wave D — Deploy Preparation (2026-07-19 — 2026-07-20)

| # | ID | Görev | Agent | Süre | Bağımlılık | Doğrulama |
|---|----|-------|-------|------|------------|-----------|
| 20 | S4-H01 | Hetzner CX33 deploy | — | — | SSH çözümü | curl https://yalihan.ai → 200 |
| 21 | S4-DB01 | JSONB tam göçü (T-UPS-V2-FULL) | Kilo | — | Sprint 4 başı | Migration: 0 hata |

**Sprint 4 Başarı Kriterleri:**

| Metrik | Başlangıç | Hedef |
|--------|-----------|-------|
| Transactional Outbox | FAIL | PASS |
| Token revocation | FAIL | PASS |
| Circuit breaker durability | PARTIAL | PASS |
| FinanceProcessor resilience | PARTIAL | PASS |
| Architecture Score | 72 | 78 |

---

## SPRINT 5 — Security + Compliance
**Tarih:** 2026-07-20 — 2026-08-01
**Hedef:** Rate limiting, Auth logging, TKGM per-tenant auth, CRM konsolidasyonu
**Toplam iş:** ~12 saat (+ CRM/Finance TBD)
**Yeni item:** 6 (+ 2 büyük)
**Agent:** TBD

### Sprint 5 — Görev Listesi

| # | ID | Görev | Süre | Bağımlılık | Doğrulama |
|---|----|-------|------|------------|-----------|
| 22 | P1-G03 | Instagram/Facebook token refresh + expiry alerting | 2h | — | Token renewal test: 55 günde refresh tetiklenir |
| 23 | P2-G04 | `AuthController.php` — email hash log | 30m | — | `grep -n "email" app/Modules/Auth/` → 0 raw email |
| 24 | P2-G11 | Chat API `routes/api/v1/ai.php:250` → `auth:sanctum` | 1h | — | Anonymous chat → 401 |
| 25 | P2-G13 | TKGM per-tenant API key auth | 3h | — | Tenant A TKGM key → Tenant B parcel → 403 |
| 26 | P2-G09 | `OwnerMesajController` `alici_id` tenant check | 1h | — | Cross-tenant mesaj → 403 |
| 27 | P2-G14 | `WizardFeatureController` `IlanPolicy` | 1h | — | Unauthorized ilan_id → 403 |
| 28 | S5-CR01 | CRM V1 + V2 model konsolidasyonu | TBD | — | 0 BC breaks |
| 29 | S5-FI01 | Finance modül çakışması çözümü | TBD | — | FinanceTest → PASS |

**Sprint 5 Başarı Kriterleri:**

| Metrik | Başlangıç | Hedef |
|--------|-----------|-------|
| OWASP A01 violations | 4 | 0 |
| OWASP A07 violations | 3 | 0 |
| Auth log PII | FAIL | PASS |
| CRM duplication | 2 modeller | 1 model |
| Architecture Score | 78 | 80 |

---

## SPRINT 6 — Mimari Olgunluk
**Tarih:** 2026-08-01 — 2026-08-15
**Hedef:** Tüm P3 bulguları kapatmak, controller refactor, Architecture Score 80+
**Toplam iş:** ~9 saat (+ refactor TBD)
**Agent:** TBD

### Sprint 6 — Görev Listesi

| # | ID | Görev | Süre | Bağımlılık | Doğrulama |
|---|----|-------|------|------------|-----------|
| 30 | P3-G04 | `PropertyPricingService` — live exchange rate API (TCMB) | 4h | — | Kur değişimi → fiyat güncelleniyor |
| 31 | P3-G01 | UPS service `LogService::audit()` — allowlist | 2h | — | `grep -n "Log::" app/Services/Ups/` → audit() kullanımı |
| 32 | P3-G02 | V2 `IlanController` `index()` tenant scope doğrulama | 1h | — | TenantScope aktif mi? Değilse ekle |
| 33 | P3-G03 | `IlanCrudService` explicit tenant_id assert | 2h | — | `assert($ilan->tenant_id === $expected)` |
| 34 | S5-TM01 | 11 controller → tek template hiyerarşisi | TBD | — | TemplateTest → PASS |
| 35 | S5-API01 | 14 controller API/Admin namespace migration | TBD | — | Route test → 0 404 |

**Sprint 6 Başarı Kriterleri:**

| Metrik | Başlangıç | Hedef |
|--------|-----------|-------|
| Architecture Score | 80 | 85 |
| P3 bulgu | 5 | 0 |
| Controller sayısı | 11+ | ~6 |
| Hardcoded exchange rates | 1 | 0 |

---

## SPRINT 7+ — Chief AI Layer + Agent Orchestrator
**Tarih:** 2026-08-15+
**Hedef:** Agent Orchestrator, tasks.json, PROJECT_STATE.json
**Not:** Agent Manager worktree kurallarına tabi

| # | ID | Görev | Öncelik |
|---|----|-------|----------|
| 36 | S6-C01 | tasks.json görev havuzu | P1 |
| 37 | S6-C02 | PROJECT_STATE.json | P1 |
| 38 | S6-C03 | chief-ai/ tam kurulum | P2 |
| 39 | S6-C04 | Agent Orchestrator pilot | P2 |

---

## GATE KONTROL PROTOKOLÜ

Her sprint sonunda zorunlu gate geçişi:

```bash
# 1. Güvenlik gate
php artisan bekci:security-scan  # 0 CRITICAL, 0 HIGH

# 2. Reliability gate
php artisan test --filter=ProjectionTest  # PASS
php artisan test --filter=LedgerTest     # PASS

# 3. Mimari gate
php artisan sab:integrity-scan      # 0 new violations
php artisan queue:work --once       # DLQ empty

# 4. Architecture score
# Consistency: 90+, Naming: 85+, Testing: 75+, Overall: 80+
```

**Herhangi bir gate FAIL → sprint kapatılamaz.**

---

## SPRINT 3.1 EXTENDED — AGENT ATAMASI

```
┌─────────────────────────────────────────────────────────┐
│  KILO (Ana Ajan)                                        │
│  Sprint 3.1 Extended — Tüm P0 + P1 item'ları          │
│  Sıra: P0-G01 → P0-G02 → P0-G03 → P1-G08 → P1-G05     │
│        → P1-G06 → P1-G07 → S3.1-N01 → S3.1-N02        │
│                                                         │
│  Doğrulama: Her item sonrası gate kontrol              │
│  Commit: Her 2-3 item sonrası atomic commit            │
│  Rollback: Herhangi bir FAIL → git revert HEAD          │
└─────────────────────────────────────────────────────────┘
```

**Agent Manager kullanımı için:**
- Kilo: P0 + P1 item'lar (tek worktree, sequential)
- paralel item'lar için Agent Manager worktree açılabilir

---

## Chief AI Karar Noktaları — Roadmap

> **D-05**: Sprint 3.1 Extended başlasın. 2026-06-29 bugün. 2026-07-05'e kadar P0+P1 kapatılır.
> **D-06**: P1-G04 (Transactional Outbox, 8h) Sprint 4 Wave A'ya taşındı — 3 ayrı独自 mimari karar, yeterli test coverage olmadan yapılmaz.
> **D-07**: Sprint 4'te `FinanceProcessor` → YalihanCortex yönlendirmesi öncelikli — Telegram bildirim hattı aktif üretim riski.
> **D-08**: CRM V1+V2 konsolidasyonu (S5-CR01) Sprint 5'te TBD — daha fazla analiz gerekli.
> **D-09**: Agent Manager worktree'ler Sprint 4'ten itibaren paralel item'lar için kullanılsın.

---

## ÖZET TABLOSU

| Sprint | Tarih | İş Saati | Bulgu Kapatma | Architecture Score |
|--------|--------|----------|--------------|-------------------|
| 3.1 Extended | 2026-06-29 — 2026-07-05 | ~12.5h | 9/27 (P0+P1) | 65 → 72 |
| Sprint 4 | 2026-07-06 — 2026-07-20 | ~31h | 10/27 | 72 → 78 |
| Sprint 5 | 2026-07-20 — 2026-08-01 | ~12h+ | 6/27 + 2 büyük | 78 → 80 |
| Sprint 6 | 2026-08-01 — 2026-08-15 | ~9h+ | 4/27 + refactor | 80 → 85 |
| Sprint 7+ | 2026-08-15+ | TBD | Agent Layer | 85+ |
| **TOPLAM** | | **~64h+** | **27 bulgu** | |

---

*Chief Enterprise Architect — 2026-06-29 — v1.0*
*Roadmap sonraki güncelleme: Sprint 3.1 Extended kapanışında (2026-07-05)*
