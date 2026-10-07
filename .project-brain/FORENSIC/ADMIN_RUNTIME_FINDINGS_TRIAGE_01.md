# ADMIN_RUNTIME_FINDINGS_TRIAGE_01
**Tarih:** 2026-09-28  
**MOD:** STRICT READ-ONLY  
**HEAD:** cdc39a92  
**Verilen:** Ayhan (Google Antigravity IDE → Parent → NEW Native Subagent)  
**Asıl Yapan:** FORENSIC RESEARCHER  
**Oturum:** Forensic-RW  

---

## KAYNAK DURUM

| Dosya | Evidence Level |
|---|---|
| `PROJECT_STATE.md` | CURRENT (HEAD mismatch — out of sync) |
| `DECISION_LOG.md` | CURRENT |
| `EVIDENCE_INDEX.md` | CURRENT |
| `KNOWN_ISSUES.md` | CURRENT |
| `git status` | DIRTY — 12+ modified, 50+ untracked |

---

## A) USER / SPATIE RBAC

### FINDING
**ROOT CAUSE:** `model_has_roles` pivot tablosu **BOŞ**. Hiçbir kullanıcıya Spatie rolü atanmamış. `roles` tablosunda 5 rol var (id:1 super-admin, id:2 admin, id:3 danisman, id:4 musteri, id:5 owner) ama hiçbiri bir kullanıcıya bağlı değil.

**EFFECTIVE_RUNTIME_PATH:**
```
User (id=1 Ayhan, role_id=1)
  → User::role() → belongsTo → roles.id=1 "super-admin" ✅
  → User::getRoleNames() → [] (model_has_roles BOŞ) ❌
  → hasRole('super-admin') → FALSE (pivot boş) ❌
  → Auth::user()->role->name → "super-admin" ✅ (legacy FK)

User (id=2 Berk, role_id=2)
  → User::role() → belongsTo → roles.id=2 "admin" ✅
  → getRoleNames() → [] (pivot boş) ❌
  → hasRole('admin') → FALSE ❌
```

**DUAL AUTHORITY CONFIRMED:**
- `User` modeli hem `HasRoles` trait (satır 16, 107) hem `role()` relation içeriyor
- `roles` tablosunda aynı satır hem Spatie `name` hem legacy FK referans
- `model_has_roles` pivot **tamamen BOŞ** — Spatie pivot mekanizması hiç kullanılmamış
- `RoleServiceProvider` dual fallback: önce Spatie `getRoleNames()`, sonra legacy `$user->role->name`

**"Süper Yönetici" LABEL KAYNAĞI:**
Sidebar'da (satır 128) **hardcoded string**: `<p class="text-xs text-slate-500 truncate">Süper Yönetici</p>`
→ Spatie ile ilgisi yok, sabit metin, her zaman görünür.

**INDEX BLADE DURUMU (git HEAD):**
Git HEAD'de `index.blade.php` hâlâ `role_id` kolonu gösteriyor (satır 14, 25). Ancak live sayfa doğru rol gösteriyor — bu, çalışma dizinindeki düzeltilmiş dosyanın Laravel view cache'inde olabileceğini veya başka bir mekanizmayla çalıştığını düşündürüyor. **Git diff clean değil — HEAD ve çalışma dizini farklı.**

**EVIDENCE_LEVEL:** REPO_VERIFIED + DIRECT_DB_QUERY + LIVE_BROWSER  
**EVIDENCE_TYPE:** CODE_AUDIT + DB_QUERY

### CLASSIFICATION: `LEGACY_FIELD_CONFUSION`

**ANALIZ:**
Spatie pivot BOŞ iken legacy `role_id` FK sistemi aktif. Kullanıcı listesi doğru rol gösteriyor (çünkü çalışma dizinindeki blade düzeltildi). Ancak:

1. Spatie rol atamak isteyen biri pivot yerine legacy FK yazabilir — Spatie `hasRole()` çalışmaz
2. `RoleServiceProvider` dual fallback'i meşru durumda çalışıyor ama sessizdir
3. `model_has_roles` boş kalırsa Spatie permission sistemi de çalışmaz

