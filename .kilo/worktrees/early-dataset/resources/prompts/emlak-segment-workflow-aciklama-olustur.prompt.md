# Emlak Segment Workflow - Açıklama Oluşturma Prompt

## Sistem Bilgisi

Bu prompt, segment tabanlı emlak yönetim sisteminde her segment için uygun açıklamalar oluşturmak için kullanılır.

## Segment Yapısı

Sistem 5 ana segment'ten oluşur:

### 1. Portföy Bilgi Formu (portfolio_info)

- **Amaç**: Temel emlak bilgilerini toplama
- **İçerik**: Başlık, fiyat, m2, ada, pafta, fotoğraflar
- **Zorunlu Alanlar**: baslik, fiyat, para_birimi, emlak_turu, ilan_turu, brut_metrekare

### 2. Dökümanlar ve Notlar (documents_notes)

- **Amaç**: Yasal belgeler ve iç notlar
- **İçerik**: Tapu, planlar, iç notlar, ek belgeler
- **Opsiyonel**: Tüm alanlar opsiyonel

### 3. Portal İlan Bilgileri (portal_listing)

- **Amaç**: Dış portallara yayın hazırlığı
- **İçerik**: Portal özel açıklamalar, senkronizasyon ayarları
- **Portallar**: Sahibinden.com, Hepsi Emlak, Emlakjet, 101 Evler

### 4. Uygun Alıcılar (suitable_buyers)

- **Amaç**: Müşteri eşleştirme
- **İçerik**: Potansiyel alıcılar, talep eşleştirmeleri
- **Kriterler**: Fiyat aralığı, konum tercihi, emlak türü

### 5. İşlem Kapama (transaction_closure)

- **Amaç**: Satış/kiralama tamamlama
- **İçerik**: Sözleşme, ödeme, arşivleme
- **Türler**: Satıldı, Kiralandı, İptal Edildi

## Context7 Uyumluluk Kuralları

- Database alanları İngilizce olmalı
- Türkçe alan isimleri YASAK (durum, aktif, sehir, musteriler)
- Model ilişkileri Context7 naming convention'ına uygun
- Blade template'ler Context7 pattern'lerini kullanmalı

## Segment Açıklama Oluşturma Kuralları

### Portföy Bilgi Formu Açıklaması

```
"Bu segment'te emlakın temel bilgilerini girin. Başlık, fiyat, metrekare ve parsel bilgileri zorunludur.
Fotoğraflar yükleyerek ilanın görsel kalitesini artırabilirsiniz."
```

### Dökümanlar ve Notlar Açıklaması

```
"Yasal belgeleri ve iç notları bu segment'te yönetin. Tapu, planlar ve diğer önemli belgeleri yükleyin.
İç notlar sadece sizin ve ekibinizin görebileceği özel bilgilerdir."
```

### Portal İlan Bilgileri Açıklaması

```
"Dış emlak portallarına yayın için özel ayarları yapın. Her portal için farklı açıklamalar yazabilir,
senkronizasyon ayarlarını yönetebilirsiniz."
```

### Uygun Alıcılar Açıklaması

```
"Potansiyel alıcıları bulun ve eşleştirin. Fiyat aralığı, konum tercihi gibi kriterlere göre
otomatik eşleştirme yapabilir veya manuel olarak müşteri seçebilirsiniz."
```

### İşlem Kapama Açıklaması

```
"Satış veya kiralama işlemini tamamlayın. Final fiyat, komisyon bilgileri ve sözleşme
dokümanlarını bu segment'te yönetin."
```

## Kullanım Senaryoları

### Yeni İlan Oluşturma

1. Portföy Bilgi Formu → Temel bilgileri gir
2. Dökümanlar ve Notlar → Belgeleri yükle
3. Portal İlan Bilgileri → Portal ayarlarını yap
4. Uygun Alıcılar → Müşteri eşleştir
5. İşlem Kapama → İşlemi tamamla

### Mevcut İlan Düzenleme

- Herhangi bir segment'e doğrudan erişim
- Önceki segment'ler tamamlanmış olmalı
- Progress bar ile ilerleme takibi

## Teknik Detaylar

### Route Yapısı

```
/admin/ilanlar/segments/create/{segment?}
/admin/ilanlar/segments/{ilan}/{segment?}
```

### Controller Metodları

- `show()` - Segment görüntüleme
- `store()` - Segment verilerini kaydetme
- `calculateProgress()` - İlerleme hesaplama
- `isSegmentCompleted()` - Segment tamamlanma kontrolü

### Validation Kuralları

Her segment için özel validation kuralları:

- Portföy Bilgi: Zorunlu alanlar
- Dökümanlar: Dosya formatları
- Portal: Portal özel kurallar
- Alıcılar: Müşteri ID'leri
- İşlem: Tarih ve fiyat formatları

## AI Entegrasyonu

- Mevcut AI Settings Controller ile entegre
- Google Gemini, OpenAI, Claude desteği
- Segment bazlı AI önerileri
- Otomatik açıklama oluşturma

## Örnek Kullanım

```php
// Segment enum kullanımı
$segment = IlanSegment::PORTFOLIO_INFO;
$title = $segment->getTitle(); // "Portföy Bilgi Formu"
$description = $segment->getDescription(); // "Temel emlak bilgilerini girin"

// Sonraki segment'e geçiş
$nextSegment = $segment->getNext(); // DOCUMENTS_NOTES
```

Bu sistem, emlak sektöründe kullanılan segment tabanlı iş akışını modern web teknolojileri ile birleştirerek kullanıcı dostu bir deneyim sunar.
