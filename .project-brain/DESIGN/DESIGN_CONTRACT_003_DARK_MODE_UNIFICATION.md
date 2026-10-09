# Design Contract: DC-003 — Dark Mode Unification

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** HIGH

---

## Evidence

### FACT (Current Dark Mode State)

| Layout | Dark Mode | Approach | Dark BG | Toggle |
|--------|-----------|----------|---------|--------|
| Frontend | ❌ YOK | CSS Variables | - | - |
| Admin | ✅ AKTIF | Tailwind `dark:` | slate-950 | Alpine.js |
| Owner | ✅ AKTIF | Tailwind `dark:` | slate-900 | Alpine.js |

### FACT (Frontend Layout)
```php
// resources/views/layouts/frontend.blade.php (line 107-122)
<style>
    :root {
        --ege:         #0D5FA3;
        --ege-light:   #EFF6FF;
        --ege-dark:    #0A4D87;
        --gri:         #F4F6F8;
        --gri-mid:     #E5E7EB;
        --metin:       #1A1A2E;
        --metin-ikinci:#6B7280;
        --satilik:     #15803D;
        --kiralik:     #B45309;
    }
</style>
```
- Hardcoded CSS variables
- No dark mode toggle
- No `dark:` Tailwind classes
- Color scheme: Aegean Blue based

### FACT (Admin Layout)
```html
<!-- resources/views/layouts/admin.blade.php -->
<html x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
      
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
```
- Alpine.js toggle with localStorage persistence
- Tailwind `dark:` prefix everywhere
- Dark BG: `slate-950`

### FACT (Owner Layout)
```html
<!-- resources/views/layouts/owner.blade.php -->
<body x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true' || 
              (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
}">
    
<body class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-300">
```
- Similar Alpine.js pattern
- System preference detection (bonus)
- Dark BG: `slate-900`

### FACT (Tailwind Config)
```js
// tailwind.config.js
darkMode: 'class',
```

### FACT (Design Map Issues)
- **Frontend:** No dark mode at all
- **Admin/Owner:** Different dark backgrounds (slate-950 vs slate-900)
- **Toggle implementations:** Slightly different (system pref detection in owner only)

---

## Karar

### APPROACH: CSS Variables + Tailwind `dark:` HYBRID

**Rationale:**
1. Frontend already has CSS variables for theming (ThemeService)
2. Admin/Owner already use Tailwind `dark:` pattern
3. CSS variables provide better theming flexibility
4. Tailwind `dark:` is the standard modern approach

**Decision:** Use CSS Variables as the single source of truth, with Tailwind `dark:` classes for conditional styling.

---

## Implementation Strategy

### Phase 1: Unify CSS Variables

Create unified design tokens as CSS variables:

```css
/* Base design tokens (both themes) */
:root {
    /* Brand Colors */
    --color-navy: #0A1628;
    --color-gold: #C9A84C;
    --color-cream: #F8F6F1;
    
    /* Semantic Colors */
    --color-primary: #0D5FA3;
    --color-secondary: #565E74;
    --color-accent: #C9A84C;
    
    /* Status */
    --color-sale: #15803D;
    --color-rent: #B45309;
    
    /* Surfaces */
    --surface-bg: #F8F6F1;
    --surface-card: #FFFFFF;
    --surface-border: #E8E2D8;
    
    /* Text */
    --text-primary: #191B23;
    --text-secondary: #434655;
    --text-muted: #6B7280;
    --text-inverse: #FFFFFF;
}

/* Dark Theme Override */
.dark {
    --surface-bg: #0F172A;
    --surface-card: #1E293B;
    --surface-border: #334155;
    --text-primary: #F1F5F9;
    --text-secondary: #CBD5E1;
    --text-muted: #94A3B8;
}
```

### Phase 2: Add Dark Mode Toggle to Frontend

**Pattern (from Admin/Owner):**
```html
<!-- In frontend.blade.php <html> tag -->
<html x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true' ||
              (!('darkMode' in localStorage) && 
               window.matchMedia('(prefers-color-scheme: dark)').matches)
}"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val)); 
              darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
      :class="{ 'dark': darkMode }">
```

**Toggle Button (in topbar/header):**
```html
<button @click="darkMode = !darkMode"
        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
        :aria-label="darkMode ? 'Light mode' : 'Dark mode'">
    <!-- Sun icon (show in dark mode) -->
    <svg x-show="darkMode" class="w-5 h-5">...</svg>
    <!-- Moon icon (show in light mode) -->
    <svg x-show="!darkMode" class="w-5 h-5">...</svg>
</button>
```

### Phase 3: Migrate Hardcoded Colors

Replace inline/hex colors with CSS variable references:

