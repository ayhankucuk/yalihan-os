# Contributing Guide — Yalıhan Emlak

Bu belge, yeni geliştirme kurallarını ve zorunlu standartları tanımlar.
Kodun kabul edilmesi için bu kurallara uyum zorunludur.

Kural çakışması durumunda öncelik sırası:

1. `.sab/authority.json` (Canonical SSOT)
2. `docs/SAB.md` (Teknik Anayasa ve Mühür Kuralları)
3. `docs/DAP_CORE.md` (Otopilot ve Karar Matrisi)
4. `CONTRIBUTING.md` (Uygulama ve Geliştirme Pratiği)
5. `docs/index.md` (Dokümantasyon Giriş Noktası)

---

## Geliştirme Döngüsü

```
1. php artisan db:table [tablo]   # Şemayı OKUMADAN sorgu yazılmaz
2. Test yaz (Context7 uyumlu)
3. Testi FAIL et
4. Minimal kodu yaz
5. Testi PASS et
6. php artisan sab:integrity-scan
7. php artisan bekci:audit --all      # Cognitive semantic audit (AST) 🧠
8. php artisan system:env-drift-guard  # Schema & env governance
9. ./scripts/guards/quality-gate.sh   # exit 0 zorunlu
```

---

## Determinism Standardı {#determinism}

### Kural: Her `first()` çağrısı deterministik olmalıdır

```php
// ❌ YASAK — non-deterministic
$template = UpsTemplate::where('aktiflik_durumu', 1)->first();

// ✅ ZORUNLU — explicit orderBy + tie-break
$template = UpsTemplate::where('aktiflik_durumu', 1)->orderBy('id')->first();
```

### Gerekçe

`first()` üzerinde `ORDER BY` yoksa:

- MySQL ve SQLite farklı satır döndürebilir
- Aynı veritabanında index'e göre sonuç değişebilir
- Test geçer, production patlayabilir

### Slug primary selector olamaz

```php
// ❌ YASAK
$type = YayinTipiSablonu::where('slug', $slug)->first();

// ✅ ZORUNLU
$type = YayinTipiSablonu::where('slug', $slug)
    ->where('aktiflik_durumu', true)
    ->orderBy('id')
    ->first();
```

### Junction-First Template Resolution

Template çözümlemesi daima junction üzerinden yapılır, doğrudan slug lookup değil:

```php
// ❌ YASAK
$template = UpsTemplate::where('yayin_tipi_id', $typeId)->first();

// ✅ ZORUNLU
$template = $resolver->resolveByJunction($junctionId, $kategoriId);
```

---

## Field Contract Standardı

### Kural: DB tipi ile uygulama tipi aynı olmalıdır

```php
// ❌ YASAK — tinyint kolonuna string yazmak
$lead->crm_durumu = 'new';

// ✅ ZORUNLU — int sabiti kullan
$lead->crm_durumu = Lead::CRM_NEW; // 0
```

### Renamed kolon — eski ad kullanılamaz

```php
// ❌ YASAK — 2026_02_10 migration ile rename edildi
AiLog::create(['status_code' => 200]);

// ✅ ZORUNLU
AiLog::create(['aktiflik_kodu' => 200]);
```

### Context7 Forbidden Fields

Şu adlar **asla** kullanılamaz:

| Yasak                                      | Canonical                                                                      |
| ------------------------------------------ | ------------------------------------------------------------------------------ |
| `s.t.a.t.u.s`                              | `yayin_durumu` (ilanlar), `talep_durumu` (talepler), `aktiflik_durumu` (genel) |
| `a.c.t.i.v.e`, `is_active`, `aktif`        | `aktiflik_durumu`                                                              |
| `o.r.d.e.r`, `sort_o.r.d.e.r`              | `display_order`                                                                |
| `featured`, `is_featured`                  | `one_cikan`                                                                    |
| `featured_image`                           | `kapak_resmi`                                                                  |
| `latitude`, `longitude`, `enlem`, `boylam` | `lat`, `lng`                                                                   |
| `city`, `sehir`                            | `il` / `il_adi`                                                                |
| `status_code` (ai_logs)                    | `aktiflik_kodu`                                                                |

