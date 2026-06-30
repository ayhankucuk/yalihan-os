# NOTEBOOKLM_STRUCTURE.md
## YALIHAN PLATFORM v2.0 — AI Bilgi Çıkarım Mimarisi

> **Tarih:** 2026-06-28
> **Sürüm:** 1.0.0
> **Yazar:** Chief Knowledge Officer (CKO)
> **Mevcut Durum:** Kısmi kurulum var — otomatizasyon eksik

---

## 1. MEVCUT DURUM

### 1.1 Bugün Ne Var?

```
NotebookLM Notebook: "Yalıhan AI OS - Project Knowledge"
├── ID: yal-han-ai-os-project-knowledg
├── URL: https://notebooklm.google.com/notebook/317f976e-6e6a-47e9-97c5-c4ca4f8ecae5
├── Kaynak Sayısı: 28
├── Model: Gemini 2.5
└── Durum: Manuel sync (PROBLEM!)
```

**Manuel sync problemi:**
1. `scripts/ops/notebooklm-sync.sh` dosyaları `storage/notebooklm-sync/` kopyalar
2. Ama NotebookLM'e yükleme hâlâ **manuel**
3. Rate limit: 50 query/gün (free tier)
4. Source güncelleme = Manuel = Unutulur = Güncelliği kaybolur

### 1.2 Eksik Olanlar

| Eksik | Öncelik | Etki |
|-------|---------|------|
| Otomatik source upload | P0 | Bilgi güncelliği kayboluyor |
| Drive → NotebookLM pipeline | P0 | Güncel Drive dosyaları yok |
| NotebookLM → Agent knowledge loop | P1 | Agent'lar NotebookLM'i kullanamıyor |
| Multi-notebook stratejisi | P1 | Tek notebook = karışıklık |
| Audio overview generation | P2 | Zengin içerik üretilemiyor |

---

## 2. HEDEF MİMARİ — NOTBOOKLM EKOSİSTEMİ

