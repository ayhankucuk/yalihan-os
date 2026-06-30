# Market Intelligence Engine v1 — Spec

**Tarih:** 28 Mart 2026
**Durum:** SPEC (kod yok, önce tasarım)
**Hedef:** İlanlardan, fiyatlardan, lokasyonlardan ve davranış sinyallerinden eyleme dönük içgörü üretmek

---

## 1. Amaç

Sadece "ortalama fiyat şu" demek değil. Sistem şunu söylemeli:

- Bu mahallede fiyat kırılımı değişiyor
- Bu segmentte talep zayıf
- Bu ilan fazla pahalı
- Bu ilanın dönüşüm ihtimali düşük
- Bu bölgede yeni fırsat penceresi açılıyor

**Temel kural:** AI, kirli veriyi akıllı hale getirmez. Kirli veriyi sadece daha sofistike biçimde yanlış yorumlar.

---

## 2. Mevcut Altyapı (Gerçek Durum)

Sıfırdan başlamıyoruz. Var olan güçlü parçalar:

### Var ve Çalışıyor

| Bileşen                      | Dosya                                               | Ne Yapıyor                                                            |
| ---------------------------- | --------------------------------------------------- | --------------------------------------------------------------------- |
| MarketAnalysisService        | `app/Services/Market/MarketAnalysisService.php`     | Comp analizi, expensive/cheap/fair pozisyonlama                       |
| MarketIntelligenceService    | `app/Services/Market/MarketIntelligenceService.php` | Piyasa değeri hesaplama, mahalle→ilçe→il fallback                     |
| MarketValuationService       | `app/Services/AI/MarketValuationService.php`        | IQR outlier filtreleme, medyan m2 fiyatı, trend, likidite             |
| OpportunityEngineService     | `app/Services/AI/OpportunityEngineService.php`      | 5 fırsat tipi: UNDERPRICED, HIGH_BUYER_MATCH, SEO, LOW_QUALITY, STALE |
| DealRadarService             | `app/Services/AI/DealRadarService.php`              | 8 sinyal kompozit deal skoru                                          |
| PortfolioDoctorService       | `app/Services/AI/PortfolioDoctorService.php`        | 9 sinyalli listing sağlık tanısı                                      |
| SellerStrategyService        | `app/Services/AI/SellerStrategyService.php`         | Satıcı fiyatlandırma stratejisi                                       |
| IntelligenceHub              | `app/Services/AI/IntelligenceHub.php`               | Market + quality + SEO skor agregasyonu                               |
| ConversationalAdvisorService | `app/Services/AI/ConversationalAdvisorService.php`  | 8 intent NLP orkestratörü                                             |
| CQRS Projections             | 6 projeksiyon modeli                                | MarketTrend, ListingSearch, ListingVelocity, BuyerInterest            |

### Var Ama Devre Dışı / Eksik

| Bileşen                   | Durum                               | Sorun                                    |
| ------------------------- | ----------------------------------- | ---------------------------------------- |
| `market_listings` tablosu | Migration `.disabled`               | Harici veri pipeline aktif değil         |
| MarketValuationService    | Çalışıyor ama `market_listings` boş | Sadece `ilanlar` tablosundan çalışabilir |
| Bazı skorlar              | Random jitter içeriyor              | Gerçek veri yerine mock data             |
| Birleşik skor yok         | Her servis kendi skorunu üretiyor   | Tek bakışta "bu ilan nasıl" diyemiyoruz  |

### Anahtar Tespit

**Problem parçalanma.** 10+ servis var ama:

- Birbirini bilmiyor
- Ortak skor yok
- Ortak enum yok
- Tek giriş noktası yok (ConversationalAdvisor hariç, o da NLP üzerinden)

---

## 3. Katmanlı Mimari

### Katman A — Data Foundation (Veri Güvenilirliği)

**Kural:** Analize girmeden önce veri güvenilir olmalı.

**Kullanılacak veri kaynakları (ilanlar tablosundan):**

