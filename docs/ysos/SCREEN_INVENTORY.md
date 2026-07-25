# YALIHAN OS — Screen Inventory v1.0

> Generated: 2026-07-25  
> Purpose: YDS v1.0 (Yalıhan Design System) & Property Command Center Information Architecture

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| **Total Blade Views** | ~250+ |
| **Admin Views** | ~150+ |
| **Owner Views** | 11 |
| **Frontend Views** | 8 |
| **Shared Components** | 70+ |
| **Vue Components** | 3 |

### By Location

| Location | Count | Description |
|----------|-------|-------------|
| `resources/views/admin/` | ~150 | Full admin panel (ilanlar, crm, analytics, blog, ai, etc.) |
| `resources/views/owner/` | 11 | Owner portal (dashboard, ilanlar, teklifler, belgeler) |
| `resources/views/frontend/` | 8 | Public-facing pages (ilanlar, danismanlar, portfolio) |
| `resources/views/layouts/` | 3 | Master layouts (admin, frontend, owner) |
| `resources/views/components/` | 70+ | Reusable Blade components (neo-*, yaliihan-*, admin-*) |
| `resources/views/livewire/` | 8 | Livewire component views (ai-telemetry, admin, publication) |
| `resources/js/components/` | 3 | Vue components (AI Chat, AI Price Prediction, AI Dashboard) |

### By Type

| Type | Count | Examples |
|------|-------|----------|
| Page | ~80 | index, show, create, edit, dashboard |
| Component | 70+ | neo-button, neo-input, property-card, icon |
| Partial | ~50 | cockpit-*, ilanlar-partials, ilanlar-components |
| Modal | ~10 | _kisi-ekle, _site-ekle |
| Layout | 3 | admin, frontend, owner |

### Navigation Complexity Assessment

**Admin Panel (layouts/admin.blade.php):**
- Primary nav: Pano, İlanlar, CRM, Danışmanlar, Analitik, AI, Portföy
- Dark mode toggle (Alpine.js)
- Notification bell
- Settings link
- Mobile hamburger menu

**Frontend (layouts/frontend.blade.php):**
- Fixed top navigation with glass morphism
- Logo + main nav: Konut, Arsa, Yazlık, Uluslararası, Danışmanlar
- Auth controls (Login/Panel)
- Mobile menu
- Dark mode toggle (removed for Aegean Clean)

**Owner Portal (layouts/owner.blade.php):**
- Top header with logo
- Tab nav: Ana Sayfa, İlanlarım, Teklifler, Mesajlar, Belgelerim, Raporlar
- Dark mode toggle
- Logout button
- Mobile menu

---

## Admin Screens (resources/views/admin/)

### Dashboard

| Path | Purpose | Entities | Complexity |
|------|---------|----------|------------|
| `admin/dashboard/index.blade.php` | Main admin dashboard with stats, AI actions, market analysis | ilanlar, kullanıcılar, danışmanlar, AI kredi | Complex |
| `admin/dashboard/admin.blade.php` | Admin-specific dashboard | kullanıcılar | Medium |
| `admin/dashboard/agent.blade.php` | Agent dashboard | ilanlar, leads | Medium |
| `admin/dashboard/danisman.blade.php` | Danışman dashboard | ilanlar, müşteriler | Medium |
| `admin/dashboard/investor.blade.php` | Investor dashboard | ilanlar, finans | Medium |
| `admin/dashboard/user.blade.php` | User dashboard | kullanıcı | Simple |

**Design Patterns:**
- Stats cards: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5`
- Gradient cards: `bg-gradient-to-br from-indigo-500 to-purple-600`
- Stats icon containers: `w-12 h-12 rounded-lg bg-{color}-100 dark:bg-{color}-900`
- Tables: `min-w-full divide-y divide-gray-200`
- Status badges: `bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
- Alpine.js: `x-data`, `x-show`, `x-for`

### İlanlar (Listings)

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/ilanlar/index.blade.php` | Listing management with tabs, filters, bulk actions | Yayınlar | Complex |
| `admin/ilanlar/show.blade.php` | Listing cockpit with vitals, radar, data grid | Genel Bakış | Complex |
| `admin/ilanlar/edit.blade.php` | Edit listing | Yayınlar | Complex |
| `admin/ilanlar/edit-elegant.blade.php` | Elegant edit form | Yayınlar | Complex |
| `admin/ilanlar/create-wizard.blade.php` | Create wizard | Yayınlar | Complex |
| `admin/ilanlar/ilanlarim.blade.php` | My listings | Yayınlar | Medium |
| `admin/ilanlar/pdf.blade.php` | PDF export | Belgeler | Medium |
| `admin/ilanlar/success.blade.php` | Success page | Yayınlar | Simple |
| `admin/ilanlar/calendar/index.blade.php` | Listing calendar | Operasyon | Medium |

**İlanlar Components (admin/ilanlar/components/):**

| Component | Purpose |
|-----------|---------|
| `cockpit/vitals.blade.php` | Sticky vitals strip |
| `cockpit/executive-strip.blade.php` | SAB executive summary |
| `cockpit/intelligence-map.blade.php` | Hero position map |
| `cockpit/radar.blade.php` | Region analysis |
| `cockpit/data-grid.blade.php` | Technical specs |
| `cockpit/pricing-insight.blade.php` | Price intelligence |
| `cockpit/location-signal.blade.php` | Location intelligence |
| `cockpit/advisor-insight.blade.php` | Advisor recommendations |
| `cockpit/logs-vault.blade.php` | Audit logs |
| `cockpit/social-crm.blade.php` | Client info |
| `basic-info.blade.php` | Basic info form |
| `basic-info-elegant.blade.php` | Elegant basic form |
| `photo-upload-manager.blade.php` | Photo management |
| `listing-photos.blade.php` | Photo gallery |
| `price-management.blade.php` | Price management |
| `publish-yayin-durumu-select.blade.php` | Publish status |
| `location-map.blade.php` | Map integration |
| `features-dynamic.blade.php` | Dynamic features |
| `category-system.blade.php` | Category management |
| `ai-quick-actions.blade.php` | AI action buttons |
| `ai-content.blade.php` | AI content generation |
| `sticky-navigation.blade.php` | Sticky nav |

### CRM

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/crm/dashboard.blade.php` | CRM overview with ROI, churn, segments | Genel Bakış | Complex |
| `admin/crm/dashboard-minimal.blade.php` | Minimal CRM view | Genel Bakış | Medium |
| `admin/crm/index.blade.php` | CRM customers list | Genel Bakış | Medium |
| `admin/crm/pipeline/index.blade.php` | Sales pipeline | Operasyon | Complex |
| `admin/crm/customers/index.blade.php` | Customer management | Genel Bakış | Medium |
| `admin/crm/dashboard-cards.blade.php` | Dashboard cards widget | Genel Bakış | Simple |

