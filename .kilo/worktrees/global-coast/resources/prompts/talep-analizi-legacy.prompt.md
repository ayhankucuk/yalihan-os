# Talep Eşleştirme - Context7 AI Prompt

**Version:** 1.1.0
**Category:** eslesme
**Type:** genel
**Priority:** high
**Last Updated:** 2026-01-08

---

## 🎯 **Görev**

Müşteri taleplerini veritabanındaki ilanlarla eşleştir ve en uygun seçenekleri öner. **Context7 uyumluluğu sağlanmıştır.**

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **talep_id:** integer - Talep ID'si
- **kategori_id:** integer - Kategori ID
- **min_fiyat:** decimal - Minimum fiyat
- **max_fiyat:** decimal - Maksimum fiyat
- **il_id:** integer - İl ID
- **ilce_id:** integer - İlçe ID (opsiyonel)

### **Opsiyonel Parametreler:**

- **mahalle_id:** integer - Mahalle ID
- **oda_sayisi:** string - Oda sayısı
- **metrekare_min:** integer - Minimum metrekare
- **metrekare_max:** integer - Maksimum metrekare
- **ozellikler:** array - İstenen özellikler
- **oncelik:** string - Öncelik seviyesi (dusuk, normal, yuksek, acil)

---

## 📤 **Çıktı Formatı**

### **JSON Format:**

```json
{
    "success": true,
    "analysis": {
        "talep_bilgileri": {
            "talep_id": "integer",
            "kategori": "string",
            "konum": "string",
            "butce_araligi": "string",
            "oncelik": "string"
        },
        "total_ilan_analyzed": "integer",
        "matching_ilan_count": "integer",
        "analysis_date": "timestamp"
    },
    "matches": [
        {
            "ilan_id": "integer",
            "baslik": "string",
            "fiyat": "decimal",
            "konum": "string",
            "match_score": "decimal",
            "score_breakdown": {
                "fiyat_uyumu": "decimal",
                "konum_uyumu": "decimal",
                "kategori_uyumu": "decimal",
                "ozellik_uyumu": "decimal"
            },
            "uygunluk_aciklamasi": "string",
            "onemli_ozellikler": ["array"],
            "oneri_derecesi": "string"
        }
    ],
    "recommendations": {
        "top_choice": "ilan_id",
        "best_value": "ilan_id",
        "best_location": "ilan_id"
    }
}
```

---

## ⚙️ **İşlem Kuralları**

### **Zorunlu Kurallar:**

- [ ] En az 3, en fazla 10 eşleşme önerilmeli
- [ ] Eşleşme skoru 0.5'ten yüksek olmalı
- [ ] Fiyat aralığı kontrolü yapılmalı
- [ ] Konum uyumu değerlendirilmeli
- [ ] Kategori uyumu zorunlu

### **Önerilen Kurallar:**

- [ ] Özellik uyumu değerlendirilmeli
- [ ] Alternatif seçenekler sunulmalı
- [ ] Açıklayıcı metinler eklenmeli
- [ ] Öncelik seviyesi dikkate alınmalı

---

## 🔍 **Eşleştirme Algoritması**

### **Skorlama Sistemi:**

```
Toplam Skor = (Fiyat Uyumu × 0.35) + (Konum Uyumu × 0.30) +
               (Kategori Uyumu × 0.25) + (Özellik Uyumu × 0.10)

Fiyat Uyumu:
- Tam aralıkta: 1.0
- %10 üzerinde: 0.8
- %20 üzerinde: 0.6
- %30 üzerinde: 0.4

Konum Uyumu:
- Aynı mahalle: 1.0
- Aynı ilçe: 0.8
- Aynı il: 0.6
- Farklı il: 0.2

Kategori Uyumu:
- Tam uyum: 1.0
- Benzer kategori: 0.7
- Farklı kategori: 0.0

Özellik Uyumu:
- Tüm özellikler: 1.0
- %80 özellik: 0.8
- %60 özellik: 0.6
- %40 özellik: 0.4
```

### **Bonus Puanlar:**

- Acil öncelik: +0.1
- Yeni ilan (< 7 gün): +0.05
- Özel özellikler: +0.05

---

## 🔍 **Kalite Kontrol**

### **Otomatik Kontroller:**

