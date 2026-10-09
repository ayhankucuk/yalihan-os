# Design Contract: DC-006 — Data Table Component Standardization

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** MEDIUM

---

## Evidence

### FACT (Current Table Components)

| Component | Location | Purpose |
|-----------|----------|---------|
| `table.blade.php` | `components/admin/` | Basic wrapper (390 bytes) |
| `listings-table.blade.php` | `components/admin/ilanlar/` | Listing-specific CRUD table |
| `desktop-table.blade.php` | `admin/ilanlar/partials/` | Inline table for ilanlar |

### FACT (Basic Table Component)

```blade
{{-- resources/views/components/admin/table.blade.php --}}
@props(['striped' => true])

<div class="overflow-hidden">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'c7-table min-w-full']) }}>
            <thead class="c7-thead">
                {{ $head ?? '' }}
            </thead>
            <tbody class="c7-tbody">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
```

**Problem:** Too basic. No sorting, filtering, pagination, bulk actions.

### FACT (Listings Table Component)

```blade
{{-- resources/views/components/admin/ilanlar/listings-table.blade.php --}}
@props(['listings'])
```

Features:
- Header with count
- Checkbox column
- Row with image, title, category, status, price
- Action buttons
- Empty state
- Mobile responsive (cards view)

**Problem:** Listing-specific, not reusable for other data types.

### FACT (Inline Table Pattern)

```blade
{{-- admin/ilanlar/partials/desktop-table.blade.php --}}
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="bg-slate-50/80 ...">
            <th>...</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ilanlar as $ilan)
            <tr>...</tr>
        @endforeach
    </tbody>
</table>
```

**Problem:** Inline tables in each page = no reuse, no consistency.

### FACT (Existing CSS)

```css
/* app.css */
.c7-table { @apply divide-y divide-gray-200 w-full; }
.c7-thead tr th { @apply px-6 py-3 text-left text-xs font-medium uppercase...; }
.c7-tbody tr { @apply bg-white; }
.c7-tbody tr:hover { @apply bg-gray-50; }

/* common-styles.css */
.pagination { @apply flex items-center justify-between; }
.pagination .page-link { ... }
.pagination .page-item.active .page-link { ... }
```

### FACT (Pages with Tables)

```
admin/kullanicilar/index.blade.php
admin/ilan-kategorileri/index.blade.php
admin/leads/index.blade.php
admin/talep-portfolyo/index.blade.php
admin/ilanlar/index.blade.php (uses partials)
admin/danisman/index.blade.php
admin/blog/posts/index.blade.php
admin/blog/categories/index.blade.php
admin/blog/tags/index.blade.php
```

**Total:** 15+ pages with tables, all different implementations.

---

## Karar

### PROPOSED: Unified Data Table Component

**Component Name:** `x-data-table`

**Design Pattern:** Headless-like with slot-based customization

```blade
<x-data-table
    :columns="$columns"
    :data="$items"
    :sortable="true"
    :filterable="true"
    :bulk-actions="true"
    :pagination="$items"
    route-prefix="admin.kisiler"
>
    <!-- Custom cell rendering via slots -->
    <x-slot name="cell:name">{{ $row->name }}</x-slot>
    <x-slot name="cell:status">
        <x-status-badge :status="$row->status" />
    </x-slot>
</x-data-table>
```

### Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | array | required | Column definitions |
| `data` | Collection | required | Data to display |
| `sortable` | bool | false | Enable column sorting |
| `filterable` | bool | false | Enable search/filter |
| `bulkActions` | bool | false | Enable bulk select |
| `pagination` | LengthAwarePaginator | null | Pagination data |
| `emptyMessage` | string | 'No items found' | Empty state text |
| `emptyAction` | string | null | Empty state CTA |

### Column Definition

```php
$columns = [
    [
        'key' => 'name',
        'label' => 'İsim',
        'sortable' => true,
        'class' => 'w-1/4',
    ],
    [
        'key' => 'email',
        'label' => 'E-posta',
        'sortable' => true,
    ],
    [
        'key' => 'status',
        'label' => 'Durum',
        'sortable' => false,
        'component' => 'status-badge', // or slot
    ],
    [
        'key' => 'actions',
        'label' => '',
        'sortable' => false,
        'class' => 'w-24 text-right',
    ],
];
```

---

## Scope

### New Component Structure

```
resources/views/components/admin/
├── data-table/
│   ├── index.blade.php           (main component)
│   ├── table-header.blade.php    (thead)
│   ├── table-row.blade.php       (tbody row)
│   ├── table-cell.blade.php      (td)
│   ├── table-empty.blade.php     (empty state)
│   ├── table-loading.blade.php   (loading state)
│   ├── bulk-actions-bar.blade.php (bulk actions)
│   ├── pagination.blade.php       (pagination)
│   └── table-search.blade.php    (search/filter)
```