### Analytics

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/analytics/index.blade.php` | Analytics overview | Analitik | Medium |
| `admin/analytics/dashboard.blade.php` | Analytics dashboard | Analitik | Complex |
| `admin/analytics/dashboard_v2.blade.php` | Analytics v2 | Analitik | Complex |
| `admin/analytics/context7-dashboard.blade.php` | Context7 analytics | Analitik | Medium |
| `admin/analytics/cortex-dashboard.blade.php` | Cortex analytics | AI | Medium |
| `admin/analytics/show.blade.php` | Analytics detail | Analitik | Medium |
| `admin/analytics/create.blade.php` | Create analytics | Analitik | Simple |
| `admin/analytics/edit.blade.php` | Edit analytics | Analitik | Medium |
| `admin/analytics/ilan_detay_analiz.blade.php` | Listing detail analysis | Analitik | Medium |

### AI Systems

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/ai/dashboard.blade.php` | AI dashboard | AI | Complex |
| `admin/ai/usage.blade.php` | AI usage tracking | AI | Medium |
| `admin/ai/usage-dashboard.blade.php` | Usage dashboard | AI | Complex |
| `admin/ai/telemetry/dashboard.blade.php` | Telemetry dashboard | AI | Complex |
| `admin/ai/logs.blade.php` | AI logs | AI | Medium |
| `admin/ai/semantic-search.blade.php` | Semantic search | AI | Medium |
| `admin/ai/advanced-dashboard.blade.php` | Advanced AI view | AI | Complex |
| `admin/ai/runtime.blade.php` | AI runtime | AI | Medium |
| `admin/ai/roi-dashboard.blade.php` | AI ROI metrics | AI | Medium |
| `admin/ai/monitoring.blade.php` | AI monitoring | AI | Complex |
| `admin/ai/debug/decisions.blade.php` | Decision debug | AI | Simple |
| `admin/danisman-ai/index.blade.php` | Danışman AI | AI | Medium |
| `admin/danisman-ai/prompt-interface.blade.php` | Prompt interface | AI | Medium |

### Blog Management

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/blog/index.blade.php` | Blog dashboard | Belgeler | Medium |
| `admin/blog/posts/index.blade.php` | Posts list | Belgeler | Medium |
| `admin/blog/posts/show.blade.php` | Post detail | Belgeler | Medium |
| `admin/blog/posts/edit.blade.php` | Edit post | Belgeler | Medium |
| `admin/blog/categories/index.blade.php` | Categories | Belgeler | Simple |
| `admin/blog/categories/edit.blade.php` | Edit category | Belgeler | Simple |
| `admin/blog/tags/index.blade.php` | Tags | Belgeler | Simple |
| `admin/blog/tags/edit.blade.php` | Edit tag | Belgeler | Simple |
| `admin/blog/comments/index.blade.php` | Comments | Belgeler | Medium |
| `admin/blog/analytics.blade.php` | Blog analytics | Belgeler | Medium |

### Settings & Configuration

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/ayarlar/index.blade.php` | Settings overview | Operasyon | Medium |
| `admin/ayarlar/show.blade.php` | View settings | Operasyon | Simple |
| `admin/ayarlar/create.blade.php` | Create setting | Operasyon | Simple |
| `admin/ayarlar/edit.blade.php` | Edit setting | Operasyon | Medium |
| `admin/ayarlar/location.blade.php` | Location settings | Operasyon | Medium |
| `admin/config-options/index.blade.php` | Config options | Operasyon | Medium |
| `admin/config-options/create.blade.php` | Create config | Operasyon | Simple |
| `admin/config-options/edit.blade.php` | Edit config | Operasyon | Medium |
| `admin/config-options/show.blade.php` | View config | Operasyon | Simple |

### Property Hub

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/property-hub/index.blade.php` | Property hub dashboard | Genel Bakış | Complex |
| `admin/property-hub/templates/index.blade.php` | Templates | Belgeler | Medium |
| `admin/property-hub/templates/edit.blade.php` | Edit template | Belgeler | Medium |
| `admin/property-hub/features/index.blade.php` | Features list | Yayınlar | Medium |
| `admin/property-hub/features/create.blade.php` | Create feature | Yayınlar | Simple |
| `admin/property-hub/features/edit.blade.php` | Edit feature | Yayınlar | Medium |
| `admin/property-hub/packs/index.blade.php` | Feature packs | Yayınlar | Medium |
| `admin/property-hub/versions/index.blade.php` | Version history | Timeline | Medium |
| `admin/property-hub/versions/diff.blade.php` | Version diff | Timeline | Medium |
| `admin/property-hub/field-suggestions/index.blade.php` | Field suggestions | AI | Medium |
| `admin/property-hub/field-suggestions/show.blade.php` | Suggestion detail | AI | Medium |
| `admin/property-hub/analytics/index.blade.php` | Property analytics | Analitik | Medium |
| `admin/property-hub/observability/index.blade.php` | Observability | AI | Complex |
| `admin/property-hub/dependency-rules/index.blade.php` | Dependency rules | Operasyon | Medium |

### Team Management (Takım Yönetimi)

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/takim-yonetimi/takim/index.blade.php` | Teams list | Operasyon | Medium |
| `admin/takim-yonetimi/takim/show.blade.php` | Team detail | Operasyon | Medium |
| `admin/takim-yonetimi/takim/edit.blade.php` | Edit team | Operasyon | Medium |
| `admin/takim-yonetimi/takim/board.blade.php` | Team board | Operasyon | Complex |
| `admin/takim-yonetimi/takim/performans.blade.php` | Team performance | Operasyon | Complex |
| `admin/takim-yonetimi/takim/takim-performans.blade.php` | Team stats | Operasyon | Medium |
| `admin/takim-yonetimi/gorevler/index.blade.php` | Tasks list | Operasyon | Medium |
| `admin/takim-yonetimi/gorevler/show.blade.php` | Task detail | Operasyon | Medium |
| `admin/takim-yonetimi/gorevler/edit.blade.php` | Edit task | Operasyon | Medium |
| `admin/takim-yonetimi/gorevler/raporlar.blade.php` | Task reports | Operasyon | Medium |

