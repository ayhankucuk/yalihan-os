# YALIHAN OS — Sprint Closure Template

> Her sprint kapanışında doldurulmalı.
> Şablon: `docs/templates/SPRINT_CLOSURE_TEMPLATE.md`
>
> ⚠️ **Governance Freeze v1.0:** Bu şablon sabittir. Değişiklik yalnızca ADR / SAAB onayı ile yapılır.

---

## Sprint Executive Report

> Hermes tarafından otomatik üretilen tek sayfalık çıktı.
> Teknik ekip, ürün yöneticisi ve yönetim için ortak referans.

```
┌─────────────────────────────────────────────────────────┐
│  YALIHAN OS — Sprint Executive Report                   │
├─────────────────────────────────────────────────────────┤
│  Capability: __________________________________________  │
│  Date: __________________  Sprint: __________________   │
├─────────────────────────────────────────────────────────┤
│  CRS: _____  │  Status: [READY / CONDITIONAL / ...]   │
│  BAI Impact: Expected +__% / Actual +__% / ±__%      │
│  Manual Hours Saved: __ saat/hafta                     │
├─────────────────────────────────────────────────────────┤
│  Customer Impact:                                      │
│  ___________________________________________________   │
├─────────────────────────────────────────────────────────┤
│  Decision: [Production Approved / Review Required / ...]│
└─────────────────────────────────────────────────────────┘
```

### Business Hypothesis

Her Capability Charter'ın ilk satırı:

```
Business Hypothesis:
"We believe this capability will increase BAI from ___% to ___%
because it automates _________________________."
```

Sprint sonunda doğrulanır:
- Hipotez doğrulandı mı?
- Sapmanın nedeni ne?

---

### Sprint Charter — Başlangıç Soruları

Her sprint başında cevaplanmalı:

| Soru | Beklenen Cevap |
|------|----------------|
| Hangi manuel işi otomatikleştiriyoruz? | Net tanım |
| **Expected BAI Impact** | Sayısal hedef |
| Sprint sonunda nasıl doğrulayacağız? | Ölçülebilir kanıt |

> **Variance Kuralı:** Tahmin ile gerçek sonuç karşılaştırılır. Zamanla planlama hassasiyeti artar.

---

---

## Capability Summary Card

> Yönetim kurulu, yatırımcı veya teknik ekip için tek sayfalık özet.

```
┌─────────────────────────────────────────────────────────┐
│ CAPABILITY: __________________________________________  │
├─────────────┬───────────────────────────────────────────┤
│ Engineering │  Tests .............. ___/___ PASS        │
│             │  Regression .......... PASS/FAIL          │
│             │  Certification ....... PASS/FAIL          │
├─────────────┼───────────────────────────────────────────┤
│ Business    │  Manual Time Saved ... __ saat/hafta      │
│             │  Automation Rate ...... __%                │
│             │  Approval Rate ........ __%                │
├─────────────┼───────────────────────────────────────────┤
│ AI          │  Agent Success ........ __%                │
│             │  Exception Rate ....... __%                │
│             │  Autonomy ............ __%                │
├─────────────┼───────────────────────────────────────────┤
│ Customer    │  Avg Response Time ... __ sn              │
│             │  Satisfaction ........ __/5                │
│             │  Manual Escalation ... __%                │
├─────────────┼───────────────────────────────────────────┤
│ CRS         │  ____ / 100                                 │
│             │  [ ] READY  [ ] NOT READY                  │
└─────────────┴───────────────────────────────────────────┘
```

### Learning
- _______________________________

### Next Capability
- _______________________________

### BAI Etkisi

| Capability | Expected | Actual | Variance |
|------------|----------|--------|----------|
| | +__% | +__% | ±__% |

> Variance: Pozitif = beklenti aşıldı, Negatif = iyileştirme gerekli

---

## Capability Readiness Score (CRS)

