# 🤖 Yalıhan AI OS — Yapay Zeka Ajanları için Onboarding ve Yetenek Ordusu Kılavuzu (.sab/ONBOARDING_AGENTS.md)

Bu belge, **Antigravity**, **Cursor**, **Roo Code**, **Cline** ve diğer otonom yapay zeka ajanlarının Yalıhan projesinde çalışırken uymak zorunda olduğu teknik, mimari, görsel ve operasyonel kuralları ve dizindeki yerleşik 80'den fazla yardımcı betiğin (scripts/tools) kullanım kılavuzunu içerir.

---

## 🏛️ 1. GENEL AJAN DAVRANIŞ İLKELERİ VE OTORİTE SIRALAMASI
Projeye dahil olan her yapay zeka ajanı, kararlarını ve kod yazımını aşağıdaki değişmez öncelik sırasına (Authority Order) göre almak zorundadır:

1. **İnsan (Kullanıcı / Geliştirici)** → Mutlak ve nihai otoritedir.
2. **Canlı Kod ve DB Şeması (Runtime Truth)** → `DESCRIBE` veya çalışan kodun gerçek durumu.
3. **`.sab/authority.json` (SSOT)** → Governance (Yönetişim) ve kural veri kaynağı.
4. **Yalıhan Bekçi / SAB Muhafızları (AST Denetimleri)** → CI/CD blocker'lar ve statik kod koruması.
5. **Referans Dokümanlar** (`docs/SAB.md`, `CLAUDE_MEMORY.md`, bu dosya) → Yol gösterici kılavuzlar.

---

## 🧱 2. FRONT-END VE BACK-END MİMARİ KARAKTERİSTİKLERİ

Ajanlar, Yalıhan mimarisini geliştirirken şu karakteristikleri bilmeli ve korumalıdır:

