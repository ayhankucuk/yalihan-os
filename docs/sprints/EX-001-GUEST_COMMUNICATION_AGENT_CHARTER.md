# SAAB Execution Charter v1.0

> **Mission ID:** EX-001
> **Capability:** Guest Communication Agent
> **Status:** 🟢 AUTHORIZED
> **Date:** 2026-08-06

---

## Başarı Sorusu (Success Question)

> YALIHAN, misafir iletişiminin tekrar eden operasyonlarını güvenli, tenant-safe ve ölçülebilir şekilde otomatikleştirerek Business Automation Index'i (BAI) artırabiliyor mu?

---

## Business Hypothesis

> "We believe Guest Communication Agent will increase BAI by approximately 8% by automatically handling repetitive guest communication while preserving customer satisfaction and reducing manual response time."

---

## Capability Boundary

### ✅ Yapar

* Karşılama mesajı
* Check-in bilgisi
* Yol tarifi
* Wi-Fi bilgileri
* Havuz bakım bilgilendirmesi
* Sık sorulan sorular (FAQ)
* Plaj / restoran önerileri
* Check-out hatırlatması
* Değerlendirme (review) isteme mesajı
* Çok dilli mesaj oluşturma

### ❌ Yapmaz

* Fiyat pazarlığı
* Rezervasyon onayı
* Para iadesi kararı
* İptal kararı
* Ev sahibi adına hukuki taahhüt
* Manuel override gerektiren durumlar

---

## Execution KPI'ları

| KPI | Başlangıç | Hedef |
|-----|-----------|-------|
| Ortalama ilk yanıt süresi | Mevcut ölçüm | %80 iyileşme |
| Otomatik cevap oranı | Mevcut ölçüm | ≥ %80 |
| İnsan müdahalesi | Mevcut ölçüm | ≤ %20 |
| Misafir memnuniyeti | Mevcut puan | Koru veya artır |
| Manuel zaman tasarrufu | 0 | +5-10 saat/hafta |
| BAI katkısı | 0 | +8% hedef |

---

## Executive Exit Criteria

Guest Communication Agent ancak aşağıdaki koşullar birlikte sağlanırsa READY kabul edilir:

* ✅ Engineering Gate PASS
* ✅ Architecture Gate PASS
* ✅ Business Gate: BAI etkisi ölçüldü
* ✅ AI Gate: Otonomi hedefi karşılandı
* ✅ Customer Gate: Memnuniyet korunuyor veya artıyor
* ✅ Learning tamamlandı
* ✅ Executive Report üretildi
* ✅ CRS ≥ 90

---

## Expected Sprint Output

| Alan | Değer |
|------|-------|
| Capability | Guest Communication Agent |
| Status | READY |
| Expected BAI | +8% |
| Actual BAI | ? |
| Automation Rate | ? |
| Manual Hours Saved | ? |
| Customer Satisfaction | ? |
| CRS | ? |
| Decision | READY / CONDITIONAL / NOT READY |

---

## Referans

* `docs/EXECUTION_ERA_STANDARD.md` — Çalışma standardı
* `docs/templates/SPRINT_CLOSURE_TEMPLATE.md` — Kapanış şablonu
* `memory/PRODUCT_METRICS.md` — BAI ve CRS hesaplama
