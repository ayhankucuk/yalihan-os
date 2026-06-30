# Property Hub Şema Analizi - Oturum 40
**Tarih:** 2026-05-25
**Hedef:** `gayrimenkul_tipi` ve `gayrimenkul_kategorisi` Context7 Kanonik Uyum Analizi

---

## 📊 Mevcut Durum Tespiti

### 1. Şema Analizi Sonuçları

#### ✅ Mevcut Yapı (Context7 Uyumlu)
Property Hub modülü şu anda **3-seviyeli kategori sistemi** kullanıyor:

```
Ana Kategori (ana_kategori_id)
    └── Alt Kategori (alt_kategori_id)
            └── Yayın Tipi (yayin_tipi_id)
```

**Tablolar:**
- `ilan_kategorileri` → Ana ve alt kategoriler (self-referencing)
- `yayin_tipi_sablonlari` → Yayın tipleri (Satılık, Kiralık, vb.)
- `alt_kategori_yayin_tipi` → Junction table (pivot)
- `ilanlar` → İlan tablosu (ana_kategori_id, alt_kategori_id, yayin_tipi_id)

#### ❌ Aranılan Alanlar: BULUNAMADI

**Arama Sonuçları:**
```bash
# Database migrations
grep -r "gayrimenkul_tipi" database/migrations/     → 0 sonuç
grep -r "gayrimenkul_kategorisi" database/migrations/ → 0 sonuç

# Models
grep -r "gayrimenkul_tipi" app/Models/              → 0 sonuç
grep -r "gayrimenkul_kategorisi" app/Models/        → 0 sonuç

# Services
grep -r "gayrimenkul_tipi" app/Services/            → 0 sonuç
grep -r "gayrimenkul_kategorisi" app/Services/      → 0 sonuç

# Views
grep -r "gayrimenkul_tipi" resources/views/         → 0 sonuç
grep -r "gayrimenkul_kategorisi" resources/views/   → 0 sonuç
```

**Sonuç:** `gayrimenkul_tipi` ve `gayrimenkul_kategorisi` alanları sistemde **hiç kullanılmıyor**.

---

### 2. İlgili Alan Tespiti: `emlak_tipi`

#### Kullanım Yeri: `talepler` Tablosu

**Migration:**
```php
// database/migrations/2024_01_01_000000_create_core_baseline_tables.php
Schema::create('talepler', function (Blueprint $table) {
    $table->string('talep_tipi');
    $table->string('emlak_tipi');  // ← Bu alan mevcut
    // ...
});
```

**Model:**
```php
// app/Models/Talep.php
protected $fillable = [
    'talep_tipi',
    'emlak_tipi',  // ← Kullanımda
    'talep_durumu',
    // ...
];
```

**Kullanım Örnekleri:**
```php
// app/Services/AIMatch/BuyerMatchDetectionService.php:60
->where('property_type', $ilan->emlak_tipi)

// app/Services/AIMatch/BuyerMatchScoringService.php:116
return $ilan->emlak_tipi === $talep->emlak_tipi ? self::WEIGHT_TYPE : 0;

// app/Services/AIMatch/BuyerIntentExtractionService.php:42
'property_types' => $latestTalep ? [$latestTalep->emlak_tipi] : []
```

---

### 3. Context7 Kanonik İsimlendirme Kontrolü

**Mevcut Kanonik Tablo (.clinerules):**
```
| ❌ Yasak (Legacy)           | ✅ Kanonik (Context7)    |
| :---                        | :---                     |
| status                      | yayin_durumu             |
| active, is_active, aktif    | aktiflik_durumu          |
| order, sort_order           | display_order            |
| featured, is_featured       | one_cikan                |
| city, sehir                 | il / il_adi              |
| musteriler                  | kisiler                  |
```

**Tespit:**
- `emlak_tipi` → Kanonik tabloda **tanımlı değil**
- `gayrimenkul_tipi` → Kanonik tabloda **tanımlı değil**
- `gayrimenkul_kategorisi` → Kanonik tabloda **tanımlı değil**
- `property_type` → Kanonik tabloda **tanımlı değil**

---

## 🎯 Sorun Analizi

### Tespit Edilen Sorunlar

#### 1. **İsimlendirme Tutarsızlığı**
```php
// Talepler tablosunda
'emlak_tipi'  // ← Türkçe

// Kod içinde (property_type kullanımları)
->where('property_type', $ilan->emlak_tipi)  // ← İngilizce-Türkçe karışımı
```

#### 2. **Kanonik Tanım Eksikliği**
Context7 kanonik isimlendirme tablosunda emlak/gayrimenkul tipi için standart YOK.