| Alan               | Kolon                                | Güvenilirlik               |
| ------------------ | ------------------------------------ | -------------------------- |
| Fiyat              | `fiyat`, `para_birimi`               | Yüksek                     |
| Alan               | `alan_m2`, `net_m2`, `brut_m2`       | Orta (bazen boş)           |
| Kategori           | `ana_kategori_id`, `alt_kategori_id` | Yüksek                     |
| Yayın tipi         | `yayin_tipi_id`                      | Yüksek                     |
| Lokasyon           | `il_id`, `ilce_id`, `mahalle_id`     | Yüksek                     |
| İlan yaşı          | `created_at` → hesaplanır            | Yüksek                     |
| Güncelleme sıklığı | `updated_at` → hesaplanır            | Yüksek                     |
| Görüntülenme       | `goruntulenme`                       | Orta (sayaç güvenilirliği) |
| Kalite skoru       | `quality_score`, `completion_score`  | Orta                       |
| Durum              | `yayin_durumu` (IlanDurumu enum)     | Yüksek                     |
| Fiyat geçmişi      | `fiyatGecmisi` relation              | Yüksek (varsa)             |
| Oda/banyo          | `oda_sayisi`, `banyo_sayisi`         | Orta                       |
| Bina yaşı          | `bina_yasi`                          | Düşük (genelde boş)        |

**Ek sinyaller (projection tablolarından):**

| Sinyal         | Kaynak                         | Güvenilirlik |
| -------------- | ------------------------------ | ------------ |
| View velocity  | `listing_velocity_projections` | Orta         |
| Buyer interest | `buyer_interest_projections`   | Orta         |
| Market trend   | `market_trend_projections`     | Orta         |
| Favori/lead    | `listing_velocity_projections` | Orta         |

**İlk kural: Güvenilirliği "düşük" olan alanlar, skor hesaplamasında ağırlık almaz.**

**Data validation kontrolleri:**

```
- fiyat > 0 ve null değil
- m2 > 0 (en az biri: alan_m2 veya brut_m2 veya net_m2)
- il_id + ilce_id mevcut
- yayin_durumu = Aktif
- Minimum comp sayısı: 5 (altında "veri yetersiz" döner)
```

---

### Katman B — Analytics Core (AI'sız Zeka)

**Prensip:** Bu katman tamamen deterministik. AI yok. Formül açık. Doğrulanabilir.

#### B1. Mahalle Bazlı m2 Fiyat Benchmark

```
Girdi: il_id, ilce_id, mahalle_id, kategori, yayin_tipi
Çıktı: {
  medyan_m2_fiyati,
  ortalama_m2_fiyati,
  alt_band (p25),
  ust_band (p75),
  ilan_sayisi,
  guncelleme_tarihi
}
```

**Mevcut temel:** `MarketIntelligenceService.calculateMarketValue()` — mahalle→ilçe→il fallback zaten var.

#### B2. Fiyat Pozisyon Tespiti

```
Girdi: ilan.fiyat, ilan.m2, benchmark
Çıktı: {
  pozisyon: "normal" | "yuksek" | "dusuk" | "agresif_dusuk" | "supheli_yuksek",
  sapma_yuzdesi: number,  // benchmark'tan % fark
  comp_sayisi: number
}
```

**Kurallar:**

- ±%10 → normal
- +%10 → +%25 → yüksek
- +%25+ → şüpheli yüksek
- -%10 → -%20 → düşük
- -%20+ → agresif düşük

**Mevcut temel:** `MarketAnalysisService.analyze()` — expensive/cheap/fair zaten var, genişletilecek.

#### B3. İlan Yaşı Risk Analizi

```
Girdi: ilan.created_at, ilan.updated_at, ilan.yayin_durumu
Çıktı: {
  yas_gun: number,
  son_guncelleme_gun: number,
  stale_risk: "dusuk" | "orta" | "yuksek" | "kritik",
  bayatlama_skoru: 0-100
}
```

