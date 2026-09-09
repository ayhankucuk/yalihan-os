# Golden Thread E2E — RC2 Browser Sertifikasyon Raporu (Güncelleme 2026-09-09)

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `4f195599` (branch: `release-candidate/RC2`)
- **Working Tree:** Dirty (1 uncommitted file: `tests/e2e/golden-thread-wizard.spec.ts`)
- **Evidence Date:** 2026-09-09T10:39:00Z (UTC) [TR: 2026-09-09 13:39:00 +03:00]
- **Evidence Level:** `BROWSER_VERIFIED`
- **Production Authorization:** `NONE (Local Test — Plan Only)`
<!-- ───────────────────────────────────────────────────────────── -->

---

## Test Sonuçları — 2026-09-09

| TC | Test | Süre | Sonuç | Kanıt |
|----|------|------|-------|-------|
| TC-GT-01 | Step 1→2 Kategori cascade | 3.4s | ✅ PASS | `tc-gt-01-step1-loaded.png`, `tc-gt-01-step2-reached.png` |
| TC-GT-02 | Step 2→3 Temel bilgiler + dynamic fields | 2.9s | ✅ PASS | `tc-gt-02-step2-filled.png`, `tc-gt-02-step3-reached.png` |
| TC-GT-03 | Step 3 Fotoğraf upload SSOT | 4.2s | ✅ PASS | `tc-gt-03-photos-added.png` — Alpine:2, Native:2, Preview:2 |
| TC-GT-04 | Step 3→4 Konum navigation | 4.3s | ✅ PASS | `tc-gt-04-step4-reached.png` |
| TC-GT-05 | Step 4→5 Önizleme + summary | 7.6s | ✅ PASS | `tc-gt-05-step5-reached.png` |
| TC-GT-06 | Full Step 1→5 + native form submit redirect | 10.7s | ✅ PASS | `tc-gt-06-all-steps-reached.png`, `tc-gt-06-results.json` |

**Toplam: 6/6 PASS — 39.8 saniye**

---

## TC-GT-06 — Detaylı Kanıt

### Önceki Durum (2026-09-08)

HTTP 422 — `cephe` validation hatası:
```
Seçilen cephe geçersiz.
```

**Kök Neden:** Fixture'da `'cephe': 'guney'` kullanılıyordu. Ancak schema-driven validation'da `cephe` field'ının whitelist'i şudur: `cadde-cepheli`, `sokak-cepheli`, `avm-ici`, `ic-cephe`. `guney` değeri whitelist dışında — `in:` validation kuralı fail ediyordu.

### Düzeltme (2026-09-09)

```diff
- 'cephe': 'guney',
+ 'cephe': 'cadde-cepheli',
```

**Commit:** `tests/e2e/golden-thread-wizard.spec.ts` — fixture'da `cephe` değeri `cadde-cepheli` olarak düzeltildi.

### Yeni Sonuç

```json
{
  "timestamp": "2026-09-09T10:39:10.746Z",
  "allStepsReached": true,
  "urlAfterSubmit": "http://127.0.0.1:8000/admin/ilanlar/75/edit",
  "ilanId": "75",
  "submitNavigatedToIlan": true,
  "httpStatus": 200,
  "consoleErrors": [
    "Failed to load resource: the server responded with a status of 429 (Too Many Requests)",
    "Failed to load resource: the server responded with a status of 429 (Too Many Requests)",
    "Failed to load resource: the server responded with a status of 429 (Too Many Requests)",
    "Failed to load resource: the server responded with a status of 404 (Not Found)"
  ]
}
```

- **HTTP Status:** 200 ✅
- **Redirect:** `/admin/ilanlar/75/edit` ✅
- **ilan ID:** 75 ✅
- **Console Errors:** 429 × 3 + 404 — non-critical (rate limit + CDN resource), `criticalErrors` filtresi tarafından geçerli kabul ediliyor

---

## Sertifikasyon Tablosu

| Test | Status | Label |
|------|--------|-------|
| TC-GT-01 Step 1→2 Kategori cascade | ✅ PASS | `BROWSER_VERIFIED` |
| TC-GT-02 Step 2→3 Temel bilgiler | ✅ PASS | `BROWSER_VERIFIED` |
| TC-GT-03 Step 3 Fotoğraf SSOT | ✅ PASS | `BROWSER_VERIFIED` |
| TC-GT-04 Step 3→4 Konum | ✅ PASS | `BROWSER_VERIFIED` |
| TC-GT-05 Step 4→5 Önizleme | ✅ PASS | `BROWSER_VERIFIED` |
| TC-GT-06 Step 1→5 + submit redirect | ✅ PASS | `BROWSER_VERIFIED` |

**Tam Sertifikasyon: 6/6 PASS — `BROWSER_VERIFIED`**

---

## Değişiklik Özeti

| Dosya | Değişiklik |
|-------|-------------|
| `tests/e2e/golden-thread-wizard.spec.ts:425` | `'cephe': 'guney'` → `'cephe': 'cadde-cepheli'` |

---

## Mevcut Working Tree Durumu

```
Branch: release-candidate/RC2 (HEAD → 4f195599)
Dirty:  tests/e2e/golden-thread-wizard.spec.ts (1 değişiklik — cephe fix)
Untracked: test-results/, playwright/.auth/ (local auth state)
```

**Commit önerisi:**
```
fix(e2e): correct cephe fixture value to match schema whitelist

'guney' is not in the schema-driven cephe whitelist for Konut.
Schema allows: cadde-cepheli, sokak-cepheli, avm-ici, ic-cephe.
Changed to 'cadde-cepheli' — valid option from the whitelist.

TC-GT-06: HTTP 422 → HTTP 200 + redirect /admin/ilanlar/75/edit
6/6 PASS — 39.8s
```

---

## Not

E2E test çalıştırmak commit/deploy yetkisi vermez. Her production migration/seed ayrı operatör onayı gerektirir.

*Bu rapor `golden-thread-certification` skill'i rehberliğinde üretilmiştir.*
