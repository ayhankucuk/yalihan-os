# Design Contract: DC-005 — Brand Color Unification

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** LOW

---

## Evidence

### FACT (Frontend Colors — Inline Styles)

```php
// resources/views/layouts/frontend.blade.php
style="color: #C9A84C;"        // Gold accent — 7 usages
style="background: #0F2A5C;"   // Footer navy — 1 usage
style="color: #ffffff;"        // White text — multiple
style="color: rgba(255,255,255,0.45);" // Muted white
style="color: rgba(255,255,255,0.5);"  // Muted white
style="color: rgba(255,255,255,0.3);"  // Footer muted
```

### FACT (Admin Colors — Orange Gradient)

```html
<!-- resources/views/layouts/admin.blade.php -->
<!-- Brand logo gradient -->
<div class="bg-gradient-to-br from-orange-500 to-amber-600 ...">

<!-- Active nav indicator -->
class="... {{ request()->is('admin/ilanlar*') ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400' : '' }}">

<!-- Footer logo -->
<div class="bg-gradient-to-br from-orange-500 to-amber-600 ...">
```

### FACT (Owner Colors — Blue)

```html
<!-- resources/views/layouts/owner.blade.php -->
class="text-blue-700 dark:text-blue-400"  // Logo
class="text-blue-600 dark:text-blue-400"  // Nav links
class="bg-blue-50 dark:bg-blue-900/30"   // Active nav
```

### FACT (Design Map Issue)