| Current | Replace With |
|---------|--------------|
| `#0A1628` | `var(--color-navy)` or `var(--surface-card)` in dark |
| `#C9A84C` | `var(--color-gold)` |
| `#F8F6F1` | `var(--surface-bg)` |
| `#E8E2D8` | `var(--surface-border)` |
| `#191B23` | `var(--text-primary)` |
| `#434655` | `var(--text-secondary)` |

### Phase 4: Unify Admin/Owner Dark BG

| Layout | Current Dark BG | Unified Dark BG |
|--------|-----------------|-----------------|
| Admin | slate-950 (`#020617`) | slate-950 |
| Owner | slate-900 (`#0f172a`) | slate-950 |

**Rationale:** slate-950 is darker, better contrast for "true dark" mode.

### Phase 5: Add System Preference Detection

Add to Admin layout (already exists in Owner):
```html
darkMode: localStorage.getItem('darkMode') === 'true' ||
          (!('darkMode' in localStorage) && 
           window.matchMedia('(prefers-color-scheme: dark)').matches)
```

---

## Scope

### Files to MODIFY

| File | Changes |
|------|---------|
| `resources/views/layouts/frontend.blade.php` | Add dark mode toggle, CSS variables, dark class |
| `resources/views/layouts/admin.blade.php` | Unify dark BG to slate-950, add system pref detection |
| `resources/views/layouts/owner.blade.php` | Unify dark BG to slate-950 (already has system pref) |
| `resources/css/app.css` | Add unified CSS variable system |

### Files NOT Affected
- Individual page views (will inherit from layouts)
- Component files (will use CSS variables where needed)
- Backend PHP files

---

## Migration Path for Frontend

### Step 1: Add CSS Variables (Safe)
```css
:root {
    /* Existing + new tokens */
    --surface-bg: #F8F6F1;
    --surface-card: #FFFFFF;
    --surface-border: #E8E2D8;
}
.dark {
    --surface-bg: #0F172A;
    --surface-card: #1E293B;
    --surface-border: #334155;
}
```

### Step 2: Add Toggle (Non-breaking)
Add dark mode toggle button. Existing UI unchanged.

### Step 3: Gradual Color Migration
Replace hardcoded colors in batches:
1. Backgrounds and surfaces
2. Text colors
3. Border colors
4. Interactive elements (buttons, links)

### Step 4: Remove Aegean Blue (Legacy)
After migration, remove legacy `--ege-*` variables.

---

## Technical Constraints

1. **No Backend Changes** — Pure frontend/CSS work
2. **Progressive Enhancement** — Light mode always works
3. **System Preference** — Respect `prefers-color-scheme` media query
4. **localStorage Persistence** — User preference survives refresh
5. **Performance** — CSS variables are fast, no JS overhead

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Frontend has dark mode toggle | Toggle visible and functional |
| AC2 | Frontend respects `prefers-color-scheme` | Test with OS dark mode |
| AC3 | Frontend persists preference in localStorage | Refresh page, mode preserved |
| AC4 | Frontend dark mode visually coherent | Check backgrounds, text, borders |
| AC5 | Admin dark BG = slate-950 | Visual inspection |
| AC6 | Owner dark BG = slate-950 | Visual inspection |
| AC7 | All layouts use same toggle pattern | Compare implementations |
| AC8 | No "flash of wrong theme" | Load page, no flash |
| AC9 | Light mode still works | Disable dark mode, all pages work |

---

## Verification Checklist

```bash
# 1. Check dark mode toggle exists in frontend
grep -r "darkMode" resources/views/layouts/frontend.blade.php

# 2. Check CSS variables defined
grep -r "var(--surface" resources/views/layouts/frontend.blade.php

# 3. Check dark BG unified
grep "slate-950" resources/views/layouts/admin.blade.php
grep "slate-950" resources/views/layouts/owner.blade.php

# 4. Manual testing
# - Enable dark mode on each layout
# - Check backgrounds, text, cards, buttons
# - Toggle dark mode on/off
# - Check localStorage persistence
# - Test with OS dark mode enabled
```

---

## Rollback Plan

If issues arise:
1. Remove `dark:` class from `<html>`
2. Remove dark mode toggle button
3. CSS variables default to light values automatically

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Add CSS variable system | LOW | 1 (app.css) |
| Add dark mode toggle to Frontend | MEDIUM | 1 (frontend.blade.php) |
| Unify dark BG Admin | LOW | 1 (admin.blade.php) |
| Unify dark BG Owner | LOW | 1 (owner.blade.php) |
| Add system pref detection to Admin | LOW | 1 (admin.blade.php) |
| **TOTAL** | MEDIUM | 4 |

---

## Related Issues (from Design Map)

| Issue | Status |
|-------|--------|
| C2: Dark mode FRAGMENTED | This contract addresses |
| H1: Typography consistency | Not addressed (separate contract) |
| C4: Brand color inconsistency | Partially addressed (CSS variables) |

---

*YALIHAN TASARIMCI — DC-003 Design Contract v1.0*
