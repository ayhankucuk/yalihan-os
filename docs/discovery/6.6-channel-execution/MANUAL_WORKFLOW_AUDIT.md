# Sprint 6.6 Discovery — Manual Workflow Audit

> **Tarih:** 2026-07-09
> **Sprint:** 6.6 — Channel Execution
> **Tip:** Discovery — Manuel İş Akışı Analizi
> **Amaç:** Bugün danışmanın eliyle yaptığı adımları haritalamak

---

## Temel Soru

> "Publishing Package üretildikten sonra bir emlak danışmanı bugün hangi Manuel adımları yapmak zorunda kalıyor?"

Bu sorunun cevabı Sprint 6.6'nın kapsamını belirler.

---

## Mevcut Manuel Adımlar (Bugün)

### Adım 1: Airbnb Manuel Kopyala-yapıştır

**Durum:** Tamamen manuel.

```
Danışman → Workspace'ten içerik kopyalar → Airbnb paneline yapıştırır.
```

**Kopyalanan içerik:**
- Başlık (AI üretilmiş olabilir)
- Açıklama (AI desk üretmiş olabilir)
- Fiyat (Laravel'den)
- Fotoğraf seçimi (opsiyonel)

**Yapışan bilgiler:**
- Hiçbir otomasyon yok

**Sorun:** Elle kopyala-yapıştır → insan hatası, tutarsızlık, versiyonlama yok.

---

## Adım 2: Sahibinden Manuel Veri Girişi

```
Danışman → Sahibinden'e giriş → İlan formu → Tüm alanları Manuel doldurur.
```

**Sahibinden form alanları:**
| Alan | Kaynak | Otomasyon |
|------|---------|-----------|
| Başlık | Ilan::baslik + AI Vision hints | Kısmi |
| Açıklama | Ilan::aciklama | Manuel düzenleme |
| Fiyat | Ilan::fiyat | ✅ |
| m² | Ilan::net_m2 | ✅ |
| Oda Sayısı | Ilan::oda_sayisi | ✅ |
| Bina Yaşı | Ilan::bina_yasi | ✅ |
| Fotoğraflar | Ilan::fotograflar | Manuel seçim |
| Konum | Manuel | Manuel |
| İmar Durumu | Manuel | Manuel |

**Sorun:** Form doldurma ~15-20 dk/ilan.

---

## Adım 3: Hepsiemlak Manuel Veri Girişi

```
Danışman → Hepsiemlak paneline giriş → Tüm alanları doldurur.
```

Benzer Sahibinden — ~15-20 dk manuel iş.

---

## Adım 4: Fiyat Tekrar Kontrol

```
Danışman → Tüm kanallarda fiyatları Manuel senkronize eder.
```

**Sorun:** Fiyat değişikliği → tüm kanallarda Manuel güncelleme.

---

## Adım 5: Durum Takibi (Bugün)

**Mevcut sistem:** Hiçbir kanal durumu takip edilmiyor.

Danışman mental olarak:
- "Airbnb'de yayında mı?"
- "Sahibinden'de ne zaman bitti?"
- "Hepsiemlak ID'si neydi?"

---

## Adım 6: Hata Durumunda Manuel Müdahale

**Bugün:** Kanal API'sı çökerse — danışman Manuel düzeltir.

---

## Manuel Adım Sayısı Bugün (Per Ilan)

| Kanal | Manuel Adım | Süre (dk) |
|-------|------------|-------------|
| Airbnb (yapıştır) | 1 | 3 |
| Sahibinden (form doldur) | 15+ alan | 15-20 |
| Hepsiemlak (form doldur) | 15+ alan | 15-20 |
| Fiyat senkronizasyonu | Kanal başı 1 | 3 |
| Durum takibi | Manuel mental takip | Sürekli |
| Hata müdahalesi | Değişken | Değişken |

**Toplam Manuel süre:** ~40-50 dk/ilan (ilaç/oda başı)

---

## Sprint 6.6 Hedef: Bu Adımları Otomatikleştirmek

### Otomasyon Potansiyeli

| Manuel Adım | Otomasyon Hedefi |
|------------|----------------|
| Kanal payload üretimi | ✅ Sprint 6.5 ile yapıldı |
| Manuel export | ✅ Sprint 6.6 hedefi |
| Execution history | ✅ Sprint 6.6 hedefi |
| Replay (tekrar çalıştırma) | ✅ Sprint 6.6 hedefi |
| Gerçek API çağrısı | Sprint 6.7+ |

---

## Çıkarım: Sprint 6.6 Ne Yapmalı?

### YAPMAMASI GEREKENLER
- Gerçek API çağrısı (riskli, ayrı sprint)
- Kanal entegrasyonunu tamamlama

### YAPMASI GEREKENLER
1. **Manual Export:** Kanal payload'ını panoya/dosyaya kopyala
2. **Execution Log:** Her denemenin kaydını tut
3. **Replay:** Başarısız kanalları yeniden çalıştır
4. **Dashboard Status:** Yayın durumunu görünür kıl

---

## Sonuç

Bugün danışman ~40-50 dk/ilan Manuel iş yapıyor. Sprint 6.6 bu işi 5 dk'a düşürmeli.
