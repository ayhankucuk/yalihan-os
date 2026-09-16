---
name: core-engineering-guard
description: Yalıhan OS için kanıt-temelli mimari, Laravel kod kalitesi ve release hazırlığı. yalihan-os-architect + laravel-enterprise-reviewer birleşimi.
---

# Core Engineering Guard

Yalıhan OS üzerinde çalışan agent'ın mimari sınırları koruyarak ilerlemesini sağlar. Kod, test, Git ve production kanıtlarını ayrı tutar.

---

## Çalışma Sırası

1. `.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarını oku
2. Görev kapsamını ve etkilenecek dosyaları belirle
3. Kaynak kodu ve testleri incele; varsayımları `INFERRED`/`UNKNOWN` olarak işaretle
4. Değişiklik öncesi etki, güvenlik ve rollback riskini değerlendir
5. Minimum diff uygula
6. Testleri, kalite kapılarını ve gerekiyorsa browser/HTTP akışını doğrula
7. `PROJECT_STATE.md`, `EVIDENCE_INDEX.md`, `KNOWN_ISSUES.md` güncelle

---

## Mimari Değişmez Kurallar

### Yetki Zinciri
- Tenant izolasyonu her sorgu ve yazma işleminde korunur
- Yazma zinciri: `Controller → Service → IlanCrudService → Repository → DB`
- Controller içinde doğrudan Eloquent `create`/`update`/`delete` **YASAK**
- Hermes yalnızca olay koordinasyonu; LLM çağırmaz
- AI önerileri açıklanabilir provenance taşımalı

### Laravel Kod Kalitesi
- `->first()` mutlaka `->orderBy('id')` içermeli (determinism)
- `env()` app/ içinde **YASAK** → `config()` kullan
- Boş `catch` bloğu **YASAK** → log + rethrow zorunlu
- Mass assignment: `$request->all()` değil `$request->validated()` kullan
- Query'lerde N+1 kontrolü: `->with()` eager loading zorunlu

### Context7 Kanonik Alan Adları
| Yasak | Kullanılacak |
|-------|-------------|
| `status` | `yayin_durumu` |
| `active`, `is_active` | `aktiflik_durumu` |
| `order`, `sort_order` | `display_order` |
| `featured` | `one_cikan` |
| `featured_image` | `kapak_resmi` |
| `city`, `sehir` | `il` / `il_adi` |
| `property_type` | `ana_kategori_id` (FK) |

### Frontend Kuralları
- FontAwesome (`fa-`, `fas`, `fab`) **KESİN YASAK** → `<x-icon name="..." />` kullan
- `@extends` dizin kuralı: `frontend/` → `layouts.frontend`, `admin/` → `layouts.admin`, `auth/` → `layouts.guest`
- Hardcoded URL **YASAK** → `route()` veya `config()` kullan
- Vite: `MIX_` prefix **YASAK** → `VITE_` kullan

---

## Kanıt Standardı

| Seviye | Anlamı |
|--------|--------|
| `REPO_VERIFIED` | Kod/AST düzeyinde doğrulandı |
| `TEST_VERIFIED` | Test çıktısı ile doğrulandı |
| `PRODUCTION_VERIFIED` | Canlı HTTP/browser/SSH kanıtı ile doğrulandı |
| `DOCUMENTED` | Dokümanda var, uygulama kanıtı ayrı gerekir |
| `INFERRED` | Koddan çıkarım |
| `UNKNOWN` | Henüz doğrulanmadı |

> PHPUnit geçmesi = `TEST_VERIFIED`. `TEST_VERIFIED` ≠ `PRODUCTION_VERIFIED`

---

## Thin Controller Kontrolü

Her controller değişikliğinde kontrol et:

```php
// ❌ YASAK — Controller'da iş mantığı
public function store(Request $request) {
    $ilan = Ilan::create($request->all());  // Mass assignment riski
    // ... Eloquent builder chaining
}

// ✅ ZORUNLU — Controller sadece validate + delegate
public function store(StoreIlanRequest $request): JsonResponse {
    $ilan = $this->ilanService->create($request->validated());
    return response()->json($ilan, 201);
}
```

---

## Release Sınırları

Commit, deploy, migration, seed ve container restart için **kullanıcıdan açık yetki gerekir**.

Yoksa: sadece local analiz, test, aday diff ve read-only production incelemesi yapılır.

Sırlar, tokenlar, ham hassas loglar rapora yazılmaz.

---

## Önerilen Doğrulama Komutları

```bash
# Mutlak minimum (her görev sonunda)
git diff --check
./scripts/tools/antigravity-preflight.sh
./scripts/tools/antigravity-full-gate.sh --quick

# Tam kontrol (artisan dahil)
php artisan sab:integrity-scan
php artisan bekci:health
php artisan bekci:audit --all
```

---

## Handoff Paketi Formatı

Her görev tamamlandığında veya devredildiğinde:

```markdown
## HANDOFF — [Görev Adı]

**Tarih:** ISO 8601
**Commit:** [hash]

### Değişen Dosyalar
- [dosya yolu]

### Çalıştırılan Komutlar
- [komut]

### Test Sonuçları
- [test]: PASS | FAIL

### Kanıt Seviyesi
[REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED | ...]

### Bilinen Riskler
- [risk] — [olasılık] — [etki]

### Tamamlanmayan İş
- [madde] — neden bekletildi?

### Sonraki Adım
- [ne yapılmalı] — kime?
```
