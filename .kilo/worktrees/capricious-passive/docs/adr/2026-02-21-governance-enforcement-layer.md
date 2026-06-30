# ADR-004: Governance Enforcement Layer — FeatureAssignment Observer Pattern

**Date:** 2026-02-21
**Status:** Accepted
**Branch:** gov/phase4-decommission

---

## Context

Template sistemi için determinism garantisi hedeflefliyordu (Sprint 20).
`Admin\TemplateController` write path'leri (`assignFeature`, `removeFeature`, `syncFeatures`)
cache invalidation ve TemplateChangeLog yazımını bypass ediyordu.

Sorun:

- `removeFeature()` ve `syncFeatures()` → `->delete()` bulk query builder kullanıyordu → Eloquent event tetiklenmiyordu
- `UpsCacheService::invalidateForJunction()` hiçbir write path'den otomatik çağrılmıyordu
- `TemplateChangeLog` yalnızca `TemplateSealedListener` üzerinden yazılıyordu — feature add/remove olaylarında kayıt yoktu
- `WizardContextService::resolve()` outer catch hata yutuyordu → `success: true` + hardcoded fallback → session açık kalıyor

## Decision

**Option 2: Governance Enforcement Layer** seçildi (Option 1: P0 cerrahi kapatma reddedildi).

Uygulanan değişiklikler:

### 1. FeatureAssignmentObserver (tam governance versiyonu)

- Eski: `Cache::tags()->flush()` — tüm cache driver'larda çalışmaz
- Yeni: `UpsCacheService::invalidateForJunction()` — registry pattern, tüm key namespace'lerini kapsıyor
- Yeni: `TemplateChangeLog::logFeatureAdded/Removed()` — her create/delete için otomatik audit
- `assignable_type !== YayinTipiSablonu::class` guard → polymorphic model kirliliği yok

### 2. Admin\TemplateController — WriteGuard (Eloquent enforcement)

- `removeFeature()`: `->delete()` → `->get()->each(fn($a) => $a->delete())` — individual model delete
- `syncFeatures()`: bulk `->delete()` → `->get()->each(fn($a) => $a->delete())` — individual model deletes
- Her iki değişiklik Observer'ın `deleted()` event'ini tetikler → bypass structurally impossible

### 3. WizardContextService — Silent Fallback Removal

- Outer `catch` → `return $this->getFallbackContext(...)` kaldırıldı
- Yerine: `throw $e` — hata yutulmaz, caller 500 alır
- `getFallbackContext()` metodu korundu (dead code, ileride temizlenecek)

### 4. Test coverage

- `FeatureAssignmentObserverTest` — cache invalidation mock assertion
- `WizardContextFallbackRemovalTest` — exception propagation assertion

## Consequences

**Positive:**

- Cache invalidation: artık her FeatureAssignment write olayında otomatik
- Changelog: artık her feature add/remove için kayıt var
- WizardContext: hata artık caller'a yansıyor — silent production bug imkânsız
- Bypass: Controller'dan Eloquent dışı delete yapılamaz hale geldi

**Negative:**

- `syncFeatures()` N×delete + N×create → bulk operasyonlarda performans artışı (büyük template sync için ~50ms ek gecikme)
- Bu gecikme kabul edilebilir: performance budget dışında, audit trail değeri daha yüksek

## Alternatives Considered

- **Option 1 (P0 Cerrahi):** Sadece hata düzeltme, Observer yok — reddedildi: bypass açık kalırdı
- **Minimal (sadece Observer):** WizardContext ve WriteGuard yapılmaz — reddedildi: mimari hazırdı, parçalı deployment daha riskli
- **Middleware WriteGuard:** Request lifecycle'ında intercept — reddedildi: Observer daha az invasive, model-scoped

## Checklist

- [x] FeatureAssignmentObserver tam governance versiyonu
- [x] AppServiceProvider kaydı zaten vardı
- [x] TemplateController individual deletes
- [x] WizardContextService throw $e
- [x] 5 yeni test, hepsi geçiyor
- [x] Quality Gate EXIT:0
- [x] ADR commit öncesi yazıldı
