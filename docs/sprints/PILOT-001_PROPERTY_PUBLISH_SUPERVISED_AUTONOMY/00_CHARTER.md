# PILOT-001 — Property Publish Supervised Autonomy

## Charter

**Sprint:** PILOT-001
**Start:** 2026-08-13
**Status:** ACTIVE
**Owner:** Kilo Agent / SAAB
**Branch:** `integration/era-v-phase2a-e01`
**YDL Phase:** Phase 3 — Agent Context Integration (operational)

---

## Mission

> Workspace'ten publish-ready property üretme sürecinde YDL Phase 3 context + authority + blocker + evidence lifecycle'ını gerçek operasyon üzerinde kanıtlamak.

**Temel soru:** YDL Phase 3 zinciri çalışırsa, bir mülkün yayına hazırlanması sürecinde danışmanın manuel emeği ölçülebilir şekilde azalır mı?

---

## SAAB Karar Gerekçesi

**Alternatif değerlendirmesi:**

| Aday | Neden Pilot Değil / Değil |
|------|--------------------------|
| Reservation Core | Teknik olarak güçlü ama mevcut ilerlemesi yüksek — YDL zinciri kanıtlanmadan önce kritik kararlar devrede |
| Booking G35 | External blocker (BOOKING_COM onboarding) — paralel iş diye pilot kapsamı dışı |
| N1-B Notification | Destek capability; ana iş akışını kanıtlamaz |

**Neden Property Publish:**
- En somut BAI kazanımı: "bir villa yayına hazır mı?" sorusu → otomatik cevap
- Publish readiness ölçümü: 60 dk manuel → X dk otomatik
- Supervised autonomy ilk uygulaması: AI PREPARES → AI VALIDATES → HUMAN APPROVES → SYSTEM PUBLISHES

---

## Supervised Autonomy Modeli

**Operating Model — Property Publish**

```
┌─────────────────────────────────────────────────────┐
│  Agent Session Başlangıcı                            │
│                                                     │
│  1. php artisan ydl:context                         │
│     → authority: FULL / LIMITED_BY_BLOCKER / STOP     │
│                                                     │
│  2. IF authority = STOP                             │
│     → HALT: blocker açıkla, sonlandır               │
│                                                     │
│  3. IF authority = LIMITED_BY_BLOCKER               │
│     → görev blocker'la kesişiyor mu?                │
│        → KESİŞİYOR: görev STOP, açıkla             │
│        → KESİŞMİYOR: devam et                     │
│                                                     │
│  4. IF authority = FULL                            │
│     → görevi başlat                                │
└─────────────────────────────────────────────────────┘
```

**Authority Decision Logic:**

```
Görev: Property Publish
YDL Context: BLK-001 [Booking Production] → DO_NOT_CONTINUE_BOOKING_CODE

Intersection analysis:
  Property Publish scope: workspace / property / listing / media / pricing
  BLK-001 scope: Booking.com production smoke test

  → Intersection: NONE
  → Decision: CONTINUE ✅ (Property Publish BAĞIMSIZ iş)

Görev: Booking Production Smoke
BLK-001 scope: Booking.com onboarding

  → Intersection: Booking.com production
  → Decision: STOP ⛔ (blocked by BLK-001)
```

---

## Scope

### In Scope

**Pipeline (uçtan uca):**

```
Workspace / Property
        ↓
YDL Context oku (authority: FULL / LIMITED)
        ↓
[authority = STOP?] → HALT
[authority = LIMITED + blocker intersect?] → HALT
        ↓
Workspace verisi completeness kontrolü
        ↓
Fotoğraf / medya kontrolü
        ↓
Fiyat + availability check
        ↓
Yasal / zorunlu alan kontrolü
        ↓
Listing content validation
        ↓
Publish readiness score hesapla
        ↓
Eksik işler → görev önerisi üret
        ↓
HUMAN APPROVAL (publish butonu)
        ↓
SYSTEM PUBLISHES
        ↓
Evidence → test → event
        ↓
YDL session-summary
        ↓
ydl:apply --confirm (controlled memory write)
```

**YDL Event üretimi:**
- `event_id = YdlEvent::generateEventId('PILOT-001', $commit, 'CONTINUE')`
- `type = YdlEvent::TYPE_SPRINT_STARTED` (yeni operasyonel başlangıç)
- `blockerChanges = []` (pilotta blocker yok ama yapı hazır)

### Out of Scope