**Kurallar:**

- 0–30 gün → düşük
- 30–60 gün → orta
- 60–90 gün → yüksek
- 90+ gün → kritik
- Son güncelleme 30+ gün ise risk bir kademe artar

**Mevcut temel:** `OpportunityEngineService` STALE_LISTING_RECOVERY tipi var.

#### B4. Basit Talep Sinyali

```
Girdi: segment (kategori + lokasyon + yayin_tipi)
Çıktı: {
  talep_durumu: "artiyor" | "duragan" | "zayifliyor",
  goruntuleme_trendi: number,  // son 7 gün vs önceki 7 gün
  yeni_talep_sayisi: number,
  comp_hareket: "hizli_kapaniyor" | "normal" | "yavas"
}
```

**Veri kaynağı:** `listing_velocity_projections` + `buyer_interest_projections` + `market_trend_projections`

#### B5. Anomali Bayrakları

```
Çıktı: flags[]  // birden fazla olabilir
```

**Bayraklar:**

- `OVERPRICED` — benchmark'ın %25+ üstünde
- `UNDERPRICED` — benchmark'ın %20+ altında
- `STALE` — 60+ gün değişiklik yok
- `NO_VIEWS` — 7 gün 0 görüntülenme
- `PRICE_DROP_FAST` — son 30 günde %15+ fiyat düşüşü
- `HIGH_DEMAND_LOW_SUPPLY` — segmentte talep/arz oranı > 3

---

### Katman C — Intelligence Layer (AI Açıklama)

**Prensip:** AI karar veren değil, açıklayan olsun. Katman B'nin deterministik çıktılarını insan diline çeviren katman.

**v1'de AI sadece 2 yerde:**

#### C1. Insight Explanation

Katman B bir ilan için `{pozisyon: "supheli_yuksek", sapma: +34%, stale_risk: "yuksek"}` üretirse, AI şunu der:

> "Bu ilan Yalıkavak'taki benzer 3+1 dairelerden %34 daha pahalı fiyatlanmış. 72 gündür güncellenmemiş ve son 14 günde hiç görüntülenmemiş. Fiyat revizyonu veya yeniden yayınlama önerilir."

**Kural:** AI'ın açıklaması, skorlarla çelişemez. Skor "yüksek" diyorsa AI "uygun" diyemez.

#### C2. Aksiyon Önerisi

Basit kural tabanlı (LLM yok), ama açıklama kısmı LLM:

| Durum                     | Öneri                                     |
| ------------------------- | ----------------------------------------- |
| Overpriced + stale        | "Fiyatı %X revize et"                     |
| Underpriced + high demand | "Fırsat penceresi — fiyatı koru"          |
| Low quality score         | "Açıklamayı güçlendir, görselleri yenile" |
| No views                  | "Yeniden yayınla veya fiyat güncelle"     |
| High potential            | "Öne çıkar, reklam ver"                   |

**v1'de yok:**

- Tahmin modeli (kapanış ihtimali)
- Otomatik fiyat önerisi
- Segment risk skoru
- Predictive analytics

---

### Katman D — Action Surface (Görünürlük)

#### D1. İlan Detay Sayfası — Insight Kartı

```
┌─────────────────────────────────────────┐
│ 📊 Piyasa Durumu                        │
├─────────────────────────────────────────┤
│ Fiyat Pozisyonu:  🔴 Yüksek (+22%)     │
│ Talep Sinyali:    🟡 Durağan           │
│ İlan Yaşı Riski:  🟠 Orta (45 gün)     │
│ Kalite Skoru:     🟢 72/100            │
│                                         │
│ 💡 Öneri: Fiyatı %8-12 revize etmeyi   │
│    düşünün. Benzer ilanlar %17 daha     │
│    düşük fiyatla 3 haftada kapanıyor.   │
│                                         │
│ Son güncelleme: 28 Mar 2026 08:00       │
│ Veri kapsamı: 23 benzer ilan            │
└─────────────────────────────────────────┘
```