### People Management (Kişiler)

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/kisiler/index.blade.php` | People list | Genel Bakış | Medium |
| `admin/kisiler/show.blade.php` | Person detail | Genel Bakış | Medium |
| `admin/kisiler/create.blade.php` | Create person | Genel Bakış | Medium |
| `admin/kisiler/edit.blade.php` | Edit person | Genel Bakış | Medium |
| `admin/kisiler/takip.blade.php` | Follow-up | Genel Bakış | Medium |

### Leads

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/leads/index.blade.php` | Leads list | Genel Bakış | Medium |
| `admin/leads/show.blade.php` | Lead detail | Genel Bakış | Medium |

### Reports & Analytics (Analitik)

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/reports/index.blade.php` | Reports dashboard | Finans | Complex |
| `admin/analitik/istatistikler/index.blade.php` | Stats overview | Analitik | Medium |
| `admin/analitik/istatistikler/genel.blade.php` | General stats | Analitik | Medium |
| `admin/analitik/istatistikler/ilan.blade.php` | Listing stats | Analitik | Medium |
| `admin/analitik/istatistikler/musteri.blade.php` | Customer stats | Analitik | Medium |
| `admin/analitik/istatistikler/satis.blade.php` | Sales stats | Finans | Medium |
| `admin/analitik/istatistikler/finans.blade.php` | Finance stats | Finans | Complex |

### Operations

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/operations/console.blade.php` | Operations console | Operasyon | Complex |
| `admin/health-dashboard.blade.php` | Health dashboard | Operasyon | Medium |
| `admin/cache-stats.blade.php` | Cache statistics | Operasyon | Simple |
| `admin/telegram/index.blade.php` | Telegram integration | AI | Medium |

### Marketplace & Tools

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/smart-calculator/index.blade.php` | Smart calculator | Finans | Medium |
| `admin/arsa/calculator.blade.php` | Land calculator | Finans | Medium |
| `admin/tkgm-parsel/index.blade.php` | TKGM parcel lookup | Operasyon | Medium |
| `admin/ups/governance/index.blade.php` | Governance | Operasyon | Medium |
| `admin/ups/analytics/index.blade.php` | UPS analytics | Analitik | Medium |
| `admin/ups/policy/index.blade.php` | Policy management | Operasyon | Medium |
| `admin/ups/packs/index.blade.php` | Feature packs | Yayınlar | Medium |
| `admin/ups/feature-packs/index.blade.php` | Feature packs | Yayınlar | Medium |
| `admin/ups/templates/index.blade.php` | Templates | Belgeler | Medium |
| `admin/ups/templates/edit.blade.php` | Edit template | Belgeler | Medium |
| `admin/ups/templates/history.blade.php` | Template history | Timeline | Medium |
| `admin/ups/templates/import-export.blade.php` | Import/Export | Belgeler | Medium |
| `admin/marketing/templates/index.blade.php` | Marketing templates | Belgeler | Medium |
| `admin/marketing/templates/edit.blade.php` | Edit marketing | Belgeler | Medium |

### Page Analyzer

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/page-analyzer/index.blade.php` | Page analyzer | AI | Medium |
| `admin/page-analyzer/create.blade.php` | Create analysis | AI | Simple |
| `admin/page-analyzer/edit.blade.php` | Edit analysis | AI | Medium |
| `admin/page-analyzer/show.blade.php` | View analysis | AI | Medium |
| `admin/page-analyzer/dashboard.blade.php` | Analysis dashboard | AI | Complex |

### Other Admin Screens

| Path | Purpose | PCC Tab | Complexity |
|------|---------|---------|------------|
| `admin/customer-profile.blade.php` | Customer profile | Genel Bakış | Medium |
| `admin/property-type-manager/index.blade.php` | Property types | Operasyon | Medium |
| `admin/property-type-manager/show.blade.php` | Type detail | Operasyon | Medium |
| `admin/property-type-manager/field-dependencies.blade.php` | Field deps | Operasyon | Medium |
| `admin/ai-category/index.blade.php` | AI categories | AI | Medium |
| `admin/workspace/cockpit.blade.php` | Workspace cockpit | Genel Bakış | Complex |
| `admin/etiket/index.blade.php` | Tags list | Operasyon | Simple |
| `admin/etiket/create.blade.php` | Create tag | Operasyon | Simple |
| `admin/etiket/edit.blade.php` | Edit tag | Operasyon | Simple |
| `admin/etiket/show.blade.php` | Tag detail | Operasyon | Simple |
| `admin/locations/show.blade.php` | Location detail | Operasyon | Medium |
| `admin/field-mcp/dashboard.blade.php` | Field MCP | AI | Medium |
| `admin/exports/pdf.blade.php` | PDF export | Belgeler | Medium |
| `admin/propertyhub/shadow-dashboard.blade.php` | Shadow dashboard | Genel Bakış | Medium |

---

## Owner Screens (resources/views/owner/)