- [ ] Tam otonom publish (insansız publish butonuna basmak pilotta YOK)
- [ ] Channel push (Booking / Airbnb — ayrı capability)
- [ ] Reservation conflict resolution
- [ ] Finance / komisyon hesaplama
- [ ] AI agent decision-making (agent kendi kararı değil, YDL deterministic authority kararı)

---

## Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | **Context:** Agent `ydl:context` ile başlıyor | CLI çıktısı gözlemlenir |
| 2 | **Authority:** Blocker intersect kararına uyuyor | BLK-001 kesişim testi |
| 3 | **Business:** Gerçek bir Property Publish operasyonu yürütülüyor | E2E test veya canlı kanıt |
| 4 | **Human Control:** Production publish insan onayı olmadan gerçekleşmiyor | Supervised model zorunlu |
| 5 | **Evidence:** Execution → event → YDL session-summary → controlled write zinciri kapanıyor | Event log + memory dosyası güncellenir |

---

## Manual Time Reduction KPI

**Baseline (öncesi):**

Bir danışmanın yeni bir villayı yayına hazır hale getirmesi:

| Adım | Manuel Süre |
|------|-------------|
| Eksik veri tespiti | 10 dk |
| Fotoğraf kontrolü | 5 dk |
| Fiyat kontrolü | 5 dk |
| Yayın onayı | 5 dk |
| **Toplam** | **25 dk** |

**Pilot sonrası hedef:**

| Adım | Otomatik | Manuel |
|------|---------|--------|
| Eksik veri tespiti | AI okur | 0 dk |
| Fotoğraf kontrolü | AI okur | 0 dk |
| Fiyat kontrolü | AI okur | 0 dk |
| Publish approval | İnsan onayı | 5 dk |
| **Toplam** | **0 dk** | **5 dk** |

**Hedef: ≥80% manual time reduction**

---

## YDL Phase 3 Pipeline (Bu Pilotta Kullanılan)

```
Oturum Başı
    │
    ├── php artisan ydl:context
    │       ↓
    │   YdlContextReader
    │       ↓
    │   authority = FULL (BLK-001 Property Publish ile kesişmiyor)
    │       ↓
    ├── Property Publish workflow başlat
    │
    ├── [Publish hazırlık işleri]
    │
    └── Oturum Sonu
            │
            ├── php artisan ydl:session-summary \
            │   --action CONTINUE \
            │   --target "PILOT-001: Property Publish" \
            │   --commit $(git rev-parse HEAD) \
            │   --dry-run
            │       ↓
            │   Event + patch plan üretilir
            │       ↓
            ├── git add . && git commit -m "feat(pilot): PILOT-001 property publish supervised autonomy"
            │       ↓
            └── php artisan ydl:apply --confirm
                    │ (G3c geçer: commit eşleşir)
                    ↓
                    Memory güncellenir
                    ↓
                    Event log'a yazılır
```

---

## Pilot Sequence

```
Step 1 ─── PILOT-001 Charter + SAAB onayı
  │
Step 2 ─── Pilot ortamı kurulumu
  │         (test property, workspace, sample data)
  │
Step 3 ─── YDL authority decision test
  │         (authority = FULL/LIMITED/STOP logic)
  │
Step 4 ─── Property Publish E2E workflow
  │
Step 5 ─── Human approval gate test
  │
Step 6 ─── YDL session-summary → ydl:apply
  │
Step 7 ─── Evidence paketi hazırla
  │
Step 8 ─── SAAB Certification
  │         KPI: ≥80% manual time reduction?
  │
Step 9 ─── PILOT-002 Rezervasyon'a geçiş kararı
```

---

## Non-Goals

- Tam otonom publish (AI'nın insan onayı olmadan basması)
- Çoklu-kanal aynı anda publish
- AI karar motoru (agent kendi kararı değil, YDL deterministic authority)

---

## Success Criteria

> PILOT-001 başarılıdır eğer:

1. `ydl:context` → authority FULL/LIMITED/STOP → agent doğru karar veriyor
2. Property Publish workflow E2E çalışıyor (test veya canlı)
3. Supervised model zorunlu: publish butonu insan onayı gerektiriyor
4. YDL session-summary → ydl:apply --confirm → memory güncelleniyor
5. **KPI:** Manual time reduction ≥80% (25 dk → ≤5 dk)

---

## Next: PILOT-002

**Rezervasyon Operasyonları** — Property Publish'te kanıtlandıktan sonra:
- Conflict resolution (double-booking prevention)
- Cancellation workflow
- Guest communication
- Finance / komisyon hesaplama
- Channel sync conflict handling
