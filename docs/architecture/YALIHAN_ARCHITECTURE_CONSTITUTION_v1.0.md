# YALIHAN OS — ARCHITECTURE CONSTITUTION v1.0
**Makam:** Strategic AI Architecture Board (SAAB) & Yalihan Engineering Office  
**Statü:** KANONİK (CANONICAL) — DEĞİŞTİRİLEMEZ TEMEL YASA  
**Hedef Sistem:** Yalıhan Emlak / Yalıhan AI OS (Bodrum Lüks Gayrimenkul & Medya İşletim Sistemi)  
**Versiyon:** 1.0.0  
**Tarih:** 2026-09-06  

---

## 🧭 ÖNSÖZ VE TEMEL FELSEFE

> *"Gelecekteki bütün olasılıkları tahmin etmeye çalışmak overengineering'dir. Mühendisliğin amacı geleceği tahmin etmek değil; değişiklik ve büyüme geldiğinde sistemi kırmadan karşılayabilecek kesin sınırları, soyutlamaları ve sözleşmeleri kurmaktır."*

Bu anayasa; Yalıhan AI OS platformunda geliştirme yapan tüm insan mühendisler, yazılım mimarları ve otonom AI ajanları (Antigravity, Kilo, Codex, Wenox) için bağlayıcıdır. Bir kural bu belgede açıkça tanımlanmışsa, yerel tercihler veya hızlı çözüm bahaneleri geçersizdir.

---

## BÖLÜM 1: TEMEL VE SINIRLAR (MADDELER 1 - 9)
*(Bu 9 madde oturmadan hiçbir Media AI veya ileri otomasyon koduna başlanamaz.)*

---

### MADDE 1: Vision, Scope & Non-Goals (Vizyon, Kapsam ve Kapsam Dışı Sınırlar)

#### 1.1 Tanım & Kapsam
Yalıhan AI OS; Bodrum merkezli lüks gayrimenkul portföy operasyonlarını, çok kanallı ilan yayıncılığını, medya zenginleştirmesini ve AI destekli gayrimenkul pazarlama/müşteri ilişkilerini otonomlaştıran dikey bir işletim sistemidir.

#### 1.2 Zorunlu Kurallar (MUST)
- Sistem, bir gayrimenkulün fiziksel varlığından (`Property`) dijital ilana (`Listing`), medya paketlerine (`MediaAsset`), müşteri etkileşimine (`CRM`) ve takvim yönetimine (`Reservation`) kadar olan lüks gayrimenkul yaşam döngüsünü yönetmelidir.
- Her yeni özellik geliştirilmeden önce şu temel soru yanıtlanmalıdır: *"Bu geliştirme hangi manuel gayrimenkul operasyonunu ortadan kaldırıyor?"*

#### 1.3 Kesin Yasaklar (FORBIDDEN)
- **Genel Amaçlı ERP Yasağı:** Sistem bordro, genel muhasebe defter-i kebir, insan kaynakları veya filo yönetimi gibi genel ERP işlevlerini içermez ve bunları çözmeye kalkışmaz.
- **Her Şeyi Yapan CMS Yasağı:** Sistem WordPress benzeri keyfi sayfa oluşturucu (arbitrary page builder) veya kontrolsüz e-ticaret sepet sistemi değildir.
- **Sosyal Ağ / Sohbet Platformu Yasağı:** Müşterilerin kendi aralarında serbestçe mesajlaştığı genel bir sosyal ağ altyapısı kurulamaz.

#### 1.4 İstisnalar
- Muhasebe/Finans için sadece gayrimenkul komisyonları, kapora/depozito kayıtları ve ilan bütçeleri gibi dikey domain çıktıları tutulur; genel muhasebe dış entegrasyonlara devredilir.

#### 1.5 Otomasyon & Kontrol
- PR ve Feature Gate denetimi: Scope dışı domain ekleme girişimleri SAAB mimari incelemesinde (`saab-enterprise-architecture-review`) doğrudan reddedilir.

---

### MADDE 2: Architecture Principles (Değişmez Mimari İlkeler)

#### 2.1 Tanım & Kapsam
Sistemin kod tabanında, servislerinde ve veri akışlarında uygulanan tavizsiz yazılım mühendisliği prensipleridir.

#### 2.2 Zorunlu Kurallar (MUST)
1. **Modülerlik & Gevşek Bağlılık (Loose Coupling):** Her domain kendi içinde yüksek uyuma (high cohesion), diğer domainlerle gevşek bağlılığa sahip olmalıdır.
2. **Tek Sorumluluk (Single Responsibility):** Bir sınıf veya servis yalnızca tek bir iş aktörünün veya domain sürecinin gerekçesiyle değişebilir.
3. **Geri Alınabilirlik (Reversibility):** Alınan her teknoloji veya kütüphane kararı, minimum eforla tersine çevrilebilir (reversible) arayüzler arkasında gizlenmelidir.
4. **Dış Servis Bağımsızlığı (Vendor Agnosticism):** Dış SaaS servisleri (Google Drive, OpenAI, Stripe, Meta vb.) adaptör katmanları arkasına alınmalı, domain çekirdeğine doğrudan sızdırılmamalıdır.
5. **Yalınlık ve YAGNI (Ponytail Principle):** İhtiyaç duyulmayan hiçbir spekülatif esneklik, aşırı kalıtım zinciri veya kullanılmayan soyutlama yazılamaz. En yalın, çalışan çözüm esastır.

