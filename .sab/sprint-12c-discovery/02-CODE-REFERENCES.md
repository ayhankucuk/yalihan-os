# Sprint 12C Discovery — Kod Referans Analizi

**Tarih:** 2026-07-17
**Durum:** DISCOVERY COMPLETE

---

## 1. Legacy Alanlar İçin Kod Referansları

### 1.1 `property_workspaces.ilan_id` Referansları

| Dosya | Satır | Kullanım Tipi |
|-------|-------|----------------|
| `CapabilityRuntimeEngine.php` | 152, 155 | Workspace → Ilan erişimi |
| `WorkspaceDashboardController.php` | 57, 154, 182, 414, 417, 440 | UI'da Ilan bilgisi |
| `PropertyWorkspaceAggregate.php` | 73 | Aggregate state |
| `WorkspaceNextActionService.php` | 39, 86, 149 | Next action hesaplama |
| `WorkspaceTimelineService.php` | 62 | Timeline event |
| `WorkspaceHealthService.php` | 381, 387 | Health check |
| `WorkspaceExecutionService.php` | 51 | Execution context |
| `DriveWebhookService.php` | 400 | Webhook payload |
| `WorkspaceTimeline.php` | 115 | Timeline data |
| `PropertyScoreAgent.php` | 80-125 | AI agent context |
| `PublishingDecisionReady.php` | 42 | Event payload |
| `PropertyScoreCalculated.php` | 42 | Event payload |
| `DescriptionCompleted.php` | 42 | Event payload |
| `PhotoAnalysisCompleted.php` | 42 | Event payload |
| `PropertyWorkspaceCreated.php` | 67 | Event payload |

**Toplam:** 29 referans, 7 farklı dosya

### 1.2 `PropertyWorkspaceService::createWorkspace` İmzası

```php
public function createWorkspace(int $ilanId, string $intent, ?string $templateId = null): PropertyWorkspace
```

**Mevcut akış:**
1. `ilanId` alınır
2. `property_workspaces` tablosuna `ilan_id` olarak kaydedilir
3. Workspace → Ilan → Property zinciri kurulur

**Yeni akış gereksinimi:**
1. `propertyId` alınmalı (ilanId yerine)
2. `property_workspaces` tablosuna `property_id` olarak kaydedilmeli
3. `property_workspaces.property_id` FK ile `properties.id`'e bağlanmalı

### 1.3 `properties.workspace_id` Referansları

| Dosya | Satır | Kullanım |
|-------|-------|----------|
| `Property.php` | 86 | Model boot'ta invariant guard |

**Not:** Sadece model-level guard. Bu alan kullanılmıyor gibi görünüyor.

### 1.4 `ilanlar.workspace_id` Referansları

**Bulunamadı.** Bu alan hiç kullanılmıyor görünüyor.

---

## 2. Etkilenecek Servisler

### 2.1 PropertyWorkspaceService

```php
// MEVCUT
public function createWorkspace(int $ilanId, string $intent, ?string $templateId = null): PropertyWorkspace

// YENİ GEREKSİNİM
public function createWorkspace(int $propertyId, string $intent, ?string $templateId = null): PropertyWorkspace
```

### 2.2 Aggregate Güncelleme

`PropertyWorkspaceAggregate` `ilan_id`'yi state'ten kaldırmalı veya `property_id` eklemeli.

---

## 3. Event Payload Değişiklikleri

### 3.1 Etkilenen Event'ler

Tüm Workspace event'leri `ilan_id` yerine `property_id` taşımalı:

| Event | Payload Değişikliği |
|-------|---------------------|
| `PropertyWorkspaceCreated` | `ilan_id` → `property_id` |
| `PublishingDecisionReady` | `ilan_id` → `property_id` |
| `PropertyScoreCalculated` | `ilan_id` → `property_id` |
| `DescriptionCompleted` | `ilan_id` → `property_id` |
| `PhotoAnalysisCompleted` | `ilan_id` → `property_id` |

