# Villa Başlık Oluşturucu

**Version:** 1.1.0
**Category:** villa
**Type:** baslik
**Priority:** high
**Last Updated:** 2026-01-08

---

## 🎯 **Görev**

Villa türü emlak ilanları için lüks ve prestij vurgulu başlıklar oluştur. **Context7 uyumluluğu sağlanmıştır.**

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **room_count:** string - Oda sayısı (örn: "5+2", "4+1")
- **location:** object - Konum bilgileri
    - **ilce:** string - İlçe adı
    - **mahalle:** string - Mahalle/bölge adı
- **price:** number - Fiyat (TRY)

### **Opsiyonel Parametreler:**

- **land_size:** number - Arsa büyüklüğü (m²)
- **luxury_features:** array - Lüks özellikler ["havuzlu", "deniz manzaralı", "müstakil"]
- **style:** string - Başlık stili ("luxury", "investment", "exclusive") - default: "luxury"

---

## 📤 **Çıktı Formatı**

```json
{
    "variants": [
        "Satılık Lüks Villa - Yalıkavak Marina, Bodrum",
        "5+2 Villa Satılık - Havuzlu, Deniz Manzaralı Yalıkavak",
        "Yalıkavak'ta Eşsiz Villa - 15.000.000 TL Satılık"
    ],
    "metadata": {
        "character_count_avg": 52,
        "luxury_score": 95,
        "exclusivity_level": "high",
        "price_psychology": "premium"
    }
}
```

---

## 🎯 **Context7 Kuralları**

- ✅ "Villa" kelimesi prominently featured
- ✅ "Satılık" keyword inclusion
- ✅ Luxury descriptors ("Lüks", "Eşsiz", "Özel")
- ✅ Premium location emphasis
- ✅ High-value features highlighting
- ✅ Prestige terminology usage
- ✅ 65 karakter altında idealdir
- ✅ Investment appeal when appropriate

---

## 📋 **Başlık Pattern'leri**

### Luxury Pattern:

`Satılık Lüks Villa - {premium_location}`

### Feature-Focused Pattern:

`{room_count} Villa Satılık - {luxury_features} {location}`

### Exclusive Pattern:

`{location}'ta Eşsiz Villa - {price} TL Satılık`

### Investment Pattern:

`Satılık Villa - {location} Yatırım Fırsatı`

---

## 📋 **Lüks Sıfatlar**

- Lüks, Eşsiz, Özel, Prestijli
- Muhteşem, Harika, Benzersiz
- Premium, Elite, Exclusive
- Rüya gibi, Büyüleyici

---

## 📋 **Örnek Çıktı**

```
Input: 5+2, Yalıkavak Marina/Bodrum, 15.000.000 TL, havuzlu, deniz manzaralı

Variants:
1. "Satılık Lüks Villa - Yalıkavak Marina, Bodrum"
2. "5+2 Villa Satılık - Havuzlu, Deniz Manzaralı Yalıkavak"
3. "Yalıkavak Marina'da Eşsiz Villa - 15.000.000 TL"
```
