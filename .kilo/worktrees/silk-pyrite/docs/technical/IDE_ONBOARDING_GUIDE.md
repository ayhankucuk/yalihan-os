# 🎓 Yalıhan 2026 — IDE/AI Asistan Onboarding Rehberi

**Tarih:** 2026-05-23
**Hedef Kitle:** Cursor, Windsurf, Cline, GitHub Copilot, JetBrains AI, v0.dev
**Amaç:** Yeni IDE/AI asistanlarına projenin mimari kurallarını öğretmek

---

## 📋 İçindekiler

1. [Hızlı Başlangıç: İlk 5 Dakika](#1-hızlı-başlangıç-ilk-5-dakika)
2. [Otorite Hiyerarşisi](#2-otorite-hiyerarşisi)
3. [Zorunlu Okuma Listesi](#3-zorunlu-okuma-listesi)
4. [10 Altın Kural (Asla İhlal Edilemez)](#4-10-altın-kural-asla-ihlal-edilemez)
5. [Context7 Kanonik İsimlendirme](#5-context7-kanonik-isimlendirme)
6. [Mimari Katman Kuralları](#6-mimari-katman-kuralları)
7. [Frontend Kuralları](#7-frontend-kuralları)
8. [Test & Validation Protokolü](#8-test--validation-protokolü)
9. [Yaygın Hatalar ve Çözümleri](#9-yaygın-hatalar-ve-çözümleri)
10. [Antigravity Araçları Kullanımı](#10-antigravity-araçları-kullanımı)

---

## 1. Hızlı Başlangıç: İlk 5 Dakika

### Adım 1: Otorite Dosyasını Oku

```bash
# Projenin teknik anayasası
cat .sab/authority.json

# SAB kuralları (bağlayıcı)
cat docs/SAB.md

# Cline kuralları (bu IDE için özel)
cat .clinerules
```

### Adım 2: Proje Yapısını Anla

```bash
# Proje tipi: Laravel 10.x Modular Monolith
# Mimari: Service-Repository Pattern + CQRS (kısmi)
# DB: MySQL 8.0+
# Frontend: Blade + Alpine.js + Tailwind CSS
# Test: PHPUnit + Playwright
```

### Adım 3: İlk Kontrol

```bash
# Tüm quality gate'leri çalıştır
./scripts/tools/antigravity-full-gate.sh --quick

# Bekçi sağlık kontrolü
php artisan bekci:health
```

**Hedef:** Bekçi sağlık skoru %70+ (şu an %33)

---

## 2. Otorite Hiyerarşisi

Her karar ve kod yazımında şu sıra **mutlak** olarak izlenir:

```
┌─────────────────────────────────────────┐
│  1. İnsan (Kullanıcı)                   │ ← Mutlak ve nihai otorite
├─────────────────────────────────────────┤
│  2. Canlı Kod ve DB Şeması              │ ← Runtime gerçeği
├─────────────────────────────────────────┤
│  3. .sab/authority.json                 │ ← Yönetişim SSOT
├─────────────────────────────────────────┤
│  4. Yalıhan Bekçi / SAB Muhafızları     │ ← CI/CD blocker
├─────────────────────────────────────────┤
│  5. Referans Dokümanlar                 │ ← SAB.md, ONBOARDING.md
└─────────────────────────────────────────┘
```

### Kritik: Asla Varsayımda Bulunma

```bash
# ❌ YANLIŞ: Varsayım
# "Bu route muhtemelen admin.ilanlar.index'tir"

# ✅ DOĞRU: Doğrulama
./scripts/tools/antigravity-route-check.sh --check admin.ilanlar.index

# ❌ YANLIŞ: Varsayım
# "Bu component muhtemelen var"

# ✅ DOĞRU: Doğrulama
./scripts/tools/antigravity-component-check.sh x-icon

# ❌ YANLIŞ: Varsayım
# "Bu kolon muhtemelen ilanlar tablosunda var"

# ✅ DOĞRU: Doğrulama
./scripts/tools/antigravity-schema-check.sh ilanlar yayin_durumu
```

---

## 3. Zorunlu Okuma Listesi

Her görev öncesi bu dosyaları oku:

### Tier 1: Mutlak Zorunlu (Her Görev)

1. **[`docs/SAB.md`](docs/SAB.md)** — Teknik anayasa (20 bağlayıcı kural)
2. **[`.sab/authority.json`](.sab/authority.json)** — Yönetişim SSOT
3. **[`.clinerules`](.clinerules)** — IDE'ye özel kurallar

### Tier 2: Mimari Anlayış (İlk Gün)

4. **[`docs/ARCHITECTURE_DEEP_DIVE.md`](docs/ARCHITECTURE_DEEP_DIVE.md)** — 5 kritik mimari kararın "neden"i
5. **[`.sab/ONBOARDING.md`](.sab/ONBOARDING.md)** — Yeni geliştirici rehberi
6. **[`docs/BEKCI_CHANGELOG.md`](docs/BEKCI_CHANGELOG.md)** — Son 10 oturum değişiklikleri

### Tier 3: Domain Bilgisi (İlk Hafta)

7. **[`docs/yalihan-project-brain-v3.md`](docs/yalihan-project-brain-v3.md)** — Proje beyin haritası
8. **[`docs/API_CONTRACT.md`](docs/API_CONTRACT.md)** — API sözleşmeleri
9. **[`docs/FRONTEND_DESIGN_VISION.md`](docs/FRONTEND_DESIGN_VISION.md)** — Frontend tasarım sistemi

---

## 4. 10 Altın Kural (Asla İhlal Edilemez)

### Kural 1: Thin Controller (Controller'da Sıfır İş Mantığı)

```php
// ❌ YASAK
class IlanController extends Controller
{
    public function store(Request $request)
    {
        $ilan = Ilan::create($request->all()); // İş mantığı controller'da

        if ($request->has('urgent')) {
            $ilan->yayin_durumu = 'yayinda';
            $ilan->save();
        }

        return response()->json($ilan);
    }
}

// ✅ ZORUNLU
class IlanController extends Controller
{
    public function __construct(
        private IlanCrudService $crudService
    ) {}

    public function store(IlanStoreRequest $request)
    {
        // Controller sadece validate + delegate
        $ilan = $this->crudService->create($request->validated());

        return redirect()
            ->route('admin.ilanlar.show', $ilan)
            ->with('success', 'İlan oluşturuldu');
    }
}
```

### Kural 2: Write Authority (DB Yazma Sadece Service'den)

```php
// ❌ YASAK: Controller'da doğrudan yazma
Ilan::create($data);
$ilan->update($data);
Ilan::where('id', $id)->update(['yayin_durumu' => 'yayinda']);

// ✅ ZORUNLU: Service üzerinden
$this->ilanCrudService->create($validatedData);
$this->ilanCrudService->update($ilan, $validatedData);
$this->ilanCrudService->publish($ilan);
```

**İstisna:** Migration dosyaları ve seeder'lar.

### Kural 3: BaseModel (Tüm Modeller BaseModel Extend Etmeli)

```php
// ❌ YASAK
class Ilan extends Model
{
    use SoftDeletes;
    // ...
}

// ✅ ZORUNLU
class Ilan extends BaseModel
{
    // BaseModel otomatik sağlar:
    // - SoftDeletes
    // - HasActiveScope (->active())
    // - HasAuditTrail (created_by, updated_by)
    // - Timestamps
}
```

### Kural 4: Facade Import (PHP'de Tam Nitelikli İsim)

```php
// ❌ YASAK
use DB;
use Route;
use Log;

DB::table('ilanlar')->get();

// ✅ ZORUNLU
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

DB::table('ilanlar')->get();
```

**Blade'de:**

```blade
{{-- ❌ YASAK --}}
@if (Route::has('login'))

{{-- ✅ ZORUNLU --}}
@if (\Illuminate\Support\Facades\Route::has('login'))
```

### Kural 5: Deterministic Query (->first() Her Zaman ->orderBy() ile)

```php
// ❌ YASAK: Non-deterministic
$ilan = Ilan::where('aktiflik_durumu', 1)->first();

// ✅ ZORUNLU: Deterministic
$ilan = Ilan::where('aktiflik_durumu', 1)
    ->orderBy('id')
    ->first();

// ✅ ZORUNLU: Bypass (meşru durum)
$ilan = Ilan::where('aktiflik_durumu', 1)
    ->first(); // @sab-ignore-determinism — aggregate query, always 1 row
```

### Kural 6: Silent Catch Yasağı (Her Exception Loglanmalı)

```php
// ❌ YASAK: Silent catch
try {
    $this->service->process();
} catch (\Exception $e) {
    // Sessizce yutuldu
}

// ❌ YASAK: Boş catch
try {
    $this->service->process();
} catch (\Exception $e) {}

// ✅ ZORUNLU: Log + rethrow
try {
    $this->service->process();
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('Process failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}

// ✅ ZORUNLU: Bypass (meşru durum)
try {
    $this->cache->get('key');
} catch (\Exception $e) {
    /** @sab-ignore-catch — cache miss is expected */
}
```

### Kural 7: env() Yasağı (app/ Dizininde)

```php
// ❌ YASAK: app/ dizininde env()
$key = env('APP_KEY');
$debug = env('APP_DEBUG');

// ✅ ZORUNLU: config() kullan
$key = config('app.key');
$debug = config('app.debug');
```

**Blade'de:**

```blade
{{-- ❌ YASAK --}}
@env('local')
    <script>...</script>
@endenv

{{-- ✅ ZORUNLU --}}
@if(config('app.env') === 'local')
    <script>...</script>
@endif
```

**İstisna:** `config/` dizini ve `.env` dosyası.

### Kural 8: FontAwesome Yasağı (KESİN)

```blade
{{-- ❌ YASAK: FontAwesome --}}
<i class="fa fa-home"></i>
<i class="fas fa-user"></i>
<i class="far fa-heart"></i>

{{-- ✅ ZORUNLU: x-icon component --}}
<x-icon name="home" class="w-5 h-5" />
<x-icon name="user" class="w-5 h-5" />
<x-icon name="heart" class="w-5 h-5" />
```

**Mevcut ikonları kontrol et:**

```bash
grep -o "'[a-z-]*'" resources/views/components/icon.blade.php
```

### Kural 9: Layout Seçimi (Dizin Bazlı)

```blade
{{-- frontend/ dizini --}}
@extends('layouts.frontend')

{{-- admin/ dizini --}}
@extends('layouts.admin')

{{-- auth/ dizini --}}
@extends('layouts.guest')
```

**Kontrol:**

```bash
./scripts/tools/antigravity-layout-check.sh
```

### Kural 10: Dark Mode (Tailwind dark: Prefix)

```blade
{{-- ❌ EKSIK: Dark mode desteği yok --}}
<div class="bg-white text-gray-900">

{{-- ✅ ZORUNLU: Dark mode desteği --}}
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
```

---

## 5. Context7 Kanonik İsimlendirme

### Temel Prensip

**Her kavram için TEK BİR kanonik isim vardır.**

### Kanonik Sözlük

| Kavram | ❌ Yasak Varyantlar | ✅ Kanonik İsim |
|--------|---------------------|-----------------|
| Yayın durumu | `status`, `durum`, `state` | `yayin_durumu` |
| Aktiflik | `active`, `is_active`, `aktif` | `aktiflik_durumu` |
| Sıralama | `order`, `sort_order`, `siralama` | `display_order` |
| Öne çıkan | `featured`, `is_featured`, `vitrin` | `one_cikan` |
| Kapak görseli | `featured_image`, `image`, `gorsel` | `kapak_resmi` |
| Enlem | `latitude`, `enlem` | `lat` |
| Boylam | `longitude`, `boylam` | `lng` |
| Şehir | `city`, `sehir` | `il` / `il_adi` |
| Müşteriler | `musteriler`, `customers` | `kisiler` |
| HTTP durum | `status_code` | `http_durum_kodu` |
| Hata | `error` | `hata_mesaji` |

### Bypass Mekanizması

```php
// ✅ Meşru bypass (external API uyumu)
$data['status'] = $ilan->yayin_durumu; // context7-ignore
return response()->json($data);
```

### Kontrol

```bash
# Context7 ihlali tara
php artisan bekci:audit --context7
```

---

## 6. Mimari Katman Kuralları

### Controller Katmanı

**Sorumluluk:** Validate + Delegate

```php
class IlanController extends Controller
{
    public function __construct(
        private IlanCrudService $crudService,
        private IlanRepository $repository
    ) {}

    public function index(IlanIndexRequest $request)
    {
        // Sadece delegate
        $ilanlar = $this->repository->paginate($request->validated());

        return view('admin.ilanlar.index', compact('ilanlar'));
    }

    public function store(IlanStoreRequest $request)
    {
        // Sadece validate + delegate
        $ilan = $this->crudService->create($request->validated());

        return redirect()
            ->route('admin.ilanlar.show', $ilan)
            ->with('success', 'İlan oluşturuldu');
    }
}
```

**Yasak:**
- İş mantığı
- DB query
- Doğrudan model yazma
- Transaction yönetimi

### Service Katmanı

**Sorumluluk:** İş Mantığı + Transaction Yönetimi

```php
class IlanCrudService
{
    public function create(array $validatedData): Ilan
    {
        return DB::transaction(function () use ($validatedData) {
            // 1. Business rule validation
            $this->validateBusinessRules($validatedData);

            // 2. Data transformation
            $data = $this->transformForCreate($validatedData);

            // 3. Create
            $ilan = Ilan::create($data);

            // 4. Post-create hooks
            $this->afterCreate($ilan);

            // 5. Audit log
            Log::info('Ilan created', ['ilan_id' => $ilan->id]);

            return $ilan;
        });
    }

    private function validateBusinessRules(array $data): void
    {
        // Business logic burada
        if ($data['fiyat'] < 0) {
            throw new \InvalidArgumentException('Fiyat negatif olamaz');
        }
    }
}
```

### Repository Katmanı

**Sorumluluk:** Veri Erişimi (Sadece Okuma)

```php
class IlanRepository
{
    public function findActive(int $id): ?Ilan
    {
        return Ilan::active()
            ->with(['fotograflar', 'il', 'ilce'])
            ->find($id);
    }

    public function paginate(array $filters, int $perPage = 15)
    {
        return Ilan::active()
            ->when($filters['il_id'] ?? null, fn($q, $ilId) =>
                $q->where('il_id', $ilId)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
```

**Yasak:**
- Yazma işlemleri (create, update, delete)
- Transaction yönetimi
- İş mantığı

### Model Katmanı

**Sorumluluk:** Veri Yapısı + İlişkiler

```php
class Ilan extends BaseModel
{
    protected $table = 'ilanlar';

    protected $fillable = [
        'baslik',
        'aciklama',
        'fiyat',
        'il_id',
        'ilce_id',
        'yayin_durumu',
        'aktiflik_durumu',
        // ...
    ];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'yayin_durumu' => 'string',
        'aktiflik_durumu' => 'integer',
    ];

    // İlişkiler
    public function il()
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    public function fotograflar()
    {
        return $this->hasMany(IlanFotograf::class, 'ilan_id')
            ->orderBy('display_order');
    }
}
```

**Yasak:**
- İş mantığı (basit accessor/mutator hariç)
- DB query (relationship hariç)

---

## 7. Frontend Kuralları

### Tailwind CSS (Zorunlu)

```blade
{{-- ✅ DOĞRU: Tailwind --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        Başlık
    </h2>
</div>

{{-- ❌ YASAK: Bootstrap --}}
<div class="card">
    <h2 class="card-title">Başlık</h2>
</div>

{{-- ❌ YASAK: Inline style --}}
<div style="background: white; padding: 20px;">
```

### Alpine.js (Hafif Interaktivite)

```blade
{{-- ✅ DOĞRU: Alpine.js --}}
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">İçerik</div>
</div>

{{-- ❌ YASAK: jQuery --}}
<script>
    $('#toggle').click(function() {
        $('#content').toggle();
    });
</script>
```

### Vite (Build Tool)

```blade
{{-- ✅ DOĞRU: Vite --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- ❌ YASAK: Laravel Mix --}}
<link rel="stylesheet" href="{{ mix('css/app.css') }}">
```

### Dark Mode (Her Zaman Destekle)

```blade
{{-- ✅ DOĞRU: Dark mode desteği --}}
<div class="bg-white dark:bg-gray-800">
    <p class="text-gray-900 dark:text-gray-100">Metin</p>
    <button class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
        Buton
    </button>
</div>
```

---

## 8. Test & Validation Protokolü

### Her Görev Öncesi

```bash
# 1. Component varlık kontrolü
./scripts/tools/antigravity-component-check.sh <component-name>

# 2. Route varlık kontrolü
./scripts/tools/antigravity-route-check.sh --check <route.name>

# 3. DB kolon varlık kontrolü
./scripts/tools/antigravity-schema-check.sh <tablo> <kolon>

# 4. Layout doğrulama
./scripts/tools/antigravity-layout-check.sh
```

### Her Görev Sonrası

```bash
# 5. Tam quality gate
./scripts/tools/antigravity-full-gate.sh

# Veya hızlı mod (artisan olmadan)
./scripts/tools/antigravity-full-gate.sh --quick
```

### Test Suite

```bash
# Unit testler
php artisan test --testsuite=Unit

# Feature testler
php artisan test --testsuite=Feature

# Tüm testler
php artisan test

# Coverage raporu
php artisan test --coverage
```

---

## 9. Yaygın Hatalar ve Çözümleri

### Hata 1: Phantom Route

**Belirti:**

```php
return redirect()->route('admin.ilanlar.yeni'); // Route yok!
```

**Çözüm:**

```bash
# Önce route'u kontrol et
./scripts/tools/antigravity-route-check.sh --check admin.ilanlar.yeni

# Eğer yoksa, doğru route'u bul
php artisan route:list | grep ilanlar
```

### Hata 2: Var Olmayan Component

**Belirti:**

```blade
<x-card-premium /> {{-- Component yok! --}}
```

**Çözüm:**

```bash
# Önce component'i kontrol et
./scripts/tools/antigravity-component-check.sh card-premium

# Eğer yoksa, mevcut component'leri listele
find resources/views/components -name "*.blade.php"
```

### Hata 3: Yanlış Layout

**Belirti:**

```blade
{{-- resources/views/admin/ilanlar/index.blade.php --}}
@extends('layouts.frontend') {{-- Yanlış! Admin layout olmalı --}}
```

**Çözüm:**

```bash
# Layout kontrolü
./scripts/tools/antigravity-layout-check.sh

# Düzeltme
@extends('layouts.admin')
```

### Hata 4: Context7 İhlali

**Belirti:**

```php
$ilan->status = 'active'; // Yasak kelimeler!
```

**Çözüm:**

```php
$ilan->yayin_durumu = 'yayinda'; // Kanonik isim
$ilan->aktiflik_durumu = 1; // Kanonik isim
```

### Hata 5: Silent Catch

**Belirti:**

```php
try {
    $this->service->process();
} catch (\Exception $e) {
    // Boş catch
}
```

**Çözüm:**

```php
try {
    $this->service->process();
} catch (\Exception $e) {
    \Illuminate\Support\Facades\Log::error('Process failed', [
        'error' => $e->getMessage(),
    ]);
    throw $e;
}
```

---

## 10. Antigravity Araçları Kullanımı

### Preflight (Git-Diff Tabanlı Hızlı Kontrol)

```bash
# Sadece değişen dosyaları kontrol et (0.8 saniye)
./scripts/tools/antigravity-preflight.sh
```

**Ne kontrol eder:**
- 10 Altın Kural ihlali
- Context7 yasak kelimeler
- Silent catch
- env() kullanımı
- Deterministic query

### Full Gate (Kapsamlı Kontrol)

```bash
# Tüm kontrolleri sırayla çalıştır (2-3 saniye)
./scripts/tools/antigravity-full-gate.sh --quick
```

**Ne kontrol eder:**
- Preflight (10 Altın Kural)
- Layout validator
- Route duplication
- Component varlık

### Component Check

```bash
# Component varlığını kontrol et
./scripts/tools/antigravity-component-check.sh x-icon
./scripts/tools/antigravity-component-check.sh card-premium
```

### Route Check

```bash
# Route varlığını kontrol et
./scripts/tools/antigravity-route-check.sh --check admin.ilanlar.index

# Duplicate route tespiti
./scripts/tools/antigravity-route-check.sh --duplicates
```

### Schema Check

```bash
# DB kolon varlığını kontrol et
./scripts/tools/antigravity-schema-check.sh ilanlar yayin_durumu
./scripts/tools/antigravity-schema-check.sh kisiler eposta
```

### Performance Check

```bash
# N+1 query tespiti
./scripts/tools/antigravity-performance-check.sh

# Detaylı performans raporu
php artisan bekci:performance
```

---

## 🎯 Özet: İlk Gün Checklist

- [ ] `.sab/authority.json` oku
- [ ] `docs/SAB.md` oku
- [ ] `.clinerules` oku
- [ ] `docs/ARCHITECTURE_DEEP_DIVE.md` oku
- [ ] `./scripts/tools/antigravity-full-gate.sh --quick` çalıştır
- [ ] `php artisan bekci:health` çalıştır
- [ ] 10 Altın Kural'ı ezberle
- [ ] Context7 kanonik sözlüğü ezberle
- [ ] İlk görevde Antigravity araçlarını kullan

---

## 📚 Ek Kaynaklar

- **[`docs/BEKCI_CHANGELOG.md`](docs/BEKCI_CHANGELOG.md)** — Son değişiklikler
- **[`docs/PROGRESS-TRACKER.md`](docs/PROGRESS-TRACKER.md)** — Proje durumu
- **[`docs/known-debt.md`](docs/known-debt.md)** — Bilinen teknik borçlar
- **[`yalihan-bekci/knowledge/`](yalihan-bekci/knowledge/)** — AI öğrenme geçmişi

---

**Sürüm:** 1.0
**Son Güncelleme:** 2026-05-23
**Yazar:** Yalıhan AI OS (Oturum 34)
