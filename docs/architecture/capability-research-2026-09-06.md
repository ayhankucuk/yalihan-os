# Derin Araştırma Raporu — Proje Yetenekleri ve Araştırılması Gereken Görevler

**Tarih:** 2026-09-06
**Kapsam:** P5 Phase 1 (Action Center) sonrası, mevcut yeteneklerin derinlemesine incelenmesi ve sonraki fazlar için araştırılması gereken görevlerin belirlenmesi
**Metodoloji:** 94+ AI service sınıfı, 9 CQRS projection modeli, 5 Hermes workforce event'i, 6 Cortex domain servisi, mevcut araştırma dokümanları ve PHASE2-ROADMAP'in tam kapsamlı analizi

---

## 1. Mevcut Yetenek Haritası (Capability Landscape)

### 1.1 AI Servis Katmanı — 94+ Sınıf

Proje, `app/Services/AI/` altında 94+ servis sınıfı barındıran devasa bir AI ekosistemine sahiptir. Bu servisler 7 fonksiyonel gruba ayrılır:

#### Grup A: Advisor Command Center (4 modül — Stateless)
| Servis | Sorumluluk | Veri Kaynağı | Persistans |
|--------|-----------|-------------|------------|
| `DealRadarService` | Hızlı satılacak ilanları tespit et (8-sinyal skorlama) | CQRS Projections (ListingVelocity, MarketTrend) | ❌ Yok — her istekte yeniden hesaplanır |
| `OpportunityEngineService` | Fırsat tespiti (5 tip: underpriced, high_buyer_match, SEO, low_quality, stale) | CQRS Projections (ListingSearch, BuyerInterest, MarketTrend) | ❌ Yok |
| `PortfolioDoctorService` | Portföy sağlık teşhisi (9 problem kategorisi) | CQRS Projections + Ilan modeli | ❌ Yok |
| `BuyerMatchQueueService` | Alıcı eşleşme kuyruğu (HOT/WARM/WATCH/LOW tier) | BuyerMatchDetectionService | ❌ Yok |

**Kritik Bulgu:** Tüm 4 modül stateless'tır. `AdvisorCommandCenterService::getCommandCenterData()` her HTTP isteğinde 30 ilan × 4 modül = 120+ DB sorgusu çalıştırır. Sonuçlar hiçbir yere yazılmaz.

#### Grup B: Cortex Domain Servisleri (6 modül)
| Servis | Sorumluluk | AI Sağlayıcı |
|--------|-----------|-------------|
| `CortexPredictionService` | Churn risk + anlaşma olasılığı tahmini | Ollama / OpenAI |
| `CortexMatchingService` | Alıcı eşleşme + satış eşleşme | BuyerMatchDetection pipeline |
| `CortexIntelligenceService` | Lokasyon analizi + değerleme + pazar trendi | TKGM + Ollama |
| `CortexQualityService` | İlan kalite kontrol | AI vision + heuristik |
| `CortexContentService` | İçerik üretimi (açıklama, başlık) | OpenAI / Ollama |
| `CortexTeamService` | Takım performans analizi | DB agregasyon |

#### Grup C: Matching Pipeline (5 servis)
| Servis | Sorumluluk |
|--------|-----------|
| `BuyerMatchDetectionService` | Alıcı adaylarını tespit et (Talep → Ilan eşleşme) |
| `BuyerMatchScoringService` | Eşleşme skorlama (price, location, features, rooms, intent, activity, churn) |
| `BuyerMatchFormatterService` | Eşleşme sebeplerini formatla |
| `BuyerMatchTelemetryService` | Eşleşme metrikleri kaydet |
| `BuyerIntentExtractionService` | Alıcı niyet analizi |

#### Grup D: Fiyat/Değerleme Zekası (5 servis)
| Servis | Sorumluluk | Veri Kaynağı |
|--------|-----------|-------------|
| `SellerStrategyService` | Satıcı stratejisi (5 strateji: OVERPRICED_RISK → UNDERPRICED_SIGNAL) | CQRS Projections |
| `MarketValuationService` | Otomatik değerleme (emsal analizi, IQR outlier filtreleme) | `market_listings` tablosu |
| `CortexPriceForecastService` | Fiyat tahmini (mevsimsellik + trend) | `IlanPriceHistory` |
| `AiPricingService` | AI tabanlı fiyat önerisi | OpenAI |
| `PricingIntelligenceSyncService` | Piyasa fiyat senkronizasyonu | Dış kaynak |

#### Grup E: Churn/Risk (2 servis)
| Servis | Hedef | Faktörler |
|--------|-------|----------|
| `ChurnRiskService` | İlan churn riski (0-100) | İlan yaşı, görüntülenme, güncelleme, yetki belgesi |
| `KisiChurnService` | Kişi (müşteri) churn riski (0-100) | Etkileşim yaşı, talep yaşı, pipeline stage |