| Path | Purpose | Entities | PCC Tab | Complexity |
|------|---------|----------|---------|------------|
| `owner/dashboard.blade.php` | Owner dashboard | ilanlar, raporlar | Genel Bakış | Simple |
| `owner/ilanlar/index.blade.php` | Owner listings | ilanlar | Yayınlar | Medium |
| `owner/ilanlar/show.blade.php` | View listing | ilan | Yayınlar | Medium |
| `owner/ilanlar/create.blade.php` | Create listing | ilan | Yayınlar | Medium |
| `owner/ilanlar/edit.blade.php` | Edit listing | ilan | Yayınlar | Medium |
| `owner/teklifler/index.blade.php` | Offers list | teklifler | Operasyon | Medium |
| `owner/teklifler/show.blade.php` | Offer detail | teklif | Operasyon | Medium |
| `owner/mesajlar/index.blade.php` | Messages | mesajlar | Operasyon | Simple |
| `owner/belgeler/index.blade.php` | Documents | belgeler | Belgeler | Simple |
| `owner/raporlar/index.blade.php` | Reports | raporlar | Finans | Medium |
| `owner/auth/login.blade.php` | Owner login | - | - | Simple |

**Design Patterns (owner layout):**
- Alpine.js: `x-data="{ darkMode, mobileMenu }"`
- Nav links: `text-gray-600 hover:text-blue-600`
- Active nav: `text-blue-600 border-b-2 border-blue-600`
- Toast messages: Fixed position with transitions
- Cards: `rounded-xl border border-gray-200 bg-white`

---

## Frontend/Guest Screens

### Public Listings (resources/views/frontend/ilanlar/)

| Path | Purpose | Complexity |
|------|---------|------------|
| `frontend/ilanlar/index.blade.php` | Listing search with filters | Complex |
| `frontend/ilanlar/show.blade.php` | Property detail page | Complex |
| `frontend/ilanlar/international.blade.php` | International listings | Medium |

### Advisors (resources/views/frontend/danismanlar/)

| Path | Purpose | Complexity |
|------|---------|------------|
| `frontend/danismanlar/index.blade.php` | Advisor list | Medium |
| `frontend/danismanlar/show.blade.php` | Advisor profile | Medium |

### Other Frontend Pages

| Path | Purpose | Complexity |
|------|---------|------------|
| `frontend/portfolio/index.blade.php` | Portfolio | Medium |
| `frontend/dynamic-form/index.blade.php` | Dynamic forms | Medium |
| `frontend/scripts/ai-search.blade.php` | AI search | Medium |

### Landing Pages (resources/views/public/landing/)

| Path | Purpose | Complexity |
|------|---------|------------|
| `public/landing/invest-in-turkey.blade.php` | Investment landing | Complex |
| `public/landing/golden-visa-greece.blade.php` | Golden visa Greece | Medium |
| `public/landing/uk-investment.blade.php` | UK investment | Medium |
| `public/landing/calculator.blade.php` | Investment calculator | Medium |
| `public/ai-advisor.blade.php` | AI advisor | Medium |

### Root Views

| Path | Purpose | Complexity |
|------|---------|------------|
| `yaliihan-home-clean.blade.php` | Clean homepage | Complex |
| `yaliihan-contact.blade.php` | Contact page | Medium |
| `yaliihan-property-listing.blade.php` | Property listing | Medium |
| `login.blade.php` | Login page | Simple |

### Danışman Views

| Path | Purpose | Complexity |
|------|---------|------------|
| `danisman/profil/edit.blade.php` | Profile edit | Medium |
| `ilanlar/danisman-ilanlari.blade.php` | Advisor listings | Medium |

---

## Shared Components (resources/views/components/)

### Neo Design System Components (`components/neo/`)

| Component | Purpose | Props |
|-----------|---------|-------|
| `neo/button.blade.php` | Button with variants | variant, size, type, disabled, loading, href, icon |
| `neo/input.blade.php` | Text input | label, error, help, type, placeholder |
| `neo/select.blade.php` | Select dropdown | - |
| `neo/badge.blade.php` | Badge component | - |
| `neo/status-badge.blade.php` | Status badge | type (success/warning/danger/info), label |
| `neo/card.blade.php` | Card component | - |
| `neo/dropdown.blade.php` | Dropdown menu | - |
| `neo/dropdown-item.blade.php` | Dropdown item | - |
| `neo/stat-card.blade.php` | Stats card | - |
| `neo/empty-state.blade.php` | Empty state | - |
| `neo/breadcrumb.blade.php` | Breadcrumb | - |
| `neo/aktiflik-durumu-badge.blade.php` | Active status badge | - |

### Yaliihan Design Components (`components/yaliihan/`)

| Component | Purpose |
|-----------|---------|
| `yaliihan/navigation.blade.php` | Navigation |
| `yaliihan/hero-section.blade.php` | Hero section |
| `yaliihan/hero-search-tabs.blade.php` | Search tabs |
| `yaliihan/search-form.blade.php` | Search form |
| `yaliihan/property-card.blade.php` | Property card |
| `yaliihan/property-listing.blade.php` | Property listing |
| `yaliihan/property-detail.blade.php` | Property detail |
| `yaliihan/property-gallery.blade.php` | Photo gallery |
| `yaliihan/map-component.blade.php` | Map |
| `yaliihan/agent-card.blade.php` | Agent card |
| `yaliihan/contact-page.blade.php` | Contact page |
| `yaliihan/footer.blade.php` | Footer |
| `yaliihan/language-currency-selector.blade.php` | Language/currency |

### Property Components

| Component | Purpose |
|-----------|---------|
| `property-card.blade.php` | Property card (general) |
| `property-placeholder.blade.php` | Placeholder image |
| `price-display.blade.php` | Price display |
| `price-converter.blade.php` | Currency converter |
| `rental-price-calculator.blade.php` | Rental calculator |
| `price-history-chart.blade.php` | Price history |
| `harita-gosterimi.blade.php` | Map display |

### Form Components (`components/fields/`)

