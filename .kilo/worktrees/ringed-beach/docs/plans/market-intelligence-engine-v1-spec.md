# SAB — Market Intelligence Engine v1 (Production Seal)

**Tarih:** 28 Mart 2026
**Versiyon:** v1.0.0-spec
**Durum:** SPEC LOCKED — kod yok, önce doğrulama
**Seal:** Production-grade, SAB-uyumlu, schema-truth-based

---

# 1. MODULE PURPOSE

## Ne

Market Intelligence Engine (MIE), Yalıhan Emlak admin panelinde **karar destek motoru** olarak çalışan dahili bir modüldür. İlanlardan, fiyatlardan, lokasyonlardan ve davranış sinyallerinden **eyleme dönük içgörü** üretir.

## Ne Değil

- Halka açık bir analitik dashboard değil
- Sihirli bir AI kehanet kutusu değil
- Otomatik fiyat belirleme sistemi değil
- Harici veri scraping pipeline'ı değil

## Cevaplaması Gereken 5 Soru

1. Bu ilan **overpriced / underpriced / fairly priced** mi?
2. Hangi bölgeler ve segmentler **ısınıyor / soğuyor**?
3. Hangi ilanlar **düşük kaliteli / bayat / zayıf performanslı**?
4. Portföydeki hangi ilanlar **aksiyon gerektiriyor**?
5. Hangi içgörüler **spekülatif AI hallüsinasyonu olmadan** güvenle gösterilebilir?

## Temel Prensip

> Önce gerçeği tanımla, sonra metrikleri tanımla, sonra deterministik skorlamayı tanımla, sonra UI yüzeylerini tanımla, sonra AI'ın nerede kullanılacağını tanımla — ve nerede yasak olduğunu.

---

# 2. V1 SCOPE

## Dahil (v1)

| Bileşen                          | Açıklama                                                  |
| -------------------------------- | --------------------------------------------------------- |
| Mahalle bazlı m2 fiyat benchmark | Medyan, p25, p75, ilan sayısı                             |
| Fiyat pozisyon tespiti           | normal / yüksek / düşük / agresif_düşük / şüpheli_yüksek  |
| İlan yaşı risk analizi           | Stale detection (30/60/90 gün bandları)                   |
| Basit talep sinyali              | Artan / durağan / zayıflayan                              |
| Anomali bayrakları               | OVERPRICED, UNDERPRICED, STALE, NO_VIEWS, PRICE_DROP_FAST |
| Birleşik skor seti               | 5 deterministik skor (0–100)                              |
| İlan detay insight kartı         | Admin sayfasında piyasa durumu kartı                      |
| Dashboard intelligence kartları  | Sıcak bölgeler, dikkat gereken ilanlar                    |
| Portföy filtresi                 | overpriced / stale / high_potential / low_quality         |
| AI açıklama katmanı              | Sadece skor sonuçlarını doğal dile çevirmek               |

## Hariç (v1'de YOK)

| Hariç                                 | Neden                                      |
| ------------------------------------- | ------------------------------------------ |
| `market_listings` tablosu aktivasyonu | Harici veri pipeline hazır değil           |
| Kapanış ihtimali tahmini              | Predictive model v3 scope                  |
| Otomatik fiyat önerisi                | Formül güvenilirliği yetersiz              |
| Segment risk skoru                    | Yeterli historical data yok                |
| Chat assistant entegrasyonu           | ConversationalAdvisor zaten ayrı çalışıyor |
| Harici portal scraping                | Legal + teknik hazır değil                 |
| ML modeli                             | v3 scope                                   |
| Isı haritası                          | v2 scope                                   |
| Benzer ilan kümeleme                  | v2 scope                                   |
| Portfolio optimization                | v3 scope                                   |

---

# 3. REQUIRED DATA INPUTS

## Birincil Veri (ilanlar tablosu — schema truth verified)

| Alan           | Kolon(lar)                                      | Amaç                    | Zorunlu            | Risk if Missing         |
| -------------- | ----------------------------------------------- | ----------------------- | ------------------ | ----------------------- |
| Fiyat          | `fiyat` decimal(15,2), `para_birimi`            | Benchmark karşılaştırma | ✅ Zorunlu         | Skor hesaplanamaz       |
| Metrekare      | `alan_m2` / `brut_m2` / `net_m2`                | m2 fiyat hesabı         | ✅ En az 1         | m2 fiyat skoru atlanır  |
| Kategori       | `ana_kategori_id`, `alt_kategori_id` FK         | Segment belirleme       | ✅ Zorunlu         | Comp eşleşme imkansız   |
| Yayın Tipi     | `yayin_tipi_id` FK                              | Satılık/kiralık ayrımı  | ✅ Zorunlu         | Benchmark yanlış        |
| Lokasyon       | `il_id`, `ilce_id`, `mahalle_id` FK             | Benchmark lokasyonu     | ✅ il+ilce zorunlu | Mahalle fallback ilçeye |
| İlan Yaşı      | `created_at` timestamp                          | Bayatlık tespiti        | ✅ Auto            | Risk yok                |
| Son Güncelleme | `updated_at` timestamp                          | Aktivite tespiti        | ✅ Auto            | Risk yok                |
| Yayın Durumu   | `yayin_durumu` string → IlanDurumu enum         | Aktif filtresi          | ✅ Zorunlu         | Risk yok                |
| Görüntülenme   | `goruntulenme` int                              | Talep sinyali           | ⚪ Opsiyonel       | Demand skoru kısıtlı    |
| Kalite Skoru   | `quality_score` int, `completion_score` tinyint | Listing quality         | ⚪ Opsiyonel       | Default 50              |
| Görünürlük     | `visibility_score` int                          | SEO sinyali             | ⚪ Opsiyonel       | Default 50              |
| SEO            | `seo_score` int                                 | SEO sinyali             | ⚪ Opsiyonel       | Default 50              |
| Oda/Banyo      | `oda_sayisi`, `banyo_sayisi` int                | Comp filtresi           | ⚪ Opsiyonel       | Comp daha geniş         |
| Bina Yaşı      | `bina_yasi` year                                | —                       | ❌ Kullanma        | Çoğunlukla boş          |

