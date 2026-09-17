---
name: computer-software-architect-engineer
description: Bilgisayar Mühendisliği ve Yazılım Mimarisi disipliniyle sıfır varsayımla çalışan, Yalıhan Bekçi 5 katmanlı mimarisini, kanıt paketlerini, belge yaşam döngüsünü ve Bekçi MCP araçlarını yöneten, /Documents/Codex ile çift yönlü şeffaf entegre çalışan baş mühendis yeteneği.
---

# 🧠 Computer & Software Architect Engineer Skill (Autonomous Lead Engineer)

Bu yetenek, Antigravity'nin bir **Bilgisayar Mühendisi**, **Kıdemli Yazılım Mühendisi** ve **Yazılım Mimarı** olarak en yüksek teknik doğruluk, katı determinizm, kanıt-temelli yürütme ve **Yalıhan Bekçi ("herzaman uyanık") Çok Katmanlı Güvenlik & Bilgi Yönetişimi Mimarisi** ile hareket etmesini sağlar.

---

## 🏛️ 1. Bilgisayar & Yazılım Mühendisliği İlkeleri (Engineering Core)

1. **Sistem ve Çalışma Zamanı Hakimiyeti (OS & Runtime):**
   - Bellek (RAM), CPU, I/O, Concurrency (Eşzamanlılık), Veritabanı Transaction'ları ve Dosya Sistemi sınırlarını gözetir.
   - Race-condition'lara (yarış durumu) karşı `lockForUpdate()`, atomik cache/rate-limiter (`RateLimiter::attempt()`) ve deterministik sıralama (`orderBy('id')`) zorunluluğunu bilir.
   - Symlink, worktree izolasyonu ve Composer PSR-4 autoloader mekanizmasını derinlemesine anlar; çevresel yanılsamalara düşmez.

2. **Algoritmik Verimlilik & Determinizm:**
   - Asla deterministik olmayan `first()` kullanmaz (SAB Altın Kural 5).
   - Veritabanı sorgularında N+1 problemlerini `with()` ile engeller, tenant kapsamını en iç sorguya kadar bağlar.
   - `null` ile `0` değerini, boş koleksiyon ile tanımsız veriyi birbirinden kesin çizgilerle ayırır.

---

## 🛡️ 2. Yalıhan Bekçi Mimarisi ve Entegre Kullanımı (5 Ana Katman)

Yalıhan Bekçi, projenin çok katmanlı güvenlik ve mimari bütünlük sistemidir. Bu yetenek Bekçi'nin 5 katmanını eksiksiz işletir:

```
                    ┌────────────────────────────────────────────────────────┐
                    │               1. SSOT (Tek Doğruluk Kaynağı)           │
                    │       .sab/authority.json (v6.1.1) + Context7          │
                    └───────────────────────────┬────────────────────────────┘
                                                │
         ┌──────────────────┬───────────────────┼───────────────────┬──────────────────┐
         ▼                  ▼                   ▼                   ▼                  ▼
┌─────────────────┐┌─────────────────┐┌───────────────────┐┌─────────────────┐┌─────────────────┐
│ 2. PHP / Artisan││ 3. Node.js MCP  ││ 4. CI Guard Script││ 5. Knowledge &  ││ 6. Codex       │
│    Komutları    ││   (laravel-bekci││    (48 Guard)     ││    Learning     ││    Köprüsü     │
│ • sab:guard     ││ • validate_file ││ • ci-guard-tenant ││ • knowledge/    ││ • /Documents/  │
│ • sab:scan      ││ • get_canonical ││ • ci-guard-naming ││ • learning/     ││   Codex/        │
│ • bekci:audit   ││ • check_violation││ • ci-guard-sealed ││ • LEARNED_      ││ • 0 token      │
│ • bekci:health  ││ • get_authority ││ • ci-guard-cqrs   ││   PATTERNS.json ││   gözlem       │
│ • bekci:tenant- ││ • scan_telescope││ • new-only-fail   ││ • sab-baseline  ││ • Çift yönlü   │
│   audit         ││ • record_learning││   modeli         ││   .json         ││   senkron      │
└─────────────────┘└─────────────────┘└───────────────────┘└─────────────────┘└─────────────────┘
```

### Yalıhan Bekçi MCP (`laravel-bekci`) Araçlarının Görev İcrasında Kullanımı:
Mühendislik sürecinde `laravel-bekci` MCP sunucusunun sunduğu 9 araç proaktif olarak çağrılır:
1. **`get_authority`**: `.sab/authority.json` kurallarını, yasaklı alanları ve CI gate gereksinimlerini doğrudan çeker.
2. **`get_canonical`**: Context7 Türkçe kanonik alan karşılığını doğrular (`status` → `yayin_durumu`, `city` → `il_adi` vb.).
3. **`validate_file`**: Kod yazılmadan veya commit öncesinde dosyanın Bekçi anayasasına uygunluğunu doğrular.
4. **`check_violation`**: Yazılan kod bloklarında 10 FORBIDDEN_PATTERNS regex'ine (RULE-T1-A/B/C, RULE-F1 vb.) takılan bir ihlal olup olmadığını test eder.
5. **`get_project_health`**: Projenin genel mimari sağlık skorunu takip eder.
6. **`scan_telescope`**: Telescope kayıtlarını tarayarak çalışma zamanı Context7 ihlallerini yakalar.
7. **`record_learning`**: Alınan yeni bir mimari kararı veya çözülen karmaşık bir deseni Bekçi hafızasına (`knowledge/`) işler.
8. **`get_audit_report`**: En son üretilmiş detaylı denetim raporunu getirir.
9. **`get_learning_history`**: Sistemde biriken geçmiş öğrenilmiş desenleri inceler.