| Component | Purpose |
|-----------|---------|
| `fields/input-text.blade.php` | Text input |
| `fields/input-number.blade.php` | Number input |
| `fields/textarea.blade.php` | Textarea |
| `fields/select.blade.php` | Select |
| `fields/multiselect.blade.php` | Multi-select |
| `fields/toggle.blade.php` | Toggle switch |
| `fields/render-loop.blade.php` | Render loop |

### Form Builder Components

| Component | Purpose |
|-----------|---------|
| `form-builder/field.blade.php` | Dynamic field |
| `form-standards.blade.php` | Form standards |

### CRUD Components (`components/crud/`)

| Component | Purpose |
|-----------|---------|
| `crud/form.blade.php` | CRUD form |
| `crud/table.blade.php` | CRUD table |
| `crud/card.blade.php` | CRUD card |
| `crud/filter.blade.php` | Filter |
| `crud/photo-upload.blade.php` | Photo upload |

### AI Components

| Component | Purpose |
|-----------|---------|
| `ai-chat-widget.blade.php` | AI chat widget |
| `ai-smart-search.blade.php` | AI search |
| `ai-hero.blade.php` | AI hero section |
| `ai-announcement-banner.blade.php` | AI announcement |
| `ai/smart-match-widget.blade.php` | Smart match |

### Other Components

| Component | Purpose |
|-----------|---------|
| `icon.blade.php` | SVG icon library (60+ icons) |
| `modal.blade.php` | Modal dialog |
| `checkbox.blade.php` | Checkbox |
| `radio.blade.php` | Radio button |
| `label.blade.php` | Label |
| `input.blade.php` | General input |
| `badge.blade.php` | Badge |
| `filter-panel.blade.php` | Filter panel |
| `gelismis-ilan-arama.blade.php` | Advanced search |
| `listing-navigation.blade.php` | Listing nav |
| `favori-toggle.blade.php` | Favorite toggle |
| `flash-messages.blade.php` | Flash messages |
| `reference-generator.blade.php` | Reference generator |
| `demographic-info-card.blade.php` | Demographics |
| `interactive-property-finder.blade.php` | Property finder |
| `site-live-search.blade.php` | Live search |
| `live-search-field.blade.php` | Live search field |
| `cortex-precision-seal.blade.php` | AI seal |
| `image-analysis-upload.blade.php` | Image upload |
| `field-mcp-widget.blade.php` | Field MCP |
| `impact-dashboard-widget.blade.php` | Impact widget |
| `voice-search-button.blade.php` | Voice search |
| `market-intelligence/trust-breakdown.blade.php` | Trust breakdown |

### Admin Components (`components/admin/`)

| Component | Purpose |
|-----------|---------|
| `admin/ilanlar/components/*.blade.php` | 30+ ilan form components |
| `admin/toast.blade.php` | Toast notifications |
| `admin/file-upload.blade.php` | File upload |
| `admin/select.blade.php` | Admin select |
| `admin/toggle.blade.php` | Admin toggle |
| `admin/modal.blade.php` | Admin modal |
| `admin/meta-info.blade.php` | Meta info |
| `admin/badge.blade.php` | Badge |
| `admin/header/notification-dropdown.blade.php` | Notifications |
| `admin/exchange-rate-widget.blade.php` | Exchange rates |
| `admin/opportunity-board-widget.blade.php` | Opportunities |
| `admin/danisman-social-links.blade.php` | Social links |

### Ilan Components (`components/ilan/`)

| Component | Purpose |
|-----------|---------|
| `ilan/feature-drag-sort.blade.php` | Feature drag-drop |
| `ilan/feature-group-list.blade.php` | Feature groups |
| `ilan/property-features-mcp.blade.php` | Property features |
| `ilan/cortex-card-a/b/c.blade.php` | Cortex cards |

### Villa Components (`components/villa/`)

| Component | Purpose |
|-----------|---------|
| `villa/oda-yatak-listesi.blade.php` | Room list |
| `villa/hizmetler-dahil.blade.php` | Included services |
| `villa/spor-eglence-saglik.blade.php` | Amenities |

### SEO Components (`components/seo/`)

| Component | Purpose |
|-----------|---------|
| `seo/meta-tags.blade.php` | Meta tags |
| `seo/structured-data.blade.php` | Schema.org |

### Context7 Components

| Component | Purpose |
|-----------|---------|
| `context7-live-search.blade.php` | Live search |
| `crm-contact-manager.blade.php` | CRM contact |

### Feature Components

| Component | Purpose |
|-----------|---------|
| `feature-modal-selector.blade.php` | Feature selector |
| `fixtures-manager.blade.php` | Fixtures |

---

## Livewire Component Views (resources/views/livewire/)

### AI Telemetry Widgets (`livewire/ai-telemetry/`)

| Component | Purpose |
|-----------|---------|
| `cost-overview-widget.blade.php` | Cost overview |
| `live-activity-widget.blade.php` | Live activity |
| `error-rate-widget.blade.php` | Error rates |
| `request-volume-widget.blade.php` | Request volume |
| `token-leaderboard-widget.blade.php` | Token usage |
| `provider-performance-widget.blade.php` | Provider stats |

### Admin Livewire (`livewire/admin/`)

| Component | Purpose |
|-----------|---------|
| `governance-dashboard.blade.php` | Governance |
| `governance-command-center.blade.php` | Command center |

### Publication (`livewire/publication/`)

| Component | Purpose |
|-----------|---------|
| `matching-results.blade.php` | Matching results |

---

## Vue Components (resources/js/components/)

| Component | Purpose |
|-----------|---------|
| `AI/AIPricePrediction.vue` | AI price prediction |
| `AI/AIChatWidget.vue` | AI chat widget |
| `AI/AIDashboard.vue` | AI dashboard |

---

## Navigation Architecture Map

### Admin Navigation (layouts/admin.blade.php)