## İkincil Veri (CQRS projections — schema truth verified)

| Projeksiyon                    | Kolon(lar)                                                                                          | Amaç               | Güvenilirlik                         |
| ------------------------------ | --------------------------------------------------------------------------------------------------- | ------------------ | ------------------------------------ |
| `market_trend_projections`     | `avg_price`, `median_price`, `price_change_7d`, `price_change_30d`, `demand_index`, `listing_count` | Bölge trendi       | Orta — projection güncelliğine bağlı |
| `listing_velocity_projections` | `view_count`, `favorite_count`, `inquiry_count`, `activity_score`, `last_activity_at`               | İlan hareketliliği | Orta — sayaç güvenilirliği           |
| `buyer_interest_projections`   | `candidate_count`, `avg_match_score`, `high_intent_buyer_count`                                     | Alıcı ilgisi       | Orta — matching kalitesine bağlı     |

## Üçüncül Veri (relation — schema truth verified)

| Relation       | Tablo                                                         | Amaç                | Güvenilirlik   |
| -------------- | ------------------------------------------------------------- | ------------------- | -------------- |
| `fiyatGecmisi` | `ilan_price_history` → `old_price`, `new_price`, `created_at` | Fiyat trend analizi | Yüksek (varsa) |
| `talepler`     | demand records                                                | Buyer match density | Orta           |

## Referans Veri

| Tablo           | Amaç                                                                       | Güvenilirlik                      |
| --------------- | -------------------------------------------------------------------------- | --------------------------------- |
| `market_trends` | `ortalama_m2_fiyat`, `trend_yonu`, `aylik_degisim_yuzde` — bölge benchmark | Orta — güncelleme sıklığına bağlı |

## Data Validation Kontrolleri (Girişte Uygulanır)

```
MUST:  fiyat > 0 AND fiyat IS NOT NULL
MUST:  (alan_m2 > 0 OR brut_m2 > 0 OR net_m2 > 0)  — en az biri
MUST:  il_id IS NOT NULL AND ilce_id IS NOT NULL
MUST:  yayin_durumu = 'yayinda'  — sadece aktif ilanlar skorlanır
MUST:  para_birimi IS NOT NULL  — TRY normalizasyonu için
RULE:  comp_sayisi >= 5  — altında "veri_yetersiz" döner
RULE:  projection.updated_at < 7 gün  — aşarsa güvenilirlik "dusuk"
```

---

# 4. CORE SCORES

## 4.1. `pricing_score` (0–100)

**Anlamı:** İlanın fiyatı piyasa benchmark'ına ne kadar uyumlu.

**Formül:**

```
m2_fiyat = fiyat / effective_m2    // effective_m2 = alan_m2 || brut_m2 || net_m2
sapma = (m2_fiyat - benchmark_medyan) / benchmark_medyan
pricing_score = clamp(0, 100, 50 - (sapma * 200))
```

- Sapma 0 → skor 50 (tam ortada)
- Sapma +%25 → skor 0 (çok pahalı)
- Sapma -%25 → skor 100 (çok ucuz)

**Güvenilirlik notu:** comp_sayisi < 5 → `guvenilirlik: "dusuk"`, skor hesaplanır ama bayrak taşır.

## 4.2. `demand_score` (0–100)

**Anlamı:** Bu ilan için ve bu segmentte ne kadar talep var.

**Formül:**

```
view_signal     = clamp(0, 100, velocity.view_count / 10)              × 0.25
favorite_signal = clamp(0, 100, velocity.favorite_count * 10)          × 0.15
inquiry_signal  = clamp(0, 100, velocity.inquiry_count * 20)           × 0.20
buyer_signal    = clamp(0, 100, buyer.avg_match_score)                 × 0.15
market_signal   = clamp(0, 100, market_trend.demand_index)             × 0.25

demand_score = view_signal + favorite_signal + inquiry_signal + buyer_signal + market_signal
```

**Güvenilirlik notu:** projection eksikse → ilgili sinyal 0 alır, `guvenilirlik: "dusuk"`.

## 4.3. `listing_quality_score` (0–100)

**Anlamı:** İlanın veri kalitesi, tamlığı ve sunumu ne durumda.

**Formül:**

