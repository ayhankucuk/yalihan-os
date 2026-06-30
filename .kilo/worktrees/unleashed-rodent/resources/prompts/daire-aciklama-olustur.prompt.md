# Daire Açıklama Oluşturucu

**Version:** 1.0.0
**Category:** daire
**Type:** aciklama
**Priority:** high
**Last Updated:** 2025-10-15

---

## 🎯 **Görev**

Daire türü emlak ilanları için SEO uyumlu, satış odaklı açıklamalar oluştur.

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **title:** string - İlan başlığı
- **location:** object - Konum bilgileri (il, ilçe, mahalle)
- **price:** number - Fiyat bilgisi (TRY)
- **features:** array - Özellikler listesi (oda sayısı, m², kat, etc.)

### **Opsiyonel Parametreler:**

- **tone:** string - Ton (seo, kurumsal, hizli_satis, luks) - default: "seo"
- **target_length:** number - Hedef karakter sayısı (150-300) - default: 200
- **keywords:** array - Ek anahtar kelimeler
- **building_age:** number - Bina yaşı
- **floor:** number - Kat bilgisi
- **heating_type:** string - Isıtma tipi

---

## 📤 **Çıktı Formatı**

```json
{
    "variants": [
        "SEO optimized description 1 with location and features",
        "Sales-focused description 2 with pricing emphasis",
        "Feature-rich description 3 with lifestyle appeal"
    ],
    "metadata": {
        "character_count": 245,
        "seo_score": 85,
        "readability": "easy",
        "keywords_used": ["daire", "satılık", "location", "features"],
        "tone_applied": "seo"
    }
}
```

---

## 🎯 **Context7 Kuralları**

- ✅ Turkish language optimization
- ✅ Real estate terminology accuracy
- ✅ Location-based keyword integration (il, ilçe, mahalle)
- ✅ Price point positioning and value proposition
- ✅ Feature highlighting (room count, size, floor)
- ✅ Call-to-action inclusion
- ✅ Mobile-friendly formatting
- ✅ SEO keyword density (2-3%)

---

## 📋 **Örnek Prompt Kullanımı**

```json
{
    "title": "3+1 Daire Satılık - Yalıkavak",
    "location": {
        "il": "Muğla",
        "ilce": "Bodrum",
        "mahalle": "Yalıkavak"
    },
    "price": 2500000,
    "features": ["3+1", "120 m²", "2. kat", "deniz manzarası", "balkon"],
    "tone": "seo",
    "target_length": 250
}
```

---

## 📋 **Örnek Çıktı**

```
"Yalıkavak'ta satılık 3+1 daire! Bodrum'un prestijli Yalıkavak bölgesinde, deniz manzaralı 120 m² daire sizleri bekliyor. 2. katta konumlanmış bu özel daire, geniş balkon ve modern tasarımıyla hayalinizdeki yaşamı sunuyor. 2.500.000 TL fiyatıyla kaçırılmayacak yatırım fırsatı. Detaylar için hemen arayın!"
```