### A. Back-End Mimarisi (Laravel 11 / PHP 8.2+)
*   **Modular Monolith Yapısı**: Kod tabanı `App\Modules\` ve `App\Domain\` altındaki modüller halinde düzenlenmiştir (örneğin: `GovernanceCore`, `Emlak`, `Talep`).
*   **Thin Controller (Zayıf Kontrolör) Kuralı**: Controller sınıfları asla iş mantığı içermez. Sadece HTTP isteklerini doğrular, girdileri hazırlar ve ilgili Service katmanına delege eder.
*   **Yazma Otoritesi (Write Authority)**: Veritabanına doğrudan yazma (Controller içinde Eloquent `create()`, `update()`, `insert()` veya raw SQL kullanımı) kesinlikle **YASAKTIR**.
    *   İlanlar için tek yetkili yazma kapısı **`IlanCrudService`** sınıfıdır.
    *   Tüm Eloquent modelleri ham `Model` sınıfı yerine `App\Models\BaseModel` sınıfını genişletmelidir.
*   **Facade Backslash Yasağı**:
    *   ❌ `\DB::table('ilanlar')` veya `\Cache::get()` gibi çağırmayın.
    *   ✅ Sınıfın başında `use Illuminate\Support\Facades\DB;` import edip `DB::table(...)` olarak çağırın.

*   **Blade Görünüm Güvenliği (Blade Facade Rule) [YENİ KODLAR İÇİN ZORUNLU / PHASE 5 HEDEFİ]**: Blade şablonlarında facade çağırmak gerektiğinde tam nitelikli sınıf adı (FQCN) kullanılmalıdır. Aksi halde izole ortamlarda derleme yaparken "Class not found" 500 hataları alınabilir. Projede eski şablonlardan kalma `Route::has()` gibi doğrudan kullanımlar yer alabilir; yeni yazılan veya düzenlenen dosyalarda bunları FQCN ile değiştirmelisiniz.
    *   ❌ `@if (Route::has('login'))`
    *   ✅ `@if (\Illuminate\Support\Facades\Route::has('login'))`
*   **Premium Visuals & Premium UX**: Kullanıcı arayüzleri premium düzeyde olmalıdır. Sıradan renkler yerine modern ve göz alıcı HSL paletleri, şık **Glassmorphic** (cam efekti / backdrop-filter) tasarımlar, yumuşak mikro animasyonlar ve geçişler kullanılmalıdır.
*   **Karanlık Mod (Dark Mode) Standardı**: Tailwind karanlık mod sınıfları (`dark:bg-slate-900`, `dark:text-slate-100` vb.) eksiksiz kullanılmalı ve kontrast oranları mutlaka test edilmelidir.
*   **Localization (Yerelleştirme) [YENİ KODLAR İÇİN ZORUNLU / PHASE 5 HEDEFİ]**: Blade şablonlarında hardcoded Türkçe veya İngilizce metinler yerine her zaman `__('messages.key')` şeklinde yerelleştirme yardımcıları kullanılmalıdır. Eski codebase genelinde bu kuralın yaygın olmayabileceğini göz önünde bulundurun, ancak yeni geliştirdiğiniz tüm bloklar için bu kurala uyun.

---

## 🛡️ 3. YALIHAN BEKÇİ VE AST GÜVENLİK DUVARLARI (AST SAFEGUARDS)

**Yalıhan Bekçi (v2.1)**, soyut sözdizimi ağacı (AST) tabanlı mimari denetim katmanıdır. Yazdığınız kod Bekçi kurallarını ihlal ederse, yerel bütünlük taramaları ve CI/CD süreçleri anında bloklanır.

### A. Temel AST Kuralları ve Yakaladıkları Hatalar:
*   **`SilentCatchAST`**: Catch blokları asla boş bırakılamaz ve hataları sessizce yutamaz. En azından hatayı loglamalı, geri fırlatmalı veya raporlamalıdır.
*   **`EnvUsageAST`**: `app/` dizini altındaki php dosyalarında doğrudan `env()` fonksiyonunu çağırmak yasaktır. Çevre değişkenleri sadece `config/` dosyalarında okunmalı, kod içinde `config('services.name')` şeklinde çağrılmalıdır.
*   **`ForbiddenFieldAST`**: Veritabanı ve model alanlarında `status`, `active`, `is_active`, `type`, `order` gibi yasaklı (eski) kelimelerin doğrudan kullanımını yakalar (Bypass edilmedikçe).
*   **`ForbiddenFunctionAST`**: Sistem düzeyinde tehlikeli olan `eval`, `exec`, `shell_exec`, `system`, `passthru` vb. fonksiyon kullanımlarını engeller.
*   **`AP-COGNITIVE-001`**: Hem boş catch hem de doğrudan `env()` kullanımı içeren çift güvenceli bilişsel ihlalleri yakalar.

### B. Bypass İşaretçileri (Bypass Markers):
Bekçi kurallarını meşru durumlarda atlatmak için şu yorum satırları veya doc comment etiketleri kullanılabilir:
*   **Catch Bypass (`@sab-ignore-catch`)**: Silent catch kuralını geçmek için catch bloğu doc comment'i içine eklenir:
    ```php
    try {
        // kod...
    } catch (\Exception $e) {
        /** @sab-ignore-catch */
    }
    ```
*   **İsimlendirme Bypass (`// context7-ignore`)**: Context7 alan adı ihlallerini meşru yerlerde (örneğin üçüncü parti API eşlemelerinde) bypass etmek için satır sonuna eklenir:
    ```php
    $data['status'] = $yayinDurumu; // context7-ignore
    ```

---

## 🔤 4. CONTEXT7 TÜRKÇE KANONİK İSİMLENDİRME KURALLARI

Veritabanı kolonları, model alanları ve kritik API parametrelerinde İngilizce veya eski isimlendirmeler yasaktır. Her zaman aşağıdaki Türkçe Kanonik karşılıkları kullanılmalıdır:

