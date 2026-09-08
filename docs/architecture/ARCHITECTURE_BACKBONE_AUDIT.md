---
document_id: ARCH-REP-20260907-BACKBONE
document_owner: architecture
decision_owner: saab
status: proposed
canonical: true
evidence_level: REPO_VERIFIED
as_of_commit: 587e7020
last_reviewed: 2026-09-08
review_after: 2026-10-08
supersedes: null
---

# YALIHAN OS — ARCHITECTURE BACKBONE AUDIT

**Tarih:** 2026-09-07 (Revize: 2026-09-08)
**Durum:** DOCUMENTED / VALIDATION_PENDING / IMPLEMENTATION-BLOCKED-PENDING-AUTH
**Branch:** release-candidate/RC2
**HEAD:** 587e702069c356155ed39d1ce4eb64747b84b01f (Base Snapshot: 587e7020)
**Kapsam:** SSOT authority, tenant isolation, domain boundaries, data ownership, CQRS/events, authorization, service layers, AI/Hermes boundaries, async/queue, frontend/API, production/deployment, documentation/Bekçi compliance

---

## 0. Amaç ve Yöntem

Bu audit, Yalıhan OS mimarisinin omurgasını oluşturan 12 alanı sistematik olarak inceleyerek:

1. **Normatif kural** (authority.json, SAB.md, Constitution, ADR) ile **kod mekanizması** (model, scope, middleware, policy) ve **gerçek uygulama** (test, production evidence) arasındaki uçları tespit eder.
2. Her bulguyu kanıt seviyesiyle (DOCUMENTED, REPO_VERIFIED, TEST_VERIFIED, PRODUCTION_VERIFIED) işaretler.
3. 15 mimari kararın her biri için owner onayı bekleyen ACTION_PROPOSED önerileri sunar.
4. Hiçbir değişiklik yapmaz — read-only audit.

### Otorite Hiyerarşisi

| Sıra | Kaynak | Rol |
|------|--------|-----|
| 1 | `.sab/authority.json` (v6.1.1) | Beklenen kural SSOT |
| 2 | `docs/SAB.md` (v24.2.0) | Teknik anayasa |
| 3 | `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` | Mimari anayasa (20 madde) |
| 4 | `docs/ysos/CONSTITUTION.md` | Süreç anayasası |
| 5 | `docs/ERA_V/PHASE2-ROADMAP.md` | Aktif roadmap |
| 6 | İlgili ADR | Mimari karar kayıtları |
| 7 | `.project-brain/DECISION_LOG.md` | Karar kayıtları |
| 8 | `.project-brain/KNOWN_ISSUES.md` | Bilinen sorunlar |

> `.sab/ONBOARDING.md` birincil kaynak değildir — "ENTRYPOINT / NOT SSOT" olarak işaretlidir.

### Üç Katmanlı Doğrulama Modeli

```
Beklenen kural:    authority.json / SAB.md / Constitution / ADR
Kod mekanizması:   Model / Scope / Middleware / Policy / Guard
Gerçek uygulama:   Test / Production DB / Runtime evidence
```

Bu üç katman ayrı ayrı raporlanır. Bir katmanın doğru olması diğerini kanıtlamaz.

---

## 1. SSOT ve Karar Otoritesi (Authority Matrix)

### 1.1 Mevcut Durum

Sistemde birden fazla "anayasa" ve "SSOT" iddiası bulunmaktadır:

| Belge | Satır | Versiyon | İddia | Gerçek Rol |
|-------|-------|----------|-------|------------|
| `.sab/authority.json` | 332 | v6.1.1 | "Centralized authority file" | Beklenen kural SSOT — Context7, CI, governance |
| `docs/SAB.md` | 195 | v24.2.0 | "Bağlayıcı teknik anayasa" | Teknik anayasa — 18 kural, drift protection |
| `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` | 561 | v1.0.0 | "Kanonik — Değiştirilemez Temel Yasa" | Mimari anayasa — 20 madde, 10 domain |
| `docs/ysos/CONSTITUTION.md` | — | — | "Süreç anayasası" | Süreç/operasyon anayasası |
| `.sab/ONBOARDING.md` | 228 | — | "ENTRYPOINT / NOT SSOT" | Yönlendirme belgesi — normatif kural kaynağı değil |
| `docs/architecture/REGISTRY.md` | 91 | — | "Living Canonical Registry" | Domain/table/event/contract catalog |

### 1.2 Çelişkiler

