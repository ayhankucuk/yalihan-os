# Design Contract: DC-002 — Neo Design System Removal

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** CRITICAL (Context7 Violation)

---

## Evidence

### FACT (Context7 Requirement)
```
Context7 explicitly forbids Neo Design System.
Any component using "neo-" prefix or neo/ directory is a violation.
```

### FACT (Current State)
**Neo Directory Contents:**
```
resources/views/components/neo/
├── aktiflik-durumu-badge.blade.php
├── badge.blade.php
├── breadcrumb.blade.php
├── button.blade.php
├── card.blade.php
├── dropdown-item.blade.php
├── dropdown.blade.php
├── empty-state.blade.php
├── form-field.blade.php
├── input.blade.php
├── select.blade.php
├── stat-card.blade.php
└── status-badge.blade.php
```

**Related Files:**
```
resources/css/admin/neo.css
resources/js/admin/neo.js
resources/views/components/admin/neo-skeleton.blade.php
resources/views/components/admin/neo-loading.blade.php
resources/views/components/neo-input.blade.php
resources/views/components/neo-select.blade.php
public/css/admin/neo-skeleton.css
public/css/admin/neo-toast.css
public/build/assets/js/neo-*.js
```

### FACT (Usage Count)
```
97 total usages across 18 files:

x-neo.button              → 42 usages
x-neo.card                → 20 usages
x-neo.status-badge        → 14 usages
x-neo.empty-state         →  9 usages
x-neo.dropdown-item       →  6 usages
x-neo.aktiflik-durumu-badge → 3 usages
x-neo.dropdown            →  2 usages
x-neo.form-field          →  1 usage
```

### FACT (Affected Files)
```
resources/views/admin/blog/posts/index.blade.php
resources/views/admin/blog/posts/show.blade.php
resources/views/admin/blog/tags/index.blade.php
resources/views/admin/blog/categories/index.blade.php
resources/views/admin/eslesmeler/index.blade.php
resources/views/admin/kisiler/takip.blade.php
resources/views/admin/kisiler/show.blade.php
resources/views/admin/danisman/tabs/hakkimda.blade.php
resources/views/admin/danisman/tabs/portfoy.blade.php
resources/views/admin/danisman/edit.blade.php
resources/views/admin/danisman/show.blade.php
resources/views/admin/ozellikler/kategoriler/kategorisiz-ozellikler.blade.php
resources/views/admin/ozellikler/kategoriler/ozellikler.blade.php
resources/views/admin/eslesme/index.blade.php
resources/views/admin/notifications/index.blade.php
resources/views/admin/takim-yonetimi/gorevler/index.blade.php
resources/views/profile/edit.blade.php
resources/views/components/neo/form-field.blade.php
```

---

## Karar

**DESIGN_DECISION:** Neo Design System fully removed. Components replaced with Context7-compliant alternatives.

### Replacement Strategy

| Neo Component | Replacement | Rationale |
|--------------|-------------|-----------|
| `x-neo.button` | `x-admin.button` | Already exists, similar API |
| `x-neo.card` | Inline Tailwind `div` | Simple wrapper, no logic needed |
| `x-neo.empty-state` | Inline Tailwind + SVG | 9 usages, simple pattern |
| `x-neo.status-badge` | `x-admin.badge` | Already exists |
| `x-neo.dropdown` | `x-admin.dropdown` | Already exists |
| `x-neo.dropdown-item` | Inline `<a>` or `x-admin.dropdown` item slot |
| `x-neo.aktiflik-durumu-badge` | Inline span with Tailwind |
| `x-neo.form-field` | Inline Tailwind form pattern |
| `x-neo.input` | Already has `x-input` component |
| `x-neo.select` | Already has `x-select` component |

---

## Scope

### Files to DELETE
```
DELETE:
├── resources/views/components/neo/                    (entire directory)
├── resources/css/admin/neo.css
├── resources/js/admin/neo.js
├── resources/views/components/admin/neo-skeleton.blade.php
├── resources/views/components/admin/neo-loading.blade.php
├── resources/views/components/neo-input.blade.php
├── resources/views/components/neo-select.blade.php
├── public/css/admin/neo-skeleton.css
├── public/css/admin/neo-toast.css
└── public/build/assets/js/neo-*.js
```