```
completion      = ilan.completion_score ?? 50                           × 0.30
quality         = ilan.quality_score ?? 50                              × 0.25
seo             = ilan.seo_score ?? 50                                  × 0.15
photo_count     = clamp(0, 100, fotograflar.count() * 10)               × 0.15
desc_quality    = clamp(0, 100, strlen(aciklama) / 20)                  × 0.15

listing_quality_score = completion + quality + seo + photo_count + desc_quality
```

**Önemli:** `rand()` YOK. Eksik veri default 50 alır, rand yerine.

## 4.4. `market_fit_score` (0–100)

**Anlamı:** Bu ilan, bulunduğu segment için ne kadar uygun.

**Formül:**

```
market_fit_score = pricing_score       × 0.40
                 + demand_score        × 0.30
                 + listing_quality_score × 0.30
```

**Neden ayrı:** pricing + demand + quality'nin ağırlıklı bileşimi. "Bu ilan piyasaya ne kadar oturuyor" sorusunun cevabı.

## 4.5. `opportunity_score` (0–100)

**Anlamı:** Bu ilanda ne kadar fırsat potansiyeli var (admin perspektifinden).

**Formül:**

```
underpriced_signal   = max(0, pricing_score - 50) * 2                    × 0.30
high_demand_signal   = max(0, demand_score - 50) * 2                     × 0.25
low_competition      = clamp(0, 100, 100 - market_trend.listing_count)   × 0.25
freshness            = clamp(0, 100, 100 - listing_age_days)             × 0.20

opportunity_score = underpriced + high_demand + low_competition + freshness
```

**Kural:** opportunity_score yüksekse → ilan düşük fiyatlı + yüksek talep + düşük rekabet + taze.

## Skor Çıktı Yapısı (Her Skor İçin)

```
{
  deger: number,            // 0-100
  aciklama: string,         // kısa Türkçe açıklama
  guvenilirlik: enum,       // "yuksek" | "orta" | "dusuk"
  comp_sayisi: number,      // kaç referans noktası kullanıldı
  son_guncelleme: datetime, // ne zaman hesaplandı
  hesaplama_detay: {}       // debug: alt sinyaller
}
```

---

# 5. DETERMINISTIC RULE ENGINE

## 5.1. Fiyat Pozisyon Tespiti

| Koşul             | Pozisyon       | Enum             |
| ----------------- | -------------- | ---------------- |
| sapma ±%10        | Normal         | `NORMAL`         |
| sapma +%10 → +%25 | Yüksek         | `YUKSEK`         |
| sapma +%25+       | Şüpheli yüksek | `SUPHELI_YUKSEK` |
| sapma -%10 → -%20 | Düşük          | `DUSUK`          |
| sapma -%20+       | Agresif düşük  | `AGRESIF_DUSUK`  |

**Girdi:** `ilan.fiyat`, `ilan.effective_m2`, benchmark medyan m2 fiyatı
**Mevcut temel:** `MarketAnalysisService::analyze()` — `expensive/cheap/fair` zaten var; genişletilecek (5 kademe).

## 5.2. Bayatlık (Stale) Tespiti

| Koşul         | Risk   | Enum     |
| ------------- | ------ | -------- |
| Yaş 0–30 gün  | Düşük  | `DUSUK`  |
| Yaş 30–60 gün | Orta   | `ORTA`   |
| Yaş 60–90 gün | Yüksek | `YUKSEK` |
| Yaş 90+ gün   | Kritik | `KRITIK` |

**Ek kural:** `updated_at` 30+ gün önce ise → risk bir kademe artar.
**Mevcut temel:** `OpportunityEngineService::STALE_LISTING_RECOVERY` — var, sistematize edilecek.

## 5.3. Düşük Kalite Tespiti

| Koşul                        | Bayrak             |
| ---------------------------- | ------------------ |
| `listing_quality_score < 40` | `LOW_QUALITY`      |
| `completion_score < 30`      | `INCOMPLETE`       |
| `fotograflar.count() == 0`   | `NO_PHOTOS`        |
| `strlen(aciklama) < 100`     | `WEAK_DESCRIPTION` |

## 5.4. Zayıf Piyasa Uyumu

| Koşul                                      | Bayrak            |
| ------------------------------------------ | ----------------- |
| `market_fit_score < 30`                    | `WEAK_MARKET_FIT` |
| `pricing_score < 20 AND demand_score < 30` | `MISALIGNED`      |

## 5.5. Talep Trendi

| Koşul                                | Sinyal     | Enum         |
| ------------------------------------ | ---------- | ------------ |
| `market_trend.price_change_7d > +2%` | Artan      | `ARTIYOR`    |
| `market_trend.price_change_7d` ±2%   | Durağan    | `DURAGAN`    |
| `market_trend.price_change_7d < -2%` | Zayıflayan | `ZAYIFLIYOR` |

## 5.6. Anomali Bayrakları

| Bayrak                   | Tetikleyici                                          |
| ------------------------ | ---------------------------------------------------- |
| `OVERPRICED`             | sapma > +%25                                         |
| `UNDERPRICED`            | sapma < -%20                                         |
| `STALE`                  | yaş > 60 gün AND son güncelleme > 30 gün             |
| `NO_VIEWS`               | son 7 gün görüntülenme = 0                           |
| `PRICE_DROP_FAST`        | son 30 günde fiyat düşüşü > %15 (ilan_price_history) |
| `HIGH_DEMAND_LOW_SUPPLY` | segment talep/arz oranı > 3                          |