| # | Çelişki | Kaynak 1 | Kaynak 2 | Risk |
|---|---------|----------|----------|------|
| C1 | SAB.md "teknik anayasa" ile Constitution "mimari anayasa" çakışıyor | SAB.md §1: "Bağlayıcı anayasa" | Constitution §1: "Değiştirilemez temel yasa" | Yüksek — hangisi öncelikli belirsiz |
| C2 | authority.json `bootstrap_entrypoint: ".sab/ONBOARDING.md"` ama ONBOARDING "NOT SSOT" | authority.json satır 5 | ONBOARDING.md satır 1 | Düşük — entrypoint ≠ SSOT, tutarlı |
| C3 | REGISTRY.md ADR indexinde 5 ADR, `docs/adr/` dizininde 23 dosya | REGISTRY.md §6 | `docs/adr/` | Orta — registry güncel değil |
| C4 | SAB.md Rule 16 "tenant_id finansal query'lerde zorunlu" ama tenant audit 40+ tabloda tenant_id eksik | SAB.md satır 27 | tenant-isolation-audit-2026-09-06.md §5 | Yüksek — kural var, uygulama eksik |
| C5 | Constitution Madde 13 "her event tenantId taşmalı" ama Hermes event log'ta tenant_id leakage tespit edildi | Constitution satır 348 | SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md §3 | Yüksek — event contract ihlali |

### 1.3 Önerilen Karar (ACTION_PROPOSED)

**Karar #1 — SSOT Hiyerarşisi:**

```
1. authority.json — beklenen kural (Context7, CI, governance, context isolation)
2. SAB.md — teknik anayasa (mutation rules, CQRS, drift protection, financial scoping)
3. Constitution — mimari anayasa (domain boundaries, contracts, AI boundaries, security)
4. ysos/CONSTITUTION.md — süreç anayasası (operasyon, agent yönetimi)
5. ADR'ler — spesifik mimari kararlar
6. REGISTRY.md — canlı catalog (domain, table, event, contract, agent, ADR index)
7. ONBOARDING.md — giriş/yönlendirme (NOT SSOT)
```

**Kapsam Ayrımı:**
- SAB.md = "Nasıl kod yazılır" (mutation, CQRS, testing, drift)
- Constitution = "Sistem ne yapar" (domain boundaries, contracts, security, AI)
- ysos = "Nasıl çalışılır" (süreç, agent yönetimi, oturum)

**Owner:** SAAB / Architecture owner
**Kapanış kriteri:** Üç belge arasında çapraz referans ve kapsam ayrımı yazılmış, REGISTRY.md ADR index güncel

---

## 2. Tenant İzolasyon Sözleşmesi

### 2.1 Normatif Kural

| Kaynak | Madde | Kural | Kanıt |
|--------|-------|------|-------|
| Constitution | Madde 15.2.1 | "Hiçbir kiracı başka kiracının verisini göremez. Her DB sorgusu tenant scope içermeli" | REPO_VERIFIED |
| SAB.md | Rule 16 | "Finansal query'lerde tenant_id zorunlu" | REPO_VERIFIED |
| SAB.md | §Mali Suçlar | "tenant_id filtresi olmayan finansal veri erişimi = Mimari Suç" | REPO_VERIFIED |
| authority.json | context_isolation | ADR-041: AI session context window / token bütçesi (DB tenant isolation ile karıştırılmamalıdır) | REPO_VERIFIED |

### 2.2 Kod Mekanizması

| Katman | Mekanizma | Durum | Kanıt |
|--------|-----------|-------|-------|
| Model Scope | `TenantScope` (BelongsToTenant trait) | Fail-open — tenant_id null ise scope uygulanmaz | REPO_VERIFIED (tenant-isolation-audit §21.1) |
| Model Scope | `CountryScope` | Fail-open — ulke_id null ise scope uygulanmaz | REPO_VERIFIED (tenant-isolation-audit §21.2) |
| Middleware | `SetTenantContext` | Web grubunda eksik — sadece API ve admin gruplarında | REPO_VERIFIED (tenant-isolation-audit §22) |
| Queue/Job | TenantAwareJobInterface | Kısmi adoption — 14 job kullanıyor (DailySnapshotsJob, OwnerReportExportJob vb.), kalan işlerde eksik | REPO_VERIFIED (commit f2ae0181) |
| CLI/Artisan | Tenant context | Set edilmiyor — scope'lar uygulanmıyor | REPO_VERIFIED (tenant-isolation-audit §23.4) |

### 2.3 Gerçek Uygulama Gap'leri

