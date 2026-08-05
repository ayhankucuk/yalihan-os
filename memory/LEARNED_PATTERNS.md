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

### LP-008: Test Contract Drift — Tablo Adı Uyuşmazlığı
**Tarih:** 2026-08-05 (Oturum ~70)
**Sorun:** Test assertion'ı `property_availability` (tekil) kullanıyordu, Model/Migration/Service `property_availabilities` (çoğul) kullanıyordu
**Dosya:** `tests/Feature/ChannelManager/AvailabilitySynchronizationServiceTest.php:103`
**İkincil Sorun:** Hardcoded tarihler dinamik Carbon-based setup'tan sapmıştı
**Düzeltme:**
- Tablo adı assertion'ı `property_availabilities` olarak düzeltildi
- Tarih beklentileri `Carbon::now()->addDays(...)` ile dinamik hale getirildi
**Doğrulama:** 9/9 PASS, 30 assertions, Commit: `52620f5`
**Koruma:** Kod review (test, model ve migration birlikte doğrulanmalı)
**Reddedilen Hipotezler:**
- ❌ RefreshDatabase eksikliği
- ❌ CI bootstrap migration problemi
- ❌ property_availabilities migration eksikliği

---

## LP-008 KAPANIŞ KAYDI
**Tarih:** 2026-08-05
**Durum:** CLOSED ✅

**SAAB Teknik Kararı:**
| Kontrol | Sonuç |
|---------|-------|
| Model–migration tablo adı uyumu | ✅ PASS |
| Testlerde tablo adı uyumu | ✅ PASS |
| Tekil property_availability kalıntısı | ✅ Yok |
| Availability testleri | ✅ 15/15 |
| Ownership testleri | ✅ 12/12 |
| Toplam doğrulama | ✅ 27 test / 85 assertion |

**Kanonik tablo adı:** `property_availabilities` (çoğul)

**context window exceeds limit (2013) HATASI:**
- ❌ Uygulama problemi değil
- ❌ Migration problemi değil
- ❌ CI veritabanı problemi değil
- ✅ AI aracının bağlam limiti aşımı (token/context budget issue)
- ✅ Kod doğruluğu değerlendirmesine dahil edilmemeli

**Evidence:**
- PropertyAvailabilityTest: 15 tests, 57 assertions — PASS
- PropertyOwnershipTest: 12 tests, 28 assertions — PASS
- SQLite missing-table failure: Artık üretilemiyor
- Tekil `property_availability` referansı: Bulunamadı

---

## SAAB KURALI: Schema Uyuşmazlığı Doğrulama Zinciri

**Prensip:** Schema uyuşmazlığı şüphesinde önce canonical contract doğrulanır; test altyapısı değiştirilmez.

**Doğrulama sırası:**
```
Model ($table)
    ↓
Migration (Schema::create)
    ↓
Service (Eloquent sorguları)
    ↓
Test (assertion'lar)
    ↓
CI Bootstrap
```

**Bu yaklaşım sayesinde:**
- Gereksiz RefreshDatabase eklemelerinden kaçınılır
- CI workflow değişiklikleri önlenir
- Migration eksikliği yanlış teşhis edilmez

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

---

## LP-016: Orphan Controller / Route Debt (2026-07-04)

### Kalıp
Controller yazıldı ama route eklenmedi — endpoint asla tetiklenemez.
Bazen controller method'larının bir kısmı route'lı, bir kısmı değil.

### Örnek
```
Api/DriveWebhookController → handle() yazıldı
routes/api.php → POST /api/drive/webhook YOK
→ Google Drive webhook'ları 404 verir
```

### Oturum-Kontrol Listesi
Her yeni controller yazımında:
```bash
# 1. Controller yaz
# 2. Route'u aynı oturumda ekle
# 3. Doğrula
php artisan route:list | grep ControllerAdi
```

### Dışlama (route EKLEME, silme):
- `*Test.php` — test-only
- `*Debug*` veya `*Demo*` — dev-only
- Base class / trait — zaten controller değil

### Nasıl Bulunur
```bash
# Tüm controller'ları listele
find app/Http/Controllers -name "*Controller.php" | wc -l

# Route'sız controller'ları bul (agent ile)
# Route dosyalarındaki tüm Controller::class referanslarını çek
# Controller dosyalarını eşleştir
```
