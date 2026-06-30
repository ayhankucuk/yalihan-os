# Yalıhan AI OS — Sistem Özellik Haritası

> Oluşturulma: 2026-05-13 | Claude AI tarafından keşfedildi ve belgelendi
> Proje: EmlakPro / Yalıhan AI OS | Konum: /Users/macbookpro/dev/yalihan2026

---

## 1. Genel Mimari

**Platform:** Multi-tenant SaaS gayrimenkul yönetim sistemi  
**Backend:** PHP 8.2 / Laravel 11  
**Ölçek:**
- 321 Controller
- 555 Service
- 179 Model
- 47 Job
- 136 Artisan Command
- 44 API route dosyası
- 4 domain modülü (Emlak, Auth, TalepAnaliz, GovernanceCore)

---

## 2. Katmanlar (Mimari)

### 2.1 Cortex (AI Orchestration Brain)
Sistemin yapay zeka merkezi. Tüm AI kararları buradan geçer.

**Temel servisler:**
- `YalihanCortex` — Ana beyin, 30+ bağımlılık, tüm AI özelliklerini orkestre eder
- `AIOrchestrator` — HTTP katmanı ile AI servisleri arasındaki facade
- `RoutedCortexExecutor` — Ranked provider seçimi + otomatik fallback
- `CortexScoringService` — İlan kalite skoru (0-100, nexus+visual+content)
- `MatchingEngine` — Lead↔İlan eşleştirme (Haversine, bütçe, özellik, vision skoru)
- `CortexMatchService` — Yüksek seviye match facade
- `CortexPitchGenerator` — Satış pitch'i üretimi
- `CortexROIEngine` — Yatırım getirisi hesaplama
- `OpportunityHunter` — Fırsat tespiti
- `CortexLearningService` — Öğrenme ve optimizasyon
- `CortexKnowledgeService` — Bilgi bankası yönetimi

**AI Provider katmanı (Infrastructure):**
- `DeepSeekCortexProvider` — Birincil provider (circuit breaker, rate limit, retry:3)
- `OpenAICortexProvider` — Fallback provider
- `OllamaCortexAdapter` — Yerel LLM (llama3.1, yalihan.internal)
- `RoutedCortexExecutor` — Provider sıralaması ve otomatik geçiş
- `ProviderRegistry` — Provider kayıt ve erişim
- `ProviderSelectorPolicy` — Hangi görev hangi provider? politikası

**Agent katmanı:**
- `CortexAgent` — Bulgu tespiti, FindingDetected event emit eder
- `WatcherAgent` — Sistem izleme
- `ExecutionAgent` — Komut yürütme
- `OptimizerAgent` — Parametre optimizasyonu
- `GovernanceAgent` — Yönetişim kontrolü

### 2.2 PropertyHub (Domain Çekirdeği)
İş mantığının yaşadığı yer.

**İlan yönetimi:**
- İlan CRUD (IlanCrudService — tek yazma otoritesi)
- Kategori sistemi (IlanKategori, alt kategoriler)
- Özellik sistemi (IlanFeatureService — Nexus blueprint)
- Fotoğraf yönetimi (VisionAnalysisService ile AI analizi)
- Yayın tipi (Satılık, Kiralık, Günlük Kiralık, Yazlık)
- İmar durumu takibi
- Cortex skorlama (CortexScoringService)
- AI başlık ve açıklama üretimi (DataDrivenAIContentService)

**Talep/Lead yönetimi:**
- Lead CRUD (LeadRepository)
- Talep analizi (TalepAnaliz modülü)
- Buyer match tespiti (BuyerMatchDetectionService)
- Eşleştirme (MatchingEngine — Haversine mesafe + bütçe + özellik)

**CRM:**
- Kişi yönetimi (KisiRepository)
- Etkinlik takibi (KisiEtkilesimRepository)
- Pipeline yönetimi
- Follow-up otomasyonu (FollowUpAutomationService)
- Lead scoring (LeadScoringService)
- CRM Orchestrator (CRMOrchestratorService)

**Danışman sistemi:**
- Profil yönetimi
- AI asistan (DanismanAIService)
- Performans ve leaderboard
- Provider profili (AiRecomputeProviderProfiles)

### 2.3 GovernanceCore (Yönetişim Çekirdeği)

**Phase 4C — Telemetry (aktif geliştirme):**
- `GovernanceMetrics` — Redis INCR tabanlı metrik toplayıcı
- `GovernanceAnalytics` — Drift ve anomali tespiti (Week 2 ✅)
- `GovernanceAlerter` — Uyarı sistemi (Week 2 devam)
- `FlushGovernanceEventsJob` — Async DB flush (afterResponse)
- `GovernanceAlertCheckJob` — Periyodik alert kontrolü

**Enstrümantasyon trait'leri:**
- `RepositoryInstrumentation` — Repository yazma/okuma kaydı
- `CacheInstrumentation` — Cache operasyon kaydı
- `QueueInstrumentation` — Queue dispatch/execution kaydı

**SAB (Semantic Architecture Board):**
- 4 domain SEALED: CRM, TASK, FINANCE, GOVERNANCE
- SealRegistry ile kontrol
- `SabAutomationGuardService` — Otomasyon sınırları
- Bekçi (Yalihan Bekci) — 136 komuttan bazıları bekçi taraması yapar

