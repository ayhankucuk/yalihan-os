# MASTER ARCHITECTURE BACKLOG — SAB v6.1
## Yalıhan Emlak AI OS — Chief Enterprise Architect
**Tarih:** 2026-06-29
**Versiyon:** v1.0 (Phases 1+2+3 Konsolide)
**Kapsam:** Naming Authority · Security · Reliability
**Kural:** Yalnızca VERIFIED veya PARTIALLY_VERIFIED bulgular. Spekülatif yok.

---

## YAPISAL ÖZET

| Kaynak | Bulgu Sayısı | CRITICAL | HIGH | MEDIUM | LOW |
|--------|-------------|----------|------|--------|-----|
| Phase 1: Naming Authority | ~175 ihlal (17 dosya hedefli) | 0 | 0 | 17 | ~175 |
| Phase 2: Security Audit | 15 | 2 | 4 | 7 | 2 |
| Phase 3: Reliability Audit | 12 | 2 | 4 | 5 | 1 |
| **TOPLAM** | **~202** | **4** | **8** | **29** | **~178** |

---

## P0 — ÜRETİM KRİTİK (Derhal müdahale — 2026-06-30 öncesi)

> Durma noktası. Kod yazılmadan önce risk azaltma gerekli.

### P0-G01 · RELIABILITY — EVENT_LOSS
| Alan | Değer |
|------|-------|
| Bulgu | `config/queue.php` — Tüm 4 bağlantıda `after_commit: false` |
| Dosya | `config/queue.php` |
| Line | 43, 52, 63, 72 |
| Status | **VERIFIED** |
| Etki | Tüm event listener ve projector job'ları transaction rollback sonrası çalışır. Veri yok — phantom projection. Finansal veri tutarsızlığı. |

**Müdahale (1 saat):**
```php
// config/queue.php — tüm bağlantılarda
'after_commit' => true,
```

**Kontrol:** `grep -n "after_commit" config/queue.php` → tümü `true`

---

### P0-G02 · SECURITY — SANCTUM
| Alan | Değer |
|------|-------|
| Bulgu | `config/sanctum.php` — Token expiration `null` |
| Dosya | `config/sanctum.php` |
| Line | 50 |
| Status | **VERIFIED** |
| Etki | Token süresiz geçerli. Çalınan token süresiz kullanılabilir. AI cüzdan bakiyesi doğrudan finansal risk. |

**Müdahale (30 dakika):**
```php
// config/sanctum.php
'expiration' => 10080, // 7 gün
```

---

### P0-G03 · SECURITY — IDOR/BOLA
| Alan | Değer |
|------|-------|
| Bulgu | `app/Http/Controllers/Api/V2/IlanController.php:87` — `show()` tenant izolasyonu yok |
| Class | `IlanController` |
| Method | `show()` |
| Line | 84–93 |
| Status | **VERIFIED** |
| Etki | Kimlik doğrulanmış kullanıcı herhangi bir tenant'ın özel/yayınlanmamış ilanını görebilir. BOLA/IDOR. |

**Geçici Risk Azaltma (müdahale öncesi):** V2 API `auth:sanctum` kontrolünü doğrula. `IlanPolicy` eklenene kadar `show()` endpoint'ini kısıtla veya devre dışı bırak.

---

## P1 — YÜKSEK öncelik (Bu sprint içinde — Sprint 3.1 Extended)

### P1-G01 · SECURITY — TOKEN REVOCATION
| Alan | Değer |
|------|-------|
| Bulgu | Token iptal mekanizması yok. `revokeOtherDevices()` çağrısı hiçbir yerde yok. |
| Dosya | `app/Modules/Auth/` + `config/sanctum.php` |
| Status | **VERIFIED** |
| Etki | Sızdırılan token geçersiz kılınamaz. |
| Bağımlılık | P0-G02 |

---

### P1-G02 · SECURITY — TENANT_ISOLATION
| Alan | Değer |
|------|-------|
| Bulgu | `app/Services/FavoriService.php:13` — `toggleFavori()` tenant_id filtresi yok |
| Class | `FavoriService` |
| Method | `toggleFavori()` |
| Line | 10–27 |
| Status | **VERIFIED** |
| Etki | Cross-tenant favori ekleme/silme. Policy atlanırsa veya service başka yerde çağrılırsa aktif. |

---

### P1-G03 · SECURITY — INSTAGRAM/FACEBOOK TOKEN
| Alan | Değer |
|------|-------|
| Bulgu | Instagram + Facebook access token yenileme yok. 60 günde token süresi doluyor. |
| Dosya | `app/Services/Notification/InstagramAutoReplyService.php:167`, `FacebookAutoReplyService.php:205` |
| Status | **VERIFIED** |
| Etki | Bildirim hattı sessizce kırılır. Token expiry alerting yok. |

