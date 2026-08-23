# C4.2 — Channel Fee Double-Entry Ledger Accrual Implementation Charter

**Charter ID:** `C4_2_CHANNEL_FEE_LEDGER_CHARTER`  
**Certified Production Code Baseline:** `1a2e9cec7fdb5026a27e7d6928e469cba35832a8`  
**Discovery Reference:** `docs/discovery/C4_2_CHANNEL_FEE_LEDGER_DISCOVERY.md`  
**Target Milestone:** ERA-V Phase 2A — Monetization Core  
**Role:** Finance Engineering & Governance  
**Status:** PROPOSED / AWAITING SAAB DECISION GATE  

---

## 1. Hedef ve Kapsam (Objective & Scope)

`FinancialLedgerService` içerisinde doğrulanmış kanal komisyonlarının (`channel_fee_amount`) çift taraflı defter-i kebire (`ledger_entries`) otomatik ve atomik olarak tahakkuk ettirilmesi, ev sahibi net alacağının kanal kesintisi düşüldükten sonra tescil edilmesi.

---

## 2. Değişiklik Yapılacak Dosyalar (Affected Components)

1. **[`App\Services\FinancialLedgerService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/FinancialLedgerService.php):**
   - `recordOwnerPayableAccrual()` fonksiyonuna kanal komisyonu ayrıştırması (TX1) eklenmesi.
   - `Kanal Komisyon Yükümlülükleri Hesabı` tescili.
   - `reverseOwnerPayableAccrual()` fonksiyonuna kanal komisyonu simetrik ters kaydının eklenmesi.
2. **[`App\Services\Finance\PayoutReadinessService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/Finance/PayoutReadinessService.php):**
   - Kanal komisyonu defter kaydı (`has_channel_fee_ledger_entry`) varlık kontrolünün hakediş onayına bağlanması.
3. **[`tests/Feature/Finance/C4_2_ChannelFeeLedgerAccrualTest.php`](file:///Users/macbookpro/repos/yalihan-os/tests/Feature/Finance/C4_2_ChannelFeeLedgerAccrualTest.php):**
   - Uçtan uca çift taraflı tahakkuk, denge, iptal ters kaydı, replay idempotency ve tenant izolasyon testleri.

---

## 3. Kabul Kriterleri (Acceptance Criteria)

1. **Kanonik Matematiksel Ayrıştırma:**
   100.000 TL brüt, 15.500 TL doğrulanmış kanal ücreti ve %15 Yalıhan komisyonu olan bir rezervasyon tamamlandığında:
   - Kanal Komisyon Yükümlülükleri: 15.500 TL (CR)
   - Komisyon Gelirleri: 15.000 TL (CR)
   - Sahip Yükümlülükleri: 69.500 TL (CR)
   - Konaklama Gelirleri: -100.000 TL (DB)
2. **Defter Dengesi ($\Sigma \text{Debit} = \Sigma \text{Credit}$):**
   Tüm işlemler net $0.00$ bakiye invariant'ını sağlamalıdır.
3. **Güvenilmeyen Kaynak Koruması:**
   `channel_fee_source === UNKNOWN` veya doğrulanmamış tahmini kayıtlar için kanal tahakkuku yazılmaz, payout bloke kalır.
4. **Replay & Idempotency:**
   Job ikinci kez çalıştığında sıfır mükerrer satır üretilir.
5. **İptal & Simetrik Ters Kayıt:**
   İptal durumunda tüm 3 alt işlem ve ana işlem ters kayıtla kapatılır.

---

## 4. Sertifikasyon Planı

- [ ] Kanonik 100K / 15.5K / 15K / 69.5K testi
- [ ] $\Sigma \text{Debit} == \Sigma \text{Credit}$ invariant testi
- [ ] UNKNOWN kaynak tahakkuk engeli testi
- [ ] Replay / Idempotency sıfır mükerrer satır testi
- [ ] İptal durumunda simetrik ters kayıt testi
- [ ] Multi-tenant izolasyon testi
- [ ] C1–C4.1 regresyon testi

---

## 5. Nihai Karar

$$\mathbf{Status: \quad C4\_2\_CHARTER\_READY}$$
