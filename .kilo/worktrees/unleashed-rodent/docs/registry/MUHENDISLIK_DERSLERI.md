# 🛡️ Yalıhan AI OS: Mühendislik Dersleri & Anti-Pattern Analizi

Bu doküman, Yalıhan AI OS projesinin Phase 1-4 süreçlerinde karşılaşılan kritik mühendislik hatalarını, öğrenilen dersleri ve gelecekteki projeler için "altın kuralları" içerir.

---

## 🚩 Kritik Anti-Patternler ve Dersler

### 1. Naming Drift (İsimlendirme Kayması)
- **Sorun:** `status`, `type`, `active` gibi İngilizce alan adlarının Türkçe domain mantığına sızması.
- **Sonuç:** 175+ noktada isimlendirme ihlali ve yüksek refactor maliyeti.
- **Ders:** Kural baştan net olmalı: `durum`, `tip`, `aktif_mi`. Domain dili ile Framework dili arasındaki sınır (Context7) ilk commit'te çekilmeli.

### 2. Belge-Kod Senkronizasyon Kopması
- **Sorun:** `PROGRESS-TRACKER` %0 gösterirken kodun %100 tamamlanmış olması.
- **Sonuç:** Dokümantasyona olan güvenin kaybolması ve yönetimsel körlük.
- **Ders:** Belge güncellemesi commit'in bir parçası olmalı. Kod değişiyorsa, onu temsil eden belge de aynı PR'da güncellenmelidir.

### 3. God Object (YalihanCortex)
- **Sorun:** `YalihanCortex` sınıfının 6.471 satıra, 74 metoda ve 31 bağımlılığa ulaşması.
- **Sonuç:** Bakımı imkansız, her değişikliğin yan etki riski taşıdığı kırılgan bir yapı.
- **Ders:** Single Responsibility Principle (SRP) ödün vermeden uygulanmalı. Launch sonrası bu "God Object" mutlaka mikro servislere/bileşenlere parçalanmalıdır.

### 4. Paralel Sistem Birikimi (Shadow Authority)
- **Sorun:** `AIOrchestrator` ve `RoutedCortexExecutor` gibi aynı işi yapan iki farklı sistemin production'da aynı anda bulunması.
- **Sonuç:** "Hangi sistem güncel?" sorusu ve operasyonel karmaşa.
- **Ders:** Legacy sistemler sadece var olmamalı, bir "decommission" (devre dışı bırakma) takvimi ile yaşatılmalıdır.

### 5. Spekülatif Dokümantasyon
- **Sorun:** Henüz kurulmamış sistemlerin (OpenClaw vb.) veya aylar sonraki ADR'lerin 20KB'lık detaylı dokümanlarının yazılması.
- **Sonuç:** "Belge Mezarlığı" ve epistemik gürültü.
- **Ders:** Kod yoksa belge de yok. Dokümantasyon, çalışan veya üzerinde aktif çalışılan sistemi temsil etmelidir.

### 6. Credentials ve Model Simülasyonu
- **Sorun:** `deepseek-v4-flash` gibi sahte model adlarıyla geliştirme yapılması ve API key eksikliği.
- **Sonuç:** Hataların çok geç (production öncesi) fark edilmesi.
- **Ders:** Gerçek API anahtarları ve gerçek model isimleri ile geliştirme ortamı en baştan kurulmalı. Mock sistemleri gerçeği %100 simüle etmeli.

### 7. Altyapı Geç Konfigürasyonu (Queue/Cache)
- **Sorun:** `sync` driver ile başlayıp sonradan Redis'e geçilmesi.
- **Sonuç:** Async davranışa bağımlı testlerin güvenilirliğini yitirmesi ve geçiş sancıları.
- **Ders:** Production altyapısı (Redis, Queue vb.) geliştirme ortamında ilk günden itibaren aktif olmalıdır.