### Files to CREATE

| File | Purpose |
|------|---------|
| `data-table/index.blade.php` | Main component with slots |
| `data-table/table-header.blade.php` | Sortable column headers |
| `data-table/table-row.blade.php` | Row wrapper |
| `data-table/table-cell.blade.php` | Cell rendering |
| `data-table/table-empty.blade.php` | Empty state |
| `data-table/table-loading.blade.php` | Loading skeleton |
| `data-table/bulk-actions-bar.blade.php` | Bulk actions |
| `data-table/pagination.blade.php` | Pagination |
| `data-table/search.blade.php` | Search input |

### Files to MODIFY (Migration)

Migrate these pages to use new component:
1. `admin/kullanicilar/index.blade.php`
2. `admin/ilan-kategorileri/index.blade.php`
3. `admin/leads/index.blade.php`
4. `admin/danisman/index.blade.php`

### Files NOT to Modify (Later)

```
admin/ilanlar/index.blade.php (complex, separate contract)
admin/blog/posts/index.blade.php (complex content)
```

---

## UI/UX Design

### Desktop Table View

```
┌──────────────────────────────────────────────────────────────────────┐
│  🔍 Search...                    [Bulk Actions ▼]        42 items   │
├──────────────────────────────────────────────────────────────────────┤
│  ☐ │ İsim ↕         │ E-posta ↕         │ Durum      │  │ ⋮    │
├────┼─────────────────┼────────────────────┼────────────┼──────────┤
│  ☐ │ Ahmet Yılmaz    │ ahmet@example.com │ ● Aktif   │  │ ⋮    │
│  ☐ │ Ayşe Demir      │ ayse@example.com  │ ● Pasif   │  │ ⋮    │
│  ☐ │ Mehmet Kaya      │ mehmet@example... │ ● Aktif   │  │ ⋮    │
└────┴─────────────────┴────────────────────┴────────────┴──────────┘
│                                                                      │
│  ◀ 1 2 3 ... 5 ▶                    Showing 1-10 of 42            │
└──────────────────────────────────────────────────────────────────────┘
```

### Mobile Card View

```
┌─────────────────────────────┐
│ ☐ Ahmet Yılmaz            │
│   ahmet@example.com        │
│   ● Aktif                  │
│   [Edit] [Delete]         │
├─────────────────────────────┤
│ ☐ Ayşe Demir              │
│   ayse@example.com         │
│   ● Pasif                   │
│   [Edit] [Delete]         │
└─────────────────────────────┘
```

### Empty State

```
┌─────────────────────────────────────┐
│                                     │
│         📭 No items found          │
│                                     │
│    Arama kriterlerinize uygun     │
│    sonuç bulunamadı.              │
│                                     │
│    [Clear Filters]  [+ New Item]   │
│                                     │
└─────────────────────────────────────┘
```

### Bulk Actions Bar

```
┌─────────────────────────────────────────────────────────────────────┐
│  ✓ 3 selected                                    [Delete] [Export] │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Component API

### Usage Example

```blade
@php
$columns = [
    ['key' => 'name', 'label' => 'İsim', 'sortable' => true],
    ['key' => 'email', 'label' => 'E-posta', 'sortable' => true],
    ['key' => 'status', 'label' => 'Durum', 'sortable' => false],
    ['key' => 'actions', 'label' => '', 'sortable' => false],
];
@endphp

<x-data-table
    :columns="$columns"
    :data="$kullanicilar"
    :sortable="true"
    :filterable="true"
    :bulk-actions="true"
    :pagination="$kullanicilar"
    empty-message="Kullanıcı bulunamadı"
