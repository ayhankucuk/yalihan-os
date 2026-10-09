# Design Contract: Danışman Çalışma Alanı

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Kanban Task:** t_d42e6c5e
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** MEDIUM

---

## Evidence

### FACT (Mevcut Dashboard)

```
File: resources/views/admin/dashboard/danisman.blade.php
Lines: 443
Status: MEVCUT ve FONKSİYONEL
```

### FACT (Mevcut İçerik)

| Section | Status | Notlar |
|---------|--------|--------|
| Header + Title | ✅ MEVCUT | "Danışman Dashboard" + CRM badge |
| İstatistik Kartları | ✅ MEVCUT | 4 kart (İlanlar, Aktif İlanlar, Müşteriler, Talepler) |
| Son İlanlarım | ✅ MEVCUT | List + "Tümünü Gör" linki |
| Son Müşterilerim | ✅ MEVCUT | List + "Tümünü Gör" linki |
| Hızlı İşlemler | ✅ MEVCUT | 5 action card |
| Performans Raporu | ✅ MEVCUT | Alpine.js + API call |
| Dark Mode | ✅ MEVCUT | dark:bg-slate-900 class'ları |

### FACT (Eksik Parçalar)

| Eksik | Öncelik | Açıklama |
|--------|---------|----------|
| Görevler/Takvim | MEDIUM | Takip edilecek görevler yok |
| Yetkisiz erişim davranışı | HIGH | Redirect/403 kontrolü eksik |
| Mobil optimizasyon | MEDIUM | Viewport/responsive kontrol gerekli |

### FACT (Ownership Rule)

```
Danışman sadece kendi verilerine erişir:
- IlanPolicy: danisman_id === auth()->id()
- KisiPolicy: danisman_id === auth()->id()
- TalepPolicy: danisman_id === auth()->id()
```

### FACT (Middleware)

```
role:danisman middleware MEVCUT
Route: /admin/dashboard/danisman
```

---

## Karar

### Option 1: Mevcut Dashboard'ı Genişlet

**Scope:**
1. Görevler/Takvim section ekle
2. Yetkisiz erişim için middleware kontrol
3. Mobil responsive optimize et

**Pros:**
- Mevcut kodu korur
- Hızlı iterasyon
- Risk düşük

**Cons:**
- Genişlemeci değil
- Yeniden kullanılabilirlik sınırlı

### Option 2: Sıfırdan Danışman Layout Oluştur

**Scope:**
1. `resources/views/danisman/layouts/` base layout
2. Sadece danışman için optimize edilmiş component'ler
3. Ownership filter'ları ile

**Pros:**
- Temiz ayrım
- Admin'den bağımsız
- Uzun vadeli sürdürülebilir

**Cons:**
- Daha fazla iş
- Risk yüksek

### RECOMMENDED: Option 1 (Genişletme)

**Rationale:**
- Mevcut dashboard zaten iyi bir temel
- Hızlı kazanç sağlar
- Düşük risk

---

## Scope

### EKLENMESİ GEREKENLER

#### 1. Görevler Section

```blade
{{-- Yeni section: Görevlerim --}}
<div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6">
    <h2 class="text-lg font-semibold mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Görevlerim
    </h2>
    
    @forelse($my_tasks ?? [] as $task)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
            <div class="flex items-center">
                <input type="checkbox" class="mr-3 rounded" {{ $task->completed ? 'checked' : '' }}>
                <span class="{{ $task->completed ? 'line-through text-gray-500' : '' }}">
                    {{ $task->title }}
                </span>
            </div>
            <span class="text-sm text-gray-500">{{ $task->due_date }}</span>
        </div>
    @empty
        <p class="text-gray-500 text-sm">Henüz görev yok</p>
    @endforelse
</div>
```

#### 2. Yetkisiz Erişim Middleware Kontrolü

```php
// app/Http/Middleware/EnsureDanismanOwnership.php
class EnsureDanismanOwnership
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user->hasRole('danisman')) {
            return $next($request);
        }
        
        // Danışman sadece kendi kayıtlarına erişebilir
        $resourceId = $request->route('id') ?? $request->route('ilan') ?? null;
        
        if ($resourceId) {
            $ilan = Ilan::find($resourceId);
            if ($ilan && $ilan->danisman_id !== $user->id) {
                abort(403, 'Bu kayda erişim yetkiniz yok.');
            }
        }
        
        return $next($request);
    }
}
```

#### 3. Mobil Responsive İyileştirmeler

```blade
{{-- Header responsive --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl md:text-4xl font-bold">...</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        {{-- Butonlar --}}
    </div>
</div>

{{-- Grid responsive --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    {{-- Kartlar --}}
</div>

{{-- Touch targets --}}
<button class="min-h-[44px] min-w-[44px] ...">
    {{-- 44px Apple minimum touch target --}}
</button>
```

#### 4. Bildirimler Section (Opsiyonel)

```blade
{{-- Son bildirimler --}}
<div class="bg-white dark:bg-slate-900 rounded-xl border p-6">
    <h2 class="text-lg font-semibold mb-4">Son Bildirimler</h2>
    
    @forelse($my_notifications ?? [] as $notification)
        <div class="flex items-start p-3 border-b border-gray-100 last:border-0">
            <div class="flex-shrink-0 mr-3">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm">{{ $notification->data['message'] }}</p>
                <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm">Yeni bildirim yok</p>
    @endforelse
</div>
```

---

## Information Architecture

### Dashboard Layout