#### 2.3 Kesin Yasaklar (FORBIDDEN)
- Domain katmanı içinde dış SDK sınıflarının doğrudan `new` ile türetilmesi veya import edilmesi yasaktır.
- İş mantığını birden fazla yerde kopyalamak (Copy-Paste Architecture) veya ikinci bir doğruluk kaynağı (Second Source of Truth) üretmek yasaktır.

#### 2.4 İstisnalar
- Framework-level çekirdek bootstrap işlemleri (Laravel Service Provider konfigürasyonları).

#### 2.5 Otomasyon & Kontrol
- `composer lint` (Laravel Pint), PHPStan Seviye 8 analizi ve `./scripts/tools/antigravity-preflight.sh` scripti ile statik doğrulama.

---

### MADDE 3: Domain Map & Bounded Contexts (Domain Haritası ve Bağlam Sınırları)

#### 3.1 Tanım & Kapsam
Sistem aşağıdaki kesin sınırları çizilmiş 10 Bounded Context (Sınırlı Bağlam) etrafında kümelenir:
1. **Property Domain:** Fiziksel gayrimenkul (ada, parsel, m², konum, oda, yapı tipi, tapu verisi).
2. **Listing Domain:** Ticari ilan teklifi (satılık/kiralık fiyat, portallar, yayın durumu, başlık, vitrin).
3. **CRM Domain:** Müşteriler, mülk sahipleri, talepler, eşleşmeler, iletişim logları (`kisiler`).
4. **Reservation Domain:** Kısa dönem kiralama takvimi, iCal senkronizasyonu, doluluk, fiyatlandırma.
5. **Media Domain:** Ham medya ingest, depolama, kalite puanlama, varyantlar, watermark, render.
6. **Finance Domain:** Komisyon hakedişleri, kapora takibi, ilan portal harcamaları.
7. **Operations Domain:** Saha görevleri, anahtar teslimi, temizlik/bakım takibi, denetimler.
8. **AI / Cortex Domain:** İlan metin üretimi, görsel etiketleme, değerleme analizi, agent orchestration.
9. **Automation Domain:** Zamanlanmış görevler, n8n webhook tetikleyicileri, otomatik dağıtım akışları.
10. **Identity & Auth Domain:** Kullanıcılar, roller, izinler, tenant yalıtımı, audit aktörleri.

#### 3.2 Zorunlu Kurallar (MUST)
- Her dosya, model ve servis bu 10 alandan tam olarak bir tanesine ait olmalıdır.
- Sınır aşımı durumunda yalnızca resmi DTO ve Service Contract kullanılmalıdır.

#### 3.3 Kesin Yasaklar (FORBIDDEN)
- "Tanımsız / Genel / Utils / Helpers" adında çöp kutusu klasör veya domain oluşturulamaz.
- Bir domain başka bir domain'in iç modellerini kendi veri transferi için kullanamaz.

