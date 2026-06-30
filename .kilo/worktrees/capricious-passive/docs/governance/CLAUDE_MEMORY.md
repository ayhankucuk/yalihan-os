# Claude Sistem Hafızası — Yalıhan AI OS

> Bu dosya Claude'un proje hafızasıdır. Her oturumda oku.
> Son güncelleme: 2026-06-16 (Oturum 59 — MD Audit & Reorganizasyon)

---

## Proje Konumu
/Users/macbookpro/dev/yalihan2026

## Kritik Dosya Konumları (Oturum 59 — 2026-06-16 — KANONİK)

### SSOT Çekirdek (docs/ kökü — 12 dosya)
- `docs/SAB.md`                             → Teknik Anayasa (SSOT)
- `docs/PROGRESS-TRACKER.md`               → Phase/Sprint ilerlemesi
- `docs/governance/CLAUDE_MEMORY.md`       → Bu dosya (AI hafızası)
- `docs/known-debt.md`                     → Teknik borç (35 madde)
- `docs/ROADMAP.md`                        → Yol haritası v2.0
- `docs/BEKCI_CHANGELOG.md`               → Governance günlüğü
- `docs/DAP_CORE.md`                       → DAP Otopilot SSOT
- `docs/yalihan-project-brain-v3.md`       → AI referans belgesi
- `docs/authority-map.md`                  → Otorite haritası
- `docs/index.md`                          → Dokümantasyon merkezi
- `docs/MD_AUDIT_REPORT.md`               → MD denetim raporu (2026-06-16)

### Governance & Mimari
- `docs/governance/AI_FINDINGS.md`         → Bulgular
- `docs/governance/WENOX_SYSTEM_PROMPT.md` → WenOX sistem prompt
- `docs/technical/SYSTEM_MAP.md`           → Sistem haritası
- `docs/technical/NAMING-AUTHORITY.md`     → Context7 isimlendirme
- `docs/architecture/domains.md`           → Domain haritası
- `docs/architecture/flows.md`             → İş akışları
- `docs/adr/README.md`                     → 21 ADR dizini
- `docs/adr/2026-06-15-sprint2-architecture-decisions.md` → ADR-021

### Feature Belgeleri (docs/features/)
- `docs/features/LISTING_LIFECYCLE.md`     → yayin_durumu durum makinesi (YENİ)
- `docs/features/WIZARD_FLOW.md`           → Wizard kritik zinciri (YENİ)
- `docs/features/ILAN_NO_AUTO_GENERATION.md`
- `docs/features/SEARCH_AND_FILTER_SPEC.md`

### Registry
- `docs/registry/MUHENDISLIK_DERSLERI.md`  → 13 mühendislik dersi
- `docs/registry/FAZLAR_GECMIS_RAPORLAR.md`→ Geçmiş fazlar özeti
- `docs/registry/REFACTORING_LOG.md`       → Refactoring geçmişi

### Raporlar
- `docs/_reports/`                         → 18 rapor (testsprite dahil)
- `docs/_reports/testsprite/`              → 6 testsprite raporu (2026-06-16 taşındı)
- `docs/runbooks/`                         → 7 operasyonel runbook

### ⚠️ Arşiv Silindi (2026-06-16 — Oturum 59)
- `docs/archive/` (218 dosya) → KALICI SİLİNDİ
- `docs/_archived/` (1 dosya) → KALICI SİLİNDİ
- `.sab/proposals/` (3 dosya) → KALICI SİLİNDİ
- Geçmiş fazlar özeti: `docs/registry/FAZLAR_GECMIS_RAPORLAR.md`

## ⚠️ Arşiv Silindi (2026-06-16 — Oturum 59)
- docs/archive/ (218 dosya) → KALICI SİLİNDİ
- docs/_archived/ (1 dosya) → KALICI SİLİNDİ
- .sab/proposals/ (3 dosya) → KALICI SİLİNDİ
- Geçmiş fazlar özeti: docs/registry/FAZLAR_GECMIS_RAPORLAR.md

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

## Sunucu (Hetzner CX33 — AKTİF)
- **IP:** 157.180.116.63
- **IPv6:** 2a01:4f9:c010:9503::/64
- **Sunucu ID:** #137958025
- **Spec:** 4 vCPU / 8 GB RAM / 80 GB SSD local
- **Traffic:** 20 TB/ay — 0 kullanım
- **Fiyat:** €6.49/ay
- **Hostname:** yalihan
- N8N: https://n8n.yalihanemlak.com.tr
- Panel: https://panel.yalihanemlak.com.tr (Laravel deploy bekliyor)

## Eski Sunucu (Oracle Cloud — DEVRE DIŞI)
- IP: 168.138.101.124 (Ubuntu 22.04) — artık kullanılmıyor

## Minimum Sunucu Gereksinimleri (2026-06-16 — Oturum 60)

### Donanım: Minimum vs. Önerilen

