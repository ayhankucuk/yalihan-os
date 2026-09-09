# YALIHAN ARCHITECTURE REGISTRY
**Status:** Living Canonical Registry  
**Governance:** Strategic AI Architecture Board (SAAB)  
**Parent Document:** [YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md](file:///Users/macbookpro/repos/yalihan-os/docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md)  
**Last Updated:** 2026-09-08  

Bu belge; Yalıhan OS ekosistemindeki domain sahipliklerini, veri tablolarını, event'leri, sözleşmeleri ve AI ajan yetki sınırlarını tek bir merkezden izlenebilir kılan kanonik referans haritasıdır.

---

## 1. DOMAIN & CAPABILITY CATALOG

| Domain | Sorumlu Namespace | Temel Görev & Kapsam | Bağımlılık İzni |
|---|---|---|---|
| **Property** | `App\Domain\Property` | Fiziksel mülkler, ada/parsel, metrekare, lokasyon, tapu verileri | *Bağımsız çekirdek* |
| **Listing** | `App\Domain\Listing` | Pazarlama ilanları, fiyat teklifi, portallar, vitrin, yayın durumları | Property (Contract ile), CRM |
| **CRM** | `App\Domain\CRM` | Müşteriler, mülk sahipleri, talepler, eşleşmeler (`kisiler`) | *Bağımsız çekirdek* |
| **Reservation** | `App\Domain\Reservation` | Kısa dönem kiralama takvimi, iCal, rezervasyonlar | Property, CRM |
| **Media** | `App\Domain\Media` | Ham medya ingest, depolama soyutlaması, varyantlar, AI etiketleme | StorageProvider, Property, Listing |
| **Finance** | `App\Domain\Finance` | Komisyon hakedişleri, kapora takibi, portal ilan maliyetleri | Listing, CRM |
| **Operations** | `App\Domain\Operations` | Saha operasyonları, denetimler, anahtar teslim, temizlik/bakım | Property, Reservation |
| **AI / Cortex** | `App\Domain\AI` (`YalihanCortex`) | Metin üretimi, görsel analiz, değerleme modelleri, prompt yönetimi | CortexProviderInterface |
| **Automation** | `App\Domain\Automation` | Zamanlanmış cron işleri, n8n webhook'ları, dış senkronizasyonlar | Hermes Orchestrator |
| **Identity & Auth** | `App\Domain\Identity` | Kullanıcılar, roller, yetkiler, tenant izolasyonu, audit izleri | *Bağımsız güvenlik çekirdeği* |

---

## 2. TABLE & SCHEMA OWNERSHIP MATRIX (SSOT)

> **Kural:** Her tablonun tek bir **Authoritative Owner (Yazma Sahibi)** vardır. Diğer domainler yalnızca ilgili domain servisinin kontratı veya DTO üzerinden bu veriyi tüketebilir.

| Tablo | Sahip Domain (Writer SSOT) | Okuyucu Domainler (Readers via Contract) | Kritik Alanlar |
|---|---|---|---|
| `properties` | **Property** | Listing, Reservation, Media, Operations | `id`, `il`, `ilce`, `ada`, `parsel`, `brut_m2`, `oda_sayisi`, `lat`, `lng` |
| `ilanlar` / `listings` | **Listing** | Media, Finance, AI, Portallar | `id`, `property_id`, `fiyat`, `para_birimi`, `yayin_durumu`, `one_cikan` |
| `kisiler` / `clients` | **CRM** | Listing, Reservation, Finance | `id`, `ad`, `soyad`, `telefon`, `email`, `rol_tipi` (owner/lead) |
| `reservations` | **Reservation** | Operations, Finance, CRM | `id`, `property_id`, `kisi_id`, `giris_tarihi`, `cikis_tarihi`, `durum` |
| `media_assets` | **Media** | Listing, Property, AI | `id`, `storage_path`, `checksum_sha256`, `mime_type`, `quality_score` |
| `commissions` / `finance` | **Finance** | CRM, Listing | `id`, `ilan_id`, `tutar`, `komisyon_orani`, `odeme_durumu` |
| `audit_logs` | **Identity & Auth** | Tüm Sistem (Yalnızca Append-Only) | `id`, `actor_type`, `actor_id`, `reason_code`, `changes` |

---

## 3. DOMAIN EVENT CATALOG

| Event Adı | Fırlatan Domain | Dinleyen Modüller / İşlemler | Payload DTO |
|---|---|---|---|
| `PropertyCreated` | Property | CRM (Owner eşleme), Audit | `PropertyCreatedDTO` |
| `ListingPublished` | Listing | AI (Pazarlama metni tetikleme), Portallar, Audit | `ListingPublishedDTO` |
| `ListingPriceChanged`| Listing | CRM (Aday müşterilere bildirim), Finance | `PriceChangedDTO` |
| `MediaUploaded` | Media | Hermes (İşleme kuyruğuna alma, varyant üretimi) | `MediaUploadedDTO` |
| `MediaVariantsReady`| Media | Listing (Vitrin güncellemesi), Portallar | `MediaVariantsReadyDTO` |
| `ReservationBooked` | Reservation | Operations (Temizlik görevi açma), Finance, CRM | `ReservationBookedDTO` |

---

## 4. CONTRACT & ABSTRACTION REGISTRY

| Arayüz (Contract) | Bulunduğu Dizin | Mevcut Adaptörler / Sürücüler |
|---|---|---|
| `StorageProviderInterface` | `App\Domain\Media\Contracts` | `LocalDiskAdapter`, `GoogleDriveAdapter`, `S3CompatibleAdapter` |
| `CortexProviderInterface` | `App\Domain\AI\Contracts` | `OllamaDriver`, `OpenAiDriver`, `DeepSeekDriver`, `ClaudeDriver` |
| `NotificationSenderInterface` | `App\Support\Contracts` | `WhatsAppSender`, `SmsSender`, `EmailSender` |
| `PortalPublisherInterface` | `App\Domain\Listing\Contracts`| `SahibindenAdapter`, `HepsiEmlakAdapter`, `EmlakjetAdapter` |

---

## 5. AI AGENT ROLES & BOUNDARY MATRIX

| Agent | Rol Tanımı | İzin Verilen Alanlar | Kesin Yasaklar |
|---|---|---|---|
| **Antigravity** | Mimari, UI/UX, Güvenlik, Research | Docs, Architecture, Tests, Frontend, Refactoring | DB Destructive drop/truncate, Prod push auth'suz |
| **Kilo** | Core Engineering, Feature Delivery | Domain Services, Controllers, Migrations, Testler | SAB Anayasasını ihlal eden kod yazımı |
| **Codex** | Kod İnceleme, Linter & Format | PR review, Pint format, PHPStan analizi | İş mantığını tek taraflı değiştirme |
| **Wenox** | Operasyon & Test Koşumu | CI/CD scriptleri, E2E testleri, Health check | Yetkisiz branch merge |

---

## 6. ARCHITECTURAL DECISION RECORDS (ADR) INDEX

> ADR dosyaları: `docs/adr/` dizininde. Toplam **22 dosya** (README hariç), **farklı numaralama sistemleri**: eski format (ADR-001..021) ve yeni format (2026-02..09).

| ADR No | Dosya | Başlık | Durum |
|---|---|---|---|
| **ADR-001** | `2026-02-15-context7-canonical-turkish-fields.md` | Use Context7 Canonical Turkish Fields | **KABUL EDİLDİ** |
| **ADR-002** | `2026-02-15-performance-regression-ci-gate.md` | Performance Regression CI Gate | **KABUL EDİLDİ** |
| **ADR-003** | `2026-02-15-no-raw-fetch-policy.md` | No Raw Fetch Policy — Merkezi Ağ Katmanı Zorunluluğu | **KABUL EDİLDİ** |
| **ADR-004** | `2026-02-21-governance-enforcement-layer.md` | Governance Enforcement Layer — FeatureAssignment Observer Pattern | **KABUL EDİLDİ** |
| **ADR-003** | `2026-02-21-ssot-determinism-constitution.md` | SSOT & Determinism Anayasası | **KABUL EDİLDİ** |
| **ADR-002** | `2026-02-21-feature-assignments-architectural-freeze.md` | Feature Assignments Modülü — Architectural Freeze | **KABUL EDİLDİ** |
| **ADR-002** | `2026-02-15-api-contract-freeze.md` | PH-AI-TEMPLATE Contract Freeze + TemplateContextResolver + Telemetry MVP | **KABUL EDİLDİ** |
| **ADR-020** | `020-governance-diff-viewer-cli-read-model.md` | Governance Diff Viewer CLI Read Model | **KABUL EDİLDİ** |
| **ADR-?** | `2026-02-15-governance-simplification-analysis.md` | Governance Karmaşa Analizi & Sadeleştirme Planı | **ANALİZ** |
| **ADR-?** | `2026-03-02-controller-mutation-delegation-batch5.md` | Controller Mutation Delegation (Batch 5) | **KABUL EDİLDİ** |
| **ADR-?** | `2026-03-03-sab-production-seal-v1.md` | SAB Production Seal v1 | **KABUL EDİLDİ** |
| **ADR-011** | `2026-04-03-ai-decision-engine.md` | AI Decision Engine (sab-decide.sh) | **KABUL EDİLDİ** |
| **ADR-?** | `2026-04-03-sidebar-5-layer-architecture.md` | Sidebar 5-Layer Product Architecture | **KABUL EDİLDİ** |
| **ADR-SAB4** | `2026-04-04-sab4-multi-agent-orchestration.md` | Multi-Agent Orchestration Layer | **KABUL EDİLDİ** |
| **ADR-SAB8** | `2026-04-04-sab8-decision-action-feedback-loop.md` | Decision → Action → Feedback Loop | **KABUL EDİLDİ** |
| **ADR-?** | `2026-04-10-env-drift-guard-contract.md` | EnvDriftGuard Production Contract | **KABUL EDİLDİ** |
| **ADR-?** | `2026-04-21-h1-ledger-legacy-import-migration.md` | H1 Ledger Legacy Finance Namespace Import Migration | **PROPOSED** |
| **ADR-H4** | `2026-04-21-h4-testing-environment-schema-authority.md` | Testing Environment — Schema Authority & Dump Drift | **PREFLIGHT SEALED** |
| **ADR-H7** | `2026-04-21-h7-problem-analyzer-v1-pack-p0.md` | H7 Problem Analyzer v1 — Pack-P0 Delivery | **PACK-P0 DELIVERED** |
| **ADR-021** | `2026-05-15-bekci-v2-1-cognitive-guardian-ast.md` | Yalıhan Bekçi v2.1 — Cognitive Guardian (AST Analysis) | **ACCEPTED** |
| **ADR-021** | `2026-06-15-sprint2-architecture-decisions.md` | Sprint 2 Mimari Kararları — Domain Birleştirme, DriftDetection, ModuleServiceProvider | **KABUL EDİLDİ** |
| **ADR-041** | `2026-06-28-adr041-context-isolation-standard.md` | Context Isolation Standard (AI session budget) | **IMPLEMENTED** |
| **ADR-042** | `2026-09-07-adr042-architecture-backbone-audit.md` | Architecture Backbone Audit — 15 Mimari Karar | **PROPOSED** |

> **Not:** ADR numaralandırması tarihsel olarak tutarsızdır — aynı numara farklı belgelere atanmış (örn. ADR-002 üç farklı dosyada). Bu tablodaki numaralar dosyalardaki orijinal numaralarıdır. REGISTRY.md'nin kendi SSOT numaralandırması SAAB tarafından atanacaktır.

---
*YALIHAN Architecture Registry — Tek Sistem, Tek Hakikat Kaynağı.*