#### 3. **Semantik Belirsizlik**
- `emlak_tipi` → Genel emlak tipi mi? (Konut, Arsa, İşyeri)
- `property_type` → API/kod içinde farklı anlamlarda kullanılıyor
- `ana_kategori_id` → Zaten kategori bilgisi tutuyor

---

## 📋 Kanonik Dönüşüm Planı

### Önerilen Kanonik İsimlendirme

#### Seçenek 1: Mevcut Yapıyı Koruma (ÖNERİLEN)
```
✅ MEVCUT: ana_kategori_id + alt_kategori_id + yayin_tipi_id
✅ AVANTAJ: Zaten Context7 uyumlu, 3-seviyeli hiyerarşi
✅ DURUM: Property Hub Recovery Report'ta onaylanmış
```

**Aksiyon:** `emlak_tipi` alanını kaldır, ilişkisel yapıya geç.

#### Seçenek 2: Yeni Kanonik Alan Tanımlama
```
❌ LEGACY: emlak_tipi (string)
✅ KANONIK: emlak_kategori_id (foreign key → ilan_kategorileri)
```

**Aksiyon:** `emlak_tipi` → `emlak_kategori_id` migration + refactor.

---

## 🔧 Migration Stratejisi

### Faz 1: Kanonik Tanım Ekleme

**Dosya:** `.clinerules` (Bölüm 5)

```diff
## 5. 🔤 CONTEXT7 TÜRKÇE KANONİK İSİMLENDİRME

| ❌ Yasak (Legacy) | ✅ Kanonik (Context7) |
| :--- | :--- |
| `status` | `yayin_durumu` |
| `active`, `is_active`, `aktif` | `aktiflik_durumu` |
+ | `property_type`, `emlak_tipi` | `ana_kategori_id` (FK) |
+ | `property_category`, `gayrimenkul_kategorisi` | `alt_kategori_id` (FK) |
| `order`, `sort_order` | `display_order` |
```

### Faz 2: `talepler` Tablosu Refactoring

#### Migration: `emlak_tipi` → `ana_kategori_id`

```php
// database/migrations/2026_05_25_XXXXXX_refactor_talepler_emlak_tipi_to_kategori_id.php

public function up()
{
    Schema::table('talepler', function (Blueprint $table) {
        // 1. Yeni kolon ekle
        $table->unsignedBigInteger('ana_kategori_id')->nullable()->after('talep_tipi');
        $table->foreign('ana_kategori_id')->references('id')->on('ilan_kategorileri')->nullOnDelete();

        // 2. Alt kategori de eklenebilir (opsiyonel)
        $table->unsignedBigInteger('alt_kategori_id')->nullable()->after('ana_kategori_id');
        $table->foreign('alt_kategori_id')->references('id')->on('ilan_kategorileri')->nullOnDelete();
    });

    // 3. Veri migrasyonu (string → ID mapping)
    $this->migrateEmlakTipiData();

    // 4. Eski kolonu kaldır (production'da dikkatli!)
    // Schema::table('talepler', function (Blueprint $table) {
    //     $table->dropColumn('emlak_tipi');
    // });
}

private function migrateEmlakTipiData()
{
    // String → ID mapping
    $mapping = [
        'Konut' => 2,
        'Arsa' => 1,
        'Villa' => 3,
        'Yazlık' => 5,
        'İşyeri' => 4,
        // ... diğer tipler
    ];

    foreach ($mapping as $tip => $kategoriId) {
        DB::table('talepler')
            ->where('emlak_tipi', $tip)
            ->update(['ana_kategori_id' => $kategoriId]);
    }
}

public function down()
{
    Schema::table('talepler', function (Blueprint $table) {
        $table->dropForeign(['ana_kategori_id']);
        $table->dropForeign(['alt_kategori_id']);
        $table->dropColumn(['ana_kategori_id', 'alt_kategori_id']);
    });
}
```

### Faz 3: Model Güncellemesi

```php
// app/Models/Talep.php

protected $fillable = [
    'talep_tipi',
    // 'emlak_tipi',  // ← DEPRECATED
    'ana_kategori_id',  // ← YENİ
    'alt_kategori_id',  // ← YENİ (opsiyonel)
    'talep_durumu',
    // ...
];

// İlişki ekle
public function anaKategori()
{
    return $this->belongsTo(IlanKategori::class, 'ana_kategori_id');
}

public function altKategori()
{
    return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
}

// Backward compatibility accessor (geçiş dönemi için)
public function getEmlakTipiAttribute()
{
    return $this->anaKategori?->name ?? 'Belirtilmemiş';
}
```