#### 3.4 İstisnalar
- Ortak temel sınıflar (`App\Models\BaseModel`, `App\ValueObjects\...`) `App\Support\` altında strictly type-safe olarak barınabilir.

#### 3.5 Otomasyon & Kontrol
- Pest/PHPUnit Architecture Testleri (`arch()->expect('App\Domain\Property')->not->toUse('App\Domain\Listing')`).

---

### MADDE 4: Ubiquitous Language & Naming Constitution (Tek Sözlük ve İsimlendirme Anayasası)

#### 4.1 Tanım & Kapsam
Yalıhan OS genelinde kullanılan terimler tek bir sözlükte standardize edilmiştir. Kod tabanında terim kargaşası ve hibrit diller yasaktır.

#### 4.2 Zorunlu Kurallar (MUST)
- **Kod Dili:** PHP/JS sınıfları, metodlar, değişkenler, DTO'lar ve DB tabloları **İngilizce** veya **Kanonik Context7** kurallarına tam sadık olmalıdır.
- **Kavramsal Ayrım:**
  - `Property` (Fiziksel Mülk) ile `Listing` (İlan/Pazarlama Teklifi) asla birbirinin yerine kullanılamaz.
  - `Owner` (Mülk Sahibi) ile `Client/Lead` (Alıcı/Kiracı Adayı) net ayrılmalıdır.
  - `MediaAsset` (Dosya kaydı) ile `MediaJob` (İşleme görevi) karıştırılamaz.
- **Context7 Kanonik Alan Eşleştirmesi:**
  - `status` → `yayin_durumu`
  - `is_active` / `active` → `aktiflik_durumu`
  - `sort_order` / `order` → `display_order`
  - `featured` → `one_cikan`
  - `featured_image` → `kapak_resmi`
  - `city` / `sehir` → `il` / `il_adi`
  - `latitude` / `longitude` → `lat` / `lng`
  - `musteriler` → `kisiler`

#### 4.3 Kesin Yasaklar (FORBIDDEN)
- Türkçe ve İngilizce kelimelerin aynı model/değişken isminde hibrit kullanılması (ör. `$ilanProperty`, `$getMusteriName()`) yasaktır.
- Kısaltma ve anlamsız isimler (ör. `$p`, `$l_stat`, `$data2`) kullanılamaz.

#### 4.4 İstisnalar
- Dış entegrasyonlardan (ör. Sahibinden, HepsiEmlak API) gelen ham payload'lar DTO girişinde Context7 formatına normalize edilene kadar geçici olarak orijinal adlarını koruyabilir.

#### 4.5 Otomasyon & Kontrol
- `php artisan sab:integrity-scan` komutu Context7 ihlallerini AST düzeyinde denetler ve bloklar.

---

### MADDE 5: Modular Monolith Strategy (Modüler Monolit Stratejisi)

#### 5.1 Tanım & Kapsam
Yalıhan OS, başlangıç ve ölçeklenme mimarisi olarak **Modular Monolith** mimarisini benimser. Tüm modüller tek bir versiyon kontrol reposunda yaşar, tek deployment birimi olarak çalışır ancak mantıksal ve fiziksel dizin sınırlarıyla katı şekilde ayrılır.

#### 5.2 Zorunlu Kurallar (MUST)
- Tüm modüller bağımsız olarak paketlenebilecek ve gelecekte gerekirse ayrı servislere dönüştürülebilecek şekilde tasarlanmalıdır.
- Modüller arası veri transferi sadece DTO'lar (Data Transfer Objects) ve Primitive tiplerle yapılmalıdır.
- Modüller kendi config, migration ve event listener tanımlarını kendi alt dizinlerinde kapsüllemelidir.

#### 5.3 Kesin Yasaklar (FORBIDDEN)
- **Erken Mikroservis Yasağı:** Dağıtık ağ karmaşası, ağ gecikmesi, RPC deserialization yükü ve 2-phase commit gerektiren mikroservis mimarisine geçiş SAAB onayı olmadan kesinlikle yasaktır.
- Modüllerin paylaşımlı global değişkenler üzerinden haberleşmesi yasaktır.

#### 5.4 İstisnalar
- Asenkron ağır işleme gerektiren işler (video transcoding, AI inferencing) monolit dışındaki worker process'lerde kuyruk aracılığıyla yürütülür.

#### 5.5 Otomasyon & Kontrol
- Klasör yapısı hiyerarşisi (`app/Domains/{DomainName}/...`) ve namespace sınırları linter/statik analiz tarafından zorunlu tutulur.

---

### MADDE 6: Module Dependency Rules (Modül Bağımlılık ve Erişim Kuralları)

#### 6.1 Tanım & Kapsam
Modüllerin birbirleriyle nasıl etkileşime gireceğini belirleyen çağrı yönü kurallarıdır.

```
[UI / Controller / API]
         │
         ▼
[Application Service / UseCase]
         │
         ▼
[Domain Service / CrudService] ◄─── (Domain Events) ───► [Other Modules via Contracts]
         │
         ▼
[Repository / Model]
         │
         ▼
     [Database]
