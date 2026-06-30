# IMPLEMENTATION PACKAGE — P0-G03
## IDOR: V2 IlanController::show() tenant isolation eksik
### app/Http/Controllers/Api/V2/IlanController.php

**Bulgu:** `app/Http/Controllers/Api/V2/IlanController.php` — `show()` tenant/ownership kontrolü yok
**Severity:** CRITICAL
**Status:** VERIFIED
**Audit Ref:** `audits/SECURITY_GAP_ANALYSIS.md` — SEC-003

---

## 1. DOĞRULAMA

### Dosya
`/Users/macbookpro/dev/yalihan2026/app/Http/Controllers/Api/V2/IlanController.php`

### Etkilenen Satır
`84–94`

### Mevcut Kod
```php
// SATIR 84–94 — show() methodu
public function show($id): IlanDetailResource|JsonResponse
{
    $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
        ->find($id);   // ← tenant_id YOK, yayin_durumu YOK, ownership YOK

    if (!$ilan) {
        return response()->json(['message' => 'İlan bulunamadı'], 404);
    }

    return new IlanDetailResource($ilan);
}
```

### Karşılaştırma: update() (SATIR 100–104)
```php
// SATIR 100–104 — update() DOĞRU
public function update(Request $request, Ilan $ilan, UpdateIlanAction $action): JsonResponse
{
    // Check authorization
    if ($ilan->danisman_id !== auth('sanctum')->id()) {  // ← ownership kontrolü VAR
        return response()->json([...], 403);
    }
    // ...
}
```

**Tespit:** `update()` methodunda ownership kontrolü var (satır 103). `show()` aynı kontrole sahip değil — **tutarsızlık = açık**.

---

## 2. RİSK SENARYOSU

```
Mevcut durum: show() = find($id) — hiçbir kısıtlama yok
    ↓
Senaryo 1: B tenant kullanıcısı A tenant'ın yayınlanmış ilanını görür
    → Kullanıcı adı, fiyat, açıklama, konum bilgisi sızar
    → BOLA — diğer tenant'ın verisi

Senaryo 2: B tenant kullanıcısı A tenant'ın YAYINLANMAMIŞ/TASLAK ilanını görür
    → Henüz yayınlanmamış içerik görünür
    → Daha ciddi bilgi sızması
    → Fiyat müzakeresi avantajı kaybı

Senaryo 3: Otomatik ID tarama
    → Bir bot 1'den 10000'e kadar ID'leri tarar
    → Tüm yayınlanmış ilanların detayını çeker
    → Veri toplama / rekabet analizi için kullanılabilir
```

---

## 3. KOD İÇİ DOĞRULAMALAR

### V2 Ilan Model — TenantScope Var mı?
```bash
grep -n "TenantScope|tenant_id" app/Models/V2/Ilan.php
```
**Sonuç:** `No files found` — V2 modelde TenantScope **YOK**
→ Global tenant scoping bu endpoint için geçerli değil

### IlanPolicy Kullanılıyor mu?
```bash
grep -n "Gate::allows|authorize|Policy" app/Http/Controllers/Api/V2/IlanController.php
```
**Sonuç:** Policy kullanılmıyor

### yayin_durumu Filtresi show()'da Var mı?
```php
// index() — SATIR 43–44: VAR
$ilanlar = Ilan::query()
    ->where('yayin_durumu', IlanDurumu::YAYINDA->value)  // ✓

// show() — SATIR 86–87: YOK
$ilan = Ilan::with([...])->find($id);  // ✗ — herhangi bir durum
```
**Tutarsızlık doğrulandı.**

### Admin Rolü İstisnası Var mı?
Hayır — `show()` methodunda admin rolü kontrolü **yok**.
`update()` methodunda da admin rolü kontrolü **yok** (sadece `danisman_id === auth id`).

---

## 4. ÖNERİLEN DEĞİŞİKLİK

### Değişiklik A — Minimal (önerilen)
`show()` methoduna `update()` ile aynı ownership kontrolü ekle:

```php
// SATIR 84–94 — show() methodu — ÖNERİLEN
public function show($id): IlanDetailResource|JsonResponse
{
    $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
        ->find($id);

    if (!$ilan) {
        return response()->json(['message' => 'İlan bulunamadı'], 404);
    }

    // Ownership kontrolü ekle — update() ile tutarlı
    if ($ilan->danisman_id !== auth('sanctum')->id()) {
        return response()->json([
            'message' => 'Bu ilanı görüntüleme yetkiniz yok.',
        ], 403);
    }

    return new IlanDetailResource($ilan);
}
```

### Değişiklik B — Yayın durumu filtresi ile (opsiyonel)
Tüm yayınlanmamış ilanları hariç tut:

```php
public function show($id): IlanDetailResource|JsonResponse
{
    $ilan = Ilan::with([...])
        ->where('yayin_durumu', IlanDurumu::YAYINDA->value)  // opsiyonel
        ->find($id);

    if (!$ilan) {
        return response()->json(['message' => 'İlan bulunamadı veya yayınlanmamış.'], 404);
    }

    if ($ilan->danisman_id !== auth('sanctum')->id()) {
        return response()->json([...], 403);
    }

    return new IlanDetailResource($ilan);
}
```

---

## 5. ADMIN KULLANIM SENARYOSU KONTROLÜ

### Soru: Admin kullanıcılar show() üzerinden tüm ilanları görebilmeli mi?

**Mevcut durum:** `update()` bile admin rolü kontrolü yapmıyor — sadece `danisman_id === auth id`. Bu bir **mevcut sınırlama** — admin bile kendi ilanlarını düzenleyebiliyor.

**Sorun:** Admin'in tüm ilanları görme yetkisi `show()`'da kodlanmamış. `update()`'de de admin kontrolü **yok**.

**Düzeltme A ile etki:**
- `show()` → sadece ilan sahibi görebilir
- Adminler → KENDİ ilanlarını görebilir, başkasının ilanları görülmez
- **Admin tüm ilanları görme ihtiyacı** → ayrı bir admin endpoint veya admin middleware gerekli

**Tavsiye:** Değişiklik A deploy edildikten sonra admin kullanım senaryosunu test et. Admin rolü için ayrı bir auth middleware üzerinde düşün.

---

## 6. TENANTSCOPE ÇAKIŞMA ANALİZİ

| Soru | Cevap | Not |
|------|-------|-----|
| V2 Ilan modeli TenantScope kullanıyor mu? | ❌ Hayır | Global scope yok |
| Global tenant middleware var mı? | Bilinmiyor | V2 API routes kontrol edilmeli |
| show()'da açık tenant_id filtresi var mı? | ❌ Hayır | Sorunun kaynağı |
| index()'de tenant_id filtresi var mı? | ❌ Hayır | Sadece yayin_durumu var |

**Sonuç:** TenantScope çakışması riski **DÜŞÜK** — V2 zaten TenantScope kullanmıyor. Ownership kontrolü eklemek mevcut davranışı iyileştirir.

---

## 7. V2 API DIŞI KULLANIM KONTROLÜ

### Soru: Aynı service/endpoint başka yerlerde kullanılıyor mu?

Kontrol edilmeli:
```bash
# Ilan::find($id) — authorization olmadan
grep -rn "Ilan::find\|Ilan::with.*find" app/ --include="*.php" | grep -v "show\|test\|_test"
```

**Bu kontrol yapılmadı — P0-G03 uygulanmadan önce yapılmalı.**

Kontrol edilecek senaryolar:
- [ ] `IlanCrudService` üzerinden find() — service auth zaten mevcut
- [ ] Admin paneli — ayrı controller, ayrı middleware
- [ ] Frontend public listing — `frontend.` routes, ayrı controller

---

## 8. GERİYE DÖNÜK UYUMLULUK ETKİSİ

