# YALIHAN OS — Product Metrics Framework

> **Tarih:** 2026-08-06
> **Governance Version:** 1.0
> **Status:** BASELINE ESTABLISHED 🟢
>
> ⚠️ **Governance Freeze v1.0:** Bu çerçevedeki metrikler ve kurallar sabittir. Değişiklik yalnızca ADR / SAAB onayı ile yapılır.

---

## YALIHAN Company Operating System — Resmi Dönüşüm

```
Yazılım Projesi
        ↓
Platform
        ↓
Company Operating System  ← BASELINE (2026-08-06)
        ↓
Ölçülebilir Dijital Organizasyon
```

---

## YALIHAN Definition of Done — 7 Kapı

Her capability, aşağıdaki 7 kapının tamamını geçmeden tamamlanmış sayılmaz:

| Gate | Soru | Çıktı |
|------|------|-------|
| 1. Engineering | Testler ve kalite kapıları geçti mi? | 52/52 PASS |
| 2. Architecture | SAAB ilkelerine uyuyor mu? | SAAB Certification |
| 3. Business | Beklenen BAI etkisi gerçekleşti mi? | Variance ±X% |
| 4. AI | Agent doğru seviyede otonom çalışıyor mu? | CRS AI ≥ 70 |
| 5. Customer | Kullanıcı için ölçülebilir fayda oluştu mu? | Customer ≥ 70 |
| 6. Learning | Öğrenilen dersler kaydedildi mi? | Sprint Closure |
| 7. Executive | CRS eşiği geçti mi ve Production onayı aldı mı? | Executive Report |

---

## 2027 North Star Goal

> "YALIHAN'ın günlük operasyonlarının %80'i agent'lar tarafından güvenli şekilde tamamlanıyor."

**BAI bu hedefi ölçen ana metriktir.**

---

## Resmi Misyon

> "Her capability, bir emlak danışmanının tekrar eden bir işini devralan dijital çalışandır."

---

## Performans Metrikleri

### Automation Metrics

| KPI | Açıklama | Hedef |
|-----|-----------|--------|
| **Automation Rate** | Tam otomatik tamamlanan görev yüzdesi | %80+ |
| **Human Approval Rate** | İnsan onayı gerektiren görev oranı | <%20 |
| **Manual Time Saved** | Kazanılan insan saati/hafta | 10+ saat |
| **Agent Success Rate** | İlk denemede başarı oranı | %90+ |
| **Exception Rate** | İnsana devredilen istisna oranı | <%10 |

### Agent Otonomi Oranı

| İş Alanı | Otomatik | İnsan Onayı | Exception |
|----------|----------|-------------|----------|
| Misafir mesajı | %100 | %0 | %0 |
| Rezervasyon kontrolü | %100 | %0 | %0 |
| Fiyat önerisi | %80 | %20 | %0 |
| Finans raporu | %90 | %10 | %0 |
| Hukuk analizi | %60 | %40 | %0 |
| Kanal senkronizasyonu | %70 | %30 | %0 |

### Customer 4. Katman — Capability Bazlı Hedefler

> **Temel İlke:** Hedef %100 otomasyon değil; güvenli biçimde mümkün olan en yüksek otomasyondur.

| Capability | Otomatik | İnsan Onayı | Exception |
|------------|----------|-------------|----------|
| Müsaitlik sorgusu | %100 | %0 | <%1 |
| Misafir bilgi mesajı | %95 | %3 | <%2 |
| Fiyat önerisi | %80 | %20 | <%5 |
| Rezervasyon iptali | %30 | %70 | <%3 |
| Hukuki değerlendirme | %60 | %40 | <%5 |
| Malik ödemesi | %70 | %30 | <%1 |

---

## Organizasyonel Yapı

```
Hermes (Genel Müdür)
    │
    ├── 📅 Reservation Agent (Operations Director)
    │       └── Reservation Core Capability
    ├── 🌐 Distribution Agent (Sales Director)
    │       └── Channel Manager Capability
    ├── 💰 Finance Agent (Finance Director)
    │       └── Finance Capability
    ├── 👥 CRM Agent (Customer Director)
    │       └── CRM Capability
    ├── 📈 Market Agent (Intelligence Director)
    │       └── Market Intelligence Capability
    └── ⚖️ Legal Agent (Compliance Director)
            └── Legal Capability
```

### Roller Tanımı

| Rol | Sorumluluk |
|-----|-------------|
| **Hermes** | Görev dağıtır, sonuç toplar, çakışmaları çözer, rapor verir |
| **Agent** | İş alanının sahibi, karar verir, Hermes'e raporlar |
| **Capability** | Teknik yetenek, işi yapar, Agent'a hizmet eder |

---

## Sprint Değerlendirme Soruları

Her sprint sonunda:

1. **Hangi iş otomatikleşti?**
2. **Agent başarı oranı ne?**
3. **İnsan onayı gerektiren görev?**
4. **Manuel iş saati kazanıldı?**

---

## Capability Readiness Score (CRS)

Platformun her capability için tek bir üretim hazırlık skoru.

| Katman | Ağırlık | Açıklama |
|--------|---------|----------|
| Engineering | %35 | Test, regression, certification |
| Business | %30 | Manuel zaman, otomasyon oranı |
| AI | %20 | Agent başarı, exception, otonomi |
| Customer | %15 | Yanıt süresi, memnuniyet, escalation |

