# YALIHAN OS — Property Blueprint Workshop (Domain Anayasası)

> Bu doküman, YALIHAN OS (YSOS) üzerindeki en kritik iş nesnesi olan **Property (Mülk/İlan)** yapısının tüm kurallarını, yaşam döngüsünü, entegrasyonlarını, otomasyon adımlarını ve AI davranışlarını belirleyen tek doğruluk kaynağıdır (SSOT - Single Source of Truth).
> 
> Hem emlak danışmanları hem yazılım mimarları hem de AI Workforce (Hermes) için ortak referans kılavuzudur.

---

## 🗺️ İçerik Haritası
1. [PROPERTY DOMAIN (Temel)](#1-property-domain-temel)
2. [WIZARD (Adım Adım Giriş Süreci)](#2-wizard-adim-adim-giris-sureci)
3. [HARİTA & LOKASYON ZEKASI](#3-harita--lokasyon-zekasi)
4. [PROPERTY INTELLIGENCE (AI Analiz ve Skorlar)](#4-property-intelligence-ai-analiz-ve-skorlar)
5. [FOTOĞRAF & MEDYA ANALİZİ](#5-fotograf--medya-analizi)
6. [BELGE & DOKÜMAN YÖNETİMİ](#6-belge--dokuman-yonetimi)
7. [WORKSPACE MİMARİSİ](#7-workspace-mimarisi)
8. [CRM & KİŞİ İLİŞKİLERİ](#8-crm--kisi-iliskileri)
9. [AI WORKFORCE VE AJANLAR](#9-ai-workforce-ve-ajanlar)
10. [TIMELINE (İşlem Geçmişi ve İzleme)](#10-timeline-islem-gecmisi-ve-izleme)
11. [DASHBOARD & KPI METRİKLERİ](#11-dashboard--kpi-metrikleri)
12. [OTOMASYON VE ENTEGRASYON ZİNCİRİ](#12-otomasyon-ve-entegrasyon-zinciri)
13. [YAYIN KANALLARI (Airbnb / Sahibinden)](#13-yayin-kanallari-airbnb--sahibinden)
14. [ŞABLON MİMARİSİ (Property Templates)](#14-sablon-mimarisi-property-templates)
15. [RENTAL DOMAIN (Rezervasyon, Takvim ve Operasyonel Giderler)](#15-rental-domain-rezervasyon-takvim-ve-operasyonel-giderler)

---

## 1. PROPERTY DOMAIN (Temel)

### S1: Mülk (Property) ile İlan (Listing) arasındaki fark nedir?
* **Mülk (Property):** Fiziksel, somut gayrimenkuldür (Bodrum Yalıkavak'ta X adasındaki villa). Bir adresi, tapusu ve sahibi vardır.
* **İlan (Listing):** Bu fiziksel mülkün pazarlama kanallarındaki (Airbnb, Sahibinden, Booking vb.) izdüşümüdür. Bir mülkün birden fazla ilanı olabilir (örneğin hem sezonda Airbnb'de kısa dönem kiralık ilanı hem de Sahibinden'de satılık ilanı).

### S2: Bir portföyün "Yayın Tipi" nedir ve hangi değerleri alabilir?
Yayın Tipi, portföyün pazara sunulma şeklini tanımlar. Geçerli değerler:
* `Satılık` (Sale)
* `Kiralık` (Long-term Rent)
* `Günlük Kiralık` (Short-term Rent)
* `Sezonluk Kiralık` (Seasonal Rent)

### S3: Kategori ve Alt Kategori ilişkisi nasıl kurulmuştur?
* **Kategori (Category):** En üst seviye sınıflamadır: `Konut`, `Arsa`, `Ticari`, `Turistik Tesis`.
* **Alt Kategori (Sub-category):** Kategoriye bağlı alt kırılımdır: `Villa`, `Daire`, `Penthouse`, `Zeytinlik`, `Butik Otel`.
* Her alt kategori, ait olduğu ana kategorinin veri şemasını ve özellik (Feature) gruplarını miras alır.

### S4: Property Template nedir?
Bir alt kategorinin (örn: *Luxury Villa*) gereksinim duyduğu zorunlu alanları, drive klasör yapısını, AI analiz kriterlerini ve evrak checklist'ini barındıran dinamik davranış şablonudur.

### S5: Aynı ilan hem satılık hem kiralık olabilir mi?
Hayır. Yazma zinciri ve veritabanı bütünlüğü (SAB Anayasası) gereği, tek bir `Ilan` kaydı tek bir yayın tipine sahip olabilir. Eğer mal sahibi mülkü **hem satmak hem de kiralamak** istiyorsa:
1. Tek bir fiziksel `Mülk` kaydı oluşturulur.
2. Bu mülke bağlı 1 adet `Satılık` ilan kaydı, 1 adet de `Günlük/Sezonluk Kiralık` ilan kaydı oluşturulur. İki ilan birbirine fiziksel mülk ilişkisi (Relation) üzerinden bağlanır.

### S6: Günlük kiralık ile sezonluk kiralık aynı property midir?
Fiziksel olarak evet, ancak iş modeli (Business Flow) olarak farklıdırlar. Günlük kiralıkta turizm belgesi zorunluluğu, temizlik ücreti ve giriş/çıkış saatleri kritik iken; sezonluk kiralıkta depozito ve kontrat şartları ön plana çıkar. Bu yüzden ayrı ilan (Listing) kayıtları üzerinden yürütülürler.

### S7: Bir property/ilan'ın yaşam döngüsü (Lifecycle States) nelerdir?
Mülk ve buna bağlı oluşturulan Workspace şu aşamalardan geçer:
* `DRAFT` (Taslak aşaması, eksik veri girişi serbest)
* `WORKSPACE_CREATED` (Google Drive klasörleri hazır)
* `MEDIA_READY` (Fotoğraflar yüklenmiş ve PhotoAgent onayından geçmiş)
* `DESCRIPTION_READY` (DescriptionAgent tarafından başlık ve açıklamalar üretilmiş)
* `QUALITY_CHECKED` (PropertyScoreAgent tarafından 100 üzerinden en az 80 skor almış)
* `READY_FOR_PUBLISH` (PublishDecisionAgent tarafından onaylanmış veya manuel yönetici onayı verilmiş)
* `PUBLISHED` (En az bir dış kanalda canlı yayında)
* `ARCHIVED` (Pasife alınmış / Satılmış / Kiralanmış)

---

## 2. WIZARD (Adım Adım Giriş Süreci)

### S8: Yeni Portföy Wizard kaç adımdan oluşmalı?
Lüks segment gayrimenkul veri hassasiyeti ve doğruluğu açısından, YALIHAN OS Wizard yapısı **9 adımdan** oluşmaktadır:

```
[Adım 1: Kategori & Yayın Tipi]
             │
             ▼
[Adım 2: Harita & POI]
             │
             ▼
[Adım 3: Adres & Konum]
             │
             ▼
[Adım 4: Mülk Sahibi & CRM]
             │
             ▼
[Adım 5: Tapu & Evrak]
             │
             ▼
[Adım 6: Teknik Özellikler]
             │
             ▼
[Adım 7: Fotoğraflar & Medya]
             │
             ▼
[Adım 8: Fiyatlama & Sezonluk Kurallar]
             │
             ▼
[Adım 9: AI Ön İzleme & Onay] ───► [READY & PUBLISH]
```

### S9: Her adımın doğrulanma kuralları (Validation Rules) nelerdir?
* **Adım 1:** Kategori ve yayın tipi seçilmeden bir sonraki adıma geçilemez.
* **Adım 2-3:** Harita üzerinde pin bırakılmadan veya adres alanları (İl/İlçe/Mahalle) doldurulmadan ilerlenemez.
* **Adım 4:** Sistemde kayıtlı bir "Kişi" (Owner) seçilmek zorundadır. Yoksa inline olarak yeni kişi kartı açtırılır.
* **Adım 5:** Tapu numarası veya parsel bilgisi girilmelidir. Lüks segmentte yetki belgesi yüklenmesi bu adımda zorunludur.
* **Adım 6:** Metrekare ve oda sayısı alanları sayısal ve pozitif olmalıdır.
* **Adım 7:** En az 1 adet kapak resmi yüklenmelidir.
* **Adım 8:** Fiyat ve para birimi belirtilmelidir.
* **Adım 9:** AI analizi tetiklenmeden önce tüm zorunlu veri girişi kilitlenir.

---

## 3. HARİTA & LOKASYON ZEKASI

### S10: Hangi harita sağlayıcısı kullanılmalı?
YALIHAN OS hibrit bir harita yapısı kullanır:
* **Admin Panel:** Detaylı koordinat seçimi ve POI çizimleri için **Google Maps API** kullanılır.
* **Frontend / Genel Arama:** Hızlı yüklenme süresi ve maliyet yönetimi için **OpenStreetMap (Leaflet)** entegrasyonu aktiftir.

### S11: Koordinat nasıl belirlenir?
1. **Haritadan Tıklama (Manuel Pin):** Danışman harita üzerinde evi bulup tıklar.
2. **GPS / Yerinde Portföy Alımı:** Danışman arazideyken mobil cihazından "Konumumu Kullan" butonuna basarak doğrudan mobil cihaz GPS'inden çeker.
3. **Adresten Otomatik Bulma (Geocoding):** Danışmanın yazdığı açık adres üzerinden [NominatimService](file:///Users/macbookpro/dev/yalihan2026/app/Services/NominatimService.php) yardımıyla arka planda otomatik koordinat tahmini yapılır.

### S12: POI (Point of Interest - Önemli Noktalar) mesafeleri nasıl hesaplanır?
Koordinat girildiği anda asenkron kuyrukta [AkilliCevreAnaliziService](file:///Users/macbookpro/dev/yalihan2026/app/Services/AkilliCevreAnaliziService.php) tetiklenir:
* Google Places API ve OpenStreetMap üzerinden Bodrum'un önemli merkezlerine (Marina, Plaj, Hastane, Havalimanı) olan kuş uçuşu (Orthodromic) mesafe otomatik hesaplanır.
* **Format:** `Yalıkavak Marina - 1.2 km`, `En Yakın Plaj - 350 m`.

### S13: Yakındaki Yerler ve Ulaşım Süreleri AI ile otomatik yazılır mı?
Evet. `CortexSpatialIntelligenceService` koordinat ve POI bilgilerini alıp, bölgesel trafik ve yol eğim katsayılarını hesaba katarak sürüş ve yürüyüş sürelerini hesaplar ve ilan açıklamasına otomatik ekler:
* *"Yalıkavak Marina'ya arabayla 5 dakika, Scorpios Beach Club'a ise sadece 8 dakika sürüş mesafesindedir."*

---

## 4. PROPERTY INTELLIGENCE (AI Analiz ve Skorlar)

### S14: AI mülk için hangi metrikleri hesaplar?
Cortex AI motoru, gayrimenkule ait verilerden şu analizleri yapar:
* **Deniz Manzarası Skoru (Sea View Index):** Lokasyonun deniz cephe açısı ve kot farkından (eğim) manzarayı kapatacak yapı olasılıklarını hesaplar.
* **Mahremiyet Skoru (Privacy Level):** Çevre parsellerdeki yapılaşma yoğunluğu ve uydudan çekilen komşu ev mesafelerine göre havuz / bahçe alanının dışarıdan görülme oranını tahmin eder.
* **Gürültü Haritası (Noise Index):** Ana yollara, plaj kulüplerine ve marinalara olan dikey mesafeye göre desibel risk analizi yapar.
* **Yatırım Skoru (ROI Index):** [CortexROIEngine](file:///Users/macbookpro/dev/yalihan2026/app/Services/CortexROIEngine.php) ile amortisman süresi ve yıllık değer artış projeksiyonunu hesaplar.
* **Airbnb Turizm Potansiyeli (Airbnb Score):** Lokasyonun sezonluk kiralama talebine, oda sayısına ve havuz tipine göre yıllık doluluk oranı projeksiyonu sunar.

---

## 5. FOTOĞRAF & MEDYA ANALİZİ

### S15: Fotoğraflar yüklendiğinde AI ne arar?
[CortexVisionService](file:///Users/macbookpro/dev/yalihan2026/app/Services/AI/CortexVisionService.php) ve `VisionAnalysisService` yüklenen görseller üzerinde nesne tespiti (Object Detection) yapar:
* **Havuz tespiti:** Havuzun açık/kapalı, müstakil/ortak olduğunu ve su temizliğini doğrular.
* **Manzara tespiti:** Pencerelerden veya terastan görünen deniz/doğa açısını yakalar.
* **Oda sınıflandırma:** Görselleri otomatik olarak `Mutfak`, `Yatak Odası`, `Banyo`, `Salon`, `Dış Cephe` klasörlerine atar.

### S16: Fotoğraf kalite denetimleri neleri kapsar?
* Çözünürlük kontrolü (En az 1920x1080px).
* Işık ve parlaklık analizi (Karanlık fotoğraflar için uyarı verir).
* Lüks segment doğrulaması: Marka filigranı (watermark) veya yabancı nesne (plaka, insan yüzü, dağınık eşyalar) tespitinde uyarı üretir.

---

## 6. BELGE & DOKÜMAN YÖNETİMİ

### S17: Yayın tipine göre hangi belgeler zorunludur?

| Belge Türü | Satılık | Kiralık | Günlük Kiralık |
|------------|:-------:|:-------:|:--------------:|
| **Tapu Fotokopisi** | Zorunlu | İsteğe Bağlı | İsteğe Bağlı |
| **İmar Durum Belgesi** | Zorunlu (Arsa ise) | Geçersiz | Geçersiz |
| **Turizm Konut İzin Belgesi** | Geçersiz | Geçersiz | Zorunlu |
| **DASK Poliçesi** | Zorunlu | Zorunlu | Zorunlu |
| **Emlak Yetki Belgesi** | Zorunlu | Zorunlu | Zorunlu |
| **Kimlik/Pasaport Örneği** | Zorunlu | Zorunlu | Zorunlu |

### S18: Eksik belgeler süreci nasıl etkiler?
Eksik zorunlu belge olması durumunda:
* İlan `READY_FOR_PUBLISH` durumuna geçemez.
* Kalite skoru doğrudan düşürülür.
* Danışman paneline ve dashboard'a **"Kritik Evrak Eksiği"** uyarısı düşer.

---

## 7. WORKSPACE MİMARİSİ

### S19: Workspace ne zaman oluşur?
[BC001Orchestrator](file:///Users/macbookpro/dev/yalihan2026/app/Services/BC001/BC001Orchestrator.php) akışı kapsamında, danışman Wizard'ın ilk adımını (Kategori ve Yayın Tipi) onaylayıp portföyü kaydettiği anda veritabanında `PortfolioDriveWorkspace` kaydı asenkron olarak oluşturulur. Danışman sonraki adımlara devam ederken arka planda Drive API entegrasyonu tamamlanır.

### S20: Workspace'in temel bileşenleri nelerdir?
* **Veritabanı Kaydı:** Klasör ID'leri, webhook kanal bilgileri ve AI ajan bayrakları (`ai_completion_flags`).
* **Google Drive:** 12 subfolder'dan oluşan fiziksel depolama alanı.
* **Operational Cockpit:** Danışmanın tüm bu süreci tek ekrandan izlediği arayüz.

---

## 8. CRM & KİŞİ İLİŞKİLERİ

### S21: Bir portföyün ilişkili olabileceği kişi tipleri ve rolleri nelerdir?
Tek bir portföy ile birden fazla kişi ilişkilendirilebilir:
* **Mülk Sahibi (Owner):** Tapuda adı geçen yasal sahip (Zorunlu).
* **Portföy Danışmanı (Advisor):** Mülkü portföyüne alan ve pazarlayan Yalıhan danışmanı.
* **Kiracı (Tenant/Guest):** Kiralık ilanlarda aktif olarak evde kalan kişi.
* **Avukat / Hukuk Temsilcisi:** Lüks satışlarda yasal süreçleri yürüten kişi.
* **Mimar / Dekorasyon Sorumlusu:** Evin restorasyon bilgilerini sağlayan kişi.

---

## 9. AI WORKFORCE VE AJANLAR

### S22: Hangi AI ajanları hangi sıra ile çalışır?

```
[DriveAgent] (Klasörleri açar, webhook kurar)
      │
      ▼
[PhotoAgent] (Drive'a düşen fotoğrafları tarar ve etiketler)
      │
      ▼
[DescriptionAgent] (SEO başlığı ve çok dilli açıklama yazar)
      │
      ▼
[PropertyScoreAgent] (Medya ve içerik kalitesini skora döker)
      │
      ▼
[PublishDecisionAgent] (Otomatik yayın kararını onaylar/reddeder)
```

### S23: Ajanların birbirine veri aktarımı nasıl sağlanır?
Tüm veri aktarımı Hermes Event Bus üzerinden, event payload'ları ile gerçekleştirilir. Bir ajanın çıktısı (`output_payload`), bir sonraki ajanın girdisi (`input_payload`) olur. Bu sayede veri akışı tamamen durumsuz (stateless) ve event-driven olarak yürütülür.

---

## 10. TIMELINE (İşlem Geçmişi ve İzleme)

### S24: Timeline üzerinde hangi işlemler izlenebilir?
* Mülkün ilk oluşturulma anı (tarih, saat ve oluşturan danışman).
* Drive klasörlerinin başarıyla açılma raporu.
* Yüklenen fotoğrafların adet ve Vision API analiz sonuçları.
* AI ajanlarının çalışma süreleri (ms) ve ürettikleri sonuçlar.
* İlan durum geçişleri (örn: `DRAFT -> READY_FOR_PUBLISH`).
* Airbnb / Sahibinden yayınlanma webhook yanıtları.

---

## 11. DASHBOARD & KPI METRİKLERİ

### S25: Bir portföyün "Sağlık Skoru" (Health Score) nasıl hesaplanır?
[WorkspaceHealthService](file:///Users/macbookpro/dev/yalihan2026/app/Services/Workspace/WorkspaceHealthService.php) 4 ana boyutta ağırlıklı hesaplama yapar:
1. **Veri Eksiksizliği (%30):** Wizard zorunlu alanlarının doluluk oranı.
2. **Medya Kalitesi (%30):** Fotoğrafların adeti, çözünürlüğü ve AI etiket zenginliği.
3. **Evrak Durumu (%20):** Yüklenmiş zorunlu belgelerin doğruluğu.
4. **AI Analiz Durumu (%20):** Cortex AI optimizasyonlarının tamamlanmış olması.

### S26: Operasyonel Dashboard'da hangi KPI'lar gösterilir?
* **Ready %:** Portföyün yayına hazır olma oranı.
* **AI Completion %:** AI Workforce sürecinin tamamlanma durumu.
* **Missing Docs Count:** Eksik evrak sayısı.
* **Target Channels Status:** Airbnb ve Sahibinden'deki yayın durumları (Yayında / Hatalı / Beklemede).

---

## 12. OTOMASYON VE ENTEGRASYON ZİNCİRİ

### S27: Adres verisi girildiğinde hangi otomasyonlar tetiklenir?
1. Koordinatlar üzerinden harita pini netleştirilir.
2. Mahalle sınırları kontrol edilerek veritabanı ilçe/mahalle kayıtları normalize edilir.
3. POI mesafeleri hesaplanır.
4. Bölgesel piyasa analizi (Market Intelligence) tetiklenerek metrekare fiyat ortalaması çıkarılır.

### S28: Bir dosya Google Drive'a yüklendiğinde ne olur?
1. `DriveWebhookService` Google'dan push notification alır.
2. Klasör ID'sine göre hangi portföye ait olduğu bulunur.
3. Eğer `01_Fotograflar` klasörüne dosya eklenmişse, `PhotoAgent` otomatik uyarılır ve analiz süreci sıfırdan başlar.

---

## 13. YAYIN KANALLARI (Airbnb / Sahibinden)

### S29: Airbnb yayını için hangi ek alanlar gereklidir?
* Minimum gecelik konaklama süresi.
* Giriş/Çıkış saat kuralları.
* Ev kuralları (Evcil hayvan, sigara izni vb.).
* Temizlik ve servis ücretleri.

### S30: Sahibinden entegrasyonunda veriler nasıl eşleşir?
Sahibinden entegrasyonu, n8n üzerinden Sahibinden ilan şablonu formatına dönüştürülerek XML veya API feed formatında aktarılır. Başlık, oda sayısı, bina yaşı ve cephe bilgileri Sahibinden kanonik alan adlarıyla (`cephe_yonu`, `bina_yasi`) eşleştirilerek gönderilir.

---

## 14. ŞABLON MİMARİSİ (Property Templates)

### S31: Property Template (Şablon) yapısının şeması nasıldır?
Her şablon, portföyün karakteristiğine göre sistemi özelleştirir. Örneğin bir `Luxury Villa` şablonu şunları içerir:

```json
{
  "template_name": "Luxury Villa",
  "required_documents": [
    "tapu",
    "emlak_yetki_belgesi",
    "turizm_konut_izin_belgesi"
  ],
  "required_photos": {
    "min_count": 12,
    "mandatory_tags": ["exterior", "pool", "kitchen", "sea_view"]
  },
  "ai_prompts": {
    "tone": "exclusive",
    "focus_points": ["sea_view_index", "privacy_level", "design_materials"]
  },
  "publishing_channels": ["airbnb", "sahibinden", "hepsiemlak", "yalihan_portal"]
}
```

## 15. RENTAL DOMAIN (Rezervasyon, Takvim ve Operasyonel Giderler)

### S32: Yazlık kiralama operasyonlarının YALIHAN OS içindeki doğru etki alanı (domain) tasarımı nasıldır?
Yazlık kiralama, ilan özelliklerinin ötesinde doğrudan bir **Property Operation (Mülk Operasyonu)** olarak ele alınmalıdır. Doğru akış zinciri şu şekildedir:
`Property → Listing → Rental Configuration → Pricing Calendar → Availability → Reservation → Finance → Channel Sync`

### S33: Rental Domain kapsamında hangi veri nesneleri (Modeller) kullanılır?
Domain temizliğini sağlamak adına aşağıdaki 5 temel iş nesnesi SSOT (Single Source of Truth) olarak tanımlanmıştır:
1. **PropertyRentalProfile (Kiralama Profili):** Mülkün kiralama kuralları (minimum stay, check-in/out saatleri, iptal politikası, temizlik ücreti, depozito, misafir kapasitesi).
2. **PropertyPricingCalendar (Fiyatlandırma Takvimi):** Günlük fiyatlar, sezonsal çarpanlar, özel gün (Bayram vb.) fiyatları ve minimum konaklama kısıtları.
3. **PropertyAvailability (Takvim Müsaitliği):** Gün bazında doluluk durumu, engelleme sebepleri (bakım, ev sahibi kullanımı vb.) ve kanal kaynakları.
4. **PropertyReservation (Rezervasyon):** Tekil misafir konaklaması, check-in/out tarihleri, toplam tutarlar, depozitolar ve dış kanal bilgileri (Rezervasyon için Tek SSOT'tur).
5. **RentalExpense (Operasyon Maliyetleri):** Elektrik, su, havuz bakımı, bahçıvanlık gibi mülkün periyodik veya rezervasyon bazlı operasyonel maliyetleri.

### S34: Günlük Kiralık Villa şablonunun (Property Hub Template) kuralları nelerdir?
Bir mülk tipi "Günlük Kiralık Villa" olarak seçildiğinde, şablon otomatik olarak aşağıdaki 4 kural setini devreye alır:
* **Rental Rules:** minimum stay, check-in time, check-out time, cancellation policy, deposit, cleaning fee, guest capacity, child/pet policy.
* **Pricing Rules:** daily price, weekly discount, monthly discount, seasonal price, special days.
* **Calendar Rules:** Airbnb sync active, Booking sync active, owner stay allowed, maintenance block allowed.
* **Readiness Rules:** rental license required, minimum photo count, house rules required, check-in instructions required.

### S35: Dış Kanallar (Calendar Sync) ile entegrasyon stratejisi nasıldır?
* **İçeriye Doğru (Import):**
  `Airbnb / Booking / Google Calendar → iCal Import → PropertyAvailability → Conflict Detection → Workspace Timeline`
* **Dışarıya Doğru (Export):**
  `PropertyReservation → PropertyAvailability block → iCal Export / n8n → External Channels`

### S36: Rezervasyonların Finansal Entegrasyonu nasıl yürütülür?
Bir rezervasyon oluşturulduğunda sistem otomatik olarak muhasebe ve finansal outbox kayıtlarını tetikler:
`Reservation Created → Expected Revenue → Cleaning Fee → Deposit Liability → Commission → Rental Expense → Net Profit`
Rezervasyon sadece bir takvim blokajı değil, doğrudan finansal bir işlemdir.

---

## 🏛️ Karar Matrisi ve Yönetmelikler

### SAB Write Authority (Yazma Kuralları)
Hiçbir AI ajan, entegrasyon veya controller **doğrudan** `Ilan` tablosunda insert, update veya delete işlemi yapamaz. Tüm işlemler [IlanCrudService](file:///Users/macbookpro/dev/yalihan2026/app/Services/Ilan/IlanCrudService.php) üzerinden yürütülmek zorundadır.

### Tenant Isolation (Güvenlik)
Her mülk ve workspace, sahibinin `tenant_id` değeri ile izole edilmiştir. Sistem seviyesindeki hiçbir arka plan işlemi (n8n veya kuyruk job'ları dahil) bu sınırı aşamaz. Aksi durum SAB Kural 1 ihlalidir.

### Rental Operations & Deprecation Strategy (SAB Kuralı)
* Kiralama operasyonlarının tek yazma otoritesi ve SSOT'u `PropertyReservation`, `PropertyAvailability`, `PropertyPricingCalendar` ve `RentalExpense` modelleridir.
* Eski `YazlikRezervasyon` ve `IlanReservation` modelleri bağımsız yazma modelleri (write models) olarak **kullanılamaz** ve deprecate edilmiştir.
* Günlük kiralama, sezonluk kiralama ve kısa dönem villa operasyonlarının tamamı Property Hub şablon mimarisi üzerinden yönlendirilmelidir.