#### Grup F: Semantic/Embedding (3 servis)
| Servis | Sorumluluk | Altyapı |
|--------|-----------|---------|
| `EmbeddingService` | Vektör embedding (OpenAI text-embedding-3-small / Ollama nomic-embed-text) | Hybrid (cloud + local) |
| `SemanticSearchService` | Semantik arama (ilan embedding) | Ollama API |
| `RetrievalService` | RAG retrieval | Embedding + DB |

#### Grup G: Workforce/Hermes Pipeline (5 servis)
| Servis | Sorumluluk |
|--------|-----------|
| `HermesDispatcher` | Event → handler dispatch (sync + async) |
| `HermesRegistry` | Event-handler kayıt defteri |
| `HermesService` | Event bus ana servis |
| `HermesReplayService` | Event replay |
| `WorkforceService` | Dashboard metrikleri (event, execution, chain, lifecycle) |

### 1.2 CQRS Projection Katmanı (9 Read Model)
| Projection | Kullanan Servisler | Doluluk Durumu |
|-----------|-------------------|----------------|
| `ListingVelocityProjection` | DealRadar, PortfolioDoctor, SellerStrategy | ⚠️ Veri akışı belirsiz — projection'ı dolduran projector sınıfı bulunamadı |
| `MarketTrendProjection` | DealRadar, OpportunityEngine, PortfolioDoctor, SellerStrategy | ⚠️ Aynı |
| `ListingSearchProjection` | OpportunityEngine | ⚠️ Aynı |
| `BuyerInterestProjection` | OpportunityEngine | ⚠️ Aynı |
| `TalepMatchProjection` | PortfolioDoctor, SellerStrategy | ⚠️ Aynı |
| `BuyerIntentProjection` | (Tespit edilemedi) | ⚠️ Kullanılmıyor olabilir |
| `IlanReadModel` | (Tespit edilemedi) | ⚠️ Kullanılmıyor olabilir |
| `LeadReadModel` | (Tespit edilemedi) | ⚠️ Kullanılmıyor olabilir |
| `KisiReadModel` | (Tespit edilemedi) | ⚠️ Kullanılmıyor olabilir |

**Kritik Bulgu — DOĞRULANDI:** 9 CQRS projection modeli tanımlı. İki farklı projection sistemi mevcut:

1. **`proj_listings` tablosu** — `ListingProjector` listener tarafından `ListingCreated`/`ListingUpdated` event'leri ile doldurulur. Idempotent (`proj_event_offsets` ile). ✅ Çalışıyor.
2. **`listing_velocity_projections`, `market_trend_projections`, `listing_search_projection` vb. tablolar** — AI servislerinin okuduğu tablolar. `ListingVelocityService::syncVelocity()` ile doldurulur, ancak kod yorumu "Simulation: In a real system, these would come from Analytics/Logs" diyor — yani **mock/simulation mode**.

**Sonuç:** AI servisleri (`DealRadarService`, `PortfolioDoctorService`, `OpportunityEngineService`) `listing_velocity_projections` ve `market_trend_projections` tablolarından okuyor. Bu tablolar `ListingVelocityService` tarafından dolduruluyor ama veri kaynağı "simulation" — gerçek analytics/log verisi değil. `DealRadarService:96-97` ve `PortfolioDoctorService:67-68` içinde `rand()` çağrıları var, bu da projection'ların çoğu zaman boş olduğunu doğrular.

### 1.3 Hermes Workforce Event Zinciri
```
portfolio.created (step 0)
    └─▶ workforce.workspace.created (step 1)
            └─▶ workforce.photo_analysis.completed (step 2)
                    └─▶ workforce.description.completed (step 3)
                            └─▶ workforce.property_score.calculated (step 4)
                                    └─▶ workforce.publishing.decision_ready (step 5)
                                            └─▶ workforce.notification.sent (step 6)
```

**Mevcut Event Sınıfları (5/7):**
- `PropertyWorkspaceCreated` ✅
- `PhotoAnalysisCompleted` ✅
- `DescriptionCompleted` ✅
- `PropertyScoreCalculated` ✅
- `PublishingDecisionReady` ✅
- `portfolio.created` ❌ Event sınıfı yok
- `workforce.notification.sent` ❌ Event sınıfı yok

### 1.4 Action Center (P5 Phase 1 — Tamamlandı)
- 14 event-to-action mapping (10 listener aktif)
- `ActionCenterService` — 7 public metod, 11 private generator
- `gorevler` tablosuna 10 additive kolon + tenant_id
- 6/6 integration test PASS
- Idempotency: source_event + entity_id kombinasyonu
- Tenant izolasyonu: BelongsToTenant trait

---

## 2. Araştırılması Gereken Görevler (12 Bulgu)

### ARAŞTIRMA-1: AdvisorCommandCenter → Action Center Persistans Gap

