# Arama, Filtreleme ve Referans Numarası Sistemi — Tam Spec

**Tarih:** 18 Mayıs 2026  
**Kaynak:** Zillow, Rightmove, Redfin, Sahibinden, Emlakjet analizi  
**Durum:** Planlama — implementasyon bekliyor

---

## Dünyada Ne Var? (Kısa Özet)

| Platform | Öne Çıkan Özellik |
|----------|-------------------|
| **Zillow** | Haritada "draw-a-search", BuyAbility (anlık ödeme hesabı), ChatGPT entegrasyonu (2025), AI Mode (2026) |
| **Redfin** | Conversational AI search (Sierra AI, Kasım 2025) — kullanıcılar **2x fazla ilan** görüyor, **%47 daha fazla** tur talep ediyor |
| **Rightmove** | Polygon harita, okul/mahalle rehberi, metrekare filtresi, kesin doğrulama |
| **Homes.com** | "Ranch tarzı, havuzlu, Austin'de" gibi doğal dil arama |
| **Emlakjet** | Akıllı arama kaydı + bildirim, adres kaydetme, harita destekli |
| **Sahibinden** | Harita arama, doğrudan mesaj, geniş kategori yapısı |

**Kritik veri:** Redfin'in conversational search testinde kullanıcılar standart filtreli aramaya göre **2 kat fazla ilan** inceledi ve **%47 daha fazla** tur talep etti. Bu Yalıhan için anlamlı: NLPProcessor zaten var.

---

## 1. Referans Numarası Sistemi

### Format Kararı

```
YLH - 2025 - 04821 - BDM
 ↑      ↑      ↑      ↑
Prefix  Yıl  Sıra No  Bölge Kodu
```

**Örnekler:**
```
YLH-2025-04821-BDM   → Bodrum satılık villa
YLH-2025-01203-IST   → İstanbul kiralık daire
YLH-2025-00087-MGL   → Muğla arsa
```

**Bölge kodları (3 harf):**
```
BDM → Bodrum        IST → İstanbul     ANK → Ankara
IZM → İzmir         ANT → Antalya      MGL → Muğla
ALN → Alanya        FTH → Fethiye      CSM → Çeşme
DDA → Didim         AYD → Aydın        OTR → Diğer
```

### Neden Bu Format?

- **Okunabilir:** Danışman telefonda okuyabilir ("YLH iki bin yirmi beş sıfır dört sekiz iki bir BDM")
- **Sıralanabilir:** Yıl + sıra numarası kronolojik sıra sağlar
- **Bölge bağlamı:** İlk bakışta nerede olduğu anlaşılır
- **Benzersiz:** Tenant + format kombinasyonu çakışmayı önler

### Arama Davranışı

Kullanıcı referans numarası yazdığında **direkt o ilana** git:
- `YLH-2025-04821` → `/ilanlar/ylh-2025-04821-bdm`
- `04821` → Fuzzy match, öneride göster
- Referans no URL'de de kullanılır (`/satilik/bodrum/villa/ylh-2025-04821-bdm`)

### Teknik Uygulama

```php
// IlanReferansNo value object (SAB uyumlu)
final class IlanReferansNo
{
    private function __construct(
        public readonly string $value // "YLH-2025-04821-BDM"
    ) {}

    public static function generate(int $yil, int $siraNo, string $bolgeKodu): self
    {
        return new self(sprintf(
            'YLH-%d-%05d-%s',
            $yil,
            $siraNo,
            strtoupper($bolgeKodu)
        ));
    }

    public static function fromString(string $value): self
    {
        // YLH-YYYY-NNNNN-BBB formatını doğrula
        if (!preg_match('/^YLH-\d{4}-\d{5}-[A-Z]{3}$/', $value)) {
            throw new InvalidArgumentException("Geçersiz referans numarası: {$value}");
        }
        return new self($value);
    }
}
```

---

## 2. Arama Sistemi — 3 Katman

### Katman 1: Anlık Öneri (Typeahead)

Kullanıcı yazmaya başladığı anda — 300ms debounce ile:

```
[Bodrum'da 3+1...]
─────────────────────────────────────────
📍 Bodrum, Muğla               → bölge
📍 Bodrum Merkez               → mahalle  
🏠 Bodrum'da Satılık Villa     → ilan başlığı
🏠 YLH-2025-04821-BDM          → referans no
👤 Mehmet Yılmaz (Danışman)    → danışman
```

Gruplandırılmış öneriler: Bölge / İlan / Referans No / Danışman

### Katman 2: Filtreli Arama (Standart)

URL tabanlı, form submit, aşağıda detaylı.

### Katman 3: Doğal Dil Arama (AI)

