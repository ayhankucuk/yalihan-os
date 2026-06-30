# Villa Açıklama Oluşturucu

**Version:** 1.0.0
**Category:** villa
**Type:** aciklama
**Priority:** high
**Last Updated:** 2025-10-15

---

## 🎯 **Görev**

Villa türü emlak ilanları için lüks ve prestij odaklı, SEO uyumlu açıklamalar oluştur.

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **title:** string - İlan başlığı
- **location:** object - Konum bilgileri (il, ilçe, mahalle/bölge)
- **price:** number - Fiyat bilgisi (TRY)
- **features:** array - Özellikler listesi (oda sayısı, m², arsa, havuz, etc.)

### **Opsiyonel Parametreler:**

- **tone:** string - Ton (luks, kurumsal, prestij, yatirim) - default: "luks"
- **target_length:** number - Hedef karakter sayısı (200-400) - default: 300
- **keywords:** array - Ek anahtar kelimeler
- **land_size:** number - Arsa büyüklüğü (m²)
- **pool:** boolean - Havuz var/yok
- **sea_view:** boolean - Deniz manzarası var/yok
- **garden:** boolean - Bahçe var/yok

---

## 📤 **Çıktı Formatı**

```json
{
    "variants": [
        "Luxury-focused description with prestige appeal",
        "Investment-oriented description with value proposition",
        "Lifestyle-centered description with amenities focus"
    ],
    "metadata": {
        "character_count": 285,
        "seo_score": 90,
        "readability": "medium",
        "keywords_used": ["villa", "satılık", "lüks", "location", "features"],
        "tone_applied": "luks",
        "luxury_score": 95
    }
}
```

---

## 🎯 **Context7 Kuralları**

- ✅ Luxury real estate terminology
- ✅ Premium location emphasis
- ✅ High-value feature highlighting
- ✅ Investment potential mention
- ✅ Lifestyle and prestige appeal
- ✅ Exclusive opportunity positioning
- ✅ Professional tone maintenance
- ✅ Multi-variant generation for A/B testing

---

## 📋 **Örnek Prompt Kullanımı**

```json
{
    "title": "Satılık Lüks Villa - Yalıkavak Marina",
    "location": {
        "il": "Muğla",
        "ilce": "Bodrum",
        "mahalle": "Yalıkavak"
    },
    "price": 15000000,
    "features": ["5+2", "400 m²", "1000 m² arsa", "özel havuz", "deniz manzarası"],
    "tone": "luks",
    "target_length": 350,
    "land_size": 1000,
    "pool": true,
    "sea_view": true
}
```

---

## 📋 **Örnek Çıktı**

```
"Yalıkavak Marina'da eşsiz lüks villa! Bodrum'un en prestijli bölgesi Yalıkavak'ta, panoramik deniz manzaralı 5+2 lüks villa satılıkta. 400 m² kapalı alan, 1000 m² özel bahçe ve özel havuz ile rüya gibi yaşam sizleri bekliyor. Marina'ya yürüme mesafesinde, premium lokasyonda konumlanmış bu eşsiz villa, hem yaşam hem de yatırım için kaçırılmayacak fırsat. 15.000.000 TL - Özel görüşme için hemen arayın."
```
