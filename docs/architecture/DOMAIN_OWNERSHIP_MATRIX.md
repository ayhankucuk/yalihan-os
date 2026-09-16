# YALIHAN OS — DOMAIN OWNERSHIP MATRIX

**Tarih:** 2026-09-07
**Durum:** DOCUMENTED / VALIDATION_PENDING
**Kaynak:** ARCHITECTURE_BACKBONE_AUDIT.md §3, REGISTRY.md §2, tenant-isolation-audit-2026-09-06.md

---

## 1. Amaç

Bu belge, Yalıhan OS veritabanındaki tüm tabloların:
- **Authoritative Owner** (yazma sahibi domain)
- **Reader domains** (okuyucu domainler)
- **tenant_id durumu** (var/yok/global/conditional)
- **CQRS durumu** (write/read/projection)
- **BelongsToTenant** (trait kullanımı)

tek bir merkezden izlenebilir olmasını sağlar.

---

## 2. Tablo Sınıflandırması

### 2.1 Legitimately Global Tablolar (tenant_id Gereksiz)

| Tablo | Sahip Domain | Açıklama | tenant_id |
|-------|-------------|----------|-----------|
| `countries` | Identity & Auth | Ülke referansı | Gereksiz |
| `cities` | Identity & Auth | Şehir referansı | Gereksiz |
| `districts` | Identity & Auth | İlçe referansı | Gereksiz |
| `neighborhoods` | Identity & Auth | Mahalle referansı | Gereksiz |
| `currencies` | Finance | Para birimi referansı | Gereksiz |
| `system_settings` | Identity & Auth | Sistem ayarları | Gereksiz |
| `portal_definitions` | Listing | Portal tanımları | Gereksiz |
| `ai_provider_profiles` | AI/Cortex | AI sağlayıcı tanımları | Gereksiz |
| `feature_definitions` | Identity & Auth | Özellik tanımları | Gereksiz |
| `migrations` | System | Laravel migration kayıtları | Gereksiz |

### 2.2 Tenant-Scoped Tablolar (tenant_id Zorunlu) — Mevcut

| Tablo | Sahip Domain | tenant_id | BelongsToTenant | Scope | Kanıt |
|-------|-------------|-----------|-----------------|-------|-------|
| `properties` | Property | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `ilanlar` | Listing | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `kisiler` | CRM | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `reservations` | Reservation | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `media_assets` | Media | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `commissions` | Finance | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `audit_logs` | Identity & Auth | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `feature_assignments` | Identity & Auth | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `communications` | CRM | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `settlements` | Finance | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `owner_reports` | Operations | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `saas_billing` | Finance | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |
| `hermes_event_log` | Automation | ✅ Var | ✅ | TenantScope | REPO_VERIFIED |

### 2.3 Tenant-Scoped Tablolar — tenant_id Eksik (Kritik Gap)

| Tablo | Sahip Domain | tenant_id | BelongsToTenant | Risk | Kanıt |
|-------|-------------|-----------|-----------------|------|-------|
| `talepler` | CRM | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `gorevler` | Operations | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `ilan_fotograflari` | Media | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `property_reservations` | Reservation | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `follow_up_tasks` | Operations | 🔴 YOK | 🔴 YOK | Orta | REPO_VERIFIED |
| `ilan_fiyat_gecmisi` | Listing | 🔴 YOK | 🔴 YOK | Orta | REPO_VERIFIED |
| `lead_scores` | CRM | 🔴 YOK | 🔴 YOK | Orta | REPO_VERIFIED |
| `ai_analysis_results` | AI/Cortex | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `ai_prompt_logs` | AI/Cortex | 🔴 YOK | 🔴 YOK | Orta | REPO_VERIFIED |
| `governance_audits` | Identity & Auth | 🔴 YOK | 🔴 YOK | Yüksek | REPO_VERIFIED |
| `property_features` | Property | 🔴 YOK | 🔴 YOK | Orta | REPO_VERIFIED |
| `reservation_extras` | Reservation | 🔴 YOK | 🔴 YOK | Düşük | REPO_VERIFIED |