```

#### 6.2 Zorunlu Kurallar (MUST)
- Modüller arası etkileşim daima `Contracts` (Arayüzler) veya `Domain Events` üzerinden gerçekleşmelidir.
- Çağrı yönü asiklik (Acyclic Dependencies Principle) olmalıdır: Modül A, Modül B'ye bağımlıysa; Modül B doğrudan Modül A'ya bağımlı olamaz (Ters yönde sadece Event fırlatılabilir).
- Yazma Otoritesi Zinciri kesintisiz işletilmelidir:
  `Controller → Service → CrudService → Repository → DB`

#### 6.3 Kesin Yasaklar (FORBIDDEN)
- **Çapraz Tablo/Model Erişimi Yasağı:** Bir modül başka bir modülün Eloquent modelini doğrudan import edip sorgulayamaz veya güncelleyemez. (Örnek: `MediaModule`, doğrudan `Ilan::where(...)` çalıştıramaz; `IlanQueryServiceInterface` kullanmak zorundadır).
- Controller içinde doğrudan `Eloquent::create`, `update`, `delete` işlemleri kesinlikle yasaktır.
- Raw SQL ile başka bir domain'in tablosuna `JOIN` atmak yasaktır.

#### 6.4 İstisnalar
- salt-okunur analitik ve raporlama sorguları için optimize edilmiş `ReadModel` veya CQRS database view'ları.

#### 6.5 Otomasyon & Kontrol
- `./scripts/tools/antigravity-preflight.sh` ve SAB Architecture Rule Engine denetimi.

---

### MADDE 7: Data Architecture Constitution (Veri Mimarisi Anayasası)

#### 7.1 Tanım & Kapsam
Veritabanı tablolarının, anahtarlarının, ilişkilerinin ve veri yaşam döngülerinin değişmez standartlarıdır.

#### 7.2 Zorunlu Kurallar (MUST)
1. **Primary Key:** Dağıtık ve sıralanabilir benzersiz kimlikler için ULID (veya UUIDv7 / BigIncrements + Public UUID) kullanılmalıdır.
2. **Foreign Key Integrity:** Bütün ilişkiler DB seviyesinde `foreign key` kısıtlamalarına ve indekslere sahip olmalıdır.
3. **Timestamps:** Her tabloda `created_at` ve `updated_at` zorunludur.
4. **Soft Delete:** Finansal, hukuki ve müşteri kayıtlarında `deleted_at` kullanılmalı; silinen kayıtların izi tutulmalıdır.
5. **Deterministic Ordering:** Bütün tekil kayıt getirme sorgularında `->first()` öncesinde deterministik bir `->orderBy('id')` veya `->orderBy('created_at')` bulunmalıdır.
6. **Strict Types:** DB kolon tipleri kesin olmalı; gereksiz `VARCHAR(255)` yerine kısıtlı string, boolean, date veya integer kullanılmalıdır.

#### 7.3 Kesin Yasaklar (FORBIDDEN)
- **Boş / Ölü Tablo Yasağı:** Kod tabanında hiçbir model veya servisle eşleşmeyen, kullanılmayan yetim (orphaned) tabloların DB'de bulunması yasaktır.
- Hardcoded enum string değerlerinin doğrudan kod içine gömülmesi yasaktır; PHP 8.1+ Enums (`BackedEnum`) kullanılmalıdır.
- Şema migration dosyalarının sonradan ezilerek geçmiş migration tarihçesinin bozulması yasaktır.

#### 7.4 İstisnalar
- Log ve telemetry tablolarında performans optimizasyonu amacıyla soft-delete yerine time-to-live (TTL) partition politikası uygulanabilir.

#### 7.5 Otomasyon & Kontrol
- `php artisan system:env-drift-guard` ve migration doğrulama testleri.

---

### MADDE 8: Single Source of Truth — SSOT Rules (Tek Doğruluk Kaynağı İlkeleri)

#### 8.1 Tanım & Kapsam
Sistemdeki her bir veri alanının tek bir mutlak sahibi (Authoritative Owner) vardır. Aynı bilginin farklı tablolarda birbirinden kopuk veya senkronize edilmeden çoğaltılması engellenir.

#### 8.2 Zorunlu Kurallar (MUST)
- **Fiziksel Özellikler:** Bir gayrimenkulün metrekaresi, oda sayısı, tapu ada/parseli yalnızca `Property` domaininde yaşar.
- **Fiyatlandırma:** Portföyün baz satış/kira fiyatı `Property` mülk kaydında; pazar vitrinindeki güncel teklif fiyatı ise `Listing` domaininde tutulur.
- **Medya Dosyaları:** Bir fotoğrafın fiziki dosyası, hash değeri ve çözünürlüğü yalnızca `MediaDomain` içindeki `MediaAsset` kaydında yaşar; diğer tablolar yalnızca bu kaydın ID'sini referans alabilir.

#### 8.3 Kesin Yasaklar (FORBIDDEN)
- Bir ilana ait oda sayısını hem `properties` hem de `ilanlar` tablosunda tutarak senkronizasyon açığı oluşturmak yasaktır.
- İlişkili tablolarda veri tutarlılığını sağlamak için veritabanı trigger'larına bağımlı kalınamaz; tutarlılık Domain Service katmanında sağlanır.

#### 8.4 İstisnalar
- Elasticsearch / Meilisearch veya Redis arama önbellekleri (Cache Views); bu yapılar sadece salt-okunur tüketim içindir ve veri kaynağı (SSOT) sayılmazlar.

#### 8.5 Otomasyon & Kontrol
- Veri şeması drift denetimi (`./scripts/tools/antigravity-schema-check.sh`) ve model ilişki testleri.

---

### MADDE 9: Storage & Media Source Abstraction (Depolama ve Medya Kaynak Soyutlaması)

#### 9.1 Tanım & Kapsam
Medya dosyalarının (fotoğraf, video, drone çekimi, 3D tur, evrak) fiziksel depolama sağlayıcılarından bağımsız olarak yönetilmesidir.

#### 9.2 Zorunlu Kurallar (MUST)
- Tüm depolama işlemleri `StorageProviderInterface` kontratı arkasında çalışmalıdır:
  - `store(FileStream $file, string $path): StorageResult`
  - `retrieve(string $path): FileStream`
  - `delete(string $path): bool`
  - `temporaryUrl(string $path, Carbon $expiry): string`
  - `calculateChecksum(string $path): string`
- Desteklenen veya gelecekte eklenecek sağlayıcılar (Google Drive, AWS S3, Cloudflare R2, Yerel NAS, VPS Local Disk) yalnızca bu adaptörü implemente etmelidir.
- Dosya sisteme girdiği anda SHA-256 hash'i çıkarılmalı ve idempotent olarak kaydedilmelidir.

#### 9.3 Kesin Yasaklar (FORBIDDEN)
- İş mantığı veya Controller katmanında `Storage::disk('local')->put(...)` veya doğrudan `file_get_contents()` / `file_put_contents()` gibi sürücüye bağımlı ham çağrılar yapılamaz.
- Canlı dosya URL'lerinin DB'de hardcoded domainlerle (`https://storage.googleapis.com/...`) saklanması yasaktır; yalnızca göreceli depolama yolu (relative path) saklanır.

