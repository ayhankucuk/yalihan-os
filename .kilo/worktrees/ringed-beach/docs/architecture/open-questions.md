# Open Questions

> STATUS: Doğrulanmamış açık sorular — spekülatif iddialar buraya yazılır, code'a değil
> Kurallar: Sorular ancak runtime doğrulama sonrasında kapatılır

---

## Confirmed Open Questions

### Q1: FeatureTemplateResolver fallback kontratı test edilmiş mi?
**Bağlam:** Resolver 4 aşamalı fallback zinciri kullanıyor (exact → parent → listing_type → global). NULL scope fallback'i test kapsamında mı?
**Risk:** NULL scope unutulursa Wizard 0 feature döndürür (bilinen en yaygın bug)
**Doğrulama:** `tests/` altında FeatureTemplateResolver fallback test'i arayın
**Status:** ⏳ OPEN

---

### Q2: AI Monitor kartları runtime-probe mı DB-aggregation mı?
**Bağlam:** AI Monitor sayfasındaki kartlar gerçek zamanlı API probe mu yapıyor, yoksa `ai_logs` tablosundan aggregation mı?
**Risk:** DB-aggregation ise → provider dead ama dashboard healthy gösterebilir
**Doğrulama:** `AiMonitorController` kaynak kodunu inceleyinherblocktype
**Status:** ⏳ OPEN

---

### Q3: Governance watcher durduğunda dashboard warning üretmeli mi?
**Bağlam:** `sab-watch.sh` process olarak çalışıyor. Durduğunda governance dashboard hala "healthy" gösteriyor olabilir.
**Risk:** Operatör watcher'ın durduğunu fark etmez
**Doğrulama:** Governance dashboard'da watcher process kontrolü var mı?
**Status:** ⏳ OPEN

---

### Q4: AI activity için production truth hangi sayfa?
**Bağlam:** AI Monitor, AI Governance, AI Telemetry ve AI İstatistikler arasında hangi sayfa AI aktivitesinin "gerçek" kaynağı?
**Risk:** Farklı sayfalar farklı metrikler gösterir → karışıklık
**Doğrulama:** Her sayfanın veri kaynağı karşılaştırılmalı
**Status:** ⏳ OPEN

---

### Q5: Sidebar'da olmayan sayfaların geleceği ne?
**Bağlam:** 16+ sayfa aktif route olarak mevcut ama sidebar'da görünmüyor (Yazlık Kiralama, Blog, Danışman AI, Market Intelligence vb.)
**Risk:** Bu sayfalar keşfedilmezse kullanılmaz; deprecated mi, sidebar'a mı eklenmeli?
**Doğrulama:** Product owner kararı gerekli
**Status:** ⏳ OPEN

---

### Q6: Çift adres yönetimi sistemi neden var?
**Bağlam:** Hem `AddressManagementController` (`/admin/address-management`) hem `AdresYonetimiController` (`/admin/adres-yonetimi`) mevcut. İkisi de aktif.
**Risk:** İki farklı adres yönetim sayfası → veri tutarsızlığı
**Doğrulama:** Hangisi canonical, hangisi legacy?
**Status:** ⏳ OPEN

---

### Q7: CRM Dashboard V1 vs V2
**Bağlam:** Hem `CRMController` (dashboard) hem `CRMDashboardController` (dashboard-v2) mevcut. İkisi de aktif route.
**Risk:** İki farklı dashboard → kullanıcı karışıklığı
**Doğrulama:** V1 deprecated mı?
**Status:** ⏳ OPEN

---

### Q8: Finance modülü ikili yapı
**Bağlam:** Hem `FinansalIslemController` (`/admin/finans/islemler`) hem `FinanceController` (`/admin/finance/*`) mevcut.
**Risk:** İki farklı finans modülü → veri bütünlüğü
**Doğrulama:** Hangisi canonical?
**Status:** ⏳ OPEN

---

## Rules

1. ❌ **Spekülatif cevap yasak** — "muhtemelen şöyle" yazılmaz
2. ✅ **Runtime doğrulama zorunlu** — soru ancak kod/test ile kapatılır
3. ✅ **Kaynakla birlikte kapatılır** — hangi dosya/test/commit doğruladı yazılır
4. ✅ **Tarih eklenir** — kapatma tarihi not edilir

---

## Closed Questions

_Henüz kapatılmış soru yok. Runtime doğrulama sonrasında buraya taşınır._
