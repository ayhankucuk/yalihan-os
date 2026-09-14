---
name: page-design-architecture-auditor
description: Yalıhan OS sayfalarını ve görünüm hiyerarşisini; Mediterranean Tasarım Sistemi, Blade layout uyumu, ölü/yetim view tespiti, route tekilliği, Alpine.js reaktivitesi ve SAB mimari kuralları ekseninde uçtan uca denetler.
---

# Yalıhan OS — Sayfa Tasarım ve Mimari Denetim Yeteneği (Page Design & Architecture Auditor)

Bu yetenek; Yalıhan OS bünyesindeki herhangi bir sayfa (veya sayfa grubu) istendiğinde, tasarımı, kullanıcı ergonomisini, bileşen hiyerarşisini ve mimari doğruluğunu 5 aşamalı katı bir protokolle denetler.

---

## 🎯 Ne Zaman Kullanılır?

- Bir sayfanın (örneğin `/admin/ilanlar`, `/ilanlar/{id}`, `/admin/dashboard`, `/owner/ilanlar`) tasarımı, yapısı veya kod temizliği sorulduğunda.
- Yeni bir sayfa yazılmadan önce veya mevcut bir sayfa yeniden yapılandırılırken (refactor).
- Sistemde mükerrer (duplicate), ölü (ghost/orphan) view ve route karmaşası şüphesi olduğunda.

---

## 🛡️ 5 Aşamalı Denetim Protokolü

### 1. Envanter ve Rota Tekilliği (Discovery & Route Single Source of Truth)
Sayfa ve rota hiyerarşisini tara:
- **Aktif Dosyalar:** Controller tarafından çağrılan gerçek view dosyasını bul (`view('...')`).
- **Ölü/Yetim (Ghost/Orphan) Dosyalar:** Dizinlerde kalan, hiçbir controller tarafından çağrılmayan veya eski İngilizce çoğul adlandırmaları (`ilans/`, eski `ilanlar/` kalıntıları) tespit et.
- **Route Çakışması:** Aynı sayfa için birden fazla yerde (`routes/admin.php`, `routes/admin/*.php`, `routes/web.php`) duplicate veya çakışan rota tanımlarını tara.

### 2. Akdeniz Lüks Tasarım Sistemi (Mediterranean Luxury Design System)
Görsel estetiği Yalıhan OS tasarım anayasasına göre denetle:
- **Renk Paleti Uyumu:** 
  - ❌ Standart jenerik Tailwind mavileri (`blue-600`, `indigo-600`) yerine;
  - ✅ **Derin Lacivert (`#0A1628` / `--navy`)**, **Sıcak Altın (`#C9A84C` / `--gold`)**, **Krem (`#F8F6F1` / `--cream`)** ve Slate tonları.
- **Dış Resim Bağımlılığı Yasağı:** `source.unsplash.com`, `transparenttextures.com` vb. dış URL kullanımı kesinlikle yasaktır. Saf CSS gradient veya dahili SVG kullanılmalıdır.
- **Dark Mode Paritesi:** Tüm kart ve metinlerde `dark:bg-slate-900`, `dark:text-slate-100`, `dark:border-slate-800` eksiksiz bulunmalıdır.
- **İkon Standardı:** Inline `<svg>` veya Font Awesome (`fa-`) kesinlikle yasaktır; sadece `<x-icon name="..." />` bileşeni kullanılmalıdır.

### 3. Kullanıcı Deneyimi ve Arayüz Ergonomisi (UX & Ergonomics)
- **Satır ve Grid Yoğunluğu (Density):** Tablolarda devasa görseller (`w-48 h-32`) yerine dikey kaydırmayı rahatlatan optimize thumbnail (`w-20 h-16` / `w-24 h-20`) boyutları tercih edilmelidir.
- **Toplu İşlem Panelleri (Bulk Actions):** Sayfa akışını bölen devasa bloklar yerine, sayfa altına sabitlenen modern *Sticky / Floating Action Bar* kullanılmalıdır.
- **Terminoloji ve Sadeleştirme:** "Klasifikasyon", "Responsibilite", "Terminasyon" gibi kullanıcıyı yoran aşırı teknik terimler yerine sade ve kurumsal gayrimenkul dili kullanılmalıdır.

### 4. SAB Mimari & Güvenlik Denetimi (Architecture & Tenant Boundary)
- **Doğru Layout `@extends`:**
  - `resources/views/frontend/` ➔ `@extends('layouts.frontend')`
  - `resources/views/admin/` ➔ `@extends('admin.layouts.admin')` (veya `@extends('layouts.admin')`)
  - `resources/views/owner/` ➔ `@extends('layouts.owner')`
- **Tenant İzolasyonu (SAB Kural 1):** 
  - Admin/Danışman sayfalarında sorgular tenant context ile filtrelenmelidir.
  - Ziyaretçi kamu sayfalarında (`IlanPublicController`) misafir kullanıcılar için `withoutGlobalScope(TenantScope::class)` güvenli bypass'ı uygulanmalı, aksi takdirde misafir 404 almamalıdır.
- **Thin Controller Zinciri:** Controller'da doğrudan veri mutasyonu olmamalı, `Service` ve `Repository` üzerinden akmalıdır.

### 5. Deneysel Doğrulama (Empirical HTTP & Gate Verification)
Denetim tamamlandığında varsayımda bulunma:
```bash
# 1. Sayfa HTTP durum kodunu tara (200 OK olmalı)
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/<route-url>

# 2. Antigravity kalite kapılarını çalıştır
./scripts/tools/antigravity-full-gate.sh --quick
```

---

## 📋 Denetim ve Uygulama Şablonu

Kullanıcıya denetim sunulurken ve değişiklik uygulanırken şu kurallara uyulur:
1. **Sayfa Envanteri:** İlgili sayfalar, roller ve URL eşleşmeleri tablosu.
2. **Tasarım & UI:** Renk, dark mode, tipografi, görsel hiyerarşi ve dış bağımlılık kontrolü (Navy `#0A1628` + Gold `#C9A84C`).
3. **UX & Ergonomi:** Tablo yoğunluğu (`w-24 h-16` kompakt thumbnail), floating işlem çubukları, filtreleme akışı.
4. **Blade Bileşen Kuralı:** Dynamic class için `:class` yerine `x-bind:class` kullan (Blade PHP parse çakışmasını önlemek için).
5. **Mimari & Güvenlik:** Layout uyumu, tenant scope durumu, vanilla JS / Alpine.js çakışmalarının temizliği.
6. **Deneysel Doğrulama:** HTTP durum kodları (`curl`), puppeteer görsel doğrulaması ve `antigravity-full-gate.sh` testi.