#### 9.4 İstisnalar
- Geçici import/export işlemleri için `/tmp` işletim sistemi dizininde işlem yapılmasına izin verilir (işlem bitiminde anında temizlenmek şartıyla).

#### 9.5 Otomasyon & Kontrol
- Storage Adapter Feature Testleri (Mocked Provider üzerinden sözleşme testleri).

---

## BÖLÜM 2: DAVRANIŞ, ORKESTRASYON VE ENTEGRASYON (MADDELER 10 - 15)

---

### MADDE 10: YALIHAN Media Domain (Medya Hattı ve Yaşam Döngüsü)

#### 10.1 Tanım & Kapsam
Fotoğrafların ve videoların sisteme girişinden pazarlama kanallarına dağıtılmasına kadar olan tam döngüdür:
`Ingest → Checksum Dedup → Quality Gate & AI Tags → Master Creation → Watermark & Web Variants → Export`.

#### 10.2 Zorunlu Kurallar (MUST)
- Her yüklenen görsel kalite filtresinden geçmelidir (minimum çözünürlük, bulanıklık kontrolü, HDR doğrulama).
- Orijinal dosya ("Master Asset") asla üzerine yazılarak değiştirilemez; her düzenleme yeni bir türev (variant/version) üretir.
- Vitrin ve sosyal medya için otomatik boyutlandırma (WebP / AVIF) asenkron kuyruk üzerinden üretilmelidir.

#### 10.3 Kesin Yasaklar (FORBIDDEN)
- HTTP request/response döngüsü sırasında senkron görsel işleme, boyutlandırma veya filigran (watermark) basma işlemi kesinlikle yasaktır (Request timeout engeli).
- Dış resim servislerine bağımlılık (ör. rastgele placeholder görsel servisleri) yasaktır; CSS fallback veya yerel optimize statik asset kullanılmalıdır.

#### 10.4 İstisnalar
- Küçük boyutlu avatar/profil resimlerinin hızlı kırpılması senkron yapılabilir.

#### 10.5 Otomasyon & Kontrol
- `MediaProcessingJob` kuyruk testleri ve varyant oluşturma doğrulama testleri.

---

### MADDE 11: AI Provider Abstraction (Yalihan Cortex ve Yapay Zeka Soyutlaması)

#### 11.1 Tanım & Kapsam
Sistemdeki metin yazarlığı, görsel analizi, değerleme tahmini ve akıllı asistan yeteneklerinin LLM sağlayıcılarından bağımsız kılınmasıdır.

#### 11.2 Zorunlu Kurallar (MUST)
- Tüm AI çağrıları `CortexProviderInterface` veya `CortexOrchestrator` üzerinden yürütülmelidir.
- Model sağlayıcıları (Ollama/Yerel, OpenAI, Claude, DeepSeek) birer yapılandırılabilir sürücüdür. Kod mantığı hiçbir modelin proprietary parametresine kilitlenemez.
- AI çıktısı her zaman şemalı ve tip korumalı bir DTO'ya (`CortexResponseDTO`) dönüştürülmelidir (JSON Schema / Structured Output zorunludur).
- AI çağrılarının token maliyeti, gecikme süresi ve model sürümü kaydedilmelidir.

#### 11.3 Kesin Yasaklar (FORBIDDEN)
- Model çıktılarını JSON doğrulaması ve sanitize işleminden geçirmeden doğrudan veritabanına yazmak yasaktır.
- Prompt metinlerinin PHP sınıfları içerisine dağınık ve hardcoded olarak yazılması yasaktır; prompt şablonları versiyonlu registry'de tutulmalıdır.

#### 11.4 İstisnalar
- Geliştirme ortamında mock AI yanıtı döndüren `FakeCortexDriver` kullanılabilir.

#### 11.5 Otomasyon & Kontrol
- `php artisan bekci:wizard-contract` ve Cortex Mock Unit Testleri.

---

### MADDE 12: Hermes Orchestration Layer (Hermes Orkestrasyon Görev Sınırları)

#### 12.1 Tanım & Kapsam
Hermes; sistemdeki asenkron görevlerin, n8n iş akışlarının, kuyruk zincirlerinin ve AI ajan görevlerinin **saf koordinasyon motorudur**.

#### 12.2 Zorunlu Kurallar (MUST)
- Hermes'in görevi yalnızca orkestrasyondur: Job dispatch, retry politikası, timeout denetimi, rate limiting, cron scheduling ve idempotency kontrolü.
- Tüm Hermes event'leri benzersiz bir `idempotency_key` taşımalıdır.

#### 12.3 Kesin Yasaklar (FORBIDDEN)
- **Hermes Business Domain Dönüşüm Yasağı:** Hermes'in içine iş mantığı (ör. "bu villa lüks ise fiyatı %10 artır", "ilanı sahibinden portalına yükle") yazılamaz. Bu mantıklar ilgili domain servislerinde (`ListingService`, `PricingService`) kalır; Hermes sadece bu servislerin işlerini koordine eder.
- Sonsuz döngü riski taşıyan, retry limiti ve timeout'u bulunmayan headless job tanımlamak yasaktır.

#### 12.4 İstisnalar
- Basit koşullu dallanmalar (ör. `if (step1.status == SUCCESS) dispatch(step2)`).

