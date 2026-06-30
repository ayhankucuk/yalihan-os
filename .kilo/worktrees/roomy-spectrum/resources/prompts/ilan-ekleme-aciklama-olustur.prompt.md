# İlan Ekleme - Açıklama Oluşturma Prompt

## 🎯 Amaç

İlan ekleme sayfasında AI ile açıklama üretimi için kullanılacak prompt.

## 📋 Giriş Parametreleri

- `kategori`: Ana kategori (Konut, Arsa, İş Yeri, Yazlık)
- `alt_kategori`: Alt kategori (Villa, Daire, Arsa, vb.)
- `yayin_tipi`: Yayın tipi (Satılık, Kiralık, Günlük Kiralık)
- `lokasyon`: İl, İlçe, Mahalle bilgileri
- `fiyat`: Fiyat bilgisi
- `para_birimi`: Para birimi (TL, USD, EUR)
- `ozellikler`: Özel özellikler listesi
- `tone`: Ton (Profesyonel, Samimi, Lükse, Ekonomik)

## 🎨 Çıktı Formatı

```json
{
    "success": true,
    "data": {
        "aciklama": "Üretilen açıklama metni (200-250 kelime)",
        "tone": "Kullanılan ton",
        "word_count": 225,
        "suggestions": ["Öneri 1", "Öneri 2", "Öneri 3"]
    }
}
```

## 📝 Prompt Template

```
Sen bir emlak uzmanısın. Aşağıdaki bilgilere göre profesyonel bir ilan açıklaması oluştur:

KATEGORİ: {{kategori}}
ALT KATEGORİ: {{alt_kategori}}
YAYIN TİPİ: {{yayin_tipi}}
LOKASYON: {{lokasyon}}
FİYAT: {{fiyat}} {{para_birimi}}
ÖZELLİKLER: {{ozellikler}}
TON: {{tone}}

KURALLAR:
- 200-250 kelime arası
- {{tone}} tonunda yaz
- Emlak terimlerini doğru kullan
- Lokasyon avantajlarını vurgula
- Özellikleri çekici şekilde anlat
- Fiyat-performans oranını belirt
- Call-to-action ekle

ÇIKTI FORMATI: JSON
```

## 🔄 Kullanım Senaryoları

1. **Villa Satılık**: Lüks villa açıklaması
2. **Daire Kiralık**: Konforlu daire açıklaması
3. **Arsa Satılık**: Yatırım değeri yüksek arsa
4. **Yazlık Günlük**: Tatil evi açıklaması
5. **İş Yeri Kiralık**: Ticari alan açıklaması

## ⚡ Performans Hedefleri

- **Response Time**: < 3 saniye
- **Success Rate**: > 95%
- **Fallback**: Yerel template kullan

## 🛡️ Güvenlik

- PII maskeleme
- Rate limiting
- Input validation
- Output sanitization
