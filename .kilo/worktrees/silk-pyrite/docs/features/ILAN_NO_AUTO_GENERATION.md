# İlan Numarası Otomatik Üretim Sistemi

**Tarih:** 2026-05-16  
**Durum:** 🔴 Eksik - Tasarım Aşaması  
**Öncelik:** 🔥 Yüksek

---

## 🎯 Amaç

Her ilan için otomatik, benzersiz, anlamlı bir numara üretmek. Bu numara:
- ✅ İlan tipini göstermeli (Satılık/Kiralık/Yazlık)
- ✅ Kategoriyi göstermeli (Daire/Villa/Arsa/İşyeri)
- ✅ Benzersiz olmalı
- ✅ Aranabilir olmalı
- ✅ Arşivlemede kullanılmalı
- ✅ Portföy kimliği olmalı

---

## 📋 Format Tasarımı

### Önerilen Format
```
{TIP}-{KATEGORİ}-{YIL}-{SIRA}
```

### Örnekler

**Satılık İlanlar:**
```
STL-DRE-2024-001  → Satılık Daire
STL-VLA-2024-002  → Satılık Villa
STL-ARS-2024-003  → Satılık Arsa
STL-ISY-2024-004  → Satılık İşyeri
```

**Kiralık İlanlar:**
```
KRL-DRE-2024-001  → Kiralık Daire
KRL-VLA-2024-002  → Kiralık Villa
KRL-ISY-2024-003  → Kiralık İşyeri
```

**Yazlık Kiralama:**
```
YZL-VLA-2024-001  → Yazlık Villa
YZL-DRE-2024-002  → Yazlık Daire
```

---

## 🏗️ Teknik Tasarım

### 1. İlan Tipi Kodları
```php
'satis' => 'STL',      // Satılık
'kiralama' => 'KRL',   // Kiralık
'yazlik' => 'YZL',     // Yazlık Kiralama
'gunluk' => 'GNL',     // Günlük Kiralama
```

### 2. Kategori Kodları
```php
'daire' => 'DRE',
'villa' => 'VLA',
'arsa' => 'ARS',
'isyeri' => 'ISY',
'konut' => 'KNT',
'ticari' => 'TCR',
```

### 3. Sıra Numarası
- Her tip+kategori kombinasyonu için ayrı sayaç
- 3 haneli, sıfır ile doldurulmuş (001, 002, ...)
- Yıl bazlı sıfırlanabilir (opsiyonel)

---

## 💻 Uygulama Yöntemleri

### Yöntem 1: Model Observer (Önerilen)
```php
// app/Observers/IlanObserver.php
class IlanObserver
{
    public function creating(Ilan $ilan): void
    {
        if (empty($ilan->ilan_no)) {
            $ilan->ilan_no = $this->generateIlanNo($ilan);
        }
    }

    private function generateIlanNo(Ilan $ilan): string
    {
        $tip = $this->getTipKodu($ilan->yayin_tipi_id);
        $kategori = $this->getKategoriKodu($ilan->ana_kategori_id);
        $yil = date('Y');
        
        // Son sıra numarasını bul
        $lastIlan = Ilan::where('ilan_no', 'like', "{$tip}-{$kategori}-{$yil}-%")
            ->orderBy('ilan_no', 'desc')
            ->first();
        
        $sira = 1;
        if ($lastIlan) {
            $parts = explode('-', $lastIlan->ilan_no);
            $sira = intval(end($parts)) + 1;
        }
        
        return sprintf('%s-%s-%s-%03d', $tip, $kategori, $yil, $sira);
    }
}
```

### Yöntem 2: Service Class
```php
// app/Services/IlanNoGenerator.php
class IlanNoGenerator
{
    public function generate(Ilan $ilan): string
    {
        return DB::transaction(function () use ($ilan) {
            // Race condition'dan korunmak için transaction içinde
            $tip = $this->getTipKodu($ilan);
            $kategori = $this->getKategoriKodu($ilan);
            $yil = date('Y');
            
            // Lock ile son numarayı al
            $lastSira = DB::table('ilanlar')
                ->where('ilan_no', 'like', "{$tip}-{$kategori}-{$yil}-%")
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING_INDEX(ilan_no, "-", -1) AS UNSIGNED)'));
            
            $sira = ($lastSira ?? 0) + 1;
            
            return sprintf('%s-%s-%s-%03d', $tip, $kategori, $yil, $sira);
        });
    }
}
```

