# İlan Ekleme - Fiyat Önerisi Prompt

## 🎯 Amaç

İlan ekleme sayfasında AI ile fiyat önerisi üretimi için kullanılacak prompt.

## 📋 Giriş Parametreleri

- `kategori`: Ana kategori (Konut, Arsa, İş Yeri, Yazlık)
- `alt_kategori`: Alt kategori (Villa, Daire, Arsa, vb.)
- `yayin_tipi`: Yayın tipi (Satılık, Kiralık, Günlük Kiralık)
- `lokasyon`: İl, İlçe, Mahalle bilgileri
- `alan_m2`: Alan bilgisi (m²)
- `ozellikler`: Özel özellikler listesi
- `mevcut_fiyat`: Mevcut fiyat (opsiyonel)
- `para_birimi`: Para birimi (TL, USD, EUR)

## 🎨 Çıktı Formatı

```json
{
    "success": true,
    "data": {
        "fiyat_onerileri": [
            {
                "seviye": "Pazarlık",
                "fiyat": 450000,
                "para_birimi": "TL",
                "aciklama": "Hızlı satış için önerilen fiyat",
                "confidence": 85
            },
            {
                "seviye": "Piyasa",
                "fiyat": 500000,
                "para_birimi": "TL",
                "aciklama": "Piyasa değeri bazlı fiyat",
                "confidence": 92
            },
            {
                "seviye": "Premium",
                "fiyat": 550000,
                "para_birimi": "TL",
                "aciklama": "Özellikler göz önüne alınarak premium fiyat",
                "confidence": 78
            }
        ],
        "analiz": {
            "piyasa_durumu": "Yükselişte",
            "talep_seviyesi": "Yüksek",
            "rekabet_durumu": "Orta",
            "oneri": "Piyasa fiyatı önerilir"
        }
    }
}
```

## 📝 Prompt Template

```
Sen bir emlak değerleme uzmanısın. Aşağıdaki bilgilere göre fiyat önerisi oluştur:

KATEGORİ: {{kategori}}
ALT KATEGORİ: {{alt_kategori}}
YAYIN TİPİ: {{yayin_tipi}}
LOKASYON: {{lokasyon}}
ALAN: {{alan_m2}} m²
ÖZELLİKLER: {{ozellikler}}
MEVCUT FİYAT: {{mevcut_fiyat}} {{para_birimi}}

KURALLAR:
- 3 seviye fiyat öner (Pazarlık, Piyasa, Premium)
- Lokasyon avantajlarını değerlendir
- Alan büyüklüğünü göz önüne al
- Özelliklerin değerini hesapla
- Piyasa durumunu analiz et
- Confidence score ver (0-100)
- Açıklama ekle

FAKTÖRLER:
- Lokasyon değeri
- Alan büyüklüğü
- Özellik kalitesi
- Piyasa durumu
- Talep seviyesi
- Rekabet durumu

ÇIKTI FORMATI: JSON
```

## 🔄 Kullanım Senaryoları

1. **Villa Satılık**: Lüks villa fiyat önerisi
2. **Daire Kiralık**: Konforlu daire kira önerisi
3. **Arsa Satılık**: İmarlı arsa değer önerisi
4. **Yazlık Günlük**: Tatil evi günlük kira önerisi
5. **İş Yeri Kiralık**: Ticari alan kira önerisi

## ⚡ Performans Hedefleri

- **Response Time**: < 3 saniye
- **Success Rate**: > 90%
- **Fallback**: Yerel hesaplama kullan

## 🛡️ Güvenlik

- PII maskeleme
- Rate limiting
- Input validation
- Output sanitization