**Her bayrak bağımsız bool.** Bir ilan birden fazla bayrak taşıyabilir.

---

# 6. AI USAGE POLICY

## İzin Verilen (v1)

| Kullanım              | Amaç                                                            | Kural                                     |
| --------------------- | --------------------------------------------------------------- | ----------------------------------------- |
| Insight açıklama      | Deterministik skor sonuçlarını Türkçe cümleye çevirmek          | AI çıktısı skor ile çelişemez             |
| Aksiyon önerisi metni | "Fiyatı revize et" gibi önerinin açıklama paragrafı             | Öneri türü kural tabanlı, sadece metin AI |
| Portföy özet raporu   | Birden fazla ilanın skor özetini doğal dil paragrafına çevirmek | Veri kaynağı sadece deterministik skorlar |

## Yasak (v1)

| Kullanım                  | Neden                                                          |
| ------------------------- | -------------------------------------------------------------- |
| Ham fiyat tahmini         | Deterministic base olmadan hallüsinasyon riski                 |
| Skor hesaplama            | Tekrarlanabilir olmalı, LLM her seferinde farklı sonuç verir   |
| Anomali tespiti           | Eşikler açık ve sabit olmalı                                   |
| Comp seçimi               | SQL sorgusu, AI belirsizliği burada zarar verir                |
| Schema truth yerine geçme | AI varsayımı ≠ gerçek kolon                                    |
| Sinyal icat etme          | Olmayan veriyi "tahmin etme"                                   |
| rand() ile değer üretme   | Mevcut PortfolioDoctorService'teki 6 rand() çağrısı gibi YASAK |
| **Silent Catch**          | **ASLA YASAK. Tüm hatalar AST (Bekçi v2.1) tarafından denetlenir.** |
| **Bypass AST Audit**      | **Cognitive Guardian (Phase 11) kuralları sarsılmazdır.**       |

## AI–Deterministic Sınır Haritası

```
┌───────────────────────────────────────────────────┐
│                 DETERMINISTIC ZONE                 │
│  (formül açık, tekrarlanabilir, auditlenebilir)   │
│                                                   │
│  benchmark → pozisyon → skor → bayrak → filtre   │
│                                                   │
├───────────────────────────────────────────────────┤
│                    AI ZONE                         │
│  (açıklama, doğal dil, özet)                      │
│                                                   │
│  skor sonucu → Türkçe açıklama → aksiyon metni   │
│                                                   │
│  KESİN KURAL: AI çıktısı deterministic            │
│  skorlarla çelişemez.                              │
└───────────────────────────────────────────────────┘
```

---

# 7. UI SURFACES

## 7.1. İlan Detay Sayfası — Piyasa Insight Kartı

```
┌─────────────────────────────────────────────┐
│ 📊 Piyasa Durumu                             │
├─────────────────────────────────────────────┤
│ Piyasa Uyumu:      🟢 74/100               │
│ Fiyat Pozisyonu:   🔴 Yüksek (+22%)        │
│ Talep Sinyali:     🟡 Durağan              │
│ İlan Yaşı Riski:   🟠 Orta (45 gün)        │
│ Kalite Skoru:      🟢 68/100               │
│ Fırsat Skoru:      ⚪ 32/100               │
│                                             │
│ Bayraklar: OVERPRICED · STALE              │
│                                             │
│ 💡 Benzer 3+1 daireler ortalama %22 daha   │
│    düşük fiyatlı. 45 gündür güncellenmemiş. │
│    Fiyat revizyonu veya yeniden yayınlama   │
│    önerilir.                                │
│                                             │
│ 📋 Veri: 23 comp · Güvenilirlik: Yüksek    │
│ ⏰ Son güncelleme: 28 Mar 2026 08:00       │
└─────────────────────────────────────────────┘
```

**Renk kuralı:** 0–39 🔴, 40–59 🟠, 60–79 🟡, 80–100 🟢. Bayraklar ayrı gösterilir.

## 7.2. Dashboard — Intelligence Kartları

```
┌───────────────────────┐ ┌───────────────────────┐
│ 🔥 Isınan Bölgeler    │ │ ⚠️ Dikkat Gereken      │
│                       │ │                       │
│ Yalıkavak    +12%    │ │ 8 overpriced ilan     │
│ Gümüşlük     +8%     │ │ 5 stale (60+ gün)     │
│ Turgutreis    +5%     │ │ 3 görüntülenme = 0    │
│                       │ │ 2 hızlı fiyat düşüşü  │
└───────────────────────┘ └───────────────────────┘
┌───────────────────────┐ ┌───────────────────────┐
│ 📊 Portföy Sağlığı    │ │ 🚀 Fırsat Havuzu      │
│                       │ │                       │
│ Sağlıklı:     67%    │ │ 4 underpriced ilan    │
│ Dikkat:       23%    │ │ 3 yüksek talep bölge  │
│ Kritik:       10%    │ │ 2 düşük rekabet segm. │
└───────────────────────┘ └───────────────────────┘
```

