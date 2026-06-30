# Domain Katmanı Konsolidasyon Raporu

> Sprint 2 sonucu — ADR-021 ile mühürlenmiş
> Son güncelleme: 2026-06-16 (Oturum 59)
> İlgili görev: #28

---

## Özet

`app/Domains/PropertySchema/` (eski DDD, 9 dosya) ile `app/Domain/` (yeni V3, 72 dosya) iki paralel namespace Sprint 2'de birleştirildi. `app/Domains/` dizini tamamen kaldırıldı. Tek namespace: `app/Domain/`.

**Commit:** `6909772`

---

## Nihai Domain Yapısı

```
app/Domain/
├── AI/              (9 dosya)   — AI kontrat ve enum'lar
├── Core/            (11 dosya)  — Bounded context, cache, security
├── CQRS/            (9 dosya)   — Aggregate root, event, projection
├── CRM/             (1 dosya)   — CRM read repository
├── Ilan/            (4 dosya)   — İlan domain yönetici, action, read repo
├── Kisi/            (3 dosya)   — Kisi domain yönetici, projection, read repo
└── PropertyHub/     (35 dosya)  — Property engine tam implementasyon
```

**Toplam:** 72 PHP dosyası, 7 domain

---

## Domain Detayları

### `Domain/AI/` — AI Kontrat Katmanı
Tüm AI provider'ları için interface ve enum tanımları. 54+ servis bu kontratları kullanıyor.

| Dosya | Rol |
|-------|-----|
| `Contracts/CortexServiceInterface.php` | YalihanCortex kontratı |
| `Contracts/AIProviderRouterInterface.php` | Provider yönlendirme kontratı |
| `Contracts/PromptInterface.php` | Prompt standart arayüzü |
| `Enums/AIProvider.php` | Ollama, DeepSeek, OpenAI, Gemini |
| `Enums/AITaskType.php` | Görev tipleri |
| `Enums/CortexCapability.php` | Cortex yetenekleri |
| `ValueObjects/ProviderScore.php` | Sağlayıcı skor value object |

### `Domain/Core/` — Çekirdek Altyapı
Tenant izolasyonu, cache ve güvenlik bounded context'leri.

| Dosya | Rol |
|-------|-----|
| `BoundedContextContract.php` | Tüm domain'ler için temel kontrat |
| `Cache/CacheIsolationContext.php` | Tenant-aware cache izolasyonu |
| `Security/GlobalHardlockManager.php` | Production lock yönetimi |
| `Security/SignatureSealEngine.php` | SAB imza mühür motoru |

### `Domain/CQRS/` — CQRS Altyapısı
Aggregate root, event dispatching ve projection yönetimi.

| Dosya | Rol |
|-------|-----|
| `AggregateRoot.php` | CQRS base aggregate |
| `Aggregates/IlanAggregate.php` | İlan aggregate |
| `Aggregates/KisiAggregate.php` | Kisi aggregate |
| `Aggregates/LeadAggregate.php` | Lead aggregate |
| `Messaging/EventDispatcher.php` | Domain event dispatch |
| `Projections/IlanProjectionHandler.php` | İlan projeksiyon işleyici |
| `Exceptions/CrossTenantAccessException.php` | Tenant ihlali exception |

### `Domain/Ilan/` — İlan Domain
İlan yazma işlemleri ve okuma repository'si.

| Dosya | Rol |
|-------|-----|
| `IlanDomainYonetici.php` | Domain yönetici (orchestrator) |
| `Actions/StoreIlanAction.php` | İlan oluşturma action |
| `Actions/UpdateIlanAction.php` | İlan güncelleme action |
| `Repositories/IlanReadRepository.php` | CQRS read tarafı |

> ⚠️ Write authority: `IlanCrudService` → `IlanRepository`. `Domain/Ilan/Actions/` read-model action'ları.

### `Domain/Kisi/` — Kişi Domain

| Dosya | Rol |
|-------|-----|
| `KisiDomainYonetici.php` | Domain yönetici |
| `Projections/KisiProjectionHandler.php` | Kişi projeksiyon |
| `Repositories/KisiReadRepository.php` | CQRS read tarafı |

### `Domain/CRM/` — CRM Domain
Şu an minimal — tek read repository.

| Dosya | Rol |
|-------|-----|
| `Repositories/LeadReadRepository.php` | Lead okuma repository'si |

### `Domain/PropertyHub/` — Property Engine (35 dosya)
En büyük ve en kompleks domain. Wizard şema çözümü, özellik yönetimi, chaos simülasyonu ve observability içerir.

| Alt Dizin | İçerik |
|-----------|--------|
| `Chaos/` | `ChaosModeService`, `ChaosSimulationService` — CHAOS_MODE=false prod guard zorunlu |
| `Engine/` | `V2TemplateResolutionEngineAdapter` (eski uyumluluk adaptörü) |
| `Events/` | `FeatureAssignedEvent`, `TemplateSealedEvent` |
| `Infrastructure/` | `YayinTipiSablonuSnapshotProvider` |
| `Listeners/` | Event listener'lar |
| `Observability/` | 6 servis: GovernanceEventCorrelation, DriftTelemetry, GovernanceExport vb. |
| `Resiliency/` | `CircuitBreaker` — PropertyHub için |
| `Resolution/` | Şema çözümleme servisleri |
| `Rules/` | İş kuralları |
| `Services/` | Domain servisleri |
| `ValueObjects/` | Value object'ler |

---

## Eski Yapı → Yeni Yapı (Sprint 2 Öncesi / Sonrası)

| Önce | Sonra | Durum |
|------|-------|-------|
| `app/Domains/PropertySchema/` (9 dosya) | `app/Domain/PropertyHub/` | ✅ Birleştirildi |
| `app/Domain/PropertyHub/` (35 dosya) | `app/Domain/PropertyHub/` | ✅ Korundu |
| `V2TemplateResolutionEngineAdapter` (köprü) | `Engine/Adapters/` altında kaldı | ⚠️ Hala mevcut |

> **Not:** `V2TemplateResolutionEngineAdapter` Sprint 2'de kaldırılmadı — bağımlı controller'lar var. Sprint 4'te temizlenecek.

---

## Açık Sorular

| # | Soru | Risk |
|---|------|------|
| #29 | `Domain/AI/Contracts` — 54 dosya bu kontratları kullanıyor, tümü audit edildi mi? | 🟠 |
| #30 | `Domain/PropertyHub/Observability/` — `governance_events` tablosuna yazıyor mu? Redis var mı? | 🟡 |
| #31 | `Domain/PropertyHub/Resiliency/CircuitBreaker` vs `Contracts/Resilience/CircuitBreakerInterface` — hangisi prod'da? | 🟡 |
| #32 | `Domain/PropertyHub/Chaos/` — `PROPERTYHUB_CHAOS_ENABLED=false` production'da doğrulandı mı? | 🟠 |

---

## İlgili Belgeler

- [`docs/adr/2026-06-15-sprint2-architecture-decisions.md`](../adr/2026-06-15-sprint2-architecture-decisions.md) — ADR-021
- [`docs/architecture/domains.md`](../architecture/domains.md) — Domain haritası
- [`docs/features/WIZARD_FLOW.md`](../features/WIZARD_FLOW.md) — Wizard zinciri (PropertyHub kullananlar)
- [`docs/known-debt.md`](../known-debt.md) — #28, #29, #30, #31, #32
