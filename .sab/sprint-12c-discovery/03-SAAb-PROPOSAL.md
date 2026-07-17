# Sprint 12C — SAAB Migration Proposal

**Tarih:** 2026-07-17
**Agent:** Kilo (Claude Opus 4.8)
**Durum:** PROPOSAL — SAAB Onayı Bekleniyor

---

## Yönetici Özeti

Sprint 12B ile kurulan Property canonical aggregate omurgası üzerine, Workspace'in Property'ye 1:1 canonical sahiplik ilişkisi kurulması önerilmektedir.

**Mimari Karar:**
> Property, canonical business aggregate'tir. PropertyWorkspace, bir Property'ye ait tek operasyonel workspace'tir. Listing'ler, Property'nin Sahibinden, Airbnb, Hepsiemlak gibi kanallardaki yayın temsilidir ve Workspace'e sahip olmaz.

---

## 1. Mimari Gerekçe

### 1.1 Mevcut Durum

```
Property ──1:N──> Listing (ilan)
     ▲
     │ (opsiyonel, legacy)
     │
Workspace (property_workspaces)
     │
     └── ilan_id (nullable, FK yok)
```

### 1.2 Önerilen Durum

```
Property ──1:1──> Workspace
     │
     └── 1:N ──> Listing
```

### 1.3 Domain Mantığı

| Kavram | Tanım |
|--------|-------|
| **Property** | Fiziksel gayrimenkulün canonical kimliği |
| **Workspace** | Bir Property'nin operasyonel context'i (1:1) |
| **Listing** | Bir Property'nin belirli bir kanaldaki yayın temsili |
| **Execution** | Workspace içinde gerçekleşen operasyon |

**Neden 1:1?**
- Workspace, ev sahibi, site, anahtar bilgileri, belgeler, medya, muhasebe, AI analizleri gibi bütünsel operasyonel durumu temsil eder
- Bir Property için birden fazla bağımsız Workspace, aynı evin geçmişini parçalar
- Replay gerektiğinde yeni Workspace değil, yeni Execution oluşturulur

---

## 2. Schema Değişikliği

### 2.1 Yeni Tablo Yapısı: property_workspaces

```sql
CREATE TABLE property_workspaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    workspace_uuid CHAR(36) NOT NULL UNIQUE,
    intent VARCHAR(255) NULL,
    template_id VARCHAR(255) NULL,
    state VARCHAR(255) NOT NULL DEFAULT 'workspace_created',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    -- Canonical ownership: 1 Property : 1 Workspace
    UNIQUE INDEX idx_property_unique (property_id),

    -- Foreign key
    CONSTRAINT fk_workspace_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    -- Indexes
    INDEX idx_tenant_state (tenant_id, state),
    INDEX idx_tenant_property (tenant_id, property_id)
);
```

### 2.2 Kaldırılacak Alanlar

| Alan | Tablo | Gerekçe |
|------|-------|---------|
| `ilan_id` | property_workspaces | Legacy; Workspace artık Property'ye değil, Listing'e değil |
| `workspace_id` | properties | Çift yönlü FK oluşturmamak için |

### 2.3 UNIQUE(property_id) Kısıtı

UNIQUE kısıtı, veritabanı seviyesinde 1:1 cardinality'yi garanti eder.

```sql
-- Bu kısıt ihlal edilirse, veritabanı hatası oluşur
-- Böylece aynı Property için iki Workspace oluşturulamaz
```

---

## 3. Migration Planı

### 3.1 Migration Sırası

```
Adım 1: property_workspaces tablosuna property_id ekle
         ├── ilan_id yanına eklenir (geçici)
         └── nullable, geri dönüşü kolay

Adım 2: Backfill
         ├── Mevcut workspace_uuid ile properties tablosunda eşleşme
         └── UNIQUE constraint ihlal kontrolü

Adım 3: UNIQUE(property_id) kısıtı ekle
         └── Veri bütünlüğü doğrulaması

Adım 4: FK constraint ekle
         └── ON DELETE RESTRICT

Adım 5: ilan_id kolonu kaldır
         └── Ayrı migration, geri alınabilir

Adım 6: properties.workspace_id kaldır
         └── Çift yönlü FK önlenir
```

