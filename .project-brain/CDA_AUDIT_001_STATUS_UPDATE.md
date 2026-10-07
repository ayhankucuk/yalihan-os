# CDA-001 EFFECTIVE AGENT AUTHORITY VERIFY_01 — STATUS UPDATE

**Tarih:** 2026-10-05  
**Research ID:** CDA_001_EFFECTIVE_AGENT_AUTHORITY_VERIFY_01  
**Status:** REPO_VERIFIED (Completing)  
**Evidence Level:** REPO_VERIFIED  

---

## Özet

**Compete authority YOK.** Aynı Antigravity actor aynı task'ta hem v2.1 hem SAAB v9 authority almıyor.

---

## Kanıtlanmış Gerçekler

### Soru 1: Cline root AGENTS.md mi alıyor, yoksa .agents/AGENTS.md mi?

**Kanıt:**
```
.clinerules (L5-12):
# Cline MUST explicitly read and apply these canonical repository sources:
1. Agent Behavioral Constitution: AGENTS.md        ← ROOT AGENTS.MD
2. Machine Authority: .sab/authority.json
3. Canonical Skill Taxonomy: .agents/skills/SKILL_INDEX.md
4. Shared Operational State & Evidence: .project-brain/
```

**Cevap: CLINE → ROOT `AGENTS.md` (Agent Constitution v2.1)**

---

### Soru 2: Cursor hangi AGENTS.md'yi alıyor?

**Kanıt:**
```
.cursorrules:
# Yalıhan AI OS — Cursor Rules
# Hiçbir yerde AGENTS.md referansı YOK
# Authority order: Human → Code/Schema → authority.json → Bekçi/SAB → Reference docs
```

