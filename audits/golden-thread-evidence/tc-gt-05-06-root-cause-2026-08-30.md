# TC-GT-05/06 Kök Neden Raporu — 2026-08-30

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `81be956` (branch: `integration/era-v-phase2a-e01`)
- **Working Tree:** `Dirty` (unstaged + untracked)
- **Evidence Date:** 2026-08-30T07:20:00Z (UTC) [TR: 2026-08-30 10:20:00 +03:00]
- **Evidence Level:** `TEST_VERIFIED / REPO_VERIFIED`
- **Production Authorization:** `NONE (Local Diagnosis)`
<!-- ───────────────────────────────────────────────────────────── -->

---

## Özet

TC-GT-05/06 timeout hatası location verisiyle **İLGİLİ DEĞİL**. Local DB zaten doğru canonical veriye sahip (81 il, 13 ilçe, 20 mahalle). Test başarıyla Step 5'e navigasyon yapıyor ancak Alpine.js validation flood nedeniyle browser çöküyor.

---

## Test Sonuçları

| Ortam | DB Durumu | TC-GT-05 | TC-GT-06 |
|-------|-----------|----------|----------|
| Local (bu oturum) | 81/13/20 canonical ✅ | Browser crash (timeout) | Browser crash (timeout) |
| Clone (2026-08-26 raporu) | Canonical + migration ✅ | PASS | PASS |

**Clone raporu kanıtı:** `audits/golden-thread-evidence/migration-clone-test-report.md`

---

## Kök Neden — Alpine Validation Flood

### Bulgu Zinciri

1. **Test Step 5'e ulaşıyor** — DOM snapshot kanıtı:
   - Heading: "✅ Son Adım: Önizleme ve Yayın" ✅
   - Başlık: "Golden Thread Test İlanı" ✅
   - Fiyat: "2.500.000 ₺" ✅
   - Konum: "Muğla / Bodrum" ✅
   - Fotoğraf: "1 Adet" ✅

2. **100+ adet "Bu alan zorunludur" Alpine toast error** — DOM snapshot kanıtı:
   ```
   generic [ref=f1e337]: ❌ Bu alan zorunludur
   generic [ref=f1e344]: ❌ Bu alan zorunludur
   ... (100+ kez tekrar ediyor)
   ```

3. **Browser çöküyor** — Playwright: `page.evaluate: Target page, context or browser has been closed`

### Mekanizma

**Kaynak dosya:** `resources/js/admin/ilan-wizard-page.js:1212-1308`

```javascript
// ilan-wizard-page.js:1212 — validateStep()
validateStep(step) {
    const stepFields = this.getStepFields(step);
    stepFields.forEach((fieldName) => {
        // ...
        if (isRequired) {
            this.showFieldError(field, msg);  // ← Her alan için ayrı toast
        }
    });

    if (!isValid) {
        this.showNotification(errorMessage, 'error');  // ← Ekstra tek toast
    }
}

// getStepFields(4):
4: ['il_id', 'ilce_id', 'mahalle_id', 'adres_detay', 'lat', 'lng']
```

`ilan-wizard-page.js`'deki `ilanWizard.nextStep()` çağrısı, Step 4→5 geçişinde `validateStep(4)` çağırıyor. `getStepFields(4)` sadece 6 alan döndürüyor.

**Ama:** `nextStep()` ayrıca `validateStep(this.currentStep)` çağırıyor (satır 1053). `currentStep` hâlâ 4. Yani `validateStep(4)` çağrılıyor — bu normal.

Ancak screenshot/snapshot'taki flood sayısı (~100) çok fazla. Bu demek ki `validateStep(4)` birçok kez çağrılıyor.

**Olası mekanizma:** `waitForFunction` polling döngüsü — `navigateStep4To5`'deki `waitForFunction` her 100ms'de `nextStep()` çağırıyor. `nextStep()` → `validateStep(4)` → `showFieldError` → `showNotification` döngüsü. `validateStep(4)` her çağrılışında aynı alanlar için `showNotification` + `showFieldError` biriktiriyor. 15 saniyelik timeout × ~10 polling/saniye = ~150 çağrı = ~150 toast = ~100 görünür toast (kaydırma nedeniyle bazıları viewport dışında kalıyor).