| ❌ Yasaklı / Eski İsim (Legacy) | ✅ Kanonik Karşılık (Context7) | Açıklama |
| :--- | :--- | :--- |
| `status` | `yayin_durumu` | İlanın yayınlanma veya akış durumu |
| `active`, `is_active` | `aktiflik_durumu` | Kaydın aktiflik/pasiflik durumu |
| `order`, `sort_order` | `display_order` | Görsel sıralama değeri |
| `featured` | `one_cikan` | Öne çıkarılmış ilan statüsü |
| `latitude`, `enlem` | `lat` | Enlem koordinatı |
| `longitude`, `boylam` | `lng` | Boylam koordinatı |
| `city`, `sehir` | `il` veya `il_adi` | İl/Şehir adı |
| `featured_image` | `kapak_resmi` | İlanın ana/kapak görseli |
| `musteriler` | `kisiler` | CRM Kişiler tablosu / Modülü |

---

## 🧰 5. SCRIPTS/TOOLS DİZİNİNDEKİ YETENEK ORDUSU KATALOĞU

`scripts/tools/` dizininde, ajanların otomatik olarak çalıştırabileceği, kodun sağlığını denetleyen, karanlık modu düzenleyen, bütünlük doğrulaması yapan 80'den fazla güçlü betik yer almaktadır. İşte en kritik betikler ve kullanım amaçları:

### A. Mimari Bütünlük, SAB ve Yönetişim Araçları (Architecture & SAB)
*   **`scripts/tools/sab-apply.sh`** & **`scripts/tools/sab-apply-proposal.sh`**:
    *   *Açıklama:* Teknik anayasaya uygun olarak mimari değişiklik önerilerini (SAB proposals) güvenli bir şekilde sisteme uygular.
*   **`scripts/tools/sab-propose.sh`**:
    *   *Açıklama:* Yapılan mimari değişiklikler için bütünlük imzası (SAB checksum hash) üretir.
*   **`scripts/tools/sab-decide.sh`**:
    *   *Açıklama:* Mimari karar alma süreçlerini (ADR) otomatize eder ve belgeler.
*   **`scripts/tools/dap-drift-check.cjs`**:
    *   *Açıklama:* Çalışan canlı kod tabanı ile `authority.json` ve `SAB.md` arasındaki mimari kaymayı (drift) denetler. Sapma bulursa raporlar.
*   **`scripts/tools/dap-seal.cjs`**:
    *   *Açıklama:* Üretim mühürünü (Production Seal) doğrular. Sistem üzerinde tüm kısıtlamaların aktif ve güvenli olduğunu doğrulamak için kullanılır.
*   **`scripts/tools/compile-authority.cjs`**:
    *   *Açıklama:* `.sab/authority.json` konfigürasyonunu yeniden derler ve günceller.

### B. Context7 ve Veri Sağlığı Araçları (Context7 & Data Hygiene)
*   **`scripts/tools/context7-advisor.php`** / **`context7-full-scan.sh`** / **`context7-strict-check.sh`**:
    *   *Açıklama:* Projede Context7 standartlarına uymayan veritabanı şemalarını ve php kodlarındaki alan adlarını tarar, iyileştirme tavsiyeleri verir.
*   **`scripts/tools/context7-auto-fix.sh`**:
    *   *Açıklama:* Yakalanan Context7 naming ihlallerini otomatik olarak güvenli bir şekilde Türkçe kanonik karşılıklarıyla değiştirir.
*   **`scripts/tools/fix-fillable-violations.php`**:
    *   *Açıklama:* Modellerdeki `$fillable` dizilerinde yer alan ve Context7'yi ihlal eden alan adlarını otomatik olarak düzeltir.

### C. UI, Görsel Estetik ve Karanlık Mod Araçları (UI & Dark Mode)
*   **`scripts/tools/fix-dark-mode.cjs`** / **`fix-dark-mode-variants.py`**:
    *   *Açıklama:* Blade şablonlarını tarayarak eksik Tailwind karanlık mod (`dark:`) sınıflarını otomatik olarak tespit eder ve güvenli bir şekilde enjekte eder.
*   **`scripts/tools/bulk-dark-mode-stabilize.cjs`**:
    *   *Açıklama:* UI temalarının karanlık modda görsel kararlılığını ve kontrast bütünlüğünü en üst düzeye çıkarır.
*   **`scripts/tools/bulk-dark-mode-standardizer.cjs`**:
    *   *Açıklama:* Karanlık mod renk geçişlerini ve HSL renk paletlerini modüller genelinde standardize eder.
