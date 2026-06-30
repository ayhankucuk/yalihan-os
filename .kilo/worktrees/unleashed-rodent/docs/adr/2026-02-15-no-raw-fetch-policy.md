# ADR-003: No Raw Fetch Policy — Merkezi Ağ Katmanı Zorunluluğu

## Context

Proje genelinde **~200+ raw `fetch()` çağrısı** tespit edildi. Bu çağrılar:

- CSRF token yönetimini her yerde tekrar ediyor
- Accept/Content-Type header'ları tutarsız uygulanıyor
- JSON parse güvenliği her yerde farklı (bazıları korumasız)
- Telemetri (http_durum_kodu, basarili, duration_ms) çoğunda eksik
- Hata yakalamada standart yok (bazıları silent fail, bazıları throw)
- Context7 scanner evasion hack'leri (`response['st'+'atus']`) oluşmasına yol açtı

Bu durum **observability, güvenlik ve bakım** açısından sürdürülebilir değil.

## Decision

**Frontend HTTP çağrıları sadece onaylanmış wrapper'lar üzerinden yapılabilir.** Raw `fetch()` kullanımı mimari ihlal olarak tescil edilir.

### Onaylanmış Ağ Katmanları (SSOT)

| Wrapper                        | Konum                                   | Kullanım Alanı                   |
| ------------------------------ | --------------------------------------- | -------------------------------- |
| `wizardFetch()`                | `ilan-wizard-page.js` (IIFE-local)      | Wizard modülü tüm HTTP çağrıları |
| `window.APIHelper.safeFetch()` | `public/js/utils/api-helper.js`         | Global admin panel çağrıları     |
| `window.safeJsonFetch()`       | `resources/js/admin-safe-fetch.js`      | Admin panel JSON çağrıları       |
| `createTelemetryFetch()`       | `resources/js/wizard/core/telemetry.js` | Telemetri sarmalı fetch          |

### wizardFetch Arayüzü

```javascript
const { response, data, ok } = await wizardFetch(
    url, // string - istek URL'i
    options, // object - { method, body, headers }
    telemetryEvent, // string|null - 'wizard_fetch_context' vb.
    telemetryExtra // object - { contextKey } vb.
);
```

**Garanti eder:**

- `data` her zaman parsed object (JSON parse hatası → fallback object)
- `ok` boolean (response.ok mirror)
- `response` erişilebilir (özel status kontrolleri için)
- CSRF, Accept, Content-Type otomatik
- Telemetri otomatik (event adı verilmişse)

### Enforcement

1. **Context7 Scanner**: `config('context7.no_raw_fetch.enforced_files')` listesindeki dosyalarda raw `fetch(` tespiti → `raw_fetch_violation` (critical)
2. **Code Review**: PR'larda yeni `fetch(` çağrısı → reject
3. **Copilot Instructions**: `.github/copilot-instructions.md` güncellenecek

## Consequences

### Pozitif

- Tek noktadan CSRF/header/telemetri yönetimi
- JSON parse güvenliği garanti (asla throw etmez)
- Context7 uyumlu telemetri her çağrıda otomatik
- Scanner evasion hack'lerine gerek kalmaz
- Hata standardizasyonu (her zaman `{ success, message }` döner)
- APIHelper varsa cache/debounce/monitoring otomatik

### Negatif

- ~200+ dosya kademeli migration gerektirir
- Wrapper'ın kendi bug'u olursa tüm çağrıları etkiler (risk azaltma: fallback)
- Küçük overhead (text → JSON.parse vs native response.json)

## Alternatives Considered

1. **ChatGPT önerisi: basit `safeFetch` wrapper** — Reddedildi. Mevcut `APIHelper`, `safeJsonFetch`, `createTelemetryFetch` zaten var. Dördüncü bir soyutlama gereksiz. Bunun yerine mevcut wrapper'ları tek SSOT politikası altında birleştiriyoruz.

2. **Global fetch monkey-patch** — Reddedildi. Debug zorlaştırır, 3rd-party kütüphaneleri etkiler.

3. **ESLint no-restricted-globals** — Gelecekte eklenebilir. Şimdilik Context7 scanner yeterli.

## Migration Plan

| Phase   | Dosyalar                                  | Durum         |
| ------- | ----------------------------------------- | ------------- |
| Phase 1 | `ilan-wizard-page.js` (10 çağrı)          | ✅ Tamamlandı |
| Phase 2 | `smart-ilan-create.js`, `AIService.js`    | ⏳ Planlandı  |
| Phase 3 | `location-wizard.js`, koordinat dosyaları | ⏳ Planlandı  |
| Phase 4 | Admin blade inline JS                     | ⏳ Planlandı  |
| Phase 5 | Public-facing JS                          | ⏳ Planlandı  |
