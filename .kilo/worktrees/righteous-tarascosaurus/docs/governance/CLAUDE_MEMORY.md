# Claude Sistem Hafızası — Yalıhan AI OS

> Bu dosya Claude'un proje hafızasıdır. Her oturumda oku.
> Son güncelleme: 2026-05-15 (Oturum 9 — BEKÇİ v2.1 AST AUDIT + MEMORY)

---

## Proje Konumu
/Users/macbookpro/dev/yalihan2026

## Kritik Dosya Konumları (Oturum 5 sonrası güncel)
- docs/SAB.md                          → Teknik Anayasa (SSOT) ✅
- docs/PROGRESS-TRACKER.md             → Phase ilerlemesi
- docs/governance/CLAUDE_MEMORY.md     → Bu dosya
- docs/governance/AI_FINDINGS.md       → Bulgular
- docs/technical/SYSTEM_MAP.md         → Sistem haritası
- docs/DAP_CORE.md                     → DAP Otopilot
- docs/known-debt.md                   → Teknik borç
- docs/registry/MUHENDISLIK_DERSLERI.md → Mühendislik dersleri ✅

## Scripts Dizini (Oturum 5'te düzenlendi)
- scripts/guards/   → CI/CD blocker'lar (quality-gate.sh, ci-guard-wizard.js)
- scripts/tools/    → Analiz araçları (dev-env-check.sh, security-baseline-update.cjs)
- scripts/ops/      → Deployment & operasyon betikleri
- scripts/archive/  → Miadı dolmuş JSON/SQL raporları

**Stubs:** migration'lar artık varsayılan olarak aktiflik_durumu üretiyor (state/status yasaklandı) ✅

**UYARI:** Antigravity derin temizlik yapar. Dosya aramak için:
find /Users/macbookpro/dev/yalihan2026/docs -name "CLAUDE_MEMORY.md"

---

## Mimari Özeti

**Stack:** PHP 8.2 / Laravel 11 | MySQL (prod) | SQLite (test) | Redis | Horizon
**Ölçek:** 321 Controller, 555 Servis, 179 Model, 47 Job, 136 Artisan Komutu
**IDE:** Google Antigravity

**AI Provider Hiyerarşisi:**
1. DeepSeek (birincil) — deepseek-chat / deepseek-reasoner
2. OpenAI (fallback)
3. Ollama (yerel, llama3.1)

**UYARI — Doğru Model Adları:**
- deepseek-chat     → DeepSeek V3 (genel kullanım) ✅
- deepseek-reasoner → R1 (analitik) ✅
- deepseek-v4-flash → YANLIŞ — 5 dosyadan temizlendi (Oturum 4)

---

## SAB Kuralları (Asla Esnetilmez)

1. Tenant Isolation — cross-tenant = en ağır ihlal
2. Repository Authority — DB yazma sadece Repository üzerinden
3. Async Context Restoration — Queue'da tenant bağlamı geri yüklenmeli
4. Fail-Open — governance telemetri hatası iş akışını kesmez
5. Performance Budget — telemetri overhead <10ms
6. Composite Score — getHealthScore() her zaman array, asla int

Expected Bypass: MatchingEngine global corpus ORM — intentional, belgelenmiş.

---

## Sunucu

Oracle Cloud: 168.138.101.124 (Ubuntu 22.04)
SSH: ssh ubuntu@168.138.101.124 (şifre ile)
N8N: https://n8n.yalihanemlak.com.tr (Docker çalışıyor) ✅
Panel: https://panel.yalihanemlak.com.tr (Laravel deploy bekliyor)
Cloudflare Tunnel: yalihan-tunnel (sistem servisi) ✅

---

## Tamamlanan Görevler

#8  DEEPSEEK_API_KEY mühürlendi, AI_DRY_RUN=false ✅
#9  MatchingEngine N+1 → Ilan::upsert() ✅
#10 N8N config tutarsızlığı giderildi ✅
#11 AiWalletService refresh() eklendi ✅
#15 Telegram @yalihanx_bot aktif ✅
B-008 ListingAIResponseValidator log+strip ✅

