# Filtre Sistemi — Tasarım ve Mimari Kararları

**Tarih:** 18 Mayıs 2026  
**Karar veren:** Sistem analizi + SAB kuralları  
**Durum:** Planlama tamamlandı — implementasyon bekliyor

---

## Mevcut Sorun

`filter-panel.blade.php` beyaz/cam efektli, `#667eea` purple kullanıyor — hem yanlış renk hem yanlış tema.  
`opportunities.blade.php` inline JS fetch ile filtreliyor — URL güncellemiyor, bookmarklanamaz.  
`advanced-search.blade.php` ayrı component, ayrı stil — tutarsız.  
Her sayfa kendi filtre mantığını yazıyor — 3 farklı yöntem, 0 standart.

---

## Karar: 3 Filtre Tipi, 1 Standart

Sayfa türüne göre 3 farklı filtre deseni. Hepsi aynı renk, aynı animasyon, aynı backend mimarisi.

---

## Tip 1 — Liste Filtresi (İlanlar, Müşteriler, Danışmanlar)

### Görsel

```
┌─────────────────────────────────────────────────────────────────┐
│  🔍 Ara...          [Bölge ▾] [Durum ▾] [Fiyat ▾]  [⚙ Filtreler 3] [Temizle] │
└─────────────────────────────────────────────────────────────────┘
         ↓ "Filtreler" tıklanınca smooth expand:
┌─────────────────────────────────────────────────────────────────┐
│  Oda Sayısı: [1+] [2+] [3+] [4+]    Metrekare: [__] — [__]     │
│  Tarih: [Bu Hafta ▾]                 Danışman: [Tümü ▾]         │
│                               [Filtreleri Uygula]  [Sıfırla]    │
└─────────────────────────────────────────────────────────────────┘

Aktif filtreler chip olarak gösterilir:
[Bodrum ×] [Satılık ×] [3+ Oda ×]
```

### Davranış Kuralları

- **URL tabanlı:** Form submit = `GET` isteği, query params. JS zorunlu değil.
- **Aktif filtre sayacı:** "Filtreler" butonunda kaç filtre aktif → `[⚙ Filtreler 3]`
- **Chip ile temizleme:** Her aktif filtre chip'ine `×` butonu — tıklayınca o param URL'den düşer
- **"Temizle":** Tüm filtreleri sıfırlar, sadece base URL'e döner
- **Alpine görevi:** Sadece drawer açma/kapama ve chip render. Backend'e dokunmaz.

### Alpine Component

```js
// x-data="filterBar()"
function filterBar() {
    return {
        open: false,
        activeCount: 0,

        init() {
            const params = new URLSearchParams(window.location.search);
            // 'page', 'sort' hariç her param = aktif filtre
            const excluded = ['page', 'sort', 'search'];
            this.activeCount = [...params.keys()]
                .filter(k => !excluded.includes(k)).length;
        },

        toggle() { this.open = !this.open; },

        removeFilter(key) {
            const url = new URL(window.location);
            url.searchParams.delete(key);
            url.searchParams.delete('page'); // sayfayı sıfırla
            window.location = url.toString();
        }
    }
}
```

---

## Tip 2 — Chip Filtresi (Advisor Sayfaları)

### Görsel

```
[Tümü]  [🔥 Bugün]  [📈 Yükselen]  [⭐ Yüksek Skor]  [⚠ Kritik]
```

### Davranış Kuralları

- Tek seçim (radio gibi) veya çoklu seçim — sayfaya göre belirlenir
- Tıklanınca API çağrısı yapar, sonuçları sayfa içinde günceller
- URL `history.pushState` ile güncellenir (bookmarklanabilir, back çalışır)
- Yüklenirken chip disabled + spinner

### Alpine Component

```js
// x-data="chipFilter({ endpoint: '/api/v1/advisor/opportunities', multi: false })"
function chipFilter({ endpoint, multi = false }) {
    return {
        active: [],
        loading: false,
        results: [],

        async select(value) {
            if (multi) {
                this.active.includes(value)
                    ? this.active = this.active.filter(v => v !== value)
                    : this.active.push(value);
            } else {
                this.active = [value];
            }

            await this.fetch();
            this.pushUrl();
        },

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams(
                this.active.map(v => ['filter', v])
            );
            const res = await fetch(`${endpoint}?${params}`);
            this.results = await res.json();
            this.loading = false;
        },

        pushUrl() {
            const url = new URL(window.location);
            url.searchParams.set('filter', this.active.join(','));
            history.pushState({}, '', url);
        }
    }
}
```