**Öncelik:** 🔴 KRİTİK (Sprint 15 Phase 2 blocker)
**Durum:** AÇIK

**Mevcut Durum:**
`AdvisorCommandCenterService::getCommandCenterData()` her HTTP isteğinde 4 modülü çalıştırır, `buildPriorityActions()` ile öncelikli aksiyonlar üretir, ancak bu aksiyonlar hiçbir yere yazılmaz. Sonuçlar ekranda gösterilir ve kaybolur.

`ActionCenterService` ise event-driven olarak çalışır — domain event geldiğinde `gorevler` tablosuna kayıt yazar. Ancak bu iki sistem birbirinden tamamen bağımsızdır.

**Gap:**
```
AdvisorCommandCenter (stateless, computed) ──X── ActionCenter (persistent, event-driven)
```

**Araştırma Soruları:**
1. AdvisorCommandCenter'ın ürettiği `priority_actions` array'i `gorevler` tablosuna persist edilmeli mi?
2. Eğer evet: periyodik bir job mu (örn. her 30 dakikada bir çalışıp AI önerilerini Gorev olarak yazacak), yoksa on-demand mı (kullanıcı "bu aksiyonu kabul et" dediğinde mi)?
3. `source_module` alanı `advisor_command_center` olan Gorev'ler ile `source_event` ile gelen Gorev'ler arasında öncelik çakışması nasıl çözülecek?
4. AI önerileri `ai_confidence_score` ve `ai_reasoning` alanlarını doldurmalı mı? (AdvisorCommandCenter şu an confidence üretmiyor)

**Önerilen Yaklaşım:**
- `SyncAdvisorActionsJob`: Periyodik (cron) çalışan bir job. `AdvisorCommandCenterService::buildPriorityActions()` çıktısını alır, her bir aksiyon için `source_module = 'advisor_command_center'` ile `gorevler` tablosuna yazar. Idempotency: `source_module + listing_id + action_label` kombinasyonu.

---

### ARAŞTIRMA-2: CQRS Projection Doluluk Durumu

**Öncelik:** 🔴 KRİTİK (Tüm AI servislerin doğruluğu buna bağlı)
**Durum:** AÇIK

**Mevcut Durum:**
9 CQRS projection modeli tanımlı. Bu modelleri dolduran projector/handler sınıfları bulunamadı. AI servisleri içinde `rand()` çağrıları var:

```php
// DealRadarService.php:96-97
if ($searchFrequency === 0) $searchFrequency = rand(10, 80);
if ($buyerMatchDensity === 0) $buyerMatchDensity = rand(20, 90);

// PortfolioDoctorService.php:67-68
$listingViewVelocity = min(100, $velocity?->view_count ?? rand(10, 80));
$imageQualityScore = rand(40, 95);
```

**Araştırma Soruları:**
1. Projection tabloları boş mu? (`SELECT COUNT(*) FROM listing_velocity_projections` vb.)
2. Bu tabloları dolduran projector sınıfları var mı, yoksa henüz yazılmadı mı?
3. Eğer boşsa, AI servisleri production'da `rand()` ile rastgele skor mu üretiyor?
4. Projection doldurma stratejisi ne olmalı: event listener ile mi, scheduled projector job ile mi, yoksa raw SQL ETL ile mi?

**Risk:** Eğer projection'lar boşsa, AdvisorCommandCenter dashboard'undaki tüm "AI önerileri" rastgele sayılara dayanıyor demektir. Bu, production'da yanlış iş kararlarına yol açar.

---

### ARAŞTIRMA-3: Hermes Workforce Chain Eksik Event'leri

**Öncelik:** 🟠 YÜKSEK (Sprint 14→15 geçiş için gerekli)
**Durum:** AÇIK

**Mevcut Durum:**
Workforce chain 7 adımdan oluşuyor (0-6), ancak sadece 5 event sınıfı var. `portfolio.created` (step 0) ve `workforce.notification.sent` (step 6) event sınıfları eksik.

**Araştırma Soruları:**
1. `portfolio.created` event'i nerede dispatch ediliyor? (Event sınıfı yok ama chain map'te var)
2. `workforce.notification.sent` event'i hiç implement edildi mi?
3. Chain'in ilk ve son adımlarının eksik olması, workforce pipeline'ın tamamlanmamış olduğu anlamına mı geliyor?
4. Action Center, `PropertyWorkspaceCreated` event'ini dinlemeli mi? (Şu an dinlemiyor — sadece `PhotoAnalysisCompleted` ve `PublishingDecisionReady` dinliyor)

---

### ARAŞTIRMA-4: Churn Risk → Action Center Entegrasyonu

**Öncelik:** 🟠 YÜKSEK (Sprint 15 Phase 2)
**Durum:** AÇIK

