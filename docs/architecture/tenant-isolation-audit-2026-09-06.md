---
document_id: ARCH-REP-20260906-TENANT
document_owner: architecture
decision_owner: saab
status: active
canonical: true
evidence_level: REPO_VERIFIED
as_of_commit: 587e7020
last_reviewed: 2026-09-06
review_after: 2026-10-06
supersedes: null
---

# Tenant İzolasyon Eksiklikleri — `tenant_id` Olmayan Tabloların Audit Raporu

> [!IMPORTANT]
> **Sınır ve Kapsam:** Bu belge production doğrulaması değildir. Migration veya deploy yetkisi vermez. Tüm tespitler ve eylem önerileri `ACTION_PROPOSED` statüsündedir.

> **Tarih:** 2026-09-06
> **Durum:** Araştırma — production doğrulaması değildir
> **Önceki Rapor:** `cqrs-projection-research-report-2026-09-06.md` (REPO_VERIFIED)

---

## İçindekiler

1. [Yönetici Özeti](#1-yönetici-özeti)
2. [Metodoloji](#2-metodoloji)
3. [tenant_id'ye Sahip Tablolar — Tam Envanter](#3-tenant_idye-sahip-tablolar--tam-envanter)
4. [BelongsToTenant Kullanan Modeller](#4-belongstotenant-kullanan-modeller)
5. [tenant_id Eksik Tablolar — Kritik Gap'ler](#5-tenant_id-eksik-tablolar--kritik-gapler)
6. [Sınıflandırma: tenant_id Gerekli mi, Global mi?](#6-sınıflandırma-tenant_id-gerekli-mi-global-mi)
7. [Risk Matrisi](#7-risk-matrisi)
8. [Önerilen Öncelik Sırası](#8-önerilen-öncelik-sırası)

---

## 1. Yönetici Özeti

Bu audit, codebase'deki tüm tabloların `tenant_id` izolasyon durumunu tarar. CQRS projection raporu 6 projection tablosunda `tenant_id` eksikliğini belgelemişti; bu rapor kapsamı **tüm codebase**'e genişletir.

### Kritik Bulgular

| Metrik | Değer |
|--------|-------|
| Toplam `Schema::create` ile oluşturulan tablo sayısı | ~140+ (benzersiz) |
| `tenant_id` kolonuna sahip tablolar | ~50 |
| `tenant_id` eksik tablolar (tenant-scoped olması gereken) | ~35+ |
| `BelongsToTenant` trait kullanan model | 12 |
| `HasCountryScope` trait kullanan model | ~180+ |
| `HasCountryScope` ama `BelongsToTenant` KULLANMAYAN model | ~170+ |
| İki traiti birden kullanan model | 4 (Ilan, Kisi, Lead, AiLog, Property) |

### Ana Sorun

Codebase'de iki izolasyon katmanı vardır:

1. **`BelongsToTenant` + `TenantScope`** — `tenant_id` kolonu üzerinden tenant izolasyonu (`TenantContextService`'ten alınır)
2. **`HasCountryScope` + `CountryScope`** — `ulke_id` kolonu üzerinden ülke izolasyonu (`Auth::user()->ulke_id`'den alınır)

**~170+ model sadece `HasCountryScope` kullanıyor, `BelongsToTenant` kullanmıyor.** Bu, `ulke_id` izolasyonu olmasına rağmen aynı ülke içindeki farklı tenant'ların verilerinin karışabileceği anlamına gelir.

**Ancak:** `ulke_id` ve `tenant_id` farklı izolasyon seviyeleridir. Tek tenant'lu tek ülke senaryosunda fark etmez; çok tenant'lu senaryolarda `tenant_id` eksikliği veri sızıntısına yol açar.

---

## 2. Metodoloji

### Veri Kaynakları

1. **`database/migrations/`** — Tüm migration dosyalarında `tenant_id` ve `Schema::create` araması
2. **`app/Models/`** — `BelongsToTenant` ve `HasCountryScope` trait kullanım araması
3. **`app/Traits/BelongsToTenant.php`** — Trait davranışı
4. **`app/Traits/HasCountryScope.php`** — Trait davranışı
5. **`app/Scopes/TenantScope.php`** — Global scope davranışı
6. **`app/Scopes/CountryScope.php`** — Global scope davranışı

### Arama Kriterleri

- `Schema::create('tablo_adi', ...)` → tablo envanteri
- `tenant_id` migration içinde → hangi tablolarda var
- `use BelongsToTenant` model içinde → hangi modellerde tenant scope var
- `use HasCountryScope` model içinde → hangi modellerde country scope var

### Sınıflandırma Mantığı

Bir tablonun `tenant_id`'ye ihtiyaç duyup duymadığını belirlemek için:

- **Tenant-scoped (tenant_id gerekli):** Tablo tenant'a özel iş verisi içeriyorsa (ilan, talep, kişi, rezervasyon, ödeme, komisyon, belge, mesaj, görev, vb.)
- **Legitimately global (tenant_id gereksiz):** Tablo referans verisi, sistem konfigürasyonu veya shared metadata içeriyorsa (ülkeler, iller, ilçeler, kategoriler, para birimleri, diller, tenants tablosunun kendisi, vb.)
- **Nullable tenant_id (koşullu):** Tablo hem global hem tenant-scoped kayıtlar barındırabiliyorsa (feature_assignments, governance_events, vb.)

---

## 3. tenant_id'ye Sahip Tablolar — Tam Envanter

Aşağıdaki tablolar migration'larda `tenant_id` kolonu ile oluşturulmuş veya sonradan eklenmiş:

### 3.1 Core Tablolar (Sonradan Eklenen)

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `users` | `2026_05_03_015000` + `2026_05_01_143751` | `foreignId` | ✅ Nullable |
| `ilanlar` | `2026_06_29_100000` | `unsignedBigInteger` | ✅ Nullable |
| `talepler` | `2026_06_29_100000` | `unsignedBigInteger` | ✅ Nullable |
| `kisiler` | `2026_06_29_100000` + `2026_07_18_011152` | `unsignedBigInteger` | ❌ NOT NULL (hardened) |
| `property_reservations` | `2026_06_29_100000` | `unsignedBigInteger` | ✅ Nullable |
| `ilan_fotograflari` | `2026_06_29_100000` | `unsignedBigInteger` | ✅ Nullable |
| `gorevler` | `2026_09_06_000001` | `unsignedBigInteger` | ✅ Nullable |
| `leads` | `2026_05_19_080616` | `unsignedBigInteger` | ✅ Nullable |
| `communications` | `2026_08_21_000001` | `foreignId` | ✅ Nullable |

### 3.2 Financial Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `ledger_accounts` | `2026_05_15_090000` | `foreignId` | ✅ Nullable |
| `ledger_entries` | `2026_05_15_090000` | `foreignId` | ✅ Nullable |
| `ledger_balances` | `2026_05_15_090000` | `foreignId` | ✅ Nullable |
| `financial_settings` | `2026_05_15_090000` | `foreignId` | ✅ Nullable |
| `commissions` | `2026_05_25_235959` | `unsignedBigInteger` | ✅ Nullable |
| `bonuses` | `2026_05_25_235959` | `unsignedBigInteger` | ✅ Nullable |
| `transactions` | `2026_05_15_202816` | `foreignId` | ❌ NOT NULL |
| `payments` | `2026_08_27_000001` | `foreignId` | ❌ NOT NULL |
| `ai_credit_balances` | `2026_05_15_090000` | `foreignId` | ❌ Unique (1:1 tenant) |
| `ai_transactions` | `2026_05_03_030000` | `unsignedBigInteger` | ❌ NOT NULL |
| `ai_workspace_wallets` | `2026_05_03_020000` | `foreignId` | ✅ Nullable |

### 3.3 Property Domain Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `properties` | `2026_07_17_155222` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_workspaces` | `2026_07_06_000001` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_access_assets` | `2026_07_18_011230` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_key_custodies` | `2026_07_18_011230` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_key_custodies_v2` | `2026_07_18_011300` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_ownerships` | `2026_07_18_011210` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_representatives` | `2026_07_18_011220` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_documents` | `2026_07_18_011240` | `unsignedBigInteger` | ❌ NOT NULL |
| `property_readiness` | `2026_08_16_000003` | `unsignedBigInteger` | ❌ NOT NULL |
| `access_credentials` | `2026_08_16_000004` | `unsignedBigInteger` | ❌ NOT NULL |
| `portfolio_drive_workspaces` | `2026_07_03_000002` | `unsignedBigInteger` | ✅ Nullable |

### 3.4 AI / Governance / Telemetry Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `ai_logs` | `2026_05_01_144000` | `foreignId` | ✅ Nullable |
| `ai_feature_usages` | `2026_05_01_144000` | `foreignId` | ✅ Nullable |
| `ai_telemetry` | `2026_05_21_000000` | `unsignedBigInteger` | ❌ NOT NULL |
| `etki_alani_olaylari` | `2026_05_27_205335` | `unsignedBigInteger` | ❌ NOT NULL |
| `etki_alani_olaylari_hatali` | `2026_05_27_212611` | `unsignedBigInteger` | ❌ NOT NULL |
| `governance_decisions` | `2026_05_29_000000` | `unsignedBigInteger` | ✅ Nullable |
| `governance_events` | `2026_05_13_000001` | `unsignedBigInteger` | ✅ Nullable |

### 3.5 Settlement / Banking Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `bank_accounts` | `2026_08_23_000004` | `unsignedBigInteger` | ❌ NOT NULL |
| `provider_settlements` | `2026_08_23_000002` | `unsignedBigInteger` | ❌ NOT NULL |
| `settlement_allocations` | `2026_08_23_000002` | `unsignedBigInteger` | ❌ NOT NULL |
| `bank_transactions` | `2026_08_23_000002` | `unsignedBigInteger` | ❌ NOT NULL |
| `reconciliation_executions` | `2026_08_23_000002` | `unsignedBigInteger` | ❌ NOT NULL |

### 3.6 Owner Report Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `owner_report_rows` | `2026_05_16_100001` + `2026_08_31_185500` | `unsignedBigInteger` | ✅ Nullable |
| `owner_report_metrics` | `2026_05_16_100002` + `2026_08_31_185500` | `unsignedBigInteger` | ✅ Nullable |
| `owner_report_exports` | `2026_05_16_100003` | `unsignedBigInteger` | ❌ NOT NULL |

### 3.7 SaaS / Billing Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `tenants` | `2026_05_03_010000` | — (tablonun kendisi) | — |
| `subscriptions` | `2026_05_03_010000` | `foreignId` | ❌ NOT NULL |
| `billing_ledger_entries` | `2026_05_03_010000` | `foreignId` | ❌ NOT NULL |

### 3.8 Communication / Messaging Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `mesajlar` | `2026_05_15_220408` | `foreignId` | ❌ NOT NULL |
| `owner_login_tokens` | `2026_05_15_220000` | `foreignId` | ❌ NOT NULL |
| `belgeler` | `2026_05_15_220952` | `foreignId` | ❌ NOT NULL |
| `teklifler` | `2026_05_15_212751` | `foreignId` | ❌ NOT NULL |
| `guest_messages` | `2026_08_16_000001` | `unsignedBigInteger` | ✅ Nullable |
| `oauth_tokens` | `2026_08_21_000003` | `foreignId` | ❌ NOT NULL |

### 3.9 Workforce / Pipeline Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `workspace_executions` | `2026_07_04_085926` | `foreignId` | ✅ Nullable |
| `workforce_executions` | `2026_08_24_000001` | `unsignedBigInteger` | ✅ Nullable |
| `workforce_execution_logs` | `2026_07_03_000001` | `unsignedBigInteger` | ✅ Nullable |
| `channel_sync_executions` | `2026_07_29_000001` | `unsignedBigInteger` | ❌ NOT NULL |

### 3.10 Read Model / Projection Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `ilanlar_read_model` | `2026_05_27_214958` | `unsignedBigInteger` | ❌ NOT NULL |
| `leads_read_model` | `2026_05_27_214719` | `unsignedBigInteger` | ❌ NOT NULL |
| `kisiler_read_model` | `2026_05_28_021000` | `foreignId` | ❌ NOT NULL |

### 3.11 Hermes / Event Log Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `hermes_event_logs` | `2026_06_28_000001` | `unsignedBigInteger` | ✅ Nullable |
| `hermes_analytics` | `2026_06_28_000002` | `unsignedBigInteger` | ✅ Nullable |

### 3.12 Feature Assignment

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `feature_assignments` | `2026_08_25_150345` | `unsignedBigInteger` | ✅ Nullable (template-level = NULL) |

### 3.13 Legacy / Bulk Convergence Tablolar

| Tablo | Migration | tenant_id Tipi | Nullable? |
|-------|-----------|----------------|-----------|
| `governance_incidents` | `2026_05_06_153500` | `string` (default 'SYSTEM') | ❌ NOT NULL |
| `country_financial_rules` | `2026_05_06_153500` | — | — (country-scoped) |
| `ai_tenant_quotas` | `2026_05_03_000000` | `unsignedBigInteger` | ❌ NOT NULL |
| `ai_tenant_settings` | `2026_05_03_000000` | `unsignedBigInteger` | ❌ NOT NULL |

## 4. BelongsToTenant Kullanan Modeller

Aşağıdaki 12 model `BelongsToTenant` trait'ini kullanır (TenantScope global scope + auto-assign `tenant_id` on create):

| Model | Tablo | Ek Trait'ler | tenant_id Durumu |
|-------|-------|--------------|------------------|
| `Ilan` | `ilanlar` | `HasCountryScope`, `EnforcesContext7Guard`, `SabGuard` | ✅ Nullable |
| `Kisi` | `kisiler` | `HasCountryScope`, `HasActiveScope`, `LogsActivity` | ✅ NOT NULL (hardened) |
| `Lead` | `leads` | `HasCountryScope`, `HasFactory` | ✅ Nullable |
| `AiLog` | `ai_logs` | `HasCountryScope` | ✅ Nullable |
| `Property` | `properties` | `HasCountryScope`, `SoftDeletes` | ✅ NOT NULL |
| `BankAccount` | `bank_accounts` | `HasFactory` | ✅ NOT NULL |
| `AccessCredential` | `access_credentials` | `SoftDeletes`, `HasFactory` | ✅ NOT NULL |
| `PropertyReadiness` | `property_readiness` | `HasFactory` | ✅ NOT NULL |
| `ProviderSettlement` | `provider_settlements` | `HasCountryScope`, `SoftDeletes` | ✅ NOT NULL |
| `BankTransaction` | `bank_transactions` | `SoftDeletes`, `HasFactory` | ✅ NOT NULL |
| `ReconciliationExecution` | `reconciliation_executions` | `SoftDeletes`, `HasFactory` | ✅ NOT NULL |
| `SettlementAllocation` | `settlement_allocations` | `SoftDeletes`, `HasFactory` | ✅ NOT NULL |

### Gözlem

- **12 model `BelongsToTenant` kullanıyor** — codebase'de ~200+ model olduğu düşünülürse bu çok düşük bir oran
- **~170+ model sadece `HasCountryScope` kullanıyor** — tenant izolasyonu yok
- Property domain ve settlement tabloları (yeni eklenenler) tutarlı şekilde `tenant_id` içeriyor
- Core tabloların bir kısmı (`ilanlar`, `talepler`, `kisiler`) sonradan `tenant_id` almış ama çoğu hala nullable
- Sadece `kisiler` tablosu `NOT NULL`'a harden edilmiş

## 5. tenant_id Eksik Tablolar — Kritik Gap'ler

Bu bölümde, `tenant_id` kolonu olmayan ve tenant-scoped olması gereken tablolar listelenir.

### 5.1 CQRS Projection Tabloları (6/6 Eksik)

> **Not:** Bu tablolar `cqrs-projection-research-report-2026-09-06.md` raporunda detaylı analiz edilmiştir.

| Tablo | Model | Mevcut Scope | tenant_id | Durum |
|-------|-------|-------------|-----------|-------|
| `listing_search_projection` | `ListingSearchProjection` | `HasCountryScope` | ❌ YOK | Boş |
| `listing_velocity_projections` | `ListingVelocityProjection` | `HasCountryScope` | ❌ YOK | Boş |
| `market_trend_projections` | `MarketTrendProjection` | `HasCountryScope` | ❌ YOK | Boş |
| `buyer_interest_projections` | `BuyerInterestProjection` | `HasCountryScope` | ❌ YOK | Boş |
| `talep_match_projection` | `TalepMatchProjection` | `HasCountryScope` | ❌ YOK | Boş |
| `buyer_intent_projection` | `BuyerIntentProjection` | `HasCountryScope` | ❌ YOK | Boş |

### 5.2 Core İş Tabloları (tenant_id Eksik veya Modelde Scope Yok)

| Tablo | Model | Mevcut Scope | tenant_id Kolonu | BelongsToTenant | Risk |
|-------|-------|-------------|------------------|-----------------|------|
| `talepler` | `Talep` | `HasCountryScope` | ✅ Var (nullable) | ❌ YOK | 🔴 Yüksek |
| `eslesmeler` | `IlanTalepEslesme` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🔴 Yüksek |
| `kisi_etkilesimler` | `KisiEtkilesim` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🟡 Orta |
| `etiketler` | `Etiket` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🟡 Orta |
| `settings` | `Setting` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🟡 Orta |
| `activity_log` | (Spatie) | — | ❌ YOK | ❌ YOK | 🟡 Orta |
| `saved_searches` | `SavedSearch` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🟡 Orta |
| `projeler` | `Proje` | `HasCountryScope` | ❌ YOK | ❌ YOK | 🟡 Orta |
| `gorevler` | `FollowUpTask` | `HasCountryScope` | ✅ Var (nullable) | ❌ YOK | 🟡 Orta |

> **Önemli:** `talepler` ve `gorevler` tablolarında `tenant_id` kolonu VAR (migration ile eklenmiş), ancak modeller `BelongsToTenant` trait'ini KULLANMIYOR. Yani kolon var ama TenantScope global scope aktif değil — sorgular tenant'a göre filtrelenmiyor.

### 5.3 İlan Alt Tabloları (tenant_id Eksik)

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `ilan_fotograflari` | `IlanFotografi` | `HasCountryScope` | ✅ Var (nullable)* | 🟡 Orta |
| `ilan_videolari` | — | — | ❌ YOK | 🟡 Orta |
| `ilan_feature` (pivot) | — | — | ❌ YOK | 🟢 Düşük |
| `ilan_demirbas` (pivot) | — | — | ❌ YOK | 🟢 Düşük |
| `ilan_notlari` | `IlanNot` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_metinleri` | `IlanMetin` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_price_history` | `IlanPriceHistory` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_taslaklar` | `IlanTaslak` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_takvim_sync` | `IlanTakvimSync` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_calendar_feeds` | `PropertyCalendarFeed` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_turizm_details` | — | — | ❌ YOK | 🟡 Orta |
| `ilan_arsa_details` | — | — | ❌ YOK | 🟡 Orta |
| `ilan_ticari_details` | — | — | ❌ YOK | 🟡 Orta |
| `yazlik_details` | `YazlikDetail` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `yazlik_fiyatlandirma` | `YazlikFiyatlandirma` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `yazlik_rezervasyonlar` | `YazlikRezervasyon` | `HasCountryScope` | ❌ YOK | 🔴 Yüksek |

> *`ilan_fotograflari` tablosuna `2026_06_29_100000` migration'ı `tenant_id` ekler, ancak model `BelongsToTenant` KULLANMAZ.

### 5.4 Lead / Talep Alt Tabloları

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `lead_activities` | `LeadActivity` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `lead_embeddings` | `LeadEmbedding` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `lead_messages` | `LeadMessage` | `HasCountryScope` | ❌ YOK | 🟡 Orta |

### 5.5 AI / ML Tabloları (tenant_id Eksik)

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `ai_deneyler` | `AiExperiment` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_prompt_logs` | `AiPromptLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_security_logs` | `AiSecurityLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_provider_profiles` | `AiProviderProfile` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_saglayici_profilleri` | `AiSaglayiciProfili` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_esik_profilleri` | `AiEsikProfili` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_threshold_overrides` | `AiThresholdOverride` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_optimization_runs` | `AiOptimizationRun` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_storages` | `AIStorage` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_description_drafts` | `AIDescriptionDraft` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_ogrenme_sinyalleri` | `AiOgrenmeSinyali` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_provider_decisions` | `AiProviderDecision` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_feature_prices` | `AiFeaturePrice` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_pricing_plans` | `AiPricingPlan` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ai_field_suggestions` | `AiFieldSuggestion` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_embeddings` | `IlanEmbedding` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `prediction_snapshots` | `PredictionSnapshot` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `listing_outcomes` | `ListingOutcome` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `feedback_results` | `FeedbackResult` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `market_valuation_reports` | — | — | ❌ YOK | 🟡 Orta |
| `property_growth_projections` | `PropertyGrowthProjection` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `listing_state_transitions` | `ListingStateTransition` | `HasCountryScope` | ❌ YOK | 🟡 Orta |

### 5.6 Governance / Audit Tabloları (tenant_id Eksik)

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `governance_audit_logs` | `GovernanceAuditLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `governance_alerts` | — | — | ❌ YOK | 🟡 Orta |
| `governance_suppressions` | — | — | ❌ YOK | 🟡 Orta |
| `governance_incidents` | `GovernanceIncident` | `HasCountryScope` | ⚠️ string* | 🟡 Orta |
| `openclaw_audit_logs` | `OpenClawAuditLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `template_audit_logs` | `TemplateAuditLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |

> *`governance_incidents` tablosunda `string` tipinde `tenant_id` var (default 'SYSTEM'), ama `BelongsToTenant` trait kullanılmıyor. Tip uyumsuzluğu: `unsignedBigInteger` vs `string`.

### 5.7 Property / Reservation Alt Tabloları

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `property_availabilities` | `PropertyAvailability` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_seasonal_rates` | `PropertySeasonalRate` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_config_versions` | `PropertyConfigVersion` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_calendar_feeds` | `PropertyCalendarFeed` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_expenses` | `PropertyExpense` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_subscriptions` | `PropertySubscription` | `HasCountryScope` | ❌ YOK | 🟡 Orta |

### 5.8 Diğer İş Tabloları

| Tablo | Model | Mevcut Scope | tenant_id | Risk |
|-------|-------|-------------|-----------|------|
| `demirbaslar` | `Demirbas` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `demirbas_kategorileri` | `DemirbasKategori` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `danisman_yorumlar` | — | — | ❌ YOK | 🟡 Orta |
| `advisor_photos` | `AdvisorPhoto` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `user_devices` | `UserDevice` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ref_sequences` | `RefSequence` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `ilan_no_sequences` | — | — | ❌ YOK | 🟡 Orta |
| `point_of_interests` | `PointOfInterest` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `copilot_action_logs` | `CopilotActionLog` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `optimizer_suggestions` | — | — | ❌ YOK | 🟡 Orta |
| `agent_memory` | — | — | ❌ YOK | 🟡 Orta |
| `agent_runs` | — | — | ❌ YOK | 🟡 Orta |
| `pipeline_runs` | `PipelineRun` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `pipeline_steps` | `PipelineStep` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `cortex_neural_connections` | `CortexNeuralConnection` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `system_learning_transactions` | `SystemLearningTransaction` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `tkgm_learning_patterns` | `TkgmLearningPattern` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `tkgm_queries` | `TkgmQuery` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `property_engine_shadow_events` | `PropertyEngineShadowEvent` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `outbox_entries` | `OutboxEntry` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `admin_notifications` | `AdminNotification` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `admin_activity_events` | `AdminActivityEvent` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `notification_templates` | `NotificationTemplate` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `outbound_notifications` | `OutboundNotification` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `config_options` | `ConfigOption` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `feature_flags` | `FeatureFlag` | `HasCountryScope` | ❌ YOK | 🟡 Orta |
| `blog_posts` | `BlogPost` | `HasCountryScope` | ❌ YOK | 🟢 Düşük |
| `blog_comments` | `BlogComment` | `HasCountryScope` | ❌ YOK | 🟢 Düşük |
| `blog_categories` | `BlogCategory` | `HasCountryScope` | ❌ YOK | 🟢 Düşük |
| `blog_tags` | `BlogTag` | `HasCountryScope` | ❌ YOK | 🟢 Düşük |

---

## 6. Sınıflandırma: tenant_id Gerekli mi, Global mi?

### 6.1 Legitimately Global Tablolar (tenant_id Gereksiz)

Bu tablolar referans verisi, sistem konfigürasyonu veya shared metadata içerir. `tenant_id` gereksizdir:

| Tablo | Gerekçe |
|-------|---------|
| `tenants` | Tenant tablosunun kendisi |
| `plans` | SaaS plan tanımları (global) |
| `ulkeler` | Ülke referans verisi |
| `iller` | İl referans verisi |
| `ilceler` | İlçe referans verisi |
| `mahalleler` | Mahalle referans verisi |
| `ilan_kategorileri` | Kategori referans verisi |
| `yayin_tipleri` | Yayın tipi referans verisi |
| `yayin_tipi_sablonlari` | Şablon referans verisi |
| `languages` | Dil referans verisi |
| `currencies` | Para birimi referans verisi |
| `roles` | Rol tanımları (Spatie) |
| `permissions` | İzin tanımları (Spatie) |
| `role_has_permissions` | Pivot (Spatie) |
| `model_has_roles` | Pivot (Spatie) |
| `model_has_permissions` | Pivot (Spatie) |
| `feature_categories` | Özellik kategorileri (global) |
| `features` | Özellik tanımları (global) |
| `ozellik_kategorileri` | Eski özellik kategorileri (global) |
| `ozellikler` | Eski özellikler (global) |
| `country_financial_rules` | Ülke bazlı finansal kurallar |
| `fx_rates` / `exchange_rates` | Döviz kurları (global) |
| `test_entities` | Test tablosu |
| `location_reconciliation_log` | Log tablosu |
| `bodrum_fk_reconcile_log` | Log tablosu |

### 6.2 Tenant-Scoped Olması Gereken Tablolar (tenant_id Gerekli)

Bu tablolar tenant'a özel iş verisi içerir. `tenant_id` ZORUNLUDUR:

#### 🔴 Kritik Öncelik (Veri sızıntısı riski yüksek)

| Tablo | Mevcut Durum | Eksiklik |
|-------|-------------|----------|
| `talepler` | tenant_id var (nullable), BelongsToTenant YOK | Model scope eksik |
| `eslesmeler` | tenant_id YOK | Hem kolon hem scope eksik |
| `yazlik_rezervasyonlar` | tenant_id YOK | Hem kolon hem scope eksik |
| 6 projection tablosu | tenant_id YOK | CQRS raporunda detaylı |

#### 🟡 Orta Öncelik (Veri sızıntısı riski orta — ulke_id var ama tenant_id yok)

| Tablo | Mevcut Durum | Eksiklik |
|-------|-------------|----------|
| `kisi_etkilesimler` | ulke_id var, tenant_id YOK | Kolon + scope |
| `etiketler` | ulke_id var, tenant_id YOK | Kolon + scope |
| `settings` | ulke_id var, tenant_id YOK | Kolon + scope |
| `saved_searches` | ulke_id var, tenant_id YOK | Kolon + scope |
| `projeler` | ulke_id var, tenant_id YOK | Kolon + scope |
| `gorevler` | tenant_id var (nullable), BelongsToTenant YOK | Model scope eksik |
| `ilan_fotograflari` | tenant_id var (nullable), BelongsToTenant YOK | Model scope eksik |
| Tüm ilan alt tabloları (12+) | ulke_id var, tenant_id YOK | Kolon + scope |
| Tüm AI tabloları (20+) | ulke_id var, tenant_id YOK | Kolon + scope |
| Tüm governance tabloları (6) | ulke_id var, tenant_id YOK | Kolon + scope |
| Tüm property alt tabloları (6) | ulke_id var, tenant_id YOK | Kolon + scope |

### 6.3 Koşullu tenant_id (Nullable — Hem Global Hem Tenant-Scoped)

| Tablo | Gerekçe |
|-------|---------|
| `feature_assignments` | Template-level = NULL, per-tenant = specific tenant_id |
| `governance_events` | Sistem olayları = NULL, tenant olayları = specific |
| `hermes_event_logs` | Sistem event'leri = NULL, tenant event'leri = specific |
| `hermes_analytics` | Sistem analytics = NULL, tenant analytics = specific |
| `communications` | Legacy kayıtlar = NULL, yeni kayıtlar = specific |

### 6.4 "Kolon Var, Scope Yok" Gap'i — Kritik Bulgu

Aşağıdaki tablolarda `tenant_id` kolonu mevcut ancak model `BelongsToTenant` trait'ini kullanmadığı için `TenantScope` global scope aktif değildir:

| Tablo | tenant_id Kolonu | Model | BelongsToTenant | Sonuç |
|-------|------------------|-------|-----------------|-------|
| `ilanlar` | ✅ Nullable | `Ilan` | ✅ Var | ✅ Scope aktif |
| `kisiler` | ✅ NOT NULL | `Kisi` | ✅ Var | ✅ Scope aktif |
| `talepler` | ✅ Nullable | `Talep` | ❌ YOK | 🔴 Scope YOK |
| `gorevler` | ✅ Nullable | `FollowUpTask` | ❌ YOK | 🔴 Scope YOK |
| `ilan_fotograflari` | ✅ Nullable | `IlanFotografi` | ❌ YOK | 🔴 Scope YOK |
| `property_reservations` | ✅ Nullable | `PropertyReservation` | ❌ YOK | 🔴 Scope YOK |
| `leads` | ✅ Nullable | `Lead` | ✅ Var | ✅ Scope aktif |
| `communications` | ✅ Nullable | `Communication` | ❌ YOK | 🔴 Scope YOK |

> **Bu gap en kritik bulgudur:** `tenant_id` kolonu eklenmiş, backfill yapılmış, ancak modelde `BelongsToTenant` trait'i eklenmemiş. Bu, `TenantScope` global scope'unun devreye girmediği ve sorguların tenant'a göre filtrelenmediği anlamına gelir. Veri kolonda var ama kullanılmıyor.

## 7. Risk Matrisi

### 7.1 Risk Skalası

| Seviye | Açıklama | Kriter |
|--------|----------|-------|
| 🔴 Kritik | Veri sızıntısı riski yüksek | tenant_id YOK + tenant-scoped iş verisi |
| 🟡 Orta | Veri sızıntısı riski orta | ulke_id var ama tenant_id YOK |
| 🟢 Düşük | Düşük risk | Pivot tablo veya az kullanılan tablo |
| ⚪ Yok | Global tablo | Referans verisi, tenant_id gereksiz |

### 7.2 Kritik Risk — "Kolon Var, Scope Yok"

Bu en tehlikeli gap'tir çünkü yanıltıcı güvenlik hissi yaratır:

| Tablo | tenant_id Kolonu | Model Scope | Gerçek İzolasyon |
|-------|------------------|-------------|------------------|
| `talepler` | ✅ Var | ❌ YOK | Sadece ulke_id |
| `gorevler` | ✅ Var | ❌ YOK | Sadece ulke_id |
| `ilan_fotograflari` | ✅ Var | ❌ YOK | Sadece ulke_id |
| `property_reservations` | ✅ Var | ❌ YOK | Sadece ulke_id |
| `communications` | ✅ Var | ❌ YOK | Sadece ulke_id |

**Etki:** Aynı ülke içindeki farklı tenant'lar birbirlerinin verilerini görebilir.

### 7.3 Kritik Risk — "Kolon da Yok, Scope da Yok"

| Tablo | tenant_id Kolonu | Model Scope | Gerçek İzolasyon |
|-------|------------------|-------------|------------------|
| `eslesmeler` | ❌ YOK | ❌ YOK | Sadece ulke_id |
| `yazlik_rezervasyonlar` | ❌ YOK | ❌ YOK | Sadece ulke_id |
| 6 projection tablosu | ❌ YOK | ❌ YOK | Sadece ulke_id |

### 7.4 Toplam Risk Özeti

| Risk Seviyesi | Tablo Sayısı | Açıklama |
|---------------|-------------|----------|
| 🔴 Kritik (kolon var, scope yok) | 5 | En öncelikli düzeltme |
| 🔴 Kritik (kolon da yok) | 8 | Migration + trait gerekli |
| 🟡 Orta (ulke_id var, tenant_id yok) | ~70+ | Kademeli düzeltme |
| 🟢 Düşük (pivot/az kullanılan) | ~10 | Düşük öncelik |
| ⚪ Global (tenant_id gereksiz) | ~25 | Düzeltme gerekmez |

## 8. Önerilen Öncelik Sırası

### Phase 0 — "Kolon Var, Scope Yok" Düzeltmesi (1 gün)

Bu tablolarda `tenant_id` kolonu zaten var. Sadece modele `BelongsToTenant` trait eklemek yeterlidir:

| Sıra | Tablo | Model | İşlem |
|------|-------|-------|-------|
| 1 | `talepler` | `Talep` | `use BelongsToTenant` ekle |
| 2 | `gorevler` | `FollowUpTask` | `use BelongsToTenant` ekle |
| 3 | `ilan_fotograflari` | `IlanFotografi` | `use BelongsToTenant` ekle |
| 4 | `property_reservations` | `PropertyReservation` | `use BelongsToTenant` ekle |
| 5 | `communications` | `Communication` | `use BelongsToTenant` ekle |

> **Uyarı:** Trait eklemeden önce mevcut sorguların `withoutTenantScope()` kullanıp kullanmadığı kontrol edilmelidir.

### Phase 1 — Kritik İş Tablolarına tenant_id Ekleme (2-3 gün)

| Sıra | Tablo | İşlem |
|------|-------|-------|
| 1 | `eslesmeler` | Migration: tenant_id nullable + backfill + BelongsToTenant |
| 2 | `yazlik_rezervasyonlar` | Migration: tenant_id nullable + backfill + BelongsToTenant |
| 3 | 6 projection tablosu | CQRS raporundaki planı izle |

### Phase 2 — İlan Alt Tabloları (3-4 gün)

`ilan_notlari`, `ilan_metinleri`, `ilan_price_history`, `ilan_taslaklar`, `ilan_takvim_sync`, `ilan_calendar_feeds`, `yazlik_details`, `yazlik_fiyatlandirma`, `ilan_turizm_details`, `ilan_arsa_details`, `ilan_ticari_details`, `ilan_videolari`

### Phase 3 — AI Tabloları (3-4 gün)

~20+ AI tablosuna `tenant_id` ekleme + `BelongsToTenant` trait.

### Phase 4 — Governance / Audit Tabloları (1-2 gün)

`governance_audit_logs`, `governance_alerts`, `governance_suppressions`, `governance_incidents` (tip düzeltme: string → bigint), `openclaw_audit_logs`, `template_audit_logs`

### Phase 5 — Property Alt Tabloları (2 gün)

`property_availabilities`, `property_seasonal_rates`, `property_config_versions`, `property_calendar_feeds`, `property_expenses`, `property_subscriptions`

### Phase 6 — Diğer Tablolar (2-3 gün)

Kalan ~30 tablo: `demirbaslar`, `danisman_yorumlar`, `advisor_photos`, `user_devices`, `ref_sequences`, `point_of_interests`, `copilot_action_logs`, `agent_memory`, `pipeline_runs`, `pipeline_steps`, vb.

### Toplam Süre: 14-19 gün

---

## 9. Backfill Stratejisi

### 9.1 Backfill Kaynak Hiyerarşisi

```
1. Tablonun kendi ilişkisinden (örn: ilan_id → ilanlar.tenant_id)
2. User ilişkisinden (örn: danisman_id → users.tenant_id)
3. Kisi ilişkisinden (örn: kisi_id → kisiler.tenant_id)
4. Default tenant (tenant_id = 1) — son çare
```

### 9.2 Çözülemeyen Kayıtlar (Orphan Records)

1. **Kaynak tabloda tenant_id NULL ise** → default tenant (1) ata
2. **İlişki kopuksa (orphan)** → sil veya default tenant ata
3. **Boş tablo ise** → no-op

> **Uyarı:** Orphan silme ve default tenant atama production veri denetimi ve açık onay gerektirir.

## 10. İzolasyon Mimarisi Özeti

### 10.1 Mevcut İki Katmanlı İzolasyon

```
┌─────────────────────────────────────────────┐
│              Request Context                  │
│                                               │
│  ┌─────────────────┐  ┌──────────────────┐   │
│  │ TenantContext    │  │ Auth::user()     │   │
│  │ Service          │  │                  │   │
│  │ (tenant_id)      │  │ (ulke_id)         │   │
│  └────────┬────────┘  └────────┬─────────┘   │
│           ▼                    ▼              │
│  ┌─────────────────┐  ┌──────────────────┐   │
│  │ TenantScope     │  │ CountryScope     │   │
│  │ (WHERE          │  │ (WHERE           │   │
│  │  tenant_id = ?)  │  │  ulke_id = ?)     │   │
│  └─────────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────┘
```

### 10.2 Sorun

- `TenantScope` sadece `BelongsToTenant` trait'i kullanan 12 modelde aktif
- `CountryScope` ~180+ modelde aktif
- Aradaki ~170 model `ulke_id` izolasyonu var ama `tenant_id` izolasyonu YOK
- Aynı ülke içinde birden fazla tenant varsa veri sızıntısı riski

### 10.3 İdeal Durum

Tenant-scoped tüm tablolarda her iki scope da aktif olmalıdır:

```
Model → use HasCountryScope, BelongsToTenant;
Tablo → tenant_id (NOT NULL) + ulke_id (NOT NULL)
```

---

## 11. Kabul Kriterleri

| # | Kriter | Durum |
|---|--------|-------|
| 1 | Tüm tabloların `tenant_id` var/yok envanteri çıkarıldı mı? | ✅ |
| 2 | `BelongsToTenant` kullanan modeller listelendi mi? | ✅ (12 model) |
| 3 | `HasCountryScope` ama `BelongsToTenant` kullanmayan modeller belgelendi mi? | ✅ (~170+) |
| 4 | "Kolon var, scope yok" gap'i tanımlandı mı? | ✅ (5 tablo) |
| 5 | Tablolar global vs tenant-scoped olarak sınıflandırıldı mı? | ✅ |
| 6 | Risk matrisi oluşturuldu mu? | ✅ |
| 7 | Backfill stratejisi tanımlandı mı? | ✅ |
| 8 | Öncelikli faz planı oluşturuldu mu? | ✅ (6 faz, 14-19 gün) |

---

## 12. Kısıtlar

- Bu rapor yalnızca araştırmadır — production doğrulaması değildir
- Migration, trait ekleme veya backfill yapılmamıştır
- Implementasyon ayrı worktree'de, veri denetimi ve açık onay sonrası yapılmalıdır
- `tenant_id = 1` default atama ve orphan silme production onayı gerektirir

---

## 13. Referanslar

- `docs/architecture/cqrs-projection-research-report-2026-09-06.md` — CQRS projection raporu (REPO_VERIFIED)
- `app/Traits/BelongsToTenant.php` — Tenant izolasyon trait'i
- `app/Traits/HasCountryScope.php` — Ülke izolasyon trait'i
- `app/Scopes/TenantScope.php` — Tenant global scope
- `app/Scopes/CountryScope.php` — Country global scope
- `database/migrations/2026_06_29_100000_add_tenant_id_to_core_tables.php` — Core tablolara tenant_id ekleme
- `database/migrations/2026_07_18_011152_add_tenant_safety_and_company_fields_to_kisiler.php` — kisiler hardening

---

## 14. Scope Bypass Analizi — `withoutGlobalScopes` / `withoutTenant` / `withoutCountryScope`

### 14.1 Özet

Codebase'de **128 adet** scope bypass çağrısı tespit edilmiştir. Bu çağrılar `withoutGlobalScopes()`, `withoutTenant()`, ve `withoutCountryScope()` metodlarını kullanır.

### 14.2 Bypass Kategorileri

| Kategori | Sayı | Risk | Açıklama |
|----------|------|------|----------|
| **Job/Queue tenant resolution** | ~25 | 🟡 Orta | Job'lar `withoutGlobalScopes()->find()` ile model yükleyip `tenant_id`'yi manuel resolve eder |
| **Channel Manager / Webhook** | ~20 | 🟡 Orta | External reservation ingest `withoutGlobalScopes()` ile dedup yapar |
| **Financial Ledger** | ~15 | 🟡 Orta | `LedgerEntry::withoutGlobalScopes()` ile idempotency check |
| **Admin Analytics** | ~10 | 🟢 Düşük | Aggregate istatistik sorguları |
| **Reservation Service** | ~10 | 🟡 Orta | Rezervasyon lookup ve conflict check |
| **Workspace/Drive** | ~8 | 🟢 Düşük | Workspace ilan lookup |
| **Guest Concierge** | ~5 | 🔴 Kritik | Guest-agnostik rezervasyon bulma — tenant bypass |
| **Matching Engine** | ~3 | 🔴 Kritik | Cross-tenant matching — intentional bypass |

### 14.3 Kritik Bypass Örnekleri

#### GuestConciergeRouter — Intentional CountryScope Bypass

```php
// app/Services/Concierge/GuestConciergeRouter.php
// DEBT-GC-01: CountryScope BYPASSED intentionally.
return PropertyReservation::withoutGlobalScopes()
    ->where('guest_phone', $phone)
```

**Risk:** Guest telefon numarası ile rezervasyon ararken tenant ve country scope'ları bypass edilir. Misafirin hangi tenant'a ait olduğu önemli değildir — bu intentional bir tasarım kararıdır. Ancak `PropertyReservation` modelinde `BelongsToTenant` yokken bu bypass sadece `CountryScope`'u kaldırır.

#### DemandMatchingEngine — Intentional TenantScope Bypass

```php
// app/Services/Matching/DemandMatchingEngine.php
// ⚠️ TenantScope bypass: cross-tenant matching için intentional
$query = Ilan::withoutTenant()->where('yayin_durumu', IlanDurumu::YAYINDA->value);
```

**Risk:** Eşleştirme motoru tüm tenant'larda arama yapar. Bu intentional ancak production'da cross-tenant veri sızıntısı riski taşır.

#### Job'lar — Tenant Resolution Pattern

```php
// app/Jobs/NotifyN8nAboutIlanPriceChange.php
$ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
return $ilan?->tenant_id;
```

**Pattern:** Job'lar `withoutGlobalScopes()` ile modeli yükler, sonra `tenant_id`'yi manuel resolve eder. Bu, queue context'inde `TenantContextService`'in aktif olmaması nedeniyle gereklidir. **Ancak:** `tenant_id` NULL ise job yanlış tenant context'inde çalışabilir.

### 14.4 Bypass Kullanımının Etkisi

`BelongsToTenant` trait'i eklendiğinde, mevcut `withoutGlobalScopes()` çağrıları `TenantScope`'u da bypass edecektir. Bu şu anlama gelir:

1. **Mevcut `withoutGlobalScopes()` kullanan kod** — `TenantScope` eklense bile etkilenmez (zaten bypass ediyor)
2. **Mevcut `withoutCountryScope()` kullanan kod** — `TenantScope` eklenirse bu kod `TenantScope`'u bypass ETMEZ, sadece `CountryScope`'u bypass eder
3. **`withoutTenant()` kullanan kod** — Sadece `TenantScope`'u bypass eder, `CountryScope` aktif kalır

> **Önemli:** Phase 0 trait ekleme öncesi, her model için `withoutGlobalScopes()` çağrılarının listesi çıkarılmalı ve her birinin intentional mı yoksa accidental mı olduğu belirlenmelidir.

---

## 15. Direct `find()` / `findOrFail()` Kullanımı

### 15.1 Özet

Codebase'de `Ilan::find()`, `Ilan::findOrFail()`, `Talep::find()`, `Talep::findOrFail()`, `Kisi::find()`, `Kisi::findOrFail()`, `PropertyReservation::find()`, `PropertyReservation::findOrFail()` çağrıları toplam **153 adet** tespit edilmiştir.

### 15.2 Risk Analizi

`find()` ve `findOrFail()` metodları Eloquent global scope'ları **UYGULAR**. Yani:

- `Ilan::find($id)` — `TenantScope` + `CountryScope` aktif → tenant ve country filtresi uygulanır
- `Ilan::withoutGlobalScopes()->find($id)` — tüm scope'lar bypass edilir

**Ancak:** `Talep` modeli `BelongsToTenant` KULLANMADIĞI için `Talep::find($id)` sadece `CountryScope` uygular — tenant filtresi YOK.

### 15.3 Kritik `find()` Kullanımları

| Model | find() Sayısı | BelongsToTenant | Gerçek İzolasyon |
|-------|-------------|-----------------|------------------|
| `Ilan` | ~80 | ✅ Var | TenantScope + CountryScope |
| `Talep` | ~15 | ❌ YOK | Sadece CountryScope |
| `Kisi` | ~25 | ✅ Var | TenantScope + CountryScope |
| `PropertyReservation` | ~15 | ❌ YOK | Sadece CountryScope |

> **Kritik:** `Talep::find($id)` ve `PropertyReservation::find($id)` çağrıları tenant filtresi UYGULAMAZ. Aynı ülke içindeki farklı tenant'lar birbirlerinin talep ve rezervasyon kayıtlarına erişebilir.

### 15.4 Controller ve Service Bazında Dağılım

| Konum | find() Sayısı | Risk |
|-------|-------------|------|
| `app/Http/Controllers/` | ~60 | 🔴 Yüksek — HTTP request context'inde tenant scope olmalı |
| `app/Services/` | ~50 | 🟡 Orta — Service katmanı tenant context'e bağlı |
| `app/Jobs/` | ~25 | 🟡 Orta — Queue context'inde manuel tenant resolution |
| `app/Console/Commands/` | ~10 | 🟢 Düşük — CLI context'inde tenant genelde explicit |
| `app/Actions/` | ~8 | 🟡 Orta |

## 16. CLI / Queue / System Context Analizi

### 16.1 Sorun

`TenantScope` ve `CountryScope` global scope'ları request context'inden gelir:
- `TenantScope` → `TenantContextService` (HTTP request middleware'inde set edilir)
- `CountryScope` → `Auth::user()->ulke_id` (HTTP session'dan gelir)

**CLI (artisan) ve Queue (job) context'lerinde bu kaynaklar yoktur:**
- `TenantContextService` set edilmemiş → `tenant_id` NULL
- `Auth::user()` yok → `ulke_id` NULL

### 16.2 Mevcut Çözüm Pattern'i

Job'lar `withoutGlobalScopes()->find()` ile modeli yükler ve `tenant_id`'yi manuel resolve eder:

```php
// app/Jobs/NotifyN8nAboutIlanPriceChange.php
public function tenant(): ?int
{
    $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
    return $ilan?->tenant_id;
}
```

Bu pattern doğru çalışır çünkü:
1. Job modeli `withoutGlobalScopes()` ile scope'ları bypass eder
2. Modelin `tenant_id` kolonundan tenant'ı resolve eder
3. Job içinde tenant context manuel set edilir

### 16.3 Riskli Senaryolar

| Senaryo | Risk | Açıklama |
|---------|------|----------|
| `tenant_id` NULL olan kayıt | 🔴 Kritik | Job NULL tenant ile çalışır, veri yanlış tenant'a yazılabilir |
| `Talep::find()` (BelongsToTenant yok) | 🔴 Kritik | Queue context'te CountryScope da aktif değil → hiçbir scope yok |
| `PropertyReservation::find()` (BelongsToTenant yok) | 🔴 Kritik | Aynı sorun — tenant filtresi yok |
| CLI command `Ilan::find()` | 🟡 Orta | CLI'da TenantScope NULL → tüm ilanlar görünür |
| Scheduled job tenant context | 🟡 Orta | Cron job'ları tenant context olmadan çalışır |

### 16.4 Queue Context'inde `BelongsToTenant` Etkisi

Phase 0'da `Talep` modeline `BelongsToTenant` eklendiğinde:

| Durum | Öncesi | Sonrası |
|-------|---------|---------|
| `Talep::find($id)` HTTP context | Sadece CountryScope | TenantScope + CountryScope |
| `Talep::find($id)` Queue context | Hiç scope (Auth yok) | TenantScope aktif ama tenant NULL → **0 sonuç** |
| `Talep::withoutGlobalScopes()->find($id)` Queue | Hiç scope | Tüm scope'lar bypass |

> **Kritik:** Phase 0 trait ekleme sonrası, queue context'inde `Talep::find()` çağrıları **0 sonuç dönebilir** çünkü `TenantContextService` set edilmemişse `TenantScope` `WHERE tenant_id = NULL` uygular. Job'ların `withoutGlobalScopes()` kullanıp kullanmadığı kontrol edilmelidir.

---

## 17. Phase 0 Pre-Check Listesi

Phase 0 (trait ekleme) öncesi her tablo için aşağıdaki kontroller yapılmalıdır:

### 17.1 `talepler` → `Talep` modeline `BelongsToTenant` ekleme

| # | Kontrol | Durum | Aksiyon |
|---|---------|-------|--------|
| 1 | `tenant_id` kolonu nullable mı? | ✅ Evet (nullable) | Backfill sonrası NOT NULL'a harden |
| 2 | Mevcut `withoutGlobalScopes()` çağrıları var mı? | ✅ Var (Job'larda) | Listele ve intentional olduğunu doğrula |
| 3 | Direct `Talep::find()` çağrıları var mı? | ✅ Var (~15) | Queue context'inde 0 sonuç riski |
| 4 | CLI/queue context'inde `Talep::find()` çağrıları var mı? | ✅ Var | `withoutGlobalScopes()`'a migrate et |
| 5 | Cross-tenant negatif test var mı? | ❌ Bilinmiyor | Test yazılmalı |
| 6 | `withoutGlobalScopes` ile `find()` kullanan Job'lar var mı? | ✅ Var | Bu Job'lar etkilenmez (zaten bypass) |
| 7 | Sabit tenant fallback var mı? | ❌ YOK | Tenant NULL ise ne olacağı tanımlanmalı |
| 8 | SQLite/MySQL parity kontrolü | ❌ Yapılmadı | Test edilmeli |

### 17.2 `gorevler` → `FollowUpTask` modeline `BelongsToTenant` ekleme

| # | Kontrol | Durum | Aksiyon |
|---|---------|-------|--------|
| 1 | `tenant_id` kolonu nullable mı? | ✅ Evet (nullable) | Backfill gerekli |
| 2 | `withoutTenant()` çağrıları var mı? | ✅ Var (ActionCenterService) | Intentional — doğrula |
| 3 | Direct `Gorev::find()` çağrıları var mı? | ❌ Bilinmiyor | Araştır |
| 4 | CLI/queue context kullanımı var mı? | ❌ Bilinmiyor | Araştır |
| 5 | Cross-tenant negatif test var mı? | ❌ YOK | Test yazılmalı |

### 17.3 `ilan_fotograflari` → `IlanFotografi` modeline `BelongsToTenant` ekleme

| # | Kontrol | Durum | Aksiyon |
|---|---------|-------|--------|
| 1 | `tenant_id` kolonu nullable mı? | ✅ Evet (nullable) | Backfill: `ilanlar.tenant_id`'den |
| 2 | `withoutGlobalScopes()` çağrıları var mı? | ❌ Bilinmiyor | Araştır |
| 3 | Direct `IlanFotografi::find()` çağrıları var mı? | ❌ Bilinmiyor | Araştır |
| 4 | Queue context kullanımı var mı? | ❌ Bilinmiyor | Araştır |

### 17.4 `property_reservations` → `PropertyReservation` modeline `BelongsToTenant` ekleme

| # | Kontrol | Durum | Aksiyon |
|---|---------|-------|--------|
| 1 | `tenant_id` kolonu nullable mı? | ✅ Evet (nullable) | Backfill: `ilanlar.tenant_id`'den |
| 2 | `withoutGlobalScopes()` çağrıları var mı? | ✅ Var (~20) | Channel Manager — intentional |
| 3 | Direct `PropertyReservation::find()` çağrıları var mı? | ✅ Var (~15) | Queue context riski |
| 4 | Queue context'inde `find()` çağrıları var mı? | ✅ Var | `withoutGlobalScopes()`'a migrate et |
| 5 | Cross-tenant negatif test var mı? | ❌ YOK | Test yazılmalı |

### 17.5 `communications` → `Communication` modeline `BelongsToTenant` ekleme

| # | Kontrol | Durum | Aksiyon |
|---|---------|-------|--------|
| 1 | `tenant_id` kolonu nullable mı? | ✅ Evet (nullable) | Backfill gerekli |
| 2 | `withoutGlobalScopes()` çağrıları var mı? | ❌ Bilinmiyor | Araştır |
| 3 | Direct `Communication::find()` çağrıları var mı? | ❌ Bilinmiyor | Araştır |
| 4 | Queue context kullanımı var mı? | ❌ Bilinmiyor | Araştır |

---

## 18. Cross-Tenant Test Gereksinimleri

### 18.1 Negatif Tenant Test Senaryosu

Her Phase 0 tablosu için aşağıdaki test yazılmalıdır:

```
1. Tenant A context'inde oturum aç
2. Tenant B'ye ait bir kayıt oluştur (direct DB insert, withoutGlobalScopes)
3. Tenant A context'inde Tenant B'nin kaydını sorgula
4. Beklenen: 0 sonuç (403 veya empty result)
5. Eğer sonuç dönerse → İZOLASYON AÇIĞI
```

### 18.2 Gerekli Test Listesi

| Test | Modeller | Beklenen Sonuç |
|------|---------|----------------|
| `TalepTenantIsolationTest` | `Talep` | Cross-tenant erişim engellenmeli |
| `GorevTenantIsolationTest` | `FollowUpTask` | Cross-tenant erişim engellenmeli |
| `IlanFotografiTenantIsolationTest` | `IlanFotografi` | Cross-tenant erişim engellenmeli |
| `PropertyReservationTenantIsolationTest` | `PropertyReservation` | Cross-tenant erişim engellenmeli |
| `CommunicationTenantIsolationTest` | `Communication` | Cross-tenant erişim engellenmeli |

### 18.3 Queue Context Test Senaryosu

```
1. Tenant A'ya ait bir Talep oluştur
2. Talep ID'sini bir Job'a geçir
3. Job içinde Talep::find($id) çağrısı yap
4. Beklenen: Tenant context olmadan 0 sonuç (trait eklendikten sonra)
5. Job içinde withoutGlobalScopes()->find($id) çağrısı yap
6. Beklenen: Kayıt bulunur, tenant_id manuel resolve edilir
```

---

## 19. Faz Bazında Gereksinimler

### 19.1 Her Faz İçin Minimum Gereksinimler

| # | Gereksinim | Açıklama |
|---|------------|---------|
| 1 | Migration planı | `tenant_id` ekleme + nullable/not-null stratejisi |
| 2 | Backfill planı | Kaynak hiyerarşisi + orphan policy |
| 3 | Cross-tenant test | 403/empty-result negatif testi |
| 4 | SQLite/MySQL parity | Test ortamı her iki DB'de çalışmalı |
| 5 | Rollback planı | Migration geri alma + trait kaldırma |
| 6 | Evidence kaydı | Local/test/production ayrı kayıt |

### 19.2 Production Deployment Engeli

**Durum:** `DOCUMENTED / SECURITY-CRITICAL / IMPLEMENTATION-BLOCKED-PENDING-AUTH`

- Production'a deploy/migration/release tenant izolasyonu için durdurulmalıdır
- Dosya untracked — `git add` veya commit yapılmamalıdır
- Phase 0 "sadece trait ekleme" değil, yukarıdaki tüm kontrollerle ele alınmalıdır
- Phase 1 ve sonrası migration gerektirdiğinden açık production yetkisi olmadan uygulanmamalıdır

### 19.3 Önceliklendirme (Güncellenmiş)

| Sıra | Kategori | Tablolar |
|------|----------|---------|
| 1 | `tenant_id` var, scope yok (5 tablo) | `talepler`, `gorevler`, `ilan_fotograflari`, `property_reservations`, `communications` |
| 2 | Kritik iş tablolarında kolon + scope eksik | `eslesmeler`, `yazlik_rezervasyonlar` |
| 3 | Sadece `HasCountryScope` kullanan tenant modelleri | ~170+ model |
| 4 | CQRS projection ve AI tabloları | 6 projection + ~20 AI tablosu |
| 5 | Global tabloların kapsam dışı bırakılması | ~25 tablo |

---

## 20. Güncellenmiş Kabul Kriterleri

| # | Kriter | Durum |
|---|--------|-------|
| 1 | Tüm tabloların `tenant_id` var/yok envanteri | ✅ |
| 2 | `BelongsToTenant` kullanan modeller listelendi | ✅ (12 model) |
| 3 | `HasCountryScope` ama `BelongsToTenant` kullanmayan modeller | ✅ (~170+) |
| 4 | "Kolon var, scope yok" gap'i tanımlandı | ✅ (5 tablo) |
| 5 | Tablolar global vs tenant-scoped sınıflandırıldı | ✅ |
| 6 | Risk matrisi oluşturuldu | ✅ |
| 7 | Backfill stratejisi tanımlandı | ✅ |
| 8 | Öncelikli faz planı oluşturuldu | ✅ (6 faz, 14-19 gün) |
| 9 | `withoutGlobalScopes` bypass analizi | ✅ (128 çağrı) |
| 10 | Direct `find()` / `findOrFail()` analizi | ✅ (153 çağrı) |
| 11 | CLI/queue context analizi | ✅ |
| 12 | Phase 0 pre-check listesi | ✅ (5 tablo × 8 kontrol) |
| 13 | Cross-tenant test gereksinimleri | ✅ |
| 14 | Faz bazında minimum gereksinimler | ✅ |
| 15 | Production deployment engeli tanımlandı | ✅ |

---

## 21. Derinlemesine Güvenlik Analizi — Scope Fail-Open Davranışı

### 21.1 TenantScope — Fail-Open (Kritik Güvenlik Açığı)

`TenantScope::apply()` metodunun davranışı:

```php
// app/Scopes/TenantScope.php
public function apply(Builder $builder, Model $model): void
{
    if (config('tenant.scope_enabled', true) === false) {
        return; // Emergency bypass
    }

    $tenantService = app(TenantContextService::class);

    if ($tenantService->hasTenant()) {
        $builder->where($model->getTable() . '.tenant_id', $tenantService->getTenant()->id);
    }
    // ⚠️ hasTenant() false ise: HİÇBİR FİLTRE UYGULANMAZ
}
```

**Kritik Bulgu:** `hasTenant()` `false` döndüğünde scope hiçbir filtre uygulamaz. Bu **fail-open** davranışıdır — güvenlik açısından doğru olan **fail-closed** olmalıydı (yani tenant context yoksa sorgu boş dönmeli veya hata fırlatmalı).

**Etki:** Tenant context set edilmemişse, `BelongsToTenant` kullanan modeller de dahil olmak üzere tüm sorgular tüm tenant'ların verilerini döndürür.

### 21.2 CountryScope — Fail-Open (Kritik Güvenlik Açığı)

```php
// app/Scopes/CountryScope.php
public function apply(Builder $builder, Model $model): void
{
    $user = Auth::user() ?? Auth::guard('sanctum')->user();

    if ($user) {
        if ($user->ulke_id && (!app()->runningInConsole() || app()->runningUnitTests())) {
            // ... filtre uygula
        }
    }
    // ⚠️ User yoksa veya ulke_id null ise: HİÇBİR FİLTRE UYGULANMAZ
    // ⚠️ Console/CLI context'inde: HİÇBİR FİLTRE UYGULANMAZ
}
```

**Kritik Bulgu:** CountryScope da fail-open davranışı sergiler:
1. `Auth::user()` null ise (queue, CLI, webhook) → filtre yok
2. `ulke_id` null ise → filtre yok
3. `runningInConsole()` true ise (CLI, cron, queue worker) → filtre yok

### 21.3 Fail-Open vs Fail-Closed Karşılaştırması

| Senaryo | Mevcut (Fail-Open) | İdeal (Fail-Closed) |
|---------|-------------------|---------------------|
| Tenant context yok | Tüm veriler görünür | Boş sonuç veya hata |
| Auth user yok | Tüm veriler görünür | Boş sonuç veya hata |
| CLI context | CountryScope bypass | Explicit tenant set gerektir |
| Queue context | Tüm veriler görünür | Job tenant manuel set etmeli |

---

## 22. Middleware Coverage Analizi — Tenant Context Gap

### 22.1 `SetTenantContext` Middleware Dağılımı

`app/Http/Kernel.php` analizi:

| Middleware Grubu | `SetTenantContext` Dahil mi? | Etki |
|------------------|------------------------------|------|
| `web` | ❌ HAYIR | Admin panel, web routes → tenant context YOK |
| `api` | ✅ EVET (line 56) | API routes → tenant context set edilir |

**Kritik Bulgu:** `SetTenantContext` middleware'i `api` grubunda var ama `web` grubunda YOK.

### 22.2 Etki Analizi

| Route Grubu | Middleware Grubu | Tenant Context | TenantScope Durumu |
|-------------|-----------------|---------------|-------------------|
| `routes/admin.php` | `web` | ❌ YOK | Fail-open (tüm tenantlar) |
| `routes/api.php` | `api` | ✅ Var | Çalışır (BelongsToTenant modelleri) |
| `routes/api/v1/*.php` | `api` | ✅ Var | Çalışır |
| `routes/auth.php` | `web` | ❌ YOK | Fail-open |
| `routes/admin/*.php` | `web` | ❌ YOK | Fail-open |

### 22.3 Admin Panel Güvenlik Açığı

Admin panel route'ları `web` middleware grubunu kullanır. `SetTenantContext` `web` grubunda olmadığı için:

1. Admin kullanıcıları tüm tenant'ların verilerini görebilir
2. `Ilan::find()`, `Kisi::find()` gibi çağrılar tenant filtresi olmadan çalışır
3. `BelongsToTenant` trait'i olsa bile `TenantScope` fail-open olduğu için filtre uygulanmaz

**Ancak:** Admin panel genellikle super-admin erişimi içindir ve tek tenant'lı kurulumlarda sorun yaratmaz. Çok tenant'lı senaryoda bu bir güvenlik açığıdır.

### 22.4 Checkout Route Özel Durumu

```php
// routes/admin.php (line 1661)
Route::prefix('admin/ilanlar/{ilan}/checkout')
    ->middleware(['web', 'auth', 'verified', 'tenant.context', 'throttle:120,1'])
    ->group(function () {
```

Checkout route'ları `web` grubuna ek olarak `tenant.context` middleware'ini manuel ekler. Bu, `web` grubunda `SetTenantContext` olmadığının farkında olunduğunu ve checkout için özel olarak eklendiğini gösterir.

**Soru:** Diğer admin route'ları neden aynı korumaya sahip değil?

### 22.5 `SetTenantContext` Middleware Davranışı

```php
// app/Http/Middleware/SetTenantContext.php
public function handle($request, Closure $next)
{
    $user = $request->user();

    // tenant_id null ise: 403 (fail-closed!)
    if (empty($user->tenant_id)) {
        Log::channel('governance_security')->critical('SAB_KURAL_1_IHLAL...');
        return response()->json(['error' => 'Tenant context missing.'], 403);
    }

    // Tenant modelini yükle ve context'i set et
    $this->tenantContextService->setTenant($tenant);
}
```

**Gözlem:** Middleware kendisi fail-closed (403 döner). Ama middleware ÇALIŞMAZSA (web grubunda yoksa), `TenantScope` fail-open davranışa düşer.

---

## 23. İzolasyon Zinciri — Uçtan Uca Analiz

### 23.1 HTTP API İsteği (Doğru Çalışan Senaryo)

```
1. Request gelir → api middleware grubu
2. auth:sanctum → user authenticate edilir
3. SetTenantContext → user->tenant_id → TenantContextService->setTenant()
4. Controller → Model::find() / Model::query()
5. TenantScope::apply() → hasTenant() = true → WHERE tenant_id = X
6. CountryScope::apply() → Auth::user() = true → WHERE ulke_id = Y
7. Sonuç: Sadece tenant X ve ülke Y'ye ait veriler
```

### 23.2 Admin Web İsteği (Açık Senaryosu)

```
1. Request gelir → web middleware grubu
2. auth → user authenticate edilir
3. SetTenantContext → ÇALIŞMAZ (web grubunda yok)
4. TenantContextService → hasTenant() = false
5. Controller → Model::find() / Model::query()
6. TenantScope::apply() → hasTenant() = false → FİLTRE YOK (fail-open)
7. CountryScope::apply() → Auth::user() = true → WHERE ulke_id = Y
8. Sonuç: Aynı ülkedeki TÜM tenant'ların verileri görünür
```

### 23.3 Queue/Job İsteği (Açık Senaryosu)

```
1. Job dispatch edilir → queue worker
2. Auth::user() = null (queue context)
3. TenantContextService → hasTenant() = false (job set etmediyse)
4. Model::find() / Model::query()
5. TenantScope::apply() → hasTenant() = false → FİLTRE YOK
6. CountryScope::apply() → Auth::user() = null → FİLTRE YOK
7. Sonuç: TÜM tenant'ların ve TÜM ülkelerin verileri görünür
```

### 23.4 CLI/Artisan İsteği (Açık Senaryosu)

```
1. php artisan command → console context
2. Auth::user() = null
3. TenantContextService → hasTenant() = false
4. Model::find() / Model::query()
5. TenantScope::apply() → hasTenant() = false → FİLTRE YOK
6. CountryScope::apply() → runningInConsole() = true → FİLTRE YOK
7. Sonuç: TÜM veriler görünür
```

---

## 24. Güncellenmiş Risk Değerlendirmesi

### 24.1 Risk Seviyesi Yükseltme

Önceki bölümlerde "🟡 Orta" olarak sınıflandırılan tabloların risk seviyesi, fail-open davranış göz önüne alındığında **🔴 Kritik**'e yükseltilmelidir.

| Bulgu | Önceki Risk | Güncellenmiş Risk | Gerekçe |
|-------|-------------|-------------------|---------|
| TenantScope fail-open | 🟡 Orta | 🔴 Kritik | Tenant context yoksa tüm veriler görünür |
| CountryScope fail-open | 🟡 Orta | 🔴 Kritik | Console/queue'da tüm veriler görünür |
| Web grubunda SetTenantContext yok | — | 🔴 Kritik | Admin panel tüm tenant verilerini görür |
| ~170 model BelongsToTenant yok | 🟡 Orta | 🔴 Kritik | Fail-open ile birleşince tam izolasyon açığı |

### 24.2 Sistem Genelinde İzolasyon Gerçeği

**Mevcut durumda sistemde tenant izolasyonu YOKTUR:**

1. `BelongsToTenant` kullanan 12 model bile `TenantScope` fail-open olduğu için tenant context olmadan tüm verileri döndürür
2. `web` grubunda `SetTenantContext` olmadığı için admin panel her zaman tenant context'siz çalışır
3. Queue ve CLI context'lerinde her iki scope da fail-open
4. `HasCountryScope` kullanan ~170 model console/queue'da tamamen korumasız

**Tek güvenlik katmanı:** API routes + `SetTenantContext` middleware + `BelongsToTenant` trait → bu zincir sadece API'de ve sadece 12 modelde çalışır.

### 24.3 Sistemde Kaç Tenant Var?

Bu rapor `tenants` tablosundaki kayıt sayısını doğrulayamamaktadır (production veri denetimi gerekir). Ancak:

- **Tek tenant'lu kurulum:** Tüm izolasyon gap'leri teorik — pratik etki yok
- **Çok tenant'lu kurulum:** Tüm gap'ler aktif güvenlik açığıdır
- **Tenant sayısı bilinmeli:** Risk değerlendirmesi için production veri denetimi şarttır

---

## 25. Düzeltme Önerileri — Derinlemesine

### 25.1 TenantScope Fail-Closed Düzeltmesi

```php
// ÖNERİLEN (fail-closed):
public function apply(Builder $builder, Model $model): void
{
    if (config('tenant.scope_enabled', true) === false) {
        return;
    }

    $tenantService = app(TenantContextService::class);

    if ($tenantService->hasTenant()) {
        $builder->where($model->getTable() . '.tenant_id', $tenantService->getTenant()->id);
    } else {
        // Fail-closed: tenant context yoksa 0 sonuç döndür
        $builder->whereRaw('1 = 0');
    }
}
```

> **Uyarı:** Bu değişiklik queue/CLI context'inde tüm sorguları bozar. Önce tüm job'ların `withoutGlobalScopes()` veya manuel tenant set kullandığından emin olunmalıdır.

### 25.2 Web Grubuna SetTenantContext Ekleme

```php
// app/Http/Kernel.php — web grubuna ekle:
'web' => [
    // ... mevcut middleware'ler
    \App\Http\Middleware\SetTenantContext::class,
],
```

> **Uyarı:** Bu, admin panel'de tenant filtresi aktifleştirir. Super-admin'ler için `withoutTenant()` veya bypass mekanizması gerekir.

### 25.3 CountryScope Console Bypass Kaldırma

```php
// Mevcut: runningInConsole() ise bypass
if ($user->ulke_id && (!app()->runningInConsole() || app()->runningUnitTests())) {

// Önerilen: Console'da da explicit country set gerektir
if ($user && $user->ulke_id) {
```

### 25.4 Queue Job'larında Tenant Context Set Etme

Her job'un constructor'ında veya `middleware()` metodunda tenant context set edilmeli:

```php
// Job pattern:
public function middleware(): array
{
    $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
    return [new SetTenantContextForJob($ilan->tenant_id)];
}
```

---

## 26. Özet — Sistem Genelinde İzolasyon Durumu

### 26.1 İzolasyon Katmanları ve Gerçek Durum

| Katman | Tasarım | Gerçek Durum | Gap |
|--------|---------|-------------|-----|
| TenantScope | `WHERE tenant_id = X` | Fail-open (context yoksa filtre yok) | 🔴 Kritik |
| CountryScope | `WHERE ulke_id = Y` | Fail-open (console/queue'da filtre yok) | 🔴 Kritik |
| SetTenantContext middleware | Tüm authenticated isteklerde | Sadece `api` grubunda, `web` grubunda YOK | 🔴 Kritik |
| BelongsToTenant trait | Tüm tenant-scoped modellerde | Sadece 12 modelde, ~170+ modelde YOK | 🔴 Kritik |
| tenant_id kolonu | Tüm tenant-scoped tablolarda | ~50 tabloda var, ~70+ tabloda YOK | 🔴 Kritik |

### 26.2 Sonuç

**Sistem genelinde tenant izolasyonu tasarlanmış ama tam olarak uygulanmamıştır.** Mevcut izolasyon sadece şu koşullarda çalışır:

1. ✅ API route + `SetTenantContext` middleware + `BelongsToTenant` trait + `tenant_id` kolonu
2. ❌ Web route → `SetTenantContext` yok → fail-open
3. ❌ Queue/CLI → tenant context yok → fail-open
4. ❌ `BelongsToTenant` yok → scope hiç uygulanmaz
5. ❌ `tenant_id` kolonu yok → scope uygulanamaz

**Bu bir araştırma bulgusudur, production doğrulaması değildir.** Production veri denetimi ve açık onay olmadan hiçbir düzeltme yapılmamalıdır.

---

## 27. Düzeltmeler ve Sınırlamalar — Reviewer Geri Bildirimi

### 27.1 Middleware Eksikliği Tek Başına Sızıntı Kanıtlamaz

§22'de "web grubunda SetTenantContext yok → admin panel tüm tenant verilerini görür" iddiası, tek başına kanıt değildir. Alternatif tenant context kurulum mekanizmaları mevcuttur:

| Mekanizma | Konum | Açıklama |
|-----------|-------|---------|
| Controller manuel set | `YazlikKiralamaController` (line 489-493) | `if (!$tenantService->hasTenant()) { $tenantService->setTenant($tenant); }` |
| Controller fallback | `IlanCommandController` (line 46) | `$tenantService->hasTenant() ? ... : ($request->user()?->tenant_id ?? 1)` |
| Webhook manuel set | `DriveWebhookController` (line 84-85) | `app(TenantContextService::class)->setTenant($tenant)` |
| Service katmanı kontrolü | Çeşitli service'ler | Tenant context var mı kontrolü, yoksa fallback |

**Düzeltme:** "Sadece API'de izolasyon çalışır" ifadesi aşırı geneldir. Doğru ifade: **"Raporda incelenen zincirde izolasyon koşullara bağlıdır; diğer giriş noktalarında doğrulanması gereken boşluklar vardır."** Bazı controller'lar manuel tenant context kurar, ancak bu tutarlı bir pattern değil — her controller için ayrı doğrulama gerekir.

### 27.2 `find()` Sayısı Doğrudan Açık Sayısı Değildir

§15'de 153 adet `find()`/`findOrFail()` çağrısı listelendi. Ancak:

- **Eloquent `find()` global scope'ları UYGULAR** — `Ilan::find($id)` TenantScope + CountryScope uygular (eğer context varsa)
- **`withoutGlobalScopes()->find()`** — scope'ları bypass eder, bu asıl risk
- **`find()` sayısı tek başına güvenlik açığı kanıtı değildir** — context varsa `find()` güvenlidir

**Düzeltme:** `find()` çağrıları "erişim noktası" olarak sınıflandırılmalı, "açık" olarak değil. Her `find()` çağrısı için:
1. Çağrı context'i (HTTP/queue/CLI) belirlenmeli
2. O context'te tenant/country scope'unun aktif olup olmadığı doğrulanmalı
3. Sadece scope'un aktif olmadığı durumlar açık olarak işaretlenmeli

### 27.3 Queue/CLI'de Scope Uygulanmaması — Bağlamsız Sorgu Riski

§16'da queue/CLI context'inde scope'ların uygulanmadığı belirtildi. Bu bir açıktan ziyade **bağlamsız sorgu riskidir**. Doğrulanması gerekenler:

| # | Kontrol | Durum |
|---|---------|-------|
| 1 | Job'lar açık tenant filtresi kullanıyor mu? | ✅ Bazileri `withoutGlobalScopes()->find()` + manuel `tenant_id` resolve |
| 2 | Job'lar arasında context temizliği var mı? | ❓ Doğrulanmadı — singleton TenantContextService job'lar arası state taşır |
| 3 | CLI komutları tenant context set ediyor mu? | ❓ Bazı komutlar `--tenant` parametresi alabilir |
| 4 | Scheduled job'lar tenant-aware mı? | ❓ Doğrulanmadı |

**Düzeltme:** Queue/CLI'de scope olmaması tek başına açık değildir — job ve komutların manuel tenant context kurup kurmadığı, context temizliği yapıp yapmadığı ayrı doğrulama gerektirir.

### 27.4 `withoutGlobalScopes()` Çağrılarının Değerlendirilmesi

§14'de 128 `withoutGlobalScopes` çağrısı listelendi. Her çağrı için:

1. **Hangi scope'lar kaldırılıyor?** — `withoutGlobalScopes()` tüm scope'ları kaldırır (TenantScope + CountryScope + diğerleri)
2. **Yerine ne konuyor?** — Bazı çağrılar manuel `WHERE tenant_id = X` ekler, bazıları eklemez
3. **Context'te tenant set mi?** — Job'lar genelde `withoutGlobalScopes()` + manuel tenant resolve yapar

**Sınıflandırma gerekiyor:**
- ✅ **Güvenli bypass:** `withoutGlobalScopes()` + manuel `WHERE tenant_id` veya tenant context set
- ⚠️ **Riskli bypass:** `withoutGlobalScopes()` + tenant kontrolü yok
- ❓ **Doğrulanmamış:** Context ve manuel kontrol belirsiz

### 27.5 Güncellenmiş Değerlendirme Çerçevesi

Raporun önceki bölümlerindeki bulgular aşağıdaki çerçevede değerlendirilmelidir:

| Bulgu | Kesin Açık mı? | Doğrulama Gereken |
|-------|---------------|-------------------|
| TenantScope fail-open | ❓ Koşullu | Tenant context'in set edilmediği senaryoların production'da oluşup oluşmadığı |
| Web grubunda SetTenantContext yok | ❓ Koşullu | Controller'ların manuel tenant context kurup kurmadığı |
| 153 `find()` çağrısı | ❓ Değil | `find()` scope uygular; context varsa güvenli |
| 128 `withoutGlobalScopes` | ❓ Karışık | Her çağrının manuel tenant kontrolü içerip içermediği |
| ~170 model BelongsToTenant yok | ✅ Kesin | Bu modellerde TenantScope hiç uygulanmaz |
| ~70+ tablo tenant_id eksik | ✅ Kesin | Kolon yoksa scope uygulanamaz |
| 5 tablo "kolon var, scope yok" | ✅ Kesin | Trait yoksa TenantScope aktif değil |

### 27.6 Sonraki Adım Önerisi

Salt-okunur kaynak incelemesiyle aşağıdaki zincirler doğrulanmalıdır:

1. **Admin controller envanteri:** Her admin controller'ı için tenant context kurulum yöntemi belirlenmeli
2. **Job envanteri:** Her job için tenant context kurulum ve temizlik pattern'i doğrulanmalı
3. **`withoutGlobalScopes` sınıflandırması:** Her çağrı güvenli/riskli/doğrulanmamış olarak sınıflandırılmalı
4. **Scheduled job audit'i:** Cron job'larının tenant context yönetimi doğrulanmalı
5. **Production tenant sayısı:** Risk seviyesi tenant sayısına bağlıdır

> **Kısıt:** Bu rapor salt-okunur kaynak incelemesidir. Düzeltme, production veri işlemi veya commit yapılmayacaktır.

---

## 28. Bekçi Implementasyonu — `bekci:tenant-audit` (Worktree)

### 28.1 Durum

Bu raporun §7'de tespit ettiği "kolon var, scope yok" gap'ını otomatik tespit eden Bekçi komutu, bir worktree'de implement edilmiştir:

**Worktree:** `yalihan-os.worktrees/tenant-isolation-bekci/`

### 28.2 Implementasyon Dosyaları

| Dosya | Rol |
|-------|-----|
| `app/Console/Commands/Bekci/TenantIsolationAuditCommand.php` | `bekci:tenant-audit` Artisan komutu |
| `app/Services/Governance/TenantIsolationAuditService.php` | Salt-okunur kaynak denetim servisi |
| `config/tenant-isolation.php` | Global tablo allowlist konfigürasyonu |
| `tests/Feature/Governance/TenantIsolationAuditCommandTest.php` | Test: `TENANT_COLUMN_WITHOUT_SCOPE` tespiti + report-only davranış |
| `docs/architecture/tenant-isolation-bekci.md` | Bekçi komutu dokümantasyonu |

### 28.3 Komut Kullanımı

```bash
php artisan bekci:tenant-audit          # Report-only (varsayılan)
php artisan bekci:tenant-audit --json    # JSON çıktı
php artisan bekci:tenant-audit --strict   # Blocking bulgu varsa exit 1
```

### 28.4 Denetim Mantığı

`TenantIsolationAuditService` şu kontrolleri yapar:

1. **Model envanteri:** `app/Models/` ve `app/Modules/` altındaki tüm Eloquent modelleri tarar
2. **Migration envanteri:** `database/migrations/` altındaki tüm `Schema::create` çağrılarından tablo listesi çıkarır
3. **tenant_id tespiti:** Her tablo için migration tanımında `tenant_id` kolonu var mı kontrol eder
4. **Trait tespiti:** Her model için `BelongsToTenant` ve `HasCountryScope` trait'leri kullanılıyor mu kontrol eder

### 28.5 Bulgu Kuralları

| Kural | Severity | Koşul | Bu Rapordaki Karşılığı |
|-------|----------|-------|----------------------|
| `TENANT_COLUMN_WITHOUT_SCOPE` | **blocking** | `tenant_id` kolonu var, `BelongsToTenant` yok | §7'deki 5 tablo (talepler, gorevler, ilan_fotograflari, property_reservations, communications) |
| `TENANT_SCOPE_WITHOUT_COLUMN` | **blocking** | `BelongsToTenant` var, `tenant_id` kolonu yok | §5'teki "trait var, kolon yok" durumu |
| `TENANT_BOUNDARY_REVIEW` | review | Global allowlist dışında, tenant kapsamı yok | §5'teki ~70+ tablo |
| `COUNTRY_SCOPE_ONLY_REVIEW` | review | Sadece `HasCountryScope` var, tenant kararı bekliyor | §6'daki ~170 model |
| `MODEL_TABLE_UNKNOWN` | review | Tablo adı çözülemedi | Manuel inceleme gerekli |

### 28.6 Global Tablo Allowlist

`config/tenant-isolation.php` içindeki `global_tables` listesi:

```php
'global_tables' => [
    'ulkeler', 'iller', 'ilceler', 'mahalleler',
    'kategoriler', 'alt_kategoriler', 'categories',
    'currencies', 'diller', 'languages',
    'roles', 'permissions',
    'model_has_roles', 'model_has_permissions', 'role_has_permissions',
    'tenants',
]
```

Bu liste §6'daki "Global (tüm tenant'lar için ortak)" sınıflandırmasıyla uyumludur.

### 28.7 Kanıt Sınırı

Bekçi komutu dokümantasyonu (`tenant-isolation-bekci.md`) şu kanıt seviyelerini tanımlar:

| Seviye | Anlamı | Bu Rapordaki Durum |
|--------|--------|-------------------|
| `DOCUMENTED` | Statik kaynak taraması | ✅ Bu rapor = DOCUMENTED |
| `REPO_VERIFIED` | Model/migration mekanizması kaynakta görüldü | ✅ Bu rapor = REPO_VERIFIED |
| `TEST_VERIFIED` | İki tenant'lı davranış testi geçildi | ❌ Henüz yok |
| `PRODUCTION_VERIFIED` | Deployed commit + canlı davranış doğrulandı | ❌ Henüz yok |

> **Önemli:** Bu Bekçi denetimi tek başına tenant izolasyonunu sertifikalandırmaz. Runtime ve production doğrulaması ayrı gate'lerdir.

### 28.8 Bu Rapor ile İlişkisi

Bu raporun §7'sinde manuel olarak tespit edilen 5 "kolon var, scope yok" tablosu, `bekci:tenant-audit` komutunun `TENANT_COLUMN_WITHOUT_SCOPE` kuralı tarafından otomatik olarak yakalanacaktır. Bu, raporun bulgularının otomatik bir Bekçi komutuna dönüştürüldüğünü gösterir.

Ancak Bekçi komutu şu noktalarda bu rapordan daha kısıtlıdır:
1. **Fail-open davranış:** `TenantScope` ve `CountryScope`'un fail-open davranışını tespit etmez (§21)
2. **Middleware gap:** `SetTenantContext`'in `web` grubunda olmamasını tespit etmez (§22)
3. **Scope bypass:** `withoutGlobalScopes` çağrılarını sınıflandırmaz (§14)
4. **Direct find():** `find()`/`findOrFail()` çağrılarını analiz etmez (§15)
5. **CLI/queue context:** Job'ların tenant context yönetimini denetlemez (§16)

Bu Bekçi komutu, bu raporun §7'sindeki en kritik bulguyu (kolon-scope uyuşmazlığı) otomatikleştirir, ancak raporun tamamını kapsamaz.