#### D2. Dashboard — Intelligence Kartları

```
┌─────────────────────┐  ┌─────────────────────┐
│ 🔥 Sıcak Bölgeler   │  │ ⚠️ Dikkat Gereken    │
│ Yalıkavak   +12%    │  │ 8 overpriced ilan   │
│ Gümüşlük    +8%     │  │ 5 stale (90+ gün)   │
│ Turgutreis   +5%    │  │ 3 no-view ilan      │
└─────────────────────┘  └─────────────────────┘
┌─────────────────────┐  ┌─────────────────────┐
│ 🚀 Hızlı Dönüşüm    │  │ 📊 Portföy Sağlığı  │
│ Villa kiralık: 12g  │  │ Sağlıklı:    67%    │
│ Daire satılık: 18g  │  │ Riskli:      23%    │
│ Arsa:         45g   │  │ Kritik:      10%    │
└─────────────────────┘  └─────────────────────┘
```

#### D3. Portföy Merkezi — Filtreler

Mevcut liste üzerine eklenecek filtreler:

- `overpriced` — benchmark'ın üstünde
- `stale_listing` — 60+ gün güncellenmemiş
- `high_potential` — düşük fiyat + yüksek talep
- `low_quality` — kalite skoru < 40
- `needs_action` — herhangi bir anomali bayrağı var

---

## 4. Skor Seti

### v1 Skorları

| Skor                    | Aralık | Hesaplama                                                                              | Kaynak              |
| ----------------------- | ------ | -------------------------------------------------------------------------------------- | ------------------- |
| `market_fit_score`      | 0–100  | Fiyat pozisyonu (%40) + talep sinyali (%30) + comp uyumu (%30)                         | B1 + B2 + B4        |
| `pricing_score`         | 0–100  | Benchmark sapması (%60) + fiyat geçmişi trendi (%20) + segment ortalaması (%20)        | B2                  |
| `demand_score`          | 0–100  | Görüntülenme trendi (%30) + talep sayısı (%30) + buyer interest (%20) + velocity (%20) | B4 + projeksiyonlar |
| `listing_quality_score` | 0–100  | Mevcut `quality_score` + `completion_score` + fotoğraf sayısı + açıklama uzunluğu      | Ilan modeli         |
| `opportunity_score`     | 0–100  | Underpriced (%30) + high demand (%25) + low competition (%25) + freshness (%20)        | B2 + B4 + B5        |

**Her skor yanında:**

- `aciklama: string` — kısa Türkçe açıklama
- `son_guncelleme: datetime` — ne zaman hesaplandı
- `veri_kapsami: number` — kaç comp/sinyal kullanıldı
- `guvenilirlik: "yuksek" | "orta" | "dusuk"` — comp sayısına göre

---

## 5. Mimari Yapı (SAB Uyumlu)

### Yeni Dosyalar

```
app/Services/MarketIntelligence/
├── MarketIntelligenceFacade.php       # Tek giriş noktası
├── BenchmarkService.php               # Katman B1: mahalle bazlı m2 benchmark
├── PricingPositionService.php         # Katman B2: fiyat pozisyon tespiti
├── ListingAgeAnalyzer.php             # Katman B3: ilan yaşı risk analizi
├── DemandSignalService.php            # Katman B4: talep sinyali
├── AnomalyDetector.php                # Katman B5: anomali bayrakları
├── ScoreAggregator.php                # 5 skoru birleştiren agregator
└── InsightExplainer.php               # Katman C: AI açıklama (LLM çağrısı)

app/DTOs/MarketIntelligence/
├── ListingInsightDTO.php              # Tek ilan insight çıktısı
├── BenchmarkDTO.php                   # Benchmark sonucu
├── PricingPositionDTO.php             # Fiyat pozisyonu
├── DemandSignalDTO.php                # Talep sinyali
└── PortfolioHealthDTO.php             # Portföy özet

app/Enums/MarketIntelligence/
├── PricingPosition.php                # normal|yuksek|dusuk|agresif_dusuk|supheli_yuksek
├── DemandStatus.php                   # artiyor|duragan|zayifliyor
├── StaleRisk.php                      # dusuk|orta|yuksek|kritik
├── AnomalyFlag.php                    # OVERPRICED|UNDERPRICED|STALE|NO_VIEWS|...
└── InsightConfidence.php              # yuksek|orta|dusuk
```

