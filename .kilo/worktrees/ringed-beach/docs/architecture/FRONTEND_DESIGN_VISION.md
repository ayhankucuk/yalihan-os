# Frontend Tasarım Vizyonu

**Tarih:** 18 Mayıs 2026  
**Durum:** Planlama — implementasyon bekliyor  
**Hedef:** Premium AI-destekli danışman arayüzü  
**Stack:** Blade + Alpine.js + Tailwind (değişmiyor)

---

## Temel Karar: Dark Theme, Tek Dil

Sistemin tamamı **koyu tema**. Light mode şimdilik yok — yarım dark/yarım light her zaman daha kötü görünür. Dil: **Türkçe**. Teknik terimler (AI, CRM, ROI) olduğu gibi kalır.

---

## Tasarım Referansı

**Linear + Apple karışımı.**

- Admin/Advisor paneli → Linear: veri yoğun, net tipografi, keskin geometri
- Public/Frontend → Apple: büyük görseller, animasyonlu geçişler, temiz boşluklar

---

## Renk Paleti (Tek Standart)

```css
:root {
    /* Arka planlar */
    --bg-base:    #0F172A;  /* slate-900 — ana zemin */
    --bg-surface: #1E293B;  /* slate-800 — kart/panel */
    --bg-raised:  #293548;  /* slate-750 — hover/raised */
    --border:     #334155;  /* slate-700 — tüm sınırlar */

    /* Primary — TEK primary renk, başka yok */
    --color-primary:       #3B82F6;  /* blue-500 */
    --color-primary-hover: #2563EB;  /* blue-600 */
    --color-primary-muted: rgba(59, 130, 246, 0.15);

    /* Durum renkleri */
    --color-success: #10B981;  /* emerald-500 — pozitif/başarı */
    --color-warning: #F59E0B;  /* amber-500 — dikkat */
    --color-error:   #EF4444;  /* red-500 — hata */

    /* Tipografi */
    --text-primary: #F8FAFC;   /* slate-50 */
    --text-muted:   #94A3B8;   /* slate-400 */
    --text-subtle:  #64748B;   /* slate-500 */

    /* AI aksanı — sadece AI elemanlarında */
    --ai-accent:       #818CF8;  /* indigo-400 */
    --ai-accent-muted: rgba(129, 140, 248, 0.12);
}
```

**Kural:** `indigo`, `purple`, `violet`, `teal` Tailwind class'ları yasak. Sadece `blue` (primary), `emerald` (success), `amber` (warning), `red` (error), `indigo` (AI-only).

---

## Layout Mimarisi

```
┌─────────────────────────────────────────────────────────────┐
│  [Sol Sidebar 64px]  [Ana İçerik — esnek]  [AI Panel 380px] │
│                                              ← slide toggle  │
│  • Nav ikonları      • Sayfa içeriği         • Copilot       │
│  • Tenant badge      • Tablolar              • Öneriler      │
│  • Profil            • Grafik/Harita         • Aksiyonlar    │
└─────────────────────────────────────────────────────────────┘
```

**Sol Sidebar:** 64px genişlik, sadece ikonlar. Hover'da tooltip ile label. Tıklayınca 240px'e genişler (Alpine.js `x-transition`).

**AI Copilot Panel:** Sağdan `slide-in`. Her sayfada var, gizli başlar. Sağ üstteki "AI" butonu ile toggle. `x-transition:enter="translate-x-full"` → `translate-x-0`.

**Mobil:** Sidebar → bottom navigation. AI Panel → bottom sheet (yukarı kaydırma).

---

## AI Katmanı — 3 Öncelikli Bileşen

### 1. Global Komut Paleti (`Cmd+K`)

Her sayfada, her zaman. Doğal dil kabul eder.

```
┌──────────────────────────────────────────────┐
│  🔍  Ne yapmak istiyorsun?                   │
├──────────────────────────────────────────────┤
│  > Bodrum'da bu ay kapanmamış ilanlar        │
│  > Ahmet Yılmaz'a WhatsApp gönder            │
│  > Yeni villa ilanı oluştur                  │
│  > Bu haftaki fırsatları göster              │
└──────────────────────────────────────────────┘
```

**Teknik:** Alpine.js + `@keydown.meta.k.window` + `NLPProcessor` (zaten var). 2-3 günlük iş.

**Route intent'leri:**
- "ilan" → `/admin/ilanlar/create`
- "müşteri / kişi" → `/admin/crm/...`
- "gönder / mesaj" → WhatsApp servisi
- Eşleşme yoksa → Copilot'a ilet

---

### 2. Copilot Yan Paneli

Sağdan slide açılan, her advisor sayfasında bağlamsal.

