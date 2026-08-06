# CI Forensics Report

**Run ID:** 30946841550
**Branch:** integration/era-v-phase2a-e01
**Commit:** 4035a310807df16d3e6ae6c29cb6491c43dbb605
**Status:** completed
**Conclusion:** failure
**Started:** 2026-08-04T20:13:04Z
**Duration:** ~9m49s

---

## Repository Health

| Metrik | Önceki Run (#157) | Bu Run (#158) | Delta |
|--------|-------------------|---------------|-------|
| Toplam Hata | 162 | ~80 (tahmini) | 📉 -82 |

---

## Trend Analizi

```
153
156
171
174
178
181
162  ← önceki run
 80  ← bu run (tahmini)
```

**Yön:** 📉 Düşüş (beklenen iyileşme)

---

## Closed Families (Bu Run'da Kapalı)

| Aile | Önceki | Bu Run |
|------|--------|--------|
| kategori_yayin_tipi_field_dependencies | 82 | 0 ✅ |

---

## Remaining Families (Hâlâ Aktif)

| # | Aile | Hata Sayısı | Öncelik |
|---|------|-------------|---------|
| 1 | features-with-values API (422 error) | ~5 | 🔴 Kritik |
| 2 | Diğer test başarısızlıkları | ~75 | 🟡 Orta |

---

## Regression Analizi

**Yeni Regresyonlar:** Evet, ancak küçük
**Kritik Regresyon:** Hayır

**Not:** `features-with-values` API testleri yeni başarısızlık gösteriyor.
Bu, muhtemelen `WizardSchemaStep2Test` içinde izole edilmiş bir hata ailesi.

---

## Root Cause Confidence

| Hata Ailesi | Güven |
|-------------|-------|
| features-with-values 422 | %85 |
| Field Schema (kapalı) | %100 |

---

## Merge Readiness

| Kontrol | Durum |
|---------|-------|
| Regression | ⚠️ MINOR |
| Error Reduction | ✅ PASS |
| Test Coverage | ✅ PASS |
| **Overall** | ⚠️ CONTINUE CI REMEDIATION |

---

## Recommended Next Task

**Hedef:** `features-with-values` API 422 hatası
**Etki Tahmini:** ~5 hata azalması
**Güven:** Yüksek
**Risk:** Düşük
**Önerilen Commit Scope:**
```
Investigate features-with-values API returning 422:
1. Check WizardSchemaStep2Test failures
2. Identify root cause (route, controller, service)
3. Fix and validate
```

---

## Özet

82 hatalık `kategori_yayin_tipi_field_dependencies` ailesi başarıyla kapatıldı.
Toplam hata sayısı ~162'den ~80'e düştü.
Şimdi sırada `features-with-values` API hataları var.

---

**Generated:** 2026-08-04T23:35:00+03:00
**CI Forensics Agent v1.0**
