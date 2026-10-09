# Design Contract: DC-009 — Responsive Audit

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** LOW-MEDIUM

---

## Evidence

### FACT (Tailwind Configuration)

**Breakpoints (Default):**
```js
screens: {
    'sm': '640px',
    'md': '768px',
    'lg': '1024px',
    'xl': '1280px',
    '2xl': '1536px',
}
```

### FACT (Table Responsiveness)

**Usage:** 120+ files use `overflow-x-auto` for table scrolling

```bash
grep -r "overflow-x-auto" resources/views/admin --include="*.blade.php" -l | wc -l
# Output: 10+ major files with tables
```

### FACT (Frontend Mobile Menu)

```blade
{{-- resources/views/layouts/frontend.blade.php --}}
<!-- Desktop navigation (hidden on mobile) -->
<div class="hidden md:flex gap-8">...</div>

<!-- Mobile menu toggle (visible on mobile) -->
<button onclick="toggleMobileMenu()" class="md:hidden">Menü</button>

<!-- Mobile menu (hidden on desktop) -->
<div id="mobileMenu" class="hidden md:hidden">...</div>
```

### FACT (Admin Settings — Horizontal Tabs)

```blade
{{-- resources/views/admin/settings/index.blade.php --}}
<!-- 9 horizontal tabs — overflow issue on mobile -->
<nav class="-mb-px flex flex-wrap px-6" aria-label="Tabs">
    <button class="tab-button active ... px-6 py-4">Genel</button>
    <button class="tab-button ... px-6 py-4">Bildirimler</button>
    <!-- 7 more tabs -->
</nav>
```

### FACT (Grid Layouts)

```blade
<!-- resources/views/layouts/frontend.blade.php -->
<div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
    <!-- Stats cards, feature grids -->
</div>

<!-- resources/views/admin/ilanlar/index.blade.php -->
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
    <!-- Listing cards -->
</div>
```

---

## Current Responsive Patterns

### 1. Table Responsiveness

| Pattern | Usage | Status |
|---------|-------|--------|
| `overflow-x-auto` | Tables | ✅ Consistent |
| `min-w-full` | Table width | ✅ Standard |
| Container scroll | Card tables | ✅ In use |

### 2. Navigation Responsiveness

| Layout | Mobile Pattern | Desktop Pattern |
|--------|----------------|-----------------|
| Frontend | Hamburger + drawer | Horizontal nav |
| Admin | Collapsed sidebar | Full sidebar |
| Owner | Bottom nav | Side nav |

### 3. Grid Responsiveness

| Context | Mobile | Tablet | Desktop |
|---------|--------|--------|---------|
| Stats | 1 col | 2 col | 4 col |
| Listings | 1 col | 2-3 col | 3 col |
| Settings | Stack | Stack | 2 col |

---

## Issues Identified

### Issue 1: Settings Tabs Overflow (HIGH)

**Problem:** 9 horizontal tabs overflow on mobile

```blade
<!-- Current: overflows on small screens -->
<nav class="flex flex-wrap px-6">
    <!-- 9 tabs with long labels -->
</nav>
```

**Solution:** Convert to vertical tabs on mobile or horizontal scroll

### Issue 2: Tables Without Scroll Container (MEDIUM)

**Problem:** Some tables might overflow page width without scroll

**Solution:** Ensure all tables wrapped in `overflow-x-auto`

### Issue 3: Admin Sidebar on Mobile (MEDIUM)

**Problem:** Admin sidebar doesn't collapse properly on mobile

**Solution:** Implement slide-out drawer pattern

### Issue 4: Card Grids Gap (LOW)

**Problem:** Some grids have inconsistent gaps

**Solution:** Standardize gap tokens

---

## Recommendations

### REC-001: Settings Tabs Mobile Fix

**Before:**
```blade
<nav class="flex flex-wrap px-6">
    <button>Genel</button>
    <!-- 8 more tabs -->
</nav>
```

**After:**
```blade
{{-- Desktop: horizontal tabs --}}
<nav class="hidden md:flex" x-show="!showMobileTabs">
    <button>Genel</button>
</nav>

{{-- Mobile: vertical accordion --}}
<div class="md:hidden" x-show="showMobileTabs">
    <select @change="switchTab($event.target.value)">
        <option value="general">Genel</option>
    </select>
</div>
```

### REC-002: Table Scroll Wrapper

**Standard pattern:**
```blade
<div class="overflow-x-auto rounded-lg border">
    <table class="min-w-full">
        {{ $slot }}
    </table>
</div>
```

### REC-003: Mobile-First Component

**Reusable component:**
```blade
<x-admin.responsive-table>
    <thead>...</thead>
    <tbody>...</tbody>
</x-admin.responsive-table>
```

### REC-004: Admin Sidebar Drawer

**Pattern:**
```blade
<!-- Mobile: slide-out drawer -->
<div x-show="sidebarOpen"
     x-transition:enter="transition ease-out"
     class="fixed inset-0 z-50 md:hidden">
    <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
    <div class="fixed inset-y-0 left-0 w-64 bg-white">
        <!-- Sidebar content -->
    </div>
</div>
```

---

## Scope

### Phase 1: Audit

1. Audit all pages for responsive issues
2. Document problem areas
3. Prioritize fixes

### Phase 2: Quick Wins

1. Add mobile tab switcher to Settings
2. Ensure all tables have scroll wrappers
3. Fix any obvious overflow issues

### Phase 3: Long-term

1. Create `responsive-table` component
2. Improve admin sidebar mobile experience
3. Document responsive patterns

---

## Files to CREATE

| File | Purpose |
|------|---------|
| `responsive-table.blade.php` | Reusable table wrapper |

## Files to MODIFY (Priority Order)

| File | Changes | Priority |
|------|---------|----------|
| `admin/settings/index.blade.php` | Mobile tab switcher | HIGH |
| `admin/layout.blade.php` | Sidebar drawer | MEDIUM |
| Various table pages | Add scroll wrapper if missing | LOW |

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Settings tabs work on mobile | Test on 375px viewport |
| AC2 | All tables scroll horizontally | Test wide tables |
| AC3 | Admin sidebar works on mobile | Test hamburger menu |
| AC4 | No horizontal page scroll | Test all pages |
| AC5 | Grids stack properly | Test various viewports |

---

## Testing Viewports

| Device | Width | Breakpoint |
|--------|-------|------------|
| iPhone SE | 375px | sm |
| iPhone 14 | 390px | sm |
| iPad Mini | 768px | md |
| iPad Pro | 1024px | lg |
| Desktop | 1280px+ | xl+ |

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Settings mobile tabs | LOW | 1 |
| Table audit | LOW | 5 |
| Admin sidebar mobile | MEDIUM | 1 |
| Component creation | LOW | 1 |
| **TOTAL** | **LOW-MEDIUM** | **~8** |

---

## Related Issues (from Design Map)

| Issue | Status |
|-------|--------|
| Responsive inconsistent | This contract addresses |
| Settings crowded | Related (DC-004) |

---

*YALIHAN TASARIMCI — DC-009 Design Contract v1.0*