---

## 4. UI/Controller Etkisi

### 4.1 WorkspaceDashboardController

```php
// MEVCUT
$ilan = $workspace->ilan_id ? Ilan::find($workspace->ilan_id) : null;

// YENİ GEREKSİNİM
$ilan = $workspace->property_id
    ? $workspace->property->listings->first()
    : null;
// VEYA doğrudan listing_id gerekirse ayrı bir aggregate üzerinden
```

---

## 5. Migration Gereksinimleri

### 5.1 Tablo Değişiklikleri

```sql
-- 1. property_id kolonu ekle (ilan_id yanına, geçici)
ALTER TABLE property_workspaces
ADD COLUMN property_id BIGINT UNSIGNED NULL AFTER ilan_id;

-- 2. Backfill: Workspace → Property eşleştirmesi
-- (workspace_id veya mevcut mantık ile)

-- 3. UNIQUE constraint ekle
ALTER TABLE property_workspaces
ADD CONSTRAINT property_workspaces_property_id_unique UNIQUE (property_id);

-- 4. FK ekle
ALTER TABLE property_workspaces
ADD CONSTRAINT property_workspaces_property_id_foreign
FOREIGN KEY (property_id)
REFERENCES properties(id)
ON DELETE RESTRICT;

-- 5. ilan_id kolonunu kaldır (ayrı migration)
ALTER TABLE property_workspaces
DROP COLUMN ilan_id;
```

### 5.2 Tablo: properties

```sql
-- workspace_id kaldırılabilir veya legacy olarak bırakılabilir
-- (Çift yönlü FK oluşturmamak için kaldırılmalı)
ALTER TABLE properties
DROP COLUMN workspace_id;
```

---

## 6. Backfill Stratejisi

### 6.1 Mevcut Durum

- `property_workspaces`: 0 kayıt
- Risk: Çok düşük

### 6.2 Backfill Senaryoları

| Senaryo | Strateji |
|---------|----------|
| Yeni Workspace oluşturulurken | `ilanId` yerine `propertyId` kullanılacak |
| Mevcut Workspace yok | Backfill gerekmiyor |
| Event replay | Yeni `property_id` ile replay |

---

## 7. Test Etkisi

### 7.1 Etkilenecek Testler

| Test Dosyası | Tahmini Etki |
|--------------|--------------|
| PropertyWorkspacePublishServiceTest | Refactor gerekli |
| WorkspaceTimelineTest | Event payload güncelleme |
| PropertyWorkspaceServiceTest | createWorkspace imza değişikliği |

### 7.2 Yeni Test Senaryoları

| Test | Açıklama |
|------|-----------|
| Property 1:1 Workspace invariant | Her Property'nin max 1 Workspace'i olmalı |
| Workspace → Property traversal | Workspace üzerinden Property'ye ulaşılabilmeli |
| Cascade delete | Property silindiğinde Workspace korunmalı (RESTRICT) |

---

## 8. Özet

### 8.1 Değişiklik Kapsamı

| Kategori | Sayı |
|----------|------|
| Servis dosyası | 1 (`PropertyWorkspaceService`) |
| Controller | 1 (`WorkspaceDashboardController`) |
| Aggregate | 1 (`PropertyWorkspaceAggregate`) |
| Event sınıfı | 5 |
| Model | 2 (`PropertyWorkspace`, `Property`) |
| Migration | 2-3 |

### 8.2 Risk Değerlendirmesi

| Risk | Seviye | Mitigation |
|------|--------|------------|
| `ilan_id` referansları | 🟠 Orta | Tek tek güncelleme |
| Event backward compat | 🟠 Orta | Versioned event payload |
| UI breaking change | 🟡 Düşük | Kademeli rollout |

---

**Discovery Durumu:** ✅ COMPLETE
**Sonraki:** SAAB Proposal hazırlanacak