```
┌─────────────────────────────────────────────────────────────────────┐
│                    NOTEBOOKLM EKOSİSTEMİ                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  KAYNAK ÜRETİMİ (Otomatik)                                    │ │
│  │                                                                │ │
│  │  GitHub/Gitea     Git Hook      CI/CD Pipeline                 │ │
│  │       │               │              │                         │ │
│  │       ▼               ▼              ▼                         │ │
│  │  ┌─────────┐   ┌──────────┐  ┌──────────────┐              │ │
│  │  │  CODE   │   │ MEMORY   │  │   DOCS       │              │ │
│  │  │ (repo) │   │ (memory/)|  │ (docs/adr/)  │              │ │
│  │  └────┬────┘   └─────┬────┘  └──────┬───────┘              │ │
│  │       │               │               │                        │ │
│  │       └───────────────┼───────────────┘                        │ │
│  │                       ▼                                        │ │
│  │              NotebookLM Sync Service                           │ │
│  │           (yalihan-notebooklm-sync.service)                   │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                     │
│                               ▼                                     │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  NOTEBOOK KATALOĞU (5 Notebook)                               │ │
│  │                                                                │ │
│  │  ┌─────────────────┐  ┌─────────────────┐                   │ │
│  │  │ NB-1: YALIHAN  │  │ NB-2: TEKNİK   │                   │ │
│  │  │ GOVERNANCE      │  │ MİMARİ          │                   │ │
│  │  │ • SAB.md        │  │ • docs/adr/*   │                   │ │
│  │  │ • authority.json│  │ • SYSTEM_ARCH   │                   │ │
│  │  │ • BEKCI rules   │  │ • DOMAIN docs   │                   │ │
│  │  └─────────────────┘  └─────────────────┘                   │ │
│  │                                                                │ │
│  │  ┌─────────────────┐  ┌─────────────────┐                   │ │
│  │  │ NB-3: PRODUCT   │  │ NB-4: DOMAIN    │                   │ │
│  │  │ KNOWLEDGE       │  │ EXPERTISE       │                   │ │
│  │  │ • Sprint docs   │  │ • CRM           │                   │ │
│  │  │ • Feature specs │  │ • Finance       │                   │ │
│  │  │ • API contract │  │ • AI Engine    │                   │ │
│  │  └─────────────────┘  └─────────────────┘                   │ │
│  │                                                                │ │
│  │  ┌──────────────────────────────────────────┐               │ │
│  │  │ NB-5: ONBOARDING & TRAINING              │               │ │
│  │  │ • HOW_IT_WORKS.md                         │               │ │
│  │  │ • LEARNED_PATTERNS.md                    │               │ │
│  │  │ • CONTRIBUTING.md                        │               │ │
│  │  │ • CLAUDE.md (short)                       │               │ │
│  │  └──────────────────────────────────────────┘               │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                     │
│                               ▼                                     │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  TÜKETİM KATMANI                                                │ │
│  │                                                                │ │
│  │  AI Agent'lar      Developer'lar      Product Owner             │ │
│  │  (MCP tool)        (Manuel)          (Dashboard)              │ │
│  │       │               │                  │                      │ │
│  │       ▼               ▼                  ▼                      │ │
│  │  ask_question   Audio Overview     Summary Cards               │ │
│  │  get_notebook   Gemini Query       Sprint Digest               │ │
│  └───────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. NOTBOOK KATALOĞU

### NB-1: YALIHAN GOVERNANCE

**Odak:** SAB, Bekçi, Governance kuralları

| Alan | Değer |
|------|-------|
| Kullanım | AI Agent'lar için SAB kuralları sorgulama |
| Model | Gemini 2.5 |
| Rate limit | 250 query/gün (Google AI Pro) |
| Source güncelleme | Her commit sonrası otomatik |

**Kaynaklar:**

| # | Kaynak | Tip | Güncelleme |
|---|--------|-----|------------|
| 1 | `docs/SAB.md` | markdown | Her commit |
| 2 | `.sab/authority.json` | json | Her commit |
| 3 | `docs/SAB.md.sha256` | checksum | Her commit |
| 4 | `docs/BEKCI_CHANGELOG.md` | markdown | Her oturum |
| 5 | `memory/LEARNED_PATTERNS.md` | markdown | Her düzeltme |
| 6 | `memory/DECISIONS.md` | markdown | Her karar |
| 7 | `docs/adr/` (tümü) | markdown | Her ADR |
| 8 | `.sab/ONBOARDING.md` | markdown | Her değişiklik |

**Kullanım Senaryoları:**
- "Bu kod SAB'a uygun mu?"
- "Silent catch neden yasak?"
- "Context7 ihlali hangi dosyada?"
- "Ownership scope nasıl enforce ediliyordu?"

---

### NB-2: TEKNİK MİMARİ

**Odak:** Sistem mimarisi, domain modelleri, CQRS

| Alan | Değer |
|------|-------|
| Kullanım | Mimari kararlar, domain bilgisi |
| Model | Gemini 2.5 |
| Rate limit | 250 query/gün |

**Kaynaklar:**

| # | Kaynak | Tip | Güncelleme |
|---|--------|-----|------------|
| 1 | `docs/SYSTEM_ARCHITECTURE.md` | markdown | Haftalık |
| 2 | `docs/YALIHAN_OS_DOMAIN_MODEL.md` | markdown | Aylık |
| 3 | `docs/architecture-lite.md` | markdown | Aylık |
| 4 | `docs/technical/IDE_AGENT_SYSTEM.md` | markdown | Değişiklikte |
| 5 | `memory/HOW_IT_WORKS.md` | markdown | Her oturum |
| 6 | `docs/technical/NAMING-AUTHORITY.md` | markdown | Değişiklikte |
| 7 | `mcp-servers/notebooklm-mcp/docs/` | markdown | Ayda bir |
| 8 | `docs/technical/NOTEBOOKLM_INTEGRATION.md` | markdown | Değişiklikte |

**Kullanım Senaryoları:**
- "Domain event'ler nasıl çalışıyor?"
- "Projection tablosu neden sadece okuma içindir?"
- "AI Workforce mimarisi nedir?"
- "8 domain boundary neyi kapsıyor?"

---

### NB-3: PRODUCT KNOWLEDGE

**Odak:** Feature'lar, sprint'ler, KPI'lar, kullanıcı hikayeleri

| Alan | Değer |
|------|-------|
| Kullanım | Product Owner, sprint planlama |
| Model | Gemini 2.5 |
| Rate limit | 250 query/gün |

**Kaynaklar:**

| # | Kaynak | Tip | Güncelleme |
|---|--------|-----|------------|
| 1 | `docs/plans/owner-portal-roadmap.md` | markdown | Sprint sonu |
| 2 | `docs/ROADMAP.md` | markdown | Sprint sonu |
| 3 | `memory/CHANGELOG_AGENT.md` | markdown | Her oturum |
| 4 | `memory/SESSION_NOTES.md` | markdown | Her oturum |
| 5 | `memory/CHIEF_AI_VISION.md` | markdown | Değişiklikte |
| 6 | `docs/plans/market-intelligence-engine-v1-spec.md` | markdown | Değişiklikte |
| 7 | `docs/features/LISTING_LIFECYCLE.md` | markdown | Aylık |
| 8 | `docs/features/SEARCH_AND_FILTER_SPEC.md` | markdown | Aylık |

**Kullanım Senaryoları:**
- "Sprint 3.4'te hangi feature'lar tamamlandı?"
- "AI Listing Assistant ne yapıyor?"
- "Capability dili nedir?"
- "Owner portal roadmap'i nedir?"

---

### NB-4: DOMAIN EXPERTISE

**Odak:** Derin domain bilgisi — CRM, Finance, AI, Location

| Alan | Değer |
|------|-------|
| Kullanım | Developer'lar, domain uzmanları |
| Model | Gemini 2.5 |
| Rate limit | 250 query/gün |

**Kaynaklar (Domain bazlı):**

| Domain | Kaynaklar |
|--------|-----------|
| Property | `app/Models/Ilan.php` (docblock), `docs/features/` |
| CRM | `app/Models/Kisi.php` (docblock), CRM modelleri |
| Finance | `docs/ai-engines/market_valuation.md` |
| AI | `prompts/cortex.md`, `prompts/sab.md` |
| Location | `docs/SYSTEM_MAP.md`, location domain dokümanları |
| Governance | `app/Services/AI/YalihanCortex.php` (docblock) |

**Kullanım Senaryoları:**
- "KisiRepository ownership scope nasıl implement ediliyor?"
- "AI Buyer Match Engine nasıl çalışıyor?"
- "Finansal tenant isolation nasıl sağlanıyor?"
- "YalihanCortex pipeline'ı hangi adımlardan oluşuyor?"

---

### NB-5: ONBOARDING & TRAINING

**Odak:** Yeni developer onboarding, eğitim materyalleri

| Alan | Değer |
|------|-------|
| Kullanım | Yeni geliştirici, AI agent onboarding |
| Model | Gemini 2.5 |
| Rate limit | 250 query/gün |

**Kaynaklar:**

| # | Kaynak | Tip | Güncelleme |
|---|--------|-----|------------|
| 1 | `CONTRIBUTING.md` | markdown | Değişiklikte |
| 2 | `memory/WHERE_IS_WHAT.md` | markdown | Her oturum |
| 3 | `memory/LEARNED_PATTERNS.md` | markdown | Her düzeltme |
| 4 | `memory/PROJECT_BRAIN.md` | markdown | Her oturum |
| 5 | `docs/technical/IDE_ONBOARDING_GUIDE.md` | markdown | Ayda bir |
| 6 | `docs/governance/CLAUDE_MEMORY.md` | markdown | Her değişiklik |
| 7 | `ROADMAP.md` | markdown | Sprint sonu |
| 8 | `README.md` | markdown | Değişiklikte |

**Kullanım Senaryoları:**
- "Yeni developer burada nasıl başlar?"
- "Kod yazmadan önce hangi kontrolleri yapmalıyım?"
- "Thin Controller kuralı tam olarak ne diyor?"
- "Bu projeye kimler katkı yapıyor?"

---

## 4. KAYNAK GÜNCELLEME STRATEJİSİ

### 4.1 Otomatik Sync Pipeline

```
Git Hook (post-commit)
        │
        ▼