| Gap | Etki | Kanıt |
|-----|------|-------|
| 40+ tabloda tenant_id kolonu yok | Veri sızıntısı riski | REPO_VERIFIED (tenant-isolation-audit §5) |
| 6 CQRS projection tablosunda tenant_id yok | Cross-tenant read model sızıntısı | REPO_VERIFIED (cqrs-projection-research §3) |
| TenantScope fail-open | tenant_id=null ise tüm veriler görünür | REPO_VERIFIED (tenant-isolation-audit §21.1) |
| Admin panel SetTenantContext yok | Admin üzerinden tüm tenant verisi görünür | REPO_VERIFIED (tenant-isolation-audit §22.3) |
| Kalan queue job'larında tenant context eksikliği | Interface uygulamayan işler tenant boundary'yi aşabilir | REPO_VERIFIED |
| `bekci:tenant-audit` komutu model/migration denetler, Markdown değil | Bekçi kodu denetler ama runtime'da scope çalışmasını doğrulamaz | REPO_VERIFIED |

### 2.4 Önerilen Karar (ACTION_PROPOSED)

**Karar #2 — Tenant Context Standard:**

```
1. TenantScope fail-closed olmalı: tenant_id=null ise bo sonuç dönmeli, tüm verileri değil
2. SetTenantContext middleware tüm route gruplarına uygulanmalı (web, api, admin)
3. Kalan queue job'ları TenantAwareJobInterface implemente etmeli, tenant context restore etmeli (14 job mevcut)
4. CLI/Artisan komutları tenant context parametresi almalı
5. CQRS projection tablolarına tenant_id eklenmeli
6. bekci:tenant-audit komutu runtime scope doğrulaması da yapmalı (test ile)
```

**Owner:** Security owner / Architecture owner
**Kapanış kriteri:** TenantScope fail-closed, tüm route gruplarında SetTenantContext, queue job'larında tenant context, projection tablolarında tenant_id

---

## 3. Domain Sahipliği ve Sınırları

### 3.1 Normatif Kural

Constitution Madde 3: 10 Bounded Context:
1. Property, 2. Listing, 3. CRM, 4. Reservation, 5. Media, 6. Finance, 7. Operations, 8. AI/Cortex, 9. Automation, 10. Identity & Auth

REGISTRY.md §1: Her domain için sorumlu namespace ve bağımlılık izni tanımlı.

### 3.2 Kod Mekanizması

| Domain | Namespace | Mevcut mu? | Uygun mu? |
|--------|-----------|------------|-----------|
| Property | `App\Domain\Property` | REPO_VERIFIED | ✅ |
| Listing | `App\Domain\Listing` | REPO_VERIFIED | ✅ |
| CRM | `App\Domain\CRM` | REPO_VERIFIED | ✅ |
| Reservation | `App\Domain\Reservation` | REPO_VERIFIED | ✅ |
| Media | `App\Domain\Media` | REPO_VERIFIED | ✅ |
| Finance | `App\Domain\Finance` | REPO_VERIFIED | ✅ |
| Operations | `App\Domain\Operations` | REPO_VERIFIED | ✅ |
| AI/Cortex | `App\Domain\AI` | REPO_VERIFIED | ✅ |
| Automation | `App\Domain\Automation` | REPO_VERIFIED | ✅ |
| Identity & Auth | `App\Domain\Identity` | REPO_VERIFIED | ✅ |

### 3.3 Çelişkiler

