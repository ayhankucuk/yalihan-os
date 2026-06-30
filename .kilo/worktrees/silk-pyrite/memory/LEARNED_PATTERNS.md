# LEARNED PATTERNS — Öğrenilen Kalıplar

> Tekrarlanan hatalar, düzeltmeler ve kalıcı çözümler
> Her yeni kalıp keşfedildiğinde veya tekrarlanan bir hata çözüldüğünde güncellenir
> Format: Tarih | Kalıp ID | Açıklama | Düzeltme

---

## HIZLI REFERANS

### Naming Authority (Context7)

| Yasak | Kanonik | Nerede |
|-------|---------|--------|
| `status` | `yayin_durumu` | Domain model |
| `active` | `aktiflik_durumu` | Domain model |
| `type` | `tip` | Domain model |
| `description` | `aciklama` | Domain model |
| `order` | `display_order` | Domain model |

### Framework (Ters)

| Yasak | Kullan | Nerede |
|-------|--------|--------|
| `olusturma_tarihi` | `created_at` | Laravel timestamps |
| `guncelleme_tarihi` | `updated_at` | Laravel timestamps |

---

## TEKRARLANAN HATALAR

### LP-001: Yanlış Layout Seçimi
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** `layouts.app` frontend view'larda kullanılıyordu
**Düzeltme:** Dizin bazlı layout seçimi zorunlu
```
resources/views/frontend/ → @extends('layouts.frontend')
resources/views/admin/ → @extends('layouts.admin')
resources/views/auth/ → @extends('layouts.guest')
```
**Koruma:** antigravity-layout-check.sh

---

### LP-002: Var Olmayan Bileşen Kullanımı
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** `x-yaliihan.property-card`, `x-frontend.tag` gibi mevcut olmayan component'ler
**Düzeltme:** Kod yazmadan önce `antigravity-component-check.sh` çalıştır
**Koruma:** antigravity-component-check.sh

---

### LP-003: Route Adı Hatası
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** `danismanlar.index` yerine `frontend.danismanlar.index` gerekiyor
**Düzeltme:** `route:list` veya `antigravity-route-check.sh --check` ile doğrula
**Koruma:** antigravity-route-check.sh

---

### LP-004: Unsplash Deprecated API
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** `source.unsplash.com/random` kullanımı
**Düzeltme:** CSS gradient kullan veya harici servis bağımlılığı yaratma
**Koruma:** Kod review

---

### LP-005: FA İkon Kullanımı
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** 107 admin dosyasında Font Awesome ikon kullanımı
**Düzeltme:** `<x-icon name="..." />` bileşeni kullan
**Koruma:** FA Guard (CI)
**İstisna:** 8 dosya `@sab-fa-intentional` ile işaretli

---

### LP-006: SAB.md Checksum Unutulması
**Tarih:** 2026-05-21 (Oturum 1-31)
**Sorun:** `docs/SAB.md` değişti ama checksum yenilenmedi
**Düzeltme:** Değişiklik sonrası `scripts/tools/sab-propose.sh` çalıştır
**Koruma:** CI drift detection

---

### LP-007: Model/Service Sayıları Eski
**Tarih:** 2026-06-25 (Oturum 33)
**Sorun:** CLAUDE.md'de eski değerler (193 model, 568 service)
**Doğru:** 211 model, 384 service, 94 AI service
**Düzeltme:** Her oturum başında `find` ve `grep` ile doğrula
**Koruma:** memory/PROJECT_BRAIN.md güncel tut

---

## CONTEXT7 NAMING VIOLATION KATEGORILERI

### Kategori 1: Domain Model ($fillable, DB kolonları)
→ Türkçe'ye çevir (ZORUNLU)

### Kategori 2: Prompt/AI/Code Generation içerikleri
→ `// context7-ignore` ile muaf (String literal, not DB field)

### Kategori 3: Laravel Framework (timestamps, relations)
→ İngilizce bırak (created_at, belongsTo)

### Kategori 4: Local PHP değişkenleri (camelCase)
→ `// context7-ignore` (DB alanı değil)

---

## HIPER HIBRI D Yaklaşım

**Tanım:** Naming Authority ihlallerini otomatik kategorize et ve uygun düzeltme öner:

```
input: "type"
  → Domain model mi? → "tip" öner
  → Framework mi? → created_at kullan
  → Prompt/AI mi? → context7-ignore ekle
  → Local var mı? → context7-ignore ekle
```

---

## DÜZELTME ŞABLONLARI

### Model Alanı Değişikliği
```bash
# 1. Migration oluştur
php artisan make:migration rename_type_to_tip_in_ilan_metinler

# 2. DB kolonu rename
Schema::rename('type', 'tip');

# 3. Model $fillable güncelle
- 'type' → + 'tip'

# 4. Test çalıştır
php artisan test

# 5. Quality gate
./scripts/tools/antigravity-full-gate.sh
```

### camelCase → snake_case
```bash
# Model scope düzeltmesi
# ❌ scopeOfType
# ✅ scopeOfTip
```
