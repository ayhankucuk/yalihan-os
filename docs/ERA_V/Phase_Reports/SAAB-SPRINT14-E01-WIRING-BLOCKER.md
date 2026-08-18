# SAAB Mimari Değerlendirme — Sprint 14 E01 (Güncelleme)

**Tarih:** 2026-07-30
**Epic:** E01 — Property Command Center Application Wiring
**Branch:** foamy-fire
**Kaynak:** Feature branch karşılaştırması

---

## Kritik Bulgu: Bağımlılık Zinciri Tespit Edildi

E01'in view skeleton'ı çalışabilmesi için gereken ancak **foamy-fire branch'inde mevcut OLMAYAN** bileşenler:

### Missing Models (foamy-fire'da yok, feature branch'de var)

| Model | Kullanım | Öncelik |
|-------|----------|---------|
| `Property.php` | Aggregate root — PCC'nin primary entity'si | 🔴 Zorunlu |
| `WorkforceExecution.php` | Execution history — Executions tab | 🔴 Zorunlu |
| `CommercialOffering.php` | Commercial tab | 🟡 Gerekli |
| `PropertyAvailabilityBlock.php` | Availability tab | 🟡 Gerekli |
| `PropertyWorkspace.php` | Workspace ilişkisi | 🟡 Gerekli |

### Missing Migrations (foamy-fire'da yok, feature branch'de var)

| Migration | Açıklama |
|-----------|----------|
| `2026_07_06_000001_create_property_workspaces_table.php` | Property ↔ Workspace junction |
| `2026_07_15_000002_add_property_and_workspace_to_ilanlar.php` | Ilan → Property FK |
| `2026_07_15_232856_create_workforce_executions_table.php` | Workforce execution log |
| `2026_07_25_000001_create_commercial_offerings_table.php` | Commercial offering tablosu |
| `2026_07_25_000005_create_property_availability_blocks_table.php` | Availability blocks |

### Feature Branch Mirası Analizi

Feature branch (`feature/sprint-19-unified-calendar-core`) — Sprint 12 Property Knowledge Graph altyapısını içeriyor:

```
Sprint 12: Property Knowledge Graph
    ├── Property aggregate (canonical reference)
    ├── Ilan → Property ilişkisi
    ├── Workspace ↔ Property junction
    └── Property availability

Sprint 13: Channel Manager
    ├── ChannelSyncExecution
    ├── AvailabilitySynchronizationService
    └── AvailabilitySyncAggregate

Sprint 14 E01: Property Command Center
    ├── PropertyCommandCenterController ← foamy-fire'da YOK
    ├── PropertyCommandCenterQueryService ← foamy-fire'da YOK
    ├── Property model ← foamy-fire'da YOK
    └── WorkforceExecution model ← foamy-fire'da YOK
```

**Sonuç:** E01 view skeleton'ı, Sprint 12 Property Knowledge Graph altyapısını gerektiriyor. Bu altyapı foamy-fire'da mevcut değil.

---

## SAAB Kararı: E01 İkiye Ayrıldı

| Bileşen | Durum | Not |
|---------|-------|-----|
| E01.1 **UI Foundation** | ✅ Tamamlandı | 853 satır view skeleton |
| E01.2 **Application Wiring** | ❌ Bloke | Property + WorkforceExecution + migrations gerekli |
| E01.3 **Baseline Test** | ❌ Bloke | Model dependency'ler olmadan çalışmaz |

---

## Mimari Seçenekler

### Seçenek A: Migration Backport (Önerilen)
Feature branch'deki migrations + modeller foamy-fire'a taşınır. Bu, Sprint 12 Property Knowledge Graph'in geriye dönük uyumluluğunu test eder.

**Artıları:**
- foamy-fire Sprint 12 mirasını devralır
- Test altyapısı tam çalışır
- E02, E03, E04 aynı branch üzerinde geliştirilebilir

**Eksileri:**
- Büyük backport riski
- Veritabanı uyumsuzluğu potansiyeli

### Seçenek B: Feature Branch'e Devam
foamy-fire "view-only" branch olarak bırakılır. Controller, service ve test geliştirmesi `feature/sprint-19-unified-calendar-core` üzerinde yapılır.

**Artıları:**
- Feature branch zaten tüm modellere sahip
- Daha az backport riski
- Daha hızlı ilerleme

**Eksileri:**
- foamy-fire branch'i yarım kalır
- İki branch arasında "view drift" riski

### Seçenek C: Minimal Foamy-Fire Wiring
Sadece en zorunlu modeller taşınır (Property + migration). Diğerleri `markTestSkipped` ile atlanır.

**Artıları:**
- En az backport riski
- foamy-fire hızla wiring yapılır

**Eksileri:**
- Test kapsamı düşer
- E02/E03/E04 aynı branch üzerinde yine bloke olabilir

---

## Önerilen Yol: Seçenek A

E01'in tamamlanması için aşağıdaki backport sırası önerilir:

```
1. Migration: create_property_workspaces_table
2. Migration: add_property_and_workspace_to_ilanlar
3. Migration: create_workforce_executions_table
4. Model: Property.php + PropertyFactory.php
5. Model: WorkforceExecution.php + WorkforceExecutionFactory.php
6. Migration: create_commercial_offerings_table
7. Model: CommercialOffering.php
8. Controller: PropertyCommandCenterController
9. Routes: PCC route tanımı
10. Baseline test: PCC açılış testi
```

---

## Sonraki Adım

SAAB'a hangi seçeneğin tercih edildiği sorulmalı:
- **Seçenek A** → Migration + model backport (en kapsamlı ama en riskli)
- **Seçenek B** → Feature branch'e devam (en güvenli ama foamy-fire yarım kalır)
- **Seçenek C** → Minimal wiring (denge)

**Bu karar alınmadan E01 Application Wiring başlatılamaz.**