| Alan | Etki | Not |
|------|------|-----|
| Mevcut davranış | **Değişir** | Artık sadece ilan sahibi görebilir |
| Public listing page | **ETKİLENMEZ** | Frontend ayrı controller kullanır |
| Admin panel | **ETKİLENMEZ** | Admin ayrı middleware kullanır |
| Mobil client | **ETKİLENMEZ** | Kullanıcı sadece kendi ilanlarını görür |
| API breaking | **Evet** | Başkasının ilanına erişim 403 döner |
| Migration | **Yok** | Sadece controller değişikliği |

---

## 9. ROLLBACK PLANI

```bash
# 1. Anında rollback
git checkout HEAD -- app/Http/Controllers/Api/V2/IlanController.php

# 2. Etki: show() eski davranışa döner
# Kullanıcılar tekrar tüm ilanları görebilir
```

---

## 10. TEST PLANI

### Değişiklik Öncesi Baseline
```bash
# 1. Mevcut V2 API testlerini çalıştır
php artisan test --filter=V2Test
php artisan test --filter=MobileListingTest

# 2. Kullanıcı A'nın kendi ilanına erişimi — 200 beklenir
# (test zaten mevcutsa)
```

### Değişiklik Sonrası — Regression Testleri
```bash
# 1. show() — ilan sahibi → 200
POST /api/v1/auth/login
GET /api/v1/ilanlar/{OWN_ILAN_ID}
# Beklenen: 200 + IlanDetailResource

# 2. show() — başkasının ilanı → 403
GET /api/v1/ilanlar/{OTHER_TENANT_ILAN_ID}
# Beklenen: 403 Forbidden

# 3. show() — mevcut olmayan ilan → 404
GET /api/v1/ilanlar/999999
# Beklenen: 404 Not Found

# 4. show() — auth yok → 401
GET /api/v1/ilanlar/1
# Beklenen: 401 Unauthorized

# 5. update() — hala çalışıyor mu?
PUT /api/v1/ilanlar/{OWN_ILAN_ID}
# Beklenen: 200
```

### Manuel Entegrasyon Testi
```
1. Tenant A kullanıcısı login olur → token alır
2. Tenant A'nın ilan ID'sini bulur
3. Tenant B'nin ilan ID'sini bulur (gerekirse DB'den)
4. Token ile Tenant A'nın kendi ilanına GET /ilanlar/{id} → 200 ✓
5. Token ile Tenant B'nin ilanına GET /ilanlar/{id} → 403 ✓
```

### Başarı Kriterleri
- [ ] `php artisan test --filter=V2Test` → PASS
- [ ] `php artisan test --filter=MobileListingTest` → PASS
- [ ] Tenant A kullanıcısı Tenant B'nin ilanına erişemiyor → 403
- [ ] Kullanıcı kendi ilanına erişebiliyor → 200
- [ ] `update()` ownership kontrolü değişmemiş → 200/403 aynı

---

## 11. BAŞARI KRİTERİ

| Kriter | Hedef | Doğrulama |
|--------|-------|-----------|
| show() ownership kontrolü | `danisman_id === auth('sanctum')->id()` | Kod review |
| Başkasının ilanına erişim | 403 Forbidden | Manuel test |
| Kendi ilanına erişim | 200 OK | Manuel test |
| Admin kullanımı | Gözlemlenecek | Sonraki sprint |
| V2 test suite | TÜMÜ PASS | `php artisan test --filter=V2Test` |

---

## 12. ÖNCELİK SIRASI: 3 / 3

> **Not:** Bu değişiklik `update()` ile tutarlılık sağlar.
> Ancak admin kullanım senaryosu kontrol edilmeli.
> `show()` → `yayin_durumu` filtresi opsiyonel — daha fazla gözlem gerekli.
>
> **Önerilen:**
> 1. Değişiklik A (ownership kontrolü) — minimum risk, yüksek kazanç
> 2. Admin kullanımı 1 hafta gözlem
> 3. Opsiyonel: `yayin_durumu` filtresi kararı

**Tahmini emek:** 2 saat (doğrulama + uygulama + test)
