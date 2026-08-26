# YALIHAN OS — AI Agent & Geliştirici Oryantasyon ve Mimari Standartlar Rehberi

> **Dosya Konumu:** `.agents/ONBOARDING_DEVELOPER.md`  
> **Kapsam:** Yalıhan OS üzerinde çalışan tüm AI modelleri (Antigravity, Codex, ChatGPT, Claude) ve insan mühendisler için tek bağlayıcı oryantasyon ve mimari referans belgesidir.  
> **Önemli İlke:** Bu belge temel rehberdir; ancak hiçbir dokümantasyon tek başına hatasızlık garantisi vermez. Kod, test ve canlı tarayıcı doğrulaması her zaman zorunludur.

---

## 1. 🏗️ Proje Kimliği ve Teknoloji Yığını

- **Proje:** Yalıhan Emlak — Bodrum merkezli lüks gayrimenkul, kısa/uzun dönem kiralama ve AI destekli portföy operasyon işletim sistemi.
- **Mimari:** Modular Monolith (`App\Domain\`, `App\Domains\`, `App\Modules\`, `App\Services\`).
- **Backend:** Laravel 10 / PHP 8.2+ (PHP-FPM `yalihanai-app-v2`).
- **Frontend:** Blade + Vanilla Tailwind CSS + Alpine.js + Vite (Build bundle `/app/public/build`).
- **İkon Standardı:** `<x-icon name="..." />` (`resources/views/components/icon.blade.php`). Font Awesome (`fa-`) kesinlikle yasaktır.
- **Kuyruk / Önbellek:** Redis 7 (`yalihanai-redis-v2`), Horizon Worker (`yalihanai-queue-v2`).
- **Tasarım Sistemi:** Premium Mediterranean Design System (Navy `#0A1628`, Gold `#C9A84C`, Cream `#F8F6F1`).

---

## 2. 🛡️ SAB Teknik Anayasası & Otorite Kuralları

Tüm kod geliştirme süreçlerinde **SAB (Strategic Architecture Board)** anayasal kuralları mutlaktır:

### A. Değişmez Yazma Otoritesi Zinciri
```
Controller → Service → IlanCrudService → Repository → DB
```
- Controller içinde doğrudan `Eloquent::create/update/delete` veya `DB::table()->insert()` yazılması anayasal suçtur.
- Veritabanına yazma yetkisi yalnızca `IlanCrudService` ve yetkili domain servislerindedir.

### B. Thin Controller Zorunluluğu
- Controller'lar sadece HTTP isteklerini doğrulamalı (`FormRequest`), yetkili servisi çağırmalı ve JSON veya View yanıtı dönmelidir. İş mantığı içeremez.

### C. Sessiz Catch Yasağı (No Silent Catch)
- Boş `catch (\Exception $e) {}` blokları yasaktır. Hatalar mutlaka `LogService` ile loglanmalı veya rethrow edilmelidir. Bypass gerekiyorsa `/** @sab-ignore-catch */` eklenmelidir.

### D. Temel Model Zorunluluğu
- Tüm Eloquent modeller istisnasız `App\Models\BaseModel` sınıfını extend etmelidir.

---

## 3. 🔒 Tenant İzolasyonu (Kural 1 — Sıfır Tolerans)

- Çapraz tenant (cross-tenant) veri sızıntısı en kritik güvenlik ihlalidir.
- Her iş tablosu ve modeli zorunlu olarak `tenant_id` barındırmalı ve `BelongsToTenant` trait'i / `TenantScope` içermelidir.
- Hiçbir API veya sorgu, tenant filtresi olmadan dışarıya veri açamaz.

---

## 4. 🔤 Context7 Türkçe Kanonik Alan Sözlüğü

Yabancı dil veya tutarsız İngilizce alan adları yerine sistem genelinde **Türkçe Kanonik Alan Standartları** zorunludur:

| ❌ Yasaklı / Eski Alan | ✅ Zorunlu Context7 Kanonik | Açıklama |
|---|---|---|
| `status` | `yayin_durumu` | İlanın yaşam döngüsü (`taslak`, `yayinda`, `pasif`) |
| `active` / `is_active` | `aktiflik_durumu` | Sistemsel aktiflik durumu (1/0) |
| `city` / `sehir` | `il` / `il_adi` | Coğrafi hiyerarşi |
| `district` / `state` / `ilce` | `ilce` / `ilce_adi` | Coğrafi hiyerarşi |
| `neighborhood` / `mahalle` | `mahalle` / `mahalle_adi` | Coğrafi hiyerarşi |
| `featured` | `one_cikan` | Vitrinde öne çıkarma |
| `featured_image` | `kapak_resmi` | Ana görsel alanı |
| `latitude` / `longitude` | `lat` / `lng` | Harita koordinatları |
| `musteriler` / `customer` | `kisiler` | CRM kişi modeli |
| `order` / `sort_order` | `display_order` | Görsel sıralama |

*Geçici bypass gerekiyorsa kod satırına `// context7-ignore` eklenmelidir.*

---

## 5. 🗄️ Çift Veritabanı ve CQRS Okuma Mimarisi

Sistemde iki farklı veritabanı bağlantısı mevcuttur:

1. **Ana Çekirdek DB (`mysql`):**
   - İlanlar, kişiler, talepler, görevler, finans ve kullanıcılar.
   - `config/database.php` içindeki `mysql` bağlantısıdır (`DB_DATABASE` env).
2. **Piyasa Zekası DB (`market_intelligence`):**
   - Harici portallardan taranan rakip ilanlar, fiyat trendleri ve scraping verileri.
   - Veritabanı: `yalihan_market`.
   - Modellerde zorunlu tanım: `protected $connection = 'market_intelligence';`.

### CQRS Projeksiyon Katmanı (Read Models)
- Dashboard'lar ve analitik paneller ana tablolara ağır sorgu atmaz; CQRS projeksiyonlarını (`MarketTrendProjection`, `ListingVelocityProjection`, `TalepMatchProjection`) kullanır.
- Projeksiyonları sıfırdan yeniden oluşturma komutu:
  ```bash
  php artisan projection:rebuild
  ```

---

## 6. 🏖️ Bodrum Yerel Gayrimenkul & Domain Zekası

- **Tapu & İmar Sınıfları:** *Kat Mülkiyeti*, *Kat İrtifakı*, *Hisseli Tapu*, *Zilliyetlik*, *Tarla* ve *Sit Alanı (1., 2., 3. Derece Sit)* kavramları arsa ve villa şablonlarında zorunlu alan kurallarına tabidir.
- **Çoklu Para Birimi:** Portföyler genellikle **Euro (€)** veya **Dolar ($)** ile girilir; ancak resmi işlemler ve listelemeler **TL (₺)** karşılığı gerektirir. `fiyat` ve `para_birimi` daima birlikte ele alınır (`CurrencyService`).
- **Dinamik Sezonluk Fiyatlandırma:** Yazlık villalarda *Düşük*, *Orta* ve *Yüksek Sezon* periyotlarına göre dinamik fiyat matrisi (`IlanFiyat`) çalışır.

---

## 7. 🧩 Çekirdek Alt Sistemler ve Mimari Sınırlar

| Alt Sistem | Sorumluluk ve Mimari Sınır |
|---|---|
| **`YalihanCortex`** | **AI Beyni:** İçerik, değerleme, alıcı-ilan eşleştirme, çok dilli çeviri ve lead skorlama. LLM (Ollama, OpenAI, Gemini) çağrılarını yönetir. |
| **`Hermes`** | **İletişim & Olay Veriyolu (Event Bus):** Webhook ve kuyruk mesajlarını karşılar, dağıtır ve idempotency sağlar. **Asla LLM modeli çağırmaz.** |
| **`Property Engine`** | **Şema & Tip Motoru:** Gayrimenkul kategorileri, özellik paketleri ve form alan bağımlılıklarını mühürlü versiyonlarla (`PropertyConfigVersion`) yönetir. |
| **`Automation Hub`** | **Otomasyon Kokpiti:** n8n iş akışları, Telegram botu, sesli arama ve çok kanallı bildirimleri koşturur. |
| **`Portfolio Doctor`** | **Klinik Tanı Motoru:** İlan sağlığını 9 pazar sinyaliyle tarar ve satış hızlandırma reçeteleri üretir. |
| **`Yalıhan Bekçi`** | **Sistem Muhafızı:** 7/24 kod izleme, Context7 AST taraması, sağlık probları (`bekci:health`) ve sözleşme denetimini yürütür. |

---

## 8. 🧰 Kalite Kapıları ve Antigravity Koruma Araçları

Herhangi bir commit atmadan veya kod teslim etmeden önce çalıştırılması zorunlu araçlar:

```bash
# 1. Değişen dosyalarda 10 Altın Kural ve Context7 kontrolü
./scripts/tools/antigravity-preflight.sh

# 2. Blade layout ve extends kontrolü
./scripts/tools/antigravity-layout-check.sh

# 3. Route varlığı ve duplicate kontrolü
./scripts/tools/antigravity-route-check.sh --duplicates

# 4. View component ve ikon varlık kontrolü
./scripts/tools/antigravity-component-check.sh x-icon layouts.frontend

# 5. TÜM kontrolleri tek seferde koşturan Master Kapı
./scripts/tools/antigravity-full-gate.sh --quick

# 6. Derin SAB AST Bütünlük Taraması
php artisan sab:integrity-scan
```

---

## 9. 🛰️ VPS Production & Güvenli Çalışma Protokolü

- **Sunucu IP:** `157.180.116.63`
- **Çalışma Dizini:** `/opt/yalihan2026/current`
- **Container Adları:** `yalihanai-nginx-v2`, `yalihanai-app-v2`, `yalihanai-queue-v2`
- **Protokol:**
  - AI ajanları VPS üzerinde varsayılan olarak **salt okunur (Read-Only)** çalışır.
  - Açık kullanıcı onayı olmadan sunucuda deploy, migration, seed veya container restart yapılamaz.
  - SSH anahtarları ve token bilgileri hiçbir rapora veya loga yazılmaz.

---

## 10. ⚠️ Geçmiş Oturumlardan Çıkarılan Acı Dersler (Tuzaklar)

1. **Determinizm İhlali:** `->first()` sorgularında mutlaka `->orderBy('id')->first()` kullanılmalıdır.
2. **Blade Route Kontrolü:** Blade içinde `Route::has()` değil, mutlaka `\Illuminate\Support\Facades\Route::has()` FQCN kullanılmalıdır.
3. **`env()` Çağrısı:** `app/` dizini içinde `env()` kullanılamaz; daima `config('...')` kullanılmalıdır.
4. **Nginx Host Public Mount:** `docker-compose` üzerinde host `public` dizini container'a mount edilmemelidir (Vite build bundle'ı ezer).
5. **Storage Güvenliği:** `/storage/` dizininden SVG ve ICO yayını XSS riski nedeniyle yasaktır; yalnızca raster görsellere (`jpg, jpeg, png, webp`) izin verilir. Private belgeler `/app/storage` altından korunmalıdır.
6. **SAAB Low-Token Kuralı:** Kod geliştirirken başlangıçta en fazla 5 ilgili dosyayı incele; tüm repoyu gereksiz yere tarama. (Güvenlik denetimleri ve büyük refactoring istisnadır).

---

## 11. 🏷️ Kanıt Etiketleme Standardı (Evidence Labels)

Teknik durum ve başarı raporlarında şu etiketlerin kullanılması zorunludur:
- `REPO_VERIFIED`: Doğrudan aktif repository kodunda incelenip kanıtlanan durumlar.
- `TEST_VERIFIED`: Otomatik testlerle (PHPUnit/Pest) başarıyla doğrulanan durumlar.
- `PRODUCTION_VERIFIED`: Canlı VPS veya gerçek tarayıcı ortamında HTTP yanıtı ve ekran çıktısıyla kanıtlanan durumlar.
- `DOCUMENTED`: Kodda doğrulanmamış, yalnızca dokümantasyonda yazan durumlar.
- `INFERRED`: Mantıksal çıkarım yapılan, ancak kesin kanıtı henüz olmayan durumlar.
- `UNKNOWN`: Bilinmeyen veya henüz test edilmemiş durumlar.