Not: Tam ve güncel eşleştirme listesi için daima `.sab/authority.json` baz alınır.

---

## Write Zinciri Kuralları

Her veri yazımı şu zinciri tamamlamalıdır:

```
write() → Observer/Event → Cache Invalidate → Changelog (varsa)
```

- Observer içinde exception catch → log → rethrow
- Cache invalidation atlanamaz
- Changelog write failure → warning log, exception swallow YASAK

---

## Ghost Migration Kuralı

Aynı tablo hem aktif hem `.disabled` migration setinde **bulunamaz**.

- Yeni tablo oluşturuyorsanız: eski `.disabled` versiyonu `database/migrations/` dışına taşı
- Canonical set: `.sab/authority.json#migration_canonicalization`
- CI Guard: `scripts/guards/ci-guard-ghost-migration.sh`

---

## Polymorphic Relation Kuralı

```php
// FeatureAssignment oluştururken:
// ✅ assignable_type → ALLOWED_ASSIGNABLE_TYPES içinde olmalı
// ✅ assignable_id → hedef modelde exists() = true olmalı
// ✅ Observer zaten guard eder — ek kontrol gerekmezse de test yaz
```

Yeni bir `assignable_type` ekliyorsanız:

1. `FeatureAssignmentObserver::ALLOWED_ASSIGNABLE_TYPES` listesine ekle
2. `.sab/authority.json#polymorphic_allowlist` güncelle
3. `./scripts/guards/quality-gate.sh` çalıştır

---

## Telemetry Field Kuralı

| ❌ Yasak           | ✅ Canonical      |
| ------------------ | ----------------- |
| `http_status_code` | `http_durum_kodu` |
| `ok`, `success`    | `basarili`        |
| `url`              | `istek_url`       |
| `error`            | `hata_mesaji`     |
| `status_code`      | `durum_kodu`      |

---

## Bilişsel Denetim (Cognitive Guardian)

Yalıhan Bekçi v2.1, kodu anlamsal (semantic) olarak denetler.

### 1. AST (Abstract Syntax Tree) Analizi
- Kodunuz sadece metin olarak değil, yapısal olarak taranır.
- Boş olmayan ama hatayı "yutan" (swallow) catch blokları anında reddedilir.
- Controller içinde doğrudan `env()` veya `$_ENV` kullanımı yasaktır (config üzerinden erişin).

### 2. Hibrit İsimlendirme (Hybrid Naming)
- **Domain Katmanı**: Türkçe olmak zorundadır (`fiyat`, `baslik`, `aciklama`).
- **Framework Katmanı**: İngilizce kalmalıdır (`Controller`, `Service`, `Middleware`).
- Bekçi, bu dengeyi AST seviyesinde denetler.

### 3. Yaşayan Bellek (Living Memory)
- Bir mimari ihlal tespit edildiğinde, `php artisan bekci:pattern:learn` ile sistem bu hatayı öğrenir.
- Öğrenilen hataların tekrarı (regression) build'i kalıcı olarak bloklar.

---

## Commit Kuralları

```
feat(kapsam): açıklama
fix(kapsam): açıklama
docs(kapsam): açıklama
refactor(kapsam): açıklama
```

- Her commit öncesi: `./scripts/guards/quality-gate.sh` (pre-commit hook otomatik çalışır)
- Governance PASS zorunludur

---

## Yasak Pratikler

| Pratik                            | Neden Yasak                                           |
| --------------------------------- | ----------------------------------------------------- |
| `Model::create($request->all())`  | Mass assignment riski; `$request->validated()` kullan |
| `DB::transaction()` controller'da | TX boundary sadece Service katmanında                 |
| `\DB::` `\Log::` backslash facade | `use` ile import et                                   |
| Silent catch (exception swallow)  | Her catch log + rethrow içermeli                      |
| `->first()` orderBy'sız           | Non-deterministic — production'da farklı sonuç        |
| Canonical dışı alan adı kullanımı | Context7 ihlali — eşleştirmeler authority dosyasından |