### Files to MODIFY
```
MODIFY (replace x-neo.* with Context7 alternatives):
├── resources/views/admin/blog/posts/index.blade.php
├── resources/views/admin/blog/posts/show.blade.php
├── resources/views/admin/blog/tags/index.blade.php
├── resources/views/admin/blog/categories/index.blade.php
├── resources/views/admin/eslesmeler/index.blade.php
├── resources/views/admin/kisiler/takip.blade.php
├── resources/views/admin/kisiler/show.blade.php
├── resources/views/admin/danisman/tabs/hakkimda.blade.php
├── resources/views/admin/danisman/tabs/portfoy.blade.php
├── resources/views/admin/danisman/edit.blade.php
├── resources/views/admin/danisman/show.blade.php
├── resources/views/admin/ozellikler/kategoriler/kategorisiz-ozellikler.blade.php
├── resources/views/admin/ozellikler/kategoriler/ozellikler.blade.php
├── resources/views/admin/eslesme/index.blade.php
├── resources/views/admin/notifications/index.blade.php
├── resources/views/admin/takim-yonetimi/gorevler/index.blade.php
└── resources/views/profile/edit.blade.php
```

### Files NOT Affected
```
├── resources/views/components/admin/button.blade.php  (keep, rename if needed)
├── resources/views/components/admin/badge.blade.php    (keep)
├── resources/views/components/admin/dropdown.blade.php (keep)
├── resources/views/components/input.blade.php         (keep)
├── resources/views/components/select.blade.php        (keep)
```

---

## Replacement Patterns

### x-neo.button → x-admin.button

**Neo API:**
```blade
<x-neo.button variant="primary" href="/url">Text</x-neo.button>
<x-neo.button variant="success" :href="$link">Text</x-neo.button>
<x-neo.button variant="danger" type="submit">Text</x-neo.button>
```

**Replacement:**
```blade
<x-admin.button variant="primary" href="/url">Text</x-admin.button>
<x-admin.button variant="success" :href="$link">Text</x-admin.button>
<x-admin.button variant="danger" type="submit">Text</x-admin.button>
```

**Note:** `x-admin.button` API is identical. Just namespace change.

---

### x-neo.card → Tailwind div

**Neo API:**
```blade
<x-neo.card>
    Content here
</x-neo.card>

<x-neo.card padding="lg" shadow="md">
    <x-slot name="header">Title</x-slot>
    Content
</x-neo.card>
```

**Replacement:**
```blade
<div class="bg-white rounded-lg shadow border border-gray-200 p-6">
    Content here
</div>

<div class="bg-white rounded-lg shadow-md border border-gray-200 p-8">
    <div class="mb-6 pb-4 border-b border-gray-200">
        Title
    </div>
    Content
</div>
```

**Variants:**
| Neo | Tailwind |
|-----|----------|
| `padding="none"` | remove `p-*` |
| `padding="sm"` | `p-4` |
| `padding="default"` | `p-6` |
| `padding="lg"` | `p-8` |
| `shadow="none"` | remove shadow |
| `shadow="sm"` | `shadow-sm` |
| `shadow="default"` | `shadow` |
| `shadow="md"` | `shadow-md` |

---

### x-neo.empty-state → Tailwind + SVG

**Neo API:**
```blade
<x-neo.empty-state 
    title="Henüz yazı yok" 
    description="İlk blog yazınızı oluşturarak başlayın"
    actionHref="/admin/blog/posts/create"
    actionText="Yeni Yazı Ekle"
/>
```

**Replacement:**
```blade
<div class="text-center py-12">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Henüz yazı yok</h3>
    <p class="text-gray-500 mb-6">İlk blog yazınızı oluşturarak başlayın</p>
    @if(isset($actionHref))
        <a href="{{ $actionHref }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            Yeni Yazı Ekle
        </a>
    @endif
</div>
```

---

### x-neo.status-badge → x-admin.badge

**Neo API:**
```blade
<x-neo.status-badge :value="$post->category->name" category="category" />
<x-neo.status-badge :value="$statusLabel" category="status" />
<x-neo.status-badge :value="ucfirst($eslesme->eslesme_durumu)" />
```

**Replacement:**
```blade
<x-admin.badge>{{ $post->category->name }}</x-admin.badge>
<x-admin.badge>{{ $statusLabel }}</x-admin.badge>
<x-admin.badge>{{ ucfirst($eslesme->eslesme_durumu) }}</x-admin.badge>
```