```
┌─────────────────────────────────────────────────────────┐
│  🧑‍💼 Danışman Dashboard                    [Rapor] [+] │
│  Kişisel performans ve müşteri yönetimi                  │
├─────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ İlanlarım │ │ Aktif    │ │Müşteriler│ │ Taleplerim│  │
│  │    12    │ │    8     │ │    25    │ │    5     │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
├─────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐ ┌─────────────────────────┐   │
│  │ Son İlanlarım        │ │ Son Müşterilerim        │   │
│  │ ┌─────────────────┐ │ │ ┌─────────────────┐    │   │
│  │ │ Villa 1  | 2s   │ │ │ │ Ahmet Y. | 5d   │    │   │
│  │ │ Villa 2  | 1d   │ │ │ │ Mehmet K.| 1s   │    │   │
│  │ └─────────────────┘ │ │ └─────────────────┘    │   │
│  │    [Tümünü Gör]     │ │    [Tümünü Gör]        │   │
│  └─────────────────────┘ └─────────────────────────┘   │
├─────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐│
│  │ Hızlı İşlemler                                    ││
│  │ [+Yeni İlan] [+Yeni Müşteri] [Talepler] [Rapor]  ││
│  └─────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐ ┌─────────────────────────┐   │
│  │ Görevlerim           │ │ Son Bildirimler          │   │
│  │ ☐ Görev 1           │ │ ● Yeni talep geldi      │   │
│  │ ☑ Görev 2           │ │ ● İlan onaylandı       │   │
│  └─────────────────────┘ └─────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Navigation Flow

```
Danışman Dashboard
    ├── İstatistik Kartı click → İlgili listeye git
    ├── Yeni İlan → admin.ilanlar.create
    ├── Yeni Müşteri → admin.kisiler.create
    ├── Talepler → admin.talepler.index
    ├── Profilim → admin.danisman.show (kendi ID)
    └── Rapor → /admin/danisman/performance-report
```

---

## Component Specifications

### 1. İstatistik Kartı

```blade
@component('admin.components.stat-card')
    @slot('title', 'Toplam İlanlarım')
    @slot('value', $danismanStats['my_ilanlar'] ?? 0)
    @slot('trend', '+2 bu hafta')
    @slot('icon', 'building')
    @slot('color', 'blue')
    @slot('link', route('admin.ilanlar.index'))
@endcomponent
```

### 2. Action Card

```blade
<a href="{{ $url }}"
   class="flex items-center p-4 bg-{color}-50 rounded-lg hover:bg-{color}-100 transition-colors">
    <svg class="w-8 h-8 text-{color}-600 mr-3">...</svg>
    <div>
        <h3 class="font-medium">{{ $title }}</h3>
        <p class="text-sm text-gray-600">{{ $subtitle }}</p>
    </div>
</a>
```

### 3. Task Item

```blade
<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
    <div class="flex items-center">
        <input type="checkbox" class="mr-3 rounded border-gray-300">
        <span class="text-sm">{{ $task->title }}</span>
    </div>
    <span class="text-xs text-gray-500">{{ $task->due_date }}</span>
</div>
```

---

## Security & Ownership

### Policy Kontrolleri

| Kaynak | Policy | Ownership Rule |
|--------|--------|---------------|
| Ilan | IlanPolicy | danisman_id === auth()->id() |
| Kisi | KisiPolicy | danisman_id === auth()->id() |
| Talep | TalepPolicy | danisman_id === auth()->id() |

### Middleware Zinciri

```php
Route::middleware(['auth', 'verified', 'role:danisman', 'ownership'])
    ->prefix('danisman')
    ->group(function () {
        Route::get('/dashboard', [DanismanDashboardController::class, 'index'])
            ->name('danisman.dashboard');
    });
```

### Erişim Reddi Senaryoları

| Senaryo | Davranış |
|---------|----------|
| Başka danışmanın ilanına erişim | 403 Forbidden |
| Admin alanına erişim | Redirect veya 403 |
| API endpoint'te ownership kontrolü | JSON 403 |

---

## Mobile Responsive

### Breakpoints

| Breakpoint | Layout |
|------------|--------|
| < 640px (sm) | 1 kolon, stacked |
| 640px - 1024px (md) | 2 kolon grid |
| > 1024px (lg) | 4 kolon grid, side-by-side |

### Touch Targets

```
Minimum touch target: 44x44px (Apple HIG)
Spacing between targets: minimum 8px
```

### Responsive Components

```blade
{{-- Stat kartları responsive --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @each('admin.components.stat-card', $stats, 'stat')
</div>

{{-- Tablolar horizontal scroll --}}
<div class="overflow-x-auto">
    <table class="min-w-full">...</table>
</div>

{{-- Action buttons full width on mobile --}}
<button class="w-full md:w-auto">...</button>
```

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Dashboard 4 istatistik kartı gösterir | Görsel kontrol |
| AC2 | "Son İlanlarım" listesi çalışır | Liste render |
| AC3 | "Son Müşterilerim" listesi çalışır | Liste render |
| AC4 | Hızlı işlemler butonları çalışır | Route kontrol |
| AC5 | Dark mode tüm element'leri kapsar | dark: class kontrol |
| AC6 | Mobil görünüm responsive | 375px test |
| AC7 | Touch target'ler 44px+ | DevTools测量 |
| AC8 | Ownership middleware çalışır | Başka danışmanın verisine erişim testi |

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Görevler section ekle | LOW | 1 view |
| Ownership middleware | MEDIUM | 1 middleware |
| Mobil responsive | LOW | 1 view (CSS adjustments) |
| Bildirimler section | LOW | 1 view |
| **TOTAL** | **MEDIUM** | **~4 files** |

---

## Rollback Plan

1. Mevcut dashboard backup'u koru
2. Değişiklikler rollback edilirse eski haline dön
3. Middleware: Route'tan çıkar, eski route korunur

---

*YALIHAN TASARIMCI — Danışman Çalışma Alanı Design Contract v1.0*