**Veri kaynağı:** Tüm aktif ilanların skor agregasyonu. Cache TTL: 1 saat.

## 7.3. Portföy Merkezi — Filtreler

Mevcut ilan listesine eklenen filtre seçenekleri:

| Filtre         | Mantık                                  |
| -------------- | --------------------------------------- |
| Overpriced     | `pricing_score < 20`                    |
| Underpriced    | `pricing_score > 80`                    |
| Stale          | `stale_risk IN ('yuksek', 'kritik')`    |
| High Potential | `opportunity_score > 70`                |
| Low Quality    | `listing_quality_score < 40`            |
| Needs Action   | Herhangi bir anomali bayrağı var        |
| Healthy        | `market_fit_score >= 70 AND bayrak yok` |

## 7.4. Lokasyon Intelligence (Bölge Özeti)

İlçe/mahalle seçildiğinde:

```
┌─────────────────────────────────────────┐
│ 📍 Yalıkavak — Piyasa Özeti            │
├─────────────────────────────────────────┤
│ Medyan m²:       €3,200                │
│ Fiyat Bandı:     €2,400 – €4,100       │
│ Ort. İlan Yaşı:  34 gün               │
│ Aktif İlan:      47                    │
│ Trend (7g):      ↑ +3.2%              │
│ Trend (30g):     ↑ +8.1%              │
│ Talep Endeksi:   72/100 (yüksek)      │
└─────────────────────────────────────────┘
```

---

# 8. SERVICE ARCHITECTURE

## Yeni Dosyalar (SAB-Uyumlu)

```
app/Services/MarketIntelligence/
├── MarketIntelligenceFacade.php        # Tek giriş noktası — orchestrator
├── BenchmarkService.php                # B1: mahalle bazlı m2 benchmark
├── PricingPositionService.php          # B2: fiyat pozisyon tespiti
├── ListingAgeAnalyzer.php              # B3: ilan yaşı risk analizi
├── DemandSignalService.php             # B4: talep sinyali
├── AnomalyDetector.php                 # B5: anomali bayrakları
├── ScoreAggregator.php                 # 5 skoru birleştiren agregator
└── InsightExplainer.php                # C: AI açıklama katmanı

app/DTOs/MarketIntelligence/
├── ListingInsightDTO.php               # Tek ilan: 5 skor + bayraklar + açıklama
├── BenchmarkDTO.php                    # Benchmark sonucu: medyan, p25, p75, count
├── PricingPositionDTO.php              # Pozisyon + sapma + enum
├── DemandSignalDTO.php                 # Talep durumu + alt sinyaller
├── AnomalyFlagsDTO.php                 # Flag listesi
└── PortfolioHealthDTO.php              # Portföy özet: dağılım + toplu metrikler

app/Enums/MarketIntelligence/
├── FiyatPozisyonu.php                  # NORMAL|YUKSEK|DUSUK|AGRESIF_DUSUK|SUPHELI_YUKSEK
├── TalepDurumu.php                     # ARTIYOR|DURAGAN|ZAYIFLIYOR
├── BayatlikRiski.php                   # DUSUK|ORTA|YUKSEK|KRITIK
├── AnomaliBayragi.php                  # OVERPRICED|UNDERPRICED|STALE|NO_VIEWS|...
└── InsightGuvenilirlik.php             # YUKSEK|ORTA|DUSUK

app/Actions/Admin/MarketIntelligence/
├── GetListingInsightAction.php         # Tek ilan insight'ı
├── GetPortfolioHealthAction.php        # Portföy sağlığı
├── GetLocationBenchmarkAction.php      # Bölge benchmark'ı
└── FilterListingsByAnomalyAction.php   # Anomali filtresi
```

## Mevcut Servislerle İlişki (Sarmalama, Yeniden Yazmama)

```
MarketIntelligenceFacade
│
├── BenchmarkService
│   └── delegates to: MarketIntelligenceService.calculateMarketValue()  [MEVCUT]
│   └── enhances: mahalle→ilçe→il fallback KORUNUR
│   └── adds: p25/p75 bandı, comp sayısı, güvenilirlik
│
├── PricingPositionService
│   └── delegates to: MarketAnalysisService.analyze()  [MEVCUT]
│   └── enhances: 3 kademe → 5 kademe (agresif düşük, şüpheli yüksek)
│   └── adds: sapma yüzdesi, enum-based çıktı
│
├── ListingAgeAnalyzer
│   └── reads: ilan.created_at, ilan.updated_at  [SCHEMA TRUTH]
│   └── new: tamamen yeni, basit tarih hesabı
│
├── DemandSignalService
│   └── reads: listing_velocity_projections  [CQRS]
│   └── reads: buyer_interest_projections  [CQRS]
│   └── reads: market_trend_projections  [CQRS]
│   └── new: sinyal agregasyonu
│
├── AnomalyDetector
│   └── reads: tüm B katmanı çıktıları
│   └── reads: ilan_price_history  [SCHEMA TRUTH]
│   └── new: bayrak enum listesi
│
├── ScoreAggregator
│   └── reads: tüm B katmanı çıktıları
│   └── produces: 5 skor + güvenilirlik
│   └── new: deterministik formüller
│
└── InsightExplainer
    └── delegates to: YalihanCortex  [MEVCUT]
    └── input: ScoreAggregator çıktısı (yapılandırılmış)
    └── output: Türkçe açıklama paragrafı
    └── KURAL: skor ile çelişemez
```