### 3.2 Veri Durumu

| Tablo | Kayıt | Not |
|-------|-------|-----|
| property_workspaces | 0 | Migration riski çok düşük |
| properties | 2 | Sprint 12B ile oluşturuldu |

### 3.3 Idempotency

Tüm migration adımları idempotent olacak:
- `IF NOT EXISTS` kontrolleri
- `hasTable` / `hasColumn` kontrolleri
- Tekrar çalıştırılabilir

---

## 4. Kod Değişiklikleri

### 4.1 PropertyWorkspaceService

```php
// MEVCUT
public function createWorkspace(int $ilanId, string $intent, ?string $templateId = null): PropertyWorkspace

// YENİ
public function createWorkspace(int $propertyId, string $intent, ?string $templateId = null): PropertyWorkspace
```

### 4.2 Aggregate State

`PropertyWorkspaceAggregate` state'inde `ilan_id` kaldırılır veya `property_id` eklenir.

### 4.3 Event Payload

| Event | Değişiklik |
|-------|-------------|
| PropertyWorkspaceCreated | `ilan_id` → `property_id` |
| PublishingDecisionReady | `ilan_id` → `property_id` |
| PropertyScoreCalculated | `ilan_id` → `property_id` |
| DescriptionCompleted | `ilan_id` → `property_id` |
| PhotoAnalysisCompleted | `ilan_id` → `property_id` |

### 4.4 Controller/UI

`WorkspaceDashboardController` güncellenir:
- `workspace->ilan_id` → `workspace->property->listings->first()`

---

## 5. Test Planı

### 5.1 Birim Testler

| Test | Senaryo |
|------|---------|
| PropertyWorkspaceServiceTest | createWorkspace with property_id |
| PropertyWorkspaceAggregateTest | State transitions |
| PropertyTest | 1:1 Workspace invariant |

### 5.2 Entegrasyon Testler

| Test | Senaryo |
|------|---------|
| WorkspaceCreationFlowTest | Property → Workspace oluşturma |
| WorkspacePublishingTest | Workspace → Execution → Publish |
| TenantIsolationTest | Workspace tenant boundary |

### 5.3 Regresyon Testleri

```bash
php artisan test --filter=Property
php artisan test --filter=Workspace
php artisan test --filter=SyncPropertyCalendarFeed
```

---

## 6. Rollback Planı

### 6.1 Migration Rollback

Her adım tersine çevrilebilir:

| Adım | Rollback |
|------|---------|
| property_id ekle | `DROP COLUMN property_id` |
| UNIQUE kısıtı | `DROP INDEX` |
| FK ekle | `DROP FOREIGN KEY` |
| ilan_id kaldır | `ADD COLUMN ilan_id` |

### 6.2 Kod Rollback

Git revert ile önceki commit'e dönülebilir:
```bash
git revert <commit-hash>
```

---

## 7. Go / No-Go Kriterleri

### 7.1 Pre-Migration

| # | Kriter | Hedef |
|---|---------|-------|
| 1 | property_workspaces veri sayısı | 0 veya tanımlı mapping |
| 2 | UNIQUE constraint doğrulaması | property_id tekrar yok |
| 3 | ilan_id referansları güncellenmiş | Tüm kod referansları değiştirilmiş |
| 4 | Event version uyumu | Yeni event formatı ile eski uyumlu |

### 7.2 Post-Migration

| # | Kriter | Hedef |
|---|---------|-------|
| 1 | UNIQUE(property_id) aktif | Tek property başına tek workspace |
| 2 | FK constraint aktif | ON DELETE RESTRICT |
| 3 | Tenant isolation | Doğrulanmış |
| 4 | Tüm testler green | 95%+ pass rate |

