# Owner Portal Roadmap - Yalıhan v6.1.2

## Vizyon
Mülk sahiplerinin gayrimenkullerini pasif bir şekilde izlemek yerine, aktif bir şekilde kiralama takvimini yönettikleri, gelir-gider takibi yaptıkları ve AI destekli piyasa analizleriyle mülk değerlerini korudukları "Premium Asset Management" deneyimi sunmak.

---

## Faz 1: Temel Sağlamlaştırma (SAB & UX) — ✅ TAMAMLANDI (2026-05-17)
*Hedef: Mevcut hataların giderilmesi ve mimari uyumluluk.*

- [x] **SAB İzolasyonu:** `IlanRepository` ile veri erişiminin mühürlenmesi.
  - `applyOwnershipScope()` → owner (user_id) + danışman (danisman_id) + admin ayrımı
  - Cross-tenant erişim 404 ile concealment (varlık sızdırılmaz)
- [x] **CRUD Yetkilendirmesi:** `IlanPolicy` owner desteği ile güncellendi.
  - `isOwnerOfListing()` → `user_id` tabanlı tenant isolation (SAB Kural 1)
- [x] **Full CRUD:** `OwnerIlanController` create / store / edit / update / destroy
  - `StoreOwnerIlanRequest` + `UpdateOwnerIlanRequest` (yayin_durumu kilidi)
  - 7 CRUD route (owner prefix grubu)
  - `create.blade.php` + `edit.blade.php` Blade view'ları
- [x] **Test Coverage:** 13 Feature test — cross-tenant izolasyon, manipülasyon koruması
- [x] **UX:** show.blade.php → `@can('update')` korumalı Düzenle butonu

---

## Faz 2: Operasyonel Yönetim (Takvim & Finans) — 🟡 DEVAM EDİYOR

- [x] **Kiralama Takvimi:** OwnerCalendarController + ReservationRepository
  - Owner Block, Privacy Masking, Overlap Prevention
  - 10 test case ✅
- [ ] **Finansal Dashboard:** Kira gelirleri ve masraf kalemlerinin şeffaf takibi.
- [ ] **Belge Yönetimi:** Tapu, poliçe ve diğer mülk evraklarının güvenli depolanması.

---

## Faz 3: AI & Zeka Katmanı — 📋 PLANLI
*Hedef: Veri odaklı karar destek mekanizmaları.*

- [ ] **Market Valuation:** Mülkün güncel piyasa değerinin AI ile anlık hesaplanması.
- [ ] **Buyer/Guest Matching:** Potansiyel alıcı veya kiracı adaylarının eşleştirilmesi.
- [ ] **Smart Insights:** "Doluluğu artırmak için fiyata %5 indirim yapın" gibi akıllı öneriler.

---

## Faz 4: Genişletilmiş Ekosistem — 📋 PLANLI

- [ ] **Bakım Talepleri:** Teknik servis entegrasyonu.
- [ ] **Rapor Otomasyonu:** Aylık performans raporlarının PDF olarak iletilmesi.
- [ ] **Mobile App Push:** Kritik rezervasyon ve ödeme bildirimleri.

---

**Son Güncelleme:** 2026-05-17
**Durum:** Faz 1 ✅ tamamlandı. Faz 2 devam ediyor (Takvim ✅, Finans/Belgeler bekliyor).