**Mevcut Durum:**
İki churn servisi var:
- `ChurnRiskService::calculateChurnRisk(Ilan)` → 0-100 skor (ilan bazlı)
- `KisiChurnService::calculateChurnRisk(Kisi)` → 0-100 skor (kişi bazlı)

Ancak bu servislerin sonucu hiçbir event tetiklemiyor. Yüksek churn riski tespit edildiğinde otomatik bir Gorev oluşturulmuyor.

**Araştırma Soruları:**
1. Churn risk > 70 olduğunda otomatik "Müşteri ile iletişime geç" Gorev'i oluşturulmalı mı?
2. Bu Gorev'ler periyodik bir job ile mi (örn. günde 1 kez tüm ilan/kişiler taranır), yoksa event-driven mı?
3. `ChurnRiskService` ve `KisiChurnService` arasında birleşik bir churn event mimarisi mi kurulmalı?
4. Action Center'ın `PRIORITY_MAP`'ine churn-bazlı aksiyonlar eklenmeli mi? (örn. `churn_alert_ilan` → acil, `churn_alert_kisi` → yuksek)

**Önerilen Yaklaşım:**
- `ChurnScanJob`: Günde 1 kez çalışan scheduled job. Tüm aktif ilanları ve kişileri tarar, churn > 70 olanlar için `ChurnRiskDetected` event'i dispatch eder. Bu event'i dinleyen bir listener, Action Center üzerinden Gorev oluşturur.

---

### ARAŞTIRMA-5: BuyerMatch → Action Center Entegrasyonu

**Öncelik:** 🟠 YÜKSEK (Sprint 15 Phase 2)
**Durum:** AÇIK

**Mevcut Durum:**
`BuyerMatchQueueService::getMatchesForQueue()` her istekte eşleşme hesaplar. `urgency_signal = 'AT_RISK'` veya `match_tier = 'HOT'` olan eşleşmeler için otomatik Gorev oluşturulmuyor.

**Araştırma Soruları:**
1. HOT + HIGH_INTENT eşleşme tespit edildiğinde otomatik "Alıcıyı ara" Gorev'i oluşturulmalı mı?
2. Bu Gorev'ler periyodik mi, yoksa yeni ilan yayınlandığında event-driven mı?
3. `BuyerMatchDetectionService::detectForListing()` sonucu bir event'e wrap'lenmeli mi? (örn. `BuyerMatchDetected`)
4. AT_RISK alıcılar için churn ile entegre bir "kurtarma" aksiyonu mu oluşturulmalı?

---

### ARAŞTIRMA-6: SellerStrategy → Action Center Entegrasyonu

**Öncelik:** 🟡 ORTA (Sprint 15 Phase 2/3)
**Durum:** AÇIK

**Mevcut Durum:**
`SellerStrategyService::generateSellerStrategy()` 5 strateji üretir (OVERPRICED_RISK → UNDERPRICED_SIGNAL). `advisor_recommendation` alanı doğal dil tavsiyesi içerir. Ancak bu stratejiler Gorev'e dönüştürülmüyor.

**Araştırma Soruları:**
1. `OVERPRICED_RISK` tespit edildiğinde otomatik "Satıcı ile fiyat görüşmesi" Gorev'i oluşturulmalı mı?
2. `UNDERPRICED_SIGNAL` tespit edildiğinde "Fiyat optimizasyonu öner" Gorev'i mi?
3. Bu stratejiler periyodik olarak mı taranmalı, yoksa `IlanPriceChanged` event'inde mi tetiklenmeli?
4. `SellerStrategyService` çıktısındaki `recommended_price_range` verisi Gorev'in `ai_reasoning` alanına yazılmalı mı?

---

### ARAŞTIRMA-7: Semantic Search / RAG → Knowledge Core (Sprint 16)

**Öncelik:** 🟡 ORTA (Sprint 16 hazırlık)
**Durum:** AÇIK

**Mevcut Durum:**
- `EmbeddingService`: OpenAI (text-embedding-3-small, 1536d) ve Ollama (nomic-embed-text, 768d) destekli
- `SemanticSearchService`: İlan embedding'leri `ilan_embeddings` tablosunda
- `RetrievalService`: RAG retrieval
- `ConversationalAdvisorService`: 8 intent'li doğal dil sorgu işleme

**Araştırma Soruları:**
1. `ilan_embeddings` tablosu dolu mu? Kaç ilan embedding'e sahip?
2. Embedding'ler ne zaman oluşturuluyor? İlan yayınlandığında otomatik mi, manuel mi?
3. Sprint 16 (Knowledge Core AI) için Knowledge Graph veri kaynağı ne olacak?
4. Mevcut `ConversationalAdvisorService`'in 8 intent'i Knowledge Core'a taşınmalı mı?
5. RAG retrieval kalitesi yeterli mi? Retrieval evaluation metrikleri var mı?

---

### ARAŞTIRMA-8: CortexLearningService → AI Feedback Loop

