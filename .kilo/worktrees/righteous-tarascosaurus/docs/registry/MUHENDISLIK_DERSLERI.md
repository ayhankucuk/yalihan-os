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

---

## 🏆 Altın Kural

> **İsimlendirme Tutarlılığı + Tek Gerçeklik Kaynağı (SSOT) + Küçük Sınıflar = Proje Hijyeninin %80'i.**

### Gelecek İçin Tavsiye:
Bir sonraki projede **SAB (System Authority Baseline)** protokolünü **İLK COMMIT** olarak ekle. Kuralları kod yazılmadan önce mühürle.

---
**Son Güncelleme:** 2026-05-14
**Durum:** Kabul Edildi ve Mühürlendi.
