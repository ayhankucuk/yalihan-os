# Klio — Architecture Intelligence Agent Backlog

**Agent:** Klio
**Identity:** Architecture Intelligence Agent
**Role:** Bağımsız kalite güvencesi. WenOX üretimini analiz eder, risk bulur, mimari bozulmaları yakalar.
**Based on:** WenOX'tan bağımsız. SAAB Board'a raporlar.

> **Klio bir ajanın işini yapmaz. Sadece analiz eder, raporlar, risk bulur.**
> WenOX Execution Agent ile aynı kişi olabilir — ama rol BAĞIMSIZDIR.

---

## KL-001 Architecture Health Audit

**Tip:** Sürekli
**Açıklama:** Her hafta veya önemli commit sonrası mimari bozulma kontrolü

Kontrol eder:
- [ ] DDD ihlalleri (Aggregate sınırları, Entity/Value Object karışıklığı)
- [ ] Controller şişmesi (Thin Controller kuralı)
- [ ] Model self-persistence ($this->save() modellerde)
- [ ] Event replay güvenliği
- [ ] Tenant isolation
- [ ] Queue safety
- [ ] Service sorumluluk sınırları

Çıktı:
```
Architecture Health Score: XX/100
BLOCKER: X
HIGH: Y
MEDIUM: Z
Trend: ↑ / ↓ / →
```

**Cycle:** Haftalık veya SAAB talep ettiğinde

---

## KL-002 Technical Debt Registry

**Tip:** Sürekli
**Açıklama:** Repodaki bilinen teknik borçları izler

Format:
```
TD-001 | Tenant isolation gap in FinansalIslem | SAB Rule 1 | MEDIUM | 2026-08-07
TD-002 | Model self-persistence in Finance domain | DDD | MEDIUM | Remediation: Klio-004 sonrası
TD-003 | Missing unique constraints in migrations | DB | HIGH | EX-002 pilot sonrası
```

Öncelik sırası: BLOCKER > HIGH > MEDIUM > LOW

---

## KL-003 ADR Consistency Review

**Tip:** Per-change
**Açıklama:** Kod değişikliği olduysa, ADR ile tutarlılığı kontrol eder

Sorgular:
- ADR-031 (Finance Domain Boundary) → kod gerçekten bu karara uyuyor mu?
- ADR-012 (Tenant Isolation) → yeni kod SAB Rule 1'e uyuyor mu?
- ADR-020 (Event Replay Safety) → yeni event replay-safe mi?

Çıktı:
```
ADR-031: INCONSISTENT — FinansalIslem tenant_id eksikliği hâlâ mevcut
ADR-012: CONSISTENT
ADR-020: CONSISTENT
```

---

## KL-004 Capability Health Dashboard

**Tip:** Haftalık
**Açıklama:** Her aktif capability için kalite puanı

```
EX-001 Guest Communication
  Architecture:  94/100
  Tests:           98/100
  Business:        IN_PROGRESS
  Pilot:          READY
  Debt:           2 open

EX-002 Finance Agent
  Architecture:  97/100
  Tests:           96/100
  Business:       IN_PROGRESS
  Pilot:          READY
  Debt:           1 open
```

Dashboard, KL-001 + KL-002 + KL-003'ten beslenir.

---

## KL-005 Documentation Drift Detection

**Tip:** Per-commit
**Açıklama:** Kod değişti, doküman değişmedi — veya tersi

Örnek:
```
Finance Agent README.md — 3 gün önce güncellenmiş
Kod: 12 yeni commit, 4 yeni dosya
→ README güncellenmemiş — DRIFT
```

---

## Öncelik Sırası

### Faz 1 (Hemen)
1. KL-001 Architecture Health Audit
2. KL-002 Technical Debt Registry

### Faz 2 (1-2 hafta)
3. KL-003 ADR Consistency Review
4. KL-005 Documentation Drift Detection

### Faz 3 (Sonra)
5. KL-004 Capability Health Dashboard (önce 1-4 tamamlanmalı)

---

## Agent Kimlik Ayrımı

| Alan | WenOX | Klio |
|------|-------|------|
| Amaç | Capability üretmek | Mimari kalite güvencesi |
| Başarı | Zamanında teslimat | Risk erken tespit |
| Raporlama | SAAB Board | SAAB Board |
| Rework yaparmı? | Gerekirse | Hayır — sadece raporlar |

---

## WenOX + Klio Ayrımı Kuralı

```
WenOX kod yazarken Klio BEKLEMEZ.
WenOX teslim ettiğinde Klio REVIEW EDER.

Asla aynı commit'te hem WenOX hem Klio olmaz.
```

---

*Klio Architecture Intelligence Backlog — Active 2026-08-07*
