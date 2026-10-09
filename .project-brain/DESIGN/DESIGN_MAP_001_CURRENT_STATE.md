# YALIHAN OS — Design Map: Current State
**Tarih:** 2026-10-08  
**Tasarımcı:** YALIHAN TASARIMCI  
**Amaç:** Sistem haritası çıkarma — gelecek iyileştirmeler için temel

---

## 1. PRODUCT ARCHITECTURE

### 1.1 Application Layers

| Layer | Count | Status |
|-------|-------|--------|
| Frontend Views (public) | 57 | ACTIVE |
| Admin Views | ~40 | ACTIVE |
| Advisor/AI Views | 15+ | ACTIVE |
| Owner Portal Views | 8 | ACTIVE |
| Total Blade Views | ~120+ | FRAGMENTED |

### 1.2 Navigation Structure

**Frontend (Public):**
```
├── Ana Sayfa
├── Konut (/konut)
├── Arsa (/arsa)
├── Yazlık Kiralık (/villas)
├── Uluslararası (/ilanlar/international)
├── Danışmanlar
└── İletişim
```

**Admin Panel:**
```
├── Pano (dashboard)
├── İlanlar
├── CRM
├── Danışmanlar
├── Analitik
├── AI Monitor
├── Portföy (Advisor)
└── Ayarlar (9 tab)
    ├── Genel
    ├── Bildirimler
    ├── Portal Entegrasyonları
    ├── Fiyatlandırma
    ├── QR Kod
    ├── Navigasyon
    ├── Kullanıcı Yönetimi
    ├── Diller
    └── Para Birimleri
```

**Owner Portal:**
```
├── Ana Sayfa
├── İlanlarım
├── Teklifler
├── Mesajlar
├── Belgelerim
└── Raporlar
```

### 1.3 Advisor/AI Module
```
├── Analytics
├── Portfolio Doctor
├── Opportunity Inbox
├── Listing Price
├── Seller Strategy
├── Diagnostics
├── Command Center
├── Conversational Advisor
├── Copilot
├── Buyer Match Queue
├── Owner Discovery
├── Market Valuation
├── Market Intelligence
├── Listing Buyers
└── Deal Radar
```

---

## 2. DESIGN SYSTEM AUDIT

### 2.1 Color Palette

**Brand Colors (Frontend):**
| Token | Hex | Usage |
|-------|-----|-------|
| Navy Primary | `#0A1628` | Headers, primary buttons, dark sections |
| Gold Accent | `#C9A84C` | Highlights, badges, active states |
| Background | `#F8F6F1` | Warm cream background |

**Status Colors:**
| Type | Hex | Badge Class |
|------|-----|-------------|
| Satılık | `#15803D` | badge-satilik |
| Kiralık | `#B45309` | badge-kiralik |
| Proje | `#1D4ED8` | badge-proje |

**Tailwind Extended:**
- `status-sale`, `status-rent` defined in config
- Full gray scale (50-900)
- Green, red, yellow, blue scales

### 2.2 Typography

**Dual Font System:**
```
Display/Headlines: Manrope (400-800)
Data/Labels:      Inter (400-700)
```

| Element | Font | Weight |
|---------|------|--------|
| Headings (h1-h6) | Manrope | 700-800 |
| Body text | Manrope | 400-500 |
| UI labels, badges | Inter | 600-700 |
| Input/forms | Inter | 600 |
| Data/numbers | Inter | 500-600 |

**Type Scale (Tailwind Config):**
- `headline-lg`: 2rem / 700
- `headline-sm`: 1.25rem / 600
- `body-md`: 1rem / 400
- `body-sm`: 0.875rem / 400
- `label-caps`: 0.7rem / 700

### 2.3 Spacing System

**CSS Variables (frontend):**
```css
--spacing-xs: 0.25rem;  /* 4px */
--spacing-sm: 0.5rem;   /* 8px */
--spacing-md: 1rem;     /* 16px */
--spacing-lg: 1.5rem;   /* 24px */
--spacing-xl: 2rem;     /* 32px */
```

**Tailwind Extended:**
- `section-gap`: 5rem
- `grid-gutter`: 1.5rem

### 2.4 Component Library

**Total Components:** 70+

**Category Breakdown:**
| Category | Count | Examples |
|----------|-------|---------|
| Admin | 28 | accordion, alert, badge, button, dropdown, modal, tabs, toast |
| Form | 10+ | input, checkbox, radio, select, toggle |
| Frontend | 9 | location-filter-tree, property-card-global, category-tabs |
| AI/Context7 | 8+ | ai-chat-widget, ai-hero, ai-smart-search |
| Property | 8+ | ilan-card, property-placeholder, price-display |
| CRM | 4 | contact-manager, oportunidad-board |
| Special | 6+ | neo-input, neo-select, neo-skeleton |

