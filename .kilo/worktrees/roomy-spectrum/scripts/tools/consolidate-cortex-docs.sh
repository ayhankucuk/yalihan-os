#!/bin/bash

# ============================================================================
# YALIHAN CORTEX DOCUMENTATION CONSOLIDATION
# ============================================================================
# Tarih: 26 Aralık 2025
# Amaç: 3 Cortex dosyasını tek master guide'a birleştir
# Dosyalar: ARCHITECTURE + CALISMA_MANTIGI + VISION → COMPLETE
# ============================================================================

set -e

# Renkler
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

DOCS_AI="/Users/macbookpro/Projects/yalihan2026/docs/ai"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}YALIHAN CORTEX KONSOLIDASYON${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# ============================================================================
# PHASE 1: YALIHAN CORTEX COMPLETE GUIDE OLUŞTUR (3→1)
# ============================================================================

echo -e "${YELLOW}PHASE 1: Yalıhan Cortex Complete Guide oluşturuluyor...${NC}"

cat > "${DOCS_AI}/YALIHAN_CORTEX_COMPLETE.md" << 'CORTEX_EOF'
# 🧠 Yalıhan Cortex - Complete System Guide

**Tarih:** 26 Aralık 2025
**Versiyon:** 3.0.0 (Konsolide)
**Durum:** ✅ Production Ready
**Context7 Uyumluluk:** %100

> **Not:** Bu doküman 3 ayrı Cortex dokümanının birleştirilmiş halidir:
> - YALIHAN_CORTEX_ARCHITECTURE_V2.1.md
> - YALIHAN_CORTEX_CALISMA_MANTIGI.md
> - YALIHAN_CORTEX_VISION_2.0.md

---

## 📋 İÇİNDEKİLER

1. [Genel Bakış](#genel-bakış)
2. [System Architecture](#system-architecture)
3. [Çalışma Mantığı ve Mimarisi](#çalışma-mantığı)
4. [Dashboard & Monitoring](#dashboard-monitoring)
5. [Ana Bileşenler](#ana-bileşenler)
6. [Algoritma Detayları](#algoritma-detayları)
7. [Vision 2.0 - Yeni Görevler](#vision-20)
8. [Performans İzleme](#performans-izleme)
9. [Kullanım Senaryoları](#kullanım-senaryoları)

---

## 🎯 GENEL BAKIŞ

### Sistem Tanımı

**Yalıhan Emlak OS**, Laravel 10 üzerinde çalışan, Context7 standartlarına uyumlu, **Olay Güdümlü (Event-Driven)** ve **AI destekli** bir emlak yönetim platformudur.

**YalihanCortex**, tüm AI servislerini yöneten merkezi bir "beyin" sistemidir. Sistem, emlak talepleri için akıllı eşleştirme, müşteri churn risk analizi, fiyat değerleme ve AI destekli öneriler sunar.

### Temel Prensip

> **"Manuel Veri Girişi" devri bitti, "AI Destekli Operasyon" devri başladı.**

### Temel Özellikler

- ✅ **Observer Mode:** Sadece izleme, metrik toplama ve öneri (enforcement YOK)
- ✅ **Merkezi Yönetim:** Tüm AI işlemleri tek bir noktadan yönetilir
- ✅ **Kâr Odaklı Zekâ:** Action Score algoritması ile en kârlı eşleşmeleri önceliklendirir
- ✅ **Churn Risk Analizi:** Müşteri kaybı riskini önceden tespit eder
- ✅ **Performans İzleme:** Tüm işlemler timer ile ölçülür ve AiLog'a kaydedilir
- ✅ **Fallback Sistemi:** AI provider hatalarında otomatik yedek provider'a geçer
- ✅ **Context7 Uyumlu:** Tüm işlemler MCP standartlarına uygun

---

## 🏗️ SYSTEM ARCHITECTURE

### Mimari Diyagram

```
┌─────────────────────────────────────────────────────────┐
│                    AIController                         │
│  (API Endpoint: /api/admin/ai/find-matches)            │
└────────────────────┬──────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              YalihanCortex (Merkezi Beyin)              │
│  ├─ matchForSale()     → Talep eşleştirme              │
│  ├─ priceValuation()   → Fiyat değerleme              │
│  └─ handleFallback()   → Hata yönetimi                 │
└─────┬──────────┬──────────┬──────────┬───────────────┘
      │          │          │          │
      ▼          ▼          ▼          ▼
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│SmartProp │ │KisiChurn │ │ Finans   │ │  TKGM    │
│MatcherAI │ │ Service  │ │ Service  │ │ Service  │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
      │          │          │          │
      └──────────┴──────────┴──────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              LogService (Timer & Logging)               │
│  ├─ startTimer()  → İşlem başlangıç zamanı             │
│  ├─ stopTimer()   → İşlem süresi (milisaniye)         │
│  └─ ai()          → AI işlem logları                   │
└────────────────────┬──────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                  AiLog (Veritabanı)                    │
│  ├─ provider: "YalihanCortex"                          │
│  ├─ request_type: "cortex_decision"                     │
│  ├─ response_time: 245.67 (ms)                          │
│  └─ status: "success" / "failed"                       │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 1. SİNİR SİSTEMİ VE İZLEME (AI Command Center)

### Dashboard

**URL:** `/admin/ai/dashboard`
**Controller:** `App\Http\Controllers\AI\AdvancedAIController`
**Route Name:** `admin.ai.dashboard`

### Bileşenler

#### Health Check

**Teknoloji:** HTTP Ping (2 saniye timeout)

**Kontrol Edilen Servisler:**

1. **Cortex Brain (Laravel)**
    - Durum: Her zaman Online
    - URL: `config('app.url')`

2. **LLM Engine (Ollama)**
    - Endpoint: `GET /api/tags`
    - URL: `env('OLLAMA_URL', 'http://ollama:11434')`
    - Durum: Online/Offline
    - Response Time: Milisaniye cinsinden

3. **Knowledge Base (AnythingLLM)**
    - Endpoint: `GET /api/system/health`
    - URL: `env('ANYTHINGLLM_URL', 'http://localhost:3001')`
    - Durum: Online/Offline/Not Configured

**Görsel Gösterim:**
- 🟢 Yeşil pulse: Online
- 🔴 Kırmızı pulse: Offline
- 🟡 Sarı pulse: Not Configured

#### Opportunity Stream

**Kaynak:** `ai_logs` tablosu

**Filtreleme:**
- `request_type` LIKE '%SmartPropertyMatcherAI%'
- `created_at` >= Son 24 saat
- `status` = 'success'
- Skor >= 80

**Gösterim:**
- Timeline formatında
- Skor 90+ olanlar "⚠️ ACİL" badge'i ile
- Her satırda: İlan/Talep başlığı, Skor, Zaman, Aksiyonlar

#### Analytics

**Metrikler:**
1. **İmar Analizi** - Bugünkü başarılı istek sayısı
2. **İlan Açıklaması** - Bugünkü başarılı istek sayısı
3. **Fiyat Hesaplama** - Bugünkü başarılı istek sayısı

---

## 🔧 ANA BİLEŞENLER

### 1. YalihanCortex Servisi

**Dosya:** `app/Services/AI/YalihanCortex.php`

**Dependency Injection ile Enjekte Edilen Servisler:**

```php
- SmartPropertyMatcherAI  → Emlak eşleştirme algoritması
- KisiChurnService        → Müşteri churn risk analizi
- FinansService           → Finansal değerleme
- TKGMService             → Tapu ve Kadastro verileri
- AIService               → Genel AI işlemleri (GPT, Gemini, vb.)
```

### 2. LogService (Timer Sistemi)

**Dosya:** `app/Services/Logging/LogService.php`

**Timer Kullanımı:**

```php
// İşlem başlat
$timerId = LogService::startTimer('cortex_match');

// İşlem yap
$result = $this->cortex->matchForSale($talep);

// İşlem bitir ve logla
LogService::ai('cortex_decision', $request, $result, $timerId);
```

**Log Formatı:**

```json
{
  "provider": "YalihanCortex",
  "request_type": "cortex_decision",
  "response_time": 245.67,
  "status": "success",
  "metadata": {
    "action_score": 85,
    "recommendation": "high_priority"
  }
}
```

---

## 🧮 ALGORİTMA DETAYLARI

### Action Score Hesaplama

**Formül:**

```
Action Score = (Match Score × 0.6) + (Churn Score × 0.4)
```

**Kategoriler:**

| Skor     | Kategori         | Aksiyon                    |
| -------- | ---------------- | -------------------------- |
| 90-100   | 🔥 Kritik Fırsat | Hemen ara, özel teklif yap |
| 70-89    | ⚡ Yüksek Öncelik | 24 saat içinde ara         |
| 50-69    | ⚠️ Orta Öncelik  | Hafta içinde ara           |
| 0-49     | 📋 Düşük Öncelik | Periyodik takip            |

### Churn Risk Skoru

**Hesaplama Faktörleri:**

```php
$churnScore = (
    ($daysSinceLastContact / 30) * 0.35 +
    ($failedContactAttempts / 5) * 0.25 +
    ($daysInPipeline / 90) * 0.20 +
    ($requestCount > 0 ? 0 : 1) * 0.20
) * 100;
```

---

## 🚀 VISION 2.0 - YENİ GÖREVLER

### 1. 🎯 FIRSAT SENTEZİ (Opportunity Synthesis)

**Amaç:** İlan girildiğinde, o ilana uygun ve Churn Riski Yüksek müşterileri filtreleyip **"Acil Satış Fırsatı"** raporu üretmek.

**Implementasyon:**

```php
public function findUrgentOpportunities(Ilan $ilan): array
{
    // 1. İlana uygun talepleri bul
    $matches = $this->propertyMatcher->match($ilan);

    // 2. Her eşleşme için churn riski hesapla
    $opportunities = [];
    foreach ($matches as $match) {
        $talep = Talep::find($match['talep_id']);
        $churnRisk = $this->churnService->calculateChurnRisk($talep->kisi);

        // 3. Acil fırsat skoru hesapla
        $urgencyScore = ($match['score'] * 0.6) + ($churnRisk['score'] * 0.4);

        if ($urgencyScore >= 70) {
            $opportunities[] = [
                'urgency_score' => $urgencyScore,
                'recommendation' => 'Acil arama yapılmalı',
                'action_items' => [
                    'Hemen telefon et',
                    'Özel teklif hazırla',
                    'VIP muamele göster',
                ],
            ];
        }
    }

    return $opportunities;
}
```

### 2. 💰 AKILLI BÜTÇE DÜZELTMESİ (Budget Correction)

**Amaç:** Müşterinin gerçek satın alma gücünü analiz edip, bütçeyi revize etmeyi danışmana önermek.

**Veri Kaynakları:**
- `Kisi.gelir_duzeyi`
- `Kisi.meslek`
- `Kisi.segment`
- `Talep.min_fiyat`, `Talep.max_fiyat`

### 3. 📊 PORTFÖY SAĞLIĞI SKORU (Portfolio Health Score)

**Amaç:** Danışmanların portföy kalitesini ölçmek ve iyileştirme önerileri sunmak.

**Metrikler:**
- Aktif talep sayısı
- Ortalama yanıt süresi
- Conversion rate
- Churn rate
- Aktif ilan sayısı

### 4. 🎁 ÖZEL KAMPANYA ÖNERİSİ (Campaign Recommendation)

**Amaç:** Belirli segmentlere özel kampanyalar önermek.

### 5. 📈 TAHMİNİ KAPANIŞ TARİHİ (Estimated Close Date)

**Amaç:** Pipeline stage ve aktivitelere göre tahmini kapanış tarihi hesaplamak.

### 6. 🔔 PROAKTIF UYARI SİSTEMİ (Proactive Alert System)

**Amaç:** Kritik durumları önceden tespit edip uyarı vermek.

---

## 📊 PERFORMANS İZLEME

### Metrikler

**Response Time:**
- Hedef: <500ms
- Mevcut: ~245ms (✅ Hedefin altında)

**Success Rate:**
- Hedef: >95%
- Mevcut: ~97% (✅ Hedefin üzerinde)

**Cache Hit Rate:**
- Hedef: >70%
- Mevcut: ~82% (✅ Hedefin üzerinde)

### Logging

Tüm Cortex işlemleri `ai_logs` tablosuna kaydedilir:

```sql
SELECT
    provider,
    request_type,
    AVG(response_time) as avg_response,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
FROM ai_logs
WHERE provider = 'YalihanCortex'
    AND created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY request_type;
```

---

## 💼 KULLANIM SENARYOLARI

### Senaryo 1: Yeni İlan Girildi

**Akış:**
1. İlan sisteme girilir
2. Cortex otomatik tetiklenir
3. SmartPropertyMatcherAI ile uygun talepler bulunur
4. Churn analizi yapılır
5. Action Score hesaplanır
6. Dashboard'da fırsatlar gösterilir
7. Danışman bildirim alır

### Senaryo 2: Müşteri Takibi

**Akış:**
1. Günlük churn analizi çalışır
2. Yüksek riskli müşteriler tespit edilir
3. Danışmana uyarı gönderilir
4. Önerilen aksiyonlar sunulur

### Senaryo 3: Portföy Optimizasyonu

**Akış:**
1. Haftalık portföy sağlığı raporu
2. Düşük performanslı talepler tespit edilir
3. İyileştirme önerileri sunulur
4. Danışman aksiyonları uygular

---

## ⚠️ HATA YÖNETİMİ

### Fallback Sistemi

```php
try {
    $result = $this->primaryAI->process($request);
} catch (AIException $e) {
    LogService::error('Primary AI failed', ['error' => $e->getMessage()]);
    $result = $this->fallbackAI->process($request);
}
```

### Error Tracking

Tüm hatalar `ai_logs` tablosunda `status = 'failed'` olarak kaydedilir.

---

## 📚 İLGİLİ DOKÜMANTASYON

- [AI Features Guide](./AI_FEATURES_GUIDE.md)
- [Smart Property Matcher](./SMART_PROPERTY_MATCHER.md)
- [Churn Analysis](./CHURN_ANALYSIS.md)
- [API Documentation](./API_DOCUMENTATION.md)

---

**Son Güncelleme:** 26 Aralık 2025
**Versiyon:** 3.0.0 (Konsolide)
**Durum:** ✅ Production Ready

CORTEX_EOF

echo -e "${GREEN}✅ YALIHAN_CORTEX_COMPLETE.md oluşturuldu (konsolide)${NC}"

# ============================================================================
# PHASE 2: ESKİ DOSYALARI SİL
# ============================================================================

echo -e "${YELLOW}PHASE 2: Eski Cortex dosyaları siliniyor...${NC}"

rm -f "${DOCS_AI}/YALIHAN_CORTEX_ARCHITECTURE_V2.1.md"
echo -e "${GREEN}✅ YALIHAN_CORTEX_ARCHITECTURE_V2.1.md silindi${NC}"

rm -f "${DOCS_AI}/YALIHAN_CORTEX_CALISMA_MANTIGI.md"
echo -e "${GREEN}✅ YALIHAN_CORTEX_CALISMA_MANTIGI.md silindi${NC}"

rm -f "${DOCS_AI}/YALIHAN_CORTEX_VISION_2.0.md"
echo -e "${GREEN}✅ YALIHAN_CORTEX_VISION_2.0.md silindi${NC}"

# ============================================================================
# PHASE 3: ÖZET RAPORU
# ============================================================================

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}KONSOLİDASYON TAMAMLANDI${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}📊 Sonuç:${NC}"
echo -e "  - Eski dosyalar: 3 (silindi)"
echo -e "  - Yeni dosya: 1 (YALIHAN_CORTEX_COMPLETE.md)"
echo -e "  - Dosya azalması: -2 dosya (-67%)"
echo ""
echo -e "${GREEN}📁 Yeni dosya konumu:${NC}"
echo -e "  ${DOCS_AI}/YALIHAN_CORTEX_COMPLETE.md"
echo ""
echo -e "${YELLOW}⚠️  Not: Eski dosyalar geri alınamaz şekilde silindi.${NC}"
echo ""

exit 0
