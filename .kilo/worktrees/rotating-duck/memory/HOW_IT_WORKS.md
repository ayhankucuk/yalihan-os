# HOW IT WORKS — Sistem Nasıl Çalışır

> Yalıhan Emlak AI OS — Teknik çalışma prensipleri
> Yeni geliştirici veya AI agent için sistemi anlama kılavuzu

---

## SİSTEMİN ÜÇ KATMANI

```
┌─────────────────────────────────────────────────────────────┐
│  1. KULLANICI KATMANI                                     │
│  ├── Admin Panel (Laravel Blade + Alpine.js)              │
│  ├── Frontend (ilanlar, danışmanlar)                      │
│  ├── Owner Portal (/owner prefix)                         │
│  ├── Telegram Bot (AI sohbet)                             │
│  └── Public API (/api/v1/public-ai/ilan-arama)            │
├─────────────────────────────────────────────────────────────┤
│  2. İŞ MANTIĞI KATMANI                                   │
│  ├── YalihanCortex (AI orchestrator)                      │
│  ├── Domain Services (IlanCrudService, KisiService vb.)  │
│  ├── AI Motors (94 service)                               │
│  └── Governance (SAB enforcement)                          │
├─────────────────────────────────────────────────────────────┤
│  3. VERİ KATMANI                                          │
│  ├── Core DB (ilanlar, kisiler, leads)                   │
│  ├── Market DB (market_listings, price_stats)            │
│  ├── Projections (CQRS read models)                       │
│  └── Telemetry (ai_logs, audit_logs)                     │
└─────────────────────────────────────────────────────────────┘
```

---

## BİR İLAN NASIL YARATILIR?

```
1. Admin Wizard (Step 1-5)
   ↓
2. StoreIlanRequest (validation)
   ↓
3. IlanCrudService::store()
   ↓
4. ListingCreated Event (dispatch)
   ↓
5. SyncListingProjectionJob (queue)
   ↓
6. listing_search_projection (CQRS read model)
   ↓
7. AI Buyer Match Engine (signal)
   ↓
8. AI Search API (/api/v1/public-ai/ilan-arama)
```

**Önemli:** İlan asla direkt projection'a yazılmaz. Sadece Event + Queue ile tetiklenir.

---

## AI ARAMA NASIL ÇALIŞIR?

```
1. Kullanıcı arama yapar
   ↓
2. POST /api/v1/public-ai/ilan-arama
   ↓
3. AIListingSearchService (NLP intent parsing)
   ↓
4. DeepSeek (intent + keyword enrichment)
   ↓
5. listing_search_projection (sorgu)
   ↓
6. Result + AI explanation döner
```

**Public endpoint — auth yok, ama sensitive field'lar filtreleniyor:**
- owner_id, danisman_id, metadata DÖNDÜRÜLMEZ

---

## AI DECISION LOOP

```
Model: Prediction → Action → Result → Feedback
         ↓            ↓        ↓         ↓
      Cortex     Advisor   User     Learning
         ↓            ↓        ↓         ↓
    AI Engine    AI Motor   KPI     Signal
```

**Her AI kararı izlenir:** `ai_query_logs`, `buyer_match_logs`, `ai_deal_prediction_logs`

---

## BEKÇİ (GOVERNANCE) NASIL ÇALIŞIR?

```
┌─────────────────────────────────────────────────────────────┐
│  BEKÇİ v2.1 — Cognitive Guardian                          │
├─────────────────────────────────────────────────────────────┤
│  1. AST Scanner                                          │
│     ├── NamingAuthorityAstRule                           │
│     ├── ForbiddenFieldAstRule                             │
│     └── SilentCatchAstRule                               │
│                                                             │
│  2. Guard Scripts                                        │
│     ├── ci-guard-tenant-isolation.sh                     │
│     ├── ci-guard-naming-authority.sh                    │
│     ├── ci-guard-exception-swallow.sh                   │
│     └── check-hardcoded-endpoints.sh                    │
│                                                             │
│  3. Health Monitoring                                    │
│     ├── bekci:health                                     │
│     ├── bekci:learn                                      │
│     └── bekci:audit (Telescope)                         │
└─────────────────────────────────────────────────────────────┘
```

**CI Pipeline:**
```
test → sab:integrity-scan → bekci:wizard-contract → env-drift-guard → quality-gate.sh
```

---

## MCP SERVER NASIL ÇALIŞIR?

```
┌─────────────────────────────────────────────────────────────┐
│  TypeScript Bridge (mcp/) — Windsurf                      │
│  Kullanıcı: bekci.scan, bekci.learn, bekci.health       │
│  PHP artisan çağırır, JSON döner                         │
├─────────────────────────────────────────────────────────────┤
│  JavaScript Server (mcp-servers/) — Cursor/Claude        │
│  Kullanıcı: validate_file, get_canonical, record_learning │
│  Guard script çalıştırır, knowledge base günceller     │
└─────────────────────────────────────────────────────────────┘

Transport: stdio (Claude Desktop, Cursor doğrudan entegrasyon)
Protocol: MCP v0.5.0
```

---

## TENANT ISOLATION NASIL ÇALIŞIR?

```
Her istek:
  ↓
SetTenantContext Middleware
  ↓
$tenantId = auth()->user()?->tenant_id
  ↓
Tüm query'lere otomatik where tenant_id = $tenantId
  ↓
Cross-tenant erişim → Exception → CI FAIL
```

**Kural 1 (En Ağır İhlal):** Cross-tenant veri erişimi KESİNLİKLE yasak.

---

## CACHE STRATEJISI

| Data Type | TTL | Invalidation |
|-----------|-----|-------------|
| Dynamic Lists | 60-120s | Model saved/deleted event |
| Financial/KPI | 600s | LedgerEntry created |
| SEO Meta | 24h | Manuel |

---

## EVENT-DRIVEN ARCHITECTURE

```
Domain Event          Event Class               Queue Job
─────────────────────────────────────────────────────────
Ilan created     →  ListingCreated      →  SyncListingProjectionJob
Lead registered  →  LeadRegistered      →  SyncLeadProjectionJob
Talep received   →  TalepReceived       →  SyncTalepProjectionJob
Ledger entry    →  LedgerDoubleEntry    →  UpdateLedgerBalanceJob
```

**Her event idempotent olmalı.**