#### 12.5 Otomasyon & Kontrol
- Hermes Event Sync denetleyici skilli (`hermes-event-sync`) ve queue worker fail log incelemeleri.

---

### MADDE 13: Event & Workflow Architecture (Olay ve İş Akışı Mimarisi)

#### 13.1 Tanım & Kapsam
Domain içindeki kritik durum değişikliklerinin (State Transitions) dış dünyaya ve diğer modüllere anons edilme standardıdır.

#### 13.2 Zorunlu Kurallar (MUST)
- Tüm event'ler geçmiş zaman kipiyle isimlendirilmelidir (`ListingPublished`, `MediaUploaded`, `PriceChanged`, `ContractSigned`).
- Her event şunları taşımalıdır: `eventId` (UUID), `occurredAt` (Timestamp), `tenantId`, `aggregateId`, `payload` (DTO).
- Event listener'lar kendi hata yönetimlerini izole etmeli, bir listener'ın çökmesi diğer listener'ların veya ana işlemin durmasına neden olmamalıdır (Queueable Listeners).

#### 13.3 Kesin Yasaklar (FORBIDDEN)
- Event payload'ı içine aktif bir DB transaction'ına bağlı canlı Eloquent Model instance'ı koyarak serialize etmek yasaktır; yalnızca ID ve immutable DTO taşınır.
- Olay fırlatmayı bir kontrol akışı mekanizması (GOTO benzeri) olarak kötüye kullanmak yasaktır.

#### 13.4 İstisnalar
- Senkron çalışması zorunlu olan güvenlik ve audit log event'leri (`UserAuthenticated`).

#### 13.5 Otomasyon & Kontrol
- Event sözleşme testleri ve Laravel Event Discovery doğrulaması.

---

### MADDE 14: API & Integration Contracts (API ve Entegrasyon Sözleşmeleri)

#### 14.1 Tanım & Kapsam
İç servisler, mobil arayüzler, n8n otomasyonları ve dış portallarla yapılan tüm veri alışverişinin kurallarıdır.

#### 14.2 Zorunlu Kurallar (MUST)
- Tüm REST uçları versiyonlanmalıdır (`/api/v1/...`, `/api/v2/...`).
- Yanıtlar standart Envelope formatında dönmelidir:
  ```json
  {
    "success": true,
    "data": { ... },
    "meta": { "timestamp": 1772840800, "version": "v1" },
    "errors": []
  }
  ```
- Mutasyon yapan (`POST`, `PUT`, `PATCH`) dış API uçlarında `Idempotency-Key` başlığı desteklenmelidir.
- API şemaları OpenAPI v3 standardında belgelenmiş olmalıdır.

#### 14.3 Kesin Yasaklar (FORBIDDEN)
- Bir API ucu geriye dönük uyumluluk (backward compatibility) sağlanmadan değiştirilemez veya alan tipi kırıcı şekilde güncellenemez (Breaking Change Yasağı).
- `500 Internal Server Error` durumunda hassas stack trace veya DB SQL sorgularının istemciye sızdırılması yasaktır.

#### 14.4 İstisnalar
- Geliştirme (local/debug) ortamında açık olan hata detayları.

#### 14.5 Otomasyon & Kontrol
- `api-contract-regression-guard` skilli ve API test suite.

---

### MADDE 15: Security, Identity & Authorization (Güvenlik, Kimlik ve Yetkilendirme)

#### 15.1 Tanım & Kapsam
Multi-tenant veri izolasyonu, kimlik doğrulama, rol ve yetki kontrolleri, secret yönetimi ve AI ajan güvenlik sınırlarıdır.

#### 15.2 Zorunlu Kurallar (MUST)
1. **Tenant Isolation (Kural 1 — En Ağır İhlal):** Hiçbir kiracı (tenant) başka bir kiracının verisini göremez veya değiştiremez. Her veritabanı sorgusu tenant scope içermek zorundadır.
2. **AI Ajan Yetki Sınırı:** Hiçbir AI ajanı veya otonom bot süper-yönetici (SuperAdmin) yetkisine sahip olamaz. Her ajanın yetkisi minimum ayrıcalık ilkesiyle (Least Privilege) sınırlı API token'ları ile kısıtlanır.
3. **Secret Zero-Trust:** Kod deposu içine hiçbir API anahtarı, şifre veya özel anahtar konamaz. Tüm sırlar `.env` veya güvenli secret manager'dan okunur.
4. **Thin Controller & FormRequest:** Gelen tüm HTTP verisi yetkilendirme (`authorize()`) ve doğrulama (`rules()`) içeren FormRequest sınıflarından geçmelidir.

#### 15.3 Kesin Yasaklar (FORBIDDEN)
- `app/` kodu içerisinde doğrudan `env()` fonksiyonunu çağırmak yasaktır (yalnızca `config('...')` kullanılmalıdır).
- `eval()`, `exec()`, `passthru()`, `shell_exec()` veya unescaped raw shell komutları çalıştırmak kesinlikle yasaktır (`ForbiddenFunctionAST`).
- CSRF korumasını devre dışı bırakmak veya CORS ayarlarında `*` (wildcard) serbest erişim vermek yasaktır.

