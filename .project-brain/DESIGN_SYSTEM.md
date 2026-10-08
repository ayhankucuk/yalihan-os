# YALIHAN OS — Design System

> **Son Güncelleme:** 2025-01-26
> **Durum:** AKTIF
> **Versiyon:** 1.0

---

## 1. RENK PALETİ

### Primary Colors
| Name | Light Mode | Dark Mode | Usage |
|------|-----------|-----------|-------|
| Primary Blue | `blue-600` (#2563EB) | `blue-400` | Ana aksiyonlar, linkler, vurgular |
| Primary Hover | `blue-700` (#1D4ED8) | `blue-300` | Hover state |

### Semantic Colors
| Name | Light Mode | Dark Mode | Usage |
|------|-----------|-----------|-------|
| Success | `green-600` | `green-400` | Başarı, onay, aktif |
| Success BG | `green-100` | `green-900/20` | Success kartlar, badge'ler |
| Warning | `yellow-500` | `yellow-400` | Uyarılar, dikkat |
| Error | `red-600` | `red-400` | Hatalar, silme, iptal |
| Error BG | `red-100` | `red-900/20` | Error kartlar |
| Info | `blue-100` | `blue-900/20` | Bilgi kartları |

### Neutral Colors
| Name | Light Mode | Dark Mode | Usage |
|------|-----------|-----------|-------|
| Background | `white` (#FFFFFF) | `slate-900` (#0F172A) | Sayfa arka planı |
| Card BG | `white` (#FFFFFF) | `slate-900` (#0F172A) | Kart arka planı |
| Border | `gray-200` (#E5E7EB) | `slate-700` (#334155) | Kart border, ayırıcılar |
| Text Primary | `gray-900` (#111827) | `slate-100` (#F1F5F9) | Ana metin |
| Text Secondary | `gray-600` (#4B5563) | `gray-400` (#9CA3AF) | İkincil metin, açıklamalar |
| Text Muted | `gray-500` (#6B7280) | `gray-500` (#6B7280) | Placeholder, disabled |

---

## 2. TYPOGRAPHY

### Font Family
```html
<!-- Sistem font -->
font-sans (Inter, -apple-system, BlinkMacSystemFont, sans-serif)
```

### Heading Sizes
| Element | Size | Class | Font Weight |
|---------|------|-------|-------------|
| H1 | 2.25rem (36px) | `text-3xl` | `font-bold` |
| H2 | 1.5rem (24px) | `text-2xl` | `font-semibold` |
| H3 | 1.125rem (18px) | `text-lg` | `font-semibold` |
| Body | 1rem (16px) | `text-base` | `font-normal` |
| Small | 0.875rem (14px) | `text-sm` | `font-normal` |
| XS | 0.75rem (12px) | `text-xs` | `font-normal` |

### Text Colors
```html
<!-- Standart metin -->
text-gray-900 dark:text-slate-100    <!-- Ana başlık -->
text-gray-700 dark:text-slate-200    <!-- İkincil başlık -->
text-gray-600 dark:text-gray-400     <!-- Açıklama metni -->
text-gray-500 dark:text-gray-500     <!-- Placeholder, muted -->
```

---

## 3. SPACING & SIZING

### Card Padding
```html
p-6  <!-- Standart kart padding -->
```

### Gap
```html
gap-4  <!-- Kartlar arası boşluk -->
space-y-6  <!-- Dikey form elemanları arası -->
```

### Border Radius
```html
rounded-lg   <!-- 0.5rem - Küçük elementler -->
rounded-xl    <!-- 0.75rem - Kartlar, modallar -->
rounded-full  <!-- Tam yuvarlak - Badge, avatar -->
```

---

## 4. SHADOWS

### Card Shadow
```html
<!-- Light mode -->
shadow-sm

<!-- Hover (opsiyonel) -->
hover:shadow-md
```

### Dark Mode
```html
<!-- Dark mode'da shadow YOK -->
dark:shadow-none
```

---

## 5. BUTTONS

### Primary Button
```html
<button class="
    px-4 py-2.5
    bg-blue-600 text-white
    rounded-lg
    font-medium
    hover:bg-blue-700
    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
    transition-all duration-200
    shadow-sm hover:shadow-md
    dark:shadow-none
">
    Buton Metni
</button>
```

### Secondary Button
```html
<button class="
    px-4 py-2.5
    bg-white dark:bg-slate-900
    text-gray-700 dark:text-slate-200
    border border-gray-300 dark:border-gray-600
    rounded-lg
    font-medium
    hover:bg-gray-50 dark:hover:bg-gray-700
    focus:outline-none focus:ring-2 focus:ring-gray-500
    transition-all duration-200
">
    Buton Metni
</button>
```

### Button Sizes
| Size | Padding | Text | Class |
|------|---------|------|-------|
| Small | `px-3 py-1.5` | `text-sm` | `rounded-md` |
| Medium | `px-4 py-2.5` | `text-base` | `rounded-lg` |
| Large | `px-6 py-3` | `text-lg` | `rounded-lg` |

### Button Variants
| Variant | BG | Text | Border |
|---------|-----|------|--------|
| Primary | `blue-600` | `white` | Yok |
| Secondary | `white`/`slate-900` | `gray-700`/`slate-200` | `gray-300`/`gray-600` |
| Success | `green-600` | `white` | Yok |
| Danger | `red-600` | `white` | Yok |

---

## 6. CARDS

### Standard Card
```html
<div class="
    rounded-xl
    border border-gray-200 dark:border-slate-700
    bg-white dark:bg-slate-900
    shadow-sm
    p-6
    dark:shadow-none
    dark:border-slate-700
    transition-shadow hover:shadow-md
">
    <!-- Card content -->
</div>
```

### Card with Header
```html
<div class="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
    <div class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
        <!-- Header content -->
    </div>
    <div class="p-6">
        <!-- Body content -->
    </div>
</div>
```

---

## 7. FORMS

### Input Field
```html
<input
    type="text"
    class="
        w-full
        px-4 py-2.5
        border border-gray-300 dark:border-gray-600
        rounded-lg
        bg-white dark:bg-slate-900
        text-gray-900 dark:text-white
        placeholder-gray-400 dark:placeholder-gray-500
        focus:ring-2 focus:ring-blue-500 focus:border-transparent
        transition-all duration-200
        dark:text-slate-100
    "
/>
```

### Select
```html
<select
    class="
        w-full
        px-4 py-2.5
        border border-gray-300 dark:border-gray-600
        rounded-lg
        bg-white dark:bg-slate-900
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-blue-500 focus:border-transparent
        transition-all duration-200
    "
>
```

### Toggle Switch
```html
<x-admin.toggle
    name="feature_enabled"
    :checked="$settings['feature_enabled'] ?? false"
/>
```

### Checkbox / Radio
```html
<input
    type="checkbox"
    class="
        w-4 h-4
        rounded
        border-gray-300 dark:border-gray-600
        text-blue-600
        focus:ring-blue-500
        dark:bg-slate-900
    "
/>
```

---

## 8. BADGES & LABELS

### Status Badge
```html
<span class="
    rounded-full
    px-3 py-1
    text-xs font-medium
    bg-green-100 dark:bg-green-900
    text-green-800 dark:text-green-200
">
    Aktif
</span>
```

### Badge Variants
| Status | Light BG | Light Text | Dark BG | Dark Text |
|--------|----------|------------|---------|-----------|
| Success | `green-100` | `green-800` | `green-900` | `green-200` |
| Warning | `yellow-100` | `yellow-800` | `yellow-900` | `yellow-200` |
| Error | `red-100` | `red-800` | `red-900` | `red-200` |
| Info | `blue-100` | `blue-800` | `blue-900` | `blue-200` |
| Neutral | `gray-100` | `gray-800` | `gray-700` | `gray-200` |

---

## 9. ALERTS & NOTIFICATIONS

### Success Alert
```html
<div class="
    rounded-lg
    border-l-4 border-green-500
    bg-green-50 dark:bg-green-900/20
    p-4
    shadow-sm dark:shadow-none
">
    <div class="flex">
        <svg class="w-5 h-5 text-green-500 mr-3">...</svg>
        <p class="text-green-800 dark:text-green-200 font-medium">
            Başarı mesajı
        </p>
    </div>
</div>
```

### Error Alert
```html
<div class="
    rounded-lg
    border-l-4 border-red-500
    bg-red-50 dark:bg-red-900/20
    p-4
    shadow-sm dark:shadow-none
">
```

### Info Alert
```html
<div class="
    rounded-lg
    border-l-4 border-blue-500
    bg-blue-50 dark:bg-blue-900/20
    p-4
    shadow-sm dark:shadow-none
">
```

---

## 10. TABLES

### Standard Table
```html
<table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
    <thead class="bg-gray-50 dark:bg-slate-800">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Başlık
            </th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                İçerik
            </td>
        </tr>
    </tbody>
</table>
```

---

## 11. NAVIGATION

### Sidebar Item
```html
<a
    href="#"
    class="
        flex items-center px-4 py-2
        text-gray-700 dark:text-gray-200
        hover:bg-gray-100 dark:hover:bg-slate-700
        rounded-lg
        transition-colors duration-200
    "
>
```

### Breadcrumb
```html
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="#" class="text-gray-700 dark:text-gray-200 hover:text-blue-600">
                Home
            </a>
        </li>
    </ol>
</nav>
```

---

## 12. GRID SYSTEM

### Page Grid
```html
<!-- 3-column cards -->
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Cards -->
</div>

<!-- 4-column stats -->
<div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
    <!-- Stats -->
</div>

<!-- 2-column forms -->
<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <!-- Form fields -->
</div>
```

---

## 13. RESPONSIVE BREAKPOINTS

| Breakpoint | Prefix | Width |
|------------|--------|-------|
| Mobile | (default) | < 640px |
| Tablet | `md:` | 640px - 767px |
| Small Desktop | `lg:` | 768px - 1023px |
| Desktop | `xl:` | 1024px - 1279px |
| Large Desktop | `2xl:` | 1280px+ |

---

## 14. ICONS

### Icon Size Standard
| Context | Size | Class |
|---------|------|-------|
| Inline (text ile) | 20x20 | `w-5 h-5` |
| Button Icon | 20x20 | `w-5 h-5` |
| Card Icon | 24x24 | `w-6 h-6` |
| Header Icon | 32x32 | `w-8 h-8` |
| Page Icon | 32x32 | `w-8 h-8` |

### Icon Colors
```html
<!-- Primary -->
text-blue-600 dark:text-blue-400

<!-- Success -->
text-green-600 dark:text-green-400

<!-- Muted -->
text-gray-400 dark:text-gray-500
```

---

## 15. DARK MODE PATTERN

### Always Include
```html
<!-- Text -->
text-gray-900 dark:text-slate-100

<!-- Background -->
bg-white dark:bg-slate-900

<!-- Border -->
border-gray-200 dark:border-slate-700

<!-- Shadows -->
shadow-sm
dark:shadow-none

<!-- Hover -->
hover:bg-gray-100 dark:hover:bg-slate-700
```

---

## 16. ANIMATION & TRANSITIONS

### Standard Transition
```html
transition-all duration-200
```

### Hover Scale
```html
hover:scale-105 active:scale-95
```

### Fade In
```html
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0 transform scale-95"
x-transition:enter-end="opacity-100 transform scale-100"
```

---

## 17. CHECKLIST — UYGULAMA

Yeni view oluştururken veya düzenlerken kontrol listesi:

- [ ] Background: `bg-white dark:bg-slate-900`
- [ ] Border: `border-gray-200 dark:border-slate-700`
- [ ] Text: `text-gray-900 dark:text-slate-100`
- [ ] Card: `rounded-xl shadow-sm dark:shadow-none`
- [ ] Primary Button: `bg-blue-600 text-white hover:bg-blue-700`
- [ ] Secondary Button: `bg-white dark:bg-slate-900 border border-gray-300`
- [ ] Input: `border-gray-300 dark:border-gray-600 focus:ring-blue-500`
- [ ] Badge: `rounded-full px-3 py-1 text-xs`
- [ ] Icon: `w-5 h-5` (inline) veya `w-6 h-6` (card)
- [ ] Padding: `p-6` (card), `px-4 py-2.5` (button)
- [ ] Spacing: `gap-4`, `space-y-6`

---

## 18. ANTI-PATTERN (YAPMA!)

### ✗ Yapma
```html
<!-- Farklı renk tonları kullanma -->
bg-blue-500 hover:bg-blue-600

<!-- Shadow dark mode'da -->
shadow-md dark:shadow-lg

<!-- Inline style -->
style="color: #333"

<!-- Eski border renk -->
border-gray-300 dark:border-gray-700
```

### ✓ Yap
```html
<!-- Tutarlı renkler -->
bg-blue-600 hover:bg-blue-700

<!-- Dark mode shadow yok -->
shadow-sm dark:shadow-none

<!-- Tailwind class -->
text-gray-900 dark:text-slate-100

<!-- Standart border -->
border-gray-200 dark:border-slate-700
```

---

## 19. COMPONENT LIBRARY

Kullanılabilir bileşenler (`resources/views/components/admin/`):

| Component | Kullanım |
|-----------|----------|
| `<x-admin.card>` | Standart kart |
| `<x-admin.form-field>` | Form alanı wrapper |
| `<x-admin.toggle>` | Toggle switch |
| `<x-admin.select>` | Select dropdown |
| `<x-admin.alert>` | Alert/notification |
| `<x-admin.button>` | Standart buton |
| `<x-admin.badge>` | Status badge |

---

## 20. DOSYA YAPISI

```
resources/views/
├── admin/
│   ├── layouts/
│   │   └── admin.blade.php          # Layout (CSS vars burada)
│   ├── components/
│   │   └── admin/                   # Reusable bileşenler
│   │       ├── card.blade.php
│   │       ├── button.blade.php
│   │       ├── form-field.blade.php
│   │       ├── toggle.blade.php
│   │       ├── select.blade.php
│   │       └── alert.blade.php
│   └── ...                          # Sayfalar
```

---

## CHANGELOG

| Versiyon | Tarih | Değişiklik |
|----------|-------|-------------|
| 1.0 | 2025-01-26 | İlk versiyon |
