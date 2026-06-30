# Wizard AI Result Null Crash Fix

## Özet

Create / Wizard ekranında Alpine template render edilirken aşağıdaki hata alınıyordu:
- **Hata:** `Alpine Expression Error: Cannot read properties of null (reading 'zorunlu_alanlar')`
- **Etkilenen Expression:** `aiResult.zorunlu_alanlar`
- **Sebep:** `aiResult` değişkeninin ilk render sırasında `null` olması ve Blade şablonunun bu değişkenin özelliklerine (`zorunlu_alanlar`, vb.) erişmeye çalışması.

Bu durum Wizard akışını bozarak formun genişlememesine, tabların görünmemesine veya template doğrulama hatalarına yol açıyordu.

## Çözüm: JS Tarafında Güvenli Başlatma (SSOT)

`resources/js/wizard/components/ai-description.js` dosyası güncellenerek `aiResult` değişkeninin asla `null` olmaması sağlandı. Kod tekrarını önlemek için `emptyAiResult` helper fonksiyonu eklendi.

### Değişiklikler

1.  **Helper Funksiyonu (SSOT):**
    Varsayılan şemayı döndüren tek bir kaynak oluşturuldu.

```javascript
const emptyAiResult = () => ({
    zorunlu_alanlar: [],
    opsiyonel_alanlar: [],
    validasyon_kurallari: {},
    ui_ipuclari: {},
});
```

2.  **Component Başlangıcı:**
    Alpine bileşeni başlatılırken helper kullanılarak initialize edildi.

```javascript
export default function aiDescriptionGenerator() {
    return {
        // ...
        // Safe AI Result Schema (SSOT via helper)
        aiResult: emptyAiResult(),
        // ...
    }
}
```

3.  **Güvenli Setter Metodu:**
    `setAiResult` metodu, dışarıdan gelen veriyi varsayılan şema ile merge ederken aynı helper'ı kullanarak tutarlılığı garantiler.

```javascript
setAiResult(payload) {
    // SSOT: Use the helper to ensure consistent defaults
    this.aiResult = { ...emptyAiResult(), ...(payload || {}) };
}
```

## Doğrulama

**Senaryo:** `Arsa -> Arsa (Konut/Villa) -> Satılık` seçimi yapıldı.

**Sonuçlar:**
- Tarayıcı konsolunda `Cannot read properties of null` hatası görülmedi.
- Wizard Step 2 ("Bilgiler") başarıyla yüklendi.
- Template alanları ("Arsa Satış Detayları") görünür hale geldi.

Bu düzeltme ile Wizard UI render süreci stabil hale getirilmiştir.
