# YALIHAN OS — BULGULAR VE YAPILACAKLAR

**Oluşturuldu:** 2026-10-07
**ATLAS Role:** Technical Architecture Master

---

## KRITIK BULGULAR (P0)

### 1. Finans Tenant Isolation Eksik
**Domain:** Finance
**Bulgu:** FinansalIslem model ve ledger_* tablolarında tenant_id kolonu yok
**Risk:** Cross-tenant finansal veri erişimi
**Kanıt:** 
```
grep -l "BelongsToTenant" FinansalIslem.php → BOŞ
```
**Önerilen Çözüm:** tenant_id ekle + migration + model güncelle
**Durum:** BEKLEMEDE
**Kaynak:** DATABASE_ARCHITECTURE.md

---

### 2. Rezervasyon → Finans Otomatik Bağlantı Yok
**Domain:** Finance + Reservation
**Bulgu:** Rezervasyon oluşturulduğunda otomatik FinansalIslem oluşturulmuyor
**Risk:** Finansal takip eksikliği
**Kanıt:** ReservationCreatedEvent → FinansalIslem bağlantısı yok
**Önerilen Çözüm:** Event listener ekle veya workflow tanımla
**Durum:** BEKLEMEDE
**Kaynak:** BUSINESS_WORKFLOWS.md

---

## YÜKSEK ÖNCELİKLİ BULGULAR (P1)

### 3. yazlik_rezervasyonlar Tenant Isolation
**Domain:** Reservation
**Bulgu:** Yazlık rezervasyonlar için ayrı tablo var, tenant_id kontrolü gerekli
**Risk:** Tenant verisi karışması
**Kanıt:** Tablo mevcut ama tenant_id kontrolü doğrulanmadı
**Önerilen Çözüm:** Migration audit + tenant_id ekle veya deprecated işaretle
**Durum:** BEKLEMEDE

---

### 4. property_reservations Legacy Binding
**Domain:** Reservation
**Bulgu:** Rezervasyon → Ilan bağlantısı hem property_id hem ilan_id üzerinden yapılıyor
**Risk:** Veri tutarsızlığı
**Kanıt:** 
```
property_reservations:
  - property_id FK (primary)
  - ilan_id FK (legacy)
```
**Önerilen Çözüm:** Canonical path belirle (property_id), ilan_id kaldır
**Durum:** BEKLEMEDE
**Kaynak:** DATABASE_ARCHITECTURE.md

---

### 5. Kisi State Transitions Otomasyon Eksik
**Domain:** CRM
**Bulgu:** Kisi durumu (SICAK→TAKIPTE vb.) otomatik güncellenmiyor
**Risk:** Müşteri takibi eksikliği
**Kanıt:** State değişikliği için manuel tetikleme gerekli
**Önerilen Çözüm:** Automation rules veya event-based triggers
**Durum:** BEKLEMEDE

---

## ORTA ÖNCELİKLİ BULGULAR (P2)

### 6. İsim Tutarsızlıkları (Hybrid Yaklaşım Belirlendi)
**Domain:** Multiple
**Bulgu:** Bazı tablolar Türkçe, bazıları İngilizce
**Örnekler:**
- `ilanlar` (Türkçe) vs `properties` (İngilizce)
- `kisiler` (Türkçe) vs `leads` (İngilizce)
- `talepler` (Türkçe) vs `demands` (İngilizce)
**Çözüm:** Hybrid yaklaşım uygulanacak (karışık isimlendirme kabul edildi)
**Durum:** GÖRÜŞÜLDÜ

---

### 7. Notification Tenant Scope
**Domain:** Notifications
**Bulgu:** NotificationDispatcher tenant-scope kontrolü doğrulanmadı
**Risk:** Cross-tenant bildirim gönderimi
**Kanıt:** Dispatcher kodu mevcut ama tenant_id filter yok
**Önerilen Çözüm:** Tenant validation ekle
**Durum:** BEKLEMEDE

---

### 8. AI Cost Tracking Eksik Detay
**Domain:** AI
**Bulgu:** AiCostService var ama per-feature breakdown yok
**Risk:** Maliyet kontrolü yetersiz
**Önerilen Çözüm:** Feature-based cost allocation
**Durum:** BEKLEMEDE

---

## DÜŞÜK ÖNCELİKLİ BULGULAR (P3)

### 9. Yazlık Modülü Legacy
**Domain:** Property
**Bulgu:** yazlik_details, yazlik_fiyatlandirma, yazlik_rezervasyonlar tabloları ayrı var
**Risk:** Kod tekrarı veya unused code
**Önerilen Çözüm:** property_reservations ile birleştirme veya deprecated
**Durum:** BEKLEMEDE

---

### 10. Test Coverage Gap - Finance
**Domain:** Finance
**Bulgu:** FinansalIslem için tenant isolation testi yok
**Risk:** Regression koruması eksik
**Önerilen Çözüm:** Tenant isolation testi ekle
**Durum:** BEKLEMEDE

---

## KAPANAN BULGULAR

### CDA_REZ_01-02 (2026-10-07)
- IlanReservation canonical boundary fix
- Reservation schema mismatch remediation
- Tenant isolation tests
- **Durum:** CLOSED ✅

### BEKCI_GATE (2026-10-07)
- Sentinel health threshold implementation
- 70% exit code threshold
- **Durum:** CLOSED ✅

---

## PRIORİTE GÖRE ÖZET

| # | Bulgu | Öncelik | Durum |
|---|-------|---------|-------|
| 1 | Finans tenant isolation | P0 | BEKLEMEDE |
| 2 | Rezervasyon→Finans auto | P0 | BEKLEMEDE |
| 3 | yazlik_rezervasyonlar | P1 | BEKLEMEDE |
| 4 | property_reservations binding | P1 | BEKLEMEDE |
| 5 | Kisi state automation | P1 | BEKLEMEDE |
| 6 | İsim tutarsızlıkları | P2 | GÖRÜŞÜLDÜ |
| 7 | Notification tenant scope | P2 | BEKLEMEDE |
| 8 | AI cost tracking | P2 | BEKLEMEDE |
| 9 | Yazlık legacy | P3 | BEKLEMEDE |
| 10 | Finance test coverage | P3 | BEKLEMEDE |

---

## SONRAKI ADIMLAR

1. P0 bulgular için Human Gate (Ayhan kararı gerekli)
2. P1 bulgular için bounded remediation planı
3. P2 bulgular için backlog'a ekle