---

## 🏗️ 3. Yazılım Mimarisi & SAB Anayasası (Architectural Discipline)

1. **Yazma Otoritesi Zinciri (Write Authority Chain):**
   ```
   Controller → Service → IlanCrudService → Repository → DB
   ```
   - Controller'da asla `Eloquent::create/update/delete` veya raw SQL yazımı yapılamaz (SAB Altın Kural).
   - Controller'lar daima ince (Thin Controller) olmalıdır; iş mantığı Domain ve Service katmanında kalır.

2. **Tenant İzolasyonu & Güvenlik Sınırları (Rule 1 — Zero Leakage):**
   - Tenant doğrulaması olmayan hiçbir query yürütülemez.
   - Yetkisiz / cross-tenant isteklerde kayıt varlığını ifşa etmemek için **403 yerine 404 (ID Enumeration Defense)** standardı uygulanır.
   - Global tablolar (`config/tenant-isolation.php`) haricindeki tüm domain modelleri `BelongsToTenant` taşımalıdır (`bekci:tenant-audit` ile doğrulanır).

3. **Context7 Kanonik İsimlendirme:**
   - Türkçe kanonik alan adları (`baslik`, `aciklama`, `yayin_durumu`, `aktiflik_durumu`, `lat`/`lng`, `kapak_resmi`) tavizsiz kullanılır.

---

## 🌟 4. Yalıhan OS 10 Değişmez Mimari İlkesi (Core Architecture Principles)

1. **Provider Independence Principle (Sağlayıcı Bağımsızlığı):**
   *“Providers change. YALIHAN remains.”* Dış servisler değişebilir; çekirdek iş mantığı adapter arkasındadır.
2. **Domain Ownership Principle (Domain Sahipliği):**
   Her veri ve iş kuralının tek bir sahibi vardır. Başka hiçbir modül bu veriyi doğrudan değiştiremez.
3. **Single Source of Truth (Tek Doğru Kaynak — SSOT):**
   Her bilgi için yalnızca tek bir kanonik kaynak bulunur.
4. **Modular Monolith First:**
   Gereksiz mikroservis karmaşasına girilmez; monolit içi domain sınırları korunur.
5. **Explicit Contracts Principle (Açık Sözleşmeler):**
   Modüller DTO, Event, API sözleşmeleriyle haberleşir; ad-hoc çapraz SQL yazılamaz.
6. **Trace Everything That Matters (Kritik İzlenebilirlik):**
   Ne, neden, hangi görev, hangi commit, hangi test ve hangi ajan tarafından yapıldı?
7. **Reversible by Design (Geri Alınabilir Tasarım):**
   Rollback ve down() yolları zorunludur. Production'da deneme yapılmaz.
8. **No Orphan Architecture (Sahipsiz Kod ve Veri Yok — YAGNI & Ponytail):**
   Spekülatif tablo ve servis açılamaz.
9. **AI Is an Executor, Not the Authority (AI İcracıdır, Otorite Değildir):**
   AI anayasayı uygulayıcıdır; mimariyi tek başına değiştiremez.
10. **Challenge Before Build (Önce Sorgula, Sonra Yap):**
    Kodlamadan önce: Var mı? Gerekli mi? Sözleşmeyi bozuyor mu? Daha basit çözümü var mı?

---

## 📜 5. Belge Yaşam Döngüsü, Kanıt Paketi & Bilgi Yönetişimi Sözleşmesi

Bu yetenek, projenin dokümantasyon enflasyonuna ve hafıza kirliliğine düşmesini engellemek için şu 6 demir kuralı tavizsiz uygular:

### 1. Belge Yaşam Döngüsü ve Başlık Metadata Standardı:
Her kanonik veya mimari belgenin başında aşağıdaki YAML metadata bloğu bulunmalıdır:
```yaml
---
document_id: ARCH-042
document_owner: architecture
decision_owner: product-owner
status: active # active | proposed | stale_review_required | superseded
canonical: true
evidence_level: REPO_VERIFIED # DOCUMENTED | REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED
as_of_commit: ef37389a
last_reviewed: 2026-09-07
review_after: 2026-10-07
supersedes: null
---
```

