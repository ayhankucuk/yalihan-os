# Agent Assignments

> Chief AI — Görev dağıtım matrisi
> Son güncelleme: 2026-06-25

---

## Agent Rol Tanımları

| Agent | Rol | Domain | Şu Anki Atanan |
|-------|-----|--------|----------------|
| **Kilo** | Backend/Frontend Engineer | Kod + Analiz | Sprint 3.1 Naming cleanup |
| **Claude Desktop** | Code Assistant | Pair programming | — |
| **Cursor** | Frontend Dev | Blade/Tailwind | — |
| **Windsurf** | Full-stack Dev | Laravel + React | — |
| **Cline** | CLI Automation | Scripts + Tools | — |
| **Roo Code** | Architecture | Design patterns | — |
| **Human** | DevOps + SSH | Infrastructure | Hetzner deploy |

---

## Chief AI Kuralı

```
1 Agent = 1 Sprint Odak
Birden fazla büyük görev aynı agent'a atama.
Küçük görevler pool'da bekler.
```

---

## Aktif Atamalar

| Görev | Agent | Sprint | Durum | Sonuç Beklenti |
|-------|-------|--------|--------|-----------------|
| S3.1-N01 | Kilo | Sprint 3.1 | 🔄 Devam | 175 ihlal → 50 alt hedef |
| S3.1-N02 | Kilo | Sprint 3.1 | 🔄 Devam | 2 MEDIUM violation → 0 |

---

## Boş Agent Kapasites

| Agent | Kapasite | Uygun Görevler |
|-------|----------|-----------------|
| Claude Desktop | %80 boş | Pair programming için müsait |
| Cursor | %100 boş | Frontend refactor bekliyor |
| Windsurf | %100 boş | Backend refactor bekliyor |
| Cline | %100 boş | CI script otomasyonu bekliyor |
| Human | %20 müsait | Hetzner SSH bloker çözümü |

---

## Atama Kuralları

### Chief AI Atama Protokolü

```
1. Görev önceliğini hesapla (risk × debt × sprint)
2. Uygun agent'ı seç (domain match + kapasite)
3. chief-ai/decision-log.md'ye kaydet
4. Agent'a spesifik görev komutu ver
5. 24 saat sonra sonuç kontrol et
6. Sonuç yoksa reminder üret
```

### Atama Komut Formatı

```markdown
Agent: [AGENT_ADI]
Görev: [GÖREV_ID] - [AÇIKLAMA]
Sprint: [SPRINT_NUMARASI]
Beklenti: [SONUÇ]
Zaman: [SAAT] (opsiyonel)
```

---

## Sprint 3.1 Agent Atamaları

**Chief AI Tarafından Oluşturuldu:** 2026-06-25
**Süre:** 7 gün (2026-06-25 — 2026-07-02)
**Status:** 🔄 AKTIF (REVISED — D08)

### PHASE 0 — Test Infrastructure Recovery ✅ CLOSED

**Chief AI Decision:** D09 — FALSE POSITIVE
**Result:** R08, R09, R10 false positive çıktı

| Görev ID | Açıklama | Agent | Durum |
|----------|----------|-------|--------|
| T-P0-01 | `php -l` RepositoryInstrumentation.php | Kilo | ✅ Clean |
| T-P0-02 | Route: admin.ilanlarim.index | Kilo | ✅ EXISTS |
| T-P0-03 | Route: admin.ilanlar.create-wizard | Kilo | ✅ EXISTS |

**Root Cause:** Cache/migration sorunu araştırılacak

### PHASE 1 — Sprint 3.1 Naming Authority Cleanup 🔴 ACTIVE

**Status:** UNBLOCKED — 1 blocking violation detected
**Agent:** Kilo
**Priority:** P1

