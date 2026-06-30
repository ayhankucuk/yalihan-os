# İlan Yaşam Döngüsü — `yayin_durumu` Durum Makinesi

> SSOT: [`app/StateMachines/ListingStateMachine.php`](../../app/StateMachines/ListingStateMachine.php)
> Durum alanı: `ilanlar.yayin_durumu` (string, 6 değer)
> Son güncelleme: 2026-06-16

---

## Durum Tanımları

| Durum | Değer | Açıklama |
|-------|-------|----------|
| **Taslak** | `taslak` | İlan oluşturulmuş ama tamamlanmamış. AI skoru hesaplanmamış. |
| **Hazır** | `hazir` | Tüm zorunlu alanlar dolu, yayına alınabilir. |
| **Yayında** | `yayinda` | Aktif olarak listeleniyor, arama sonuçlarında görünür. |
| **Pasif** | `pasif` | Geçici olarak yayından kaldırıldı. Veri korunuyor. |
| **Beklemede** | `beklemede` | Moderasyon/onay sürecinde. |
| **Arşiv** | `arsiv` | Kalıcı olarak kapatıldı. Salt okunur. |

---

## İzin Verilen Geçişler

```
taslak ──────────► hazir ──────────► yayinda
   │                 │                  │
   │                 │                  ▼
   │                 │              pasif ◄──► yayinda
   │                 │                  │
   │                 ▼                  ▼
   └──────────► beklemede ──────────► arsiv
                                        ▲
                               pasif ───┘
```

| Kaynak | Hedef | Tetikleyen | Kural |
|--------|-------|-----------|-------|
| `taslak` | `hazir` | Kullanıcı "Hazırla" | Tüm zorunlu alanlar dolu olmalı |
| `taslak` | `beklemede` | Admin | Manuel moderasyon |
| `hazir` | `yayinda` | Kullanıcı "Yayınla" | `cortexScore >= 40` veya "Düşük Skorla Kaydet" |
| `hazir` | `beklemede` | Admin | Manuel moderasyon |
| `yayinda` | `pasif` | Kullanıcı/Admin | İstediği zaman |
| `pasif` | `yayinda` | Kullanıcı/Admin | Yeniden aktifleştirme |
| `pasif` | `arsiv` | Kullanıcı/Admin | Kalıcı kapatma |
| `beklemede` | `yayinda` | Admin onayı | Moderasyon geçti |
| `beklemede` | `arsiv` | Admin reddi | Moderasyon reddedildi |
| `yayinda` | `arsiv` | Admin | Acil kapatma |

---

## Yasak Geçişler

| Kaynak | Hedef | Neden Yasak |
|--------|-------|-------------|
| `arsiv` | herhangi | Arşiv terminal state — geri dönüş yok |
| `yayinda` | `taslak` | Veri kaybı riski |
| `hazir` | `taslak` | Geriye gidiş yok |

---

## AI Skoru ve Yayınlama Kuralı

```php
// AIController → YalihanCortex → cortexScore hesapla
if ($cortexScore === 0) {
    // "Taslak Olarak Kaydet" — her zaman aktif (AI bloke etmez)
} elseif ($cortexScore < 40) {
    // "Düşük Skorla Kaydet" — sarı uyarı, kullanıcı onaylar
} else {
    // "Yayınla" — yeşil, direkt yayında
}
```

**Kural:** AI asla kullanıcıyı bloke etmez. `cortexScore=0` → taslak kaydı her zaman mümkün.

---

## Write Authority Zinciri

```
StoreIlanRequest (validation)
    ↓
IlanCrudController::store()
    ↓
IlanCrudService::store()  ← TEK DB WRITE AUTH
    ↓
IlanRepository::create()
    ↓
ListingStateMachine::transition()  ← durum geçişi burada
```

> ⚠️ `ListingStateMachine` bypass YASAK. Durum geçişleri doğrudan model üzerinden yapılamaz.

---

## İlgili Dosyalar

| Dosya | Rol |
|-------|-----|
| [`app/StateMachines/ListingStateMachine.php`](../../app/StateMachines/ListingStateMachine.php) | Durum makinesi SSOT |
| [`app/Services/Ilan/IlanCrudService.php`](../../app/Services/Ilan/IlanCrudService.php) | Write authority |
| [`app/Http/Requests/Ilan/StoreIlanRequest.php`](../../app/Http/Requests/Ilan/StoreIlanRequest.php) | Validation |
| [`app/Models/Ilan.php`](../../app/Models/Ilan.php) | Model — `yayin_durumu` scope'ları |
| [`docs/architecture/flows.md`](../architecture/flows.md) | Genel iş akışları |