notebooklm-sync.service
(GitHub Actions veya launchd)
        │
        ▼
┌─────────────────────────────────────────┐
│  Sync Pipeline (scripts/ops/sync-*)     │
│                                         │
│  1. Git diff → değişen dosyalar        │
│  2. Dosyaları kategorize et             │
│     • Governance → NB-1                  │
│     • Architecture → NB-2               │
│     • Product → NB-3                   │
│     • Domain → NB-4                    │
│     • Onboarding → NB-5                 │
│  3. Her notebook için source update      │
│  4. Rate limit kontrolü                │
│  5. Log kaydı                          │
└─────────────────────────────────────────┘
        │
        ▼
NotebookLM API
 veya
Manuel Upload (fallback)
```

### 4.2 Source Güncelleme Kuralları

| Değişiklik Tipi | Hedef Notebook | Tetikleyici |
|-----------------|---------------|-------------|
| `docs/SAB.md` değişti | NB-1 | Her commit |
| `docs/adr/` yeni dosya | NB-1 | Her commit |
| `memory/LEARNED_PATTERNS.md` | NB-1 | Her oturum |
| `docs/SYSTEM_ARCHITECTURE.md` | NB-2 | Her commit |
| `memory/CHANGELOG_AGENT.md` | NB-3 | Her oturum |
| `memory/SESSION_NOTES.md` | NB-3 | Her oturum |
| `memory/PROJECT_BRAIN.md` | NB-5 | Her oturum |
| `CONTRIBUTING.md` | NB-5 | Her commit |
| Domain model değişikliği | NB-4 | Her commit |

### 4.3 Rate Limit Yönetimi

```
Free Tier: 50 query/gün
Google AI Pro: 250 query/gün