**Governance kuralları:**
- Tenant Isolation (en ağır ihlal: cross-tenant erişim)
- Repository Authority (DB yazma sadece Repository üzerinden)
- Async Context Restoration (Queue'da tenant bağlamı geri yüklenmeli)
- Fail-Open (telemetri hatası iş akışını kesmez)
- Performance Budget (<10ms overhead)

### 2.4 N8N Workflow Automation

**Entegrasyon noktaları:**
- AI ilan taslağı üretimi (AIIlanTaslagiService → n8n webhook)
- AI mesaj taslağı (AIMessageService → n8n)
- AI sözleşme taslağı (AIContractService → n8n)
- Webhook controller: N8nWebhookController
- N8N UseCase'leri: ProcessAIIlanTaslagiUseCase, ProcessAIMesajTaslagiUseCase, ProcessAIContractDraftUseCase

**⚠️ UYARI:** N8N_WEBHOOK_URL .env'de tanımsız. Tüm n8n özellikleri şu an çalışmıyor.

---

## 3. AI Özellikleri (Kullanıcı Tarafı)

| Özellik | Servis | Durum |
|---|---|---|
| İlan başlığı üretimi | DataDrivenAIContentService | AI_DRY_RUN=true |
| İlan açıklaması üretimi | AIIlanTaslagiService | N8N URL eksik |
| İlan taslağı (Wizard) | WizardAIService, CopilotListingGenerator | Kısmi |
| Fiyat önerisi | AIOrchestrator::getPriceSuggestionsMetrics | Çalışıyor (DB sorgusu) |
| Fotoğraf analizi | VisionAnalysisService, LocalVisionService | Ollama gerektirir |
| Lead eşleştirme | MatchingEngine | Çalışıyor (N+1 problemi var) |
| Sözleşme taslağı | AIContractService | N8N URL eksik |
| Mesaj taslağı | AIMessageService | N8N URL eksik |
| ROI hesaplama | CortexROIEngine | Cortex bağımlı |
| Pitch üretimi | CortexPitchGenerator | Cortex bağımlı |
| Talep analizi | AIAnalizService | Çalışıyor |
| Portfolio doktor | PortfolioDoctorService | AI bağımlı |
| Ses/çağrı analizi | CallIntelligenceService, AudioService | Özel entegrasyon |
| Sesli arama | VoiceSearchService | Özel entegrasyon |
| Semantik arama | SemanticSearchService, EmbeddingService | Vektör DB gerektirir |
| AI çeviri | AITranslationService, ListingTranslationService | API bağımlı |
| Arsa analizi | AIArsaAnalizService | Deprecated model |
| AI kredi cüzdanı | AiWalletService | Balance bug var |

---

## 4. Zamanlayıcı Görevler (Cron)

| Görev | Sıklık |
|---|---|
| TCMB kur güncelleme | Günlük 10:00 |
| TestSprite auto-learn | Günlük 03:00 |
| Quality Gate (context7) | Günlük 03:00 + Her 6 saatte |
| Context7 query-scan | Saatlik |
| Context7 hot-fix auto-repair | Saatlik |
| Context7 smart-detect | Her 2 saatte |

---

## 5. Teknoloji Yığını

| Katman | Teknoloji |
|---|---|
| Backend | PHP 8.2 / Laravel 11 |
| Birincil AI | DeepSeek (deepseek-chat / deepseek-reasoner) |
| Fallback AI | OpenAI (gpt-4o-mini) |
| Yerel AI | Ollama (llama3.1) @ ollama.yalihanemlak.internal |
| Workflow | N8N |
| Veritabanı (prod) | MySQL |
| Veritabanı (test) | SQLite in-memory |
| Cache | Redis (PID 957, port 6379) |
| Queue | Redis + Laravel Horizon |
| Frontend | Blade + Alpine.js |
| Admin Panel | Livewire 2.0 (30s polling) |
| Hata takibi | Sentry |
| Request izleme | Laravel Telescope |
| Görev izleme | Laravel Horizon |
| Embed/Vektör | AnythingLLM |
| Tapu entegrasyonu | TKGM servisi |

---

## 6. Bilinen Yönetişim Borcu

| ID | Konu | Alan | Öncelik |
|---|---|---|---|
| GD-001 | bulkUpdateAktiflikDurumu — tenant-scoped değil | IlanRepository | Backlog |
| GD-002 | MatchingEngine global corpus ORM bypass | MatchingEngine | Expected Bypass (belgelendi) |

---

## 7. Phase Durumu

| Phase | Konu | Durum |
|---|---|---|
| 4A | Semantic sealing | ✅ Tamamlandı |
| 4B | CRM governance | ✅ Tamamlandı |
| 4C Week 1 | Telemetry Foundation | ✅ Tamamlandı |
| 4C Week 2 | Analytics + Alerting | ✅ Tamamlandı |
| 4C Week 3 | Dashboard | 🔄 Devam ediyor |
| Üretim | Gerçek API key'ler, AI_DRY_RUN=false | ❌ Bekliyor |