---

### x-neo.aktiflik-durumu-badge → Inline span

**Neo API:**
```blade
<x-neo.aktiflik-durumu-badge :status="$kategori->aktiflik_durumu" />
```

**Replacement:**
```blade
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kategori->aktiflik_durumu ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
    {{ $kategori->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
</span>
```

---

### x-neo.dropdown → x-admin.dropdown

**Neo API:**
```blade
<x-neo.dropdown>
    <x-neo.dropdown-item href="...">Action 1</x-neo.dropdown-item>
    <x-neo.dropdown-item href="...">Action 2</x-neo.dropdown-item>
</x-neo.dropdown>
```

**Replacement:**
```blade
<x-admin.dropdown>
    <x-slot name="trigger">
        <button class="p-2 hover:bg-gray-100 rounded">
            <svg class="w-5 h-5">...</svg>
        </button>
    </x-slot>
    <a href="..." class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Action 1</a>
    <a href="..." class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Action 2</a>
</x-admin.dropdown>
```

---

## Technical Constraints

1. **No Backend Changes** — This is pure frontend/view refactoring
2. **Build Assets** — Run `npm run build` after changes to regenerate public assets
3. **Testing** — Verify each modified page renders correctly
4. **No Runtime Behavior Change** — Only visual/structural changes

---

## Implementation Order

### Phase 1: Delete Files
1. Delete `resources/views/components/neo/` directory
2. Delete `resources/css/admin/neo.css`
3. Delete `resources/js/admin/neo.js`
4. Delete `resources/views/components/admin/neo-*.blade.php`
5. Delete `resources/views/components/neo-*.blade.php`
6. Run `npm run build` to clean public assets

### Phase 2: Replace Usages (by file)
1. `admin/blog/posts/` — button, empty-state, status-badge
2. `admin/blog/tags/` — empty-state, status-badge
3. `admin/blog/categories/` — empty-state, status-badge
4. `admin/eslesmeler/` — status-badge, empty-state
5. `admin/kisiler/` — aktiflik-durumu-badge, button, dropdown
6. `admin/danisman/` — button, card, dropdown
7. `admin/ozellikler/` — button, empty-state
8. `admin/eslesme/` — button, empty-state
9. `admin/notifications/` — button
10. `admin/takim-yonetimi/` — button
11. `profile/edit/` — button, card

### Phase 3: Verify
1. Run `php artisan view:clear`
2. Run `npm run dev` or `npm run build`
3. Manually test each modified page

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | All `x-neo.*` components removed from views | Grep for `x-neo` returns 0 |
| AC2 | `resources/views/components/neo/` deleted | Directory not exists |
| AC3 | `resources/css/admin/neo.css` deleted | File not exists |
| AC4 | `resources/js/admin/neo.js` deleted | File not exists |
| AC5 | All usages replaced with Context7 alternatives | Pages render correctly |
| AC6 | No broken UI on affected pages | Visual inspection |
| AC7 | Build succeeds | `npm run build` exits 0 |

---

## Verification Commands

```bash
# After implementation, run these:

# 1. Check no x-neo usage remains
grep -r "x-neo" resources/views --include="*.blade.php"

# 2. Check neo directory deleted
ls resources/views/components/neo/

# 3. Check CSS/JS deleted
ls resources/css/admin/neo.css
ls resources/js/admin/neo.js

# 4. Test pages load
php artisan view:clear
```

---

## Rollback Plan

If issues arise, rollback by:
1. Restore deleted files from git
2. Revert view changes from git

```bash
git checkout HEAD -- \
    resources/views/components/neo/ \
    resources/css/admin/neo.css \
    resources/js/admin/neo.js \
    resources/views/components/admin/neo-*.blade.php
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Delete neo directory | LOW | 1 |
| Replace button usages | LOW | 8 |
| Replace card usages | MEDIUM | 5 |
| Replace empty-state | LOW | 6 |
| Replace status-badge | LOW | 4 |
| Replace dropdown | MEDIUM | 2 |
| Replace form-field | LOW | 1 |
| **TOTAL** | MEDIUM | ~20 |

---

*YALIHAN TASARIMCI — DC-002 Design Contract v1.0*