>
    {{-- Name cell --}}
    <x-slot name="cell:name">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center">
                {{ substr($row->name, 0, 1) }}
            </div>
            <span class="font-medium">{{ $row->name }}</span>
        </div>
    </x-slot>

    {{-- Status cell --}}
    <x-slot name="cell:status">
        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
            {{ $row->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
            {{ $row->status === 'active' ? 'Aktif' : 'Pasif' }}
        </span>
    </x-slot>

    {{-- Actions cell --}}
    <x-slot name="cell:actions">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.kullanicilar.edit', $row->id) }}" class="...">Edit</a>
            <button @click="delete({{ $row->id }})" class="...">Delete</button>
        </div>
    </x-slot>
</x-data-table>
```

### Props Interface

```php
// resources/views/components/admin/data-table/index.blade.php
@props([
    'columns' => [],           // Column definitions
    'data' => collect([]),     // Collection of items
    'sortable' => false,      // Enable column sorting
    'filterable' => false,    // Enable search
    'bulkActions' => false,   // Enable bulk select
    'pagination' => null,     // Laravel pagination
    'emptyMessage' => 'No items found',
    'emptyAction' => null,    // Route for empty state CTA
    'emptyActionText' => 'Add New',
    'searchPlaceholder' => 'Search...',
    'selectAllName' => 'select-all',
    'rowCheckboxName' => 'selected[]',
])
```

---

## Technical Implementation

### Sorting Logic

```php
// Sort URL generation
$sortUrl = function($column) {
    $params = request()->except(['sort', 'direction']);
    $direction = (request('sort') === $column && request('direction') === 'asc') ? 'desc' : 'asc';
    return route(request()->route()->getName(), array_merge($params, [
        'sort' => $column,
        'direction' => $direction,
    ]));
};
```

### Bulk Actions

```blade
<!-- Bulk actions bar (appears when items selected) -->
<div x-show="selected.length > 0"
     x-transition
     class="fixed bottom-4 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-4">
    <span x-text="selected.length + ' selected'"></span>
    <form method="POST" action="{{ route('admin.kullanicilar.bulk-delete') }}">
        @csrf
        <input type="hidden" name="ids" x-model="selected.join(',')">
        <button type="submit" class="...">Delete</button>
    </form>
</div>
```

### Mobile Responsive

```blade
<!-- Toggle between table and cards -->
<div x-show="viewMode === 'table'" class="hidden md:block">
    <!-- Desktop table -->
</div>

<div x-show="viewMode === 'cards'" class="md:hidden">
    <!-- Mobile cards -->
    @foreach($data as $row)
        <div class="bg-white rounded-lg border p-4 mb-3">
            <!-- Card content -->
        </div>
    @endforeach
</div>
```

---

## Migration Guide

### Before (Inline Table)

```blade
<!-- admin/kullanicilar/index.blade.php -->
<table class="min-w-full">
    <thead>
        <tr>
            <th>İsim</th>
            <th>E-posta</th>
            <th>Durum</th>
        </tr>
    </thead>
    <tbody>
        @foreach($kullanicilar as $kullanici)
            <tr>
                <td>{{ $kullanici->name }}</td>
                <td>{{ $kullanici->email }}</td>
                <td>{{ $kullanici->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### After (Using Component)

```blade
@php
$columns = [
    ['key' => 'name', 'label' => 'İsim', 'sortable' => true],
    ['key' => 'email', 'label' => 'E-posta', 'sortable' => true],
    ['key' => 'status', 'label' => 'Durum'],
];
@endphp

<x-data-table
    :columns="$columns"
    :data="$kullanicilar"
    :sortable="true"
    :pagination="$kullanicilar"
>
    <x-slot name="cell:name">{{ $row->name }}</x-slot>
    <x-slot name="cell:email">{{ $row->email }}</x-slot>
    <x-slot name="cell:status">{{ $row->status }}</x-slot>
</x-data-table>
```

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Data table component exists | File exists at expected path |
| AC2 | Component accepts columns prop | Test with different column configs |
| AC3 | Sorting works on sortable columns | Click column header, verify URL |
| AC4 | Bulk actions selectable | Checkbox selects rows |
| AC5 | Bulk actions bar appears | Select rows, bar appears |
| AC6 | Empty state displays | Test with empty collection |
| AC7 | Pagination renders | Test with paginated data |
| AC8 | Mobile cards view works | Resize to mobile, check view |
| AC9 | Custom cell slots work | Test with slot content |
| AC10 | Existing pages still work | Migrate one page, verify |

---

## Files to CREATE

```
resources/views/components/admin/data-table/
├── index.blade.php           (48 lines)
├── table-header.blade.php    (25 lines)
├── table-row.blade.php       (15 lines)
├── table-cell.blade.php      (12 lines)
├── table-empty.blade.php     (25 lines)
├── table-loading.blade.php   (20 lines)
├── bulk-actions-bar.blade.php (30 lines)
├── pagination.blade.php      (35 lines)
└── search.blade.php         (20 lines)
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Create component files | MEDIUM | 9 |
| Add Alpine.js logic | MEDIUM | 1 |
| Add CSS styles | LOW | 1 |
| Test with real data | LOW | - |
| Migrate 1 sample page | LOW | 1 |
| **TOTAL** | **MEDIUM** | **~12** |

---

## Related Issues (from Design Map)

| Issue | Status |
|-------|--------|
| Tables inconsistent | This contract addresses |
| No reusable table component | This contract addresses |
| Pagination patterns vary | This contract addresses |

---

*YALIHAN TASARIMCI — DC-006 Design Contract v1.0*
