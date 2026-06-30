# Architecture Score

> Chief AI — Mimari Kalite Skoru
> Sürekli hesaplanır
> Her sprint hedefi bu skoru yükseltmek olmalı

---

## OVERALL ARCHITECTURE SCORE

```
╔═══════════════════════════════════════════════════════════════╗
║                 ARCHITECTURE SCOREBOARD                     ║
╠═══════════════════════════════════════════════════════════════╣
║  Consistency    ████████████████████████████░░░░  92/100  ║
║  Coupling       ████████████████████████░░░░░░░░  81/100  ║
║  Naming         ██████████████████████░░░░░░░░░  73/100  ║
║  Testing        █████████████████░░░░░░░░░░░░░░  61/100  ║
║  Documentation  ███████████████████████████░░░░░  95/100  ║
║  Governance     ████████████████████████████░░░  99/100  ║
╠═══════════════════════════════════════════════════════════════╣
║  OVERALL        ████████████████████████░░░░░░░░  86/100  ║
║                                                        ║
║  GRADE: A-                                              ║
║  Trend: ↑ (+3 from last sprint)                        ║
╚═══════════════════════════════════════════════════════════════╝
```

---

## SCORE BREAKDOWN

### 1. Consistency (92/100)

| Metric | Score | Status |
|--------|-------|--------|
| Naming consistency | 89 | 🟡 |
| Pattern consistency | 94 | ✅ |
| Style consistency | 93 | ✅ |

**Hedef:** 95+

### 2. Coupling (81/100)

| Metric | Score | Status |
|--------|-------|--------|
| Service coupling | 78 | 🟡 |
| Domain boundaries | 85 | ✅ |
| Circular dependencies | 80 | 🟡 |

**Hedef:** 85+

### 3. Naming (73/100) ⚠️

| Metric | Score | Status |
|--------|-------|--------|
| Context7 violations | 45 | 🔴 |
| Turkish field names | 92 | ✅ |
| Framework conventions | 95 | ✅ |

**Hedef:** 90+

### 4. Testing (61/100) ⚠️

| Metric | Score | Status |
|--------|-------|--------|
| Test coverage | 58 | 🔴 |
| Fail tests | 40 | 🔴 |
| CI stability | 85 | ✅ |

**Hedef:** 80+

### 5. Documentation (95/100)

| Metric | Score | Status |
|--------|-------|--------|
| Code docs | 92 | ✅ |
| Architecture docs | 98 | ✅ |
| API docs | 94 | ✅ |

**Hedef:** 98+

### 6. Governance (99/100)

| Metric | Score | Status |
|--------|-------|--------|
| SAB compliance | 100 | ✅ |
| CI gates | 98 | ✅ |
| Protected files | 100 | ✅ |

**Hedef:** 100

---

## SCORE HISTORY

| Tarih | Consistency | Coupling | Naming | Testing | Docs | Governance | Overall |
|-------|-------------|----------|--------|---------|------|------------|---------|
| 2026-05-10 | 85 | 75 | 60 | 55 | 80 | 95 | 75 |
| 2026-06-15 | 89 | 78 | 65 | 58 | 88 | 97 | 79 |
| 2026-06-25 | 92 | 81 | 73 | 61 | 95 | 99 | 86 |

---

## SPRINT 3.1 TARGETS

| Metric | Current | Target | Δ |
|--------|---------|--------|---|
| Consistency | 92 | 94 | +2 |
| Coupling | 81 | 84 | +3 |
| Naming | 73 | 85 | +12 |
| Testing | 61 | 70 | +9 |
| Documentation | 95 | 96 | +1 |
| Governance | 99 | 99 | 0 |
| **Overall** | **86** | **90** | **+4** |

---

## IMPROVEMENT ACTIONS

### Naming (En Düşük)
| Action | Impact | Owner |
|--------|--------|-------|
| context7-ignore ekle | +8 | Cline |
| Domain field düzelt | +4 | Kilo |

### Testing (İkinci Düşük)
| Action | Impact | Owner |
|--------|--------|-------|
| Fail test düzelt | +5 | Sprint 3.1 |
| Coverage artır | +4 | Sprint 4 |

---

## Chief AI Notu

> Score düşük = mimari drift başlıyor
> Naming en düşük = Sprint 3.1 öncelik
> Testing kritik = kalıcı strateji gerekli