*   **`scripts/tools/dark-mode-invariants.js`**:
    *   *Açıklama:* Tema uyumluluk kurallarını zorunlu tutarak görsel bozulmaları engeller.
*   **`scripts/tools/verify-sidebar-dark.sh`**:
    *   *Açıklama:* Kenar çubuğu (sidebar) bileşeninin karanlık modda kusursuz göründüğünü doğrulamak için özel bir görsel test çalıştırır.

### D. Test Otomasyonu ve E2E Entegrasyon Araçları (Testing & E2E)
*   **`scripts/tools/test-ilan-wizard.mjs`**:
    *   *Açıklama:* Yeni ilan ekleme sihirbazının (Listing Wizard) front-end ve back-end entegrasyonunu tarayıcı (Playwright) üzerinden uçtan uca simüle ederek test eder.
*   **`scripts/tools/generate-wizard-env.js`**:
    *   *Açıklama:* Yerel test ve e2e süreçleri için ilan sihirbazına özel izole sandbox ortamı ve test `.env` dosyasını hazırlar.
*   **`scripts/tools/e2e-data-setup.php`**:
    *   *Açıklama:* E2E testleri öncesinde veritabanında test kullanıcıları ve örnek ilan verileri oluşturur.
*   **`scripts/tools/check-wizard-networking.php`**:
    *   *Açıklama:* İlan oluşturma sihirbazının ön yüzü ile API arka yüzü arasındaki network bağlantı ve port durumlarını test eder.
*   **`scripts/tools/test-v2-api.sh`**:
    *   *Açıklama:* Laravel API v2 uç noktalarına hızlı duman (smoke) testleri göndererek API'nin ayakta olduğunu doğrular.

### E. Veritabanı ve Şablon Hata Giderme (Database Diagnostics)
*   **`scripts/tools/debug-db.php`**:
    *   *Açıklama:* Veritabanı bağlantı kalitesini ve şema tablolarını hızlıca sorgular.
*   **`scripts/tools/fix-arsa-alani.php`** / **`fix-arsa-template.php`** / **`fix-arsa-ui-ipuclari.php`**:
    *   *Açıklama:* Arsa kategorisine ait özel şablonları, alan hesaplama ve UI ipuçlarını Context7 standartlarına göre onarır.
*   **`scripts/tools/seed-pivot-data.php`**:
    *   *Açıklama:* Çoka çok (many-to-many) ilişkili pivot tablolarına test verisi basar.

### F. Antigravity Otonom Koruma Araçları (AI Agent Self-Guard — Oturum 14)

> **Oluşturulma Tarihi:** 2026-05-20 | **Amaç:** Geçmiş 10 hata kategorisinin tekrarını sıfıra indirmek

*   **`scripts/tools/antigravity-preflight.sh`** ⭐ (10 Altın Kural Tarayıcısı):
    *   *Açıklama:* Sadece değiştirilmiş dosyaları (git diff tabanlı) tarar. FontAwesome yasağı, FQCN Blade Facade, hardcoded URL, MIX_ prefix, deterministik `first()`, deprecated API, `env()` dışı kullanım ve sessiz catch kontrollerini tek komutla yapar.
    *   *Kullanım:* `./scripts/tools/antigravity-preflight.sh`

*   **`scripts/tools/antigravity-schema-check.sh`** (Şema-Önce Doğrulayıcı):
    *   *Açıklama:* Kod yazmadan önce tablo ve kolon varlığını canlı veritabanından doğrular. Yasaklı Context7 alan adları kullanılmışsa kanonik karşılığını önerir (örneğin `status` → `yayin_durumu`).
    *   *Kullanım:* `./scripts/tools/antigravity-schema-check.sh ilanlar yayin_durumu fiyat`

