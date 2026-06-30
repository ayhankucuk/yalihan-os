# RISK PRIORITY MATRIX — SAB v6.1
## Yalıhan Emlak AI OS — Chief Enterprise Architect
**Tarih:** 2026-06-29
**Versiyon:** v1.0
**Kural:** Yalnızca VERIFIED veya PARTIALLY_VERIFIED bulgular.

---

## MATRIS GENEL GÖRÜNÜMÜ

```
                         OLASILIK
                    Düşük    Orta    Yüksek
              ┌──────────┬──────────┬──────────┐
       Yüksek │          │   P1     │  ● P0    │
  ETKI       │          │          │  G01     │
              │          │          │  ● P0    │
              │          │          │  G02     │
              ├──────────┼──────────┼──────────┤
       Orta   │   P3     │   P2     │   P1     │
              │          │          │          │
              │          │          │          │
              ├──────────┼──────────┼──────────┤
       Düşük  │          │          │   P2     │
              │          │          │          │
              │          │          │          │
              └──────────┴──────────┴──────────┘
```

### Matris Kuralı
- **P0**: CRITICAL etki + herhangi bir olasılık → Derhal
- **P1**: HIGH etki + yüksek/orta olasılık → Bu sprint
- **P2**: MEDIUM etki + yüksek/orta olasılık → Sonraki sprint
- **P3**: Düşük etki veya düşük olasılık → Planlı sprint

---

## DETAY MATRIS

### P0 — ÜRETIM KRITIK

| ID | Bulgu | Etki | Olasılık | Doğrulama | Risk Skoru | Tahmini |
|----|-------|------|----------|-----------|-----------|--------|
| **P0-G01** | `after_commit: false` (queue.php) | **CRITICAL** | Yüksek | VERIFIED | 🔴 25 | 1 saat |
| **P0-G02** | Token expiration `null` (sanctum.php) | **CRITICAL** | Yüksek | VERIFIED | 🔴 25 | 30 dakika |
| **P0-G03** | V2 IlanController IDOR (show()) | **CRITICAL** | **Orta** | VERIFIED | 🟠 20 | 2 saat |

**P0 toplam: 3 item — Derhal müdahale (toplam ~3.5 saat)**

---

### P1 — YÜKSEK

| ID | Bulgu | Etki | Olasılık | Doğrulama | Risk Skoru | Tahmini |
|----|-------|------|----------|-----------|-----------|--------|
| **P1-G04** | Transactional Outbox eksikliği | HIGH | **Orta** | VERIFIED | 🟠 16 | 8 saat |
| **P1-G05** | ListingProjector try/catch yok | HIGH | **Orta** | VERIFIED | 🟠 16 | 2 saat |
| **P1-G06** | LeadProjector try/catch yok | HIGH | **Orta** | VERIFIED | 🟠 16 | 2 saat |
| **P1-G07** | PhotoService file/DB ordering | HIGH | **Orta** | VERIFIED | 🟠 16 | 3 saat |
| **P1-G08** | FavoriService tenant isolation | HIGH | **Düşük** | VERIFIED | 🟡 9 | 2 saat |
| **P1-G09** | Token revocation mekanizması yok | HIGH | **Düşük** | VERIFIED | 🟡 9 | 3 saat |

**P1 toplam: 6 item — Bu sprint (toplam ~20 saat)**

---

### P2 — ORTA

| ID | Bulgu | Etki | Olasılık | Doğrulama | Risk Skoru | Tahmini |
|----|-------|------|----------|-----------|-----------|--------|
| **P2-G01** | Dual AiBudgetGuard double-spend | MEDIUM | **Orta** | VERIFIED | 🟡 9 | 3 saat |
| **P2-G02** | CircuitBreaker Cache durability | MEDIUM | **Orta** | PARTIAL | 🟡 6 | 4 saat |
| **P2-G03** | Chat API rate limit / auth yok | MEDIUM | **Yüksek** | VERIFIED | 🟠 12 | 1 saat |
| **P2-G04** | TKGM shared secret auth | MEDIUM | **Orta** | VERIFIED | 🟡 9 | 3 saat |
| **P2-G05** | FinanceProcessor AI timeout yok | MEDIUM | **Orta** | PARTIAL | 🟡 6 | 4 saat |
| **P2-G06** | SyncListingProjectionJob config eksik | MEDIUM | **Orta** | VERIFIED | 🟡 9 | 2 saat |
| **P2-G07** | CQRS drift silent swallow | MEDIUM | **Düşük** | PARTIAL | ⚪ 3 | 4 saat |
| **P2-G08** | DLQ routing disconnected | MEDIUM | **Düşük** | VERIFIED | ⚪ 4 | 2 saat |
| **P2-G09** | OwnerMesajController cross-tenant | MEDIUM | **Düşük** | PARTIAL | ⚪ 3 | 1 saat |
| **P2-G10** | WizardFeatureController auth yok | MEDIUM | **Düşük** | VERIFIED | ⚪ 4 | 1 saat |
| **P2-G11** | AuthController email log masking | MEDIUM | **Orta** | VERIFIED | 🟡 9 | 30 dakika |

**P2 toplam: 11 item — Sonraki sprint (toplam ~25.5 saat)**

---

### P3 — DÜŞÜK

| ID | Bulgu | Etki | Olasılık | Doğrulama | Risk Skoru | Tahmini |
|----|-------|------|----------|-----------|-----------|--------|
| **P3-G01** | UPS log masking | LOW | **Düşük** | VERIFIED | ⚪ 1 | 2 saat |
| **P3-G02** | V2 IlanController index tenant | LOW | **Düşük** | PARTIAL | ⚪ 1 | 1 saat |
| **P3-G03** | IlanCrudService tenant assert | LOW | **Düşük** | PARTIAL | ⚪ 1 | 2 saat |
| **P3-G04** | PropertyPricingService hardcoded rates | LOW | **Düşük** | VERIFIED | ⚪ 2 | 4 saat |
| **P3-G05** | Instagram/Facebook token refresh | LOW | **Orta** | VERIFIED | ⚪ 3 | 2 saat |

