# İlan Ekleme - Lokasyon Analizi Prompt

## 🎯 Amaç

İlan ekleme sayfasında AI ile lokasyon analizi üretimi için kullanılacak prompt.

## 📋 Giriş Parametreleri

- `il`: İl adı
- `ilce`: İlçe adı
- `mahalle`: Mahalle adı
- `adres`: Detaylı adres
- `kategori`: Ana kategori (Konut, Arsa, İş Yeri, Yazlık)
- `yayin_tipi`: Yayın tipi (Satılık, Kiralık, Günlük Kiralık)
- `latitude`: Enlem (opsiyonel)
- `longitude`: Boylam (opsiyonel)

## 🎨 Çıktı Formatı

```json
{
    "success": true,
    "data": {
        "lokasyon_skoru": 85,
        "harf_notu": "A-",
        "potansiyel": "Yüksek",
        "analiz": {
            "ulasim": {
                "skor": 90,
                "aciklama": "Merkezi konum, toplu taşıma erişimi mükemmel"
            },
            "cevre": {
                "skor": 80,
                "aciklama": "Yeşil alanlar ve sosyal tesisler yakın"
            },
            "yatirim": {
                "skor": 85,
                "aciklama": "Gelişen bölge, yatırım potansiyeli yüksek"
            },
            "guvenlik": {
                "skor": 75,
                "aciklama": "Güvenli mahalle, düşük suç oranı"
            }
        },
        "avantajlar": [
            "Merkezi konum",
            "Toplu taşıma erişimi",
            "Yeşil alanlar yakın",
            "Gelişen bölge"
        ],
        "dezavantajlar": ["Trafik yoğunluğu", "Gürültü seviyesi"],
        "oneri": "Bu lokasyon yatırım için uygun, kiralama potansiyeli yüksek"
    }
}
```

## 📝 Prompt Template

```
Sen bir emlak lokasyon analiz uzmanısın. Aşağıdaki bilgilere göre lokasyon analizi yap:

LOKASYON: {{il}} - {{ilce}} - {{mahalle}}
ADRES: {{adres}}
KATEGORİ: {{kategori}}
YAYIN TİPİ: {{yayin_tipi}}
KOORDİNAT: {{latitude}}, {{longitude}}

KURALLAR:
- 0-100 arası skor ver
- A-F arası harf notu ver
- Potansiyel seviyesi belirt (Düşük, Orta, Yüksek)
- 4 ana kriter analiz et (Ulaşım, Çevre, Yatırım, Güvenlik)
- Avantaj ve dezavantajları listele
- Genel öneri ver

KRİTERLER:
1. ULAŞIM: Toplu taşıma, ana yollar, erişim kolaylığı
2. ÇEVRE: Yeşil alanlar, sosyal tesisler, alışveriş merkezleri
3. YATIRIM: Gelişim potansiyeli, projeler, değer artışı
4. GÜVENLİK: Suç oranı, aydınlatma, güvenlik önlemleri

ÇIKTI FORMATI: JSON
```

## 🔄 Kullanım Senaryoları

1. **Merkezi Konum**: Yüksek skor, A notu
2. **Gelişen Bölge**: Orta-yüksek skor, B+ notu
3. **Uzak Lokasyon**: Düşük skor, C notu
4. **Lüks Mahalle**: Yüksek skor, A+ notu
5. **İş Merkezi**: Yüksek skor, A notu

## ⚡ Performans Hedefleri

- **Response Time**: < 3 saniye
- **Success Rate**: > 90%
- **Fallback**: Yerel veri tabanı kullan

## 🛡️ Güvenlik

- PII maskeleme
- Rate limiting
- Input validation
- Output sanitization
