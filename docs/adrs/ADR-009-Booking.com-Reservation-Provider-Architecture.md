# ADR-009: Booking.com Reservation Provider Architecture

**Status:** ACCEPTED (SAAB 2026-08-11, Auth clarification incorporated)
**Date:** 2026-08-11
**Baseline:** 683931d
**Deciders:** SAAB Board
**Supersedes:** ADR-006 §3 (BookingChannelAdapter disabled stub provision)

---

## Context

CHANNEL_MANAGER_PROVIDER Wave 1–3 (Channex) webhook/push modeliyle tamamlandı.
Booking.com production entegrasyonu başlamadan önce 3 kritik mimari karar dondurulmalı.

### Mimari Problemler

1. **Provider protocol farkı**: Channex push/webhook modeli Booking.com'da çalışmaz.
   Booking.com retrieval + acknowledgement modeli kullanır — Yalıhan API'ye göre polling yapar.

2. **Explicit acknowledgement invariant**: Channex'de acknowledgement implicit (200 OK).
   Booking.com'da canonical commit SONRASI explicit acknowledgement gerekir.
   Bu sıralama bozulursa double-booking riski doğar.

3. **Contract scope ayrımı**: `ChannelSyncContract` yalnızca availability sync içindir.
   Reservation lifecycle ayrı contract gerektirir — gelecek provider'lar (Expedia, vrbo)
   farklı protokol kullanabilir (webhook, polling, streaming).

4. **Auth mekanizması**: `IlanTakvimSync`'teki `api_key`/`api_secret`
   credential-based auth, Booking.com tarafından **31 Aralık 2025**'te sunset ediliyor.

---

## Decision

### 1. Provider Protocol Model — Retrieval + Polling

> Booking.com, Yalıhan'ın periyodik olarak rezervasyon verisi çekmesini bekler.
> Bu model webhook/push modelinden temelden farklıdır.

```
Booking.com Provider Architecture
───────────────────────────────────────────────────────────────────────

  ┌─────────────────────────────────────────────────────────────────┐
  │                     YALIHAN SYSTEM                               │
  │                                                                 │
  │   BookingReservationRetrievalJob (scheduled: ~20s)                │
  │          │                                                       │
  │          ▼                                                       │
  │   BookingConnectivityTransport                                    │
  │     GET /reservations?created_after=...&hotel_id=...            │
  │          │                                                       │
  │          ▼                                                       │
  │   BookingReservationDTO[]  ← Normalize provider payload           │
  │          │                                                       │
  │          ▼                                                       │
  │   ChannelReservationContract  ← Canonical interface               │
  │          │                                                       │
  │          ▼                                                       │
  │   BookingReservationIngestService                                 │
  │          │                                                       │
  │          ▼                                                       │
  │   ReservationService.createReservation()  ← Canonical write       │
  │          │                                                       │
  │          ▼                                                       │
  │   DB COMMIT  ✓                                                  │
  │          │                                                       │
  │          ├──────────────────────────────────┐                   │
  │          │                                  │                   │
  │          ▼                                  ▼                   │
  │   BookingAcknowledgementService      Event Dispatch               │
  │   POST /reservations/{id}/ack        (ChannexReservation*Event) │
  │   (only after DB commit success)                                 │
  │                                                                 │
  │   ── Stale / Out-of-Order ──                                   │
  │   If transaction is stale or not latest:                        │
  │     → Re-fetch latest message → Retry acknowledgement            │
  │     → DO NOT acknowledge outdated transaction                    │
  │                                                                 │
  │   ── Failure → No acknowledgement ──                             │
  │   If DB commit fails:                                          │
  │     → NO acknowledgement sent                                   │
  │     → Booking.com will retry on next poll                       │
  │     → Recovery window preserved                                 │
  └─────────────────────────────────────────────────────────────────┘
```

**Retrieval frequency:**
- New reservations: ~20 saniye (Booking.com önerisi)
- Recovery check: ~30 dakika (Booking.com önerisi)

---

### 2. Canonical Acknowledgement Ordering Invariant

> **Booking acknowledgement MUST occur only after successful canonical persistence.**
> Bu değişmez herhangi bir koşulda ihlal edilemez.

