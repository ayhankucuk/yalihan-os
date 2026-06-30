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

## 📊 MEVCUT DURUM (Oturum 33 — 2026-06-25)

| Metrik | Durum |
|--------|-------|
| SilentCatchAST ihlali | **0** ✅ |
| EnvUsageAST ihlali | **0** ✅ |
| first() orderBy eksik | **0** (194 fix + 7 aggregate/random ignore) ✅ |
| FA ikonları (tüm proje) | **0** (8 @sab-fa-intentional) ✅ |
| Route::has() Blade FQCN | **0** ✅ |
| `\DB::` backslash ihlali | **0** ✅ |
| Hardcoded admin URL (Jobs) | **0** ✅ |
| bekci:health | **91.85%** ✅ (MCP 100%, KB 100%, PH 59.25%) |
| Project Health | **59.25%** ⚠️ (Naming Authority violations) |
| Cross-IDE kurallar | ✅ |
| Premium Mediterranean UI | ✅ TAMAMLANDI |
| **AI Workspace** | ✅ OLUŞTURULDU |
| Genel ilerleme | ~99% |

**Açık riskler:**
- Project Health 59.25% — Naming Authority violations (175 dosya)
- Gate 1/3 pre-existing: `bootstrap/providers.php` env(), admin FA @sab-fa-intentional, boş route adı

**AI Workspace Yapısı:**
```
agents/     — 5 agent instruction dosyası
prompts/    — 3 prompt dosyası
knowledge/ — learning, patterns, agents
memory/    — CHANGELOG, SESSION, LEARNED, DECISIONS, WHERE, HOW
workflows/  — deploy, ci-cd
audits/     — README
```

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

---

## 🤖 KILO AGENT — ÖĞRENME & HAFIZA STRATEJİSİ

> Oturum 32+ itibarıyla Kilo Agent için bu bölüm kullanılır.

### Memory Kuralları (Otomatik Güncelleme)
**ÖNEMLİ:** Her agent oturumu memory dosyalarını günceller.
1. **Oturum başı:** `memory/PROJECT_BRAIN.md` ve `memory/WHERE_IS_WHAT.md` oku.
2. **Kod değişikliği öncesi:** Yapılacak değişikliği özetle.
3. **Her anlamlı görev sonrası:** `memory/CHANGELOG_AGENT.md` güncelle.
4. **Oturum sonu:** `memory/SESSION_NOTES.md` güncelle.
5. **Tekrarlayan hata/düzeltme bulunduğunda:** `memory/LEARNED_PATTERNS.md` güncelle.
6. **Mimari karar alındığında:** `memory/DECISIONS.md` güncelle.
7. **Her zaman:** Ne değişti, neden değişti, nasıl doğrulanır — belgele.
8. **Korunan dosyaları:** Sessizce değiştirme.

### Kimlik
- **Model:** GPT-5.2 Codex (aiwebmodel/gpt-5.2-codex)
- **Tip:** Kilo — Yalıhan Emlak AI OS özel ajan
- **Görev:** Software engineering, analiz, kod üretimi

### Memory Dosyaları

| Dosya | Ne İçin | Ne Zaman |
|-------|---------|----------|
| `memory/CHIEF_AI_VISION.md` | Chief AI vizyonu | İlk oku |
| `memory/CHANGELOG_AGENT.md` | Agent değişiklik kaydı | Her görev sonu |
| `memory/SESSION_NOTES.md` | Oturum notları | Oturum sonu |
| `memory/LEARNED_PATTERNS.md` | Tekrarlayan hatalar | Hata/düzeltme |
| `memory/DECISIONS.md` | Mimari kararlar | Karar alındığında |
| `memory/WHERE_IS_WHAT.md` | Hızlı referans haritası | Her zaman |
| `memory/HOW_IT_WORKS.md` | Sistem nasıl çalışır | Yeni geliştirici |

### Korunan Dosyalar (Değiştirilemez)

| Dosya | Sebep |
|-------|-------|
| `docs/SAB.md` | Teknik anayasa — checksum gerekli |
| `.sab/authority.json` | Governance SSOT |
| `app/Services/Ilan/IlanCrudService.php` | Tek write authority |
| `app/Services/AI/YalihanCortex.php` | AI orchestrator |

### Yeni Agent Hızlı Başlangıç

```bash
# 1. memory/PROJECT_BRAIN.md oku
# 2. memory/WHERE_IS_WHAT.md oku
# 3. agents/ klasöründen ilgili dosyayı oku
# 4. php artisan bekci:health --detailed
```

### Öğrenme Protokolü
```
1. OKUMA → Kaynak dosyaları oku (README, CLAUDE, SAB, authority.json)
2. ANALİZ → Metrics topla, yapıyı anla, sınıflandır
3. DOGRULA → Doğrulama gereken bilgileri işaretle
4. HAFIZAYA AL → todowrite + bu dosyaya yaz
5. RAPORLA → findings.md veya analiz olarak sun
```

### Doğrulama Gerektiren Bilgiler (Her Oturum Kontrol Et)
| Bilgi | Eski Değer | Doğru Değer | Kaynak |
|-------|------------|--------------|--------|
| Model sayısı | 193 | **211** | `find app/Models -name "*.php" | wc -l` |
| Service sayısı | 568 | **384** | `grep "^class.*Service" app/Services --include="*.php" | wc -l` |
| AI Service | 149 (tahmin) | **94** | `grep app/Services/AI/` |
| bekci:health | 36.85% | **59.25%** (2026-06-25) | `php artisan bekci:health --detailed` |

### Hafıza Stratejisi
| Hafıza Türü | Saklama Yöntemi |
|------------|------------------|
| Oturum içi | `todowrite` (todo listesi) |
| Kalıcı | Bu dosya (`CLAUDE.md`), `BEKCI_CHANGELOG.md`, `PROGRESS-TRACKER.md` |
| Kod yapısı | Kaynak dosyalardan sorgulama |

### Sonraki Oturum İçin İlk Kontroller
```bash
# 1. Sistem sağlığı
php artisan bekci:health --detailed

# 2. Metrics doğrula
find app/Models -name "*.php" | wc -l
grep -rh "^class.*Service" app/Services --include="*.php" | wc -l

# 3. Mimari ihlal
php artisan sab:integrity-scan

# 4. Devam eden işler için todo kontrol et
# todowrite tool'u ile mevcut durumu gör
```

### Naming Authority Hybrid Yaklaşım
```
KATEGORİ 1: Domain Model ($fillable, DB kolonları)
  → Türkçe'ye çevir (ZORUNLU)
  Yasaklı: type → tip, description → aciklama, category → kategori

KATEGORİ 2: Prompt/AI/Code Generation içerikleri
  → context7-ignore ile muaf (String literal, not DB field)

KATEGORİ 3: Laravel Framework (timestamps, relations)
  → İngilizce bırak (created_at, belongsTo)

KATEGORİ 4: Local PHP değişkenleri (camelCase)
  → context7-ignore (DB alanı değil)
```

### Devam Eden İşler (Oturum 63 — 2026-06-30)
- [ ] Sprint 3.1: Naming Authority Violation temizliği (hibrid plan)
- [x] Sprint 4.0: Reliability Hardening (Outbox, Circuit Breaker, CQRS Recovery, File Safety, Idempotent Billing) ✅
- [ ] 89 fail test → yeşile çek
- [ ] Context7 baseline reduce (4500 → 3000)
- [ ] scripts/services/ klasörü eksik (start-mcp-server.sh)
- [x] Priority 3: README standardı tamamlandı ✅