## Controller Kullanımı (Thin Controller Pattern)

```php
// İlan detay — insight kartı
public function show(Ilan $ilan)
{
    $insight = app(GetListingInsightAction::class)->execute($ilan);
    return view('admin.ilanlar.show', compact('ilan', 'insight'));
}

// Dashboard — portföy sağlığı
public function getDashboardStats()
{
    $portfolio = app(GetPortfolioHealthAction::class)->execute();
    // ... mevcut stats ile birleştir
}
```

---

# 9. CANONICAL DOMAIN LANGUAGE

## Zorunlu Terimler (Context7 Uyumlu)

| Kavram          | Kanonik Terim           | YASAK Alternatifler               |
| --------------- | ----------------------- | --------------------------------- |
| Fiyat pozisyonu | `fiyat_pozisyonu`       | status, price_status, positioning |
| Talep durumu    | `talep_sinyali`         | demand_status, demand_type        |
| İlan yaşı riski | `bayatlik_riski`        | stale_status, age_status          |
| Anomali bayrağı | `anomali_bayragi`       | flag, alert, warning_type         |
| Piyasa uyumu    | `piyasa_uyum_skoru`     | market_fit, fit_score             |
| Fiyatlama skoru | `fiyatlama_skoru`       | pricing, price_score              |
| Talep skoru     | `talep_skoru`           | demand, demand_score              |
| Kalite skoru    | `kalite_skoru`          | quality, quality_score            |
| Fırsat skoru    | `firsat_skoru`          | opportunity, opp_score            |
| Benchmark       | `benchmark_verisi`      | data, market_data                 |
| Comp sayısı     | `referans_sayisi`       | comp_count, sample_size           |
| Güvenilirlik    | `guvenilirlik_seviyesi` | confidence, reliability           |
| Sapma oranı     | `sapma_yuzdesi`         | deviation, diff                   |

## Yasak Kelimeler (Hiçbir Yerde Kullanma)

| Kelime               | Neden           | Yerine                          |
| -------------------- | --------------- | ------------------------------- |
| `status`             | Context7 ihlali | İlgili kanonik terim            |
| `type`               | Belirsiz        | Açık alan adı                   |
| `data`               | Belirsiz        | `_verisi` suffix                |
| `order`              | Context7 ihlali | `display_order` veya `siralama` |
| `active`             | Context7 ihlali | `aktiflik_durumu`               |
| `score` (tek başına) | Belirsiz        | `_skoru` suffix ile             |

---

# 10. RISKS

| Risk                                                                 | Seviye    | Etki                               | Önlem                                                                                    |
| -------------------------------------------------------------------- | --------- | ---------------------------------- | ---------------------------------------------------------------------------------------- |
| **Kirli veri** — m2 eksik, fiyat yanlış                              | 🔴 Yüksek | Yanlış benchmark → yanlış pozisyon | Data validation gateway: eksik m2 → "veri yetersiz" döner, skor hesaplanmaz              |
| **Yanlış güven** — düşük comp'lu skor kesinmiş gibi görünmesi        | 🔴 Yüksek | Admin yanlış karar verir           | `guvenilirlik_seviyesi` her skorda zorunlu, UI'da gösterilir                             |
| **Bayat benchmark** — projection güncel değil                        | 🟠 Orta   | Eski verilere dayalı skor          | projection.updated_at > 7 gün → güvenilirlik "dusuk"                                     |
| **AI aşırı kullanımı** — açıklama katmanının karar katmanına sızması | 🟠 Orta   | Tekrarlanamaz çıktı                | AI ZONE kesin sınır, deterministik çıktı sıfır AI bağımlılığı                            |
| **rand() kontaminasyonu** — mevcut servislerdeki mock data           | 🟠 Orta   | Güvenilmez skor                    | MIE v1'de sıfır rand(); mevcut servisleri sarmalıyoruz, rand'lı olanları bypass ediyoruz |
| **UI karmaşıklığı** — çok fazla skor / kart                          | 🟡 Düşük  | Admin bilgi kirliliği              | v1'de 5 skor + anomali bayrakları, fazlası yok                                           |
| **Bodrum sezon etkisi** — kiralık fiyatlar yaz/kış farkı             | 🟠 Orta   | Sezon-agnostic benchmark yanıltıcı | v1'de kiralık ilanlar için `para_birimi` bazlı ayrım, sezon faktörü v2'de                |
| **Performance** — her ilan için 5 skor hesabı                        | 🟡 Düşük  | Yavaş sayfa                        | Skor cache TTL 1 saat, batch hesaplama cron                                              |
| **Scope creep** — "bir de şu skoru ekleyelim"                        | 🔴 Yüksek | Modül şişer, test edilemez         | 5 skor KILITLI, yeni skor v2+ gerektirir                                                 |

---

# 11. VALIDATION STRATEGY

## 11.1. Schema Truth Check