```
"Bodrum'da 5 milyon altı, deniz manzaralı, 3+1 villa"
      ↓ NLPProcessor (zaten var)
fiyat_max=5000000 & tip=villa & oda=3 & ozellik=deniz_manzarasi & bolge=bodrum
      ↓ IlanRepository
Filtreli sonuçlar
```

Redfin verisi: konuşmalı arama kullanıcıları **%47 daha fazla** tur talep ediyor.  
Yalıhan'da `NLPProcessor` ve `Cortex` zaten var — bu katman bağlanmayı bekliyor.

---

## 3. Filtre Sistemi — Tam Liste

### Ana Filtreler (Her Zaman Görünür)

| Filtre | Tip | Değerler |
|--------|-----|----------|
| İşlem Türü | Radio/Tab | Satılık · Kiralık · Günlük Kiralık |
| Emlak Tipi | Multi-select | Daire · Villa · Müstakil · Arsa · İşyeri · Devre Mülk |
| Bölge / Konum | Autocomplete + Harita | İlçe/Mahalle arama |
| Fiyat Aralığı | Range slider | Min – Max (₺) |
| Oda Sayısı | Chip seçim | Stüdyo · 1+1 · 2+1 · 3+1 · 4+1 · 5+ |

### Gelişmiş Filtreler (Drawer'da)

**Boyut ve Yapı**
| Filtre | Tip |
|--------|-----|
| Brüt m² | Min – Max |
| Net m² | Min – Max |
| Arsa m² | Min – Max (villa/müstakil için) |
| Bina yaşı | Seçim: Sıfır · 1-5 · 6-10 · 11-20 · 20+ yıl |
| Kat | Min – Max |
| Toplam kat | Min – Max |
| Bulunduğu kat | Giriş · Ara kat · Son kat · Bahçe katı |

**Isıtma ve Teknik**
| Filtre | Tip |
|--------|-----|
| Isıtma | Multi: Doğalgaz · Merkezi · Klima · Yerden ısıtma · Yok |
| Yakıt türü | Multi: Doğalgaz · Elektrik · Güneş · Kombi |
| Eşyalı mı? | Toggle: Eşyalı · Eşyasız · Seçmeli |
| Site içinde mi? | Toggle |
| Aidat | Max aidat (₺/ay) |

**Özellikler (Checkbox grubu)**
```
□ Deniz manzarası    □ Havuz          □ Otopark (kapalı)
□ Dağ manzarası      □ Bahçe          □ Otopark (açık)
□ Şehir manzarası    □ Teras          □ Asansör
□ Göl manzarası      □ Balkon         □ Güvenlik / Güvenlik kamerası  
□ Doğa manzarası     □ Garaj          □ Jeneratör
□ İskele / Marina    □ Depo / Kiler   □ Engelli erişimi
□ Akıllı ev sistemi  □ Şömine        □ Su deposu
```

**Konum ve Çevre**
| Filtre | Tip |
|--------|-----|
| Denize mesafe | Seçim: 0-100m · 100-500m · 500m-1km · 1km+ |
| Okul mesafesi | Toggle + seçim |
| Hastane mesafesi | Toggle |
| Toplu taşıma | Toggle |
| AVM / Market | Toggle |

**İlan Bilgisi**
| Filtre | Tip |
|--------|-----|
| İlan tarihi | Son 24 saat · Son 3 gün · Son hafta · Son ay |
| Kimden | Sahibinden · Emlak ofisinden · Tümü |
| Tapu durumu | Kat mülkiyeti · Kat irtifakı · Hisseli · Arsa tapusu |
| İmar durumu | Konut · Ticari · Tarım · Karma |
| Danışman | Autocomplete (dahili kullanım) |

### Sıralama Seçenekleri

```
[En Yeni ▾]
  ✓ En Yeni
    En Eski
    Fiyat (Artan)
    Fiyat (Azalan)
    m² Fiyatı (Artan)
    m² Fiyatı (Azalan)
    Öne Çıkanlar
    AI Skoru (Yüksekten)    ← Yalıhan'a özel
```

---

## 4. Harita Tabanlı Arama

### Özellikler (Rightmove + Zillow'dan alınan en iyiler)

**Draw-a-Search (Polygon)**
Kullanıcı haritada serbest çizim yapar, o alan içindeki ilanlar filtrelenir. Leaflet-draw zaten kurulu.

**Yarıçap Arama**
Bir noktaya tıkla → 500m / 1km / 2km / 5km yarıçapında ara.

**POI Filtresi**
Harita üzerinde: Okul · Hastane · Market · Plaj · Marina katmanları açılıp kapanabilir.