Oturum 4:
- deepseek-v4-flash 5 dosyadan temizlendi ✅
- docs/ 200+ eski dosya arşive taşındı ✅
- SAB.sha256 güncellendi ✅
- PROGRESS-TRACKER %100 senkronize ✅
- MÜHENDİSLİK_DERSLERİ.md oluşturuldu ✅

Oturum 5 & 6 (Konsolidasyon & Audit):
- scripts/ → guards/ tools/ ops/ archive/ hiyerarşisine taşındı ✅
- docs/SAB.md (Version 1.0.0) ve README.md senkronize edildi ✅
- YalihanCortex God Object decomposition (#19) — DEVAM EDİYOR:
  5800+ → 3139 satır (~2700 satır tahliye edildi)
  suggestCategory → CortexQualityService ✅
  getTopChurnRisks → CortexPredictionService ✅
  analyzeMarketTrends → CortexIntelligenceService ✅
  Commit: f157217
  Kalan: VoiceSearch + Notification integration katmanları

---

## Dizin Denetim İstatistikleri
- **Taranan Ana Klasörler**: 33 (Root-level)
- **Derinlemesine İncelenen/Düzenlenen**: 8 (scripts, tools, docs, .sab, .github, app, database, storage)
- **Kalan İncelenecek Kritik Alanlar**: 9 (app/Modules, app/Application, app/UseCases, tests, resources, hooks, yalihan-bekci, mcp, .github/workflows-logic)

## Bekleyen Deploy Görevleri (#20-27)

#20 PHP 8.2 + Nginx + MySQL + Redis + Supervisor kur
#21 Laravel rsync ile sunucuya gönder
#22 composer install + migrate + cache
#23 Nginx config + Cloudflare Tunnel panel subdomain
#24 Supervisor + Horizon başlat
#25 Telegram webhook set + testler
#26 OpenAI API key (VoiceProcessor/Whisper zorunlu)
#27 N8N: Kalan 7 workflow

## Post-Launch

#12 4 servis Deprecated model kullanıyor
#13 İki paralel AI orchestration
#16 FinanceProcessor OpenAI bağımlı
#17 PortfolioProcessor whereBetween → Haversine
#18 yayin_durumu 6 farklı string
#19 YalihanCortex God Object dekompozisyonu

## Domain Katmanı Bulgular (Oturum 5 — YENİ)

### app/Domains/ vs app/Domain/ — Çift Mimari (#28)
- app/Domains/PropertySchema/  → Eski DDD, 9 dosya, 1 controller kullanıyor
- app/Domain/PropertyHub/ + AI/ → Yeni V3 pipeline, 35 dosya, 13+ dosya kullanıyor
- Sorun: İki template resolution sistemi, entegrasyon yok
- V2TemplateResolutionEngineAdapter köprü olarak mevcut
- Çözüm: Domains/ → Domain/PropertyHub/ altına taşı

### app/Domain/AI/ Contracts (#29)
- CortexServiceInterface, AIProviderRouterInterface, PromptInterface
- 54 dosya bu contract'ları kullanıyor → audit gerekli

### app/Domain/PropertyHub/Observability/ (#30)
- 6 servis: GovernanceEventCorrelation, DriftTelemetry, GovernanceExport,
  HealthExplain, GovernanceIncident, GovernanceTimeline
- governance_events tablosuna yazıyor mu? Redis entegrasyonu var mı?

### Domain/PropertyHub/Resiliency/ — Çift CircuitBreaker (#31)
- Domain/PropertyHub/Resiliency/CircuitBreaker.php
- Contracts/Resilience/CircuitBreakerInterface (AI provider için)
- Hangisi production'da kullanılacak?

### Domain/PropertyHub/Chaos/ — Production Risk (#32)
- ChaosSimulationService + ChaosModeService
- Production guard var mı? .env flag kontrol et → CHAOS_MODE=false zorunlu

---

## N8N

Canli: https://n8n.yalihanemlak.com.tr ✅
İlk workflow: Yeni İlan Bildirimi
URL: https://n8n.yalihanemlak.com.tr/webhook/d0247957-388e-4b38-8729-f25fb91e63d2
Kalan 7 workflow: Yüksek Eşleşme, Talep Karşılandı, Kritik Güncelleme,
Görev Deadline, Fiyat Değişikliği, Yeni Talep, Churn Risk, Haftalık Rapor

---

## Telegram

Bot: @yalihanx_bot ✅
Admin Chat ID: 515406829 ✅
Team Channel ID: 515406829 (geçici)
Webhook: https://panel.yalihanemlak.com.tr/api/telegram/webhook
Deploy sonrası: php artisan telegram:set-webhook

---

## Phase Durumu

- Phase 4A ✅  Phase 4B ✅  Phase 4C ✅
- Deploy: BEKLEYEN (#20-25)
- Launch: 16 Mayıs 2026

## Oturum 8 Antigravity Modül Denetimi Bulguları (2026-05-15)

### GovernanceCore — ÇÖZÜLDÜ
- GovernanceCoreServiceProvider.php oluşturuldu (app/Modules/GovernanceCore/) ✅
- GovernanceEngine.php oluşturuldu — GovernanceEngineInterface tam implementasyon ✅
- config/app.php'ye eklendi ✅
- DriftDetectionService çift implementasyon: Core\ (ActiveConfigRegistry) vs Services\ (YayinTipiSablon) — #58 pending
- GovernanceEngineInterface.detectDrift() → Core\DriftDetectionService kullanıyor (canonik seçildi)

### Modül Kayıt Eksiklikleri — ÇÖZÜLDÜ
- TalepServiceProvider + TalepAnalizServiceProvider → ModuleServiceProvider'a eklendi ✅
- Market modülü: ServiceProvider yok (#59 izleniyor)
- App\Providers\ModuleServiceProvider vs App\Modules\ModuleServiceProvider → isimlendirme kaos (#60)

### Açık Bulgular
- #58: DriftDetectionService çift — Sprint 1'de kanonik seç
- #60: İki ModuleServiceProvider isim çakışması — Providers\ olanı rename et

## Deploy Öncesi Fix Özeti (Oturum 7 — 2026-05-15)

| Fix | Dosya | Görev |
|-----|-------|-------|
| APP_DEBUG=false | .env | #36 ✅ |
| N8N URL fallback null | config/services.php | #37 ✅ |
| FieldMCP auth:sanctum+tenant | routes/api/v1/field-mcp.php | #48 ✅ |
| SetTenantContext middleware | app/Http/Middleware/SetTenantContext.php | #49 ✅ |
| Kernel tenant.context alias | app/Http/Kernel.php | #49 ✅ |
| Admin API session auth intentional | RouteServiceProvider.php yorumu | #50 ✅ |
| CI PHP 8.2 → production match | .github/workflows/core-ci.yml | #51 ✅ |
| SetTenantContextTest | tests/Feature/Middleware/ | #49 ✅ |

**Kalan Deploy Bloker**: YOK ✅
**Kalan Deploy Görevleri**: #20-25 (Sunucu kurulum)

## Git Commit Durumu (Oturum 8 sonu)
- `bc48b72` — GovernanceCore DI + yetim modül kayıtları ✅ (pushed)
- Staged (yerel commit bekliyor):
  - app/Http/Middleware/SetTenantContext.php
  - app/Http/Kernel.php
  - config/services.php
  - routes/api/v1/field-mcp.php
  - .github/workflows/core-ci.yml
  - tests/Feature/Middleware/SetTenantContextTest.php
  - docs/governance/CLAUDE_MEMORY.md
  - docs/REFACTORING_LOG.md
- **bootstrap/cache/services.php → commit'e dahil etme** (otomatik üretilir)

---

## IDE ile Çalışma

IDE: Google Antigravity
1. Claude analiz → ide-output skill → Antigravity uygular
2. Antigravity çıktısı Claude'a yapıştırılır
3. Claude sonraki görevi hazırlar

## Dizin İnceleme Sırası — Gerekçeli Karar

### 🔴 DEPLOY ÖNCESİ — Sıra ve Neden
| # | Dizin | Durum | Neden Bu Sıra |
|---|-------|-------|---------------|
| 1 | database/ | ✅ TAMAM | migrate deploy'da çalışır — FK/tablo hatası sunucuyu patlatır |
| 2 | routes/ | ✅ TAMAM | Telegram/N8N webhook'ları hazır mı? Auth açıkları var mı? |
| 3 | .github/ | ✅ TAMAM | CI/CD gate'leri çalışmıyorsa governance sadece kağıt üstünde kalır |
| 4 | app/Modules/ | ✅ TAMAM | GovernanceCore ServiceProvider yok (#53 açıldı), DriftDetection duplicate, CI artisanları doğrulandı |
| 5 | app/Http/Middleware/ | ✅ TAMAM | 46 middleware — SAB Kural #1 ihlali doğrulandı, yeni görevler #53-55 |
| 6 | tests/ | ✅ TAMAM | 369 dosya, 92 skip (%25), tenant testleri CI'da aktif, smoke/security testleri skip (#56) |

### 🟠 SPRINT 1 — Launch Sonrası
| # | Dizin | Neden |
|---|-------|-------|
| 7 | app/Application/ | ✅ TAMAM | 27 dosya, temiz DDD katmanı. Actions user alıyor ama tenant enforce etmiyor (HTTP katmanı halletti). ListingAIResultDTO #40 doğrulandı |
| 8 | app/UseCases/ | N8N DTOs, ProcessAIIlanTaslagiUseCase — N8N workflow'ları için kritik |
| 9 | resources/ | 13 eksik lang key blade'lerde, view'lar kırık |
| 10 | hooks/ + .husky | Pre-commit SAB/Context7 gate'leri çalışıyor mu? |

### 🟣 SPRINT 2
| # | Dizin | Neden |
|---|-------|-------|
| 11 | yalihan-bekci/ | Bekçi sistemi production'da aktif mi, ne izliyor? |
| 12 | mcp/ | IDE entegrasyonu, Antigravity araçları |

### ⬇️ ATLA
reports/ · patches/ · playwright/ · testsprite_tests/ · vendor/ · node_modules/ · .vercel · launchd · .idx

### ✅ Zaten İncelendi
database/ · routes/ · app/Services/AI/ · app/Domain(s)/ · config/ · lang/
app/Events/ · app/DTO(s)/ · stubs/ · scripts/ · docs/ · .env

---

### database/ Audit Özeti
- 59 migration, 2 yıllık geçmiş ✅
- transactions tablosu MEVCUT (2026_04_20) ✅
- governance_events + governance_alerts MEVCUT (2026_05_13) ✅
- FK sırası DOĞRU, circular dependency YOK ✅
- telegram_id: users'da telegram_chat_id var → TelegramBrain ne bekliyor? (#47)
- governance_alerts.acknowledged_by → users.id FK yok (#46)
- 5 yeni tablo için factory yok (#46) · 10 orphan seeder (#46)
- Run 60-73: schema drift stabilize edildi ✅

### routes/ Audit Özeti (~1200 route, 46 dosya)
- Telegram webhook ✅ /api/v1/integrations/telegram/webhook (telegram.secret)
- N8N webhook ✅ /api/v1/webhook/n8n/* (7 endpoint, n8n.secret + throttle)
- Admin panel ✅ auth + verified + role:admin + sab.write.guard
- FieldMCP ❌ auth YOK — DEPLOY BLOCKER (#48)
- Tenant isolation middleware ❌ hiçbir route'da yok (#49) SAB Kural #1
- Admin API 'auth' yerine 'auth:sanctum' olmalı (#50)

### tests/ Audit Özeti (369 dosya)
- Toplam: 369 test dosyası, 19 testsuite dizini
- **CI'da aktif**: Unit, Feature, Governance, GovernanceTelemetry suites ✅
- **Skip (92 dosya, %25)**: `@group skip-until-migration-complete` — AdminLoginTest, AuthzTest, AICostGuardTest, MultiTenantIsolationTest dahil (#56)
- Tenant isolation testleri: CRMTenantIsolationTest, CRMCacheTenantScopingTest, TenantContextResolverTest, CRMQueueTenantSafetyTest → CI'DA AKTİF ✅
- GovernanceMetrics Phase 4C testleri: fail-open, performance budget, composite score → CI'DA AKTİF ✅
- Architecture/ReleaseGuardTest: Controllers DB direct erişim + legacy accessor kontrol → AKTİF ✅
- YENİ: SetTenantContextTest → tests/Feature/Middleware/SetTenantContextTest.php yazıldı ✅
- phpunit.xml: DB_CONNECTION=sqlite (testing), AUTH_MODEL=App\Models\V2\User ✅
- String obfuscation (`getStaxxCode`) testlerde de görülüyor — aynı anti-pattern

### app/Http/Middleware/ Audit Özeti (46 dosya)
- TenantContextService: Singleton bound (AppServiceProvider.php:36), AMA HTTP katmanında setTenant() çağıran middleware YOK (#53 KRİTİK)
- FieldMCP: `Route::prefix('v1')->middleware([ThrottleApiRequests::class])` altında — sadece throttle, auth YOK (#48 deploy blocker)
- SabComplianceMiddleware: api grup'a kayıtlı, pass-through stub — enforce etmiyor (#55)
- GlobalWriteGuard: sadece loglama, enforcement yok — intentional mı? belgelenmeli (#55)
- CheckRole.php: Kernel alias'ta yok, hiçbir route kullanmıyor — legacy, silinmeli (#55)
- RoleMiddleware: Spatie tabanlı, 'role' alias — canonical ✅
- EnsureAgentScope / OpenClaw stack: tam 7 katman güvenlik ✅
- VerifyTelegramWebhookSecret + CheckN8nSecret: webhook güvenliği tam ✅
- ProductionLockMiddleware: PRODUCTION_LOCK=OPEN kontrol ediyor ✅
- Models/SaaS/Tenant.php: fillable'da 'status' (yasak kelime) → #54
- TenantContextResolver: auth()->user()->tenant_id'den resolve ediyor — middleware çağırırsa çalışır

### .github/ Audit Özeti
- Tek workflow: core-ci.yml — push/PR to main+develop trigger ✅
- Governance CI'da GERÇEKTEN çalışıyor: quality:gate + sab:integrity-scan + domain:seal-check ✅
- Phase 4B.3 tenant isolation testleri explicit çağrılıyor ✅
- Service layer + Repository pattern grep check (final-verdict job) ✅
- PHP 8.4 CI ≠ PHP 8.2 production (#51) — deploy öncesi karar verilmeli
- CD pipeline YOK (#52) — deploy sadece manuel scripts/ops/deploy-production.sh
- .github/agents/: fix-generator, master-orchestrator, debug-executor, master-admin-copilot mevcut

## Config & Lang Bulgular (Oturum 5 — YENİ)

### TAMAMLANDI
#36 APP_DEBUG=false + APP_ENV=production yapıldı ✅ (deploy öncesi kritikti)

### Deploy Öncesi Zorunlu
#37 config/services.php satır 200-209 → N8N webhook fallback URL'leri null yapılmalı
     (production domain hardcoded — env var yoksa dev ortamı prod N8N'i tetikler)

### Sprint 1 (Launch sonrası)
#33 lang/en/ dosyaları TR ile aynı — gerçek İngilizce değerler girilmeli
#34 ar/de/fr/ru dil dosyaları boş ({}) — localization.php'den çıkar veya doldur
#35 13 eksik lang key (UI kırık), 48 ölü key, 4 hardcoded TR string blade'de
#38 AI_HARD_CAP 3 farklı isim (ENABLED/ACTIVE/hard_cap_aktif) — standardize et
#39 40+ env değişkeni .env.example'da yok (N8N, PROPERTYHUB, CONTEXT7, GOVERNANCE aileleri)

### config/ Yapısı
- 91 config dosyası
- N8N SSOT → n8n.php (services.php'deki N8N bloğu kaldırılacak)
- 3 ayrı AI budget sistemi: ai-budgets.php · ai-cost-guard.php · services.php
- Chaos servisleri: PROPERTYHUB_CHAOS_ENABLED varsayılan false ✅ (production-safe)
- Model isimleri temiz: deepseek-v4-flash kalmadı ✅

Görev bitince bu dosyayı güncelle.

---

## 🗂️ Admin Sidebar Menü Haritası (config/menus.php — Oturum 10)

> Kaynak: `config/menus.php` | Güncelleme: 2026-06-11

### 5 Katman Mimarisi

**L1: BUSINESS (display_order 1-6)**
- Dashboard → `admin.dashboard.index`
- İlanlar & Portföy grubu:
  - İlanlarım → `admin.ilanlarim.index`
  - Tüm İlanlar → `admin.ilanlar.index`
  - Yeni İlan (AI) → `admin.ilanlar.create`
  - Danışmanlar → `admin.danisman.index`
- CRM & Müşteri grubu:
  - CRM Dashboard → `admin.crm.dashboard`
  - Kişiler → `admin.kisiler.index`
  - Kişilerim → `admin.kisilerim.index`
  - Talepler → `admin.talepler.index`
  - Eşleştirmeler (AI) → `admin.eslesmeler.index`
- Takım & Operasyon grubu:
  - Takımlar → `admin.takim.takimlar.index`
  - Görevler → `admin.takim.gorevler.index`
  - Projeler → `admin.takim.projeler.index`
  - Kanban Board → `admin.takim.board`
- Finans & Satış grubu:
  - Finansal İşlemler → `admin.finans.islemler.index`
  - Satışlar → `admin.satislar.create`
- Bildirimler → `admin.notifications.index`

**L2: PROPERTY ENGINE (display_order 7)**
- Dashboard → `admin.property-hub.index`
- Özellik Havuzu → `admin.property-hub.features.index`
- Şablonlar → `admin.property-hub.templates.index`
- Özellik Paketleri → `admin.property-hub.packs.index`
- Özellik Kategorileri → `admin.ozellikler.kategoriler.index`
- Kategori Matrisi → `admin.property_types.index`
- Bağımlılık Kuralları → `admin.property-hub.dependency-rules.index`
- TKGM Parsel → `admin.tkgm-parsel.index`

**L3: INTELLIGENCE (display_order 8-9)**
Cortex (AI grubu):
- AI Dashboard → `admin.ai.dashboard`
- Cortex Analytics → `admin.cortex`
- Cortex Monitoring → `admin.ai-monitor.index`
- AI Alan Önerileri → `admin.property-hub.field-suggestions.index`
- Kullanım & Maliyet → `admin.ai.statistics`
- İstatistikler → `admin.analitik.istatistikler.index`
- Tüm Raporlar → `admin.reports.index`
- Portfolio Doctor (AI) → `advisor.portfolio-doctor`

Governance (SAB grubu):
- Telemetri İzleme (LIVE) → `admin.governance.telemetry`
- AI Kontrol Merkezi → `admin.governance.intelligence-center`
- Karar Kuyruğu → `admin.governance.review-queue`
- Governance Dashboard → `admin.governance.dashboard`
- Özellik Sağlık Matrisi → `admin.governance.feature-health`
- AI Governance → `admin.analytics.ai-governance`
- Denetim Kayıtları → `admin.ups.audit-log`
- Otonom Kontrol → `admin.governance.autonomy-panel`
- Aksiyon Döngüsü → `admin.governance.action-dashboard`
- Yalıhan Bekçi → `admin.yalihan-bekci.index`

**L4: AUTOMATION HUB (display_order 10)**
- Telegram Bot → `admin.telegram-bot.index`
- n8n Workflows → `admin.integrations.n8n-workflows`
- Entegrasyonlar → `admin.integrations.index`
- Sesli Arama → `admin.voice-search.settings`

**L5: SYSTEM (display_order 11)**
- Sistem Sağlığı → `admin.ups.health`
- Telescope → `/telescope`
- Horizon → `/horizon`
- Kullanıcılar → `admin.kullanicilar.index`
- Genel Ayarlar → `admin.ayarlar.index`
- AI Ayarları → `admin.ai-settings.index`
- Adres Yönetimi → `/admin/address-management`

### Sistem Ölçeği (yalihan-project-brain-v3.md — Mayıs 2026)
- 16 Domain | 327 Controller | 567 Servis (145 AI) | 189 Model | 48 Route dosyası | 2.066 Test

---

## 🛡️ Yalıhan Bekçi v2.1 — Bilişsel Mimari Koruma Sistemi (Oturum 9)

### Genel Tanım
Bekçi, projenin **AST (Abstract Syntax Tree)** tabanlı mimari denetim katmanıdır.
v2.1 itibarıyla regex/grep'ten PHP yapısını anlayan ağaç analizine geçildi.
`nikic/php-parser` kütüphanesi kullanılır.

### Çalıştırma
```bash
php artisan bekci:audit --all          # CI/CD — tüm kurallar
php artisan bekci:audit --secret-scan  # Sadece sır/anti-pattern taraması
php artisan bekci:audit --silent-catch # Sadece sessiz catch denetimi
php artisan bekci:audit --naming       # İsimlendirme ihlalleri
php artisan bekci:audit --technical-debt # TODO/FIXME takibi
```

### Temel Dosyalar
- Komut: `app/Console/Commands/Governance/BekciAuditCommand.php`
- Tarama motoru: `app/Services/Governance/Ast/AstScannerService.php`
- Kural kaydı: `app/Services/Governance/Ast/GovernanceAstRuleRegistry.php`
- Kural arayüzü: `app/Services/Governance/Ast/GovernanceAstRuleInterface.php`
- Kural dizini: `app/Services/Governance/Ast/Rules/`
- Konfigürasyon: `config/sab_ast.php`

### Aktif AST Kuralları

| Kural ID | Dosya | Severity | Ne Yakalar |
|---|---|---|---|
| `SilentCatchAST` | `SilentCatchAstRule.php` | MEDIUM | Boş catch, throw/Log/report içermeyen catch |
| `EnvUsageAST` | `EnvUsageAstRule.php` | HIGH | `app/` içinde doğrudan `env()` çağrısı |
| `ForbiddenFunctionAST` | `ForbiddenFunctionAstRule.php` | HIGH | `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen` |
| `ForbiddenFieldAST` | `ForbiddenFieldAstRule.php` | MEDIUM | `status`, `type`, `is_active`, `order` gibi yasaklı alan adları |
| `NamingAuthorityAST` | `NamingAuthorityAstRule.php` | WARNING | Domain/Framework isimlendirme karışıklığı, camelCase DB alanları |
| `LanguageHardcodeAST` | `LanguageHardcodedArrayAstRule.php` | HIGH | Hardcoded dil kodu dizileri (`['en','ru','ar',...]`) |
| `AP-COGNITIVE-001` | `CognitiveGuardianRule.php` | BLOCKING | Boş catch + `env()` kullanımı (çift güvence) |

### Yaşayan Bellek (Living Memory)
- `docs/governance/ANTI_PATTERNS.json` → Bilinen anti-pattern imzaları (regex tabanlı)
- `docs/governance/LEARNED_PATTERNS.json` → Çözülen hatalardan öğrenilen regresyon engelleyiciler
- `app/Console/Commands/Governance/BekciPatternLearnCommand.php` → Yeni pattern öğretme

### Bypass Mekanizmaları
- `@sab-ignore-catch` → Catch bloğu doc comment'inde → SilentCatch kuralını atla
- `// context7-ignore` → Satır bazında → ForbiddenField kuralını atla
- `config/sab_ast.php` → `enabled: false` veya `report_only: true` ile kural bazında devre dışı

### SAB Bağlantısı
- **Madde 4:** Silent catch yasaktır → `SilentCatchAST`
- **Madde 8:** Context7 ihlal toleransı = 0 → `ForbiddenFieldAST`
- **Madde 14:** Bilişsel Muhafız bypass yasaktır → `AP-COGNITIVE-001`
- **Madde 15:** Learned Patterns regresyonu bloklar → Living Memory taraması

### Diğer Governance Komutları (aynı dizinde)
`BekciPatternLearnCommand`, `GovDriftScan`, `GovernanceAnalyzeCommand`,
`GovernanceHealthCheckCommand`, `TraceAnalysisCommand`, `GovViewDiffCommand`

### Sprint 2 Notu
`yalihan-bekci/` dizini (MCP server + knowledge base) henüz incelenmedi → Sprint 2'de ele alınacak (#61).
