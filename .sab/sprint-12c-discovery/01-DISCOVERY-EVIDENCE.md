# Sprint 12C Discovery Evidence

**Oturum:** 114 (devam)
**Tarih:** 2026-07-17
**Agent:** Kilo (Claude Opus 4.8)
**Durum:** DISCOVERY IN PROGRESS

---

## 1. Mevcut Durum Analizi

### 1.1 Tablo Yapısı

```
property_workspaces
├── id (PK)
├── tenant_id
├── ilan_id (nullable, FK YOK)
├── workspace_uuid (unique)
├── intent
├── template_id
├── state
├── created_at
├── updated_at
└── deleted_at

ilanlar
├── id (PK)
├── property_id (→ properties.id, FK VAR ✅)
├── workspace_id (nullable, FK YOK)
└── ...

properties
├── id (PK)
├── tenant_id
├── workspace_id (nullable)
├── uuid
├── canonical_reference
├── lifecycle_state
└── ...
```

### 1.2 Foreign Key Durumu

| Kaynak Tablo | Kolon | Hedef Tablo | FK Var mı? |
|--------------|-------|-------------|-------------|
| ilanlar | property_id | properties | ✅ EVET |
| ilanlar | workspace_id | property_workspaces | ❌ HAYIR |
| property_workspaces | ilan_id | ilanlar | ❌ HAYIR |

### 1.3 Veri Durumu

| Tablo | Kayıt | Not |
|-------|-------|-----|
| property_workspaces | 0 | Boş tablo |
| ilanlar | 2 | İkisi de property_id ile maplı |
| properties | 2 | Sprint 12B ile oluşturuldu |

---

## 2. Temel Mimari Soru

### 2.1 Domain Model İlişkisi

**Soru:** PropertyWorkspace'in canonical sahibi kim olmalı?

**Mevcut State:**
```
Option A: Workspace → Listing → Property
          (property_workspaces.ilan_id → ilanlar.id → ilanlar.property_id → properties.id)

Option B: Workspace → Property → Listing
          (property_workspaces.ilan_id → ilanlar.id)
          (YENİ: property_workspaces.property_id → properties.id)
```

### 2.2 İlişki Diagramı

```
                    ┌─────────────────┐
                    │    Property     │
                    │   (Canonical   │
                    │   Aggregate)   │
                    └────────┬────────┘
                             │
                             │ 1:N
                             ▼
                    ┌─────────────────┐
                    │     Ilan       │
                    │   (Listing)   │
                    └────────┬────────┘
                             │
                             │ 1:N (olası)
                             ▼
                    ┌─────────────────┐
                    │PropertyWorkspace│
                    │   (Publish      │
                    │   Context)      │
                    └─────────────────┘
```

---

## 3. Olası Tasarım Seçenekleri

### Option A: Workspace → Listing (Mevcut)

```
property_workspaces
├── ilan_id → ilanlar.id (nullable FK)
└── (İsteğe bağlı property_id eklenebilir)

ilanlar
└── property_id → properties.id (Sprint 12B ✅)
```

**Avantaj:**
- Mevcut yapıya minimum değişiklik
- Workspace bir Listing'e bağlı

**Dezavantaj:**
- Bir Property'nin birden fazla Listing'i olabilir
- Workspace hangi Listing'e bağlı? İlkine? Tümüne?

### Option B: Workspace → Property (Önerilen)

```
property_workspaces
├── property_id → properties.id (nullable FK)
└── ilan_id → ilanlar.id (nullable, backward compat)

properties
└── workspace_id → property_workspaces.id (nullable, 1:1)
```

**Avantaj:**
- Workspace canonical olarak Property'ye bağlı
- Property birden fazla Listing'e sahip olabilir
- Listing'ler Workspace context'ini Property üzerinden paylaşır

**Dezavantaj:**
- Schema değişikliği gerektirir
- Migration ve backfill gerekli

---

## 4. Impact Analysis

### 4.1 Service Katmanı Etkisi

| Service | İlişki Tipi | Etki |
|---------|-------------|------|
| PropertyWorkspaceService | Model kullanıyor | Review gerekli |
| PropertyCrudService | workspace_id alanı var | Değişiklik gerekebilir |
| WorkspaceSummaryService | ilan_id üzerinden erişiyor | Bakılmalı |

### 4.2 Event/Aggregate Etkisi

| Aggregate | State | Etki |
|-----------|-------|------|
| PropertyWorkspaceAggregate | Mevcut | Değişiklik yok |
| PropertyAggregate | workspace_id alanı var | Review gerekli |

### 4.3 Test Etkisi

| Test | Durum | Not |
|------|-------|-----|
| PropertyWorkspacePublishServiceTest | ⏳ Bulunamadı | Konum doğrulanmalı |
| PropertyAggregateTest | ✅ 13/13 PASS | Etkilenmez |

---

## 5. Discovery Soruları

### 5.1 Mimari Sorular

1. **Workspace'in amacı nedir?**
   - Bir Property için publishing context mi?
   - Bir Listing için editing workspace mı?
   - Yoksa her ikisi için mi?

2. **Bir Property kaç Workspace'e sahip olabilir?**
   - 1:1 mi?
   - 1:N mi?

3. **Workspace silindiğinde ne olmalı?**
   - Listing'ler silinmeli mi?
   - Sadece workspace association kalkmalı mı?

### 5.2 Geçiş Stratejisi Soruları

1. **Mevcut veri var mı?**
   - `property_workspaces` tablosu: 0 kayıt
   - Migration riski: Düşük

2. **Backward compatibility gerekli mi?**
   - `ilan_id` kolonu korunmalı mı?
   - Yoksa tamamen `property_id`'ye geçilmeli mi?

---

## 6. Sonraki Adımlar

1. [ ] Workspace'in asıl kullanım senaryosunu anlama
2. [ ] Property → Workspace cardinality kararı (1:1 veya 1:N)
3. [ ] FK constraint tasarımı
4. [ ] Migration strategy
5. [ ] SAAB Proposal taslağı

---

**Discovery Durumu:** 🟡 IN PROGRESS
**SAAB Proposal:** ⏳ HAZIRLANACAK
