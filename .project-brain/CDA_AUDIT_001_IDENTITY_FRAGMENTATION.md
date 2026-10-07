# CDA-001: IDENTITY_FRAGMENTATION — Agent Constitutional Authority Drift

**Tarih:** 2026-10-05  
**Tip:** AUTHORITY_CONTRACT_DRIFT + AUTHORITY_IDENTITY_FRAGMENTATION  
**Severity:** DOWNGRADED → LOW (documentation mapping issue only)  
**Status:** RESOLVED — NOT REAL (REPO_VERIFIED)  
**Resolution:** CDA_001_EFFECTIVE_AGENT_AUTHORITY_VERIFY_01 tamamlandi

---

## Ozet

Mimari dokumantasyonda iki AGENTS.md dosyasi farkli kimlikler tanimliyordu. Ancak yapilan arastirma, **competing authority KANITLANMADI**.

---

## Bulgu Durumlari

### ONCEKI (INFERRED - Yanlis Pozitif)

```
CDA-001: IDENTITY_FRAGMENTATION
Severity: CRITICAL
Sorun: Iki AGENTS.md celiskili kimlik tanimliyor
Hipotez: Ayni actor ayni task'ta hem v2.1 hem SAAB v9 aliyor
```

### YENI (REPO_VERIFIED - Durum Aciklandi)

```
CDA-001: IDENTITY_FRAGMENTATION
Severity: DOWNGRADED
Durum: NOT REAL

Kanit:
1. Tum aktif ajanlar AYNI ROOT AGENTS.md (v2.1) aliyor
2. .agents/AGENTS.md (SAAB v9) hicbir yerde runtime referansi YOK
3. Competing authority → YOK
4. Sadece dokumantasyon mapping yetersizligi
```

---

## Effective Authority Graph (Kanitlanmis)

| Agent | Canonical Constitution | Kaynak |
|-------|----------------------|--------|
| Cline | ROOT `AGENTS.md` (v2.1) | `.clinerules` L5: "Agent Behavioral Constitution: AGENTS.md" |
| Cursor | authority.json (direct) | `.cursorrules` authority.json'a gider |
| Roo Code | ROOT `AGENTS.md` (v2.1) | `.agents/skills/*/SKILL.md` |
| Antigravity (Skills) | ROOT `AGENTS.md` (v2.1) | `.agents/skills/*/SKILL.md` |

**`.agents/AGENTS.md` (SAAB v9) → HICBIR AJAN ALMIYOR (orphan/legacy)**

---

## Kanit Detaylari

### Cline
```
.clinerules (L5-8):
# Cline MUST explicitly read and apply these canonical repository sources:
1. Agent Behavioral Constitution: AGENTS.md        ← ROOT AGENTS.MD
2. Machine Authority: .sab/authority.json
3. Canonical Skill Taxonomy: .agents/skills/SKILL_INDEX.md
4. Shared Operational State & Evidence: .project-brain/

→ CLINE ALGoritmasi: AGENTS.md → ROOT AGENTS.MD (v2.1)
```

### Cursor
```
.cursorrules:
# Yalihan AI OS — Cursor Rules
# Authority order: Human → Code/Schema → authority.json → Bekci/SAB → Reference docs
# AGENTS.md referansi YOK

→ CURSOR: authority.json'a direkt gider, AGENTS.md OKUMAZ
```

### Antigravity Skills
```
.agents/skills/yalihan-os-architect/SKILL.md (L14):
"`.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarini oku."

→ Buradaki AGENTS.md = ROOT AGENTS.MD (v2.1)
→ .agents/AGENTS.md (SAAB v9) → HICBIR YERDE RUNTIME REFERANS YOK
```

### authority.json
```json
{
  "bootstrap_entrypoint": ".sab/ONBOARDING.md",
  "authority_sync": "resync_from_source"
}
→ ONBOARDING.md: "DOCUMENT STATUS: ENTRYPOINT / NOT SSOT"
→ AGENTS.md referansi YOK
```

---

## Sonuc

```
CDA-001: IDENTITY_FRAGMENTATION → NOT REAL

Sebep:
- Tum aktif ajanlar ayni constitution'i (v2.1) aliyor
- SAAB v9 hicbir yerde referans edilmiyor → orphaned/legacy
- Dokumantasyon mapping yetersizligi → CDA raporu yeterli
```

### Human Gate Gerekli Mi?

**HAYIR.** Sistem calisiyor. Competing authority yok.

---

## Opsiyonel Aksiyonlar (User Onayli)

| # | Aksiyon | Risk | Oncelik |
|---|---------|------|---------|
| 1 | `.agents/AGENTS.md` → `.agents/AGENTS_SAAB_LEGACY.md` rename | DUSUK | MEDIUM |
| 2 | `.clinerules` L5-8: "ROOT AGENTS.md (Agent Constitution v2.1)" acik belirt | DUSUK | LOW |
| 3 | authority.json: `bootstrap_entrypoint` guncelle (AGENTS.md referansi ekle) | DUSUK | LOW |

> Bu aksiyonlar opsiyoneldir. Sistem SU AN calisiyor. Hicbir competing authority yok.

---

## Evidence Log

| Dosya | Satir | Bulgu |
|-------|-------|-------|
| `.clinerules` | L5-8 | Cline explicitly reads ROOT `AGENTS.md` |
| `.cursorrules` | L1-5 | Cursor AGENTS.md referansi YOK, authority.json'a gider |
| `authority.json` | `bootstrap_entrypoint` | `.sab/ONBOARDING.md` — AGENTS.md YOK |
| `ONBOARDING.md` | L1-7 | NOT SSOT, authority order tanimliyor ama AGENTS.md referansi YOK |
| `.agents/skills/yalihan-os-architect/SKILL.md` | L14 | Skill reads `AGENTS.md` → ROOT AGENTS.MD |
| `SEARCH: .agents/AGENTS.md` | — | Sadece dosyanin kendisinde bulunuyor |

---

## Output Files

- `.project-brain/CDA_AUDIT_001_STATUS_UPDATE.md` → REPO_VERIFIED
- `.project-brain/EVIDENCE_INDEX.md` → CDA_001_EFFECTIVE_AGENT_AUTHORITY_VERIFY_01 kaydi eklendi

---

*Bu rapor, 2026-10-05'te CDA_001_EFFECTIVE_AGENT_AUTHORITY_VERIFY_01 arastirmasi sonucunda olusturuldu.*
*Evidence Level: REPO_VERIFIED*