Strateji:
• Her notebook ayrı rate limit hesabı (5 × 250 = 1250 query/gün)
• Batch query'ler (maks 10 kaynak/batch)
• Query caching (aynı soru = cache)
• Off-peak scheduling (gece 02:00-05:00)
```

---

## 5. MCP ENTEGRASYON MATRİSİ

### 5.1 Mevcut MCP Tools (Kullanılıyor)

| Tool | Notebook | Kullanım |
|------|----------|----------|
| `ask_question` | Tümü | Gemini'ye soru sor |
| `list_notebooks` | Tümü | Kütüphane listele |
| `select_notebook` | Tümü | Aktif notebook değiştir |
| `get_notebook` | Tümü | Metadata getir |

### 5.2 Yeni Eklenmesi Gereken Tools

| Tool | Notebook | Öncelik | Açıklama |
|------|----------|----------|----------|
| `sync_sources` | Tümü | P0 | Otomatik source güncelleme |
| `get_audio_overview` | NB-5 | P2 | Audio overview URL'i al |
| `search_notebooks` | Tümü | P1 | Cross-notebook arama |
| `get_query_stats` | Tümü | P2 | Query istatistikleri |

### 5.3 Agent Kullanım Matrisi

| Agent | Birincil Notebook | Kullanım Frekansı |
|-------|------------------|------------------|
| Kilo | NB-1 + NB-2 | Her oturum |
| Windsurf | NB-1 + NB-2 | Her oturum |
| Cursor | NB-1 + NB-4 | Her oturum |
| Claude Desktop | NB-5 | Onboarding |
| Windsurf (Product) | NB-3 | Sprint planlama |

---

## 6. KALİTE GÜVENCESİ

### 6.1 Source Güncelliği Kontrolü

```bash
# Her oturum başı kontrol
php artisan notebooklm:check-sources