| | Minimum | Önerilen (Production) |
|---|---|---|
| **vCPU** | 2 core | 4 core (CX33) |
| **RAM** | 4 GB | 8 GB ⚠️ |
| **Disk** | 40 GB SSD | 80 GB SSD |
| **OS** | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

> ⚠️ Laravel Horizon + Redis + PHP-FPM + MySQL aynı anda çalışacağı için 4 GB RAM gerçek yük altında yetersiz kalabilir. AI provider çağrıları queue'ya düştüğünde bellek baskısı artar — **8 GB şiddetle önerilir.**

### Zorunlu Yazılımlar

| Bileşen | Versiyon |
|---|---|
| PHP-FPM | 8.2 |
| Nginx | Latest Stable |
| MySQL | 8.0 |
| Redis | 7.x |
| Supervisor | Latest (Horizon için) |
| Composer | 2.x |
| Node.js | 18+ (Vite build için) |

**Zorunlu PHP Extension'ları:**
```
pdo_mysql  bcmath  fileinfo  xml  intl  gd  pcntl  zip  redis  mbstring  curl
```

### Açık Portlar

```
80 / 443  → Nginx (web)
6379      → Redis   (sadece localhost)
3306      → MySQL   (sadece localhost)
```

### Dış Servis Bağımlılıkları

| Servis | Zorunlu |
|---|---|
| Cloudflare Tunnel (SSL) | ✅ |
| DeepSeek API (`deepseek-chat` / `deepseek-reasoner`) | ✅ Birincil AI |
| OpenAI API | ✅ VoiceProcessor + FinanceProcessor |
| Telegram Bot `@yalihanx_bot` | ✅ Bildirimler |
| N8N `n8n.yalihanemlak.com.tr` | ✅ Workflow automation |
| Ollama `ollama.yalihanemlak.internal` | İsteğe bağlı (yerel AI) |

---

## Tamamlanan Görevler

### Sprint 1 (Oturum 1-8)
#8  DEEPSEEK_API_KEY mühürlendi, AI_DRY_RUN=false ✅
#9  MatchingEngine N+1 → Ilan::upsert() ✅
#10 N8N config tutarsızlığı giderildi ✅
#11 AiWalletService refresh() eklendi ✅
#15 Telegram @yalihanx_bot aktif ✅
B-008 ListingAIResponseValidator log+strip ✅
deepseek-v4-flash 5 dosyadan temizlendi ✅
SAB.sha256 güncellendi ✅
scripts/ → guards/ tools/ ops/ hiyerarşisi ✅
docs/SAB.md v1.0.0 + README senkronize ✅

### Sprint 2 (Oturum 9-10) — TAMAMEN KAPANDI ✅
#19 YalihanCortex God Object dekompoze — `5004346` ✅
#28 app/Domains/ → app/Domain/ birleştirme — `6909772` ✅
#58 DriftDetectionService kanonik seçim — `a8cf352` ✅
#60 ModuleServiceProvider isim çakışması — `6125ca3` ✅
#61 yalihan-bekci/ MCP dizin denetimi — `b68a7c9` ✅
B-006 Deprecated ghost model temizliği (P1-P5F) — `a947d80c` ✅

### Sprint 3 (Oturum 58-59 — 2026-06-15/16)
Kisi.php Context7 email→eposta düzeltmesi — `6923cf73` ✅
Ilan.php + Kisi.php pivot aktiflik_durumu fix ✅
IlanCrudService Split-Brain fix (handleVerticalDetails) ✅
MD Audit: 432→195 dosya, docs/ kökü 12 SSOT'a indirildi ✅
PROGRESS-TRACKER kırık referanslar temizlendi ✅
known-debt.md 35 maddeye güncellendi ✅

---

## Dizin Denetim İstatistikleri
- **Taranan Ana Klasörler**: 33 (Root-level)
- **Derinlemesine İncelenen/Düzenlenen**: 8 (scripts, tools, docs, .sab, .github, app, database, storage)
- **Kalan İncelenecek Kritik Alanlar**: 9 (app/Modules, app/Application, app/UseCases, tests, resources, hooks, yalihan-bekci, mcp, .github/workflows-logic)

## Bekleyen Deploy Görevleri (#20-27) 🔴 AKTİF BLOKER

#20 PHP 8.2 + Nginx + MySQL + Redis + Supervisor kur
#21 Laravel rsync ile sunucuya gönder
#22 composer install + migrate + cache
#23 Nginx config + Cloudflare Tunnel panel subdomain
#24 Supervisor + Horizon başlat
#25 Telegram webhook set + testler
#26 OpenAI API key (VoiceProcessor/Whisper zorunlu)
#27 N8N: Kalan 7 workflow
**Bloker:** SSH known_hosts engeli — sunucu erişimi doğrulanmalı
**Hedef sunucu:** Hetzner CX33 — 157.180.116.63

## Sprint 4 Görevleri (Bekleyen)