```bash
# ilanlar tablosu gerçekliği
php artisan db:table ilanlar | grep -E "fiyat|m2|kategori|il_id|yayin_durumu"

# Projection tabloları varlığı
php artisan db:table market_trend_projections
php artisan db:table listing_velocity_projections
php artisan db:table buyer_interest_projections

# Price history varlığı
php artisan db:table ilan_price_history
```

## 11.2. Deterministik Skor Test Cases

```php
// PricingPositionServiceTest
public function test_overpriced_listing_detected()
{
    // benchmark medyan: 10,000 TRY/m2
    // ilan fiyat: 15,000 TRY/m2 (+50%)
    $result = $service->calculate($ilan, $benchmark);
    $this->assertEquals(FiyatPozisyonu::SUPHELI_YUKSEK, $result->pozisyon);
    $this->assertLessThan(20, $result->pricing_score);
}

public function test_fair_priced_listing()
{
    // benchmark medyan: 10,000 TRY/m2
    // ilan fiyat: 10,500 TRY/m2 (+5%)
    $result = $service->calculate($ilan, $benchmark);
    $this->assertEquals(FiyatPozisyonu::NORMAL, $result->pozisyon);
    $this->assertBetween(40, 60, $result->pricing_score);
}

// ListingAgeAnalyzerTest
public function test_stale_listing_90_days()
{
    $ilan = Ilan::factory()->create(['created_at' => now()->subDays(95)]);
    $result = $analyzer->analyze($ilan);
    $this->assertEquals(BayatlikRiski::KRITIK, $result->bayatlik_riski);
}

// AnomalyDetectorTest
public function test_multiple_flags_possible()
{
    // +30% overpriced + 70 gün eski
    $flags = $detector->detect($ilan, $scoreset);
    $this->assertContains(AnomaliBayragi::OVERPRICED, $flags);
    $this->assertContains(AnomaliBayragi::STALE, $flags);
}

// ScoreAggregatorTest
public function test_missing_m2_returns_veri_yetersiz()
{
    $ilan = Ilan::factory()->create(['alan_m2' => null, 'brut_m2' => null, 'net_m2' => null]);
    $result = $aggregator->forListing($ilan);
    $this->assertEquals('dusuk', $result->guvenilirlik_seviyesi);
    $this->assertNull($result->fiyatlama_skoru); // m2 olmadan fiyat skoru yok
}

// InsightExplainerTest
public function test_ai_explanation_does_not_contradict_score()
{
    $insight = InsightExplainer::explain($scoreset);
    // pricing_score < 20 → açıklama "uygun fiyatlı" DEMEMELİ
    $this->assertStringNotContainsString('uygun', $insight->aciklama);
}
```

## 11.3. Admin Smoke Checks

```
1. Bilinen 5 overpriced ilan → hepsi SUPHELI_YUKSEK veya YUKSEK
2. Bilinen 5 hızlı kapanan ilan → pricing_score > 60, demand_score > 60
3. 90+ günlük 5 ilan → hepsinde STALE bayrağı
4. m2 olmayan 5 ilan → hiçbiri crash etmemeli, "veri yetersiz"
5. Yalıkavak villa benchmark → makul m2 fiyatı (€2,000–€5,000 bandı)
```

## 11.4. Benchmark Sanity Checks

```
- Bodrum ilçe bazlı benchmark: en az 8 ilçe için medyan üretebilmeli
- Her benchmark: comp_sayisi >= 5, aksi halde güvenilirlik "dusuk"
- Medyan m2 fiyat: pozitif ve makul aralıkta (0 veya negatif ASLA)
- Farklı kategoriler farklı benchmark üretmeli (villa ≠ daire ≠ arsa)
```

---

# 12. ROLLOUT PLAN

## Phase 1 — Deterministic Core (v1.0)

**Bu spec'in kapsamı.**

| Milestone | İçerik                                    | Tahmini Dosya               |
| --------- | ----------------------------------------- | --------------------------- |
| M1        | Enums + DTOs                              | 10 dosya                    |
| M2        | BenchmarkService + PricingPositionService | 2 servis + 2 test           |
| M3        | ListingAgeAnalyzer + DemandSignalService  | 2 servis + 2 test           |
| M4        | AnomalyDetector + ScoreAggregator         | 2 servis + 2 test           |
| M5        | MarketIntelligenceFacade + Actions        | 1 facade + 4 action         |
| M6        | İlan detay insight kartı (Blade)          | 1 partial                   |
| M7        | Dashboard intelligence kartları           | 1 partial                   |
| M8        | Portföy filtre entegrasyonu               | Controller + view düzenleme |

**Çıktı:** Tüm skorlar deterministik. AI sıfır. rand() sıfır.

## Phase 1.1 — AI Explanation Layer (v1.1)

| İçerik                    | Detay                                   |
| ------------------------- | --------------------------------------- |
| InsightExplainer service  | YalihanCortex üzerinden Türkçe açıklama |
| Aksiyon önerisi metinleri | Kural tabanlı öneri tipi + AI metin     |
| Portföy özet raporu       | Çoklu ilan skor özetini paragraf        |
| AI maliyet bütçe kontrolü | config/ai-budgets.php limitleri         |

## Phase 2 — Enhanced Intelligence (v2.0)