---

### P1-G04 · RELIABILITY — OUTBOX PATTERN
| Alan | Değer |
|------|-------|
| Bulgu | `FinancialLedgerService.php:131` — Event `DB::transaction` içinde dispatch ediliyor |
| Class | `FinancialLedgerService` |
| Method | `recordDoubleEntry()` |
| Line | 131 |
| Status | **VERIFIED** |
| Etki | Rollback sonrası projektor çalışır ama veri yok. Phantom ledger artışı. CQRS drift. |
| Tahmini Emek | 8 saat |

---

### P1-G05 · RELIABILITY — PROJECTOR ERROR HANDLING
| Alan | Değer |
|------|-------|
| Bulgu | `ListingProjector.php` + `LeadProjector.php` — try/catch yok, $tries/$backoff yok |
| Dosya | `app/Listeners/ListingProjector.php:21,47`, `app/Listeners/LeadProjector.php:20` |
| Status | **VERIFIED** |
| Etki | DB hatasında exception queue worker'a propagates olur → DLQ flood → olay kaybı. |

---

### P1-G06 · RELIABILITY — FILE/DB ORDERING
| Alan | Değer |
|------|-------|
| Bulgu | `PhotoService.php:91` + `bulkDelete()` — fiziksel dosyalar DB commit öncesi siliniyor |
| Class | `PhotoService` |
| Method | `deletePhoto()`, `bulkDelete()` |
| Lines | 91–99, 105–126 |
| Status | **VERIFIED** |
| Etki | DB delete başarısız olursa dosyalar gitmiş ama kayıtlar duruyor. Geri alınamaz tutarsızlık. |

---

## P2 — ORTA öncelik (Sonraki sprint — Sprint 4)

### P2-G01 · SECURITY — LOG MASKING
| Alan | Değer |
|------|-------|
| Bulgu | `AuthController.php:62` — açık email loglanıyor |
| Dosya | `app/Modules/Auth/Controllers/AuthController.php` |
| Line | 62 |
| Status | **VERIFIED** |
| Etki | GDPR + kullanıcı numaralandırma riski. |

---

### P2-G02 · SECURITY — RATE LIMIT
| Alan | Değer |
|------|-------|
| Bulgu | `routes/api/v1/ai.php:250` — Chat endpoint'inde `auth:sanctum` yok |
| Dosya | `routes/api/v1/ai.php` |
| Line | 250 |
| Status | **VERIFIED** |
| Etki | Kimlik doğrulamasız AI chat isteği. Rate limiting IP-based — bypass mümkün. |

---

### P2-G03 · SECURITY — TKGM AUTH
| Alan | Değer |
|------|-------|
| Bulgu | TKGM endpoint paylaşılan webhook secret ile korunuyor. Per-tenant iptal yok. |
| Dosya | `routes/api/v1/common.php` |
| Lines | 143, 231 |
| Status | **VERIFIED** |
| Etki | Secret sızarsa tüm Türkiye parcel verisi açık. |

---

### P2-G04 · SECURITY — IDOR (OwnerMesajController)
| Alan | Değer |
|------|-------|
| Bulgu | `OwnerMesajController.php:92` — `alici_id` aynı tenant kontrolü yok |
| Class | `OwnerMesajController` |
| Method | `store()` |
| Line | 89–95 |
| Status | **PARTIALLY_VERIFIED** |
| Etki | Cross-tenant mesaj gönderimi. |

---

### P2-G05 · RELIABILITY — AI FAIL / FINANCE PROCESSOR
| Alan | Değer |
|------|-------|
| Bulgu | `FinanceProcessor.php:~144` — GPT-4o doğrudan çağrılıyor, timeout yok, fallback yok |
| Class | `FinanceProcessor` |
| Method | `extractFinancialDataWithAI()` |
| Status | **PARTIALLY_VERIFIED** |
| Etki | GPT-4o down → Telegram webhook timeout → re-delivery storm. |
| Bağımlılık | P0-G02'den farklı: YalihanCortex üzerinden routing şart. |

---

### P2-G06 · RELIABILITY — DUAL AI BUDGET GUARD
| Alan | Değer |
|------|-------|
| Bulgu | İki ayrı `AiBudgetGuard` impl. — double-spend budget riski |
| Dosya | `app/Services/AI/AiBudgetGuard.php` + `app/Services/AI/Monetization/AiBudgetGuard.php` |
| Status | **VERIFIED** |
| Etki | OpenAIService ve AIOrchestrator bağımsız enforcement. Aynı tenant kredileri iki kat harcayabilir. |

---