**Öncelik:** 🟡 ORTA (Sprint 16 hazırlık)
**Durum:** AÇIK

**Mevcut Durum:**
`CortexLearningService::analyzeQualityOutcomes()` — read-only analytics. `AiLog` üzerinden kalite skorları, publish kararları ve override'ları analiz eder. "Advisory only" — AI çıktısı konfigürasyon değiştiremez.

**Araştırma Soruları:**
1. Learning service'in ürettiği "recommendation"lar gerçekten bir yere yazılıyor mu, yoksa sadece API cevabında mı dönüyor?
2. AI öneri kalitesi zamanla iyileşiyor mu? Metrik var mı?
3. Action Center'da oluşturulan Gorev'lerin tamamlanma/iptal oranları `AiLog`'a yazılarak learning loop'a beslenebilir mi?
4. `ai_reasoning` alanı learning için kullanılabilir mi? (Hangi AI önerisi hangi sonucu doğurdu?)

---

### ARAŞTIRMA-9: Multi-Tenant AI Servis İzolasyonu

**Öncelik:** 🟠 YÜKSEK (Güvenlik)
**Durum:** AÇIK

**Mevcut Durum — DOĞRULANDI:**

✅ **`Ilan` modeli `BelongsToTenant` trait kullanıyor** (satır 12, 102). `TenantScope` global scope otomatik uygulanır. `DealRadarService`, `PortfolioDoctorService` gibi servisler `Ilan::where(...)` sorgularında tenant filtresi alır.

❌ **Ancak CQRS Projection modellerinde tenant izolasyonu YOK:**
- `ListingVelocityProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil
- `MarketTrendProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil
- `ListingSearchProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil
- `BuyerInterestProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil
- `TalepMatchProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil
- `BuyerIntentProjection` — `BelongsToTenant` yok, `tenant_id` fillable değil

✅ Sadece Read Model'lerde `tenant_id` var: `IlanReadModel`, `KisiReadModel`, `LeadReadModel` (fillable içinde).

⚠️ **`market_trend_projections` tablosunda `tenant_id` kolonu var** (default 'SYSTEM'), ancak model katmanında `BelongsToTenant` trait yok — query-level'da filtre uygulanmıyor.

**Risk Değerlendirmesi:**
- `Ilan` üzerinden çalışan AI servisleri (DealRadar, PortfolioDoctor) → ✅ Tenant-safe (TenantScope auto-applied)
- CQRS Projection üzerinden çalışan AI servisleri (OpportunityEngine `ListingSearchProjection::query()`) → ❌ Cross-tenant veri okuyor
- `ListingSearchProjection::all()` (OpportunityDetectionService:17) → ❌ Tüm tenant'ların verisi

**Araştırma Soruları:**
1. `ListingSearchProjection` tablosunda `tenant_id` kolonu var mı? (Migration kontrol edilmeli)
2. Eğer yoksa, OpportunityEngine cross-tenant fırsatlar mı gösteriyor?
3. Projection tablolarına `tenant_id` eklenmeli mi, yoksa `BelongsToTenant` trait mi eklenmeli?
4. `BuyerMatchDetectionService::detectForListing()` — `Talep` modeli üzerinden çalışıyor, `Talep` modelinde `BelongsToTenant` var mı?

---

### ARAŞTIRMA-10: Action Center Phase 2 — Auto-Assignment Stratejisi

**Öncelik:** 🟡 ORTA (Sprint 15 Phase 2)
**Durum:** PLANLANDI

**Mevcut Durum:**
`ActionCenterService::assignAction(Gorev $gorev, int $userId)` metodu manuel atama yapıyor. Otomatik atama stratejisi henüz yok.

**Araştırma Soruları:**
1. Round-robin mi, workload-balance mı, skill-based mi?
2. Danışman'ın mevcut iş yükü nasıl hesaplanacak? (Aktif Gorev sayısı mı, toplam saat mi?)
3. Atama yapıldığında danışmana notification gönderilmeli mi? (Mevcut `CortexNotificationService` kullanılabilir mi?)
4. Atama reddedilebilir mi? (Danışman "bu işi yapamam" diyebilir mi?)
5. SLA süresi dolan atama otomatik olarak başka danışmana mı aktarılır?

---

### ARAŞTIRMA-11: Action Center Phase 3 — Evidence Tracking

**Öncelik:** 🟡 ORTA (Sprint 15 Phase 3)
**Durum:** PLANLANDI

**Mevcut Durum:**
`ActionCenterService::trackActionEvidence()` metodu stub olarak var — `gorev_takip` tablosuna yazıyor. Mimari doküman `action_evidence` tablosu öneriyor.

**Araştırma Soruları:**
1. `action_evidence` tablosu mu oluşturulmalı, yoksa mevcut `gorev_takip` tablosu yeterli mi?
2. Evidence tipleri neler? (fotoğraf, not, sistem log, AI analizi)
3. Evidence dosyaları nerede saklanacak? (Google Drive, local storage, S3?)
4. Evidence'e AI analizi eklenebilir mi? (örn. "bu fotoğrafta su kaçağı var" tespiti)
5. Evidence, Knowledge Core'a (Sprint 16) beslenebilir mi? (Geçmiş çözümlerden öğrenme)

---

### ARAŞTIRMA-12: Migration Drift — ai_saglayici_profilleri Split-Brain

**Öncelik:** 🟡 ORTA (Teknik borç)
**Durum:** AÇIK (2026-09-04 araştırmasından carry-forward)

**Mevcut Durum:**
İki migration aynı kavram için iki ayrı tablo yaratıyor:
- `ai_provider_profiles` (İngilizce kolonlar) → `AiProviderProfile` modeli → `ProviderOptimizationService`
- `ai_saglayici_profilleri` (Türkçe kolonlar) → `AiSaglayiciProfili` modeli → `ProviderSelectorService`

**Araştırma Soruları:**
1. Bu split-brain durumu Action Center'ı etkiliyor mu? (AI servisleri provider seçerken hangi tabloyu kullanıyor?)
2. `ProviderSelectorService` ve `ProviderOptimizationService` aynı AI sağlayıcı için farklı skor mu üretiyor?
3. Birleştirme migration'ı riskli mi? (İki tablo arası veri taşınması)

---

## 3. Entegrasyon Noktaları Haritası

### 3.1 Mevcut Entegrasyonlar (Çalışan)
```
Domain Events ──▶ EventServiceProvider ──▶ Action Center Listeners ──▶ gorevler tablosu
                                                                              │