```
┌─────────────────────────┐
│  ✦ AI Copilot           │
│  ─────────────────────  │
│  📍 Bodrum Satışları     │
│  Bu hafta 3 ilan         │
│  kapanma potansiyeli var │
│                         │
│  [Listele] [Raporla]    │
│  ─────────────────────  │
│  💬 Soru sor...         │
│  [________________________│
└─────────────────────────┘
```

**Teknik:** `copilot.blade.php` zaten var, `CortexOrchestratorService`'e bağlanacak. `x-data="copilot()"` Alpine component.

---

### 3. Sabah Aksiyon Kartları (Dashboard)

Danışman paneli açıldığında AI'ın seçtiği 3-5 öncelik.

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  🔥 Bugün Ara    │  │  📋 Fiyat Güncelle│  │  💬 Yanıt Bekliyor│
│  Mehmet Yılmaz   │  │  Villa Bodrum     │  │  3 WhatsApp       │
│  3 gündür sessiz │  │  Piyasanın %12    │  │  mesajı var       │
│  [Ara]           │  │  üstünde          │  │  [Gelen Kutusu]   │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

**Teknik:** `DecisionEngineService` + `OpportunityService` (zaten var) → view'a geçilecek veri.

---

## Animasyon Kuralları

Alpine.js `x-transition` ile yapılır, CSS animation kütüphanesi yok.

```html
<!-- Panel slide-in -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="translate-x-full opacity-0"
x-transition:enter-end="translate-x-0 opacity-100"

<!-- Kart fade-in -->
x-transition:enter="transition ease-out duration-150"
x-transition:enter-start="opacity-0 scale-95"
x-transition:enter-end="opacity-100 scale-100"

<!-- Modal -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0"
x-transition:enter-end="opacity-100"
```

**Kural:** Animasyon süresi max 250ms. Daha uzun = yavaş hissettirir.

---

## Tipografi

```css
/* Başlıklar */
font-family: 'Inter', system-ui, sans-serif;

/* Kod / metrik değerler */
font-family: 'JetBrains Mono', 'Fira Code', monospace;
```

Boyut hiyerarşisi:
- Sayfa başlığı: `text-2xl font-bold tracking-tight`
- Bölüm başlığı: `text-base font-semibold`
- Gövde: `text-sm`
- Yardımcı: `text-xs text-muted`

---

## Bileşen Standartları

### Kart
```html
<div class="rounded-xl border border-slate-700 bg-slate-800 p-6">
```

### Primary Buton
```html
<button class="rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white
               hover:bg-blue-600 transition-colors duration-150">
```

### AI Aksanı (sadece AI elemanları)
```html
<div class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-4">
    <span class="text-indigo-400">✦</span>
```

### Input
```html
<input class="admin-input">
/* admin-input: w-full rounded-lg border border-slate-700 bg-slate-900
               px-4 py-2.5 text-sm text-slate-100
               placeholder-slate-500 focus:border-blue-500
               focus:outline-none focus:ring-1 focus:ring-blue-500 */
```

### Badge / Durum
```html
<!-- Aktif -->
<span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400">
<!-- Beklemede -->
<span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-xs font-medium text-amber-400">
<!-- Pasif -->
<span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs font-medium text-slate-400">
```

---

## İkon Standardı

**Sadece inline SVG.** FontAwesome kaldırılıyor.

```html
<!-- Örnek: arama ikonu -->
<svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
</svg>
```

---

## Uygulama Sırası

### Sprint 1 — Temel (3-5 gün)
- [ ] `app.css` token'larını güncelle (bu dokümandaki palette)
- [ ] `admin-input` Tailwind component'i
- [ ] Sol sidebar yeniden yaz (64px icon-only)
- [ ] Renk standardizasyonu: admin/advisor → blue-only primary

### Sprint 2 — AI Katmanı (1 hafta)
- [ ] Global Komut Paleti (`Cmd+K`) Alpine component
- [ ] Copilot Yan Paneli slide mekanizması
- [ ] Dashboard Aksiyon Kartları

### Sprint 3 — Polishing (1 hafta)
- [ ] Animasyon standardizasyonu (tüm sayfalar)
- [ ] FontAwesome kaldır, inline SVG geç
- [ ] Advisor sayfaları Türkçe başlık + responsive

---

## Neyin Değişmediği

- **Stack:** Blade + Alpine.js + Tailwind. React/Vue yok.
- **SAB kuralları:** Thin controller, repository authority — aynen devam.
- **Mevcut route yapısı:** Değişmiyor.
- **673 blade dosyası:** Toplu değil, kademeli dönüşüm.

---

*Son güncelleme: 18 Mayıs 2026*
