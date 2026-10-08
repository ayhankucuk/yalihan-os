# Sistem Ayarları — Audit Raporu

**Tarih:** 2026-01-27
**Analiz Eden:** ATLAS
**Kapsam:** Tüm Ayarlar Sekmeleri

---

## 📊 GENEL DEĞERLENDİRME

| Sekme | Tasarım | Component | Veri | Öncelik |
|-------|---------|-----------|------|---------|
| Genel | ⚠️ Orta | ✅ Modern | ✅ | Düşük |
| Para Birimi | ⚠️ Orta | ⚠️ Mixed | ✅ | Orta |
| Diller | ⚠️ Orta | ⚠️ Mixed | ✅ | Orta |
| Bildirim | ✅ İyi | ✅ Modern | ✅ | — |
| Navigasyon | ✅ İyi | ✅ Modern | ✅ | — |
| Portallar | ⚠️ Orta | ⚠️ Mixed | ✅ | Düşük |
| Fiyatlandırma | ✅ İyi | ✅ Modern | ✅ | — |
| QR Kod | ✅ İyi | ✅ Modern | ✅ | — |
| Kullanıcılar | ✅ İyi | ✅ Modern | ✅ | — |

---

## 🔴 YAPILACAKLAR LİSTESİ

### SEVİYE 1 — KRITIK (Önce)

#### 1. Border Renk Tutarsızlığı
**Dosya:** `currencies.blade.php`, `languages.blade.php`

**Sorun:**
```blade
border-gray-200 dark:border-slate-700  {{-- Farklı renk tonları --}}
```

**Design System'e göre:**
```blade
border-gray-200 dark:border-gray-700
```

**Etkilenen:** Para Birimi, Diller tabloları

---

### SEVİYE 2 — ORTA

#### 2. Toggle Component Kullanılmıyor
**Dosya:** `currencies.blade.php`, `languages.blade.php`

**Sorun:** Custom toggle yerine inline HTML kullanılmış

**Mevcut:**
```blade
<div class="bg-blue-600 relative h-5 w-10 rounded-full...">
```

**Design System:**
```blade
<x-admin.toggle name="aktiflik_durumu" :checked="$curr->aktiflik_durumu" />
```

**Etkilenen:** Para Birimi toggle, Diller toggle

---

#### 3. Emoji Kullanımı
**Dosya:** `currencies.blade.php`, `languages.blade.php`

**Sorun:** Emojiler section başlıklarında kullanılmış

**Mevcut:**
```blade
<h3 class="text-lg font-semibold text-green-600">💰 Para Birimi Yönetimi</h3>
```

**Design System:**
```blade
<h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
    <svg class="h-6 w-6 text-green-600 dark:text-green-400">...</svg>
    Para Birimi Yönetimi
</h3>
```

**Etkilenen:** Para Birimi, Diller, Genel (kısmen)

---

#### 4. Başlık Renk Tutarsızlığı
**Dosya:** Tüm sekmeler

**Sorun:** Bazı başlıklar renkli, bazıları grayscale

**Design System Standart:**
```blade
<h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400">...</svg>
    Başlık
</h3>
```

**Etkilenen:** Para Birimi, Diller, Portallar, Fiyatlandırma, Kullanıcılar

---

### SEVİYE 3 — DÜŞÜK (İyileştirme)

#### 5. Tablo Hover State
**Dosya:** `currencies.blade.php`, `languages.blade.php`

**Sorun:** Dark mode hover belirsiz

**Mevcut:**
```blade
hover:bg-gray-50 dark:hover:bg-slate-800/50
```

**Design System:**
```blade
hover:bg-gray-50 dark:hover:bg-gray-700
```

---

#### 6. Background Renk Tutarsızlığı
**Dosya:** `currencies.blade.php`

**Sorun:**
```blade
bg-gray-50  {{-- Light mode yeterli --}}
dark:bg-slate-800/60  {{-- Opacity kullanılmış --}}
```

**Design System:**
```blade
bg-gray-50 dark:bg-slate-900
```

---

## 📁 DOSYA LİSTESİ

| Dosya | Durum | Öncelik |
|-------|-------|---------|
| `settings/sections/general.blade.php` | ⚠️ Minor | Düşük |
| `settings/sections/currencies.blade.php` | ⚠️ Orta | Orta |
| `settings/sections/languages.blade.php` | ⚠️ Orta | Orta |
| `settings/sections/notifications.blade.php` | ✅ İyi | — |
| `settings/sections/navigation.blade.php` | ✅ İyi | — |
| `settings/sections/portals.blade.php` | ⚠️ Minor | Düşük |
| `settings/sections/pricing.blade.php` | ✅ İyi | — |
| `settings/sections/qrcode.blade.php` | ✅ İyi | — |
| `settings/sections/users.blade.php` | ⚠️ Minor | Düşük |

---

## ✅ YAPILACAKLAR ÖZETİ

| # | Görev | Öncelik | Tahmini Süre |
|---|-------|---------|--------------|
| 1 | Border renk tutarsızlığı (Para Birimi, Diller) | 🔴 Kritik | 15 dk |
| 2 | Toggle component (Para Birimi, Diller) | 🟡 Orta | 30 dk |
| 3 | Emoji → SVG ikon (Para Birimi, Diller) | 🟡 Orta | 20 dk |
| 4 | Başlık renk standardizasyonu | 🟢 Düşük | 30 dk |
| 5 | Tablo hover state düzeltmesi | 🟢 Düşük | 15 dk |
| 6 | Background renk tutarlılığı | 🟢 Düşük | 10 dk |

---

## 🎯 HEDEF

Tüm Ayarlar sekmeleri **Design System** standartlarına uyumlu hale getirilecek:
- ✅ Uniform border renkleri
- ✅ Modern component kullanımı
- ✅ SVG ikon tercihi (emoji yerine)
- ✅ Tutarlı başlık formatı
- ✅ Dark mode uyumluluğu

---

## 📝 TASK KONTRATLARI

### Task 1: Border + Toggle (currencies + languages)
**Öncelik:** Orta
**Dosyalar:**
- `settings/sections/currencies.blade.php`
- `settings/sections/languages.blade.php`

**Yapılacak:**
1. `border-slate-700` → `border-gray-700`
2. Custom toggle → `<x-admin.toggle>`
3. Emoji → SVG ikon
4. Background opacity kaldır

---

### Task 2: Başlık Standardizasyonu
**Öncelik:** Düşük
**Dosyalar:** Tüm sekmeler

**Yapılacak:**
1. Tüm başlıkları uniform formatta
2. Renk paleti: Primary blue icon, grayscale text
