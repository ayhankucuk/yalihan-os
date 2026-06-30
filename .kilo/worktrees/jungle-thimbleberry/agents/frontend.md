# Frontend Agent Instructions

> Yalıhan Emlak frontend geliştirme kuralları.

## Stack

- **Framework**: Alpine.js + Vite
- **CSS**: Tailwind CSS
- **Blade**: Laravel Blade template

## Layout Seçimi

| Dizin | Layout |
|-------|--------|
| `resources/views/frontend/` | `@extends('layouts.frontend')` |
| `resources/views/admin/` | `@extends('layouts.admin')` |
| `resources/views/auth/` | `@extends('layouts.guest')` |

## İkon Kütüphanesi

Font Awesome YASAK. Bileşen kullan:

```blade
<x-icon name="ev" class="w-5 h-5" />
<x-icon name="konum" class="w-4 h-4 text-blue-600" />
```

Mevcut ikonları görmek için:
```bash
grep -o "'[a-z-]*'" resources/views/components/icon.blade.php | tr -d "'"
```

## Dark Mode

Tailwind `dark:` prefix eksiksiz kullan:

```blade
dark:bg-slate-900
dark:text-slate-100
dark:border-slate-700
```

## Component Kontrolü

Kod yazmadan önce:
```bash
./scripts/tools/antigravity-component-check.sh x-icon layouts.frontend
```

## Route Kullanımı

Hardcoded URL YASAK:

```blade
{{-- ❌ YASAK --}}
<a href="/admin/ilan">

{{-- ✅ ZORUNLU --}}
<a href="{{ route('admin.ilanlar.index') }}">
```

Blade'de Route::has() kullanımı:
```blade
{{-- ❌ --}}
Route::has('ilanlar.index')

{{-- ✅ FQCN zorunlu --}}
{{\Illuminate\Support\Facades\Route::has('ilanlar.index')}}
```

## External Dependencies

Unsplash `source.unsplash.com` DEPRECATED. CSS gradient kullan.

## Premium Mediterranean Design System

- **Palette**: Navy `#0A1628` · Gold `#C9A84C` · Cream `#F8F6F1`
- **CSS vars**: `--navy`, `--gold`, `--cream` (layouts/frontend.blade.php :root)

## MIX_ Prefix

Vite env prefix kullan:

```bash
# ❌ YASAK
MIX_API_URL=

# ✅ ZORUNLU
VITE_API_URL=
```