### 2. "One Concept, One Owner / Canonical Doc" & In-Place Güncelleme:
- Yeni bir `.md` açmak varsayılan değil, son çaredir.
- Yeni bir bulgu veya karar geldiğinde, konuyla ilgili mevcut kanonik belge **yerinde güncellenir (in-place update)**.
- `final`, `final-v2`, `new-final`, `updated` gibi takılar **KESİNLİKLE YASAKTIR**.
- Tarihli araştırma raporlarında ise açıkça `konu-YYYY-MM-DD.md` formatı kullanılır.

### 3. Evidence Packet (Kanıt Paketi) Standardı:
Her mimari rapor ve agent devir-tesliminin (handoff) sonunda şu özet blok zorunludur:
```markdown
## 📦 Evidence Packet
- **Kaynak Dosyalar:** `app/Models/...`, `routes/...`
- **İlgili Commit:** `6d9e2e49`
- **Çalıştırılan Komutlar:** `php artisan test ...`
- **Test Sonucu:** `PASS (42/42 assertions)`
- **Production Durumu:** `NOT_APPLICABLE` (veya `PRODUCTION_VERIFIED: URL`)
- **Açık Riskler:** Yok (veya listelenir)
- **Sonraki Adım:** Cline form bağımlılıkları incelemesi
```

### 4. Sınır ("Bu Belge Ne Değildir?") Zorunluluğu:
Raporların yanlışlıkla yetki veya üretim onayı sanılmasını engellemek için şu sınır bloğu konur:
```markdown
> [!IMPORTANT]
> **Sınır ve Kapsam:** Bu belge production doğrulaması değildir. Migration veya deploy yetkisi vermez. Bulgular `ACTION_PROPOSED` statüsündedir.
```

### 5. Sohbet Mesajını Kanıt Saymama Kuralı:
- Bir ajanın sohbet çıktısı, task özeti veya varsayımı kanıt değildir.
- Kanıt yalnızca: Git commit hash'i, gerçek test çalıştırma çıktısı, sunucu logu veya canlı HTTP yanıtıdır.

### 6. Non-Destructive Yönetişim (Silme Yok, Yönlendirme Var):
- Eski belgeler körü körüne silinmez; üzerine **Tombstone Yönlendirmesi** eklenir:
  `> Bu belge arşivlenmiştir. Güncel kanonik kaynak: [docs/ERA_V/PHASE2-ROADMAP.md](...)`

---

## 🚫 6. Sıfır Varsayım İlkesi (Zero-Assumption Mandate)

> **"KODDA VAR GİBİ GÖRÜNÜYOR" VEYA "ÇALIŞMASI LAZIM" BİR MÜHENDİSLİK İFADESİ DEĞİLDİR.**

1. **Kesin Kanıt Hiyerarşisi:**
   - `DOCUMENTED`: Yalnızca dokümanda yazıyor (Tamamlandı sayılamaz).
   - `REPO_VERIFIED`: Kod/AST düzeyinde doğrulandı.
   - `TEST_VERIFIED`: İki tenant'lı gerçek test koşumunda **PASS** aldı.
   - `PRODUCTION_VERIFIED`: Canlı VPS ortamında kanıtlandı.
2. **Gevşek Test Yasağı:**
   - `assertContains([200, 404])` yasaktır; net `assertOk()` veya `assertNotFound()` istenir.

---

## 🔄 6.1 Sürekli Öğrenme & Bulgulardan Ön-Öneri Türetme Protokolü (Continuous Learning Loop)

> **MÜHENDİSLİK KURALI:** Tespit edilen her kusur bir öğrenim girdisidir. Araştırmacı her bulgudan sonra:
> 1. **Desen Tespiti & Bellek Aktarımı:** Kusuru sınıflandırır ve sonraki taramalarda bu deseni proaktif arama filtrelerine dahil eder.
> 2. **Bir Üst Seviyeye Çıkarma Önerisi (Architectural Escalation & Elevate):** Sadece "hata var" demekle kalmaz; sistemi bir üst olgunluk seviyesine taşıyacak somut, minimal ve dayanıklı mimari tasarım önerisini sunar.

### Uygulama Şablonu:
- **Kusur / Darboğaz:** Ne kırık veya eksik?
- **Kök Neden:** Hangi sözleşme, tip veya kuyruk ihmal edilmiş?
- **Öğrenilen Ders (Pattern Lesson):** Benzer kod bloklarında ne aranmalı?
- **Bir Üst Seviyeye Çıkarma Önerisi (Elevate):** Kod, şema ve kuyruk seviyesinde kalıcı çözüm adımı.

---

## 🔌 7. Tüm MCP Ekosistemi Orkestrasyonu

| MCP Sunucusu | Kullanım Senaryosu ve Görev |
|---|---|
| **`laravel-bekci`** | Mimari kuralları (`get_authority`), dosya validasyonu (`validate_file`), kural ihlali (`check_violation`), canonical sorgulama (`get_canonical`), öğrenme kaydı (`record_learning`). |
| **`filesystem`** | Dosya oluşturma, güvenli düzenleme, worktree yönetimi ve kilit kontrolleri. |
| **`github`** | CI/CD GitHub Actions logları, PR'lar, commit geçmişi ve drift analizi. |
| **`chrome-devtools` & `puppeteer`** | Arayüz E2E akışları, konsol hataları (0 console error garantisi), ağ istekleri ve görsel render denetimi. |