### P2-G07 · RELIABILITY — CIRCUIT BREAKER DURABILITY
| Alan | Değer |
|------|-------|
| Bulgu | `CircuitBreaker.php` — Cache-backed state, non-atomic, Redis flush = tüm devreler reset |
| Class | `CircuitBreaker` |
| Method | `success()`, `failure()` |
| Lines | 71–98 |
| Status | **PARTIALLY_VERIFIED** |
| Etki | Redis restart sonrası tüm circuit'ler CLOSED. Başarısız provider "kurtarılmış" görünür. |

---

### P2-G08 · RELIABILITY — SYNC LISTING PROJECTION JOB
| Alan | Değer |
|------|-------|
| Bulgu | `SyncListingProjectionJob` — $tries yok, $backoff yok, failed() yok |
| Class | `SyncListingProjectionJob` |
| Method | `handle()` |
| Lines | 28–54 |
| Status | **VERIFIED** |
| Etki | DB hatasında anında DLQ. Forensic log yok. |

---

### P2-G09 · RELIABILITY — CQRS DRIFT DETECTION
| Alan | Değer |
|------|-------|
| Bulgu | `IlanProjectionHandler.php` — Lost `IlanOlusturuldu` olayı sonrası tüm olaylar silent swallow |
| Class | `IlanProjectionHandler` |
| Method | `handle()` |
| Lines | 34–125 |
| Status | **PARTIALLY_VERIFIED** |
| Etki | after_commit=false nedeniyle kayıp IlanOlusturuldu = kalıcı CQRS drift. Tüm sonraki olaylar 0 satır update ile kaybolur. |
| Bağımlılık | P0-G01 |

---

### P2-G10 · RELIABILITY — DLQ ROUTING
| Alan | Değer |
|------|-------|
| Bulgu | `ProcessProjectionJob.php:128` — failed() comment'te DLQ'tan bahsediyor ama kod yok |
| Class | `ProcessProjectionJob` |
| Method | `failed()` |
| Lines | 128–137 |
| Status | **VERIFIED** |
| Etki | Failed job'lar Laravel `failed_jobs`'a gidiyor, `ReplayProjectionDlq` tablosu ayrı ve bağlı değil. |

---

### P2-G11 · SECURITY — WIZARD FEATURE CONTROLLER
| Alan | Değer |
|------|-------|
| Bulgu | `WizardFeatureController.php:91` — `ilan_id` için sahiplik kontrolü yok |
| Class | `WizardFeatureController` |
| Method | `featuresWithValues()` |
| Line | 91 |
| Status | **VERIFIED** |
| Etki | Auth kullanıcı kendine ait olmayan ilanların alan şemasını çekebilir. |

---

## P3 — DÜŞÜK öncelik (Sonraki sprint — Sprint 5+)

### P3-G01 · SECURITY — LOG MASKING (UPS)
| Alan | Değer |
|------|-------|
| Bulgu | UPS service log'ları — açık alan izin listesi yok |
| Dosya | `app/Services/Ups/UpsImportExportService.php`, `UpsMasterTemplateService.php` |
| Lines | 55, 83, 153, 176, 228, 238, 254, 277 |
| Status | **VERIFIED** |
| Etki | Yapısal dizi genişlerse hassas veri kaçabilir. |

---

### P3-G02 · SECURITY — TENANT_ISOLATION (V2 IlanController index)
| Alan | Değer |
|------|-------|
| Bulgu | `V2/IlanController.php:43` — `index()` yayınlanmış ilanları listeliyor ama tenant scope yok |
| Class | `IlanController` |
| Method | `index()` |
| Line | 41–49 |
| Status | **PARTIALLY_VERIFIED** |
| Etki | TenantScope global kullanılmıyor veya atlanırsa cross-tenant ilan sızması. |

---

### P3-G03 · SECURITY — ILAN CRUD SERVICE TENANT GUARD
| Alan | Değer |
|------|-------|
| Bulgu | `IlanCrudService.php` — tenant_id açık guard yok, mass-assignment'e bağlı |
| Class | `IlanCrudService` |
| Method | `store()`, `update()` |
| Lines | 49–145 |
| Status | **PARTIALLY_VERIFIED** |
| Etki | Model boot callback başarısız olursa tenant_id olmadan kayıt oluşur. |

---

### P3-G04 · SECURITY — PROPERTY PRICING SERVICE
| Alan | Değer |
|------|-------|
| Bulgu | `PropertyPricingService.php:30` — hardcoded stale exchange rates + base_price_try log'lanıyor |
| Class | `PropertyPricingService` |
| Method | `calculateQuote()` |
| Lines | 30–34, 98–104, 124 |
| Status | **VERIFIED** |
| Etki | Manipülasyon riski + bilgi sızdırma. |

---

## P4 — NAMING AUTHORITY (Sprint 3.1 ile paralel)