### Yöntem 3: Database Sequence (En Güvenli)
```php
// Migration
Schema::create('ilan_no_sequences', function (Blueprint $table) {
    $table->id();
    $table->string('tip_kodu', 3);
    $table->string('kategori_kodu', 3);
    $table->integer('yil');
    $table->integer('son_sira')->default(0);
    $table->unique(['tip_kodu', 'kategori_kodu', 'yil']);
});

// Service
class IlanNoGenerator
{
    public function generate(Ilan $ilan): string
    {
        return DB::transaction(function () use ($ilan) {
            $tip = $this->getTipKodu($ilan);
            $kategori = $this->getKategoriKodu($ilan);
            $yil = date('Y');
            
            $sequence = DB::table('ilan_no_sequences')
                ->where('tip_kodu', $tip)
                ->where('kategori_kodu', $kategori)
                ->where('yil', $yil)
                ->lockForUpdate()
                ->first();
            
            if (!$sequence) {
                DB::table('ilan_no_sequences')->insert([
                    'tip_kodu' => $tip,
                    'kategori_kodu' => $kategori,
                    'yil' => $yil,
                    'son_sira' => 1,
                ]);
                $sira = 1;
            } else {
                $sira = $sequence->son_sira + 1;
                DB::table('ilan_no_sequences')
                    ->where('id', $sequence->id)
                    ->update(['son_sira' => $sira]);
            }
            
            return sprintf('%s-%s-%s-%03d', $tip, $kategori, $yil, $sira);
        });
    }
}
```

---

## 🔍 Arama Özellikleri

### Tam Eşleşme
```php
Ilan::where('ilan_no', 'STL-DRE-2024-001')->first();
```

### Kısmi Arama
```php
// Tüm satılık daireler
Ilan::where('ilan_no', 'like', 'STL-DRE-%')->get();

// 2024 yılı ilanları
Ilan::where('ilan_no', 'like', '%-2024-%')->get();

// Tüm villalar
Ilan::where('ilan_no', 'like', '%-VLA-%')->get();
```

### Full-Text Search Index
```sql
ALTER TABLE ilanlar ADD FULLTEXT INDEX idx_ilan_no (ilan_no);
```

---

## 📊 Database Değişiklikleri

### Migration
```php
// Mevcut ilan_no alanı zaten var, sadece index ekle
Schema::table('ilanlar', function (Blueprint $table) {
    $table->index('ilan_no');
    // UNIQUE constraint eklenebilir
    // $table->unique('ilan_no');
});
```

---

## 🎨 UI Gösterimi

### Detay Sayfasında
```blade
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 dark:bg-blue-900/20">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/>
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-700 dark:text-blue-300">
                İlan Numarası: <span class="font-mono font-bold">{{ $ilan->ilan_no }}</span>
            </p>
        </div>
    </div>
</div>
```

### Liste Sayfasında
```blade
<span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-mono font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
    {{ $ilan->ilan_no }}
</span>
```

---

## ✅ Uygulama Adımları

1. **Migration Oluştur**
   - `ilan_no_sequences` tablosu
   - `ilanlar.ilan_no` index

2. **Service Class Oluştur**
   - `IlanNoGenerator` service
   - Tip ve kategori mapping'leri

3. **Observer Ekle**
   - `IlanObserver` oluştur
   - `creating` event'inde otomatik üret

4. **Test Yaz**
   - Unit test: Numara formatı
   - Integration test: Benzersizlik
   - Concurrency test: Race condition

5. **Mevcut İlanları Güncelle**
   - Boş `ilan_no` alanlarını doldur
   - Artisan command ile toplu güncelleme

---

## 🚨 Dikkat Edilmesi Gerekenler

1. **Race Condition:** Transaction ve lock kullan
2. **Performans:** Index ekle, cache kullan
3. **Geriye Dönük Uyumluluk:** Mevcut ilanlar için migration
4. **Yıl Değişimi:** Yıl bazlı sıfırlama stratejisi
5. **Kategori Değişikliği:** İlan kategorisi değişirse numara değişmemeli

---

## 📝 Sonraki Adımlar

Bu bir **tasarım dokümanı**. Uygulamak için:

1. Yeni bir task/issue oluştur
2. Yöntem seç (Observer/Service/Sequence)
3. Migration ve test'leri yaz
4. Mevcut ilanları güncelle
5. UI'da göster

**Tahmini Süre:** 4-6 saat  
**Öncelik:** Yüksek  
**Bağımlılıklar:** Yok