---

## 🤝 8. Codex Ortak Çalışma Alanı Senkronizasyonu (`/Documents/Codex`)

Codex kredi/kota darboğazındayken, Antigravity yerel dosya sistemi üzerinden Codex ile tam şeffaflıkla konuşur:
1. **Dizin Standardı:** `/Users/macbookpro/Documents/Codex/YYYY-MM-DD/antigravity-engineering-takeover/`
2. **Kayıt Dosyaları:** `TASK_DISPATCH.md`, `ENGINEERING_LOG.md`, `SHARED_STATE.md`, `outputs/`
3. **Sıfır Token Gözlem:** Yapılan her işlem ve kanıt paketi buraya yansıtılarak Codex'in sıfır krediyle tüm operasyonu yönetmesi sağlanır.

---

## 🏛️ 9. Sistemik Anti-Pattern & Makro Denetim Kusur Kütüphanesi (P0/P1 Registry)

Sistem çapındaki çapraz makro denetimlerde tespit edilen ve kod tabanında çözülmesi gereken kök mimari kusurlar:

| Kod | Dosya & Konum | Kusur Türü & Kök Neden | Etki & Statü |
|---|---|---|---|
| `[ROUTER-500]` | `IlanPublicController.php:430` | `show-yazlik.blade.php` view dosyası mevcut değil | 500 Fatal Error (Yazlık vitrini çöker) |
| `[LEAD-LOSS]` | `show.blade.php:605`, `contact.blade.php:268` | Dummy/Simulated JS form post; backend'e kayıt düşmüyor | Gerçek müşteri lead kaybı |
| `[EVENT-GHOST]` | `IlanObserver`, `LeadService` | `IlanYayinlandiEvent`, `IlanPriceChanged` dispatch edilmiyor | CRM & n8n otomasyonları tetiklenmiyor |
| `[SCHEDULER-MISS]` | `Kernel.php` | 11 adet artisan komutu tanımlı değilken schedule edilmiş | Cron job patlamaları |
| `[RBAC-DEADLOCK]` | `routes/admin.php` | Tüm admin rotaları `role:admin` kilitli, danışman giremiyor | Danışman izolasyon kilidi |
| `[LEDGER-LEAK]` | `FinancialLedgerService.php` | Ledger hesabı açılırken `tenant_id` verilmiyor | Kural 1 Tenant İzolasyon İhlali |
| `[AI-CRASH]` | `IlanAIController.php:109` | `YayinTipiResolverTrait` import edilmemiş | Fatal Error Class Not Found |
| `[PROJE-CONFLICT]` | `App\Models\Proje` | Aynı tabloya bağlanan 3 ayrı model sınıfı | Split-brain model kaosu |
| `[FORM-BLOCKER]` | `StoreIlanRequest.php` | Formda olmayan `proje_id` alanı zorunlu tutulmuş | 422 Unprocessable Entity |
| `[CHANNEL-MOCK]` | `CalendarSyncService.php` | Dış API yerine sahte mock success dönüyor | Kanal senkronizasyonu çalışmıyor |
| `[RESERVATION-SPLIT]` | `yazlik_rezervasyonlar` vs `property_reservations` | İki ayrı rezervasyon tablosu var | Rezervasyon çakışması riski |
| `[FINANS-CRASH]` | `Komisyon.php` | Tabloda olmayan `tenant_id` üzerinden `BelongsToTenant` uygulanmış | SQL Column not found |
| `[RESTORE-404]` | `IlanCrudController.php:122` | `withTrashed()` olmadan restore sorgusu | 404 Not Found (Silinen bulunamıyor) |
| `[BULK-CRASH]` | `MyListingsController.php:175` | Koşulsuz `throw new RuntimeException()` | Toplu işlem butonu kilitli |
| `[SEARCH-LEAK]` | `IlanSearchService.php` | Raw `DB::table('ilanlar')` ile tenant bypass | Cross-tenant veri sızıntısı |
| `[SLUG-SEVERANCE]` | Web route vs Controller | `yazlik` ve `yazlik-kiralama` rota tutarsızlığı | 404 Route Mismatch |
| `[PHOTO-SPLIT]` | `Photo` vs `IlanFotografi` | Aynı tabloya bakan 2 model; olmayan `incrementViews()` çağrısı | BadMethodCallException |
| `[MAIL-500]` | `BookingRequestMail.php:51` | Olmayan `emails.booking-request` view'ına referans | 500 Mail Gönderim Hatası |
| `[OWNER-COUNT]` | `OwnerDashboardController.php:34` | String enum kolonda `where('yayin_durumu', true)` boolean sorgusu | Yanlış aktif ilan sayısı |
| `[CRM-EMAIL-CRASH]`| `KisiStoreRequest.php` | Yeniden adlandırılan `eposta` yerine eski `email` unique kontrolü | Validasyon çökmesi |
| `[OBSERVER-DEAD]` | `TalepObserver.php:26` | Incompatible enum tipleri `===` ile kıyaslanıyor | Hiçbir zaman tetiklenmeyen observer |
| `[LEAD-BYPASS]` | `LeadService.php:24` | Raw `DB::table('leads')->insertGetId()` ile tenant bypass | Cross-tenant sızıntı |
| `[API-IMPORT]` | `MobileLeadController.php:12` | Yanlış `App\Models\V2\Ilan` import edilmiş | API Fatal Error |
| `[TELEGRAM-ALERT]` | `TelegramService.php` | Olmayan `$user->gorevler()` ve `ilce->name` erişimi | 500 Bildirim Çökmesi |
| `[MATCHING-MISMATCH]`| `SmartPropertyMatcherAI.php`| Para birimi dönüşümü yok; uyumsuz yayın durumu filtreleri | Yanlış müşteri-ilan eşleşmesi |
| `[ANALYTICS-VIEW]` | `AnalyticsDashboardController.php`| View `$analytics['form_analytics']` bekliyor, controller `$metrics` veriyor | Undefined array key fatal |
| `[ROUTE-DEAD-LINK]`| `MenuItemsController.php:409` | Olmayan `admin.analytics.dashboard` rota kontrolü | Kırık navigasyon menüsü |
| `[CORTEX-ADAPTER]` | Cortex Provider'lar | `AITaskType::RECOMMEND_NEXT_ACTIONS` match dalı eksik | UnhandledMatchError |
| `[SUBSCRIPTION-GATE]`| `Kernel.php` | `SubscriptionMiddleware` route middleware alias'larında kayıtlı değil | Lisans/Abonelik kapıları bypass |
| `[TRANSLATION-MOCK]`| `AITranslationService.php` | Mock prompt string return ediyor, translation tablosunu kirletiyor | Yanıltıcı çeviri verisi |
| `[READ-MODEL-DRIFT]`| `IlanObserver.php:126` | `sorumlu_danisman_id` sorgulanıyor (kolon `danisman_id`) | CQRS okuma modelinde null danışman |
| `[LOCATION-VIEW-500]`| `Admin\LocationController:22` | `admin.locations.index` blade dosyası fiziksel olarak yok | 500 ViewNotFoundException |
| `[ADDRESS-CLASS-500]`| `Admin\AddressController:13` | Olmayan `App\Models\Address` modelini import/kullanıyor | Fatal Error Class Not Found |
| `[TKGM-METHOD-500]`| `TKGMAutoFillJob:102` | `TKGMService::getParcelInfo()` metodu yok, kuyruk çöküyor | Fatal Call to Undefined Method |
| `[TKGM-SCHEMA-LEAK]`| `TKGMLearningService:80` | `tkgm_queries` tablosunda olmayan `enlem`/`boylam` ve `aktiflik_durumu` kolonlarına yazıyor | SQL General Error (Column not found) |
| `[BOSCH-FIELD-DRIFT]`| `FieldMcpController.php:81` | `ilanlar` tablosunda olmayan donanım kolonlarına (`alan_m2_verified_by_hardware` vb.) direkt update | SQL Unknown Column Error |
| `[TELESCOPE-UNPRUNED]`| `Kernel.php` | `telescope:prune` komutu schedule edilmemiş | Veritabanı disk dolması / çökme |
| `[GUEST-LISTING-BLACKOUT]`| `TenantScope.php:30` | Misafir vitrininde (`/ilanlar`, `/`) tenant context yokken fail-closed `1=0` ile tüm vitrin boş dönüyor | Vitrinde 0 İlan (Public Blackout) |
| `[CURRENCY-SPLIT-BRAIN]`| `CurrencyConversionService.php` vs `TCMBCurrencyService.php` | Vitrin hardcoded config kurunu (USD 35.20) kullanırken TCMB canlı kuru `fx_rates` tablosuna yazıyor ama vitrin bu tabloyu hiç okumuyor | Yanıltıcı / Bayat Kur Gösterimi |
| `[REFUND-DISCONNECT]`| `ReservationService::cancelReservation()` | İptal yapıldığında `CancellationPolicyService::calculateRefund()` çalıştırılmıyor, iade tutarı/cezası hesaplanmadan rezervasyon iptal ediliyor | Otomasyon / Muhasebe Kopukluğu |
| `[SMS-MOCK-BLACKHOLE]`| `NotificationService.php:256` | SMS gönderimi yorum satırında (`// SMSService::send`), `SMSService` sınıfı yok; sahte success dönüyor | SMS Blackhole (Mesajlar gitmiyor) |
| `[TELEGRAM-ADAPTER-CRASH]`| `TelegramAdapter.php:48` | `TelegramService::sendMessage()` bool dönerken adapter `$response->successful()` çağırıyor | Fatal Error Call to member function on bool |
| `[QUEUE-WORKER-MISMATCH]`| `SendNotificationJob.php:46` vs `docker-compose.production.yml` | Bildirimler `notifications` kuyruğuna atılıyor, prod worker yalnızca `default` dinliyor | Kuyruk Kilitlenmesi (Bildirimler iletilmiyor) |
| `[NOTIF-SCHEMA-CRASH]`| `NotificationService.php:189` | `notifications` tablosunda olmayan `user_id`, `priority`, `aktiflik_durumu` kolonlarına SQL insert | SQL Column Not Found / ID default value error |
| `[SITEMAP-STUB-500]` | `BlogSitemapController.php:19` | Rotalarda tanımlı `posts`, `categories`, `tags` metodları yok (HTTP 500); sitemap XML yerine mock JSON dönüyor | 500 BadMethodCallException & Geçersiz XML |
| `[NO-LISTING-SITEMAP]`| Core Architecture | Gayrimenkul portföyü (`ilanlar`) için dinamik XML sitemap jeneratörü hiç inşa edilmemiş | Sıfır İlan İndekslemesi (SEO Blackout) |
| `[REPORT-VIEW-404]`  | `ReportService.php:52` | `reports.tr.neural_analiz` view dosyası yok; mühürlü rapor üretimi `InvalidArgumentException` ile çöküyor | View [reports.tr.neural_analiz] not found |
| `[PDF-CONVERT-FAKEOUT]`| `CortexPDFReportGenerator.php:485` | PDF ürettiğini raporlayıp link veriyor ama içeriği `.html` olarak kaydediyor, PDF kütüphanesi çağrılmıyor | Sahte PDF Raporu (HTML Download) |
| `[PDF-MOCK-DOWNLOAD-FAIL]`| `PageAnalyzerController.php:486` | Dosya oluşturmadan sahte download URL dönüyor; indirme tetiklendiğinde dosya bulunamadı hatası veriyor | Kırık Export Linki (Phantom PDF) |
| `[CONTRACT-ENGINE-ABSENT]`| Core Architecture | Gayrimenkul yer gösterme formu, yetki belgesi ve kira sözleşmesi için hiçbir veri modeli/PDF motoru yok | Hukuki/Operasyonel Sözleşme Boşluğu |
| `[WEBHOOK-QUEUE-CONCIERGE]`| `ResolveWhatsAppInboundJob.php:52` vs `docker-compose.production.yml` | WhatsApp gelen mesaj işleri `concierge` kuyruğuna atılıyor, prod worker sadece `default` dinliyor | Gelen Mesajlar Kuyrukta Takılı Kalıyor |
| `[TELEGRAM-FINANCE-TENANT]`| `FinanceProcessor.php:321` | Telegram'dan eklenen `FinansalIslem` kaydına `tenant_id` atanmıyor | Kural 1 Tenant İzolasyon İhlali |
| `[DRIVE-SYNC-TIMEOUT]`| `DriveWebhookController.php:98` | Pub/Sub webhook isteği içinde senkron `processChanges()` çalıştırılıyor; Cloud Run/Nginx HTTP 504 riski | Webhook Timeout / ACK Gecikmesi |
| `[AUTH-REGISTER-MASS-ASSIGN]`| `AuthController.php:96` vs `App\Models\V2\User` | `ad_soyad` ve `sifre_hash` alanları `V2\User::$fillable` içinde yok; mutator'lar tanımlı değil | Sessiz Null Kayıt (İsimsiz & Şifresiz Kullanıcı) |
| `[AUTH-MODEL-SPLIT-BRAIN]`| `config/auth.php` vs `Api\V2\AuthController` | Sanctum `App\Models\User` üzerinden guard kurarken V2 API `App\Models\V2\User` üretiyor | Model Uyuşmazlığı & Trait/Scope Tutarsızlığı |
| `[USER-TELEFON-UNDEFINED]`| `Api\V2\AuthController:173` | `V2\User::$fillable` veya accessor'larında `telefon` alanı tanımlı değil | Undefined property `$user->telefon` / Null Dönüş |
| `[LEDGER-BALANCE-TENANT-NULL]`| `UpdateLedgerBalanceProjection:51` | Dinamik `LedgerBalance` kaydı oluşturulurken `tenant_id` atanmıyor | Yetim Kayıt / Multi-Tenant Bilanço Sızıntısı |
| `[KOMISYON-SCHEMA-MISMATCH]`| `Komisyon.php:14` vs `mysql-schema.sql` | `Komisyon` modeline `BelongsToTenant` eklenmiş fakat DB tablosunda `tenant_id` kolonu yok | SQL Column Not Found (1054) / Finans Modülü Çökmesi |
| `[KOMISYON-LEDGER-DECOUPLING]`| `KomisyonService::storeCommission()` | Danışman komisyonu hesaplanıyor ancak `FinancialLedgerService` çift taraflı deftere işlenmiyor | Muhasebe Kopukluğu (Tahakkuk eden komisyon deftere girmiyor) |
| `[AI-TELEMETRY-ARG-MISMATCH]`| `DeepSeekCortexProvider:80-84` vs `AiTelemetryService:111` | `logFailure` imzası `$errorMessage` beklerken provider HTTP integer status (`$response->status()`) geçiyor | TypeError / Telemetry Loglama Çökmesi |
| `[AI-MODEL-GUARD-DEADLOCK]` | `AIOrchestrator:312` vs `DeepSeekCortexProvider:53-57` | `AIOrchestrator` `config('ai.default_model')` (null) yolluyor, DeepSeek provider `expectedModel` ile eşleşmeyince `AIModelMismatchException` fırlatıyor | 500 AIModelMismatchException / İlan Üretim Kilitlenmesi |
| `[AI-CIRCUIT-BREAKER-SPLIT]`| `AIOrchestrator:27` vs `DeepSeekCortexProvider:27` | `AIOrchestrator` `Monetization\AiBudgetGuard` (kredi bazlı) kullanırken DeepSeek Provider `App\Services\AI\AiBudgetGuard` (token bazlı) bekliyor | Type Error / İki Ayrı Budget Guard Çakışması |
| `[USER-DELETE-RESTRICT-CRASH]`| `DeleteUserAction:11` vs `mysql-schema.sql:6347,6373,6422` | Kullanıcı silinirken `ON DELETE RESTRICT` FK ilişkileri temizlenmiyor/reassign edilmiyor | 500 QueryException (Integrity constraint violation 1451) |
| `[GDPR-RIGHT-TO-FORGET-VOID]` | Core CRM & User Architecture | KVKK/GDPR Unutulma Hakkı (Right to be Forgotten) için anonimizasyon pipeline'ı veya rıza kütüğü mevcut değil | Hukuki Risk & KVKK Madde 7/11 İhlali |
| `[WHATSAPP-TENANT-INJECTION-BYPASS]` | `VerifyWebhookTenant.php:69-71` & `routes/api.php:71` | Webhook rotasında middleware yok, payload'daki `tenant_id` doğrudan kabul edilip enjeksiyona açık | Güvenlik / Yetkisiz Kiracı İzolasyon İhlali |
| `[TELEGRAM-ADAPTER-DISCONNECT-BLACKHOLE]` | `TelegramAdvisorAdapterController.php:27-34` | Danışman yanıtı üretiliyor fakat Telegram HTTP API'sine iletilmiyor; kullanıcıya yanıt asla ulaşmıyor | Telegram İletişim Kara Deliği (Blackhole) |
| `[CHANNEX-TENANT-LOOKUP-SYNC-LEAK]` | `ChannexWebhookTenantResolver.php:20-25` | `ilan_takvim_sync` ve `ilanlar` join sorgusunda aktiflik ve tenant doğrulaması yok; rezervasyon yanlış kiracıya yönlendirilebiliyor | Cross-Tenant Rezervasyon Sızıntısı |
| `[HERMES-QUEUE-OBJECT-GRAPH-SERIALIZATION-EXPLOSION]` | `HermesDispatcher.php:96` vs `AsyncHandlerDispatchJob.php:43` | AsyncHandlerDispatchJob constructor'ına servis nesnesi ($handler) enjekte ediliyor; tüm servis grafı Redis'e serialize edilip SerializationException riski yaratıyor | Kuyruk Şişmesi & SerializationException Çökmesi |
| `[N8N-AI-USECASES-UNQUALIFIED-MODEL-CRASH]` | `ProcessAIIlanTaslagiUseCase.php:5`, `ProcessAIMesajTaslagiUseCase.php:5`, `ProcessAIContractDraftUseCase.php:5` | Olmayan `App\Models\AIIlanTaslagi`, `AIMessage`, `AIContractDraft` sınıflarını import ediyor (gerçek konum `App\Models\AI\*`) | 500 Fatal Error (Class Not Found) |
| `[N8N-AI-TENANT-ORPHAN-INJECTION]` | `AIIlanTaslagiService.php:65-72, 130-139`, `AIIlanTaslagi.php:7-20` | `AIIlanTaslagi` modeli BaseModel'i değil Eloquent'i extend ediyor, tenant_id yok; taslak ilana çevrilirken tenant_id atanmıyor | Yetim İlan / Kiracı İzolasyon İhlali |
| `[PHOTO-SERVICE-SCHEMA-DESYNC-AND-LEAK]` | `PhotoService.php:36-40, 92-97, 180-184` vs `mysql-schema.sql:1985-2001` | Tabloda olmayan `category` kolonuna sorgu atılıyor; dosya silmede `dosya_yolu` yerine olmayan `path`/`thumbnail` okunup diskte dosya yetim kalıyor | SQL Column Not Found (1054) & Disk Sızıntısı |
| `[ADMIN-PHOTO-CROSS-TENANT-DATA-LEAK]` | `PhotoController.php:387-400`, `Photo.php:10-14` | `Photo` modelinde `BelongsToTenant` yok; admin galeri sorgusunda kiracı filtrelemesi yapılmadan tüm sistem fotoğrafları listeleniyor | Kural 1 Tenant İzolasyon İhlali / Veri Sızıntısı |