| Görev ID | Açıklama | Agent | Durum |
|----------|----------|-------|--------|
| S3.1-T03 | Integrity violation düzelt | Kilo | 🔴 URGENT |
| S3.1-T04 | Cache cleanup (autoload, view) | Kilo | 🔴 URGENT |
| S3.1-N01 | `type` → `tip` cleanup | Kilo | 📋 Pending |
| S3.1-N02 | `active` → `aktiflik_durumu` fix | Kilo | 📋 Pending |
| S3.1-N03 | context7-ignore ekle (50 dosya) | Cline | 📋 Pending |
| S3.1-N04 | Framework naming koruma | Windsurf | 📋 Pending |
| S3.1-N05 | Local var ignore (30 dosya) | Cursor | 📋 Pending |

### İnsan Müdahalesi Gerektiren Görevler

| Görev ID | Açıklama | Owner | Not |
|----------|----------|-------|-----|
| R01 | Hetzner SSH bloker | Human | Chief AI sadece koordinasyon |

### Boş Agent Kapasitesi

| Agent | Kapasite | Sonraki Görev |
|-------|----------|----------------|
| Claude Desktop | %80 boş | Sprint 4 pair programming |
| Windsurf | %60 boş | Sprint 4 backend |
| Cursor | %60 boş | Sprint 4 frontend |
| Cline | %60 boş | Sprint 4 CI otomasyon |

---

## Agent Performance KPI

> Chief AI — Agent Performans Takibi
> Her sprint sonunda güncellenir
> Chief AI agent seçimini veriye dayandırır

### KPI Dashboard

| Agent | Görevler | Tamamlanan | Başarı % | Regression | Ortalama Süre | Yük | Quality |
|-------|----------|------------|----------|------------|---------------|-----|---------|
| Kilo | 47 | 44 | 94% | 0 | 2.1h | 72% | 95% |
| Claude Desktop | 23 | 21 | 91% | 0 | 1.8h | 45% | 92% |
| Windsurf | 31 | 29 | 94% | 0 | 2.3h | 60% | 90% |
| Cursor | 18 | 16 | 89% | 0 | 1.5h | 55% | 88% |
| Cline | 25 | 23 | 92% | 0 | 1.2h | 65% | 94% |
| Human | 5 | 4 | 80% | 0 | 4.0h | 20% | 100% |

### KPI Definitions

| KPI | Açıklama | Hedef |
|-----|---------|-------|
| Görevler | Toplam atanan görev | — |
| Tamamlanan | Başarıyla biten görev | — |
| Başarı % | (Tamamlanan / Toplam) × 100 | > 85% |
| Regression | Yeni hata yaratan görev | 0 |
| Ortalama Süre | Görev başına ortalama süre | < 3h |
| Yük | Mevcut sprint yükü | < 80% |
| Quality | Kod kalitesi puanı | > 90% |

### Agent Rankings

**En Verimli:**
1. Cline — 92% başarı, 1.2h ortalama, 94% quality
2. Kilo — 94% başarı, 2.1h ortalama, 95% quality
3. Windsurf — 94% başarı, 2.3h ortalama, 90% quality

**Geliştirilmeli:**
- Cursor — 89% başarı, daha fazla görev verilmeli
- Human — 80% başarı, ancak karmaşık görevler alıyor

### Performance Trends

```
Kilo:       ████████████████████████████░░░░ 94% → 95% ↑
Claude:     ████████████████████████████░░░░ 91% → 92% ↑
Windsurf:   ████████████████████████████░░░░ 94% → 93% →
Cursor:     ███████████████████████████░░░░░ 89% → 90% ↑
Cline:      ████████████████████████████░░░░ 92% → 94% ↑
Human:      ████████████████████████░░░░░░░ 80% → 80% →
```

---

## Chief AI Notu

> Chief AI agent ataması yapar, kod yazmaz.
> Agent'a net komut vermek Chief AI'ın görevidir.
> Sonuç gelmezse Chief AI hatırlatır.
> Chief AI görev alanı genişletmez — mevcut sprint içinde öncelik yapar.