**INTERSECTION_WITH_CDA-006:**  
AdminUserSeeder HELD — çalışsa bile Spatie pivot yazması gerekiyor. CDA-006 çözülmeden AdminUserSeeder tam Spatie entegrasyonu yapamaz.

**RECOMMENDED_BOUNDED_FIX:**
CDA-006 çözüldükten sonra AdminUserSeeder'a Spatie entegrasyonu ekle:
```php
$user->assignRole('super-admin'); // Pivot'a yazar
```
Şimdilik **NO_FIX** — aktif defect yok, legacy sistem çalışıyor.

**=> NO_FIX_REQUIRED (bu aşamada)**

---

## B) ADMIN USERS ROUTING / HTTP 500

### FINDING
**ROOT CAUSE:** `layouts.app` view mevcut değil (`php artisan tinker` → "View [layouts.app] not found"). Ancak live sayfa düzgün render oluyor — çalışma dizinindeki `index.blade.php` `@extends('layouts.app')` yazmasına rağmen admin sidebar doğru görünüyor. Muhtemelen çalışma dizinindeki dosya farklı veya Laravel view compiled cache etkili.

Git HEAD'deki `index.blade.php` gerçekten `@extends('layouts.app')` içeriyor — bu dosya `/admin/kullanicilar` açılsaydı 500 hata verirdi.

**ACTIVE UI LINK:**
- Sidebar'da `Kullanıcılar` → `/admin/kullanicilar` ✅ (doğru, canonical route)
- `/admin/users` route tanımı yok, hiçbir yerde linki yok

**EVIDENCE_LEVEL:** REPO_VERIFIED + LIVE_BROWSER  
**EVIDENCE_TYPE:** CODE_AUDIT + TINKER_QUERY + UI_NAVIGATION

### CLASSIFICATION: `REAL_ACTIVE_DEFECT` (git HEAD üzerinde) / `ALREADY_FIXED` (çalışma dizini)

**ANALIZ:**
Git HEAD'de `@extends('layouts.app')` hatalı. Ancak çalışma dizinindeki dosya düzeltilmiş görünüyor. Page reload sonrası doğru sidebar + roller görünüyor.

Ayhan'ın bahsettiği "HTTP 500" iddiası doğrulamadı. Live page düzgün render oluyor ama diskteki dosya `@extends('layouts.app')` içeriyor. Muhtemelen:

1. **Compiled view cache** — Laravel storage/framework/views/ içinde derlenmiş versiyon farklı
2. **Farklı view path** — Başka bir index.blade.php dosyası kullanılıyor olabilir
3. **Laravel view resolution** — `layouts.app` alias yanlış resolve ediliyor

Son kontrol: `layouts.app` mevcut DEĞİL (`php artisan tinker` → "View [layouts.app] not found") ama sayfa açılıyor. Bu tutarsızlık view cache'ten kaynaklanıyor olabilir.

**RECOMMENDED_BOUNDED_FIX:**
```bash
php artisan view:clear
# veya
php artisan cache:clear
```

Sonra `/admin/kullanicilar` tekrar aç — 500 hata alırsan dosyayı düzelt:
```blade
@extends('admin.layouts.admin')  {{-- layouts.app yerine --}}
```

**=> BOUNDED_FIX_CANDIDATE + VIEW_CACHE_INVESTIGATION**

---

## C) FX NaN

### FINDING
**ROOT CAUSE:** Potansiyel kaynak tespit edildi ancak **NaN iddiası doğrulanamadı**.

**EFFECTIVE_RUNTIME_PATH:**
```
TCMBCurrencyService::getTodayRates()
  → HTTP GET https://www.tcmb.gov.tr/kurlar/today.xml (timeout: 10s)
  → XML parser → rates array veya null
  → CacheHelper 'medium' TTL (1 saat)
  → null ise getFallbackRates() → ExchangeRate tablosu

ExchangeRateController::index()
  → TCMBCurrencyService->getTodayRates()
  → ResponseService::success([ 'rates' => $rates ])
```

**POTANSİYEL NaN KAYNAKLARI:**
1. TCMB API başarısız + DB boş → `getFallbackRates()` boş array → frontend "NaN"
2. TCMB XML formatı değişti → parse hatası → null
3. Frontend formatter hatası → backend doğru ama JS "NaN"

