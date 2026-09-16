---
name: api-contract-envelope-guardian
description: Frontend fetch/axios çağrıları ile backend route JSON zarflarını eşleştirir; alan adı drifti, HTTP durum kodu ve tenant scope uçlaklarını release öncesi yakalar.
---

# API Contract Envelope Guardian

Browser kodunun beklediği API sözleşmesiyle backend rotasının gerçekten sunduğu sözleşmeyi birlikte denetler.

---

## Denetim Adımları

### 1. Frontend API Çağrılarını Çıkar

Frontend fetch/axios çağrılarını bul ve kaydet:

```js
// Örnek: app/Http/Controllers/Owner/IlanController.php üzerinden yapılan API çağrısı
fetch('/api/owner/ilanlar', { method: 'GET', headers: { Authorization: `Bearer ${token}` } })
fetch('/api/owner/ilanlar', { method: 'POST', body: JSON.stringify(payload) })
```

Her çağrı için kaydet:
- URL (mutlak veya göreli)
- HTTP metodu
- Çağıran ekran/handler

### 2. Route Varlık Kontrolü

```bash
php artisan route:list --json | python3 -c "
import json, sys
routes = json.load(sys.stdin)
found = [r for r in routes if '/api/owner/ilanlar' in r.get('uri','')]
for r in found:
    print(f\"{r['method']} {r['uri']} -> {r['name']} [{r['action']}]\")
"
```

Eğer route yok → **BLOCKING**: Yazma işlemine devam etme.

### 3. HTTP Durum Kodu Kontratı

Her endpoint için başarı ve hata durumlarını kontrol et:

| Durum | Ne Anlama Gelir | Frontend Beklentisi |
|-------|----------------|-------------------|
| `200` | Başarılı, body var | `response.data` kullanılabilir |
| `201` | Kaynak oluşturuldu | `response.data.id` mevcut |
| `204` | Başarılı, body yok | `response.status === 204` |
| `401` | Yetkisiz | Login sayfasına yönlendir |
| `403` | Yasak | "Erişim reddedildi" mesajı |
| `404` | Bulunamadı | "Kaynak bulunamadı" mesajı |
| `419` | CSRF token süresi dolmuş | Sayfayı yeniden yükle |
| `422` | Validasyon hatası | Form hatalarını göster |
| `429` | Rate limit | "Çok fazla istek" mesajı |
| `500` | Sunucu hatası | "Sistem hatası" mesajı |

### 4. JSON Zarfı Alan Eşleşmesi

```json
// Frontend bekliyor
{
  "success": true,
  "data": { "id": 1, "status": "active" },
  "message": "İlan kaydedildi"
}

// Backend döndürüyor
{
  "yayin_durumu": 1,
  "id": 1
}
```

**Drift örnekleri:**

| Frontend Bekliyor | Backend Döndürüyor | Sorun |
|-------------------|-------------------|-------|
| `status` | `yayin_durumu` | Alan adı drifti |
| `ilan_id` | `id` | Tutarsız ID adlandırma |
| `email` | `eposta` | Context7 ihlali |
| `data.items[0].price` | `data.items[0].fiyat` | Nested alan drifti |

### 5. Tenant Scope Kontrolü

Çok tenant'lı endpoint'lerde:

```php
// ❌ YASAK: Tenant scope olmayan sorgu
$ilanlar = Ilan::all();

// ✅ ZORUNLU: Tenant scope ile
$ilanlar = Ilan::where('tenant_id', $tenantId)->get();
```

---

## Denetim Çıktısı Formatı

```markdown
## API Contract Audit — [Endpoint Adı]

### Endpoint
`GET /api/owner/ilanlar`

### Route Bulundu
✅ Evet → `owner.ilanlar.index`

### HTTP Durum Kodu Kontratı
| Senaryo | Backend | Frontend Beklentisi | Eşleşme |
|---------|---------|---------------------|---------|
| Başarılı | 200 | 200 | ✅ |
| Yetkisiz | 401 | 401 | ✅ |
| Bulunamadı | 404 | 404 | ✅ |
| Validasyon | 422 | 422 | ✅ |

### JSON Zarfı
| Alan | Frontend | Backend | Eşleşme |
|------|----------|---------|---------|
| `id` | ✅ | ✅ | ✅ |
| `yayin_durumu` | ❌ Bekliyor: `status` | ✅ | ⚠️ DRIFT |
| `fiyat` | ✅ | ✅ | ✅ |

### Tenant Scope
✅ `tenant_id` scope'u mevcut

### Sonuç
PASS | FAIL | CONDITIONAL
```

---

## Kanıt Seviyeleri

| Seviye | Anlamı |
|---------|--------|
| `REPO_VERIFIED` | Statik tarama (route mevcut, controller kodu okundu) |
| `TEST_VERIFIED` | Playwright/http_test çalıştı, HTTP yanıtı doğrulandı |
| `PRODUCTION_VERIFIED` | Canlı endpoint, gerçek tenant verisi ile test edildi |

---

## Sınırlar

- API kontratını düzeltmek migration, seed, canlı veri değişikliği veya production deploy yetkisi **vermez**
- Belirsiz bir endpoint'i `200` döndüren sahte route ile **kapatma**
- Frontend mock/override varsa, kontrat gerçek backend'e karşı doğrulanmalı