### 2.5 Icons

**Icon System:** Custom `<x-icon>` Blade component (32,044 bytes)
- Material Symbols Outlined fallback
- Custom SVG icons for brand elements
- Consistent 16px/20px sizing

---

## 3. LAYOUT ARCHITECTURE

### 3.1 Layout Inventory

| Layout | Lines | Dark Mode | CSS Variables | Tailwind |
|--------|-------|-----------|---------------|----------|
| frontend.blade.php | 486 | NO | YES (ThemeService) | Partial |
| admin.blade.php | 156 | YES (Alpine) | NO | FULL |
| owner.blade.php | 209 | YES (Alpine) | NO | FULL |

### 3.2 Frontend Layout Analysis

**Strengths:**
- Excellent SEO meta handling (OG, Twitter, hreflang, canonical)
- ThemeService integration for dynamic theming
- Manrope + Inter dual font system
- Responsive navigation with mobile hamburger
- Vite + CDN fallback mechanism

**Issues:**
- CSS variables hardcoded inline (line 113-122)
- Theme system feels complex/inconsistent
- Vite fallback logic is workaround, not solution

### 3.3 Admin Layout Analysis

**Strengths:**
- Clean, modern dark mode toggle
- Consistent Tailwind-only approach
- Well-structured navigation
- Proper max-width container

**Issues:**
- Brand color (orange gradient) doesn't match frontend
- Font system inherited from app.css (Inter only)
- Less sophisticated than frontend layout

### 3.4 Owner Layout Analysis

**Strengths:**
- Simple, focused UX
- Clear navigation hierarchy
- Alpine.js mobile menu

**Issues:**
- Most basic of the three layouts
- Dark mode implementation different from admin
- No CSS variable theming

---

## 4. SETTINGS ARCHITECTURE

### 4.1 Current State

**Settings Locations:**
- `/admin/settings/index` — Main settings (1001 lines, 9 tabs)
- `/admin/ayarlar/*` — Alternative settings CRUD
- `/admin/ai-settings/index` — AI-specific settings
- `/admin/integrations/*` — Voice, notifications
- `/admin/market-intelligence/settings` — MI settings
- `/admin/config-options/index` — Config options

**Problem:** Settings FRAGMENTED across multiple locations

### 4.2 Settings Tabs (Main)

1. **Genel** — General settings
2. **Bildirimler** — Notifications
3. **Portal Entegrasyonları** — Portal integrations
4. **Fiyatlandırma** — Pricing
5. **QR Kod** — QR code
6. **Navigasyon** — Navigation
7. **Kullanıcı Yönetimi** — User management
8. **Diller** — Languages
9. **Para Birimleri** — Currencies

### 4.3 Design Issues

- 9 tabs in single page = crowded UI
- No settings categorization/grouping
- Bulk update form approach may cause UX issues
- Different settings pages use different patterns

---

## 5. FORM & VALIDATION UX

### 5.1 Form Components

**Available Components:**
- `<x-input>` — Text inputs
- `<x-select>` — Dropdowns
- `<x-checkbox>` — Checkboxes
- `<x-radio>` — Radio buttons
- `<x-toggle>` — Toggle switches
- `<x-textarea>` — Text areas
- Advanced components: price-input, location-selector, feature-selector

### 5.2 Design Patterns

**Modern Form Wizard:**
```css
/* Admin modern-form-wizard.css */
- Multi-step form flow
- Progress indicators
- Validation states
```

**Input States:**
- Default: `border-[#E8E2D8]`, `bg-[#F8F6F1]`
- Focus: `border-[#C9A84C]`, `ring-1 ring-[#C9A84C]/20`
- Error: Red border + error message
- Height: Consistent 44px or 52px

### 5.3 Issues

- Form validation UX not standardized
- Error messages placement inconsistent
- Success/error flash messages vary by page
- No unified form component with built-in validation display

---

## 6. DATA DISPLAY — TABLES

### 6.1 Current Table Pattern

**Minimal `<x-table>` component** (390 bytes) — Very basic

**Typical Implementation:** Inline tables with Tailwind grid/flex

### 6.2 Analysis

- No sophisticated data table component
- Pagination varies by module
- Sorting UI inconsistent
- Bulk actions pattern exists (`<x-bulk-actions>`)

### 6.3 Issues

- Tables are page-specific implementations
- No reusable sophisticated table (sort, filter, pagination, export)
- Mobile table experience likely poor

---

## 7. RESPONSIVE BEHAVIOR

### 7.1 Responsive Strategy

**Tailwind Breakpoints:**
- Mobile-first approach
- `sm:` (640px), `md:` (768px), `lg:` (1024px), `xl:` (1280px)

### 7.2 Module Analysis