| # | Çelişki | Kanıt |
|---|---------|-------|
| D1 | `app/Domain/` vs `app/Domains/` çift dizin (known-debt #28 — kapatıldı) | docs/known-debt.md satır 201 |
| D2 | REGISTRY.md tablo sahiplik matrixi 7 tablo listeliyor, gerçekte 100+ tablo var | REGISTRY.md §2 |
| D3 | Constitution "her dosya tek domain'e ait" ama `app/Models/` tüm modeller tek dizinde | REPO_VERIFIED |
| D4 | `app/Application/` katmanı Constitution'da tanımlı değil ama kodda mevcut | REPO_VERIFIED |

### 3.4 Önerilen Karar (ACTION_PROPOSED)

**Karar #3 — Domain Ownership Matrix:**

REGISTRY.md §2 tablo sahiplik matrixi tüm 100+ tabloyu kapsayacak şekilde genişletilmeli. Her tablo için:
- Authoritative Owner (yazma sahibi)
- Reader domains (okuyucu domainler)
- tenant_id durumu (var/yok/global)
- CQRS durumu (write/read/projection)

→ Detaylı matrix: `DOMAIN_OWNERSHIP_MATRIX.md` (Ayrı dosya)

**Owner:** Architecture owner
**Kapanış kriteri:** Tüm tablolar REGISTRY.md'de sahiplik ile listelenmiş, tenant_id durumu işaretlenmiş

---

## 4. Veri Sahipliği ve CQRS

### 4.1 Normatif Kural

| Kaynak | Kural |
|--------|-------|
| SAB.md Rule 6 | "Projection tabloları yalnızca Read Model içindir" |
| SAB.md Rule 2 | "Core'a doğrudan write yasaktır. Mutation yalnızca Service katmanından" |
| Constitution Madde 6 | "Controller → Service → CrudService → Repository → DB zinciri" |
| Constitution Madde 8 | "Her veri alanının tek mutlak sahibi vardır" |

### 4.2 CQRS Projection Durumu

| Projection Tablosu | tenant_id | Sahip Domain | Durum |
|--------------------|-----------|--------------|-------|
| `listing_read_models` | YOK | Listing | 🔴 Kritik |
| `property_read_models` | YOK | Property | 🔴 Kritik |
| `crm_read_models` | YOK | CRM | 🔴 Kritik |
| `reservation_read_models` | YOK | Reservation | 🔴 Kritik |
| `media_read_models` | YOK | Media | 🔴 Kritik |
| `finance_read_models` | YOK | Finance | 🔴 Kritik |

Kanıt: `cqrs-projection-research-report-2026-09-06.md` — 6/6 projection tablosunda tenant_id eksik.

### 4.3 Önerilen Karar (ACTION_PROPOSED)

**Karar #4 — CQRS Projection Lifecycle:**

```
1. Her projection tablosu tenant_id içermeli
2. Projection rebuild tenant-aware olmalı (sadece o tenant'ın verisini rebuild)
3. Projection DLQ replay tenant context korumalı
4. Projection health check tenant bazlı raporlamalı
5. Yeni projection tabloları için migration şablonu tenant_id içermeli
```

**Owner:** Architecture owner
**Kapanış kriteri:** 6 projection tablosuna tenant_id eklenmiş, rebuild/replay tenant-aware

---

## 5. Event ve Queue Sözleşmesi

### 5.1 Normatif Kural

| Kaynak | Madde | Kural |
|--------|-------|-------|
| Constitution | Madde 13.2 | "Her event: eventId (UUID), occurredAt, tenantId, aggregateId, payload (DTO) taşmalı" |
| Constitution | Madde 12.2 | "Tüm Hermes event'leri idempotency_key taşmalı" |
| SAB.md | Rule 10 | "Event işleme idempotent olmak zorundadır" |
| SAB.md | Rule 9 | "DLQ zorunludur ve replay doğrulanmış olmalıdır" |

### 5.2 Event Catalog Gap

REGISTRY.md §3 sadece 6 event listeliyor:
`PropertyCreated`, `ListingPublished`, `ListingPriceChanged`, `MediaUploaded`, `MediaVariantsReady`, `ReservationBooked`

Gerçekte sistemde çok daha fazla event olduğu INFERRED — tam envanter çıkarılmamış.

### 5.3 Queue Tenant Context Gap

| Mekanizma | Durum | Kanıt |
|-----------|-------|-------|
| `TenantAwareJobInterface` | 0 adoption | REPO_VERIFIED (SECURITY_EVIDENCE §4) |
| `RestoreTenantContext` | 0 adoption | REPO_VERIFIED |
| Queue job tenant context | Set edilmiyor | REPO_VERIFIED |
| Hermes event log tenant_id | Leakage tespit edildi | REPO_VERIFIED (SECURITY_EVIDENCE §3) |

### 5.4 Önerilen Karar (ACTION_PROPOSED)

**Karar #5 — Event Naming, Versioning, Idempotency:**

```
1. Event naming: geçmiş zaman kipi (ListingPublished, PriceChanged)
2. Event versioning: her event payload version taşmalı (v1, v2)
3. Event idempotency: idempotency_key her event'te zorunlu
4. Event tenant context: tenantId her event'te zorunlu (Constitution Madde 13)
5. Event catalog: REGISTRY.md tüm event'leri listelemeli
```

**Karar #6 — Queue Tenant Context:**

```
1. Tüm queue job'ları TenantAwareJobInterface implemente etmeli
2. Job dispatch sırasında tenant context serialize edilmeli
3. Job handle sırasında tenant context restore edilmeli
4. Queue worker retry'leri tenant context korumalı
```

→ Detaylı sözleşme: `EVENT_AND_QUEUE_CONTRACT.md` (Ayrı doska)

**Owner:** Architecture owner / Backend owner
**Kapanış kriteri:** Event catalog tam, queue job'ları tenant-aware, idempotency_key zorunlu

---

## 6. Yetkilendirme ve Erişim Kontrolü

### 6.1 Normatif Kural

| Kaynak | Madde | Kural |
|--------|-------|-------|
| Constitution | Madde 15.2.2 | "Hiçbir AI ajanı SuperAdmin yetkisine sahip olamaz" |
| Constitution | Madde 15.2.4 | "Tüm HTTP verisi FormRequest'ten geçmeli (authorize + rules)" |
| authority.json | product_policies | ADR-Ilan-Erisim-Politikasi: yayınlanmış=public, taslak=404, cross-tenant=404 |

### 6.2 Gap'ler

| Gap | Kanıt | Risk |
|-----|-------|------|
| Admin panel SetTenantContext yok — admin tüm tenant verisini görür | REPO_VERIFIED (tenant-isolation-audit §22.3) | Yüksek |
| Checkout route SetTenantContext dışında | REPO_VERIFIED (tenant-isolation-audit §22.4) | Orta |
| `find()` kullanımı scope bypass olabilir | REPO_VERIFIED (tenant-isolation-audit §15) | Orta |
| `withoutGlobalScopes()` çağrıları | REPO_VERIFIED (tenant-isolation-audit §27.4) | Değerlendir |

### 6.3 Önerilen Karar (ACTION_PROPOSED)

**Karar #7 — Admin/Super-Admin Access:**

```
1. Admin panel SetTenantContext ile tenant-aware olmalı
2. SuperAdmin tüm tenant'ları görebilir ama audit log taşmalı
3. AI ajanlar SuperAdmin yetkisine sahip olamaz (Constitution Madde 15.2.2)
4. Admin erişimleri audit log ile izlenmeli
```

**Owner:** Security owner
**Kapanış kriteri:** Admin panel tenant-aware, AI ajan SuperAdmin yasağı enforce

---

## 7. Servis Katmanı ve İstisna Yönetimi

### 7.1 Normatif Kural

| Kaynak | Kural |
|--------|-------|
| SAB.md Rule 2 | "Core'a doğrudan write yasaktır. Mutation yalnızca Service katmanından" |
| Constitution Madde 6.2 | "Çağrı yönü: Controller → Service → CrudService → Repository → DB" |
| SAB.md Rule 4 | "Silent catch yasaktır (Fail-Fast zorunlu)" |
| Constitution Madde 16.2 | "Sessiz yakalama yasaktır; her catch loglamalı, rethrow etmeli veya belgelenmeli" |

### 7.2 Durum

| Kural | Durum | Kanıt |
|-------|-------|-------|
| Thin Controller | SAB Guard denetli, 645 pre-existing violation (LP-014) | REPO_VERIFIED (authority.json ci_guards) |
| Service Layer | Mevcut, SAB Guard denetli | REPO_VERIFIED |
| Silent catch | AST denetli (Bekçi v2.1) | REPO_VERIFIED |
| CQRS boundary | Guard:cqrs komutu denetli | REPO_VERIFIED |

### 7.3 Önerilen Karar

**Karar #8 — API Versioning:**

```
1. Tüm REST uçları versiyonlanmalı (/api/v1/, /api/v2/) — Constitution Madde 14.2
2. Backward compatibility korunmalı — breaking change yasak
3. API şemaları OpenAPI v3 standardında belgelenmeli
4. Idempotency-Key başlığı mutasyon endpoint'lerinde desteklenmeli
```

**Owner:** Backend owner
**Kapanış kriteri:** API versioning tutarlı, OpenAPI spec güncel

---

## 8. AI / Hermes Sınırı

### 8.1 Normatif Kural

| Kaynak | Madde | Kural |
|--------|-------|-------|
| Constitution | Madde 12.3 | "Hermes'in içine iş mantığı yazılamaz — pure orchestrator" |
| Constitution | Madde 11.2 | "Tüm AI çağrıları CortexProviderInterface üzerinden" |
| Constitution | Madde 15.2.2 | "AI ajanlar SuperAdmin olamaz" |
| SAB.md | Rule 17 | "AI Circuit Breaker: Her AI operasyonu AiBudgetGuard kontrolüne tabi" |

### 8.2 Gap'ler

| Gap | Kanıt | Risk |
|-----|-------|------|
| Hermes event log tenant_id leakage | REPO_VERIFIED (SECURITY_EVIDENCE §3) | Yüksek |
| Hermes workforce runtime wiring | docs/known-debt.md #39 — çözüldü | Kapalı |
| AI provider abstraction | Constitution Madde 11 — mevcut | ✅ |

### 8.3 Önerilen Karar (ACTION_PROPOSED)

**Karar #9 — AI-Hermes-Domain Separation:**

```
1. Hermes = pure orchestrator (job dispatch, retry, timeout, rate limit, idempotency)
2. Hermes içine iş mantığı yazılamaz (Constitution Madde 12.3)
3. AI çağrıları CortexProviderInterface üzerinden (Constitution Madde 11.2)
4. AI ajanlar Least Privilege ile sınırlandırılmalı (Constitution Madde 15.2.2)
5. Hermes event log tenant_id taşmalı (Constitution Madde 13)
```

→ Detaylı sınır: `AI_HERMES_BOUNDARY.md` (Ayrı dosya)

**Owner:** AI owner / Architecture owner
**Kapanış kriteri:** Hermes pure orchestrator, AI provider abstraction, tenant_id in event log

---

## 9. Async / Queue Mimarisi

### 9.1 Normatif Kural

| Kaynak | Kural |
|--------|-------|
| SAB.md Rule 9 | "DLQ zorunludur ve replay doğrulanmış olmalıdır" |
| SAB.md Rule 10 | "Event işleme idempotent olmak zorundadır" |
| Constitution Madde 12.2 | "Tüm Hermes event'leri idempotency_key taşmalı" |

### 9.2 Durum

| Mekanizma | Durum | Kanıt |
|-----------|-------|-------|
| DLQ | Aktif, SAB Guard denetli | REPO_VERIFIED |
| Idempotency | Event bazlı zorunlu (SAB Rule 10) | DOCUMENTED |
| Worker restart | Idempotent (SAB §6) | DOCUMENTED |
| Queue tenant context | Eksik — 0 adoption | REPO_VERIFIED |

### 9.3 Önerilen Karar

**Karar #10 — Migration, Backfill, Rollback:**

```
1. Migration'lar tenant-aware olmalı (tenant_id ekleme migration'ları)
2. Backfill stratejisi: tenant_id=null kayıtlar için default tenant atama
3. Rollback planı: her migration için down() methodu tenant-aware
4. Production migration preflight: tenant_id ekleme öncesi orphan record kontrolü
```

**Owner:** Backend owner / DBA
**Kapanış kriteri:** Migration'lar tenant-aware, backfill stratejisi tanımlı, rollback planı mevcut

---

## 10. Frontend / API Katmanı

### 10.1 Normatif Kural

| Kaynak | Kural |
|--------|-------|
| authority.json | `css_framework: "tailwind_only"`, `forbidden_frameworks: ["neo-design-system", "bootstrap"]` |
| Constitution Madde 14 | "Tüm REST uçları versiyonlanmalı", "Standart Envelope formatı" |
| Constitution Madde 16.2 | "Blade'de Font Awesome yasaktır, `<x-icon>` kullanılmalı" |

### 10.2 Durum

| Kural | Durum | Kanıt |
|-------|-------|-------|
| Tailwind only | authority.json enforce | REPO_VERIFIED |
| API versioning | v1 ve v2 mevcut | REPO_VERIFIED |
| API envelope | SAB Crystal format | REPO_VERIFIED (README.md §20.7) |
| Frontend view standardı | Constitution Madde 16.2 | DOCUMENTED |

### 10.3 Önerilen Karar

Mevcut durum büyük ölçüde uyumlu. İzleme önerisi:
- API versioning tutarlılığını periyodik denetle
- Frontend view dizin standardını koru

**Owner:** Frontend owner
**Kapanış kriteri:** API versioning tutarlı, frontend standardı korumalı

---

## 11. Production / Deployment

### 11.1 Normatif Kural

| Kaynak | Kural |
|--------|-------|
| SAB.md §Production Seal | "CQRS sağlıklı, Projection HEALTHY, DLQ doğrulandı, Governance 0 ihlal, SAB drift yok" |
| authority.json | CI pipeline: core-ci.yml, 6 gates |
| Constitution Madde 17 | "Testi geçmeyen kod merge edilemez, deploy edilemez" |

### 11.2 CI Pipeline Gates

| Gate | Komut | Durum |
|------|-------|-------|
| 1 | `sab:integrity-scan` | Aktif |
| 2 | `guard:cqrs` | Aktif |
| 3 | `guard:routes:v2` | Aktif |
| 4 | `quality:gate` | Aktif |
| 5 | `test --compact` | Aktif |
| 6 | `sab:preflight --profile=release` | Aktif |

### 11.3 Önerilen Karar

**Karar #11 — Production Evidence:**

```
1. Production deployment öncesi tenant isolation test çalıştırılmalı
2. Production DB'de tenant_id eksik tablo kontrolü periyodik yapılmalı
3. SAB drift detection production'da aktif olmalı
4. Projection health check production'da tenant-aware raporlamalı
```

**Owner:** DevOps / Security owner
**Kapanış kriteri:** Production tenant isolation test, periyodik tenant_id audit, drift detection aktif

---

## 12. Dokümantasyon ve Bekçi Uyumu

### 12.1 Dokümantasyon SSOT

| Belge | Rol | Güncel mi? |
|-------|-----|------------|
| authority.json | Beklenen kural SSOT | ✅ v6.1.1 |
| SAB.md | Teknik anayasa | ✅ v24.2.0 |
| Constitution | Mimari anayasa | ✅ v1.0.0 |
| REGISTRY.md | Canlı catalog | 🟡 ADR index güncel değil, tablo matrix eksik |
| ONBOARDING.md | Giriş/yönlendirme | ✅ NOT SSOT |
| docs/known-debt.md | Teknik borç | ✅ 343 satır, aktif |
| docs/ERA_V/PHASE2-ROADMAP.md | Roadmap | ✅ Sprint 13-16 |

### 12.2 Bekçi Kapsamı

| Mekanizma | Kapsam | Hariç |
|-----------|-------|-------|
| `sab:integrity-scan` | app/ PHP dosyaları | docs/ (`.bekciignore`) |
| `bekci:tenant-audit` | Model + migration dosyaları | docs/, runtime scope |
| `guard:cqrs` | CQRS boundary | docs/ |
| `sab:guard` | Tüm SAB kuralları | docs/ |

**Önemli:** Bekçi kodu denetler, Markdown dokümantasyonunu denetlemez. `.bekciignore` `docs/`'u hariç tutar.

### 12.3 Önerilen Karar (ACTION_PROPOSED)

**Karar #12 — Documentation SSOT and Archiving:**

```
1. REGISTRY.md ADR index güncellenmeli (23 ADR, 5 değil)
2. REGISTRY.md tablo matrix tüm tabloları kapsamalı
3. Eski/stale dokümanlar docs/_archive/'a taşınmalı
4. SSOT hiyerarşisi dokümante edilmeli (Karar #1)
5. Dokümantasyon denetimi periyodik yapılmalı (Bekçi dışı, ayrı süreç)
```

**Karar #13 — Bekçi Enforcement Scope:**

```
1. Bekçi kod denetimi yapar (model, migration, controller, service)
2. Bekçi Markdown denetimi yapmaz (.bekciignore docs/ hariç tutar)
3. Bekçi runtime scope doğrulaması yapmaz (test ile doğrulanmalı)
4. Bekçi tenant-audit: model/migration denetler, runtime değil
5. MD denetimi ayrı süreç gerektirir (MD_MIMARI_UYUMLULUK_DENETIMI_AGENT_TALIMATI)
```

→ Detaylı SSOT map: `DOCUMENTATION_SSOT_MAP.md` (Ayrı dosya)

**Owner:** Architecture owner / Documentation owner
**Kapanış kriteri:** REGISTRY.md güncel, stale docs arşiv, SSOT hiyerarşisi dokümante

---

## 13. 15 Mimari Karar Özeti

| # | Karar | Owner | Durum | Kanıt |
|---|-------|-------|-------|-------|
| 1 | SSOT hiyerarşisi ve kapsam ayrımı | SAAB | ACTION_PROPOSED | REPO_VERIFIED |
| 2 | Tenant context standard (fail-closed, tüm route'lar, queue) | Security | ACTION_PROPOSED | REPO_VERIFIED |
| 3 | Domain ownership matrix (tüm tablolar) | Architecture | ACTION_PROPOSED | REPO_VERIFIED |
| 4 | CQRS projection lifecycle (tenant_id, rebuild, replay) | Architecture | ACTION_PROPOSED | REPO_VERIFIED |
| 5 | Event naming, versioning, idempotency | Architecture | ACTION_PROPOSED | DOCUMENTED |
| 6 | Queue tenant context (TenantAwareJobInterface) | Backend | ACTION_PROPOSED | REPO_VERIFIED |
| 7 | Admin/Super-Admin access (tenant-aware, AI yasağı) | Security | ACTION_PROPOSED | REPO_VERIFIED |
| 8 | API versioning (v1/v2, OpenAPI, Idempotency-Key) | Backend | ACTION_PROPOSED | DOCUMENTED |
| 9 | AI-Hermes-domain separation (pure orchestrator) | AI | ACTION_PROPOSED | REPO_VERIFIED |
| 10 | Migration, backfill, rollback (tenant-aware) | Backend/DBA | ACTION_PROPOSED | DOCUMENTED |
| 11 | Production evidence (tenant test, drift, projection health) | DevOps | ACTION_PROPOSED | DOCUMENTED |
| 12 | Documentation SSOT and archiving | Architecture | ACTION_PROPOSED | REPO_VERIFIED |
| 13 | Bekçi enforcement scope (kod vs MD vs runtime) | Architecture | ACTION_PROPOSED | REPO_VERIFIED |
| 14 | Global vs tenant table policy | Architecture | ACTION_PROPOSED | REPO_VERIFIED |
| 15 | Model/Scope/Policy requirements | Security | ACTION_PROPOSED | REPO_VERIFIED |

### Karar #14 — Global vs Tenant Table Policy

```
Legitimately Global (tenant_id gereksiz):
  - system_settings, countries, cities, districts, currencies
  - ai_provider_profiles (sistem bazlı)
  - portal_definitions (sistem bazlı)

Tenant-Scoped (tenant_id zorunlu):
  - properties, ilanlar, kisiler, reservations, media_assets
  - commissions, finance, audit_logs
  - CQRS projection tabloları (6/6 eksik)

Conditional (nullable tenant_id):
  - users (super-admin = null, tenant user = tenant_id)
```

### Karar #15 — Model/Scope/Policy Requirements

```
1. Her tenant-scoped model BelongsToTenant trait kullanmalı
2. TenantScope fail-closed olmalı (tenant_id=null → bo sonuç)
3. CountryScope fail-closed olmalı (ulke_id=null → bo sonuç)
4. Her tenant-scoped model global scope kaydetmeli (boot())
5. Policy katmanı tenant-aware authorization yapmalı
6. withoutGlobalScopes() sadece audit log ile kullanılmalı
```

**Owner:** Security / Architecture
**Kapanış kriteri:** Tüm tenant-scoped modellerde BelongsToTenant, fail-closed scope, tenant-aware policy

---

## 14. Çıktı Dosyaları

| # | Dosya | İçerik | Durum |
|---|-------|--------|-------|
| 1 | `docs/architecture/ARCHITECTURE_BACKBONE_AUDIT.md` | Bu dosya — ana audit | ✅ Yazıldı |
| 2 | `docs/architecture/DOMAIN_OWNERSHIP_MATRIX.md` | Tüm tabloların sahiplik matrixi | ⏳ Sonraki |
| 3 | `docs/architecture/TENANT_ISOLATION_CONTRACT.md` | Tenant izolasyon sözleşmesi | ⏳ Sonraki |
| 4 | `docs/architecture/EVENT_AND_QUEUE_CONTRACT.md` | Event ve queue sözleşmesi | ⏳ Sonraki |
| 5 | `docs/architecture/AI_HERMES_BOUNDARY.md` | AI/Hermes sınırı | ⏳ Sonraki |
| 6 | `docs/architecture/DOCUMENTATION_SSOT_MAP.md` | Dokümantasyon SSOT map | ⏳ Sonraki |
| 7 | `docs/adr/ADR-XXXX-ARCHITECTURE-BACKBONE.md` | ADR kaydı | ⏳ Sonraki |

---

## 15. Teslim Cümlesi

```text
Bu çalışma Yalıhan OS mimari omurgasının salt-okunur denetimidir.
15 mimari karar için ACTION_PROPOSED önerileri sunulmuştur.
Hiçbir kod değişikliği, migration, production doğrulaması veya
release onayı bu kapsamda yapılmamıştır. Her öneri, ilgili owner
onayı ve güncel snapshot üzerinde ayrı doğrulama gerektirir.
```

**Kanıt seviyesi:** Bu rapor REPO_VERIFIED ve DOCUMENTED kanıt seviyelerine dayanır. TEST_VERIFIED ve PRODUCTION_VERIFIED kanıtları bu kapsamda toplanmamıştır.

---

*Bu audit 2026-09-07 tarihinde hazırlanmıştır. Kaynaklar: authority.json (v6.1.1), SAB.md (v24.2.0), YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md, REGISTRY.md, tenant-isolation-audit-2026-09-06.md, cqrs-projection-research-report-2026-09-06.md, SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md, docs/known-debt.md, docs/ERA_V/PHASE2-ROADMAP.md.*