| İçerik                      | Detay                                              |
| --------------------------- | -------------------------------------------------- |
| Benzer ilan karşılaştırması | Comp tablosu + deep comparison                     |
| Bölge ısı haritası          | Mahalle bazlı trend görselleştirme                 |
| Advisor recommendation feed | Danışman dashboard'unda kişiselleştirilmiş insight |
| NLP soru-cevap              | "Yalıkavak'ta daire piyasası nasıl?"               |
| Sezon faktörü               | Kiralık ilanlar için yaz/kış band ayrımı           |

## Phase 3 — Predictive Intelligence (v3.0)

| İçerik                      | Detay                            |
| --------------------------- | -------------------------------- |
| Kapanış ihtimali tahmini    | ML modeli, historical data       |
| Önerilen fiyat aralığı      | Benchmark + trend + sezon        |
| Otomatik aksiyon önerileri  | Rule engine → auto-suggest queue |
| Portfolio optimization      | Portföy dengeleme önerileri      |
| market_listings aktivasyonu | Harici veri pipeline             |
| Segment risk skoru          | Bölge + kategori bazlı risk      |

---

# 13. FINAL VERDICT

## Neden Bu v1 Güvenli

1. **Deterministik temel:** Hiçbir skor LLM'e bağımlı değil. Her çalışmada aynı girdi → aynı çıktı.
2. **Sıfır rand():** Mevcut servislerdeki `rand()` kontaminasyonu MIE'ye sızmıyor. BenchmarkService ve PricingPositionService doğrudan DB'den okuyor.
3. **Data validation gateway:** Eksik veri crash yerine "veri yetersiz" döndürüyor.
4. **Güvenilirlik şeffaflığı:** Her skor yanında comp sayısı ve güvenilirlik seviyesi. Admin yanılmaz.
5. **Scope kilidi:** 5 skor, 6 anomali bayrağı, 4 UI yüzeyi. Fazlası v2+.

## Neden Stabilize Mimariye Uyuyor

1. **Thin controllers:** Action class'lar iş mantığını taşır, controller sadece çağırır.
2. **Service delegation:** MarketIntelligenceFacade → alt servisler. Mevcut servisler sarmalanır, yeniden yazılmaz.
3. **Schema truth:** Tüm kolon isimleri migration'lardan doğrulanmış. Varsayım sıfır.
4. **Context7 uyumu:** Enum isimleri, DTO alanları, kanonik terimler Türkçe ve tutarlı.
5. **No giant AI blob:** 7 ayrı servis, her biri tek sorumluluk. InsightExplainer sadece açıklama.
6. **Test edilebilir:** Her servis bağımsız test edilebilir, mock-free (DB veya factory).

## v1 Mevcut Servisleri Neden Yeniden Yazmıyor

| Mevcut Servis                                           | MIE İlişkisi                                                                 |
| ------------------------------------------------------- | ---------------------------------------------------------------------------- |
| MarketAnalysisService → 3 kademe (expensive/cheap/fair) | PricingPositionService onu 5 kademe yapıyor, ama analyze() çağrısını koruyor |
| MarketIntelligenceService → mahalle fallback            | BenchmarkService onu sarmalıyor, fallback mantığını koruyor                  |
| OpportunityEngineService → CQRS composite score         | opportunity_score ayrı formül ama projection'ları paylaşıyor                 |
| PortfolioDoctorService → rand() ile 6 sinyal            | Bypass ediliyor — MIE kendi deterministik sinyallerini kullanıyor            |
| DealRadarService → 8 sinyal                             | Korunuyor, MIE onun scope'una girmiyor                                       |
| IntelligenceHub → 4 sub-score                           | Korunuyor, MIE daha dar ve deterministik                                     |

---

# IMPLEMENT FIRST

## İlk Milestone: BenchmarkService + PricingPositionService + Enums

**Neden bu:**

- En dar scope (2 servis + 5 enum + 2 DTO)
- En yüksek değer (admin hemen "bu ilan pahalı mı" görebilir)
- En düşük risk (sadece ilanlar tablosundan SELECT, yan etkisi sıfır)
- Hemen doğrulanabilir (bilinen ilanlarla smoke test)
- Mevcut servisleri sarmalıyor (MarketAnalysisService + MarketIntelligenceService)

**Dosya listesi:**

```
1. app/Enums/MarketIntelligence/FiyatPozisyonu.php
2. app/Enums/MarketIntelligence/InsightGuvenilirlik.php
3. app/DTOs/MarketIntelligence/BenchmarkDTO.php
4. app/DTOs/MarketIntelligence/PricingPositionDTO.php
5. app/Services/MarketIntelligence/BenchmarkService.php
6. app/Services/MarketIntelligence/PricingPositionService.php
7. tests/Unit/MarketIntelligence/BenchmarkServiceTest.php
8. tests/Unit/MarketIntelligence/PricingPositionServiceTest.php
```

**Doğrulama:**

```
php artisan test --filter=MarketIntelligence
→ pricing position doğru tespit edilmeli
→ benchmark doğru hesaplanmalı
→ comp < 5 → güvenilirlik "dusuk"
→ m2 eksik → "veri yetersiz"
```

**Tahmini etki:** Admin ilan detayında "bu ilan piyasanın %22 üstünde fiyatlandırılmış, benchmark: 23 ilan" görebilir. Tek bu bile karar kalitesini artırır.