```
┌─────────────────────────────────────────────────────────────┐
│  Yalıhan AI OS   [Pano] [İlanlar] [CRM] [Danışmanlar] [Analitik] [AI] [Portföy]  [🔔] [⚙️] │
└─────────────────────────────────────────────────────────────┘

Pano → admin.dashboard.index
├── admin.dashboard.admin
├── admin.dashboard.agent
├── admin.dashboard.danisman
├── admin.dashboard.investor
└── admin.dashboard.user

İlanlar → admin.ilanlar.index
├── admin.ilanlar.create-wizard
├── admin.ilanlar.edit
├── admin.ilanlar.show (cockpit)
└── admin.ilanlar.calendar

CRM → admin.crm.dashboard
├── admin.crm.index
├── admin.crm.pipeline
└── admin.crm.customers.index

Danışmanlar → admin.danisman.index

Analitik → admin.analytics.index
├── admin.analytics.dashboard
├── admin.analytics.context7-dashboard
└── admin.analitik.istatistikler.*

AI → admin.ai.dashboard
├── admin.ai.usage
├── admin.ai.telemetry.dashboard
├── admin.danisman-ai.index
└── admin.page-analyzer.*

Portföy → advisor.portfolio-doctor
```

### Owner Navigation (layouts/owner.blade.php)

```
[Logo: Mülk Sahibi Paneli]
[Ana Sayfa] [İlanlarım] [Teklifler] [Mesajlar] [Belgelerim] [Raporlar]  [🌙] [Çıkış]
```

### Frontend Navigation (layouts/frontend.blade.php)

```
┌─────────────────────────────────────────────────────────────────┐
│  Yalıhan EMLAK   [Konut] [Arsa] [Yazlık] [Uluslararası] [Danışmanlar]  [İletişim] [Giriş Yap/Panel] │
└─────────────────────────────────────────────────────────────────┘
```

---

## Design Pattern Inventory

### 1. Stats Card Pattern

**File:** `admin/dashboard/index.blade.php`

```blade
<div class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow dark:border-slate-800 dark:bg-slate-900">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900">
                    <svg class="h-6 w-6 text-orange-600">...</svg>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="truncate text-sm font-medium text-gray-500">Title</dt>
                    <dd class="flex items-baseline">
                        <div class="text-2xl font-semibold text-gray-900">Value</div>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
```

**CSS Classes:** `overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow`

### 2. Gradient Action Card Pattern

**File:** `admin/dashboard/index.blade.php`

```blade
<div class="overflow-hidden rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg">
    <div class="p-8">
        <!-- Gradient backgrounds for AI/emphasis sections -->
    </div>
</div>
```

### 3. Data Table Pattern

```blade
<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-slate-900">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Header</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 bg-gray-50 dark:divide-gray-700 dark:bg-slate-900">
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">Data</td>
        </tr>
    </tbody>
</table>
```

### 4. Filter/Search Form Pattern

**File:** `admin/ilanlar/index.blade.php`

```blade
<div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-200 dark:border-slate-700 shadow-xl p-8">
    <form @submit.prevent="applyFilters()">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <div class="md:col-span-12 lg:col-span-12 flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative group">
                    <input type="text" x-model="filters.search" 
                           class="w-full pl-14 pr-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl">
                </div>
            </div>
        </div>
    </form>
</div>
```

### 5. Tab Navigation Pattern

```blade
<div class="flex items-center p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border border-slate-200 w-fit">
    <a href="{{ route('...', ['tab' => 'active']) }}"
       class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter
              {{ $activeTab === 'active' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-lg scale-105' : 'text-slate-500' }}">
        AKTİF
    </a>
</div>
```

### 6. Bulk Actions Toolbar Pattern

```blade
<div x-show="selectedIds.length > 0" x-transition
     class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 px-6 py-4 flex items-center justify-between mb-4 rounded-lg">
    <span x-text="`${selectedIds.length} ilan seçildi`"></span>
    <div class="flex items-center gap-3">
        <button @click="bulkAction('activate')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white...">
            Aktif Yap
        </button>
        <button @click="bulkAction('deactivate')" class="...">Pasif Yap</button>
        <button @click="confirmBulkDelete()" class="...">Sil</button>
    </div>
</div>
```

### 7. Toast Notification Pattern

**File:** `layouts/owner.blade.php`

```blade
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     x-transition class="pointer-events-auto bg-white/80 backdrop-blur-md border-l-4 border-emerald-500 rounded-lg shadow-xl p-4 flex items-start gap-3">
    <svg class="w-5 h-5 text-emerald-500">...</svg>
    <p class="text-sm text-gray-700">{{ session('basarili') }}</p>
</div>
```

### 8. Cockpit Widget Pattern

**File:** `admin/ilanlar/show.blade.php`

```blade
<section class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-900">Title</h3>
        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded border">Status</span>
    </div>
    <div class="p-6">
        @include('admin.ilanlar.components.cockpit.sub-component')
    </div>
</section>
```

### 9. Badge Pattern (Multiple Variants)

```blade
<!-- Success -->
<span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 inline-flex rounded-full px-2 text-xs font-semibold leading-5">
    Aktif
</span>

<!-- Warning -->
<span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 px-2 py-1 text-xs font-medium rounded-full">
    Beklemede
</span>

<!-- Danger -->
<span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-3 py-1 text-xs font-medium rounded-full">
    Hata
</span>

<!-- Info -->
<span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-0.5 text-xs font-medium rounded border">
    Canlı
</span>
```

### 10. Property Card Pattern

**File:** `frontend/ilanlar/index.blade.php`

```blade
<a href="{{ route('ilanlar.show', $ilan->id) }}" class="ilan-card-lux group bg-white rounded-2xl p-4">
    <div class="relative h-64 overflow-hidden bg-slate-100">
        <img src="{{ $fotoUrl }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover">
        <div class="absolute top-4 left-4 flex gap-2">
            <span class="bg-primary text-white px-3 py-1 rounded-full text-[10px] font-bold">KİRALIK</span>
        </div>
        <div class="absolute bottom-4 right-4">
            <span class="bg-primary text-white px-4 py-2 rounded-lg font-bold text-lg">€1,500,000</span>
        </div>
    </div>
    <div class="p-6">
        <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $ilan->baslik }}</h3>
        <div class="flex items-center gap-1.5 text-sm text-slate-500 mb-6">
            <x-icon name="konum" class="w-4 h-4"/> Location
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <span>3 Oda</span>
            <span>2 Banyo</span>
            <span>150 m²</span>
        </div>
    </div>
</a>
```