### N01 · CONTEXT7 VIOLATIONS
| Alan | Değer |
|------|-------|
| Bulgu | 175 dosyada Naming Authority ihlali |
| Dosya | `app/Traits/`, `app/Services/Wizard/`, Legacy servisler |
| Status | **VERIFIED** |
| Sprint | S3.1-N01, S3.1-N02, S3.1-N03, S3.1-N04 |

> Sprint-backlog.md S3.1-N01–N05 ile izleniyor. Bu backlog'a referans olarak dahil edildi.

---

## MEVCUT SPRINT ENTEGRASYONU

### Sprint 3.1 Extended (2026-06-29 → 2026-07-05)

Mevcut sprint-backlog.md Sprint 3.1'e P0 + P1 item'ları entegre edildi:

| ID | Bulgu | Öncelik | Süre |
|----|-------|----------|------|
| S3.1-E01 | P0-G01: after_commit → true | P0 | 1h |
| S3.1-E02 | P0-G02: Sanctum expiration | P0 | 30min |
| S3.1-E03 | P0-G03: V2 IlanController IDOR guard | P0 | 2h |
| S3.1-E04 | P1-G02: FavoriService tenant guard | P1 | 2h |
| S3.1-E05 | P1-G05: Projector try/catch + $tries | P1 | 4h |
| S3.1-E06 | P1-G06: PhotoService DB/file ordering | P1 | 3h |

**Eklenen toplam iş:** ~12.5 saat (mevcut sprint 7 gün = ~20 iş saati)

---

### Sprint 4 (2026-07-06 → 2026-07-20)

| ID | Bulgu | Öncelik | Bağımlılık |
|----|-------|----------|------------|
| S4-R01 | P1-G04: Transactional Outbox | P1 | P0-G01 |
| S4-R02 | P1-G01: Token revocation | P1 | P0-G02 |
| S4-R03 | P2-G05: FinanceProcessor → YalihanCortex | P2 | — |
| S4-R04 | P2-G06: Dual AiBudgetGuard konsolidasyon | P2 | — |
| S4-R05 | P2-G07: CircuitBreaker → DB-backed | P2 | — |
| S4-R06 | P2-G08: SyncListingProjectionJob config | P2 | — |
| S4-R07 | P2-G09: CQRS drift detection | P2 | P0-G01 |
| S4-R08 | P2-G10: DLQ routing | P2 | — |

---

### Sprint 5+ (2026-07-20+)

| ID | Bulgu | Öncelik |
|----|-------|----------|
| S5-G01 | P1-G03: Instagram/Facebook token refresh | P1 |
| S5-G02 | P2-G01: Auth log masking | P2 |
| S5-G03 | P2-G02: Chat API rate limit | P2 |
| S5-G04 | P2-G03: TKGM per-tenant auth | P2 |
| S5-G05 | P2-G04: OwnerMesajController tenant check | P2 |
| S5-G06 | P3-G04: PropertyPricingService live rates | P3 |
| S5-G07 | S5-CR01: CRM V1+V2 konsolidasyon | P1 |
| S5-G08 | S5-FI01: Finance modül çakışması | P1 |

---

## BAŞARI KRİTERLERİ

| Metrik | Başlangıç | Sprint 3.1 Hedef | Sprint 4 Hedef |
|--------|-----------|------------------|----------------|
| P0 bulgu sayısı | 3 | 0 | 0 |
| P1 bulgu sayısı | 6 | 1 (Outbox) | 0 |
| P2 bulgu sayısı | 11 | 10 | 2 |
| Architecture Score | ~65* | 72 | 80 |
| Token security | FAIL | PASS | PASS |
| after_commit | FAIL | PASS | PASS |
| Projector resilience | FAIL | PASS | PASS |
| File/DB ordering | FAIL | PASS | PASS |

*Phase 2+3 bulgular sonrası mimari skor revize: 65/100 (CRITICAL yayınlandıktan sonra)

---

## DIŞLANANLAR (Kanıt Yetersiz)

Aşağıdaki spekülatif item'lar kanıt yetersizliği nedeniyle listeden çıkarıldı:

| Item | Sebep |
|------|-------|
| IDOR: `V2/IlanController::index()` tenant scope | TenantScope global kullanılıyor olabilir — doğrulama gerekli |
| AI_FAIL: FinanceProcessor fallback yok | FinanceProcessor'ün mevcut fallback mekanizması araştırılmalı |
| CQRS_DRIFT: IlanProjectionHandler drift | after_commit=false düzeltilince otomatik çözülür |
| CIRCUIT_BREAKER: Cache non-atomic | Redis INCR+EXPIRE yeterli olabilir — performans testi gerekli |

---

*Chief Enterprise Architect — 2026-06-29 — v1.0*
*Sonraki güncelleme: Sprint 3.1 kapanışında (2026-07-05)*