| # | Görev | Risk |
|---|-------|------|
| T-FAV-01 | ilan_favorileri.user_id vs pivot kisi_id FK uyumsuzluğu | 🟠 |
| T-UPS-V2-FULL | Tam JSONB göçü (ekstra_ozellikler) — 3 servis + 5 controller | 🔴 |
| #14 | 175 Context7 ihlali rename | 🟠 |
| #26 | bekci:pattern:sync komutu | 🟡 |
| #12 | Deprecated model kullanan 4 servis ✅ KAPALI (B-006) | — |
| #16 | FinanceProcessor OpenAI bağımlı | 🟡 |
| #17 | PortfolioProcessor whereBetween → Haversine | 🟡 |
| #18 | yayin_durumu 6 farklı string standardizasyonu | 🟡 |

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

---

## 🔧 Oturum 10 — Sprint 2 Kapanış + Sprint 3 Başlangıç (2026-06-15)

### Sprint 2 — TAMAMEN KAPANDI ✅

| # | Görev | Commit |
|---|-------|--------|
| #19 | YalihanCortex God Object dekompoze | `5004346` |
| #28 | app/Domains/ → app/Domain/ birleştirme | `6909772` |
| #58 | DriftDetectionService kanonik seçim | `a8cf352` |
| #60 | ModuleServiceProvider isim çakışması | `6125ca3` |
| #61 | yalihan-bekci/ MCP dizin denetimi | `b68a7c9` |
| B-006 | Deprecated ghost model temizliği (P1-P5F) | `a947d80c` |

### Sprint 3 — Başladı

#### ✅ Pivot Context7 Fix (2026-06-15)
- `app/Models/Ilan.php` + `app/Models/Kisi.php`
- `favorilenKisiler()`, `tumFavorileri()`, `favoriIlanlar()`, `tumFavoriIlanlar()` pivot metodları
- `withPivot('is_active')` + `wherePivot('is_active', 1)` → `aktiflik_durumu`
- DB'de `ilan_favorileri.aktiflik_durumu` zaten kanonikti — migration gerekmedi
- `kisiler.eposta`, `kisiler.son_etkilesim_tarihi` → zaten kanonik ✅
- Bekçi kaydı: `learning_context7_fix_2026-06-15T14-26-26.json`

#### ✅ #27 T-UPS-V2 Seçenek A — Split-Brain Fix (2026-06-15)
- `app/Services/Ilan/IlanCrudService.php` → `handleVerticalDetails()`
- **Önceki:** `$data['check_in_saati']` → double-write (iki bağımsız kaynak)
- **Sonraki:** `$ilan->check_in_time` → ilanlar SSOT, turizmDetail salt mirror
- `sezon_baslangic`/`sezon_bitis` ilanlar'da fiziksel karşılığı yok → `$data`'dan okunmaya devam
- `IlanDetailTables` trait'e dokunulmadı — 200 satır accessor/mutator bridge korundu
- Bekçi: ✅ TEMİZ (4/4 guard)
- Bekçi kaydı: `learning_architecture_decision_2026-06-15T16-00-25.json`

### Kritik Mimari Gerçekler (Bu Oturumda Doğrulandı)

**ilan_favorileri tablosu:**
- Kolonlar: `id`, `user_id`, `ilan_id`, `aktiflik_durumu` (boolean), `created_at`, `updated_at`
- NOT: `kisi_id` yok — `user_id` var. `Ilan.php` + `Kisi.php` pivot metodları `kisi_id` ile join yapıyor → runtime'da çalışıp çalışmadığı doğrulanmalı (#T-FAV-01)

**ilanlar tablosunda fiziksel turizm kolonları:**
- `minimum_stay`, `max_guests`, `check_in_time`, `check_out_time`, `cleaning_fee`, `gunluk_fiyat`, `havuz`, `havuz_var`
- `ekstra_ozellikler` JSONB kolonu **henüz mevcut değil**

**Split-Brain tüketicileri (salt mirror okuyucular):**
- `CortexROIEngine` → `$detail->havuz_var`, `$detail->min_konaklama`
- `IlanVerticalDomainService` → `->with(['turizmDetail'])` + `whereHas`
- `CortexPDFReportGenerator` → `$ilan->load(['turizmDetail'])`
- Tam JSONB göçü için 3 servis + 5+ controller güncellenmeli → Sprint 4

### Bekleyen Teknik Borç (Sprint 4+)

| # | Görev | Risk |
|---|-------|------|
| **T-FAV-01** | `ilan_favorileri.user_id` vs pivot `kisi_id` FK uyumsuzluğu — runtime doğrulanmalı | 🟠 |
| **T-UPS-V2-FULL** | Tam JSONB göçü: `ekstra_ozellikler` migration + 3 servis refaktör | 🔴 Sprint 4 |
| **#20-25** | Oracle Cloud deploy (SSH known_hosts engeli) | 🔴 |
| **#14** | 175 Context7 ihlali rename | 🟠 |
| **#26** | `bekci:pattern:sync` komutu | 🟡 |