---

## Color & Status Usage

### Color → Semantic Meaning

| Color Class | Light Mode | Dark Mode | Usage |
|-------------|-----------|-----------|-------|
| `green-*` / `emerald-*` | Active, success, online | Same | Yayın_durumu = Aktif |
| `red-*` | Error, danger, deleted | Same | Errors, churn risk |
| `yellow-*` / `amber-*` | Warning, pending | Same | Beklemede, fiyat fırsatı |
| `blue-*` | Info, links, primary | Same | Primary actions, info |
| `purple-*` | AI, premium features | Same | AI dashboards |
| `orange-*` | İlanlar, highlights | Same | İlan management |
| `gray-*` / `slate-*` | Neutral, disabled | Same | Inactive, borders |
| `pink-*` | Special features | Same | Matching, alerts |

### Status Badge Styles

```blade
<!-- Yayın Durumu -->
yayin_durumu = 'yayinda' → bg-green-100 text-green-800
yayin_durumu = 'beklemede' → bg-yellow-100 text-yellow-800
yayin_durumu = 'taslak' → bg-gray-100 text-gray-800
yayin_durumu = 'pasif' → bg-red-100 text-red-800
yayin_durumu = 'arsiv' → bg-slate-100 text-slate-800

<!-- Özel Durumlar -->
Satıldı → bg-blue-100 text-blue-600
Kiralandı → bg-purple-100 text-purple-600
İşlendi → bg-emerald-100 text-emerald-600
Yeni → bg-white/90 text-slate-800
```

### CSS Variables (Frontend Layout)

```css
:root {
    --primary: #2563EB;
    --ege: #0D5FA3;
    --ege-light: #EFF6FF;
    --satilik: #15803D;
    --kiralik: #B45309;
}

/* Badge System */
.badge-satilik { background: #DCFCE7; color: #15803D; }
.badge-kiralik { background: #FEF3C7; color: #B45309; }
.badge-proje { background: #EFF6FF; color: #1D4ED8; }
```

---

## Button Hierarchy Inventory

### Primary Actions

```blade
<!-- Large CTA -->
<a href="..." class="inline-flex items-center gap-2.5 px-6 py-3.5 
    bg-gradient-to-r from-orange-500 to-amber-600 
    hover:from-orange-600 hover:to-amber-700 
    text-white rounded-xl shadow-lg shadow-orange-500/25 
    font-black text-sm uppercase tracking-tighter">
    <x-icon name="ekle" class="w-4 h-4" />
    New Listing
</a>
```

### Secondary Actions

```blade
<!-- Standard Button -->
<button class="px-4 py-2 bg-blue-600 text-white rounded-lg 
    hover:bg-blue-700 font-medium text-sm">
    Action
</button>

<!-- Outline Button -->
<button class="px-4 py-2 border border-gray-300 text-gray-700 
    rounded-lg hover:bg-gray-50 font-medium">
    Cancel
</button>
```

### Tertiary/Danger Actions

```blade
<!-- Danger -->
<button class="px-4 py-2 bg-red-600 text-white rounded-lg 
    hover:bg-red-700 font-medium">
    Delete
</button>

<!-- Ghost -->
<button class="text-gray-600 hover:text-gray-900 underline">
    Cancel
</button>
```

### Icon Buttons

```blade
<!-- Square Icon Button -->
<button class="w-9 h-9 flex items-center justify-center 
    rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
    <svg class="w-4 h-4">...</svg>
</button>
```

### neo/button Variants

```php
$variantClasses = [
    'primary' => 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white',
    'secondary' => 'bg-gradient-to-r from-gray-500 to-gray-600 text-white',
    'success' => 'bg-gradient-to-r from-green-500 to-green-600 text-white',
    'danger' => 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    'warning' => 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
    'info' => 'bg-gradient-to-r from-cyan-500 to-cyan-600 text-white',
    'ghost' => 'bg-transparent hover:bg-gray-100 text-gray-700 border border-gray-300',
    'link' => 'bg-transparent text-blue-600 hover:text-blue-800 underline',
];
```

---

## Form Patterns

### Input Field Pattern

**File:** `components/neo/input.blade.php`

```blade
<div class="space-y-1">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
        {{ $label }}
        @if($required) <span class="text-red-500 ml-1">*</span> @endif
    </label>
    <div class="relative">
        <input type="{{ $type }}"
               id="{{ $inputId }}"
               name="{{ $name }}"
               value="{{ $value }}"
               placeholder="{{ $placeholder }}"
               class="block w-full px-4 py-2.5 border rounded-lg 
                      focus:outline-none focus:ring-2 focus:ring-offset-2
                      border-gray-300 focus:border-blue-500 focus:ring-blue-500/20
                      dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    @if($help)
        <p class="text-sm text-gray-500">{{ $help }}</p>
    @endif
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
```

### Select Field Pattern

```blade
<select name="kategori_id" x-model="filters.kategori_id" @change="applyFilters()"
        class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-100 
               rounded-2xl focus:border-orange-500 cursor-pointer">
    <option value="">Tümü</option>
    @foreach($kategoriler as $kategori)
        <option value="{{ $kategori->id }}">{{ $kategori->name }}</option>
    @endforeach
</select>
```

### Checkbox Pattern

```blade
<!-- Custom Checkbox -->
<input type="checkbox" 
       class="w-5 h-5 rounded-lg border-2 border-slate-300 
              text-orange-500 focus:ring-orange-500 bg-transparent">

<!-- Toggle Switch -->
<input type="checkbox" 
       class="relative inline-flex h-6 w-11 items-center rounded-full 
              bg-gray-200 peer-checked:bg-blue-600"
       x-model="value">
```