| Module | Mobile | Tablet | Desktop |
|--------|--------|--------|---------|
| Frontend Listing | YES | PARTIAL | YES |
| Admin Panel | YES | YES | YES |
| Owner Portal | YES | PARTIAL | YES |
| Advisor Views | PARTIAL | PARTIAL | YES |

### 7.3 Common Patterns

**Mobile Filter Pattern (ilanlar/index):**
```html
<!-- Mobile filter button -->
<button @click="mobileFilterOpen = !mobileFilterOpen">
<!-- Sidebar: class="lg:block" with Alpine toggle -->
```

**Responsive Grid:**
```html
grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6
```

### 7.4 Issues

- Responsive tested inconsistently across modules
- Mobile navigation varies (Alpine vs custom JS)
- Some admin views may break on tablet
- Advisor module likely desktop-only

---

## 8. AI/HERMES EXPERIENCE

### 8.1 AI Components

**Context7 Integration:**
- Location intelligence
- Property analysis
- Smart search

**AI Views:**
- `conversational-advisor.blade.php` — Chat interface
- `copilot.blade.php` — AI copilot
- `command-center.blade.php` — AI control hub
- `ai-chat-widget.blade.php` — Floating chat
- `ai-smart-search.blade.php` — Search enhancement

### 8.2 Design Analysis

**Strengths:**
- AI branded consistently (purple accents)
- Context7 badge/indicator visible
- Multiple AI interaction points

**Issues:**
- AI features feel ADDED rather than INTEGRATED
- Inconsistent AI UI across modules
- No unified AI experience/design language
- "AI OS" footer text suggests system identity confusion

### 8.3 Hermes Agent

**Integration Points:**
- YALIHAN ATLAS — Technical parent
- YALIHAN Kodlayıcı — Implementation
- YALIHAN Denetçi — Verification
- YALIHAN Emlak AI — Business logic

---

## 9. PUBLIC SITE SEO

### 9.1 Frontend Layout SEO

**Implemented:**
```html
<!-- Meta -->
<meta name="description">
<meta name="robots">

<!-- Open Graph -->
og:title, og:description, og:type, og:url
og:locale, og:site_name, og:image

<!-- Twitter Card -->
twitter:card, twitter:title, twitter:description, twitter:image

<!-- Technical -->
<link rel="canonical">
<link rel="alternate" hreflang>
```

### 9.2 Page-Specific SEO

**Dynamic SEO via Blade:**
```php
$seo['title'] = ...;
$seo['description'] = ...;
$seo['og_image'] = ...;
```

### 9.3 Issues

- SEO implementation varies by page
- No unified SEO service/component
- Structured data (JSON-LD) not visible
- Sitemap generation unclear

---

## 10. INCONSISTENCIES & VIOLATIONS

### 10.1 Critical Inconsistencies

| Issue | Frontend | Admin | Owner |
|-------|----------|-------|-------|
| Dark Mode | NO | YES (Alpine) | YES (Alpine) |
| Theme System | CSS Variables | NONE | NONE |
| Font | Manrope+Inter | Inter only | Inter only |
| Brand Color | Navy/Gold | Orange gradient | Blue |
| Layout Size | 1400px max | 1320px max | 1024px max |

### 10.2 Context7 Violations

**CONFIRMED VIOLATIONS:**

1. **Neo Design System** — Components exist:
   - `resources/views/components/neo/*`
   - `neo-input.blade.php`, `neo-select.blade.php`
   - `neo-skeleton.blade.php`, `neo-loading.blade.php`
   - `resources/css/admin/neo.css`
   
   **Violation:** Context7 explicitly forbids Neo Design System

2. **Bootstrap Classes** — Searched but NOT FOUND in main views
   - No `btn-`, `card-`, `table-` Bootstrap patterns
   - OK: Pure Tailwind + custom CSS

3. **Inline Styles** — MODERATE VIOLATION
   - Frontend layout has hardcoded colors (lines 113-122)
   - Should use CSS variables consistently

### 10.3 Component Inconsistency

**Naming Chaos:**
```
✓ badge.blade.php
✓ checkbox.blade.php
✓ input.blade.php
✗ neo-input.blade.php (violation)
✗ neo-select.blade.php (violation)
```

---

## 11. STRENGTHS

### 11.1 Architecture Strengths

1. **Comprehensive Component Library**
   - 70+ reusable Blade components
   - Good categorization (admin, form, frontend, ai)
   - Consistent Tailwind usage in most places

2. **Modern Frontend Stack**
   - Vite build system
   - Alpine.js for interactivity
   - Tailwind CSS for styling
   - ThemeService for theming

3. **SEO Infrastructure**
   - Excellent meta handling in frontend
   - Open Graph, Twitter cards implemented
   - Canonical + hreflang support

