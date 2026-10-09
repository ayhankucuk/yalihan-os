# Design Contract: DC-004 — Settings Architecture Redesign

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** HIGH
**Complexity:** MEDIUM
**Scope:** Frontend/UX only — Backend untouchable

---

## Canonical Authority (UNCHANGED)

| Component | Location | Status |
|-----------|----------|--------|
| AyarlarController | app/Http/Controllers | UNCHANGED |
| Setting Model | app/Models | UNCHANGED |
| Language Model | app/Models | UNCHANGED |
| Currency Model | app/Models | UNCHANGED |
| Routes | /admin/ayarlar/* | UNCHANGED |
| Field Names | All form inputs | PRESERVED |
| Request Contracts | Form requests | PRESERVED |

---

## Evidence

### FACT (Current State)

| File | Lines | Status |
|------|-------|--------|
| `admin/settings/index.blade.php` | 1029 | Main settings (9 tabs) |
| `admin/ayarlar/index.blade.php` | 69 | Fallback page |

### FACT (Current Tab Structure)

```
9 Tabs (flat hierarchy):
├── Genel                    (site_title, site_url, logo, favicon, lang)
├── Bildirimler              (email, whatsapp, telegram toggles)
├── Portal Entegrasyonları   (sahibinden, heps emlak API keys)
├── Fiyatlandırma            (price_rounding)
├── QR Kod                   (qrcode settings)
├── Navigasyon               (navigation settings)
├── Kullanıcı Yönetimi       (user registration, password strength)
├── Diller                   (language table CRUD)
└── Para Birimleri           (currency table CRUD)
```

### FACT (Current Form Structure)

```blade
{{-- Main form --}}
<form method="POST" action="{{ route('admin.ayarlar.bulk-update') }}" id="settingsForm">
    @csrf
    @method('POST')

    {{-- Tab navigation (9 buttons) --}}
    <nav class="flex ...">
        <button type="button" data-tab="genel">Genel</button>
        ...
    </nav>

    {{-- Tab contents --}}
    <div id="genel" class="tab-content">...fields...</div>
    <div id="bildirim" class="tab-content hidden">...fields...</div>
    ...
</form>
```

### FACT (Current Issues)

| Issue | Description |
|-------|-------------|
| Flat hierarchy | 9 tabs at same level — cognitive overload |
| Tab width | Long labels overflow on smaller screens |
| Cross-tab dependencies | "Para birimi" referenced in "Fiyatlandırma" |
| No section hierarchy | All settings equal weight |
| Form consistency | Varying input patterns per tab |
| Validation UX | No inline validation, only server errors |
| Loading states | Full page reload on save |
| Accessibility | Limited ARIA, keyboard nav issues |

---

## Karar

### PROPOSED: Hierarchical Settings Architecture

**Philosophy:** "Progressive Disclosure" — show only what's relevant, hide complexity until needed.

### Information Architecture

```
Sistem Ayarları (Hub)
├── Temel
│   ├── Genel (site, logo, lang)
│   └── Bildirimler (notifications)
├── Entegrasyonlar
│   ├── Portallar (sahibinden, heps emlak)
│   └── QR Kod
├── İçerik
│   ├── Fiyatlandırma
│   └── Navigasyon
└── Sistem
    ├── Kullanıcı Yönetimi
    ├── Diller (CRUD)
    └── Para Birimleri (CRUD)
```

### Grouping Rationale

| Group | Tabs | Rationale |
|-------|------|-----------|
| **Temel** | Genel, Bildirimler | Core site operation |
| **Entegrasyonlar** | Portallar, QR Kod | External integrations |
| **İçerik** | Fiyatlandırma, Navigasyon | Content presentation |
| **Sistem** | Kullanıcı, Diller, Paralar | System administration |

### Tab Icon Mapping

| Group | Tab | Icon |
|-------|-----|------|
| Temel | Genel | Home/Settings |
| Temel | Bildirimler | Bell |
| Entegrasyonlar | Portallar | Globe |
| Entegrasyonlar | QR Kod | QR Code |
| İçerik | Fiyatlandırma | Currency |
| İçerik | Navigasyon | Map |
| Sistem | Kullanıcı | Users |
| Sistem | Diller | Language |
| Sistem | Paralar | Money |

---

## Component Architecture

### 1. Settings Hub (`settings/hub.blade.php`)

```blade
@extends('admin.layouts.admin')

@section('title', 'Sistem Ayarları')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            Sistem Ayarları
        </h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">
            Site genelinde tüm ayarları buradan yönetin
        </p>
    </div>

    {{-- Grouped Cards --}}
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        @each('admin.settings.partials.group-card', $groups, 'group')
    </div>
</div>
@endsection
```

### 2. Group Card (`settings/partials/group-card.blade.php`)

```blade
@props(['group'])

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    {{-- Group Header --}}
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-3">
            <span class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                @include('admin.settings.icons.' . $group['icon'])
            </span>
            {{ $group['label'] }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $group['description'] }}</p>
    </div>

    {{-- Settings List --}}
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @foreach($group['items'] as $item)
        <a href="{{ route('admin.settings.' . $item['route']) }}"
           class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $item['label'] }}</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        @endforeach
    </div>
</div>
```

### 3. Settings Section (`settings/sections/*.blade.php`)

Each tab becomes a dedicated section blade:
- `settings/sections/general.blade.php`
- `settings/sections/notifications.blade.php`
- `settings/sections/portals.blade.php`
- etc.

### 4. Reusable Section Wrapper

```blade
@props([
    'title',
    'description' => null,
])

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
    {{-- Section Header --}}
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
        @if($description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
        @endif
    </div>

    {{-- Section Content --}}
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
```

### 5. Back Button Component

```blade
@props(['href', 'label' => 'Ayarlar'])

<a href="{{ $href }}"
   class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    {{ $label }}
</a>
```

---

## Form Patterns

### Text Input

```blade
<x-admin.form-field label="Site Başlığı" name="site_title" required>
    <x-admin.input
        name="site_title"
        :value="old('site_title', $settings['site_title'] ?? '')"
        placeholder="Yalıhan Emlak"
    />
</x-admin.form-field>
```

### Toggle/Checkbox

```blade
<x-admin.form-field label="E-posta Bildirimleri" name="email_notifications"
    :hint="'Yeni ilan oluşturulduğunda e-posta alın'">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="hidden" name="email_notifications" value="0">
        <input type="checkbox" name="email_notifications" value="1"
               class="sr-only peer"
               {{ old('email_notifications', $settings['email_notifications'] ?? 0) ? 'checked' : '' }}>
        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-100 dark:peer-focus:ring-blue-900 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
    </label>
</x-admin.form-field>
```

### Select with Options

```blade
<x-admin.form-field label="Fiyat Yuvarlama" name="price_rounding">
    <x-admin.select name="price_rounding">
        <option value="1" {{ old('price_rounding', $settings['price_rounding'] ?? 1) == 1 ? 'selected' : '' }}>1</option>
        <option value="100" {{ old('price_rounding', $settings['price_rounding'] ?? 1) == 100 ? 'selected' : '' }}>100</option>
        <option value="1000" {{ old('price_rounding', $settings['price_rounding'] ?? 1) == 1000 ? 'selected' : '' }}>1.000</option>
    </x-admin.select>
</x-admin.form-field>
```

---

## UX Patterns

### 1. Inline Validation

```blade
<x-admin.form-field label="Site URL" name="site_url" required
    :error="$errors->first('site_url')">
    <x-admin.input
        type="url"
        name="site_url"
        :value="old('site_url', $settings['site_url'] ?? '')"
        placeholder="https://example.com"
    />
</x-admin.form-field>
```

### 2. Success Feedback

```blade
@if(session('success'))
    <x-admin.form-alert type="success" title="Kaydedildi">
        Ayarlarınız başarıyla kaydedildi.
    </x-admin.form-alert>
@endif
```

### 3. Loading State

```blade
<form x-data="{ saving: false }" @submit="saving = true">
    <button type="submit" :disabled="saving"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
        <span x-show="!saving">Kaydet</span>
        <span x-show="saving" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Kaydediliyor...
        </span>
    </button>
</form>
```

### 4. Disabled State

```blade
<input type="text" name="field" value="..." disabled
       class="opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 rounded-lg">
```

---

## Responsive Behavior

### Desktop (>1024px)

Full layout with 2-3 column grid for group cards.

### Tablet (768-1024px)

2 column grid for group cards.

### Mobile (<768px)

Stacked layout:
- Full-width cards
- Collapsible groups
- Sticky save button

### Mobile Back + Tab Select

```blade
{{-- Mobile: Back button + Tab selector --}}
<div class="md:hidden mb-4 space-y-3">
    <x-admin.back-button href="{{ route('admin.settings.hub') }}"/>

    <select x-model="activeTab"
            class="block w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-3">
        @foreach($groups as $group)
            <optgroup label="{{ $group['label'] }}">
                @foreach($group['items'] as $item)
                    <option value="{{ $item['route'] }}">{{ $item['label'] }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
```

---

## Accessibility

### Requirements

| ID | Requirement | Implementation |
|----|-------------|-----------------|
| A1 | Keyboard navigation | Tab order, focus indicators |
| A2 | ARIA labels | `aria-label` on all interactive elements |
| A3 | Screen reader | Proper heading hierarchy (h1 > h2 > h3) |
| A4 | Color contrast | 4.5:1 minimum for text |
| A5 | Focus visible | `focus:ring` on all inputs |
| A6 | Error announcements | `aria-live="polite"` for errors |

### Focus Management

```blade
{{-- Focus first error on form submission --}}
<form @submit="
    if (!this.checkValidity()) {
        this.$el.querySelector(':invalid')?.focus();
        return false;
    }
">
```

---

## Data Structure for Controller

```php
// Passed from controller to view — NO BACKEND CHANGES
$settingsGroups = [
    'temel' => [
        'label' => 'Temel Ayarlar',
        'description' => 'Site çalışması için temel ayarlar',
        'icon' => 'settings',
        'items' => [
            ['label' => 'Genel', 'route' => 'general'],
            ['label' => 'Bildirimler', 'route' => 'notifications'],
        ],
    ],
    'entegrasyonlar' => [
        'label' => 'Entegrasyonlar',
        'description' => 'Dış sistemlerle bağlantılar',
        'icon' => 'globe',
        'items' => [
            ['label' => 'Portal Entegrasyonları', 'route' => 'portals'],
            ['label' => 'QR Kod', 'route' => 'qrcode'],
        ],
    ],
    'icerik' => [
        'label' => 'İçerik',
        'description' => 'Site içerik ayarları',
        'icon' => 'document',
        'items' => [
            ['label' => 'Fiyatlandırma', 'route' => 'pricing'],
            ['label' => 'Navigasyon', 'route' => 'navigation'],
        ],
    ],
    'sistem' => [
        'label' => 'Sistem',
        'description' => 'Sistem yönetimi',
        'icon' => 'cog',
        'items' => [
            ['label' => 'Kullanıcı Yönetimi', 'route' => 'users'],
            ['label' => 'Diller', 'route' => 'languages'],
            ['label' => 'Para Birimleri', 'route' => 'currencies'],
        ],
    ],
];
```

---

## Migration Strategy

### Phase 1: Hub Page

1. Create `settings/hub.blade.php`
2. Create `settings/partials/group-card.blade.php`
3. Create icon components
4. **NOTE:** Route stays the same — hub renders in index or separate page

### Phase 2: Section Blades

1. Extract each tab to `settings/sections/*.blade.php`
2. Create section wrapper component
3. **CRITICAL:** Maintain form field names (UNCHANGED)
4. **CRITICAL:** Maintain form action (UNCHANGED)
5. **CRITICAL:** Maintain submit route (UNCHANGED)

### Phase 3: Component Standardization

1. Replace inline inputs with `<x-admin.input>`
2. Replace inline toggles with standardized pattern
3. Add `<x-admin.form-field>` wrappers
4. Add `<x-admin.form-alert>` for feedback

### Phase 4: Polish

1. Add loading states with Alpine.js
2. Add inline validation
3. Add keyboard navigation
4. Accessibility audit

---

## Files to CREATE

```
resources/views/admin/settings/
├── hub.blade.php                          (Hub page - optional)
├── sections/
│   ├── general.blade.php                   (Genel tab)
│   ├── notifications.blade.php             (Bildirimler tab)
│   ├── portals.blade.php                   (Portal Entegrasyonları tab)
│   ├── pricing.blade.php                   (Fiyatlandırma tab)
│   ├── qrcode.blade.php                    (QR Kod tab)
│   ├── navigation.blade.php                 (Navigasyon tab)
│   ├── users.blade.php                     (Kullanıcı Yönetimi tab)
│   ├── languages.blade.php                 (Diller tab)
│   └── currencies.blade.php                (Para Birimleri tab)
└── partials/
    ├── group-card.blade.php                (Group card for hub)
    ├── section-wrapper.blade.php            (Section wrapper)
    └── back-button.blade.php                (Back navigation)
```

## Files to MODIFY

| File | Changes |
|------|---------|
| `admin/settings/index.blade.php` | Refactor to use section blades, add grouping UI |

## Files to PRESERVE (UNCHANGED)

- All field names in forms
- Form action URLs
- Request validation rules
- Controller logic
- Model relationships

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Hub/group view displays all 9 settings grouped | Visual inspection |
| AC2 | Each group/item links to correct section | Click each link |
| AC3 | Section pages preserve field names | Check form HTML |
| AC4 | Section forms submit to same endpoint | Check form action |
| AC5 | Inline validation works | Submit invalid form |
| AC6 | Success/error alerts display | Trigger save |
| AC7 | Loading state shows on submit | Submit form |
| AC8 | Mobile navigation works | Test on mobile viewport |
| AC9 | Keyboard navigation works | Tab through form |
| AC10 | Screen reader announces sections | Test with screen reader |

---

## Technical Notes

### Controller Passing Data (MINIMAL CHANGE)

```php
// AyarlarController — ONLY add this method
public function getSettingsGroups(): array
{
    return [
        'temel' => [...],
        'entegrasyonlar' => [...],
        'icerik' => [...],
        'sistem' => [...],
    ];
}
```

### Routes (UNCHANGED)

```php
// routes/admin.php — NO CHANGES NEEDED
Route::post('/ayarlar/bulk-update', [AyarlarController::class, 'bulkUpdate'])
    ->name('admin.ayarlar.bulk-update');
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Hub page (optional) | LOW | 1 |
| Section blades (9x) | MEDIUM | 9 |
| Section wrapper | LOW | 1 |
| Group card partial | LOW | 1 |
| Back button | LOW | 1 |
| Icon components | LOW | 4 |
| Refactor index | MEDIUM | 1 |
| **TOTAL** | **MEDIUM** | **~15** |

---

## Rollback Plan

1. Keep `admin/settings/index.blade.php` as backup
2. Feature flag between old/new
3. Incremental migration per section

---

*YALIHAN TASARIMCI — DC-004 Design Contract v2.0*
