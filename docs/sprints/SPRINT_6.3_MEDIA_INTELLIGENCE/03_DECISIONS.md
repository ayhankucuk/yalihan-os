# 03_DECISIONS.md — Sprint 6.3

## Mimari Kararlar

### 1. Kural Tabanlı Room Detection (AI Vision EN QUEUE)

**Karar:** RoomDetectionService kural tabanlı olarak başlatıldı.
**Gerekçe:** AI Vision entegrasyonu Sprint 6.4'te yapılacak. Sprint 6.3 core altyapıyı kurduktan sonra AI tabanlı tespit eklenebilir.
**Alternatif:** AI Vision ile başlayabilirdik ama MVP stable olmalı.

### 2. Quality Analysis Simulated

**Karar:** ImageQualityEngine simulated metrics kullanıyor (gerçek image processing yerine).
**Gerekçe:** Gerçek fotoğraf analizi CI ortamında zor. Sprint 6.4'te DeepSeek/GPT-4 Vision ile değiştirilecek.
**Geçici Çözüm:** `kalite_puani` simulation ile 50-95 arası random score üretiliyor.

### 3. API Contract Standardization

**Karar:** MediaController yeni API contract'a uyarlandı: `success | data | meta | error`.
**Gerekçe:** Diğer API controller'larla tutarlılık. `ApiContractTest.php` standartları uygulandı.

### 4. Database Migration Split

**Karar:** 3 ayrı migration dosyası oluşturuldu.
**Gerekçe:** Sprint 6.2'deki media migration'ları test ortamında tablo oluşturmuyordu (RefreshDatabase sorunu).
**Çözüm:** Ayrı migration + migration manifest'te yer alması sağlandı.

### 5. Thin Controller Standard

**Karar:** MediaController sadece HTTP katmanı. Tüm iş mantığı MediaIntelligenceEngine'de.
**SAB Uyumu:** Controller'da Eloquent write yok. Engine → Repository → DB write.

---

## Teknik Borç

| # | Borç | Öncelik | Sprint |
|---|------|---------|--------|
| 1 | AI Vision API entegrasyonu (GPT-4 Vision) | HIGH | 6.4 |
| 2 | Real fotoğraf pipeline test | MEDIUM | 6.4 |
| 3 | WorkspaceSummaryService media tam entegrasyonu | MEDIUM | Backlog |
| 4 | IlanFotografiFactory oluşturulmadı | LOW | Backlog |