**P3 toplam: 5 item — Planlı sprint (toplam ~11 saat)**

---

## RİSK SKORU HESAPLAMA

```
Skor = Etki × Olasılık

Etki:  CRITICAL = 5, HIGH = 4, MEDIUM = 2, LOW = 1
Olasılık: Yüksek = 5, Orta = 3, Düşük = 1
```

| Skor Aralığı | Renk | Eylem |
|-------------|------|-------|
| 20–25 | 🔴 CRITICAL | Derhal |
| 12–19 | 🟠 HIGH | Bu sprint |
| 6–11 | 🟡 MEDIUM | Sonraki sprint |
| 1–5 | ⚪ LOW | Planlı |

---

## FİNANSAL RİSK ÖNCELİKLENDİRME

Finansal etki doğrudan ölçülebilir bulgular:

| ID | Bulgu | Finansal Risk | Toplam Düzeltme |
|----|-------|--------------|-----------------|
| P0-G02 | Token süresiz geçerli | AI cüzdan hırsızlığı | 30 dakika |
| P1-G04 | Outbox yok | Phantom ledger artışı | 8 saat |
| P1-G07 | File/DB ordering | Veri kaybı tazminatı | 3 saat |
| P3-G04 | Hardcoded exchange rates | Fiyat manipülasyonu | 4 saat |
| P2-G01 | Dual AiBudgetGuard | Budget double-spend | 3 saat |

**Toplam finansal risk düzeltme süresi: ~18.5 saat**

---

## OPERASYONEL RİSK ÖNCELİKLENDİRME

Sistem güvenilirliğini doğrudan etkileyen bulgular:

| ID | Bulgu | Operasyonel Etki | Düzeltme |
|----|-------|-----------------|-----------|
| P0-G01 | after_commit: false | Olay kaybı, veri tutarsızlığı | 1 saat |
| P1-G05 | ListingProjector crash | DLQ flood, servis kesintisi | 2 saat |
| P1-G06 | LeadProjector crash | DLQ flood, servis kesintisi | 2 saat |
| P2-G02 | CircuitBreaker Cache | Yanlış circuit state | 4 saat |
| P2-G05 | FinanceProcessor timeout | Telegram mesaj kaybı | 4 saat |
| P2-G07 | CQRS drift | Raporlama hataları | 4 saat |

**Toplam operasyonel risk düzeltme süresi: ~17 saat**

---

## GÜVENLİK RİSK ÖNCELİKLENDİRME

| ID | Bulgu | OWASP Kategori | Düzeltme |
|----|-------|--------------|---------|
| P0-G02 | Token expiration yok | A07:2021 – Identification Failure | 30 dakika |
| P0-G03 | V2 IlanController IDOR | A01:2021 – Broken Access Control | 2 saat |
| P1-G08 | FavoriService tenant | A01:2021 – Broken Access Control | 2 saat |
| P2-G09 | OwnerMesajController | A01:2021 – Broken Access Control | 1 saat |
| P2-G04 | TKGM shared secret | A07:2021 – Identification Failure | 3 saat |
| P2-G03 | Chat API auth yok | A07:2021 – Identification Failure | 1 saat |
| P2-G11 | AuthController email log | A09:2021 – Security Logging | 30 dakika |

**OWASP A01 + A07 kritik hizalanması: 4 bulgu**

---

## KRITIK BAGLANTILAR HARİTASI

```
P0-G01 (after_commit)
    └── P1-G04 (Outbox) ← outbox olmadan event kaybı devam eder
    └── P2-G07 (CQRS drift) ← after_commit düzeltilmeden drift çözülmez
    └── P2-G09 (IlanProjectionHandler) ← aynı

P0-G02 (Sanctum expiration)
    └── P1-G09 (Token revocation) ← eksikliğin tamiri

P1-G04 (Outbox)
    └── P2-G05 (FinanceProcessor) ← ledger event kaybı direct etki

P2-G01 (Dual AiBudgetGuard)
    └── P0-G02 (Token security) ← AI bütçe hırsızlığı aynı kategori
```

---

## KAYNAK DAGILIMI

| Sprint | İş Saati | P0 | P1 | P2 | P3 |
|--------|----------|----|----|----|----|
| **Sprint 3.1 Extended** | ~14 saat | 3 | 6 | 0 | 0 |
| **Sprint 4** | ~30 saat | 0 | 1 | 9 | 0 |
| **Sprint 5+** | ~20 saat | 0 | 1 | 2 | 5 |
| **TOPLAM** | **~64 saat** | **3** | **8** | **11** | **5** |

---

## Chief AI Karar Noktaları

> **D-01**: P0-G01 + P0-G02 + P0-G03 Sprint 3.1 Extended olarak acil sprint açılsın.
> **D-02**: P1-G04 Outbox 8 saatlik独自 mimari borç. Sprint 4 başına planlansın.
> **D-03**: P2-G01 Dual AiBudgetGuard tam kaldırılma değil, consolidated annotation + shared state ile düzeltilsin.
> **D-04**: P3-G05 Instagram/Facebook token refresh SAB Phase 12 Financial Seal ile hizalanıyor — birlikte ele alınsın.

---

*Chief Enterprise Architect — 2026-06-29 — v1.0*
*Risk Matrix sonraki güncelleme: Sprint 3.1 kapanışında (2026-07-05)*