#### 15.4 İstisnalar
- Webhook alıcı uçları (Stripe, WhatsApp, portallar) CSRF'den hariç tutulabilir; ancak webhook signature doğrulaması zorunludur.

#### 15.5 Otomasyon & Kontrol
- `scripts/tools/secret-scan.sh`, `tests/Feature/Security/`, ve `authorization-boundary-auditor` skilli.

---

## BÖLÜM 3: MÜHENDİSLİK KALİTESİ, GÖZLEMLENEBİLİRLİK VE YÖNETİŞİM (MADDELER 16 - 20)

---

### MADDE 16: Engineering Standards (Mühendislik Standartları ve Kod Düzeni)

#### 16.1 Tanım & Kapsam
Kod kalitesi, dosya hiyerarşisi, stil rehberleri, exception yönetimi ve loglama standartlarıdır.

#### 16.2 Zorunlu Kurallar (MUST)
- Tüm PHP kodu PSR-12 ve Laravel Pint standartlarına %100 uymalıdır.
- Exception hiyerarşisi domain bazlı olmalıdır (`ListingNotFoundException`, `MediaProcessingFailedException`).
- Sessiz yakalama (Silent Catch) yasaktır; her `catch` bloğu ya hatayı loglamalı, ya rethrow etmeli ya da açıkça `/** @sab-ignore-catch */` ile belgelenmelidir.
- Blade view'larında Font Awesome (`fa-`, `fas`) kullanımı yasaktır; daima `<x-icon name="..." />` bileşeni kullanılmalıdır.
- Frontend view dizin standardı:
  - `resources/views/frontend/` → `@extends('layouts.frontend')`
  - `resources/views/admin/` → `@extends('layouts.admin')`
  - `resources/views/auth/` → `@extends('layouts.guest')`

#### 16.3 Kesin Yasaklar (FORBIDDEN)
- Boş catch blokları (`catch (\Exception $e) {}`).
- Kod içinde `dd()`, `dump()`, `ray()`, `var_dump()` veya `console.log()` unutulması.
- Sabit URL string'lerinin Blade veya Controller içine hardcode yazılması (`route('name')` zorunludur).

#### 16.4 İstisnalar
- Sadece lokal test scriptlerinde geçici dump fonksiyonları kullanılabilir; git commit öncesi temizlenmelidir.

#### 16.5 Otomasyon & Kontrol
- `composer lint`, `git hooks/pre-commit`, `./scripts/tools/antigravity-preflight.sh`.

---

### MADDE 17: Testing & Quality Gates (Test ve Kalite Kapıları)

#### 17.1 Tanım & Kapsam
Yazılan kodun doğrulanması, regresyonların engellenmesi ve canlıya çıkış kapılarının yönetimidir.

#### 17.2 Zorunlu Kurallar (MUST)
- "Testi geçmeyen kod yazılamaz, merge edilemez, deploy edilemez."
- Her domain servisi için Unit Test, her dışa açık endpoint için Feature Test zorunludur.
- Testler deterministik olmalı, dış ağa bağımlı kalmamalıdır (HTTP client mocking zorunludur).
- Multi-agent ortamında test DB kirliliğini önlemek için test suite'ler izole SQLite veya transaction rollback ile çalışmalıdır.

#### 17.3 Kesin Yasaklar (FORBIDDEN)
- Test koşmadan branch merge etmek veya production'a push atmak yasaktır.
- Başarısız olan testleri `@ignore` veya comment-out yaparak bypass etmek kesinlikle yasaktır.

#### 17.4 İstisnalar
- Gerçek dış donanım/kamera testleri mock edilerek doğrulanır.

#### 17.5 Otomasyon & Kontrol
- `./scripts/tools/antigravity-full-gate.sh` ve CI/CD GitHub Actions pipeline.

---

### MADDE 18: Audit, Provenance & Traceability (Denetim İzi ve Değişiklik Soyağacı)

#### 18.1 Tanım & Kapsam
*"Bu değişikliği kim yaptı, hangi AI yaptı, hangi prompt/issue/ADR nedeniyle yaptı?"* sorusunun sistemsel olarak cevaplanabilmesidir.

#### 18.2 Zorunlu Kurallar (MUST)
- Sistemdeki her kritik veri mutasyonunda `AuditLog` kaydı oluşturulmalıdır:
  - `actor_type` (`USER`, `AI_AGENT`, `SYSTEM_JOB`)
  - `actor_id` (Kullanıcı ID veya Agent Kimliği: Antigravity, Kilo, Codex vb.)
  - `reason_code` (İlgili Task, Issue, ADR veya Prompt referansı)
  - `old_values` & `new_values` (JSON farkı)
- Agent devir-teslimlerinde (Multi-Agent Handoff) kanıt etiketi (`REPO_VERIFIED`, `TEST_VERIFIED`, `PRODUCTION_VERIFIED`) zorunludur.

#### 18.3 Kesin Yasaklar (FORBIDDEN)
- Bir yapay zeka ajanının yaptığı sistemsel bir eylemi insan kullanıcının üzerine yazmak (Identity Masking) yasaktır.
- Audit tablolarından kayıt silmek veya güncellemek yasaktır (Append-Only Log).

