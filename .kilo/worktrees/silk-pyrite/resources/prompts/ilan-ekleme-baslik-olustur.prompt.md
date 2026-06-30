# İlan Ekleme - Başlık Oluşturma Prompt

## 🎯 Amaç

İlan ekleme sayfasında AI ile başlık üretimi için kullanılacak prompt.

## 📋 Giriş Parametreleri

- `kategori`: Ana kategori (Konut, Arsa, İş Yeri, Yazlık)
- `alt_kategori`: Alt kategori (Villa, Daire, Arsa, vb.)
- `yayin_tipi`: Yayın tipi (Satılık, Kiralık, Günlük Kiralık)
- `lokasyon`: İl, İlçe, Mahalle bilgileri
- `fiyat`: Fiyat bilgisi
- `para_birimi`: Para birimi (TL, USD, EUR)
- `ozellikler`: Özel özellikler listesi
- `tone`: Ton (Profesyonel, Samimi, Lükse, Ekonomik)
- `variant_count`: Varyant sayısı (1-5)

## 🎨 Çıktı Formatı

```json
{
    "success": true,
    "data": {
        "basliklar": [
            {
                "baslik": "Başlık 1",
                "tone": "Profesyonel",
                "length": 45,
                "seo_score": 85
            },
            {
                "baslik": "Başlık 2",
                "tone": "Samimi",
                "length": 52,
                "seo_score": 78
            }
        ],
        "recommended": 0,
        "suggestions": ["Öneri 1", "Öneri 2"]
    }
}
```

## 📝 Prompt Template

```
Sen bir emlak uzmanısın. Aşağıdaki bilgilere göre {{variant_count}} farklı başlık oluştur:

KATEGORİ: {{kategori}}
ALT KATEGORİ: {{alt_kategori}}
YAYIN TİPİ: {{yayin_tipi}}
LOKASYON: {{lokasyon}}
FİYAT: {{fiyat}} {{para_birimi}}
ÖZELLİKLER: {{ozellikler}}
TON: {{tone}}

KURALLAR:
- 40-60 karakter arası
- {{tone}} tonunda yaz
- Lokasyon adını içer
- Yayın tipini belirt
- Öne çıkan özelliği vurgula
- SEO dostu olsun
- Çekici ve net olsun

VARYANTLAR:
1. Profesyonel ton
2. Samimi ton
3. Lüks ton
4. Ekonomik ton
5. Yaratıcı ton

ÇIKTI FORMATI: JSON
```

## 🔄 Kullanım Senaryoları

1. **Villa Satılık**: "Lüks Villa - Bodrum Merkez - 2.500.000 TL"
2. **Daire Kiralık**: "3+1 Daire - Çankaya - 8.500 TL/Ay"
3. **Arsa Satılık**: "İmarlı Arsa - Antalya - 450.000 TL"
4. **Yazlık Günlük**: "Deniz Manzaralı Yazlık - Çeşme - 1.200 TL/Gün"
5. **İş Yeri Kiralık**: "Merkezi Ofis - İstanbul - 15.000 TL/Ay"

## ⚡ Performans Hedefleri

- **Response Time**: < 2 saniye
- **Success Rate**: > 95%
- **Fallback**: Yerel template kullan

## 🛡️ Güvenlik

- PII maskeleme
- Rate limiting
- Input validation
- Output sanitization