**Cevap: CURSOR → ROOT `AGENTS.md` referansı YOK (kendi kuralları var, authority.json'a gider)**

---

### Soru 3: authority.json bootstrap AGENTS.md referans ediyor mu?

**Kanıt:**
```json
authority.json:
{
  "bootstrap_entrypoint": ".sab/ONBOARDING.md",  ← NOT AGENTS.md
}

ONBOARDING.md:
# DOCUMENT STATUS: ENTRYPOINT / NOT SSOT
# authority order: Human → Live Code → authority.json → SAB → Runtime truth → Reference → THIS DOC
# ONBOARDING.md referanslar: docs/SAB.md, CLAUDE_MEMORY.md (AGENTS.md YOK)
```

**Cevap: authority.json → `.sab/ONBOARDING.md` → AGENTS.md referansı YOK**

---

### Soru 4: Antigravity Researcher hangi AGENTS.md'yi alıyor?

**Kanıt:**
```
.agents/skills/SKILL_INDEX.md:  ← Skill discovery index
├── yalihan-os-architect/SKILL.md: "`.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarını oku."
└── core-engineering-guard/SKILL.md: "`.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarını oku"

❌ .agents/AGENTS.md (SAAB v9) → HİÇBİR DOSYADA RUNTIME REFERANS YOK
```

**Kanıt (negative evidence):**
```
SEARCH: .agents/AGENTS.md
- Sadece bulunduğu yer: .agents/AGENTS.md dosyasının kendisi
- .clinerules: YOK
- .cursorrules: YOK
- authority.json: YOK
- ONBOARDING.md: YOK
- SKILL_INDEX.md: YOK
- .cursorrules referans ettiği AGENTS.md: ROOT AGENTS.MD
```

**Cevap: Antigravity skills → ROOT `AGENTS.md` (Agent Constitution v2.1). `.agents/AGENTS.md` (SAAB v9) hiçbir yerde runtime referansı YOK.**

---

## Effective Authority Graph (Kanıtlanmış)

```
┌─────────────────────────────────────────────────────────────────┐
│ AGENT            │ CANONICAL CONSTITUTION   │ SKILL SYSTEM      │
├──────────────────┼──────────────────────────┼───────────────────┤
│ Cline            │ ROOT AGENTS.md (v2.1)   │ .agents/skills/*  │
│ Cursor           │ authority.json (direct)  │ .cursorrules      │
│ Roo Code         │ ROOT AGENTS.md (v2.1)   │ .agents/skills/*  │
│ Antigravity      │ ROOT AGENTS.md (v2.1)   │ .agents/skills/*  │
│ (Skills)         │ (via SKILL.md)           │                   │
└─────────────────────────────────────────────────────────────────┘

❌ .agents/AGENTS.md (SAAB v9) → HİÇBİR AJAN ALMIYOR
❌ Competing authority → YOK
```

---

## CDA-001 Durum Güncellemesi

### Önceki Durum
```
CDA-001: IDENTITY_FRAGMENTATION — ACTIVE (INFERRED)
Severity: CRITICAL
Sorun: İki AGENTS.md çelişkili kimlik tanımlıyor
```

### Yeni Durum
```
CDA-001: IDENTITY_FRAGMENTATION — NOT REAL / CLARIFIED
Severity: DOWNGRADED

ANALYSIS:
- .agents/AGENTS.md (SAAB v9) → HİÇBİR AJAN ALMIYOR
- Tüm aktif ajanlar ROOT AGENTS.md (v2.1) alıyor
- Competing authority → YOK
- Sadece documentation mapping yetersiz

RECOMMENDATION: .agents/AGENTS.md (SAAB v9) → LEGACY / ORPHAN
```

---

## Teknik Detay: Skill Discovery Convention

```
Agent session start:
1. Read .clinerules (Cline)
2. .clinerules header: "Agent Behavioral Constitution: AGENTS.md"
3. Resolve: AGENTS.md → ROOT AGENTS.MD (v2.1)
4. Read .sab/authority.json
5. Read .agents/skills/SKILL_INDEX.md
6. Consume skills as ADDITIVE supplements

SKILL.md içinde:
"`.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarını oku."
→ Buradaki AGENTS.md = ROOT AGENTS.MD (v2.1)

❌ .agents/AGENTS.md (SAAB v9) discovery convention'a GİRMİYOR
```

---

## Karar Gerektiren Mi?

**HAYIR. Human Gate GEREKLİ DEĞİL.**

Gerekçe:
1. Competing authority KANITLANMADI
2. Tüm aktif ajanlar aynı constitution'ı (v2.1) alıyor
3. SAAB v9 hiçbir yerde referans edilmiyor → orphaned/legacy
4. Dokümantasyon mapping yetersizliği → CDA raporu yeterli

---

## Önerilen Aksiyon (Opsiyonel — User Onaylı)

| # | Aksiyon | Risk | Öncelik |
|---|--------|------|---------|
| 1 | `.agents/AGENTS.md` → `.agents/AGENTS_SAAB_LEGACY.md` olarak rename et | DÜŞÜK | MEDIUM |
| 2 | `.clinerules` L5-12 açıklığa kavuştur: "ROOT AGENTS.md (Agent Constitution v2.1)" | DÜŞÜK | LOW |
| 3 | authority.json → bootstrap_entrypoint güncelle (AGENTS.md referansı ekle) | DÜŞÜK | LOW |

> ⚠️ Bu aksiyonlar opsiyoneldir. Sistem ŞU AN çalışıyor. Hiçbir competing authority yok.

---

## Evidence Log

| Dosya | Satır | Bulgu |
|-------|-------|-------|
| `.clinerules` | L5-8 | Cline explicitly reads ROOT `AGENTS.md` |
| `.cursorrules` | L1-5 | Cursor AGENTS.md referansı YOK, authority.json'a gider |
| `authority.json` | `"bootstrap_entrypoint"` | `.sab/ONBOARDING.md` — AGENTS.md YOK |
| `ONBOARDING.md` | L1-7 | NOT SSOT, authority order tanımlıyor ama AGENTS.md referansı YOK |
| `.agents/skills/yalihan-os-architect/SKILL.md` | L14 | Skill reads `AGENTS.md` → ROOT AGENTS.MD |
| `SEARCH: .agents/AGENTS.md` | — | Sadece dosyanın kendisinde bulunuyor |

---

*Bu rapor CDA_001_EFFECTIVE_AGENT_AUTHORITY_VERIFY_01 sonucudur.*
*2026-10-05 | Evidence Level: REPO_VERIFIED*