---

## Tip 3 — Harita Filtresi

### Görsel

```
                           ┌─────────────────────┐
  [Harita alanı]           │  Fiyat: 0 — 10M ₺   │
                           │  Tip: [○] Satılık     │
                           │       [○] Kiralık     │
                           │  [Ara]               │
                           └─────────────────────┘
```

- Sağ üstte float panel, haritanın üstünde
- Küçük form submit = harita + liste birlikte güncellenir
- Mobilde → yukarıdan aşağıya inen drawer

---

## Backend Mimarisi (SAB Uyumlu)

### Katman Akışı

```
HTTP GET ?fiyat_min=500000&bolge=bodrum&oda=3
    ↓
IlanFilterRequest   (validasyon — Controller'a gelmeden önce)
    ↓
IlanFilterDTO       (typed DTO — raw array yasak)
    ↓
IlanRepository      (tek write/read authority)
  → applyFilters(Builder, FilterDTO)
    ↓
Paginated results   → View
```

### IlanFilterDTO

```php
// app/DTOs/IlanFilterDTO.php
final class IlanFilterDTO
{
    public function __construct(
        public readonly ?string $arama      = null,
        public readonly ?int    $fiyatMin   = null,
        public readonly ?int    $fiyatMax   = null,
        public readonly ?string $bolge      = null,
        public readonly ?int    $odaSayisi  = null,
        public readonly ?string $ilanDurumu = null,
        public readonly ?int    $danismanId = null,
        public readonly string  $siralama   = 'created_at',
        public readonly string  $yon        = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            arama:      $request->string('search')->toString() ?: null,
            fiyatMin:   $request->integer('fiyat_min') ?: null,
            fiyatMax:   $request->integer('fiyat_max') ?: null,
            bolge:      $request->string('bolge')->toString() ?: null,
            odaSayisi:  $request->integer('oda') ?: null,
            ilanDurumu: $request->string('durum')->toString() ?: null,
            danismanId: $request->integer('danisman_id') ?: null,
            siralama:   $request->string('sort')->toString() ?: 'created_at',
            yon:        $request->string('yon')->toString() ?: 'desc',
        );
    }
}
```

### Repository Filter Metodu

```php
// app/Repositories/IlanRepository.php — mevcut repository'e eklenir
public function listWithFilters(IlanFilterDTO $dto, int $perPage = 20): LengthAwarePaginator
{
    $query = Ilan::query()->forCurrentTenant(); // tenant scope

    if ($dto->arama) {
        $query->where(function ($q) use ($dto) {
            $q->where('baslik', 'like', "%{$dto->arama}%")
              ->orWhere('aciklama', 'like', "%{$dto->arama}%");
        });
    }

    if ($dto->fiyatMin) $query->where('fiyat', '>=', $dto->fiyatMin);
    if ($dto->fiyatMax) $query->where('fiyat', '<=', $dto->fiyatMax);
    if ($dto->bolge)    $query->whereHas('ilce', fn($q) => $q->where('slug', $dto->bolge));
    if ($dto->odaSayisi) $query->where('oda_sayisi', '>=', $dto->odaSayisi);
    if ($dto->ilanDurumu) $query->where('yayin_durumu', $dto->ilanDurumu);
    if ($dto->danismanId) $query->where('danisman_id', $dto->danismanId);

    $allowedSort = ['created_at', 'fiyat', 'guncelleme_tarihi'];
    $sort = in_array($dto->siralama, $allowedSort) ? $dto->siralama : 'created_at';
    $yon  = $dto->yon === 'asc' ? 'asc' : 'desc';

    return $query->orderBy($sort, $yon)->paginate($perPage)->withQueryString();
}
```

### Controller (SAB: Thin)

```php
// app/Http/Controllers/Admin/IlanController.php
public function index(IlanFilterRequest $request): View
{
    $dto     = IlanFilterDTO::fromRequest($request);
    $ilanlar = $this->ilanRepository->listWithFilters($dto);

    return view('admin.ilanlar.index', compact('ilanlar', 'dto'));
}
```

---

## Sıralama Standardı

Her liste sayfasında sağ üstte sıralama dropdown'u. Seçenekler sayfaya göre değişir ama yapı aynı:

```
[En Yeni ▾]
  ✓ En Yeni
    En Eski
    Fiyat (Artan)
    Fiyat (Azalan)
```

URL: `?sort=fiyat&yon=asc` — form submit, JS yok.

---

## Bileşen Listesi (Yeni Yazılacaklar)

| Bileşen | Dosya | Kullanım |
|---------|-------|---------|
| Filter Bar | `components/filter-bar.blade.php` | Liste sayfaları |
| Filter Chips | `components/filter-chips.blade.php` | Advisor sayfaları |
| Active Filter Tags | `components/filter-tags.blade.php` | Liste sayfaları (aktif filtreler) |
| Sort Dropdown | `components/sort-dropdown.blade.php` | Tüm liste sayfaları |
| Filter Drawer | `components/filter-drawer.blade.php` | Gelişmiş filtreler |

**Mevcut silinecekler:**
- `components/filter-panel.blade.php` (yanlış stil, yerini `filter-bar` alır)
- `components/advanced-search.blade.php` (yerini `filter-bar + filter-drawer` alır)

---

## Görsel Standartlar

### Filter Bar (koyu tema)

```html
<div class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-800 p-3">

    <!-- Arama -->
    <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500">...</svg>
        <input class="w-full rounded-lg bg-slate-900 py-2 pl-10 pr-4 text-sm text-slate-100
                      placeholder-slate-500 border border-slate-700
                      focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>

    <!-- Hızlı filtreler -->
    <select class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-300
                   focus:border-blue-500 focus:outline-none">

    <!-- Gelişmiş filtreler butonu -->
    <button class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-900
                   px-3 py-2 text-sm text-slate-300 hover:border-blue-500 hover:text-blue-400
                   transition-colors">
        <svg>...</svg> Filtreler
        <span x-show="activeCount > 0"
              class="rounded-full bg-blue-500 px-1.5 py-0.5 text-xs font-bold text-white"
              x-text="activeCount"></span>
    </button>
</div>
```

### Aktif Filtre Chip'leri

```html
<div class="flex flex-wrap gap-2">
    <span class="flex items-center gap-1.5 rounded-full border border-slate-600
                 bg-slate-800 px-3 py-1 text-xs text-slate-300">
        Bodrum
        <button @click="removeFilter('bolge')"
                class="text-slate-500 hover:text-slate-200 transition-colors">×</button>
    </span>
</div>
```

### Chip Filtresi (Advisor)

```html
<button @click="select('today')"
        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
        :class="active.includes('today')
            ? 'border-blue-500 bg-blue-500/15 text-blue-400'
            : 'border-slate-700 bg-slate-800 text-slate-400 hover:border-slate-500'">
    🔥 Bugün
</button>
```

---

## Uygulama Planı

### Sprint 1 — Temel Bileşenler (3-4 gün)
- [ ] `IlanFilterDTO` oluştur
- [ ] `IlanRepository::listWithFilters()` yaz
- [ ] `filter-bar.blade.php` yaz (koyu tema)
- [ ] `filter-tags.blade.php` yaz
- [ ] `sort-dropdown.blade.php` yaz
- [ ] `admin/ilanlar/index` → yeni filter-bar'a geçir

### Sprint 2 — Advisor Filtreleri (2-3 gün)
- [ ] `filter-chips.blade.php` yaz
- [ ] `opportunities.blade.php` → chip filtre geçişi
- [ ] `deal-radar.blade.php` → chip filtre geçişi
- [ ] `buyer-match-queue.blade.php` → chip filtre geçişi

### Sprint 3 — Temizlik (2 gün)
- [ ] `filter-panel.blade.php` sil
- [ ] `advanced-search.blade.php` sil
- [ ] Tüm sayfaları yeni bileşenlere geçir
- [ ] `IlanFilterRequest` validasyon kuralları ekle

---

## Değişmeyen Kurallar (SAB)

- Controller filtre mantığı içermez — sadece DTO oluşturur, Repository'e geçer
- Repository dışında `Ilan::where()` yazılmaz
- Tenant scope her sorguda zorunlu — `forCurrentTenant()` atlanamaz
- Raw SQL yasak — Eloquent builder kullanılır
- `withQueryString()` zorunlu — sayfalama URL filtrelerini korur

---

*Son güncelleme: 18 Mayıs 2026*
