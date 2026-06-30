# Arsa Açıklama Oluşturucu

**Version:** 1.0.0
**Category:** arsa
**Type:** aciklama
**Priority:** high
**Last Updated:** 2025-10-15

---

## 🎯 **Görev**

Arsa türü emlak ilanları için yatırım odaklı, potansiyel vurgulu açıklamalar oluştur.

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **title:** string - İlan başlığı
- **location:** object - Konum bilgileri (il, ilçe, mahalle)
- **price:** number - Fiyat bilgisi (TRY)
- **size:** number - Arsa büyüklüğü (m²)
- **zoning_status:** string - İmar durumu

### **Opsiyonel Parametreler:**

- **tone:** string - Ton (yatirim, gelecek, potansiyel) - default: "yatirim"
- **target_length:** number - Hedef karakter sayısı (150-250) - default: 200
- **keywords:** array - Ek anahtar kelimeler
- **development_rights:** string - Yapı hakkı bilgisi
- **accessibility:** array - Ulaşım bilgileri
- **nearby_projects:** array - Yakın projeler

---

## 📤 **Çıktı Formatı**

```json
{
    "variants": [
        "Investment-focused description with future potential",
        "Development-oriented description with zoning emphasis",
        "Location-based description with accessibility features"
    ],
    "metadata": {
        "character_count": 195,
        "seo_score": 80,
        "readability": "medium",
        "keywords_used": ["arsa", "satılık", "yatırım", "imar", "location"],
        "investment_score": 90
    }
}
```

---

## 🎯 **Context7 Kuralları**

- ✅ Investment terminology usage
- ✅ Zoning and development focus
- ✅ Future potential emphasis
- ✅ Location accessibility mention
- ✅ Legal status clarity (imar durumu)
- ✅ Size and measurement accuracy
- ✅ Price per m² calculation
- ✅ Development opportunity highlighting

---

## 📋 **Örnek Prompt Kullanımı**

```json
{
    "title": "Satılık İmarlı Arsa - Yalıkavak",
    "location": {
        "il": "Muğla",
        "ilce": "Bodrum",
        "mahalle": "Yalıkavak"
    },
    "price": 3000000,
    "size": 500,
    "zoning_status": "villa imar",
    "tone": "yatirim",
    "development_rights": "400 m² yapı hakkı"
}
```

---

## 📋 **Örnek Çıktı**

```
"Yalıkavak'ta yatırım fırsatı! Villa imarli 500 m² arsa satılıkta. Bodrum'un değer kazanan Yalıkavak bölgesinde, 400 m² yapı hakkı bulunan bu arsa, hem kişisel kullanım hem de yatırım için ideal. Ana yola cepheli, altyapı hazır konumda. 3.000.000 TL (6.000 TL/m²) - Kaçırılmayacak yatırım fırsatı!"
```
