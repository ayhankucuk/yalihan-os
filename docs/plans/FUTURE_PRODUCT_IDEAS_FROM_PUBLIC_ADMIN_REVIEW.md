# Future Product Ideas from Admin/IDE Review

> **Tarih:** 2026-07-03
> **Kaynak:** Admin UI review + IDE suggestions (Session 67)
> **Kural:** Sprint 4.2 kapsamına alınmayacak. Sadece planlamaya kaydedilecek.

---

## Sprint 4.3 — AI Workforce (Sonraki)

| # | Öneri | Açıklama | Öncelik |
|---|--------|-----------|---------|
| 1 | **AI fiyat anomali tespiti** | Komisyon/ilan fiyatlarında anomalileri tespit eden AI ajanı | HIGH |
| 2 | **Cortex müşteri-ilan eşleştirme** | Otomatik potansiyel alıcı-ilan eşleştirme (DemandMatchingEngine entegrasyonu) | HIGH |

---

## Sprint 5.x — Product UX

| # | Öneri | Açıklama | Öncelik |
|---|--------|-----------|---------|
| 1 | **Yazlık/bungalov filtreleri** | Yaz sezonu için özel filtreler (havuz, deniz mesafesi, kiralama uygunluk) | HIGH |
| 2 | **Canlı fiyat hesaplama** | Kredi/faiz hesaplama, vade simülasyonu | MEDIUM |
| 3 | **Teknik arsa filtreleri** | Ada/parsel no, kat karşılığı uygunluk, imar durumu filtreleri | MEDIUM |
| 4 | **Çift onay modalı** | Kritik işlemlerde (silme, onay, ödeme) ikinci onay dialogu | LOW |

---

## Sprint 6.x — Integration

| # | Öneri | Açıklama | Öncelik |
|---|--------|-----------|---------|
| 1 | **TKGM parsel harita katmanı** | Harita üzerinde TKGM parsel overlay, tapu sorgulama entegrasyonu | HIGH |
| 2 | **Kadastro/dask entegrasyonu** | Tapu Kadastro ve DASK sorgulamaları | MEDIUM |

---

## Later — Architecture

| # | Öneri | Açıklama | Öncelik |
|---|--------|-----------|---------|
| 1 | **Property Engine matris ekranı** | Ayrı mimari sprint gerektiren property engine matrix view | HIGH |
| 2 | **GraphQL API geçişi** | REST'ten GraphQL'e geçiş (uzun vadeli) | LOW |

---

## Yapılmayacaklar (Not Now)

Aşağıdaki öneriler değerli fikirler ancak mevcut sprint planına uymuyor:

- Sprint 4.2'de UI iyileştirmesi yapılmayacak (yeni özellik = new architecture risk)
- Frontend/public suggestion'ları şimdi implemente etmeyeceğiz
- Confirmation modal'ları şimdi değil (küçük ama test gerektiren UX değişikliği)

---

## Sprint 4.2 Tamamlanan Certification'lar

| Domain | Durum | Commit |
|--------|-------|--------|
| Ilan CRUD | ✅ Certified | Sprint 4.1 |
| Kisi CRUD | ✅ Certified | Sprint 4.2 Task 3 |
| Talep CRUD | ✅ Certified | Sprint 4.2 Task 4 |
| Komisyon CRUD + Tenant Isolation | ✅ Certified | `38bc6ff` |

**Sprint 4.2 Status: COMPLETE ✅**
