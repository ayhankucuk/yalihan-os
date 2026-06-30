# Context7 Naming Standards

> Yalıhan Emlak — Context7 Türkçe Kanonik Alan Adları

## Domain Kavramları

Domain model `$fillable`, DB kolonları, migration'lar Türkçe olmalı:

| ❌ Yasak | ✅ Kanonik |
|---------|-----------|
| `status` | `yayin_durumu` |
| `active` | `aktiflik_durumu` |
| `is_active` | `aktiflik_durumu` |
| `is_enabled` | `aktiflik_durumu` |
| `order` | `display_order` |
| `sort_order` | `display_order` |
| `featured` | `one_cikan` |
| `featured_image` | `kapak_resmi` |
| `city` | `il` |
| `sehir` | `il` |
| `latitude` | `lat` |
| `longitude` | `lng` |
| `type` | `tip` |
| `category` | `kategori` |
| `description` | `aciklama` |
| `title` | `baslik` |
| `address` | `adres` |
| `phone` | `telefon` |
| `notes` | `notlar` |

## Framework Kavramları (Ters)

Laravel framework terimleri İngilizce:

| ❌ Yasak (Türkçe) | ✅ Kullan |
|-------------------|----------|
| `olusturma_tarihi` | `created_at` |
| `guncelleme_tarihi` | `updated_at` |
| `silme_tarihi` | `deleted_at` |
| `hatirla_token` | `remember_token` |
| `dogrulama_tarihi` | `email_verified_at` |

## camelCase Kontrolü

DB kolonları snake_case:

```php
// ❌ Yasak
$query->where('yayinTipleri', ...);

// ✅ Zorunlu
$query->where('yayin_tipleri', ...);
```

## Hybrid Yaklaşım

### Kategori 1: Domain Model ($fillable, DB kolonları)
→ Türkçe'ye çevir (zorunlu)

### Kategori 2: Prompt/AI/Code Generation içerikleri
→ `// context7-ignore` ile muaf

### Kategori 3: Laravel Framework (timestamps, relations)
→ İngilizce bırak (created_at, belongsTo)

### Kategori 4: Local PHP değişkenleri (camelCase)
→ `// context7-ignore` (DB alanı değil)
