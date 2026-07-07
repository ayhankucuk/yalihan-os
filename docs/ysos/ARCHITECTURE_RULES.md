# YSOS — Architecture Rules

> Architecture constraints for YALIHAN OS. These are not suggestions.

---

## Core Architecture

```
YALIHAN OS
├── Workspace (Primary Business Aggregate)
│   ├── Portfolio
│   ├── CRM
│   ├── Drive
│   ├── Publishing
│   ├── Finance
│   ├── Reservations
│   └── AI
├── Hermes (AI Workforce Orchestrator)
└── YSOS (Engineering Operating System)
```

---

## Forbidden Patterns

### No Direct Model Manipulation in Controllers

**Rule:** Controllers must never use Eloquent::create/update/delete directly.

**Correct:**
```php
// Controller
public function store(StoreRequest $request): RedirectResponse
{
    $ilanService = app(IlanService::class);
    $result = $ilanService->storeListing($request->validated());
    return to_route('ilanlar.show', $result['id']);
}
```

**Forbidden:**
```php
// ❌ NEVER
public function store(Request $request)
{
    Ilan::create($request->all()); // Direct model manipulation
}
```

---

### No Global Cache Operations

**Rule:** All cache operations must be tenant-scoped.

**Forbidden:**
```php
// ❌ NEVER
Cache::put('ilanlar', $ilanlar); // No tenant scope
Cache::flush(); // No tenant scope
```

**Correct:**
```php
// ✅ ALWAYS tenant-scoped
Cache::tags(["tenant_{$tenantId}"])->put('ilanlar', $ilanlar);
```

---

### No bare `DB::` backslash

**Rule:** Always import `DB` facade.

**Forbidden:**
```php
// ❌ NEVER
\DB::table('ilanlar')->get();
```

**Correct:**
```php
// ✅ ALWAYS imported
use Illuminate\Support\Facades\DB;
DB::table('ilanlar')->get();
```

---

### No Hardcoded URLs in Blade

**Rule:** Use `route()` helper.

**Forbidden:**
```php
// ❌ NEVER
<a href="/admin/ilanlar">İlanlar</a>
```

**Correct:**
```php
// ✅ ALWAYS route()
<a href="{{ route('admin.ilanlar.index') }}">İlanlar</a>
```

---

### No Route::has() in Blade Without FQCN

**Rule:** Use full namespace.

**Forbidden:**
```php
// ❌ NEVER
@if(Route::has('ilanlar.index'))
```

**Correct:**
```php
// ✅ ALWAYS FQCN
@if(\Illuminate\Support\Facades\Route::has('ilanlar.index'))
```

---

### No Empty Catch Blocks

**Rule:** Every catch block must log or rethrow.

**Forbidden:**
```php
// ❌ NEVER
try {
    // ...
} catch (\Exception $e) {
    // Silent catch
}
```

**Correct:**
```php
// ✅ ALWAYS log
try {
    // ...
} catch (\Exception $e) {
    Log::error('Ilan create failed', ['exception' => $e]);
    throw $e;
}
```

---

### No env() in Application Code

**Rule:** Use `config()` or `app()->environment()`.

**Forbidden:**
```php
// ❌ NEVER
$apiKey = env('OPENAI_API_KEY');
```

**Correct:**
```php
// ✅ ALWAYS config()
$apiKey = config('services.openai.api_key');
```

---

## Naming Authority (Context7 Turkish Canonical Names)

### Domain Model Fields

Database columns must use Turkish names:

| Forbidden | Canonical | Reason |
|-----------|-----------|--------|
| `status` | `yayin_durumu` | Domain field |
| `active` | `aktiflik_durumu` | Domain field |
| `is_active` | `aktiflik_durumu` | Domain field |
| `order` | `display_order` | Domain field |
| `featured` | `one_cikan` | Domain field |
| `featured_image` | `kapak_resmi` | Domain field |
| `city` | `il` | Domain field |
| `description` | `aciklama` | Domain field |
| `category` | `kategori` | Domain field |

### Framework Fields

Laravel framework fields stay in English:
- `created_at`, `updated_at`, `deleted_at`
- `remember_token`
- `id`, `uuid`

### Local Variables

PHP local variables use camelCase:
```php
$ilanListesi = Ilan::all(); // camelCase OK (local variable)
// BUT database columns must use snake_case
```

---

## Repository Authority Pattern

All write operations flow through:

```
Controller
    ↓
Service
    ↓
IlanCrudService (or domain-specific CrudService)
    ↓
Repository
    ↓
Database
```

No write operation bypasses this chain.

---

## Tenant Isolation

Every database query must be tenant-scoped:

```php
// ✅ Tenant-aware
Ilan::where('tenant_id', $tenantId)->get();

// ❌ NEVER unscoped
Ilan::all(); // Without tenant scope
```

Models use global scopes for tenant isolation.

---

## Hermes Architecture

```
Hermes (Event Broker)
├── Events
│   ├── IlanCreated
│   ├── IlanUpdated
│   ├── PhotoUploaded
│   └── DescriptionGenerated
├── Channels
│   ├── PhotoAgent
│   ├── DescriptionAgent
│   └── NotificationAgent
└── Pipeline
    └── Event → Channel → Agent → Result
```

---

## RESTRICTIONS

```
Do NOT redesign existing Hermes architecture.
Do NOT redesign Workspace architecture.
Do NOT introduce new Offices.
Do NOT duplicate governance.
```

---

## Architecture Decision Records

When an architecture decision is made:

1. Document in `docs/sprints/SPRINT_X_Y/03_DECISIONS.md`
2. If permanent, add to `docs/ysos/ARCHITECTURE_RULES.md`
3. If it changes existing patterns, update relevant context files

---

*Architecture constraints are not suggestions.*