### 7.3 No-Go Kriterleri

| # | Kriter | Aksiyon |
|---|---------|---------|
| 1 | UNIQUE constraint ihlal | Rollback, manuel müdahale |
| 2 | FK ekleme başarısız | Rollback |
| 3 | Test pass rate < 90% | Düzeltme, tekrar test |

---

## 8. Risk Analizi

### 8.1 Risk Matrisi

| Risk | Olasılık | Etki | Çözüm |
|------|-----------|------|-------|
| Mevcut workspace verisi yok | Yüksek | Düşük | Backfill gerekmiyor |
| ilan_id referansları | Orta | Orta | Kademeli migration |
| Event backward compat | Düşük | Orta | Versioned event |
| UI breaking change | Düşük | Düşük | Incremental rollout |

### 8.2 Impact Assessment

| Kategori | Etkilenecek Dosya Sayısı |
|----------|--------------------------|
| Migration | 2-3 |
| Service | 1 |
| Controller | 1 |
| Aggregate | 1 |
| Event | 5 |
| Test | 3-5 |

---

## 9. Canonical Ownership Rule

### 9.1 Temel Kural

```
Only Property owns Workspace.
Listing never owns Workspace.
Workspace never owns Listing.
```

### 9.2 Açıklama

Bu kural, veritabanı tasarımında yanlış FK eklemelerini engeller.

**Yasak:**
- `property_workspaces.ilan_id` → canonical ownership DEĞİL
- `ilanlar.workspace_id` → canonical ownership DEĞİL

**İzin Verilen:**
- `property_workspaces.property_id` → canonical ownership EVET
- `ilanlar.property_id` → Property → Listing ilişkisi EVET

---

## 10. Workspace Invariant

### 10.1 Domain Kuralı

```
Invariant: One Property = One Active Workspace
```

### 10.2 Açıklama

- Bir Property için aynı anda yalnızca bir ACTIVE Workspace bulunabilir
- İkinci Workspace oluşturulmak istendiğinde:
  - Sistem hata vermeli VE
  - Mevcut Workspace'in ARCHIVED olması gerektiği belirtilmeli

### 10.3 Uygulama

```php
// PropertyWorkspaceService::createWorkspace
public function createWorkspace(int $propertyId, string $intent, ?string $templateId = null): PropertyWorkspace
{
    // Invariant check: Property already has an active workspace?
    $existingWorkspace = PropertyWorkspace::where('property_id', $propertyId)
        ->whereNotIn('state', ['archived'])
        ->first();

    if ($existingWorkspace) {
        throw new DomainException(
            "Property already has an active workspace. " .
            "Archive the existing workspace before creating a new one."
        );
    }

    // ... create workspace
}
```

---

## 11. Event Naming Policy

### 11.1 Property Merkezli Adlandırma

Tüm Workspace event'leri Property merkezli isimlendirilir:

| Eski Event | Yeni Event | Açıklama |
|------------|------------|----------|
| PropertyWorkspaceCreated | PropertyWorkspaceCreated | Property ile ilişkilendirildi |
| PropertyWorkspaceArchived | PropertyWorkspaceArchived | — |
| PropertyWorkspaceActivated | PropertyWorkspaceActivated | — |
| PropertyWorkspaceMerged | PropertyWorkspaceMerged | Gelecek için |
| PropertyWorkspaceDeleted | PropertyWorkspaceDeleted | — |

### 11.2 Event Payload

```php
// Tüm Workspace event'leri property_id taşımalı
class PropertyWorkspaceCreated
{
    public function __construct(
        public readonly int $propertyId,
        public readonly int $workspaceId,
        public readonly string $intent,
        public readonly int $tenantId,
    ) {}

    public function toArray(): array
    {
        return [
            'property_id' => $this->propertyId,  // propertyId öncelikli
            'workspace_id' => $this->workspaceId,
            'intent' => $this->intent,
            'tenant_id' => $this->tenantId,
        ];
    }
}
```