**Isı Haritası**
Fiyat yoğunluğu → renk gradyanı. Hangi mahalleler pahalı, hangisi uygun anlık görülür.

**Cluster**
Zoom out'ta ilanlar kümelenir (cluster), zoom in'de açılır.

### Leaflet Entegrasyonu (Mevcut)

`leaflet-integration.js` ve `leaflet-draw-loader.js` zaten var. Eksik:
- Polygon arama backend bağlantısı
- Isı haritası katmanı
- POI katmanları (PoiService zaten var)

---

## 5. Akıllı Arama Kaydı (Saved Search)

Emlakjet'in en güçlü özelliği. Yalıhan'a uyarlanmış hali:

```
"Bu aramayı kaydet" → modal açılır
  İsim: [Bodrum Villa Araması]
  Bildirim: ● Anında  ○ Günlük özet  ○ Haftalık özet
  Kanal:    ☑ WhatsApp  ☑ E-posta  ○ Yalnızca app
  [Kaydet]
```

Yeni ilan kriterlere uyunca:
- WhatsApp mesajı: `"Bodrum Villa Araması" için yeni ilan: YLH-2025-04821-BDM [link]`
- E-posta: Günlük digest

**Teknik:** `SavedSearch` model → `IlanYayinlandiEvent` listener → filtre match → `SendWhatsAppMessageJob`

---

## 6. Karşılaştırma Aracı

Zillow ve Rightmove'da var, Türkiye'de yok. Fark yaratır.

```
İlan 1          vs      İlan 2          vs      İlan 3
YLH-04821               YLH-04905               YLH-05012
─────────────────────────────────────────────────────────
Fiyat   4.2M ₺          3.8M ₺                  4.9M ₺
m²      220             195                      280
m²/₺    19.090          19.487                  17.500 ✓en iyi
Oda     3+1             3+1                      4+1
Kat     3               5                        2
Yaş     5 yıl           12 yıl                  Sıfır ✓
Havuz   ✓               ✗                        ✓
Deniz   500m            200m ✓                  1.2km

[AI Yorumu: m² başına fiyat ve sıfır yapı ile İlan 3 öne çıkıyor,
 ancak denize mesafe İlan 2'de daha iyi.]
```

---

## 7. Uygulama Planı

### Sprint 1 — Referans No (2 gün)
- [ ] `IlanReferansNo` value object yaz
- [ ] Migration: `ilanlar.referans_no` kolonu ekle
- [ ] Mevcut ilanlara toplu referans no üret
- [ ] URL yapısına ekle: `/satilik/{bolge}/{tip}/{referans-no}`
- [ ] Typeahead'de referans no araması

### Sprint 2 — Filtre Genişletme (3-4 gün)
- [ ] `IlanFilterDTO` → yeni alanlar ekle (denize_mesafe, ozellikler, imar_durumu)
- [ ] DB: eksik filtre kolonları / feature tablosu
- [ ] `filter-bar.blade.php` → tam filtre seti
- [ ] `filter-drawer.blade.php` → gelişmiş filtreler

### Sprint 3 — Doğal Dil Arama (3-4 gün)
- [ ] `NLPProcessor::parseSearchQuery()` → `IlanFilterDTO`'ya map
- [ ] Global komut paletine bağla (`Cmd+K`)
- [ ] Öğrenme: hangi sorgular null sonuç verdi → log

### Sprint 4 — Harita İyileştirme (3-4 gün)
- [ ] Polygon arama → `IlanRepository::inPolygon()`
- [ ] Isı haritası katmanı
- [ ] POI katmanları (PoiService bağlantısı)

### Sprint 5 — Akıllı Arama Kaydı (2-3 gün)
- [ ] `SavedSearch` model + migration
- [ ] `IlanYayinlandiEvent` → match → WhatsApp/e-posta

### Sprint 6 — Karşılaştırma (2-3 gün)
- [ ] Karşılaştırma listesi (max 3 ilan, localStorage)
- [ ] Karşılaştırma sayfası + AI yorumu

---

## Öncelik Matrisi

| Özellik | Etki | İş | Öncelik |
|---------|------|-----|---------|
| Referans No | Yüksek | Düşük | **1. Sprint** |
| Gelişmiş filtreler | Yüksek | Orta | **2. Sprint** |
| Doğal dil arama | Çok Yüksek | Orta | **3. Sprint** |
| Akıllı arama kaydı | Yüksek | Orta | **4. Sprint** |
| Harita polygon | Orta | Orta | **5. Sprint** |
| Karşılaştırma | Orta | Orta | **6. Sprint** |

---

*Son güncelleme: 18 Mayıs 2026*  
*Kaynak: Zillow, Rightmove, Redfin, Sahibinden, Emlakjet analizi*
