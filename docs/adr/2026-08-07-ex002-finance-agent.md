# ADR: EX-002 Finance Agent

**Date:** 2026-08-07
**Status:** ACCEPTED
**Mission:** EX-002
**Branch:** feature/ex-002-finance-agent
**Author:** SAAB Board

---

## Context

YALIHAN'ın Airbnb operasyonlarında aylık payout süreci manuel yürütülmektedir. Airbnb her ay toplam ödemeyi platform üzerinden yapmasına rağmen, bu ödemenin villa bazında ayrıştırılması, YALIHAN komisyonunun hesaplanması ve ev sahibine net ödeme raporunun hazırlanması tamamen manueldir. Bu süreç hata riski taşımakta ve operatör zamanını tüketmektedir.

**Business Hypothesis:** Airbnb payout import, reconciliation, komisyon hesaplama ve owner payout preparation süreçlerini otomatikleştirerek manuel iş yükünü azaltmak ve BAI'yi +6% artırmak.

---

## Decision

`app/Domains/Finance/` altında yeni bir DDD domain'i inşa edilecek. Bu domain:

1. **AirbnbPayoutImport** — Airbnb'den gelen payout verisini import eden aggregate
2. **PayoutReconciliation** — Import edilen veriyi rezervasyonlarla eşleştiren aggregate
3. **OwnerPayout** — Ev sahibine ödenecek net tutarı hazırlayan aggregate

Tüm domain SAAB v8 kurallarına, DDD prensiplerine, CQRS pattern'ına ve tenant isolation gereksinimlerine uygun inşa edilecek.

---

## Domain Structure

```
app/Domains/Finance/
├── Aggregates/
│   ├── AirbnbPayoutImport.php
│   ├── PayoutReconciliation.php
│   └── OwnerPayout.php
├── ValueObjects/
│   ├── Money.php
│   ├── CommissionRate.php
│   └── PayoutPeriod.php
├── Services/
│   ├── PayoutReconciliationService.php
│   ├── CommissionCalculatorService.php
│   ├── AirbnbPayoutImportService.php
│   ├── OwnerPayoutPreparationService.php
│   └── FinanceAgentFeatureFlags.php
├── Events/
│   ├── AirbnbPayoutImported.php
│   ├── PayoutReconciled.php
│   └── OwnerPayoutPrepared.php
├── Listeners/
│   ├── ReconcilePayoutOnImport.php
│   └── PrepareOwnerPayoutOnReconciliation.php
├── Jobs/
│   ├── ProcessAirbnbPayoutJob.php
│   └── PrepareOwnerPayoutJob.php
├── DTOs/
│   ├── AirbnbPayoutImportDTO.php
│   └── OwnerPayoutDTO.php
└── Contracts/
    ├── PayoutImportSourceContract.php
    └── OwnerPayoutRepositoryContract.php
```

---

## Database

3 yeni tablo:

- `airbnb_payout_imports` — Import edilen ham payout kayıtları
- `payout_reconciliations` — Rezervasyon eşleştirme sonuçları
- `owner_payouts` — Ev sahibi ödeme hazırlıkları

Tüm tablolar `tenant_id` ile izole edilecek, idempotency key içerecek.

---

## Consequences

- ✅ Manuel payout hazırlama süreci otomasyona alınır
- ✅ Komisyon hesaplama hataları elimine edilir
- ✅ Ev sahibi net ödeme raporu otomatik üretilir
- ✅ Tüm işlemler audit trail ile kayıt altına alınır
- ✅ Admin onay akışı korunur (human-in-the-loop)
- ⚠️ Legacy `commissions` ve `transactions` tablolarıyla coexistence; migration sonrası deprecate edilecek

---

## Exit Criteria

- [ ] Tüm migration'lar çalışıyor
- [ ] Unit testler PASS
- [ ] Integration testler PASS
- [ ] Feature flag ile kontrollü aktif edilebiliyor
- [ ] Tenant isolation doğrulandı
- [ ] Admin approval panel çalışıyor
- [ ] Executive Report hazır
- [ ] CRS ≥ 90 hedefi karşılanıyor

---

## SAAB Constraints

- SAAB v8 uyumu zorunlu
- DDD + CQRS pattern'ı
- Thin Controllers — sıfır iş mantığı controller'da
- Replay-safe events
- Tenant isolation her seviyede
- No business logic in Jobs (delegate to Services)
- Silent catch yasak — her catch: log + rethrow
- env() app/ içinde yasak — config() kullan
- orderBy zorunlu her ->first() çağrısında
