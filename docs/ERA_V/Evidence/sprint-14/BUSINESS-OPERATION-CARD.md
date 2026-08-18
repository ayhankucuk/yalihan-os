# Business Operation Card

**Sprint 14: Property Command Center — Unified Property Operations Dashboard**
**Date:** 2026-07-30

---

## Operation

```
Property Daily Operations Management
```

---

## Capability

```
Property Command Center — Single-Pane Property Operations Dashboard
```

---

## Trigger

```
User navigates to /admin/property/{id}/command-center
```

---

## Before (Baseline — Fragmented)

```
1. Admin paneline gir
2. Property'yi bul — ilanlar listesi
3. Rezervasyonlari gormek icin ayri sayfa ac
4. Airbnb durumunu gormek icin ayri sayfa ac (veya panele git)
5. Sahibinden durumunu gormek icin ayri sayfa ac
6. Son sync sonuclarini gormek icin log'lara bak
7. Tüm bilgileri mental olarak birlestir

Manuel adim: 7
Ortalama sure: ~12 dk
Insan mudahalesi: %100
```

---

## After — Sprint 14 Target

```
1. /admin/property/{id}/command-center ac
2. Tek ekranda tum bilgiler: kimlik, sahip, danisman, durum,
   müsaitlik, kanallar, rezervasyonlar, timeline, acik görevler
3. Operasyon baslat: "Yayinla", "Senkronize Et", "Rezervasyon Ekle"

Manuel adim: 7 → 2 (property sec + operasyon sec)
Otomatik adim: 0 → 5 (tüm bilgiler otomatik yuklenir)
Ortalama sure: ~12 dk → ~45 sn
```

---

## Automation Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  PropertyCommandAggregate                                      │
│  └── Data: property + owner + danisman + durum                │
│      + availability + reservations + listings                  │
│      + channel_status + operations + timeline                  │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  PropertyCommandController                                    │
│  └── GET /admin/property/{id}/command-center                  │
│      + GET /api/property/{id}/command/*                       │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  PropertyAvailabilityPanel (E02)                              │
│  └── Kanallardan: Airbnb, Sahibinden, Booking.com            │
│      + Son sync zamani + health status                       │
│      + Reservation listesi + Takvim gorunumu                  │
│      + [Rezervasyon Ekle] [Blok Ekle] [Senkronize Et]       │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  PropertyListingPanel (E03)                                  │
│  └── Yayinda mi? External ID? Last synced?                   │
│      + [Tumunu Yayinla] [Tumunu Kaldir]                    │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  PropertyTimelinePanel (E04)                                 │
│  └── Son 20 domain event + sync executions                  │
│      + Reservation history + Publication history             │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  Sprint 13 Channel Manager Integration                       │
│  └── AvailabilitySynchronizationService                       │
│      + ChannelSyncExecution                                  │
│      + AirbnbChannelAdapter                                  │
└──────────────────────────────────────────────────────────────┘
```

---

## Evidence

| Kanıt | Durum | Referans |
|-------|-------|----------|
| PropertyCommandAggregate | ⬜ | E01 |
| Availability Panel | ⬜ | E02 |
| Listing Panel | ⬜ | E03 |
| Timeline Panel | ⬜ | E04 |
| Command Actions | ⬜ | E05 |
| Feature Tests | ⬜ | E06 |
| Manual Verification | ⬜ | E06 |
| Sab:integrity-scan | ⬜ | E06 |

---

## BAI Summary

| Metrik | Baseline | Target | Sprint 14 Scope |
|--------|----------|--------|----------------|
| Manuel adim | 7 | 2 | ✅ |
| Ortalama sure | ~12 dk | ~45 sn | ✅ |
| Insan mudahalesi | %100 | ~20% | ✅ |
| Bilgi noktalari | 7 ayri sayfa | 1 tek sayfa | ✅ |
| Operasyon baslatma | Manuel | 1 tik | ✅ |

---

## Sprint 13 Integration

Sprint 14, Sprint 13 Channel Manager yeteneklerini gorunur yapar:

| Sprint 13 Capability | Sprint 14 Kullanim |
|--------------------|--------------------|
| ChannelSyncExecution | E04 — sync history timeline |
| AvailabilitySyncAggregate | E02 — müsaitlik paneli |
| AirbnbChannelAdapter | E02 — "Senkronize Et" butonu |
| IlanTakvimSync | E02 — kanal eslestirme |

---

## S13-CD Debt in Sprint 14 Context

| Debt ID | Konu | Sprint 14'daki Etkisi |
|---------|------|----------------------|
| S13-CD-001 | 4 skipped tests | E02 test yazarken MySQL gerekecek |
| S13-CD-002 | Airbnb API yok | Airbnb paneli "Sandbox" modunda, tiklanabilir ama gercek update yok |
| S13-CD-003 | Production BAI yok | Property Command Center'dan internal timing olculebilir |
