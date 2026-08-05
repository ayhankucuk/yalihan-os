# Sprint Charter — RESERVATION_CORE Phase 2

**Charter Tarihi:** 2026-08-05
**Hazırlayan:** WenOX
**Faz:** 2 — Availability Projection Hardening
**Önkoşul:** ✅ Phase 1 CLOSED (commit 0f3df35)
**SAAB Onayı:** ⏳ Bekleniyor

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

## Phase 2 Dışında Kalan

| Konu | Neden |
|------|--------|
| Telegram migration | Ayrı faz |
| Channel Manager | Kanal katmanı |
| Airbnb/Booking adapter | Dış sistem entegrasyonu |
| Finance refactor | Finansal katman |
| Money Value Object | Ayrı sprint |
| Tam conflict engine | Conflict resolution Phase 3 |

---

## Başarı Sorusu

**Phase 2 başarı sorusu:**

> Aynı reservation event'i yeniden işlendiğinde availability kayıtları çoğalmadan, iptal edildiğinde eksiksiz serbest bırakılarak ve tenant sınırları korunarak çalışıyor mu?

## Başarı Kriteri

Sprint sonunda şunu söyleyebilmeliyiz:

> Her rezervasyon durum değişikliği PropertyAvailability projeksiyonuna doğru şekilde yansır; leak yok, duplicate yok, tenant izolasyonu korunur.

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
| `confirms_reservation_creates_availability_blocks` | T1, I1 |
| `cancels_reservation_frees_availability_blocks` | Leak-1, Leak-2 |
| `cancelling_already_cancelled_is_idempotent` | I2 |
| `no_show_frees_availability_blocks` | State transition |
| `replay_rebuilds_availability_from_reservations` | R1, R2, R3 |
| `replay_preserves_external_source_blocks` | R2 |
| `external_source_blocks_not_overwritten_by_cancel` | Leak-3 |
| `reservation_tenant_matches_availability_tenant` | T1, T2 |
| `cross_tenant_availability_access_blocked` | T2 |

---

## Çıktılar

- [ ] Değişen dosyalar listesi
- [ ] Migration inventory
- [ ] Event contract dokümanı
- [ ] Test sonuçları
- [ ] Tenant isolation kanıtı

---

*Phase 2 SAAB onayından sonra implementasyona açılacaktır.*