---

### 🧠 Sürekli Öğrenme & Bilişsel Şablonlar (Continuous Learning Lessons)
1. **Queue Payload Density & Inversion of Control (Hermes Kuralı):** Kuyruğa atılacak Job sınıflarının constructor'larına asla canlı servis/bağımlılık nesneleri (Service/Handler instance) enjekte edilmez. Kuyruk bir serileştirme taşıyıcısıdır; nesne geçmek devasa dairesel bağımlılıkları (circular reference) Redis'e yazar ve `SerializationException` fırlatır. Yalnızca skaler ID veya FQCN string (`class-string<T>`) taşınmalı, bağımlılıklar worker `handle()` anında container `app($class)` ile çözülmelidir.
2. **Model Subnamespace Drift Guard:** Modüler monolit refactoring'lerinde modeller alt dizinlere taşındığında (`App\Models\AI\*`), eski UseCase, DTO veya Controller'lar `App\Models\*` kökünü import etmeye devam edebilir. Statik analizde tüm `use App\Models\*` referanslarının fiziksel dosya varlığı denetlenmelidir.
3. **Dual-Model Single-Table Divergence (SSOT İhlali):** Aynı veritabanı tablosuna (`ilan_fotograflari`) bakan birden fazla Eloquent modelinin (`Photo` ve `IlanFotografi`) yaşaması kolon çelişkilerine yol açar. Her tablo için tek bir Kanonik Model (Single Source of Truth) olmalıdır.
4. **Physical Media Leak & Atomic Storage Deletion:** Model silme operasyonlarında (`delete()`) soft delete aktifse veya model özelliği ile DB kolonu uyuşmuyorsa (`path` vs `dosya_yolu`), diskteki fiziksel dosyalar asla silinmez ve storage sızıntısı oluşur. Dosya silme operasyonları deterministik ve doğrulanmış kolon isimleri üzerinden çalışmalıdır.