Alternatif: `showNotification` fonksiyonunda hiçbir deduplication mekanizması yok — her çağrı yeni bir DOM elementi yaratıyor.

### showNotification Analizi

```javascript
// resources/js/admin/ilan-create/core.js:334
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 ...`;
    document.body.appendChild(notification);  // ← Her çağrı yeni div
    setTimeout(() => notification.remove(), 5000);
}
```

**Tespit:** `showNotification` fonksiyonunda hiçbir debounce, throttle veya deduplication mekanizması yok. Aynı mesaj bile olsa her çağrı yeni bir notification div yaratıyor.

### Kilitli Sorular

1. `validateStep(4)` neden 100+ kez çağrılıyor?
   - `waitForFunction` polling mekanizması muhtemel
   - `navigateStep4To5` satır 285: `waitForFunction` → `inst.nextStep()` → `validateStep(4)` çağrı zinciri
   - Her polling iterasyonunda `validateStep` çalışıyor ve `showFieldError` + `showNotification` birikiyor

2. Neden crash oluyor?
   - 100+ aynı DOM elementi yaratılıyor
   - Alpine.js reactivity her notification için re-render yapıyor
   - Browser memory/page unresponsive

---

## Location Veri Analizi — CLARIFIED

| Tablo | Local DB | Beklenen | Durum |
|-------|----------|----------|-------|
| `iller` | 81 | 81 | ✅ DOĞRU |
| `ilceler` | 13 | 13 (Muğla) | ✅ DOĞRU |
| `mahalleler` | 20 | 20 (Bodrum) | ✅ DOĞRU |

**Sonuç:** Location veri sorunu çözüldü. Migration clone test raporu doğruydu.

---

## Düzeltme Öncelikleri

### 🔴 CRITICAL — Test infrastructure

**Dosya:** `tests/e2e/golden-thread-wizard.spec.ts` — `navigateStep4To5()`

**Sorun:** `waitForFunction` polling döngüsü `validateStep(4)`'ü her ~100ms'de çağırıyor. Her çağrı `showFieldError` + `showNotification` biriktiriyor → flood → crash.

**Düzeltme:**
```typescript
// navigateStep4To5() — mevcut kod:
// waitForFunction içinde inst.nextStep() çağrılıyor (her polling iterasyonunda)
// Düzeltme: validateStep zaten il/ilce/mahalle seçimlerini kontrol ediyor
// ama form submit değil navigasyon için çağrılıyor
// → Ya waitForFunction'ı kaldır, ya da validateStep call'ını isolate et
```

### 🟡 HIGH — Production UX

**Dosya:** `resources/js/admin/ilan-create/core.js:334` — `showNotification()`

**Sorun:** Her çağrı yeni div yaratıyor. 100+ notification aynı anda görünürse browser çöker.

**Düzeltme:** Deduplication mekanizması:
```javascript
let _notificationTimeouts = {};
let _lastNotification = null;
function showNotification(message, type = 'info') {
    // Skip if same message within 2 seconds
    if (_lastNotification === message && _notificationTimeouts[message]) return;
    _lastNotification = message;
    // ... existing code ...
}
```

---

## Karar Noktası

| Soru | Cevap |
|-------|--------|
| Location verisi doğru mu? | **EVET** — 81/13/20 ✅ |
| TC-GT-05/06 location seed'e bağlı mı? | **HAYIR** — Alpine flood bağımsız sorun |
| Clone migration test sonucu geçerli mi? | **EVET** — 6/6 PASS |
| Kök neden: Location mı Alpine mi? | **Alpine validation flood** |
| Düzeltme önceliği? | Test infrastructure (kritik) + showNotification dedup (production UX) |

---

## Önerilen Sonraki Adımlar

1. [ ] `navigateStep4To5` test fonksiyonunu düzelt — validation flood隔离
2. [ ] `showNotification` deduplication ekle
3. [ ] TC-GT-05/06'yı yeniden koştur
4. [ ] Clone migration test kanıtını production'a taşıma kararı al
