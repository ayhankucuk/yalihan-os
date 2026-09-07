# YALIHAN OS — TENANT ISOLATION CONTRACT

**Tarih:** 2026-09-07
**Durum:** DOCUMENTED / VALIDATION_PENDING / IMPLEMENTATION-BLOCKED-PENDING-AUTH
**Kaynak:** ARCHITECTURE_BACKBONE_AUDIT.md §2, tenant-isolation-audit-2026-09-06.md, SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md

---

## 1. Amaç

Bu belge, Yalıhan OS'de çoklu-tenant (multi-tenant) veri izolasyonu için:
- Normatif kuralları (authority.json, SAB.md, Constitution)
- Kod mekanizmalarını (scope, middleware, policy)
- Gerçek uygulama gereksinimlerini (test, production)
- Kabul kriterlerini

tek bir sözleşme halinde tanımlar.

---

## 2. Normatif Kurallar

### 2.1 Constitution Madde 15.2.1 (Kural 1 — En Ağır İhlal)

> "Hiçbir kiracı (tenant) başka bir kiracının verisini göremez veya değiştiremez. Her veritabanı sorgusu tenant scope içermek zorundadır."

### 2.2 SAB.md Rule 16

> "Multi-Tenant Financial Scoping: Finansal query'lerde tenant_id zorunludur."

### 2.3 SAB.md Mali Suçlar §1

> "Authority Leakage (Yetki Sızıntısı): tenant_id filtresi olmayan her türlü finansal veri erişimi."

### 2.4 authority.json context_isolation

> ADR-041, P0_IMPLEMENTED, budget tiers: normal 0-80K, warning 80-120K, freeze 120-150K, archive >150K

---

## 3. Kod Mekanizmaları

### 3.1 Model Scope'ları

| Scope | Trait | Fail Modu | Durum | Kanıt |
|-------|-------|-----------|-------|-------|
| `TenantScope` | `BelongsToTenant` | **Fail-open** (tenant_id=null → scope uygulanmaz) | 🔴 Kritik | REPO_VERIFIED |
| `CountryScope` | `BelongsToCountry` | **fail-open** (ulke_id=null → scope uygulanmaz) | 🔴 Kritik | REPO_VERIFIED |

**Gereken değişiklik:**
```
TenantScope::apply():
  if (tenant_id === null) {
    return $builder->whereRaw('1=0'); // fail-closed: bo sonuç
  }
  return $builder->where('tenant_id', $tenant_id);
```

### 3.2 Middleware

| Middleware | Route Grubu | Durum | Kanıt |
|------------|-------------|-------|-------|
| `SetTenantContext` | API | ✅ Mevcut | REPO_VERIFIED |
| `SetTenantContext` | Admin | ✅ Mevcut | REPO_VERIFIED |
| `SetTenantContext` | Web | 🔴 Eksik | REPO_VERIFIED |
| `SetTenantContext` | Checkout | 🔴 Özel durum | REPO_VERIFIED |
| `SetTenantContext` | CLI/Artisan | 🔴 Eksik | REPO_VERIFIED |
| `SetTenantContext` | Queue/Job | 🔴 Eksik | REPO_VERIFIED |

**Gereken değişiklik:**
- Web grubuna SetTenantContext ekle
- CLI komutlarına --tenant parametresi ekle
- Queue job dispatch sırasında tenant context serialize et

### 3.3 Queue/Job Tenant Context

| Mekanizma | Adoption | Kanıt |
|-----------|----------|-------|
| `TenantAwareJobInterface` | 0/∞ job | REPO_VERIFIED |
| `RestoreTenantContext` | 0/∞ job | REPO_VERIFIED |
| `DailySnapshotsJob` | Yok | REPO_VERIFIED |
| `OwnerReportExportJob` | Yok | REPO_VERIFIED |
| `NotifyN8nAboutIlanPriceChange` | Yok | REPO_VERIFIED |
| `TalepTopluAnalizJob` | Yok | REPO_VERIFIED |

**Gereken değişiklik:**
```
interface TenantAwareJobInterface {
    public function getTenantId(): ?int;
    public function setTenantId(int $tenantId): void;
}

// Job dispatch:
$job = new SomeJob($data);
$job->setTenantId(tenant()->id);
dispatch($job);

// Job handle:
class SomeJob implements TenantAwareJobInterface {
    public function handle() {
        $this->restoreTenantContext();
        // ... job logic
    }
}
```

### 3.4 Bypass Kategorileri