### Mevcut Servislerle İlişki

```
MarketIntelligenceFacade
├── BenchmarkService
│   └── uses: MarketIntelligenceService (mevcut, mahalle fallback)
├── PricingPositionService
│   └── uses: MarketAnalysisService (mevcut, comp analizi)
├── DemandSignalService
│   └── reads: listing_velocity_projections, buyer_interest_projections
├── ScoreAggregator
│   └── reads: tüm B katmanı çıktıları
└── InsightExplainer
    └── uses: YalihanCortex (mevcut, AI çağrısı)
```

**Kural:** Mevcut servisleri yeniden yazmıyoruz. Onları sarmalıyoruz.

### Controller Kullanımı

```php
// İlan detay — insight kartı
$insight = MarketIntelligenceFacade::forListing($ilan);
return view('admin.ilanlar.show', compact('ilan', 'insight'));

// Dashboard — portföy sağlığı
$portfolio = MarketIntelligenceFacade::portfolioHealth();
return view('admin.dashboard', compact('portfolio'));

// Portföy merkezi — filtreler
$listings = MarketIntelligenceFacade::filterByAnomaly('overpriced');
```

---

## 6. AI Nerede Kullanılacak / Kullanılmayacak

### Kullanılacak

| Yer                   | Nasıl                 | Neden                            |
| --------------------- | --------------------- | -------------------------------- |
| Insight açıklama      | LLM ile doğal dil     | Skor sonuçlarını insana anlatmak |
| Aksiyon önerisi metin | LLM ile cümle üretimi | Kullanıcı dostu öneri            |

### Kullanılmayacak

| Yer                  | Neden                                                 |
| -------------------- | ----------------------------------------------------- |
| Skor hesaplama       | Deterministik olmalı, tekrarlanabilir, auditlenebilir |
| Fiyat tespiti        | Formül açık olmalı, AI "tahmin etti" kabul edilemez   |
| Anomali tespiti      | Eşikler net olmalı, her çalışmada aynı sonuç          |
| Filtreleme           | Boolean mantık, AI belirsizliği burada zarar verir    |
| Karşılaştırma (comp) | SQL sorgusu, AI hallüsinasyonu riski                  |

---

## 7. Riskler

| Risk                         | Seviye     | Önlem                                                      |
| ---------------------------- | ---------- | ---------------------------------------------------------- |
| Comp sayısı az (köy/mahalle) | Yüksek     | Minimum 5 comp kuralı, fallback to ilçe                    |
| m2 verisi eksik              | Orta       | m2 yoksa fiyat/m2 skoru hesaplanmaz, "veri yetersiz" döner |
| Fiyat geçmişi yok            | Orta       | Trend hesaplanmaz, sadece anlık pozisyon gösterilir        |
| Farklı para birimleri        | Orta       | TRY'ye normalize et, kur günlük güncelle                   |
| Seasonal bias (Bodrum)       | Yüksek     | Kiralık ilanlar için sezon faktörü ekle                    |
| Stale projection data        | Orta       | Projeksiyon yaşı 7+ gün ise `guvenilirlik: "dusuk"`        |
| AI hallüsinasyon             | Düşük (v1) | AI sadece açıklama yapıyor, karar vermiyor                 |
| Performance (dashboard)      | Orta       | Skorları cache'le (1 saat TTL), batch hesapla              |

---

## 8. Doğrulama Metodu

### v1 Doğrulama Kriterleri