### 2.4 CQRS Projection Tabloları — tenant_id Eksik (6/6)

| Tablo | Sahip Domain | tenant_id | CQRS Rolü | Risk | Kanıt |
|-------|-------------|-----------|-----------|------|-------|
| `listing_read_models` | Listing | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |
| `property_read_models` | Property | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |
| `crm_read_models` | CRM | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |
| `reservation_read_models` | Reservation | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |
| `media_read_models` | Media | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |
| `finance_read_models` | Finance | 🔴 YOK | Read Model | Kritik | REPO_VERIFIED |

### 2.5 Conditional (Nullable tenant_id)

| Tablo | Sahip Domain | tenant_id | Açıklama | Kanıt |
|-------|-------------|-----------|----------|-------|
| `users` | Identity & Auth | Nullable | Super-admin = null, tenant user = tenant_id | REPO_VERIFIED |
| `roles` | Identity & Auth | Nullable | Global rol = null, tenant rol = tenant_id | REPO_VERIFIED |

### 2.6 "Kolon Var, Scope Yok" Gap'i

| Tablo | tenant_id kolonu | BelongsToTenant | Scope | Risk | Kanıt |
|-------|-----------------|-----------------|-------|------|-------|
| `ilanlar` (V2) | ✅ Var | 🔴 Yok | 🔴 Yok | Kritik | REPO_VERIFIED |
| `properties` (V2) | ✅ Var | 🔴 Yok | 🔴 Yok | Kritik | REPO_VERIFIED |

> Bu tablolarda tenant_id kolonu mevcut ama model BelongsToTenant trait kullanmıyor ve global scope kaydetmiyor. Veri sızıntısı riski yüksek.

---

## 3. Domain Bazında Özet

| Domain | Toplam Tablo | tenant_id Var | tenant_id Yok | Projection | Kanıt |
|--------|-------------|---------------|---------------|------------|-------|
| Property | ~15 | ~10 | ~5 | 1 (eksik) | REPO_VERIFIED |
| Listing | ~12 | ~8 | ~4 | 1 (eksik) | REPO_VERIFIED |
| CRM | ~10 | ~6 | ~4 | 1 (eksik) | REPO_VERIFIED |
| Reservation | ~8 | ~5 | ~3 | 1 (eksik) | REPO_VERIFIED |
| Media | ~8 | ~5 | ~3 | 1 (eksik) | REPO_VERIFIED |
| Finance | ~10 | ~8 | ~2 | 1 (eksik) | REPO_VERIFIED |
| Operations | ~6 | ~3 | ~3 | — | REPO_VERIFIED |
| AI/Cortex | ~8 | ~3 | ~5 | — | REPO_VERIFIED |
| Automation | ~5 | ~3 | ~2 | — | REPO_VERIFIED |
| Identity & Auth | ~12 | ~8 | ~4 | — | REPO_VERIFIED |
| **Toplam** | **~94** | **~59** | **~35** | **6 (eksik)** | |

---

## 4. Önerilen Aksiyonlar

| # | Aksiyon | Öncelik | Sahip | Kanıt |
|---|---------|---------|-------|-------|
| 1 | "Kolon Var, Scope Yok" tablolarına BelongsToTenant ekle | P0 | Security | REPO_VERIFIED |
| 2 | 6 CQRS projection tablosuna tenant_id ekle | P0 | Architecture | REPO_VERIFIED |
| 3 | 12 tenant_id eksik iş tablosuna tenant_id ekle | P1 | Backend | REPO_VERIFIED |
| 4 | REGISTRY.md §2 tablo matrixini bu belgeyle senkronize et | P2 | Architecture | DOCUMENTED |
| 5 | Periyodik tenant_id denetimi (bekci:tenant-audit + runtime test) | P2 | Security | DOCUMENTED |

---

*Bu belge ARCHITECTURE_BACKBONE_AUDIT.md §3 (Karar #3) gereği üretilmiştir. Veri kaynakları: tenant-isolation-audit-2026-09-06.md, cqrs-projection-research-report-2026-09-06.md, REGISTRY.md.*
