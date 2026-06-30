# Daire Başlık Oluşturucu

**Version:** 1.0.0
**Category:** daire
**Type:** baslik
**Priority:** high
**Last Updated:** 2025-10-15

---

## 🎯 **Görev**

Daire türü emlak ilanları için SEO optimize, dikkat çekici başlıklar oluştur.

---

## 📥 **Giriş Parametreleri**

### **Zorunlu Parametreler:**

- **room_count:** string - Oda sayısı (örn: "3+1", "2+1")
- **location:** object - Konum bilgileri
    - **ilce:** string - İlçe adı
    - **mahalle:** string - Mahalle adı (opsiyonel)
- **price:** number - Fiyat (TRY)

### **Opsiyonel Parametreler:**

- **size:** number - Metrekare
- **special_features:** array - Özel özellikler ["deniz manzarası", "havuzlu", "yeni"]
- **urgency:** boolean - Acil satış durumu
- **style:** string - Başlık stili ("classic", "modern", "urgent") - default: "classic"

---

## 📤 **Çıktı Formatı**

```json
{
    "variants": [
        "3+1 Daire Satılık - Yalıkavak, Bodrum",
        "Satılık 3+1 Daire - Yalıkavak Merkez 2.500.000 TL",
        "Yalıkavak'ta Deniz Manzaralı 3+1 Daire - Acil Satılık"
    ],
    "metadata": {
        "character_count_avg": 45,
        "seo_score": 88,
        "click_potential": "high",
        "keywords_density": 85
    }
}
```

---

## 🎯 **Context7 Kuralları**

- ✅ Room count başta (3+1, 2+1, 4+1)
- ✅ "Satılık" keyword mutlaka kullan
- ✅ Location hierarchy: Mahalle, İlçe (il genelde atlanır)
- ✅ Price mention (optional but recommended)
- ✅ Special features highlighting
- ✅ 60 karakter altında tutmaya çalış
- ✅ Turkish grammar accuracy
- ✅ No unnecessary punctuation

---

## 📋 **Başlık Pattern'leri**

### Classic Pattern:

`{room_count} Daire Satılık - {mahalle}, {ilce}`

### Modern Pattern:

`Satılık {room_count} Daire - {location} {price} TL`

### Urgent Pattern:

`{location}'ta {special_feature} {room_count} Daire - Acil Satılık`

### Premium Pattern:

`{special_feature} {room_count} Daire - {premium_location}`

---

## 📋 **Örnek Çıktı**

```
Input: 3+1, Yalıkavak/Bodrum, 2.500.000 TL, deniz manzarası

Variants:
1. "3+1 Daire Satılık - Yalıkavak, Bodrum"
2. "Satılık 3+1 Daire - Yalıkavak 2.500.000 TL"
3. "Yalıkavak'ta Deniz Manzaralı 3+1 Daire - Satılık"
```