| Layout | Brand Color | Accent |
|--------|-------------|--------|
| Frontend | Navy (#0A1628) | Gold (#C9A84C) |
| Admin | Orange Gradient | Orange |
| Owner | Blue (#2563eb) | Blue |

**Problem:** Three different brand color systems.

---

## Karar

### BRAND: Navy + Gold (from Frontend)

The frontend brand colors should become the single source of truth:

| Token | Hex | Usage |
|-------|-----|-------|
| `--color-navy` | `#0A1628` | Primary backgrounds, headers |
| `--color-navy-light` | `#0F2A5C` | Footer, secondary dark |
| `--color-gold` | `#C9A84C` | Accents, highlights, active states |
| `--color-cream` | `#F8F6F1` | Background surface |
| `--color-white` | `#FFFFFF` | Card backgrounds |

### Migration Plan

| Layout | Current Brand | Target Brand |
|--------|--------------|--------------|
| Frontend | Navy + Gold | Keep (use CSS variables) |
| Admin | Orange gradient | Navy + Gold |
| Owner | Blue | Navy + Gold |

---

## Scope

### Phase 1: Define CSS Variables

**In `resources/css/app.css`:**

```css
:root {
    /* Brand Colors */
    --color-navy: #0A1628;
    --color-navy-light: #0F2A5C;
    --color-gold: #C9A84C;
    --color-cream: #F8F6F1;
    --color-white: #FFFFFF;
    
    /* Semantic */
    --brand-primary: var(--color-navy);
    --brand-accent: var(--color-gold);
}
```

### Phase 2: Update Admin Layout

**Changes in `admin.blade.php`:**

1. **Logo Gradient → Navy/Gold**
```html
<!-- Before: Orange gradient -->
<div class="bg-gradient-to-br from-orange-500 to-amber-600 ...">

<!-- After: Navy with gold accent -->
<div class="bg-[#0A1628] ...">
```

2. **Nav Active State**
```html
<!-- Before: Orange -->
class="... bg-orange-50 text-orange-600 ..."

<!-- After: Gold accent -->
class="... bg-[#C9A84C]/10 text-[#C9A84C] ..."
```

3. **Footer Logo**
```html
<!-- Before: Orange gradient -->
<div class="bg-gradient-to-br from-orange-500 to-amber-600 ...">

<!-- After: Navy -->
<div class="bg-[#0A1628] ...">
```

### Phase 3: Update Owner Layout

**Changes in `owner.blade.php`:**

1. **Logo**
```html
<!-- Before: Blue -->
class="text-blue-700 dark:text-blue-400">

<!-- After: Navy -->
class="text-[#0A1628] dark:text-white">
```

2. **Nav Links**
```html
<!-- Before: Blue -->
class="... text-blue-600 ..."

<!-- After: Gold -->
class="... text-[#C9A84C] ..."
```

3. **Active Nav**
```html
<!-- Before: Blue bg -->
class="... bg-blue-50 ..."

<!-- After: Gold bg -->
class="... bg-[#C9A84C]/10 ..."
```

### Phase 4: Replace Inline Styles in Frontend

**Changes in `frontend.blade.php`:**

Replace inline styles with CSS variables:

```html
<!-- Before -->
style="color: #C9A84C;"
style="background: #0F2A5C;"

<!-- After -->
style="color: var(--color-gold);"
style="background: var(--color-navy-light);"
```

---

## Files to MODIFY

| File | Changes |
|------|---------|
| `resources/css/app.css` | Add brand color variables |
| `resources/views/layouts/frontend.blade.php` | Replace inline styles |
| `resources/views/layouts/admin.blade.php` | Replace orange with navy/gold |
| `resources/views/layouts/owner.blade.php` | Replace blue with navy/gold |

---

## Replacement Map

### Admin Color Migrations

| Before | After |
|--------|-------|
| `from-orange-500 to-amber-600` | `bg-[#0A1628]` |
| `bg-orange-50` | `bg-[#C9A84C]/10` |
| `text-orange-600` | `text-[#C9A84C]` |
| `text-orange-400` (dark) | `text-[#C9A84C]` |
| `bg-orange-900/20` (dark) | `bg-[#C9A84C]/20` |

### Owner Color Migrations

| Before | After |
|--------|-------|
| `text-blue-700` | `text-[#0A1628]` |
| `text-blue-400` (dark) | `text-white` |
| `text-blue-600` | `text-[#C9A84C]` |
| `bg-blue-50` | `bg-[#C9A84C]/10` |
| `bg-blue-900/30` (dark) | `bg-[#C9A84C]/20` |

### Frontend Inline Style Migrations

| Before | After |
|--------|-------|
| `style="color: #C9A84C;"` | `style="color: var(--color-gold);"` |
| `style="background: #0F2A5C;"` | `style="background: var(--color-navy-light);"` |

---

## Technical Constraints

1. **No Backend Changes** — Pure CSS/HTML work
2. **Dark Mode Compatibility** — CSS variables should work in dark mode
3. **Incremental Migration** — Replace colors one layout at a time
4. **No Breaking Changes** — Same visual structure, just colors

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Admin uses Navy/Gold brand colors | Visual inspection |
| AC2 | Owner uses Navy/Gold brand colors | Visual inspection |
| AC3 | Frontend inline styles replaced | Grep for `#C9A84C` inline returns 0 |
| AC4 | All layouts have consistent brand | Compare screenshots |
| AC5 | Dark mode works correctly | Test with dark mode enabled |
| AC6 | Gold accent visible on all layouts | Visual inspection |

---

## Verification Commands

```bash
# Check inline gold styles remain
grep -r 'style=".*#C9A84C' resources/views/layouts/

# Check orange gradient remains
grep -r 'from-orange' resources/views/layouts/

# Check blue remains in admin/owner
grep -r 'text-blue' resources/views/layouts/admin.blade.php
grep -r 'text-blue' resources/views/layouts/owner.blade.php
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Define CSS variables | LOW | 1 (app.css) |
| Update Admin layout | LOW | 1 |
| Update Owner layout | LOW | 1 |
| Update Frontend inline styles | LOW | 1 |
| **TOTAL** | **LOW** | **4** |

---

## Rollback Plan

If issues arise:
1. Revert CSS variable changes
2. Restore original color values
3. No database/backend changes

---

*YALIHAN TASARIMCI — DC-005 Design Contract v1.0*