### 8. Arşivsiz Büyüme
- **Sorun:** 130+ governance belgesinin kontrolsüz birikmesi.
- **Sonuç:** Bilgi kirliliği ve arananın bulunamaması.
- **Ders:** Her oturumda "bu dosya hala gerekli mi?" sorusu sorulmalı. Güncelliğini yitiren her şey anında `archive/` altına taşınmalıdır.

### 9. Çift Namespace Kaosuna İzin Verme (Sprint 2 — #28, #60)
- **Sorun:** `app/Domains/` ve `app/Domain/` iki ayrı dizin olarak büyüdü; `ModuleServiceProvider` iki farklı namespace'de aynı isimle yaşadı.
- **Sonuç:** Laravel container hangisini resolve edeceğini bilmiyor; runtime'da belirsiz davranış.
- **Ders:** Dizin/namespace kararı ilk gün verilmeli ve ADR ile mühürlenmeli. İki paralel namespace'e asla izin verme; keşfedildiği anda hemen birleştir (commit: `6909772`, `6125ca3`).

### 10. Split-Brain Veri Yazımı (Sprint 3 — T-UPS-V2)
- **Sorun:** `IlanCrudService::handleVerticalDetails()` aynı veriyi hem `ilanlar` ana tablosuna hem `ilan_turizm_details` yardımcı tablosuna yazıyordu (double-write).
- **Sonuç:** İki bağımsız gerçek kaynağı — hangisi güncel belirsiz. `CortexROIEngine` yanlış kaynaktan okuma riski.
- **Ders:** Tek write authority: Her verinin tek sahibi olmalı. Yardımcı tablolar salt mirror olmalı, asla bağımsız write hedefi olmamalı. İlk tasarımda SSOT belirle.

### 11. Pivot FK Uyumsuzluğu Erken Yakalanmalı (Sprint 3 — T-FAV-01)
- **Sorun:** `ilan_favorileri` tablosu `user_id` içerirken pivot metodları `kisi_id` ile join yapıyordu.
- **Sonuç:** Favori sorguları yanlış sonuç dönebilir — production'da fark edilmesi zor.
- **Ders:** Pivot tablo tanımlarken her iki tarafın FK'ını model testinde doğrula. `withPivot()` + integration test şart.

### 12. Dokümantasyon Mezarlığını Periyodik Temizle (Oturum 59)
- **Sorun:** 432 MD dosyası birikti — 218'i arşiv, 40+ tanesi docs/ kökünde dağınık, kırık referanslar.
- **Sonuç:** Hangi belgenin güncel olduğu belirsiz, yeni geliştirici onboarding'i zorlaşıyor.
- **Ders:** Her sprint sonunda MD audit yapılmalı. `docs/` kökünde max 15 SSOT dosyası kuralı. Stale dosyalar anında silinmeli ya da arşivlenmeli. (Oturum 59: 432 → 195 dosya)

### 13. AST Tabanlı Context7 İhlali Erken Yakalanır
- **Sorun:** `Kisi.php`'de `$this->email` referansları `kisiler.eposta` kanonik adını kullanmıyordu.
- **Sonuç:** Bekçi RULE-N1 ihlali — model accessor'lar yanlış alana erişiyordu.
- **Ders:** `get_canonical` MCP aracını her yeni alan adında çağır. Bekçi `validate_file` commit öncesi çalıştır. `email` → `eposta`, `status` → `yayin_durumu` (commit: `6923cf73`).

---

## 🏆 Altın Kural

> **İsimlendirme Tutarlılığı + Tek Gerçeklik Kaynağı (SSOT) + Küçük Sınıflar = Proje Hijyeninin %80'i.**

### Gelecek İçin Tavsiye:
Bir sonraki projede **SAB (System Authority Baseline)** protokolünü **İLK COMMIT** olarak ekle. Kuralları kod yazılmadan önce mühürle.

---
**Son Güncelleme:** 2026-06-16 (Oturum 59 — Sprint 2/3 dersleri eklendi)
**Durum:** Kabul Edildi ve Mühürlendi.