Reservation Events ──▶ CreateOperationalTasksJob ──▶ OperationalGorevService ──┘
```

### 3.2 Eksik Entegrasyonlar (Araştırılması Gereken)
```
AdvisorCommandCenter ──X──▶ gorevler (persistans yok)
ChurnRiskService ──────X──▶ gorevler (event yok)
KisiChurnService ──────X──▶ gorevler (event yok)
SellerStrategyService ─X──▶ gorevler (event yok)
BuyerMatchQueueService ─X──▶ gorevler (event yok)
CortexLearningService ─X──▶ gorevler (feedback loop yok)
```

### 3.3 Önerilen Entegrasyon Mimarisi (Phase 2)
```
                    ┌─────────────────────────────────────┐
                    │         DOMAIN EVENTS               │
                    │ (IlanCreated, LeadCreated, vb.)      │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │      ACTION CENTER (Phase 1 ✅)      │
                    │  Event → Gorev mapping (10 listeners)│
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │    AI INSIGHT JOBS (Phase 2 ⏳)      │
                    │                                      │
                    │  SyncAdvisorActionsJob (periyodik)   │
                    │  ChurnScanJob (günde 1x)             │
                    │  BuyerMatchScanJob (yeni ilanda)      │
                    │  SellerStrategyJob (fiyat değişiminde)│
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │      PRIORITY & ASSIGNMENT           │
                    │  (Phase 2 ⏳)                        │
                    │  - Auto-assignment (workload)        │
                    │  - SLA tracking                      │
                    │  - Escalation                        │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │     EVIDENCE & TRACKING (Phase 3)    │
                    │  - action_evidence tablosu          │
                    │  - Lifecycle state machine           │
                    │  - AI feedback loop                  │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │   KNOWLEDGE CORE AI (Sprint 16)      │
                    │  - RAG + Knowledge Graph             │
                    │  - Explainable AI (citation)         │
                    │  - Learning from evidence            │
                    └─────────────────────────────────────┘