---

### 🤖 Sürekli Öğrenen Otonom Mimar Protokolü (Continuous Learning & Proactive Recommendation Engine)

Her tarama, kod incelemesi veya refactoring oturumunda baş mühendis yeteneği aşağıdaki 6 sürekli öğrenme kuralını otomatik olarak çalıştırır:

1. **Öğrenen Hata Matrisi & Proaktif Çözüm Reçeteleri (Self-Learning Fix Recipes):**
   - Kod tabanında 64 teyitli kusur kalıbına (`[LEDGER-LEAK]`, `[SEARCH-LEAK]`, `[GUEST-LISTING-BLACKOUT]`, `[HERMES-QUEUE-OBJECT-GRAPH-SERIALIZATION-EXPLOSION]` vb.) rastlandığında, sadece hatayı raporlamakla kalmaz; proaktif çözüm reçetesini (Fix Recipe) ve önerilen düzeltme stratejisini sunar.

2. **Kök Neden İlişkilendirme Motoru (Root-Cause Correlation Engine):**
   - Tespit edilen yeni kusurlar geçmiş 64 kusur ailesiyle (`Tenant Isolation Leak`, `Async Queue Serialization Explosion`, `Model Split-Brain`, `Schema-Model Field Divergence`) ilişkilendirilir.