---

## 12. Migration Safety Policy

### 12.1 Temel İlkeler

| İlke | Açıklama |
|------|----------|
| **Reversible** | Her migration tersine çevrilebilir olmalı |
| **Idempotent** | Tekrar çalıştırılabilir olmalı |
| **No Data Loss** | Hiçbir veri kaybı olmamalı |
| **Tenant-Safe** | Tenant izolasyonu korunmalı |

### 12.2 Implementation Kuralları

```php
// 1. Tablo kontrolü
if (Schema::hasTable('property_workspaces')) {
    return; // Skip if exists
}

// 2. Kolon kontrolü
if (Schema::hasColumn('property_workspaces', 'property_id')) {
    return; // Skip if exists
}

// 3. Constraint kontrolü
if ($this->fkExists('property_workspaces', 'fk_property')) {
    return; // Skip if exists
}

// 4. Rollback log
$this->log("Migration completed at " . now());
```

### 12.3 Yasaklar

- `DROP COLUMN` ilk migration'da YASAK
- `TRUNCATE` kesinlikle YASAK
- Sessiz veri dönüşümü YASAK
- Tenant boundary ihlali YASAK

---

## 13. Future Compatibility

### 13.1 Property Operating System Vizyonu

```
                    Property (Canonical Aggregate)
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
   Workspace          Owner             Accounting
        │                  │                  │
        ▼                  ▼                  ▼
   Executions        Contacts           Transactions
        │
        ▼
   Listings
   │
   ├── Sahibinden
   ├── Airbnb
   ├── Hepsiemlak
   └── ...
```

### 13.2 Sprint 12D–12F İlişkilendirmesi

| Sprint | Domain | Property İlişkisi |
|--------|--------|-------------------|
| Sprint 12D | Property Owner | Owner → Property (1:N) |
| Sprint 12E | Accounting | Accounting → Property (1:N) |
| Sprint 12F | External Listing Intelligence | ELI → Property → Listing |

### 13.3 Bu Kararın Önemi

Sprint 12C kararı, Property OS mimarisinin temelini oluşturur:

```
Property (Root)
    │
    ├── Workspace (1:1)
    │       └── Executions (1:N)
    │
    ├── Owner (1:N)
    │
    ├── Accounting (1:N)
    │
    ├── Keys (1:N)
    │
    ├── Documents (1:N)
    │
    ├── Listings (1:N)
    │       └── Channels (1:N per listing)
    │
    └── Reservations (1:N)
```

Bu yapı:
- Tekil kimlik (single source of truth)
- Tutarlı ownership zinciri
- Gelecekteki AI capability'ler için sağlam temel

---

## 14. SAAB Onayı İçin Sorular

1. **Workspace → Property 1:1 cardinality onaylanıyor mu?** ✅
2. **ON DELETE RESTRICT FK davranışı onaylanıyor mu?** ✅
3. **ilan_id kaldırılması (legacy cleanup) onaylanıyor mu?** ✅
4. **properties.workspace_id kaldırılması onaylanıyor mu?** ✅
5. **Canonical Ownership Rule onaylanıyor mu?** ✅
6. **Workspace Invariant (1 Property = 1 Active Workspace) onaylanıyor mu?** ✅
7. **Event Naming Policy onaylanıyor mu?** ✅

---

## 10. Önerilen Onay Sırası

```
Discovery          ████████████ 100%
Code References     ████████████ 100%
Proposal            ████████████ 100%
┌─ SAAB Review ────┐
│  Mimari karar     │  ⏳ BEKLENİYOR
│  Schema onayı     │
│  Risk değerlendirmesi │
└──────────────────┘
Implementation      ⏸️ 0%
Testing             ⏸️ 0%
Certification       ⏸️ 0%
```

---

**Proposal Durumu:** ✅ COMPLETE
**SAAB Onayı:** ⏳ BEKLENİYOR