| Test                 | Geçme Koşulu                                                   |
| -------------------- | -------------------------------------------------------------- |
| Benchmark hesaplama  | Bodrum ilçelerinde en az 5 mahalle için benchmark üretebilmeli |
| Fiyat pozisyonu      | Bilinen overpriced ilanı "yüksek" olarak işaretlemeli          |
| Anomali tespiti      | 90+ günlük ilan "STALE" bayrak taşımalı                        |
| Talep sinyali        | View velocity yüksek segment "artiyor" göstermeli              |
| Comp sayısı kontrolü | 5'ten az comp varsa `guvenilirlik: "dusuk"` dönmeli            |
| Dashboard performans | Portföy sağlığı kartı < 500ms yüklenmeli                       |
| AI açıklama          | Skor ile çelişen açıklama üretmemeli                           |
| Null safety          | m2 olmayan ilan crash etmemeli, "veri yetersiz" dönmeli        |

### Manuel Doğrulama

```
1. Bilinen 5 overpriced ilan seç → hepsini "yüksek" işaretlemeli
2. Bilinen 5 hızlı kapanan ilan seç → "düşük/normal" fiyat + "artiyor" talep
3. 90+ günlük 5 ilan seç → hepsinde STALE bayrağı
4. Boş m2 olan 5 ilan seç → hiçbiri crash etmemeli
5. Yalıkavak villa benchmark → makul m2 fiyatı üretmeli
```

---

## 9. Fazlama

### Phase 1 — AI'sız Zeka (v1, bu spec)

- BenchmarkService
- PricingPositionService
- ListingAgeAnalyzer
- DemandSignalService (basit)
- AnomalyDetector
- ScoreAggregator
- İlan detay insight kartı
- Dashboard portföy sağlığı kartı
- Portföy filtre (overpriced / stale / high_potential)
- AI sadece insight açıklama

### Phase 2 — AI Explanation Layer (v2)

- Detaylı "neden" açıklamaları
- Similar listing karşılaştırması
- Region heatmap
- Advisor recommendation feed
- NLP ile soru-cevap ("Yalıkavak'ta daire piyasası nasıl?")

### Phase 3 — Predictive AI (v3)

- Kapanış ihtimali tahmini
- Önerilen fiyat aralığı
- Auto action suggestions
- Portfolio optimization
- Segment risk skoru
- market_listings tablosu aktivasyonu (harici veri)

---

## 10. Yapılmayacaklar (v1)

- market_listings tablosu AKTİF EDİLMEYECEK (harici veri pipeline hazır değil)
- Tahmin modeli YOK
- Otomatik fiyat önerisi YOK (sadece band gösterilecek)
- Chat assistant entegrasyonu YOK (ConversationalAdvisor zaten var, ayrı)
- Harici API çağrısı YOK (sahibinden, hepsiemlak scraping yok)
- Karmaşık ML modeli YOK

---

## 11. Bağımlılıklar

| Bağımlılık                     | Durum                     | Risk                   |
| ------------------------------ | ------------------------- | ---------------------- |
| `ilanlar` tablosu              | ✅ Production'da dolu     | Düşük                  |
| `market_trend_projections`     | ✅ Var, CQRS              | Projection güncelliği? |
| `listing_velocity_projections` | ✅ Var, CQRS              | Projection güncelliği? |
| `buyer_interest_projections`   | ✅ Var, CQRS              | Projection güncelliği? |
| MarketIntelligenceService      | ✅ Çalışıyor              | Düşük                  |
| MarketAnalysisService          | ✅ Çalışıyor              | Düşük                  |
| YalihanCortex                  | ✅ Çalışıyor              | AI maliyet bütçesi     |
| IlanDurumu enum                | ✅ Var                    | Düşük                  |
| Fiyat geçmişi                  | ⚠️ Kısmi (her ilanda yok) | Trend hesabı kısıtlı   |

---

**Tek cümlelik özet:**
Mevcut 10+ dağınık servisi tek bir facade arkasında birleştir, deterministik skorlama ekle, AI'ı sadece açıklama katmanında kullan.