| Katman | Ağırlık |
|--------|---------|
| Engineering | %35 |
| Business | %30 |
| AI | %20 |
| Customer | %15 |

**Hesaplama:** `(Engineering × 0.35) + (Business × 0.30) + (AI × 0.20) + (Customer × 0.15)`

### Governance Gate — Minimum Eşikler

CRS tek başına yeterli değil. Üretim için **üç koşulun tamamı** sağlanmalı:

| Koşul | Minimum | Durum |
|-------|---------|-------|
| CRS Toplam | >= 90 | |
| Engineering | >= 90 | |
| Customer | >= 70 | |

> Kural: Engineering veya Customer düşükse, yüksek CRS ile üretime çıkılamaz.

| Skor | Durum | Aksiyon |
|------|-------|---------|
| 90-100 + Gate ✓ | READY | Production'a çık |
| 70-89 | CONDITIONAL | Review gerekir |
| 50-69 | NOT READY | Geliştirme devam |
| <50 | BLOCKED | Blocker çözülmeli |

---

## Sprint Bilgileri

| Alan | Değer |
|------|--------|
| Sprint | |
| Tarih | |
| Commit | |
| Agent | |

---

## Mühendislik Değerlendirmesi

| Metrik | Sonuç |
|--------|--------|
| Test PASS | |
| Regression PASS | |
| Certification | |
| Architecture Compliance | |

---

## İş Değerlendirmesi

| KPI | Hedef | Gerçekleşen |
|-----|--------|---------------|
| Manuel iş saati kazanıldı | 10+ saat/hafta | |
| Automation Rate | %80+ | |
| Human Approval Rate | <%20 | |

---

## AI Değerlendirmesi

| KPI | Hedef | Gerçekleşen |
|-----|--------|---------------|
| Agent Success Rate | %90+ | |
| Exception Rate | <%10 | |
| Otonomi Oranı | %80+ | |

---

## Müşteri Değerlendirmesi (4. Katman)

> **Temel İlke:** Güvenli biçimde mümkün olan en yüksek otomasyon.

| Capability | Otomatik Hedef | Gerçekleşen | İnsan Onayı Hedef | Gerçekleşen | Exception Hedef | Gerçekleşen |
|------------|----------------|-------------|-------------------|-------------|-----------------|-------------|
| (capability adı) | | | | | | |

---

## Hangi Manuel İş Otomatikleşti?

> "Bu capability tamamlandığında hangi iş artık dijital çalışan tarafından yapılıyor?"

1. _____________
2. _____________
3. _____________

---

## Hermes Raporu (Gelecek Sprint İçin)

```json
{
  "date": "",
  "agents": {
    "reservation": { "tasks": 0, "success": "%" },
    "distribution": { "tasks": 0, "success": "%" },
    "finance": { "tasks": 0, "success": "%" }
  },
  "automation_rate": "%",
  "human_approval_needed": 0,
  "exceptions": 0
}
```

---

## Sonraki Sprint İçin Öncelik Kararı

> "Bu capability başlamadan once: Hangi manuel işi devralıyor?"

| Karar | Değerlendirme |
|-------|---------------|
| Otomatikleşen iş | |
| İnsan onayı gereken | |
| Exception politikası | |
| Rollback planı | |

---

## Learning & Next Improvement

> Her capability sonunda öğrenilenler kaydedilir. Zamanla değerli kurumsal bilgi birikimi oluşturur.

| Alan | İçerik |
|------|--------|
| En büyük öğrenim | Bu sprintte en önemli teknik/iş dersi |
| Beklenmeyen durum | Karşılaşılan sürpriz veya risk |
| Sonraki capability için öneri | Gelecek sprintte uygulanacak iyileştirme |

---

## Referanslar

- `memory/PRODUCT_METRICS.md` — KPI Framework
- `memory/CHIEF_AI_VISION.md` — Agent Organizasyonu
- `memory/SESSION_NOTES.md` — Sprint notları
