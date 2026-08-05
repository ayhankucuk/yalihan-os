# Sprint Charter — RESERVATION_CORE Phase 2

**Charter Tarihi:** 2026-08-05
**Hazırlayan:** WenOX
**Faz:** 2 — Availability Projection Hardening
**Önkoşul:** ✅ Phase 1 CLOSED (commit 0f3df35)
**SAAB Onayı:** ✅ APPROVED (2026-08-05)

---

## SAAB Resmi Kararı

| Alan | Değer |
|------|-------|
| Status | **APPROVED** |
| Mission | Availability Projection Hardening |
| Primary Goal | Deterministic, Idempotent, Replay-safe, Tenant-safe, Observable |
| Success Metric | Reservation lifecycle remains the single source of truth and every availability projection can be rebuilt without divergence. |

**OUT OF SCOPE:**
- Channel Manager
- Telegram migration
- Finance
- Pricing
- Conflict Engine UI

---

## Misyon

Rezervasyon lifecycle event'lerini PropertyAvailability projeksiyonuna bağlamak. Her rezervasyon durum değişikliği Availability tablosunu doğru şekilde günceller — leak yok, duplicate yok, tenant izolasyonu korunur.

---

## Phase 1'den Devralınan Durum

| Bileşen | Durum |
|---------|-------|
| PropertyReservation model | ✅ Canonical |
| ReservationState enum | ✅ 6 state |
| State transition methods | ✅ confirm/cancel/complete/markNoShow |
| IlanReservation | ✅ @deprecated |
| ReservationService | ✅ tenant_id auto-resolve |

---

## Phase 2 Kapsamı (P0 Priority)

### 2.1 Availability Leak Düzeltmesi

| Problem | Açıklama |
|---------|----------|
| **Leak-1** | `cancelReservation()` çağrıldığında PropertyAvailability kayıtları düzgün şekilde freed olmuyor |
| **Leak-2** | `confirmed_at` set ediliyor ama `cancelled_at` set edilmiyor on cancel |
| **Leak-3** | External source (Airbnb) kayıtları yanlışlıkla overwrite ediliyor |

### 2.2 Domain Event Zinciri

```
ReservationConfirmed → PropertyAvailabilityBlockedEvent
ReservationCancelled → PropertyAvailabilityUnblockedEvent
ReservationNoShow → PropertyAvailabilityUnblockedEvent
```

### 2.3 Idempotency

| Kriter | Açıklama |
|---------|----------|
| I1 | Aynı reservation_id ile birden fazla block oluşturulmamalı |
| I2 | Cancel idempotent olmalı — tekrar cancel çağrısı hata vermemeli |
| I3 | Replay sırasında duplicate kayıt oluşmamalı |

### 2.4 Tenant/Property Eşleşmesi

| Invariant | Açıklama |
|-----------|----------|
| T1 | PropertyAvailability.tenant_id = PropertyReservation.tenant_id |
| T2 | Cross-tenant availability erişimi engellenmeli |

### 2.5 Replay-Safe Projection

| Kriter | Açıklama |
|---------|----------|
| R1 | `rebuildAvailabilityProjection()` çağrıldığında mevcut internal kayıtları düzgün reset etmeli |
| R2 | External source kayıtları korumalı (source_system != 'internal') |
| R3 | Replay sonrası tüm confirmed rezervasyonlar için block olmalı |

### 2.6 Direct ORM Bypass Kontrolü

Tespit edilen bypass noktaları:
```
❌ IlanReservationService::create() — IlanReservation::create()
❌ IlanReservationService::cancel() — direkt update()
❌ TelegramAIBotService — direkt IlanReservation::find()
```

---

## Phase 2 Dışında Kalan (KESİNLİKLE YAPILMAZ)

| Yasak | Neden |
|-------|--------|
| ❌ Telegram Migration | Ayrı faz |
| ❌ Channel Manager | Kanal katmanı — Availability'e bağımlı |
| ❌ Airbnb Adapter | Dış sistem entegrasyonu |
| ❌ Booking Adapter | Dış sistem entegrasyonu |
| ❌ Pricing Engine | Finansal katman |
| ❌ Finance Refactor | Ayrı sprint |
| ❌ Conflict Engine UI | Availability'e bağımlı |

---

## P1: Gelecek Sprintlerde

| Konu | Not |
|------|-----|
| Projection Rebuild | Reservation History → Projection |
| Drift Detection | Reservation ↔ Availability mismatch raporu |

---

## Başarı Sorusu

**Phase 2 başarı sorusu:**

> Aynı reservation event'i yeniden işlendiğinde availability kayıtları çoğalmadan, iptal edildiğinde eksiksiz serbest bırakılarak ve tenant sınırları korunarak çalışıyor mu?

## Resmi Başarı Kriteri

Her reservation olayı (create, confirm, cancel, replay) PropertyAvailability üzerinde:

| Özellik | Açıklama |
|---------|----------|
| ✅ Deterministic | Aynı input her zaman aynı output |
| ✅ Idempotent | Aynı event 3 kere çalıştır → 1 kayıt |
| ✅ Replay-safe | Replay sırasında duplicate oluşmaz |
| ✅ Tenant-safe | Tenant A, Tenant B availability'sine dokunamaz |
| ✅ Observable | Drift/mismatch raporlanabilir |

---

## Mimari Kural

**Korunmalı:**
```
Reservation → Event → Projection Service → PropertyAvailability
```

**ASLA yapılmamalı:**
```
Reservation → PropertyAvailability::save()
```

Direct ORM write bypass kesinlikle yasak.

### Ölçülebilir Hedefler

| Kriter | Hedef |
|--------|--------|
| `ReservationConfirmed` → `PropertyAvailability` block | ✅ Tüm confirmed rezervasyonlar için block |
| `ReservationCancelled` → `PropertyAvailability` freed | ✅ Tüm cancelled rezervasyonlar için free |
| Cancel idempotency | ✅ Tekrar cancel hata vermemeli |
| Replay safety | ✅ Replay sonrası doğru projeksiyon |
| Tenant isolation | ✅ Cross-tenant erişim yok |

---

## Zorunlu Test Paketi

| Test | Kapsanan |
|------|----------|
| `creates_availability_block_once` | Deterministic |
| `confirm_twice_is_idempotent` | Idempotent |
| `cancel_releases_block` | Availability Release |
| `cancel_twice_is_safe` | Idempotent |
| `replay_does_not_duplicate_projection` | Replay-safe |
| `projection_rebuild_matches_runtime` | Deterministic |
| `tenant_cannot_touch_other_projection` | Tenant-safe |
| `availability_projection_matches_reservation` | Observable |
| `reservation_delete_rebuilds_projection` | Observable |
| `concurrent_confirm_produces_single_block` | Replay-safe |
| `concurrent_cancel_safe` | Idempotent |
| `drift_detector_detects_manual_changes` | Observable |

---

## Çıktılar

- [ ] Değişen dosyalar listesi
- [ ] Migration inventory
- [ ] Event contract dokümanı
- [ ] Test sonuçları
- [ ] Tenant isolation kanıtı

---

*Phase 2 SAAB onayından sonra implementasyona açılacaktır.*