### Faz 4: Service Refactoring

```php
// app/Services/AIMatch/BuyerMatchDetectionService.php

// ❌ ESKİ
->where('property_type', $ilan->emlak_tipi)

// ✅ YENİ
->where('ana_kategori_id', $ilan->ana_kategori_id)
```

```php
// app/Services/AIMatch/BuyerMatchScoringService.php

// ❌ ESKİ
return $ilan->emlak_tipi === $talep->emlak_tipi ? self::WEIGHT_TYPE : 0;

// ✅ YENİ
return $ilan->ana_kategori_id === $talep->ana_kategori_id ? self::WEIGHT_TYPE : 0;
```

---

## ⚠️ Risk Değerlendirmesi

### Yüksek Riskli Alanlar

1. **Veri Kaybı Riski**
   - `emlak_tipi` string değerleri → ID mapping sırasında eşleşmeyen değerler
   - **Önlem:** Migration öncesi veri analizi + mapping validation

2. **API Breaking Change**
   - Frontend/Mobile API'ler `emlak_tipi` bekliyor olabilir
   - **Önlem:** Backward compatibility accessor + API versioning

3. **Projection/CQRS Tabloları**
   - `TalepMatchProjection`, `BuyerIntentProjection` gibi tablolar etkilenebilir
   - **Önlem:** Projection rebuild + test coverage

### Düşük Riskli Alanlar

1. **Property Hub Modülü**
   - Zaten `ana_kategori_id` + `alt_kategori_id` kullanıyor
   - **Etki:** YOK

2. **İlan Tablosu**
   - `emlak_tipi` alanı YOK
   - **Etki:** YOK

---

## ✅ Önerilen Yaklaşım

### Aşamalı Geçiş (Gradual Migration)

#### Faz 1: Kanonik Tanım (Hemen)
- `.clinerules` güncelle
- `NAMING-AUTHORITY.md` güncelle
- Bekçi kurallarına ekle

#### Faz 2: Paralel Kolon (1 Sprint)
- `ana_kategori_id` ekle
- `emlak_tipi` koru (deprecated)
- Veri senkronizasyonu

#### Faz 3: Kod Refactoring (2 Sprint)
- Service katmanı güncelle
- API backward compatibility
- Test coverage

#### Faz 4: Cleanup (1 Sprint)
- `emlak_tipi` kaldır
- Deprecated accessor'ları temizle
- Final validation

---

## 📊 Etki Analizi

### Etkilenen Dosyalar

**Models (2 dosya):**
- `app/Models/Talep.php`
- `app/Models/Projections/TalepMatchProjection.php`

**Services (3 dosya):**
- `app/Services/AIMatch/BuyerMatchDetectionService.php`
- `app/Services/AIMatch/BuyerMatchScoringService.php`
- `app/Services/AIMatch/BuyerIntentExtractionService.php`

**Migrations (1 yeni):**
- `database/migrations/2026_05_25_XXXXXX_refactor_talepler_emlak_tipi_to_kategori_id.php`

**Toplam:** ~6 dosya + 1 migration

---

## 🎯 Sonuç ve Öneriler

### Ana Bulgular

1. ✅ **Property Hub modülü zaten Context7 uyumlu**
   - `ana_kategori_id` + `alt_kategori_id` + `yayin_tipi_id` yapısı mevcut
   - Property Hub Recovery Report'ta onaylanmış

2. ❌ **`gayrimenkul_tipi` ve `gayrimenkul_kategorisi` alanları mevcut değil**
   - Sistemde hiç kullanılmamış
   - Migration gerekmez

3. ⚠️ **`emlak_tipi` alanı Context7 uyumlu değil**
   - Sadece `talepler` tablosunda kullanılıyor
   - String-based, ilişkisel değil
   - Refactoring gerekiyor

### Önerilen Aksiyon

**ÖNCELİK 1:** `emlak_tipi` → `ana_kategori_id` refactoring
**ÖNCELİK 2:** Kanonik isimlendirme tablosuna ekleme
**ÖNCELİK 3:** Bekçi kurallarına ekleme

### Başarı Kriterleri

- [ ] `.clinerules` güncellendi
- [ ] Migration oluşturuldu ve test edildi
- [ ] Model ilişkileri eklendi
- [ ] Service katmanı refactor edildi
- [ ] Bekçi sağlık skoru korundu (%75+)
- [ ] 0 blocking violation
- [ ] Antigravity gate başarılı

---

**Mühürlendi 🛡️**
**Oturum 40 - Property Hub Şema Restorasyonu**