| Bypass | Amaç | Risk | Kanıt |
|--------|------|------|-------|
| `GuestConciergeRouter` | CountryScope bypass — misafir concierge | Düşük (intentional) | REPO_VERIFIED |
| `DemandMatchingEngine` | TenantScope bypass — talep eşleştirme | Orta (intentional) | REPO_VERIFIED |
| `withoutGlobalScopes()` | Çeşitli yerlerde | Değerlendir | REPO_VERIFIED |
| `find()` kullanımı | Scope bypass olabilir | Orta | REPO_VERIFIED |

---

## 4. Kabul Kriterleri

### 4.1 TenantScope

- [ ] TenantScope fail-closed (tenant_id=null → bo sonuç)
- [ ] Tüm tenant-scoped modeller BelongsToTenant kullanır
- [ ] `withoutGlobalScopes()` sadece audit log ile kullanılır

### 4.2 Middleware

- [ ] SetTenantContext tüm route gruplarında (API, Admin, Web)
- [ ] CLI komutları --tenant parametresi alır
- [ ] Checkout route tenant-aware

### 4.3 Queue/Job

- [ ] Tüm job'lar TenantAwareJobInterface implemente eder
- [ ] Job dispatch tenant context serialize eder
- [ ] Job handle tenant context restore eder

### 4.4 CQRS Projection

- [ ] 6 projection tablosunda tenant_id var
- [ ] Projection rebuild tenant-aware
- [ ] Projection DLQ replay tenant context korur

### 4.5 Test

- [ ] Negatif tenant test: Tenant A, Tenant B verisini göremez
- [ ] Queue context test: Job tenant boundary'yi aşamaz
- [ ] Admin test: Admin tenant-aware modda çalışır
- [ ] CLI test: CLI komutu tenant context set eder

---

## 5. İzolasyon Katmanları ve Gerçek Durum

| Katman | İdeal | Mevcut | Gap |
|--------|-------|--------|-----|
| DB kolon (tenant_id) | Tüm tenant tablolarında | ~59/94 tabloda | 35 tablo eksik |
| Model scope (BelongsToTenant) | Tüm tenant modellerinde | ~59/94 modelde | 35 model eksik |
| Middleware (SetTenantContext) | Tüm route gruplarında | 2/5 grup | 3 grup eksik |
| Queue (TenantAwareJob) | Tüm job'larda | 0/∞ | Tamamen eksik |
| CLI (tenant parametre) | Tüm komutlarda | 0/∞ | Tamamen eksik |
| Test (negatif tenant) | Mevcut | Planlanan | Eksik |
| Production (periyodik denetim) | Mevcut | Yok | Eksik |

---

## 6. Phase Planı

| Phase | İçerik | Süre | Öncelik |
|-------|--------|------|---------|
| Phase 0 | "Kolon Var, Scope Yok" düzeltmesi | 1 gün | P0 |
| Phase 1 | Kritik iş tablolarına tenant_id ekleme | 2-3 gün | P0 |
| Phase 2 | İlan alt tabloları | 3-4 gün | P1 |
| Phase 3 | AI tabloları | 3-4 gün | P1 |
| Phase 4 | Governance/audit tabloları | 1-2 gün | P1 |
| Phase 5 | Property alt tabloları | 2 gün | P2 |
| Phase 6 | CQRS projection tabloları | 2 gün | P0 |
| Phase 7 | TenantScope fail-closed | 1 gün | P0 |
| Phase 8 | SetTenantContext tüm route'larda | 1 gün | P0 |
| Phase 9 | Queue tenant context | 2-3 gün | P1 |
| Phase 10 | Negatif tenant testleri | 2-3 gün | P0 |

**Toplam tahmini süre:** 20-25 gün

---

## 7. Backfill Stratejisi

### 7.1 Backfill Kaynak Hiyerarşisi

```
1. Mevcut tenant context (session/auth)
2. İlan → tenant_id (ilanlar tablosundan join)
3. Property → tenant_id (properties tablosundan join)
4. User → tenant_id (users tablosundan join)
5. Default tenant (sistem ayarı)
```

### 7.2 Çözülemeyen Kayıtlar (Orphan Records)

- tenant_id atanamayan kayıtlar için: `tenant_id = NULL` bırak + audit log
- Orphan kayıtlar periyodik raporlanmalı
- Manual resolution gerekli

---

*Bu belge ARCHITECTURE_BACKBONE_AUDIT.md §2 (Karar #2) gereği üretilmiştir. Veri kaynakları: tenant-isolation-audit-2026-09-06.md (1450 satır), SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md, cqrs-projection-research-report-2026-09-06.md.*
