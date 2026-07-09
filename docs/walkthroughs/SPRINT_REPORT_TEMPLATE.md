# Sprint Rapor Template

> Her sprint raporuna şu iki bölüm zorunludur: **Teknik Sonuçlar** + **İş Sonuçları**

---

## Sprint Raporu: [Sprint Adı]

**Tarih:** YYYY-MM-DD
**Sprint:** X.X
**Era:** ERA III — Product Validation
**Durum:** 🟡/🟢/🔴

---

## A) TEKNİK SONUÇLAR

### 1. Test Durumu

| Test Suite | Geçen | Başarısız | Kapsam |
|-----------|--------|-----------|--------|
| [Test name] | X | Y | % |
| **TOPLAM** | **X** | **Y** | **%** |

### 2. Capability Durumu

| Capability | Sprint | Status |
|-----------|--------|--------|
| Workspace Runtime | 6.1 | ✅/⏳ |
| Location Intelligence | 6.2 | ✅/⏳ |
| Media Intelligence | 6.3 | ✅/⏳ |
| AI Vision Intelligence | 6.4 | ✅/⏳ |
| Publishing Intelligence | 6.5 | ✅/⏳ |
| Execution Layer | 6.6 | ⏳ |

### 3. Mimari Uyumluluk

| Kural | Sonuç |
|-------|--------|
| SAB Integrity | ✅/❌ |
| Tenant Isolation | ✅/❌ |
| Queue Safety | ✅/❌ |
| Replay Safety | ✅/❌ |

### 4. Kod Kalitesi

| Metrik | Değer |
|--------|--------|
| Eklenen test | +X |
| Kod satırı (net) | +X / -X |
| Yeni dosya | X |
| Violation (yeni) | 0 |

---

## B) İŞ SONUÇLARI

### 1. Hazırlanan Gerçek İlan

> Bu sprintte kaç gerçek ilan sisteme girildi ve pipeline'dan geçti?

| İlan | Portföy | Adımlar | Durum |
|------|---------|---------|--------|
| [Villa Betül] | [Portföy] | Workspace→Vision→Publish | ✅/❌ |
| [Villa Ela] | [Portföy] | Workspace→Vision→Publish | ✅/❌ |
| **TOPLAM** | | | **X/X** |

### 2. Otomatik Doldurulan Alanlar

> AI tarafından otomatik doldurulan alanlar:

| Alan | Önce (Manuel) | Sonra (Otomatik) | Fark |
|------|-----------------|---------------------|-------|
| Başlık | ~10 dk | ~30 sn | -95% |
| Açıklama | ~15 dk | ~2 dk | -87% |
| Oda tespiti | ~10 dk | ~0 sn | -100% |
| Amenity'ler | ~5 dk | ~0 sn | -100% |

### 3. Kazandırılan Süre

> Bu sprintte toplam kaç dakika manuel iş otomatik hale geldi?

| Operasyon | Eski (Manuel) | Yeni (Otomatik) | Kazanılan |
|-----------|---------------|------------------|----------|
| İlan oluşturma | ~35 dk | ~12 dk | -23 dk |
| AI Vision analizi | ~0 | ~5 dk | +5 dk |
| Payload hazırlığı | ~15 dk | ~3 dk | -12 dk |
| **TOPLAM** | **~50 dk** | **~20 dk** | **-30 dk** |

### 4. Business Automation Index

> Manuel adım sayısı → Otomatik adım sayısı

$$\text{BAI} = \frac{\text{Otomatik Adımlar}}{\text{Toplam Adımlar}} \times 100$$

| Sprint | Manuel | Otomatik | Toplam | **BAI** |
|---------|---------|---------|--------|---------|
| 6.1 | X | X | X | **X%** |
| 6.2 | X | X | X | **X%** |
| 6.3 | X | X | X | **X%** |
| 6.4 | X | X | X | **X%** |
| 6.5 | X | X | X | **X%** |
| 6.6 | X | X | X | **X%** |

### 5. Manuel Müdahale Noktaları

> AI pipeline'ında insanın manuel müdahale ettiği adımlar:

| # | Adım | Sebep | Öncelik |
|---|------|-------|----------|
| 1 | [Adım] | [Neden?] | 🔴/🟠 |
| 2 | [Adım] | [Neden?] | 🔴/🟠 |

---

## C) ERA DURUMU

| Gate | Kriter | Durum |
|------|---------|--------|
| G1 | P0 Blocker | ✅/⏳ |
| G2 | RC1 Saha Testi | ✅/⏳ |
| G3 | Gerçek İlan Kanıtı | ✅/⏳ |
| G4 | Süre Ölçümü | ✅/⏳ |
| G5 | Sprint 6.6 Tamamlanma | ✅/⏳ |

---

## D) SONRAKİ SPRİNT İÇİN GERİ BİLDİRİM

### Takılma Noktaları (Bu Sprintten)

| # | Sorun | Çözüm Önerisi |
|---|-------|---------------|
| 1 | | |

### Backlog Öncelikleri (Sonraki Sprint İçin)

1. [ ] [Öncelik 1]
2. [ ] [Öncelik 2]
3. [ ] [Öncelik 3]

---

## Özet

> Bu sprintte teknik olarak [X] test eklendi, [X] capability ilerledi.
> İş olarak [X] gerçek ilan hazırlandı, [X] dakika manuel iş otomatik hale geldi.
> Business Automation Index: **X%**

**Bu sprintte danışmanın kaç dakikalık manuel işi otomatik hale geldi?**
→ **[X] dakika**