#### 18.4 İstisnalar
- Salt-okunur listeleme veya arama sorguları için audit tutulmaz (yalnızca mutasyonlar).

#### 18.5 Otomasyon & Kontrol
- `app/Support/Governance/Audit/` servisleri ve `agent-handoff-verifier` skilli.

---

### MADDE 19: Observability, Health & Dead-Code Control (Gözlemlenebilirlik ve Ölü Kod Kontrolü)

#### 19.1 Tanım & Kapsam
Sistem metriklerinin, hata oranlarının, kuyruk sağlığının izlenmesi ve sistemde zamanla biriken atıl kod ve şemaların temizlenmesidir.

#### 19.2 Zorunlu Kurallar (MUST)
- Tüm kritik hatalar yapılandırılmış (structured JSON) log formatında kaydedilmelidir.
- Kuyruk gecikmeleri, başarısız job sayıları ve API yanıt süreleri eşik değerlerle izlenmelidir.
- 60 günden uzun süredir hiçbir route veya servis tarafından çağrılmayan ölü kodlar (Dead Code) ve sahipsiz tablolar periyodik olarak tespit edilip temizlenmelidir.

#### 19.3 Kesin Yasaklar (FORBIDDEN)
- Hata logları içine hassas müşteri verisi, şifre veya API token'ı basılması yasaktır.
- Sağlık kontrolü (Healthcheck) uçlarının veritabanı durumunu denetlemeden daima `200 OK` dönmesi yasaktır.

#### 19.4 İstisnalar
- Arşiv amaçlı tutulan regülasyon logları.

#### 19.5 Otomasyon & Kontrol
- `php artisan bekci:health --detailed`, Laravel Telescope taraması ve statik ölü kod analiz araçları.

---

### MADDE 20: Governance, ADR & Challenger Protocol (Yönetişim, ADR ve Sorgulama Protokolü)

#### 20.1 Tanım & Kapsam
Büyük mimari kararların kayıt altına alınması ve mühendislik ekibi ile AI ajanlarının "her öneriye körü körüne evet demesini" engelleyen sorgulama mekanizmasıdır.

#### 20.2 Zorunlu Kurallar (MUST)
1. **ADR Zorunluluğu:** Mimaride, veri tabanında, dış servislerde veya kritik protokollerde yapılacak tüm yapısal değişiklikler için `Architecture Decision Record (ADR)` yazılmalıdır.
2. **Challenger Protokolü (Red-Team İlkesi):** Önerilen her mimari veya teknik değişiklik şu 4 açıdan zorunlu olarak sorgulanmalıdır:
   - **Kanıt (Evidence):** Bu değişikliğin gerekli olduğuna dair ölçüm veya somut veri var mı?
   - **Maliyet (Engineering & Operational Cost):** Geliştirme ve bakım maliyeti nedir?
   - **Risk (Failure Modes):** Bu değişiklik çökerse sistemin neresi durur?
   - **Alternatif (Simplest Alternative):** Bunu daha az kodla veya mevcut araçlarla çözebilir miyiz?
3. **Bekçi / SAAB Onayı:** Tüm anayasa değişiklikleri SAAB onayından geçmelidir.

#### 20.3 Kesin Yasaklar (FORBIDDEN)
- Somut bir iş gerekçesi ve kanıtı olmadan sadece "popüler" veya "yeni" olduğu için kütüphane veya teknoloji eklemek yasaktır (No Resume-Driven Development).
- `docs/SAB.md` ve bu anayasa belgesini usulsüzce, kontrolsüzce veya sessizce değiştirmek yasaktır.

#### 20.4 İstisnalar
- Küçük typo, dokümantasyon veya linter düzeltmeleri ADR gerektirmez.

#### 20.5 Otomasyon & Kontrol
- `red-team-architecture-challenger`, `saab-enterprise-architecture-review` yetki motorları ve `.sab/authority.json`.

---

## 🏛️ BÖLÜM 4: BAĞLAYICI ÇATI — YALIHAN ARCHITECTURE REGISTRY

Bütün bu anayasal yapıyı birleştiren ve sistemin canlı haritasını sunan tekil merkez: **`docs/architecture/REGISTRY.md`**.

### Registry Bileşenleri:
1. **Domain & Capability Catalog:** Hangi domain hangi yeteneklere sahiptir ve kod tabanında nerededir?
2. **Table & Schema Ownership Matrix:** Hangi tablonun mutlak sahibi kimdir, kimler sadece okuyabilir?
3. **Event Catalog:** Sistemde yayınlanan tüm domain event'leri, fırlatan servisler ve dinleyicileri.
4. **Contract & API Registry:** İç ve dış sözleşmelerin aktif versiyonları.
5. **AI Agent Roles & Boundaries:** Hangi AI ajanının hangi dosyalara ve operasyonlara yazma yetkisi vardır?
6. **ADR Index:** Geçmişten bugüne alınan tüm mimari kararların değişmez dizini.

---
*Yalıhan AI OS — Kararlılık, Yalınlık, Kusursuzluk.*  
*Engineering Office implements. SAAB decides. Bekçi guards.*
