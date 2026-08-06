# YALIHAN OS — Product Metrics Framework

> **Tarih:** 2026-08-06
> **Commit:** f26fe157
> **Durum:** Platform Vizyon Kilometre Taşı

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

| İş Alanı | Otomati | İnsan Onayı | Exception |
|----------|---------|-------------|----------|
| Misafir mesajı | %100 | %0 | %0 |
| Rezervasyon kontrolü | %100 | %0 | %0 |
| Fiyat önerisi | %80 | %20 | %0 |
| Finans raporu | %90 | %10 | %0 |
| Hukuk analizi | %60 | %40 | %0 |
| Kanal senkronizasyonu | %70 | %30 | %0 |

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

## Önceliklendirme Kriteri

Yeni capability önerildiğinde:

```
1. "Bu hangi manuel işi devralıyor?" → Net cevap yoksa öncelik düşür
2. "Agent başarı oranı kaç?" → %80+ hedef
3. "İnsan onayı gerekecek mi?" → Gerekirse iş akışı tasarla
```

---

## Referanslar

- `memory/CHIEF_AI_VISION.md` — Agent vizyonu
- `docs/sprints/` — Sprint belgeleri
- `memory/SESSION_NOTES.md` — Operasyonel notlar