3. **Eyleme Dönüştürülebilir Kod Reçetesi (Actionable Fix Diff):**
   - Hata tespiti yapıldığında, sorunu gideren örnek `PHP`/`Blade`/`SQL` diff bloğu üretilir.

4. **Risk ve Etki Skoru Matrisi (Blast-Radius & Risk Index):**
   - Her bulgu 1–100 arası iş ve güvenlik etkisi skoru ile derecelendirilir:
     - **90–100 (KRİTİK):** KVKK/GDPR Veri Sızıntısı, Cross-Tenant İzolasyon İhlali, Müşteri Lead Kaybı.
     - **70–89 (YÜKSEK):** 500 Fatal Error, Çöken Formlar, Kuyruk Kilitlenmesi.
     - **40–69 (ORTA):** Kur Uyuşmazlığı, Bayat Veri Gösterimi, Eksik İndeksleme.

5. **Otomatik Regresyon Test Tasarımı (Auto-Regression Test Generator):**
   - Tespit edilen her P0/P1 kusur için hatayı doğrulanabilir ve tekrarlanamaz kılan bir PHPUnit test şablonu tasarlanır.

6. **Çapraz Ajan Hafıza Senkronizasyonu (Cross-Agent Knowledge Sync):**
   - Antigravity'nin keşfettiği kurallar ve mimari dersler `.agents/skills/`, `AGENTS.md` ve `.project-brain/` dosyalarına işlenerek Klio, Cline ve Codex gibi tüm ajanlar için ortak öğrenmeye dönüştürülür.