4. **AI Integration Foundation**
   - Context7 components ready
   - Multiple AI interaction patterns
   - Advisor module comprehensive

5. **Responsive Base**
   - Tailwind mobile-first approach
   - Consistent breakpoint usage
   - Mobile navigation pattern established

### 11.2 Design Strengths

1. **Color System**
   - Navy + Gold brand colors strong
   - Status colors (satılık/kiralık) consistent
   - Tailwind color scale available

2. **Typography**
   - Manrope + Inter dual system makes sense
   - Proper heading hierarchy
   - Label/badge typography defined

3. **Spacing**
   - CSS variable spacing system
   - Tailwind extended spacing
   - Section/grid gutter defined

---

## 12. IMPROVEMENT OPPORTUNITIES

### Priority 1 — Critical

| ID | Area | Issue | Impact |
|----|------|-------|--------|
| C1 | Design System | **REMOVE Neo components** (Context7 violation) | Compliance |
| C2 | Design System | **Unify dark mode** across all layouts | UX consistency |
| C3 | Architecture | **Consolidate settings** into coherent architecture | User experience |
| C4 | Layout | **Unify brand colors** across admin/owner | Brand consistency |

### Priority 2 — High

| ID | Area | Issue | Impact |
|----|------|-------|--------|
| H1 | Typography | Ensure Manrope used in admin/owner | Consistency |
| H2 | Tables | Create reusable data table component | DX + UX |
| H3 | Forms | Standardize validation error display | UX consistency |
| H4 | Responsive | Audit all advisor views for mobile | Mobile UX |

### Priority 3 — Medium

| ID | Area | Issue | Impact |
|----|------|-------|--------|
| M1 | SEO | Add JSON-LD structured data | SEO |
| M2 | AI | Create unified AI design language | Brand |
| M3 | CSS | Replace inline styles with CSS variables | Maintainability |
| M4 | Icons | Audit icon sizing consistency | Visual polish |

---

## 13. CANDIDATE IMPROVEMENTS (For Future Design Contracts)

### Design Contract Candidates

| ID | Title | Priority | Complexity |
|----|-------|----------|------------|
| DC-002 | Neo Component Removal | ❌ CLOSED (insufficient evidence) | - |
| DC-003 | Dark Mode Unification | ✅ COMPLETED (ce1e0cdf) | MEDIUM |
| DC-004 | Settings Architecture | ✅ COMPLETED (e7364f0) — PASS | MEDIUM |
| DC-005 | Brand Color Unification | ✅ COMPLETED (6dd2223b) — PASS | LOW |
| DC-006 | Data Table Component | ✅ COMPLETED (9339969) — PASS | MEDIUM |
| DC-007 | Form Validation Components | ✅ COMPLETED (fbdbe0d) — PASS | MEDIUM |
| DC-008 | AI Design Language | ⏸️ BEKLETİLDİ | HIGH |
| DC-009 | Responsive Audit | ✅ COMPLETED (62b6686) — PASS | LOW-MEDIUM |

---

## 14. METRICS SUMMARY

| Metric | Value | Status |
|--------|-------|--------|
| Total Views | ~120 | HIGH |
| Total Components | 70+ | GOOD |
| Layouts | 3 | NEEDS UNIFICATION |
| Color Tokens | 20+ | OK |
| Font Families | 2 | GOOD |
| Breakpoints | 4 (sm/md/lg/xl) | STANDARD |
| Dark Mode | ✅ UNIFIED | COMPLETED |
| SEO Meta | FULL | GOOD |
| AI Components | 8+ | GOOD |
| Neo Violations | 5 files | CRITICAL |

---

## 15. RECOMMENDATIONS

### Immediate Actions (Post-DC-003)

1. ~~Remove Neo Design System~~ — ❌ CLOSED (insufficient evidence)

2. ~~Document Dark Mode Strategy~~ — ✅ COMPLETED (DC-003)

3. **Settings Architecture Review** — Evaluate DC-004 priority

### Short Term

4. **Create Unified Layout** — Base layout that frontend/admin/owner extend

5. **Design Token Export** — Export design tokens to CSS variables for all layouts

6. **Settings Consolidation** — Redesign settings architecture with better categorization

### Long Term

7. **AI Design Language** — Create cohesive AI visual identity

8. **Component Registry** — Document all components with usage guidelines

9. **Responsive Audit** — Systematic mobile testing across all modules

---

---

## Completed Issues

| Issue | Status | Commit | Notes |
|-------|--------|--------|-------|
| MAIL-500 | ✅ COMPLETED | dccb0e73 | booking-request.blade.php oluşturuldu |
| LOCATION-FONTAWESOME | ✅ CLOSED | 26bc036b | 8 FontAwesome → SVG |

---

*YALIHAN TASARIMCI — Design Map v1.1*
*Generated: 2026-10-08*
