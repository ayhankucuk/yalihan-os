# Yalıhan AI OS — Claude Oturum Kılavuzu

> Bu dosya her Claude Code oturumunda otomatik okunur.
> Projeye yeni başlayan Claude, bu dosyayı okuyarak 20+ oturumluk bağlamı anında kazanır.

---

## 🏗️ Proje Kimliği

**Yalıhan Emlak** — Bodrum merkezli lüks gayrimenkul portföyü + AI destekli ilan yönetim sistemi.

- **Stack**: Laravel 10 / PHP 8.2+ / MySQL (prod) + SQLite (test) / Tailwind CSS + Alpine.js + Vite
- **DB**: `yalihanai_test` (ana) + `yalihan_market` (market intelligence)
- **Mimarisi**: Modular Monolith — `App\Domain\`, `App\Domains\`, `App\Modules\`, `App\Services\`
- **AI**: Cortex Orchestrator (Ollama, DeepSeek, OpenAI) + YalihanCortex pipeline
- **193 model**, **568 servis**, **195+ route**

---

## ⚡ İLK YAPILACAKLAR (Yeni Oturumda)

```bash
# 1. Sistem sağlığını kontrol et
php artisan bekci:health --detailed

# 2. Mimari ihlal var mı bak
php artisan sab:integrity-scan

# 3. Değişiklik yapacaksan önce gate çalıştır
./scripts/tools/antigravity-full-gate.sh --quick
```

---

## 🛡️ DEĞİŞMEZ KURALLAR (SAB Anayasası)

### Yazma Otoritesi
```
Controller → Service → IlanCrudService → Repository → DB
```
Controller'da **asla** `Eloquent::create/update/delete` yok. Sadece `IlanCrudService` yazar.

### Tenant Isolation (Kural 1 — En Ağır İhlal)
Cross-tenant veri erişimi kesinlikle yasak. Her query tenant scope içermeli.

### Thin Controller
```php
// ❌ YASAK
public function store(Request $request) {
    Ilan::create($request->all()); // Controller'da ORM write
}
// ✅ ZORUNLU
public function store(StoreIlanRequest $request) {
    return $this->ilanCrudService->create($request->validated());
}
```

### Context7 Türkçe Kanonik Alan Adları
| ❌ Yasak | ✅ Kanonik |
|---------|-----------|
| `status` | `yayin_durumu` |
| `active` / `is_active` | `aktiflik_durumu` |
| `order` / `sort_order` | `display_order` |
| `featured` | `one_cikan` |
| `featured_image` | `kapak_resmi` |
| `city` / `sehir` | `il` / `il_adi` |
| `latitude` / `longitude` | `lat` / `lng` |

Bypass: `$data['status'] = $x; // context7-ignore`

---

## 🚫 KESİN YASAKLAR

| Yasak | Doğrusu |
|-------|---------|
| `fa-`, `fas`, `fab` (Font Awesome) | `<x-icon name="..." />` — kütüphane: `resources/views/components/icon.blade.php` |
| `env()` — `app/` içinde | `config('key')` veya `app()->environment()` |
| Boş/sessiz catch bloğu | Log + rethrow veya `/** @sab-ignore-catch */` |
| `Route::has()` blade'de | `\Illuminate\Support\Facades\Route::has()` — FQCN zorunlu |
| `->first()` orderBy'sız | `->orderBy('id')->first()` — determinism |
| Hardcoded URL string | `route('name')` kullan |
| `MIX_` env prefix | `VITE_` kullan |
| `\DB::` backslash | `use DB; ... DB::` ile import et |

---

## 📁 KRİTİK DOSYALAR

| Dosya | Ne İşe Yarar |
|-------|-------------|
| `.sab/authority.json` | Governance SSOT — kural çakışmasında referans |
| `.sab/ONBOARDING_AGENTS.md` | Tüm agent kuralları ve scripts/tools kataloğu |
| `docs/SAB.md` | Teknik anayasa — değişiklik checksum gerektiriyor |
| `CONTRIBUTING.md` | Geliştirme döngüsü, determinism, write zinciri |
| `.cursorrules` | Cursor IDE kuralları |
| `.clinerules` | Cline IDE kuralları |
| `.roomodes` | Roo Code uzman modları (5 ajan) |
| `docs/BEKCI_CHANGELOG.md` | Oturum başına güncellenmeli (SAB Rule 7) |
| `docs/PROGRESS-TRACKER.md` | Proje durumu — her oturum sonunda güncellenmeli |

---

## 🧰 ANTİGRAVİTY ARAÇLARI

Kod yazmadan önce çalıştır:

```bash
# Bileşen var mı?
./scripts/tools/antigravity-component-check.sh x-icon layouts.frontend

# Route var mı?
./scripts/tools/antigravity-route-check.sh --check ilanlar.index
./scripts/tools/antigravity-route-check.sh --duplicates

# DB kolonu var mı?
./scripts/tools/antigravity-schema-check.sh ilanlar yayin_durumu

# Layout doğru mu?
./scripts/tools/antigravity-layout-check.sh

# Tüm kontroller (master)
./scripts/tools/antigravity-full-gate.sh
```

---

## 🏠 FRONTEND MİMARİSİ

### Layout Seçimi
- `resources/views/frontend/` → `@extends('layouts.frontend')`
- `resources/views/admin/` → `@extends('layouts.admin')`
- `resources/views/auth/` → `@extends('layouts.guest')`

### İkon Kütüphanesi
```blade
<x-icon name="ev" class="w-5 h-5" />
<x-icon name="konum" class="w-4 h-4 text-blue-600" />
```
Mevcut ikonları görmek için:
```bash
grep -o "'[a-z-]*'" resources/views/components/icon.blade.php | tr -d "'"
```

### Dark Mode
Tailwind `dark:` prefix eksiksiz. `dark:bg-slate-900`, `dark:text-slate-100` standart.

---

## 🔄 GÖREV TAMAMLAMA PROTOKOLÜ

Her değişiklik sonrası:
```bash
./scripts/tools/antigravity-full-gate.sh   # Gate kontrol
php artisan sab:integrity-scan              # Yeni ihlal yok mu?
```

Sonra `docs/BEKCI_CHANGELOG.md`'ye oturum kaydı ekle (SAB Rule 7).

---

## 📊 MEVCUT DURUM (Oturum 31 sonu — 2026-05-21)

| Metrik | Durum |
|--------|-------|
| SilentCatchAST ihlali | **0** ✅ |
| EnvUsageAST ihlali | **0** ✅ |
| first() orderBy eksik | **0** (194 fix + 7 aggregate/random ignore) ✅ |
| FA ikonları (tüm proje) | **0** (8 @sab-fa-intentional) ✅ |
| Route::has() Blade FQCN | **0** ✅ |
| `\DB::` backslash ihlali | **0** ✅ |
| Hardcoded admin URL (Jobs) | **0** ✅ |
| bekci:health | ~36.85% (Oturum 24.2 ölçümü) |
| Cross-IDE kurallar | `.cursorrules` + `.clinerules` + `.roomodes` ✅ |
| Premium Mediterranean UI | ✅ TAMAMLANDI (navy+gold+cream) |
| Genel ilerleme | ~99% |

**Açık riskler:**
- bekci:health %36.85 — legacy technical debt (canlı sunucu ölçümü gerekli)
- Gate 1/3 pre-existing: `bootstrap/providers.php` env(), admin FA @sab-fa-intentional, boş route adı

### 🎨 Premium Mediterranean Design System
- **Palette:** Navy `#0A1628` · Gold `#C9A84C` · Cream `#F8F6F1` · Cream-text `#F5F0E8`
- **CSS vars:** `--navy`, `--navy-mid`, `--navy-light`, `--gold`, `--gold-light`, `--gold-dim`, `--cream`, `--cream-text` (`layouts/frontend.blade.php` `:root` bloğu)
- **Dosyalar:** `layouts/frontend.blade.php` (nav+footer) + `yaliihan-home-clean.blade.php` (6 section)

---

## 🧠 GEÇMİŞ OTURUMLARDAN ÖĞRENILENLER

1. **Var olmayan bileşen kullanma** — `x-yaliihan.property-card`, `x-frontend.tag` gibi mevcut olmayan component'ler yazıldı. Her component kullanımından önce `antigravity-component-check.sh` çalıştır.

2. **Yanlış layout** — `layouts.app` frontend view'larında kullanıldı. Dizine göre layout seç.

3. **Unsplash deprecated API** — `source.unsplash.com/random` kullanıldı. Dış servis bağımlılığı yaratma; CSS gradient tercih et.

4. **Route adı hatası** — `danismanlar.index` yerine `frontend.danismanlar.index`. Kullanmadan önce `route:list` veya `antigravity-route-check.sh --check` ile doğrula.

5. **SAB.md checksum** — `docs/SAB.md` değiştirildi ama `scripts/tools/sab-propose.sh` çalıştırılmadı. Anayasa değişikliği = checksum yenileme zorunlu.

6. **FA Guard** — 107 admin dosyasında FA temizliği yapıldı. Yeni dosyaya FA ekleme — hiçbir koşulda.