**Hesaplama:** `(Eng × 0.35) + (Bus × 0.30) + (AI × 0.20) + (Cust × 0.15) = CRS`

| Skor | Durum | Aksiyon |
|------|-------|---------|
| 90-100 | READY | Production'a çık |
| 70-89 | CONDITIONAL | Review gerekir |
| 50-69 | NOT READY | Geliştirme devam |
| <50 | BLOCKED | Blocker çözülmeli |

**Governance Gate — Minimum Eşikler:**

CRS tek başına yeterli değil. Kritik katmanlar için minimum eşikler zorunlu:

```
CRS >= 90
VE
Engineering >= 90
VE
Customer >= 70
```

> **Kural:** Mükemmel Business skoru kötü test kalitesini telafi etmez.
> Mükemmel genel skor, başarısız Customer deneyimini gizlemez.

**Örnek:**
```
Finance Agent
CRS: 96/100 ✅
Engineering: 92 ✅
Customer: 71 ✅
→ READY ✅ Production'a çıkabilir
```

---

## Business Automation Index (BAI)

**YALIHAN OS North Star Metric**

Şirket seviyesinde tek metrik — platformun otonomi gelişimini ölçer.

```
BAI = Tam otomatik tamamlanan operasyon sayısı
      ─────────────────────────────────────────
                   Toplam operasyon sayısı
```

| BAI | Anlamı |
|-----|--------|
| %0-25 | Manuel süreç |
| %26-50 | Destekli otomasyon |
| %51-75 | Yarı-otonom |
| %76-90 | Yüksek otonomi |
| %91-100 | Tam otonomi |

**Hedef:** Her sprint sonunda BAI artışı.

**Örnek:**
```
Günlük operasyon: 500
Tam otomatik: 340

BAI = %68 → Yarı-otonom
```

> "YALIHAN bugün dünden daha otonom çalışıyor mu?" sorusunun tek cevabı.

### BAI Tahmin Matrisi — Backlog Önceliklendirme

Her yeni capability için tahmini BAI etkisi, backlog sıralamasında kullanılır.

| Capability | Expected BAI | Stratejik Öncelik |
|------------|-------------|------------------|
| Guest Communication Agent | +8% | 🥇 1 |
| Finance Agent | +6% | 🥈 2 |
| Channel Manager Wave 2 | Orta | 🥉 3 |
| Market Intelligence | +4% | 4 |
| Legal Agent | +2% | 5 |

> **Kural:** Backlog yalnızca teknik zorluğa göre değil, BAI etkisine göre de sıralanır.

### BAI Yorumlama

| Sonuç | Yorum |
|-------|-------|
| Capability mükemmel ama BAI düşük | Altyapı capability — sonraki otomasyonların temeli |
| BAI yüksek ama Engineering düşük | Production'a çıkma — test kalitesi artırılmalı |
| Her ikisi de yüksek | Tam başarı — üretime hazır |

---

## Sprint Learning Framework

Her sprint kapanışında doldurulur:

| Alan | İçerik |
|------|--------|
| En büyük öğrenim | Teknik/iş dersi |
| Beklenmeyen durum | Risk veya sürpriz |
| Sonraki öneri | Uygulanacak iyileştirme |

---

## Önceliklendirme Kriteri

Yeni capability önerildiğinde:

```
1. "Bu hangi manuel işi devralıyor?" → Net cevap yoksa öncelik düşür
2. "Agent başarı oranı kaç?" → %80+ hedef
3. "İnsan onayı gerekecek mi?" → Gerekirse iş akışı tasarla
```

---

## Governance Dashboard

Her sprint sonunda otomatik üretilen yönetim özeti.

### Sprint Dashboard Template

| Metric | Current | Trend | Target |
|--------|---------|-------|--------|
| CRS Ortalama | | ↑↓→ | 90+ |
| BAI | % | ↑ | 80%+ |
| Active Agents | | ↑ | - |
| Automation Rate | % | ↑ | 80%+ |
| Manual Hours Saved | saat/hafta | ↑ | 10+ |
| Capability Ready | / | → | - |

### Tablo Okunması

| Trend | Anlamı |
|-------|--------|
| ↑ | Hedefe doğru iyileşme |
| → | Stabil |
| ↓ | Dikkat gerektirir |

---

## Platform Evrimi — Milestone Timeline

| Milestone | Açıklama | Durum |
|-----------|----------|-------|
| Architecture Milestone | SAAB ve DDD temeli | ✅ Tamamlandı |
| Capability Milestone | Reservation Platform tamamlanması | 🔄 Devam ediyor |
| **Governance Milestone** | KPI, CRS, Sprint Closure Framework | ✅ Tamamlandı |
| Automation Milestone | İlk tam otonom agent | ⏳ Beklemede |
| Autonomous Company Milestone | Agentların birlikte operasyon yürütmesi | ⏳ Beklemede |

---

## Referanslar

- `docs/MILESTONES.md` — Platform Milestone kayıtları
- `memory/CHIEF_AI_VISION.md` — Agent vizyonu
- `docs/sprints/` — Sprint belgeleri
- `memory/SESSION_NOTES.md` — Operasyonel notlar
- `docs/templates/SPRINT_CLOSURE_TEMPLATE.md` — Capability Summary Card şablonu