```

---

## 4. Öncelik Sıralaması ve Sprint Eşleştirme

| # | Araştırma | Öncelik | Hedef Sprint | Blocker mı? |
|---|-----------|---------|-------------|-------------|
| 1 | AdvisorCommandCenter → Action Center persistans | 🔴 KRİTİK | Sprint 15 Phase 2 | EVET — Phase 2 blocker |
| 2 | CQRS Projection doluluk durumu | 🔴 KRİTİK | Sprint 15 Phase 2 | EVET — AI doğruluğu |
| 3 | Hermes Workforce eksik event'leri | 🟠 YÜKSEK | Sprint 14→15 geçiş | HAYIR — ama chain tam değil |
| 4 | Churn Risk → Action Center entegrasyonu | 🟠 YÜKSEK | Sprint 15 Phase 2 | HAYIR |
| 5 | BuyerMatch → Action Center entegrasyonu | 🟠 YÜKSEK | Sprint 15 Phase 2 | HAYIR |
| 6 | SellerStrategy → Action Center entegrasyonu | 🟡 ORTA | Sprint 15 Phase 2/3 | HAYIR |
| 7 | Semantic Search / RAG → Knowledge Core | 🟡 ORTA | Sprint 16 | HAYIR |
| 8 | CortexLearning → AI Feedback Loop | 🟡 ORTA | Sprint 16 | HAYIR |
| 9 | Multi-Tenant AI servis izolasyonu | 🟠 YÜKSEK | Sprint 15 Phase 2 | EVET — güvenlik |
| 10 | Action Center Phase 2 — Auto-assignment | 🟡 ORTA | Sprint 15 Phase 2 | HAYIR |
| 11 | Action Center Phase 3 — Evidence tracking | 🟡 ORTA | Sprint 15 Phase 3 | HAYIR |
| 12 | Migration drift — ai_saglayici_profilleri | 🟡 ORTA | Teknik borç | HAYIR |

---

## 5. Kritik Risk Değerlendirmesi

### 5.1 Production Risk: AI Servisleri `rand()` Kullanıyor

**Risk Seviyesi:** 🔴 KRİTİK

`DealRadarService` ve `PortfolioDoctorService` içinde `rand()` çağrıları var. Eğer CQRS projection'lar boşsa, bu servisler production'da rastgele skor üretiyor demektir. Danışmanlar bu skorlara bakarak "HOT_DEAL" ilanları arıyor — ama skor rastgele.

**Aksiyon:** ARAŞTIRMA-2 acil olarak incelenmeli. Projection tablolarının doluluk durumu `SELECT COUNT(*)` ile kontrol edilmeli.

### 5.2 Security Risk: CQRS Projection'larda Tenant İzolasyonu Yok

**Risk Seviyesi:** 🟠 YÜKSEK (Kısmen doğrulandı)

✅ **`Ilan` modeli `BelongsToTenant` kullanıyor** — `DealRadarService`, `PortfolioDoctorService` gibi `Ilan` üzerinden çalışan servisler tenant-safe.

❌ **CQRS Projection modelleri `BelongsToTenant` KULLANMIYOR** — `ListingSearchProjection`, `ListingVelocityProjection`, `MarketTrendProjection`, `BuyerInterestProjection`, `TalepMatchProjection`, `BuyerIntentProjection` modellerinde tenant izolasyonu yok.

**Kesin risk:** `OpportunityEngineService::getOpportunities()` → `ListingSearchProjection::query()` → tüm tenant'ların verisi. `OpportunityDetectionService::detect()` → `ListingSearchProjection::all()` → tüm tenant'ların ilanları.

**Aksiyon:** CQRS projection tablolarına `tenant_id` kolonu eklenmeli ve projection modellerine `BelongsToTenant` trait eklenmeli. Veya projection sorgularına manuel `where('tenant_id', ...)` filtresi eklenmeli.

### 5.3 Architecture Risk: Stateless → Persistent Gap

**Risk Seviyesi:** 🟡 ORTA

`AdvisorCommandCenterService` her istekte 120+ DB sorgusu çalıştırıyor ve sonuçları kaydetmiyor. Production'da bu, her dashboard yüklemede yüksek DB yükü + kaybolan AI önerileri anlamına gelir.

**Aksiyon:** ARAŞTIRMA-1 ile çözülecek. `SyncAdvisorActionsJob` ile periyodik persistans.

---

## 6. Sonraki Adımlar

### Acil (Bu sprint):
1. ARAŞTIRMA-2: CQRS projection tablolarının doluluk durumunu kontrol et (`SELECT COUNT(*) FROM listing_velocity_projections` vb.)
2. ARAŞTIRMA-9: ✅ Doğrulandı — `Ilan` modelinde `BelongsToTenant` var. CQRS projection'larda YOK — projection modellerine `BelongsToTenant` eklenmeli
3. ARAŞTIRMA-1: `SyncAdvisorActionsJob` tasarımını başlat
4. ARAŞTIRMA-9 (devam): CQRS projection tablolarına `tenant_id` kolonu ekle (migration)

### Sprint 15 Phase 2:
4. ARAŞTIRMA-4: Churn → Action Center entegrasyonu
5. ARAŞTIRMA-5: BuyerMatch → Action Center entegrasyonu
6. ARAŞTIRMA-10: Auto-assignment stratejisi

### Sprint 15 Phase 3:
7. ARAŞTIRMA-11: Evidence tracking
8. ARAŞTIRMA-8: AI feedback loop

### Sprint 16:
9. ARAŞTIRMA-7: Knowledge Core RAG
10. ARAŞTIRMA-8: Learning loop tam entegrasyon

---

## 7. Özet

Bu araştırma, P5 Phase 1 (Action Center) tamamlandıktan sonra projenin mevcut yeteneklerini derinlemesine incelemiştir. 94+ AI servis sınıfı, 9 CQRS projection modeli, 5 Hermes workforce event'i ve 6 Cortex domain servisi analiz edilmiştir.

**En kritik bulgular:**
1. **AdvisorCommandCenter stateless → persistent gap** — AI önerileri kayboluyor, Gorev'e dönüştürülmüyor
2. **CQRS projection doluluk belirsizliği** — AI servisler `rand()` ile mock veri üretiyor olabilir
3. **Multi-tenant AI izolasyon eksikliği** — AI servisleri tenant filtresi yapmıyor olabilir
4. **Churn/BuyerMatch/SellerStrategy entegrasyon eksikliği** — 4 AI servisi Action Center'a bağlı değil

## 8. ARAŞTIRMA-2 Bulgu Detayı (2026-09-06 — SESSION 160)

### Durum: ✅ TAMAMLANDI

**Sorgu Sonuçları (Tüm projection tabloları — SQLite test DB):**

| Projection Tablo | Kayıt Sayısı | Durum |
|-----------------|-------------|-------|
| `listing_velocity_projections` | **0** | 🔴 BOŞ |
| `listing_search_projection` | **0** | 🔴 BOŞ |
| `buyer_interest_projections` | **0** | 🔴 BOŞ |
| `market_trend_projections` | **0** | 🔴 BOŞ |
| `talep_match_projection` | **0** | 🔴 BOŞ |
| `buyer_intent_projection` | **0** | 🔴 BOŞ |

**Kritik Risk Doğrulandı:**
- `DealRadarService::gatherSignals()` → satır 96-97: `searchFrequency` ve `buyerMatchDensity` sıfırsa `rand(10,80)` ve `rand(20,90)` ile **rastgele fallback** üretiyor
- `PortfolioDoctorService::calculateHealthScore()` → satır 67,70,86,89,95,98: 6 ayrı `rand()` fallback
- **Sonuç:** Projection'lar boşken tüm skorlar tamamen rastgele — HOT_DEAL/FAST_MOVING/WATCHLIST sınıflandırması anlamsız

**Aksiyon:** Projection rebuild mekanizması (`RebuildCqrsProjections`) tetiklenmeli veya cron job kurulmalı. AI skorlarının güvenilir olması için projection doluluk oranı %80+ olmalı.

---

## 9. ARAŞTIRMA-9 Bulgu Detayı (2026-09-06 — SESSION 160)

### Durum: ✅ DOĞRULANDI

**6/6 CQRS Projection Model İncelendi:**

| Model | Tenant İzolasyonu | Mevcut Scope | Risk |
|-------|------------------|--------------|------|
| `ListingVelocityProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🔴 Cross-tenant veri okunabilir |
| `ListingSearchProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🔴 Cross-tenant veri okunabilir |
| `BuyerInterestProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🔴 Cross-tenant veri okunabilir |
| `MarketTrendProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🟡 Lokasyon-bazlı, düşük risk |
| `TalepMatchProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🔴 Talep eşleşmeleri cross-tenant sızabilir |
| `BuyerIntentProjection` | ❌ YOK | `HasCountryScope` (ulke_id) | 🔴 Alıcı niyetleri cross-tenant sızabilir |