*   **`scripts/tools/antigravity-component-check.sh`** (Bileşen/View Varlık Denetçisi):
    *   *Açıklama:* Blade bileşenleri (`x-yaliihan.property-card`) ve view referansları (`layouts.frontend`) gibi isimlerin diskte gerçekten var olup olmadığını doğrular. Bulunamazsa en yakın alternatifleri listeler.
    *   *Kullanım:* `./scripts/tools/antigravity-component-check.sh layouts.frontend x-yaliihan.property-card frontend.scripts.ai-search`

*   **`scripts/tools/antigravity-route-check.sh`** (Route Muhafızı):
    *   *Açıklama:* Belirli bir named route'un varlığını doğrular (`--check`), duplicate route isimlerini tespit eder (`--duplicates`), veya tüm route'ları filtreler (`--list`).
    *   *Kullanım:*
        ```bash
        ./scripts/tools/antigravity-route-check.sh --check frontend.ilanlar.index
        ./scripts/tools/antigravity-route-check.sh --duplicates
        ./scripts/tools/antigravity-route-check.sh --list frontend
        ```

*   **`scripts/tools/antigravity-layout-check.sh`** (Layout Doğrulayıcı):
    *   *Açıklama:* Blade dosyalarının konumuna göre doğru `@extends()` layout kullanıp kullanmadığını denetler. Kurallar:
        *   `frontend/` ve `public/` → `@extends('layouts.frontend')`
        *   `admin/` → `@extends('admin.layouts.admin')` veya `@extends('layouts.admin')` veya `@extends('layouts.app')`
        *   `auth/` → `@extends('layouts.guest')` veya `@extends('layouts.app')`
    *   *Kullanım:* `./scripts/tools/antigravity-layout-check.sh`

*   **`scripts/tools/antigravity-full-gate.sh`** ⭐ (Tam Kalite Kapısı Pipeline'ı):
    *   *Açıklama:* Yukarıdaki tüm Antigravity araçlarını ve artisan komutlarını sıralı olarak çalıştırır: Preflight → Layout → Route → SAB Scan → Bekçi Health. Hızlı mod için `--quick` flag kullanılabilir.
    *   *Kullanım:*
        ```bash
        ./scripts/tools/antigravity-full-gate.sh          # Tam pipeline
        ./scripts/tools/antigravity-full-gate.sh --quick   # Artisan komutları olmadan hızlı mod
        ```

---

## 🏃‍♂️ 6. AJANLAR İÇİN GÜNLÜK ÇALIŞMA PROTOKOLÜ (SAB PROTOCOL)

Yalıhan AI OS'ta bir göreve başladığınızda şu adımları izleyin:

1.  **Görevi İnceleyin**: İlgili dosyaları okuyun.
2.  **Bekçi ve SAB Durumunu Kontrol Edin**:
    ```bash
    php artisan sab:integrity-scan
    php artisan bekci:health
    ```
3.  **Kodu Geliştirin**:
    *   Thin Controller, Service Katmanı ve `IlanCrudService` kurallarına uyun.
    *   Context7 Türkçe kanonik isimlendirmelerinden sapmayın.
    *   Catch bloklarında asla sessiz hata yutmayın.
    *   Blade şablonlarında tam nitelikli Facade isimleri kullanın.
4.  **Arayüzleri Wow Faktörü ile Donatın**:
    *   Geliştirilen Blade sayfalarında şık renk paletleri, dark mode entegrasyonu, glassmorphic arayüzler ve mikro animasyonlar tasarlayın.
5.  **Bütünlüğü Doğrulayın**:
    *   Yaptığınız değişikliklerin ardından Bekçi'yi tekrar çalıştırın ve testleri koşun:
    ```bash
    php artisan sab:integrity-scan
    php artisan bekci:audit --all
    php artisan test
    ```
6.  **Gerekirse Kayma (Drift) Korumasını Güncelleyin**:
    *   Teknik anayasa (`docs/SAB.md`) üzerinde meşru bir değişiklik yaptıysanız, checksum imzasını yeniden üretin:
    ```bash
    scripts/tools/sab-propose.sh
    ```

---
*Bu kılavuz, yapay zeka ajanlarının Yalıhan projesinde en yüksek kalitede, uyumlu ve kusursuz kod üretmesini sağlamak amacıyla tasarlanmıştır.*
