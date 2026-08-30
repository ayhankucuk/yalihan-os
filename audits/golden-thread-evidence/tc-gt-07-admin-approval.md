# TC-GT-07 — Yönetici Onayı (Admin Approval) Akışı — PRODUCTION_VERIFIED

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-27T00:00:00Z (UTC) [TR: 2026-08-27 03:00:00 +03:00]
- **Evidence Level:** `PRODUCTION_VERIFIED`
- **Production Authorization:** `AUTHORIZED (Production DB port 8002)`
<!-- ───────────────────────────────────────────────────────────── -->

**Ortam:** Production DB (`yalihanai_v2_production`), sunucu port 8002
**Test:** `tests/e2e/golden-thread-admin-approval.spec.ts`
**Sonuç:** ✅ **PRODUCTION_VERIFIED** — 8 adımlı doğrulama zinciri tamamlandı

---

## 1. Doğrulama Zinciri (Kullanıcının İstediği 8 Adım)

| # | Adım | Sonuç | Kanıt |
|---|------|-------|-------|
| 1 | Taslak ilanı açma | ✅ | GET `/api/v1/ilanlar/{id}` → 200, ilan bulundu |
| 2 | `taslak → incelemede` geçişi | ✅ | PATCH `/admin/ilanlar/{id}/yayin-durumu` → 200 |
| 3 | Yönetici yetki kontrolü | ✅ | Super-admin (id=2) yetkili, geçiş başarılı |
| 4 | API yanıtı | ✅ | `islem_durumu=ok`, `yayin_durumu=beklemede` |
| 5 | DB `yayin_durumu` değişikliği | ✅ | `ilanlar.yayin_durumu='beklemede'` |
| 6 | Activity/audit kaydı | ✅ | `listing_state_transitions` immutable row |
| 7 | Yetkisiz kullanıcıyla reddedilme | ✅ | Musteri (role_id=4) → **403** |
| 8 | Browser'da başarı bildirimi | ✅ | Index sayfasında `beklemede` ifadesi |

---

## 2. Kritik Bulgular

### 2.1 `updateYayinDurumu` `tryFrom()` kullanır — canonical değer zorunlu

`IlanPublishController::updateYayinDurumu()` `IlanDurumu::tryFrom()` kullanır
(exact enum case match). `'incelemede'` bir **LEGACY alias**'tır ve yalnızca
`IlanDurumu::normalize()` / `ListingStateMachine::normalizeToInt()` tarafından
`BEKLEMEDE`'ye map edilir.

- `tryFrom('incelemede')` → `null` → **400** "Geçersiz yayın durumu: incelemede"
- Client **CANONICAL** değeri `'beklemede'` göndermelidir.

### 2.2 Tek yazma yolu: `YalihanLifecycle::transition()`

Tüm durum geçişleri `YalihanLifecycle::transition()` üzerinden geçer:
- State machine doğrulaması (`ListingStateMachine::gecisYap`)
- DB transaction: `yayin_durumu` güncelle + `ListingStateTransition` audit kaydı
- `ListingStateTransition` **immutable** (update/delete YASAK)

### 2.3 Yetki zinciri (3 katman)

1. Controller constructor: `can:manage-ilanlar`
2. Route middleware: `can:edit-ilanlar`
3. Method: `$this->authorize('edit-ilan', $ilan)` (resource-aware, ownership)

Musteri rolü (role_id=4) bu yetkilerin hiçbirine sahip değil → **403**.

### 2.4 Browser login throttle (429)

Login route'u `throttle:5,1` middleware'ine sahiptir. Tekrarlanan login
denemeleri 429 döner (production güvenlik özelliği). Bu yüzden yetkisiz
kullanıcı reddi testi, Laravel kernel üzerinden doğrudan HTTP isteği yapan
bir PHP script ile doğrulanır (`storage/tmp-unauth-rejection-check.php`).

---

## 3. Kanıt Detayları

### 3.1 API Yanıtı (PATCH)
```json
{
  "islem_durumu": "ok",
  "mesaj": "İlan durumu başarıyla mühürlendi.",
  "yayin_durumu": "beklemede"
}
```

### 3.2 DB `yayin_durumu`
```json
{ "id": 9, "yayin_durumu": "beklemede" }
```

### 3.3 Audit Kaydı (`listing_state_transitions`)
```json
{
  "id": 8,
  "ilan_id": 9,
  "from_state": "taslak",
  "to_state": "beklemede",
  "aktan_id": 2,
  "meta": "{\"ip\": \"127.0.0.1\", \"source\": \"admin_update\"}"
}
```

### 3.4 Yetkisiz Kullanıcı Reddi
```
Musteri (role_id=4, user id=13) → PATCH → 403
AuthorizationException: This action is unauthorized.
```

---

## 4. Sonuç

**TC-GT-07 PRODUCTION_VERIFIED** — Yönetici onayı akışı (taslak → incelemede)
production DB üzerinde uçtan uca doğrulandı:

- API, DB ve audit kaydı tutarlı
- Yetki kontrolü 3 katmanlı ve doğru çalışıyor
- Yetkisiz kullanıcı 403 ile reddediliyor
- Browser'da durum değişikliği görünüyor

**Sıradaki adımlar** (kullanıcının onayı sonrası):
1. Yayınlama (taslak → yayında)
2. CRM eşleşmesi
3. Danışman görevi testleri