**OpportunityEngineService Kesin Risk:**
```php
// app/Services/AI/OpportunityEngineService.php:40-43
$buyerSignal = BuyerInterestProjection::where('listing_id', $listing->listing_id)->first();
$marketSignal = MarketTrendProjection::where('city', $listing->city)
    ->where('district', $listing->district)
    ->where('property_type', $listing->property_type)
```
→ `tenant_id` filtrelemesi yok. Tüm tenant'ların BuyerInterestProjection satırlarına erişim riski.

**Çözüm Planı:**
1. Her 6 tabloya `tenant_id` kolonu ekle (migration)
2. Her 6 modele `BelongsToTenant` trait ekle
3. Projection rebuild sonrası `tenant_id` backfill

---

## 10. ARAŞTIRMA-1 — SyncAdvisorActionsJob Tasarımı (2026-09-06)

### Hedef
`AdvisorCommandCenterService` stateless → persistent. Her HTTP isteğinde kaybolan AI önerileri, `gorevler` tablosuna kalıcı görev olarak yazılacak.

### Mimari Öneri

```
Schedule (her 15 dakikada bir)
  └── SyncAdvisorActionsJob
        ├── DealRadarService::getRadarListings()  → HOT_DEAL / FAST_MOVING tespit
        ├── OpportunityEngineService::getOpportunities() → fırsat tespit
        ├── PortfolioDoctorService::getPortfolios() → sağlık sorunu tespit
        └── ActionCenterService::createGorevFromSignal() → Gorev persist et
```

**Idempotency:** `source_event` + `entity_id` kombinasyonu ile tekrar engeli (ActionCenterService'de mevcut).

**Tenant izolasyonu:** Her iteration `tenant_id` bazlı döngü — mevcut `BelongsToTenant` kullanımı korunur.
Bu 12 araştırma görevi, Sprint 15 Phase 2/3 ve Sprint 16 için yol haritası oluşturmaktadır.
