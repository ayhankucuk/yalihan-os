# Sprint 6.1 — Architecture Diagram
**ERA III · Constitutional Phase**

---

## 1. Service Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Kilo Agent (Claude)                          │
│                    System Prompt + Agent Rules + Memory                 │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     Workspace Runtime Layer                             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐  │
│  │ WorkspaceContext │  │  CapabilityDisc. │  │  BusinessAutomation  │  │
│  │    Service       │  │     Service      │  │      Index           │  │
│  └────────┬─────────┘  └────────┬─────────┘  └──────────┬───────────┘  │
│           │                      │                       │             │
│           ▼                      ▼                       ▼             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐  │
│  │ WorkspaceRuntime  │  │  CapabilityRun. │  │  Cockpit Dashboard   │  │
│  │    Service       │  │     Service      │  │  (Alpine.js + Vue)  │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        Form / Aggregate Layer                           │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐  │
│  │ DynamicForm       │  │  Aggregate       │  │  IlanCrudService    │  │
│  │    Service       │  │  Lifecycle       │  │  (Single Write Auth) │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          Data Layer (MySQL)                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐  │
│  │ Ilanlar  │  │  Kisiler  │  │  Talpler │  │  ilan_aggreagtes     │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Agent Memory Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                      Kilo Agent Session                               │
├─────────────────────────────────────────────────────────────────────┤
│  Oturum Başı:                                                        │
│    1. PROJECT_BRAIN.md ──────→ Chief AI Vision                       │
│    2. WHERE_IS_WHAT.md ──────→ Hızlı referans haritası              │
│    3. SAB.md + authority.json ──→ Governance SSOT                    │
│    4. agents/*.md ────────────→ Agent rolleri                        │
│                                                                       │
│  Görev Sonrası:                                                       │
│    CHANGELOG_AGENT.md ──────→ Yapılan değişiklikler                  │
│                                                                       │
│  Oturum Sonu:                                                         │
│    SESSION_NOTES.md ────────→ Oturum özeti                           │
│    LEARNED_PATTERNS.md ──────→ Tekrarlayan hatalar/çözümler         │
│    DECISIONS.md ────────────→ Mimari kararlar                        │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Capability Runtime Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Capability Discovery                              │
│  Agent → CapabilityRuntimeService.discover(capability_name)          │
│    ├─ AgentKnowledgeService.getCapability(capability)                │
│    ├─ capability.yaml / JSON parse                                   │
│    └─ Runtime capability metadata return                              │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Capability Execution                               │
│  ┌────────────────────────────────────────────────────────────┐     │
│  │  Ilan Lifecycle Capability                                  │     │
│  │  ├─ Create ──→ IlanCrudService.create()                    │     │
│  │  ├─ Read ────→ IlanQueryService                            │     │
│  │  ├─ Update ──→ IlanCrudService.update()                    │     │
│  │  ├─ Archive ─→ IlanCrudService.archive()                  │     │
│  │  ├─ Restore ─→ IlanCrudService.restore()                  │     │
│  │  └─ Delete ──→ IlanCrudService.softDelete()               │     │
│  └────────────────────────────────────────────────────────────┘     │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────┐     │
│  │  Workspace Runtime Capability                               │     │
│  │  ├─ context.setup(agent, task)                             │     │
│  │  ├─ agent.execute(task, context)                           │     │
│  │  └─ context.teardown()                                     │     │
│  └────────────────────────────────────────────────────────────┘     │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────┐     │
│  │  Location Intelligence Capability (Sprint 6.2)              │     │
│  │  ├─ Geocoding ──→ Nominatim + AdresDB                     │     │
│  │  ├─ POI Analysis → LocationIntelligenceService             │     │
│  │  └─ Scoring ────→ location_signal_score (0–100)           │     │
│  └────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 4. SAB Governance Architecture

```
┌──────────────────────────────────────────────────────────────────────┐
│                    SAAB (Software Architecture Board)                 │
├──────────────────────────────────────────────────────────────────────┤
│  ANAYASA (SAB.md) ──────────→ Technical Constitution                  │
│  │  Checksum korumalı (değişiklik → sab-propose.sh)                 │
│  │                                                                    │
│  ├─ authority.json ──────────→ Governance SSOT                       │
│  │     Tüm kural çakışmalarında referans                             │
│  │                                                                    │
│  ├─ BEKCI (Bileşen Kalite) ──→ AST-based quality guards              │
│  │     ├─ SilentCatch ─────────→ Boş catch yakalar                   │
│  │     ├─ EnvUsage ─────────────→ env() kullanımını izler            │
│  │     ├─ FirstWithoutOrderBy ──→ Determinism kontrolü               │
│  │     ├─ FA_Guard ─────────────→ Font Awesome ikon koruması         │
│  │     └─ RouteHasFQCN ──────────→ Blade Route::has() FQCN           │
│  │                                                                    │
│  ├─ ANTIGRAVITY ─────────────→ Pre-write gate tools                  │
│  │     ├─ component-check.sh ───→ Bileşen var mı?                     │
│  │     ├─ route-check.sh ──────→ Route tanımlı mı?                    │
│  │     └─ schema-check.sh ─────→ DB kolonu var mı?                    │
│  │                                                                    │
│  └─ SAB INTEGRITY SCAN ──────→ Mimari ihlal tarayıcı                 │
│        Tüm dosyalarda kural ihlali tarar                               │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 5. Sprint 6.1 → 6.2 Transition

```
Sprint 6.1 Foundation                    Sprint 6.2 Capability
┌──────────────────────┐                 ┌──────────────────────┐
│ Workspace Runtime ✅ │  ────┬─────────→ │ Location Intelligence│
│ Dynamic Forms ✅     │       │           │ (Geocoding + POI)   │
│ Aggregate Lifecycle✅│       │           │                      │
│ Capability Runtime ✅│       │           │ Pipeline:            │
│ Cockpit Dashboard ✅│       │           │ Address → Coords →  │
└──────────────────────┘       │           │ Score → AI Summary  │
                               │           └──────────────────────┘
                               │
                    ┌──────────┴──────────┐
                    │  Shared Foundation   │
                    │  • PoiService ✅     │
                    │  • LocationIntellig. ✅│
                    │  • AdresLocation ✅  │
                    │  • LocationCopilot ✅│
                    └─────────────────────┘
```

---

## 6. Data Flow: Ilan → Location Intelligence (Sprint 6.2)

```
┌──────────┐
│  Ilan    │  (ilan_id: 123)
└────┬─────┘
     │ getAddress()
     ▼
┌─────────────────────────────────────────┐
│  Address String                          │
│  "Gölbet Mah., Yalıkavak, Bodrum, Muğla"│
└────────┬────────────────────────────────┘
         │ GeocodingService.resolve()
         ▼
┌─────────────────────────────────────────┐
│  Coordinates { lat: 37.06, lng: 27.42 } │
│  Source: nominatim / adres_db           │
└────────┬────────────────────────────────┘
         │ LocationIntelligenceService.analyze()
         ▼
┌─────────────────────────────────────────┐
│  LocationInsightDTO                      │
│  • score: 72                             │
│  • access: 28 / density: 22 / coverage: 22│
│  • top_groups: [beach, food, shopping]  │
│  • confidence: HIGH                      │
└────────┬────────────────────────────────┘
         │ AI Summary (isteğe bağlı)
         ▼
┌─────────────────────────────────────────┐
│  IlanCrudService.updateLocationData()   │
│  → ilanlar.location_data = JSON         │
│  → ilanlar.location_score = 72          │
│  → ilanlar.location_score_confidence=HIGH│
└─────────────────────────────────────────┘
```

---

## 7. Stack Summary

```
Frontend
  └─ Alpine.js + Vue 3 (cockpit dashboard)
  └─ Tailwind CSS + custom design system
  └─ Vite build system

Backend
  └─ Laravel 10 / PHP 8.2+
  └─ MySQL (prod) + SQLite (test)

AI / Agents
  └─ YalihanCortex (Ollama + DeepSeek + OpenAI)
  └─ Kilo Agent (Claude Sonnet 4.6)
  └─ Workspace Runtime + Capability Discovery

Data
  └─ POI Database (location_intelligence config)
  └─ Türkiye Administrative Boundaries (il/ilçe/mahalle)
  └─ Nominatim / OSM (geocoding)

Quality
  └─ SAAB Governance (SAB.md + authority.json)
  └─ BEKCI AST Guards (6 rule types)
  └─ Antigravity Pre-write Gates
```