```
SUCCESS PATH:
  fetch() → normalize() → canonicalCommit() → DB OK → acknowledgement() → DONE

FAILURE PATH:
  fetch() → normalize() → canonicalCommit() → DB FAIL → NO acknowledgement
                                                           ↑
                                                  Booking.com retry bekler

STALE PATH:
  fetch() → detect stale/out-of-order → re-fetch latest → canonicalCommit() → ack()
```

**Neden önemli:**
- Booking.com yanlış veya artık latest olmayan transaction'ı acknowledge ederse hata alır
- Out-of-order modification/cancellation acknowledgement'ı invalid state'e kilitler
- Commit başarısızken acknowledgement yapılırsa: double booking riski

**Sağlayıcı:** `BookingAcknowledgementService` — commit sonrası çağrılır, commit başarısızsa çağrılmaz.

---

### 3. Contract Architecture — Two Separate Contracts

> `ChannelSyncContract` availability sync içindir — reservation ile şişirilmez.
> Gelecek provider'lar (Expedia webhook, vrbo streaming) farklı protokol kullanabilir.

```
┌─────────────────────────────────────────────────────────────┐
│                 CHANNEL SYNC CONTRACT                        │
│                                                             │
│  Canonical Availability Sync Interface                        │
│  ├── pushAvailability()    Yalıhan → Channel (EXPORT)      │
│  ├── pullAvailability()    Channel → Yalıhan (IMPORT)       │
│  ├── testConnection()                                        │
│  └── getChannel()                                          │
│                                                             │
│  Scope: Availability / Rates / Calendar                      │
│  Providers: Airbnb (Channex), Booking (Connectivity API)    │
└────────────────────────────┬────────────────────────────────┘
                             │
                             │ distinct contracts
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              CHANNEL RESERVATION CONTRACT                    │
│                                                             │
│  Canonical Reservation Lifecycle Interface                    │
│  ├── retrieveNew()        Fetch new reservations             │
│  ├── retrieveModified()   Fetch reservation changes         │
│  ├── retrieveCancelled()  Fetch cancellations               │
│  ├── acknowledge()        Confirm to provider (post-commit)  │
│  └── testConnection()                                       │
│                                                             │
│  Scope: Reservation inbound lifecycle                        │
│  Protocol: Provider-specific (polling, webhook, streaming)  │
│                                                             │
│  IMPLEMENTATION NOTE:                                       │
│  acknowledge() is intentionally NOT in ChannelSyncContract.  │
│  Acknowledgement belongs to reservation lifecycle,            │
│  and MUST fire only after canonical persistence success.   │
└─────────────────────────────────────────────────────────────┘

Provider Adapter (Booking-specific):
  BookingConnectivityAdapter
      │
      ├── BookingConnectivityTransport  ← HTTP + Auth
      │      └── GET /reservations
      │      └── POST /reservations/{id}/ack
      │
      └── BookingReservationDTO        ← Provider → Canonical mapper
```

**Provider-specific transport** (`BookingConnectivityTransport`) adapter içinde kalır.
`ChannelReservationContract` provider-agnostic kalır.

---

### 4. Authentication — Token-Based (No Legacy Credentials)

> Legacy Basic / Credential-based auth kullanılmayacaktır.
> Booking.com'un ilgili API'leri için güncel desteklenen token mekanizması kullanılacaktır.

**Neden:** `api_key`/`api_secret` credential-based auth, Booking.com tarafından **31 Aralık 2025**'te sunset ediliyor.

**`IlanTakvimSync` yeni alanları:**

```php
// YalıhanCredential → OAuth2 Token migration
IlanTakvimSync::$fillable[] = 'token_access';
IlanTakvimSync::$fillable[] = 'token_refresh';
IlanTakvimSync::$fillable[] = 'token_expires_at';
```

**Kullanılmayacak:**
```php
// SUNSET — 2025-12-31 sonrası kullanılmaz
'ilan_takvim_sync' => ['api_key', 'api_secret']
```

**OAuth flow yönü:** Yalıhan → Booking.com'a token-based API erişimi (two-legged machine account flow).