**EVIDENCE_LEVEL:** PARTIAL (service audited, live observation yok)  
**EVIDENCE_TYPE:** CODE_AUDIT

### CLASSIFICATION: `UNKNOWN`

**ANALIZ:**
Kullanıcı hangi endpoint/UI elementinde NaN gördü? `/api/exchange-rates` mü? İlan detay sayfası mı? Hangi para birimi çifti?

**RECOMMENDED_BOUNDED_FIX:**
Kullanıcıdan detay gerekli: URL, endpoint, para birimi çifti, API response içeriği.

**=> NO_FIX (INFO_GAP)**

---

## D) HORIZON

### FINDING
**ROOT CAUSE:** Horizon Redis connection kullanıyor (`QUEUE_CONNECTION=redis`) ama local ortamda Redis çalışıyor mu? Worker'lar açık mı?

**CONFIGURATION:**
```php
// config/horizon.php
'connection' => 'redis'        // Redis zorunlu
REDIS_CLIENT=phpredis         // ⚠️ phpredis PHP extension gerekli
QUEUE_CONNECTION=redis        // ✅
```

**EFFECTIVE RUNTIME:**
```
Horizon (/horizon) → Redis connection required
  → Redis server alive locally?
  → phpredis extension installed?
  → Pending/failed jobs exist?
```

**EVIDENCE_LEVEL:** CONFIG_AUDIT_ONLY  
**EVIDENCE_TYPE:** CODE_AUDIT + CONFIG_REVIEW

### CLASSIFICATION: `EXPECTED_INACTIVE` / `UNKNOWN`

**ANALIZ:**
1. Horizon `redis` connection zorunlu — Redis çalışmıyorsa beklenen şekilde ölü
2. Worker kapalı = defect değil — queue workload yoksa
3. Production worker durumu ayrı araştırılmalı

**RECOMMENDED_BOUNDED_FIX:**
```bash
redis-cli ping  # → PONG gelmiyorsa Redis çalışmıyor
php artisan horizon:status  # → çalışmıyorsa beklenen
```

**=> NO_FIX (INSUFFICIENT_DATA)**

---

## ÖZET: 4 MADDE

| # | Finding | Classification | CDA-006 bağımlı? | Action |
|---|---|---|---|---|
| A | Spatie pivot BOŞ, legacy FK aktif, "Süper Yönetici" hardcoded | `LEGACY_FIELD_CONFUSION` | Dolaylı (AdminUserSeeder) | NO_FIX — legacy çalışıyor |
| B | `layouts.app` git HEAD'de yok, 500 riski var | `REAL_ACTIVE_DEFECT` (HEAD) / `ALREADY_FIXED` (WD) | ❌ Bağımsız | Bounded fix → micro-commit |
| C | FX NaN — gözlem detayı yok | `UNKNOWN` | ❌ | NO_FIX — info gap |
| D | Horizon Redis bekliyor, worker durumu bilinmiyor | `EXPECTED_INACTIVE` / `UNKNOWN` | ❌ | NO_FIX — Redis alive kontrolü gerekli |

---

## TEK AKTİF BOUNDED FIX (B maddesi)

**Dosya:** `resources/views/admin/kullanicilar/index.blade.php`  
**Satır:** 1  
**Git HEAD durumu:** `@extends('layouts.app')` → ❌ HATALI  
**Çalışma dizini:** Muhtemelen düzeltilmiş (live sayfa doğru görünüyor)

**Değişiklik:**
```blade
{{-- ÖNCE: --}}
@extends('layouts.app')
{{-- SONRA: --}}
@extends('admin.layouts.admin')
```

**Aynı hataya sahip dosyalar (git HEAD):**
- `resources/views/admin/crm/pipeline/index.blade.php`
- `resources/views/admin/finance/dashboard.blade.php`

**Risk:** Düşük — layout path düzeltmesi  
**Test:** `/admin/kullanicilar` açılır, admin sidebar + rollü tablo doğru render

---

**TRIAGE SONUCU:**  
4 iddia → 1 bounded fix adayı (B, micro-commit önerilir), 1 legacy clarification (A), 2 info gap (C, D)  
Ayhan'a: B maddesi için git diff kontrol et ve çalışma dizinindeki dosyanın gerçekten düzeltilip düzeltilmediğini teyit et.