### Validation Error Display

```blade
@if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-lg">
        @foreach($errors->all() as $error)
            <p class="text-sm text-red-700">{{ $error }}</p>
        @endforeach
    </div>
@endif
```

### Form Layout Patterns

```blade
<!-- Grid Form -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label>Field 1</label>
        <input>
    </div>
    <div>
        <label>Field 2</label>
        <input>
    </div>
</div>

<!-- Stacked Form -->
<div class="space-y-4">
    <div>
        <label>Field</label>
        <input class="w-full">
    </div>
</div>
```

---

## Property Command Center Readiness

### Assessment by Screen

| Screen | Belongs to PCC | Command Center Tab |
|--------|---------------|-------------------|
| `admin/dashboard/index` | Yes | Genel Bakış |
| `admin/ilanlar/index` | Yes | Yayınlar |
| `admin/ilanlar/show` | Yes | All tabs (cockpit) |
| `admin/ilanlar/edit` | Yes | Yayınlar |
| `admin/ilanlar/create-wizard` | Yes | Yayınlar |
| `admin/ilanlar/calendar` | Partial | Operasyon |
| `admin/crm/dashboard` | Yes | Genel Bakış |
| `admin/crm/pipeline` | Yes | Operasyon |
| `admin/analytics/*` | Partial | Analitik |
| `admin/ai/*` | Yes | AI |
| `admin/blog/*` | No | - |
| `admin/property-hub/*` | Yes | Genel Bakış |
| `admin/takim-yonetimi/*` | Yes | Operasyon |
| `admin/reports/*` | Yes | Finans |
| `admin/workspace/cockpit` | Yes | Genel Bakış |
| `owner/dashboard` | Yes | Genel Bakiş |
| `owner/ilanlar/*` | Yes | Yayınlar |
| `owner/teklifler/*` | Yes | Operasyon |
| `owner/belgeler/*` | Yes | Belgeler |
| `owner/raporlar/*` | Yes | Finans |

### Command Center Tab Mapping

#### Tab 1: Genel Bakış
- Dashboard stats
- Recent activity
- Quick actions
- Property vitals

#### Tab 2: Yayınlar
- Listing management
- Create/Edit forms
- Photo gallery
- Publish workflow
- Feature packs

#### Tab 3: Rezervasyonlar
- Calendar views
- Booking management
- Seasonal pricing

#### Tab 4: Operasyon
- Pipeline management
- Team tasks
- CRM contacts
- Leads

#### Tab 5: Belgeler
- Document management
- Templates
- Export/Import
- Blog content

#### Tab 6: Medya
- Photo gallery
- Video management
- Document attachments

#### Tab 7: Finans
- Reports
- Price management
- Calculators
- Revenue forecast

#### Tab 8: Timeline
- Version history
- Audit logs
- Change tracking

#### Tab 9: AI
- AI dashboard
- Usage telemetry
- Smart suggestions
- Cortex actions

---

## Icon Library (x-icon component)

Located at: `resources/views/components/icon.blade.php`

### Icon Categories

**Action Icons:** arama, ekle, duzenle, sil, kaydet, kapat, goster, gizle, kopyala, yenile, filtrele, indir, yukle, gonder, paylash

**Status Icons:** onay, onay-daire, hata, uyari, bilgi, yildiz

**Navigation Icons:** sol-ok, sag-ok, yukari-ok, asagi-ok, sag-chevron, sol-chevron, asagi-chevron, menu

**Object Icons:** ev, konum, kullanici, kullanicilar, telefon, etiket, bina, takvim, grafik

**Contact Icons:** eposta, flas, katman, kutu

**AI/System Icons:** ai, robot, cog, zil, yukleniyor, para, harita

**Real Estate Icons:** yatak, banyo, oda, alan, bina, cagir, resim

**Admin Icons:** saat, kalkan, liste, ampul, kilit, dis-baglanti, resim, hesap, sunucu, parmak, ag, gunes, ay

---

## Technical Notes

### Alpine.js Usage Patterns

```blade
<!-- Component initialization -->
<div x-data="{ show: false, selectedIds: [] }">

<!-- Conditional rendering -->
<div x-show="selectedIds.length > 0" x-transition>

<!-- Event handling -->
<button @click="submit()" @mouseover="hover = true">

<!-- Two-way binding -->
<input x-model="search">

<!-- Computed -->
<span x-text="selectedIds.length + ' selected'">
```

### Livewire Integration

```blade
@livewire('component-name', ['prop' => $value])
```

### Vue Integration

```blade
<div x-data="aiDashboard()">
    <!-- Vue-like Alpine components -->
</div>
```

### Tailwind Dark Mode

```blade
<!-- Always use both classes -->
<div class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white">

<!-- Hover states -->
<button class="hover:bg-blue-700 dark:hover:bg-blue-600">

<!-- Border handling -->
<div class="border-gray-200 dark:border-slate-800">
```

---

## Files Reference

### Key Layout Files
- `/resources/views/layouts/admin.blade.php` - Admin master layout
- `/resources/views/layouts/frontend.blade.php` - Public layout
- `/resources/views/layouts/owner.blade.php` - Owner portal layout

### Key Component Files
- `/resources/views/components/icon.blade.php` - Icon library (60+ icons)
- `/resources/views/components/neo/button.blade.php` - Button component
- `/resources/views/components/neo/input.blade.php` - Input component
- `/resources/views/components/neo/status-badge.blade.php` - Status badge
- `/resources/views/components/property-card.blade.php` - Property card

### Key Page Files
- `/resources/views/admin/dashboard/index.blade.php` - Admin dashboard
- `/resources/views/admin/ilanlar/index.blade.php` - Listings management
- `/resources/views/admin/ilanlar/show.blade.php` - Listing cockpit
- `/resources/views/frontend/ilanlar/index.blade.php` - Public listings
- `/resources/views/owner/dashboard.blade.php` - Owner dashboard

---

*Document generated for YDS v1.0 and Property Command Center Information Architecture*