**Auth tipi — CLOSED (SAAB 2026-08-11):**
> Booking.com Connectivity API, **Machine Account** için **two-legged token-based authentication** kullanır:
> - Authorization Code KULLANILMAZ (kullanıcı etkileşimi gerektirir)
> - Client ID + Client Secret → Token Exchange → ~1 saatlik Access Token
> - Token refresh: client credentials yeniden exchange
>
> ```
> Booking.com Connectivity Auth — Machine Account
> ─────────────────────────────────────────────────
> Client ID + Client Secret          ← environment/hardcoded
>        ↓
> POST /oauth/tokens               ← token exchange
>        ↓
> Short-lived Access Token         ← ~1 hour expiry
>        ↓
> Connectivity API                ← reservation/availability calls
> ```

**Production-readiness checklist:** Recovery API (missed reservations) Booking.com tarafında varsayılan **kapalıdır** — Connectivity Support'un aktive etmesi gerekir.

**Karar kodu:** `CHANNEL_MANAGER_BOOKING_DEBT-002`

---

### 5. Stale / Out-of-Order Message Handling

> Booking.com bir transaction'ın artık latest olmadığını belirtebilir.
> Bu durumda o transaction acknowledge edilmez — latest re-fetch edilir.

```
Booking.com semantics:
- Her reservation transaction'ın latest flag'i vardır
- latest=false ise: başka transaction onu supersede etmiş
- latest=false transaction acknowledge edilirse → HATA

Yalıhan response:
- latest=false → transaction atılır, sonraki retrieval'da yeniden dene
- acknowledged_at set edilmiş → skip (idempotent)
- HATA aldıysan → transaction stale, re-fetch
```

---

### 6. Reservation Recovery

> Booking.com başarısız acknowledgement'ları recovery mekanizmasına taşır.
> Yalıhan periyodik recovery kontrolü yapar.

```
Senaryo: Yalıhan offline → mesaj kaçırdı
1. Booking.com → reservation mesajı gönderdi
2. Yalıhan ulaşamadı → acknowledgement yok
3. Booking.com → recovery kuyruğuna ekler

Yalıhan Recovery:
- Periyodik recovery check job (~30 dakika)
- Booking.com Recovery API çağırır
- Kaçırılan rezervasyonları alır → canonical commit → ack
```

---

## Consequences

### Positive

- Reservation lifecycle açıkça ayrı contract ile tanımlı — gelecek provider eklemesi kolay
- Acknowledgement invariant kodda değil, mimari katmanda — yanlışlıkla ihlal edilemez
- Auth credential sunset öncesi doğru yönde başlanmış olur
- Stale/out-of-order koruması explicit — Booking.com API semantics ile uyumlu

### Negative

- İki ayrı contract bakım yükü — ama provider coupling'i azaltır
- Polling-based retrieval webhook'dan farklı scheduler/job altyapısı gerektirir
- Recovery job ayrı implementasyon gerektirir

### Risks

| Risk | Mitigation |
|------|------------|
| Polling frequency rate limit aşımı | Exponential backoff + Batch retrieval |
| Token refresh downtime | Background refresh before expiry |
| Recovery job gecikmesi | 30 dakika max RTO |
| Duplicate acknowledgement (retry storm) | Idempotent ack + DB dedup |

---

## References

- Booking.com Connectivity API Documentation (2026)
- ADR-006: Channel Manager Provider Architecture
- ADR-007: Channex Webhook Ingest
- ADR-008: Channex Reservation Lifecycle
- CHANNEL_MANAGER_BOOKING_DEBT-001: Production Implementation
- CHANNEL_MANAGER_BOOKING_DEBT-002: Token Auth Enforcement

---

## Open Questions

| # | Soru | Durum |
|---|------|--------|
| ~~1~~ | ~~OAuth2 flow tipi~~ | ✅ **CLOSED** — two-legged machine account, authorization_code DEĞİL |
| 2 | Token storage: encrypted field vs dedicated credential service? | Wave 1/2 |
| 3 | Retrieval batch size: max reservations per request? | Wave 1/2 (Booking.com 10–200 aralığını destekliyor) |
| 4 | Acknowledgement timeout: retry window? | Wave 2 |