- [ ] Eşleşme sayısı kontrolü (3-10)
- [ ] Skor hesaplama doğruluğu
- [ ] Fiyat aralığı kontrolü
- [ ] JSON format uygunluğu

### **Manuel Kontroller:**

- [ ] Eşleşme kalitesi
- [ ] Açıklama doğruluğu
- [ ] Öneri mantığı
- [ ] Müşteri memnuniyeti

---

## 📊 **Performans Metrikleri**

### **Teknik Metrikler:**

- **Yanıt Süresi:** < 5 saniye
- **Doğruluk Oranı:** > %85
- **Başarı Oranı:** > %90

### **Kalite Metrikleri:**

- **Kullanıcı Memnuniyeti:** > 4.0/5
- **Eşleşme Kalitesi:** > 4.0/5
- **Öneri Doğruluğu:** > 4.0/5

### **İş Metrikleri:**

- **Dönüşüm Oranı:** > %25
- **Kullanım Sıklığı:** > 50/gün
- **Hata Oranı:** < %8

---

## 💡 **Örnek Kullanım**

### **Giriş:**

```json
{
    "talep_id": 456,
    "kategori_id": 1,
    "min_fiyat": 500000,
    "max_fiyat": 800000,
    "il_id": 34,
    "ilce_id": 1,
    "mahalle_id": 15,
    "oda_sayisi": "3+1",
    "metrekare_min": 100,
    "metrekare_max": 150,
    "ozellikler": ["asansör", "otopark"],
    "oncelik": "yuksek"
}
```

### **Çıktı:**

```json
{
    "success": true,
    "analysis": {
        "talep_bilgileri": {
            "talep_id": 456,
            "kategori": "Konut",
            "konum": "Kadıköy, İstanbul",
            "butce_araligi": "500.000 - 800.000 TL",
            "oncelik": "Yüksek"
        },
        "total_ilan_analyzed": 45,
        "matching_ilan_count": 8,
        "analysis_date": "2025-01-27T15:30:45Z"
    },
    "matches": [
        {
            "ilan_id": 123,
            "baslik": "Kadıköy'de Deniz Manzaralı 3+1 Daire",
            "fiyat": 750000,
            "konum": "Moda, Kadıköy",
            "match_score": 0.95,
            "score_breakdown": {
                "fiyat_uyumu": 1.0,
                "konum_uyumu": 1.0,
                "kategori_uyumu": 1.0,
                "ozellik_uyumu": 0.8
            },
            "uygunluk_aciklamasi": "Talep kriterlerinize tam uyumlu. Fiyat aralığında, aynı mahallede ve istenen özelliklere sahip.",
            "onemli_ozellikler": ["Deniz manzarası", "Asansör", "Otopark", "Güvenlik"],
            "oneri_derecesi": "Mükemmel"
        }
    ],
    "recommendations": {
        "top_choice": 123,
        "best_value": 156,
        "best_location": 123
    }
}
```

---

## ⚠️ **Hata Yönetimi**

### **Hata Kodları:**

- **400:** Geçersiz parametreler
- **422:** Doğrulama hatası
- **500:** Sunucu hatası
- **503:** Servis kullanılamıyor

### **Hata Mesajları:**

```json
{
    "success": false,
    "error": {
        "code": 400,
        "message": "Geçersiz parametre",
        "details": "talep_id parametresi gerekli"
    }
}
```

---

## 🎨 **Stil Rehberi**

### **Dil Kuralları:**

- Türkçe dilbilgisi kurallarına uygun
- Profesyonel emlak dili
- Müşteri odaklı ton
- Açık ve net ifadeler

### **Format Kuralları:**

- JSON formatında yanıt
- Yapılandırılmış veri
- Performans metrikleri dahil
- Hata yönetimi kapsamlı

### **İçerik Kuralları:**

- Eşleşme nedenleri açıklanmalı
- Alternatif seçenekler sunulmalı
- Müşteri faydaları vurgulanmalı
- Profesyonel öneriler

---

## 🔄 **Versiyonlama**

### **Version 1.1.0 (2026-01-08):**

- Context7 uyumluluğu sağlandı
- Küçük hata düzeltmeleri ve iyileştirmeler

---

**Not:** Bu prompt Context7 hafızasından veritabanı şemasını otomatik olarak okuyacak ve ilan/talep tablolarının yapısını anlayacaktır.
