# RC2 Dirty Envanter — Sınıflandırma Raporu

**Tarih:** 2026-09-13  
**Kapsam:** `release-candidate/RC2` — 32 modified + 42 untracked  
**Yöntem:** Salt-okunur git/yol analizi  
**Sahiplik:** Tüm dosyalar `UNKNOWN_OWNER` — üretken ajan/oturum kimliği doğrulanmadı  

---

## YÖNETİCİ ÖZET

| Durum | Sayı | Öncelik |
|-------|------|---------|
| Modified (staged yok, sadece unstaged) | 32 | HIGH |
| Untracked dosya/dizin | 42 | MEDIUM |
| Staged (hazır commit) | 0 | — |

> **Tüm 32 modified dosya unstaged durumda.** Hiçbiri bir önceki oturumdan `git add` edilmemiş. Commit için her dosya ayrı ayrı veya grupça `git add` gerektirir.

---

## PAKET A — RC2 Kapsamı (Büyük İhtimalle)

Bu dosyaların commit mesajı/imesti RC2 (`01eec131`, `557f79b7`) ile uyumlu. Büyük olasılıkla **RC2 release branch**'inin bir parçası olarak commit edilmek veya RC2'ye entegre edilmek üzere hazırlandı.

| Dosya | Durum | Katman | Not |
|-------|-------|--------|-----|
| `config/crm.php` | M | Config | RC2 Strangler Fig DI + feature flags |
| `config/feature-flags.php` | M | Config | RC2 Strangler Fig DI + feature flags |
| `app/Http/Controllers/Api/IlanWizardController.php` | M | API | RC2 Strangler Fig integration |
| `app/Providers/AppServiceProvider.php` | M | Provider | RC2 DI binding (commit 01eec131'de var) |
| `app/Providers/EventServiceProvider.php` | M | Provider | RC2 DI binding (commit 01eec131'de var) |
| `routes/admin/talepler.php` | M | Routing | RC2 Talep CRUD routes |
| `app/Console/Commands/YalihanBekciHealthCommand.php` | M | Command | RC2 P0 auth fix? (seems unrelated to RC2) |
| `AGENTS.md` | M | Governance | RC2 governance docs |

> **Soru:** `YalihanBekciHealthCommand.php` — RC2 ile ilgili mi? Commit history'de RC2'nin bir parçası olarak görünmüyor. Sahipliği doğrulanmalı.

---

## PAKET B — Wizard Domain (Ayrı Strangler Fig Dalı Bekliyor)

Bu dosyalar `Wizard` event listener + field resolver domain'i ile ilgili. `BEKCI_CHANGELOG.md` satır 42-48'e göre ayrı branch'te kalması önerildi.

| Dosya | Durum | İçerik | Öncelik |
|-------|-------|--------|---------|
| `app/Listeners/Wizard/HandleWizardStepCompleted.php` | UT | Listener | Yeni dosya |
| `app/Listeners/Wizard/HandleWizardSubmission.php` | UT | Listener | Yeni dosya |
| `app/Services/Wizard/FieldEngine/FieldResolver.php` | M | Service | Deprecated annotation mevcut; consumer yok |
| `app/Http/Controllers/Api/IlanWizardController.php` | M | Controller | Ayrıca Paket A'da |
| `app/Services/Location/PoiService.php` | M | Service | Wizard domain'e yakın |

---

## PAKET C — Location Domain (Ayrı Branch Bekliyor)

`BEKCI_CHANGELOG.md` satır 42'e göre ayrı branch'te kalması gerekiyor. DatabaseSeeder + Location/POI alanı.

| Dosya | Durum | İçerik |
|-------|-------|---------|
| `app/Http/Controllers/Api/V1/LocationPoiController.php` | M | Controller |
| `app/Services/Location/PoiService.php` | M | Service |
| `config/location.php` | M | Config |
| `database/seeders/DatabaseSeeder.php` | M | Seeder (Location + Ozellik seeder'ları) |
| `database/seeders/legacy/` | UT | Legacy seeder dizini |
| `database/seeders/OzellikKategoriSeeder.php` | D | Silinmiş (diskte var, git'te silinmiş) |
| `database/seeders/PropertyHubOzelliklerSeeder.php` | D | Silinmiş (diskte var, git'te silinmiş) |
| `tests/Feature/Location/` | UT | Test dizini (2 test dosyası) |

---

## PAKET D — Form Contract / Domain Field Policy

ADR-043 kapsamında üretilen yeni domain katmanı. RC2 sonrası ayrı feature flag ile devreye girecek.

| Dosya | Durum | İçerik |
|-------|-------|---------|
| `app/Application/Ilan/Services/DomainFieldResolverAdapter.php` | UT | Adapter |
| `app/Domain/Ilan/Policies/CategoryFieldPolicy.php` | UT | Policy |
| `app/Domain/Ilan/ValueObjects/FieldDefinition.php` | UT | Value Object |
| `app/Domain/Ilan/ValueObjects/FieldKey.php` | UT | Value Object |
| `app/Domain/Ilan/ValueObjects/ValidationRule.php` | UT | Value Object |
| `tests/Feature/Wizard/FormFieldContractParityTest.php` | UT | Test |
| `tests/Unit/Domain/Ilan/` | UT | Unit test dizini |
| `docs/adr/2026-09-12-adr043-canonical-form-contract-and-seeder-governance.md` | UT | ADR |
| `docs/architecture/DATA_CONTRACT_AND_SEEDER_GOVERNANCE.md` | UT | Standart doc |

---

## PAKET E — Güvenlik & Bekçi Sistemi

Bu dosyalar güvenlik veya Yalıhan Bekçi izleme sistemi ile ilgili. P0 incident olarak işaretlenmiş veya BEKCI changelog'a göre işleniyor.

| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Services/Bekci/AuditMcpServer.php` | M | MCP audit |
| `app/Services/Bekci/Scanners/ArchitectureGuardScanner.php` | M | AST scanner |
| `.sab/authority.json` | M | Authority SSOT |

---

## PAKET F — Migration'lar (Risk: Orta-Yüksek)

Database migration dosyaları. Bazıları production migration riski taşıyor.

| Dosya | Durum | Risk |
|-------|-------|------|
| `database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php` | M | Düşük (schema-only, tablo zaten mevcut) |
| `database/migrations/2026_08_23_000002_create_c51_settlement_domain_tables.php` | M | ORTA (c51 settlement domain) |
| `database/migrations/2026_08_23_000004_create_bank_accounts_table.php` | M | ORTA (bank accounts) |
| `database/migrations/2026_08_24_000001_create_workforce_executions_table.php` | M | Düşük (schema) |
| `database/migrations/2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php` | M | Düşük (index only) |

---

## PAKET G — Diğerleri

| Dosya | Durum | Not |
|-------|-------|-----|
| `app/Http/Requests/Owner/StoreOwnerIlanRequest.php` | M | Owner domain |
| `app/Services/CRM/KisiScoringService.php` | M | CRM domain — `strtolower(KisiTipi)` bug mevcut (KNOWN_ISSUES) |
| `resources/js/app.js` | M | Frontend asset |
| `resources/views/frontend/ilanlar/show.blade.php` | M | Frontend view |
| `resources/views/owner/ilanlar/create.blade.php` | M | Frontend view |
| `routes/admin.php` | M | Admin routes |

---

## PAKET H — Project Brain / Dokümantasyon

| Dosya | Durum | Not |
|-------|-------|-----|
| `.project-brain/DECISION_LOG.md` | M | Karar kaydı |
| `.project-brain/EVIDENCE_INDEX.md` | M | Kanıt envanteri |
| `.project-brain/KNOWN_ISSUES.md` | M | Bilinen hatalar |
| `.project-brain/PROJECT_STATE.md` | M | Proje durumu |
| `.project-brain/P0-1-RC2-INVENTORY.md` | UT | P0-1 RC2 envanteri |
| `.project-brain/SECURITY-WIZARD-FEATURE-SUGGESTIONS-01.md` | UT | Güvenlik karar dokümanı |
| `.project-brain/TEMPLATE_HUB_AUDIT.md` | UT | Template Hub denetim kanıtı |
| `.project-brain/TENANT-FEATURE-ASSIGNMENT-01.md` | UT | Tenant karar dokümanı |
| `.project-brain/TENANT-FEATURE-ASSIGNMENT-01A.md` | UT | Tenant karar dokümanı |
| `docs/BEKCI_CHANGELOG.md` | M | BEKCI geliştirme günlüğü |
| `docs/PROGRESS-TRACKER.md` | M | İlerleme takibi |
| `docs/SAB/` | UT | SAB dokümantasyon dizini |
| `docs/architecture/YALIHAN_OS_ENTERPRISE_TAXONOMY.md` | UT | Mimari doküman |
| `config/exchange.php` | UT | Exchange config |

---

## PAKET I — Araştırma & Scripts

| Dosya | Durum | Not |
|-------|-------|-----|
| `YALIHAN_OS_RESEARCH/` | UT | Araştırma dizin |
| `scripts/services/bekci-mcp-lifecycle.sh` | UT | Bekçi MCP script |

---

## ÖNERİLEN PAKETLEME

| Paket | Öncelik | Neden | Risk |
|-------|---------|-------|------|
| **PAKET D** (Form Contract) | CRITICAL | ADR-043 tamamlandı, test edildi, feature flag var | Düşük |
| **PAKET A** (RC2 residual) | HIGH | RC2 entegrasyonu yarım kalmış | Orta |
| **PAKET E** (Bekçi/MCP) | HIGH | Güvenlik — MCP scanner + authority | Düşük |
| **PAKET F** (Migration'lar) | MEDIUM | Her biri ayrı değerlendirilmeli | Orta-Yüksek |
| **PAKET C** (Location) | MEDIUM | Ayrı branch olarak kalması önerildi | Yüksek |
| **PAKET B** (Wizard Listeners) | MEDIUM | Wizard Strangler Fig bekliyor | Orta |
| **PAKET G** (Diğer) | LOW | CRM, Owner, Frontend — kapsam belirsiz | Orta |

---

## BİLİNMEYENLER ( Sahipliği Doğrulanmalı )

1. **`app/Console/Commands/YalihanBekciHealthCommand.php`** — RC2 ile gerçekten ilgili mi?
2. **`app/Services/CRM/KisiScoringService.php`** — Known issue (`strtolower(KisiTipi)`) bug'ı fix edilmeli
3. **Paket G** — Owner domain ve frontend değişikliklerinin sahibi bilinmiyor
4. **`config/exchange.php`** — Yeni config, kimin/ne için eklendiği bilinmiyor

---

## EYLEM: SAHİPLİK KİLİTLEME

Bu envanterin ardından **insan karar verici** şunları onaylamalı:

1. Her paket için: commit edilsin mi, silinsin mi, beklesin mi?
2. `YalihanBekciHealthCommand.php` → RC2 kapsamında mı?
3. `KisiScoringService.php` → bug fix ayrı paket mi, yoksa RC2'ye dahil mi?
4. Migration'lar → hangisi production'a hazır?

**Mülkiyet durumu:** Tüm dosyalar `UNKNOWN_OWNER` — sahiplik doğrulaması için ilgili ajan/oturum kayıtlarına bakılmalı veya insan onayı gerekir.