# Çıktı:
# ✅ NB-1: 8/8 sources güncel (son sync: 2 saat önce)
# ✅ NB-2: 8/8 sources güncel (son sync: 2 saat önce)
# ⚠️ NB-3: 6/8 sources güncel (BEKCI_CHANGELOG.md: 3 gün eski)
# ✅ NB-4: 5/5 sources güncel
# ✅ NB-5: 8/8 sources güncel
```

### 6.2 Notebook Health Dashboard

```
┌─────────────────────────────────────────────────────────────┐
│  NotebookLM Health — 2026-06-28                            │
├─────────────────────────────────────────────────────────────┤
│  NB-1: GOVERNANCE    ████████████ 100%  ✅              │
│  NB-2: ARCHITECTURE  ████████████ 100%  ✅              │
│  NB-3: PRODUCT       ██████████░░  75%   ⚠️  3 gün eski   │
│  NB-4: DOMAIN       ████████████ 100%  ✅              │
│  NB-5: ONBOARDING    ████████████ 100%  ✅              │
│                                                             │
│  Total: 5/5 notebooks active                              │
│  Query today: 47/250 (NB-1)                              │
│  Last sync: 14:32 UTC                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. BİLGİ GÖÇ PLANI

### Phase 1: Tek Notebook → Multi-Notebook (1 gün)

1. Bugünkü 28 kaynağı 5 kategoriye ayır
2. 4 yeni notebook oluştur
3. Kaynakları yeni notebook'lara dağıt
4. Yeni notebook ID'lerini MCP config'e ekle

### Phase 2: Otomatik Sync (2 gün)

1. `scripts/ops/notebooklm-sync.sh` güncelle (multi-notebook)
2. GitHub Actions workflow oluştur
3. Rate limit monitor kur
4. Fallback (manuel upload) dokümanı hazırla

### Phase 3: MCP Tool Genişletme (1 gün)

1. `sync_sources` tool implementasyonu
2. `search_notebooks` tool implementasyonu
3. Agent prompt'larını güncelle
4. Test + doğrulama

---

## 8. NOTLAR VE UYARILAR

### 8.1 Rate Limit Uyarısı

NotebookLM free tier **50 query/gün** ile sınırlı. Üretim kullanımı için **Google AI Pro** (~$20/ay) şiddetle önerilir — 250 query/gün, 5 concurrent notebook.

### 8.2 Veri Gizliliği

NotebookLM'e yüklenen dosyalar Google's AI sunucularında işlenir. Gizli/saklı bilgi içeren dosyalar (API anahtarları, müşteri verileri, finansal detaylar) **yüklenmemelidir**.

**Yüklenecek:** SAB, mimari dokümanlar, kod örnekleri, feature spec'ler
**Yüklenmeyecek:** .env, migration dosyaları, test fixture'ları, müşteri dökümanları

### 8.3 Citation Güvenilirliği

Gemini'nin ürettiği cevaplar sentezlenmiş bilgidir. Kaynak referansları doğru olsa bile, Gemini'nin yorumu her zaman doğrulanmalıdır. SAB kuralları kesin ve bağlayıcıdır — her zaman `docs/SAB.md`'ye referans verilmelidir.

---

## 9. OTURUM DOĞRULAMA

```bash
# Mevcut sync script kontrolü
./scripts/ops/notebooklm-sync.sh --dry-run

# NotebookLM health
# (yeni komut olacak)
php artisan notebooklm:health

# Kaynak güncelliği
php artisan notebooklm:check-sources

# MCP tool test
# (yeni komut olacak)
php artisan notebooklm:test --notebook=governance
```

---

## 10. ÇAPRAZ REFERANSLAR

| Doküman | İlişki |
|---------|--------|
| `KNOWLEDGE_BLUEPRINT.md` | Ana blueprint — bu dokümanın bağlı olduğu stratejik plan |
| `CORPORATE_MEMORY.md` | Katman 1 detayı — memory/ dosyaları |
| `DRIVE_STRUCTURE.md` | Katman 3-4 — NotebookLM'in entegre olduğu Drive yapısı |
| `docs/technical/NOTEBOOKLM_INTEGRATION.md` | Mevcut kurulum — buradan göç edilecek |
| `docs/technical/NOTEBOOKLM_SYNC.md` | Mevcut sync script — burası genişletilecek |
| `mcp-servers/notebooklm-mcp/` | MCP server implementasyonu |

---

*Bu doküman Yalıhan Platform'un NotebookLM ekosisteminin teknik tasarımıdır. Chief Knowledge Officer tarafından yönetilir, AI Coordinator tarafından uygulanır.*
