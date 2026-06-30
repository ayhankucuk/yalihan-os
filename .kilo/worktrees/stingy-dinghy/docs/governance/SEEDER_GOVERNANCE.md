# 🌱 Seeder Yönetişim Kılavuzu

**Versiyon:** 1.0
**Tarih:** 2026-05-25
**Oturum:** 41 - Post-Sealing Audit
**Durum:** ACTIVE

---

## 📋 İÇİNDEKİLER

1. [Genel Prensipler](#genel-prensipler)
2. [Context7 Uyumluluk](#context7-uyumluluk)
3. [Seeder Sıralaması](#seeder-sıralaması)
4. [Hardcoded ID Politikası](#hardcoded-id-politikası)
5. [Best Practices](#best-practices)
6. [Seeder Şablonu](#seeder-şablonu)
7. [Test ve Doğrulama](#test-ve-doğrulama)

---

## 🎯 GENEL PRENSİPLER

### 1. İdempotency (Tekrar Çalıştırılabilirlik)

Tüm seederlar birden fazla kez çalıştırılabilir olmalıdır:

```php
// ❌ YANLIŞ
Model::create($data);

// ✅ DOĞRU
Model::updateOrCreate(
    ['slug' => $data['slug']], // Unique key
    $data                       // Data to insert/update
);
```

### 2. Slug-Bazlı Lookup

Hardcoded ID yerine slug kullanın:

```php
// ❌ YANLIŞ
'kategori_id' => 5

// ✅ DOĞRU
$kategori = IlanKategori::where('slug', 'konut')->first();
'kategori_id' => $kategori->id
```

### 3. Environment Awareness

Test verileri sadece development ortamında:

```php
if (app()->environment(['local', 'development', 'testing'])) {
    $this->call([
        DemoIlanSeeder::class,
        MusteriSeeder::class,
    ]);
}
```

---

## 🏛️ CONTEXT7 UYUMLULUK

### Context7 ID Formatı

Her seeder dosyasında Context7 ID bulunmalıdır:

```php
/**
 * Seeder Açıklaması
 *
 * Context7: C7-SEEDER-NAME-YYYY-MM-DD
 */
class MySeeder extends Seeder
{
    // ...
}
```

### Kanonik İsimlendirme

Context7 standartlarına uygun alan adları:

| ❌ Yasak | ✅ Kanonik |
|----------|------------|
| `status` | `yayin_durumu` |
| `active` | `aktiflik_durumu` |
| `order` | `display_order` |
| `email` | `eposta` |
| `city` | `il` / `il_adi` |

### Örnek Context7 Uyumlu Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\Ozellik;
use Illuminate\Database\Seeder;

/**
 * Özellikler Canonical Seeder
 *
 * Context7: C7-OZELLIKLER-2026-05-25
 */
class OzelliklerSeeder extends Seeder
{
    public function run(): void
    {
        $ozellikler = [
            [
                'name' => 'Brüt Metrekare',
                'slug' => 'brut-metrekare',
                'veri_tipi' => 'number',
                'aktiflik_durumu' => true,
                'display_order' => 1,
            ],
            // ...
        ];

        foreach ($ozellikler as $o) {
            Ozellik::updateOrCreate(
                ['slug' => $o['slug']],
                $o
            );
        }
    }
}
```

---

## 📊 SEEDER SIRALAMASI

### Bağımlılık Ağacı

Seederlar **mutlaka** bu sırada çalıştırılmalıdır:

```
1. RoleSeeder                          → Spatie roles (bağımlılık yok)
2. AdminUserSeeder                     → Super-admin users (RoleSeeder'a bağımlı)
3. UlkeSeeder                          → Ülkeler (bağımlılık yok)
4. IlanKategoriSeeder                  → Ana/Alt kategoriler (bağımlılık yok)
5. YayinTipiSeeder                     → Yayın tipleri (bağımlılık yok)
6. KategoriYayinTipiPivotSeeder        → Pivot (IlanKategori + YayinTipi'ye bağımlı)
7. OzellikKategoriSeeder               → Özellik kategorileri (bağımlılık yok)
8. PropertyHubOzelliklerSeeder         → Özellikler (OzellikKategori'ye bağımlı)
9. SmartFormsCanonicalSeeder           → Form kuralları (IlanKategori + YayinTipi'ye bağımlı)
10. ExpenseItemSeeder                  → Gider kalemleri (bağımlılık yok)
11. TurkiyeLocationSeeder              → İller, İlçeler, Mahalleler (bağımlılık yok)
12. BodrumPoiSeeder                    → POI verileri (TurkiyeLocationSeeder'a bağımlı)
13. DanismanSeeder (local/dev/test)    → Test danışmanlar (RoleSeeder'a bağımlı)
14. MusteriSeeder (local/dev/test)     → Test müşteriler (RoleSeeder'a bağımlı)
15. DemoIlanSeeder (local/dev/test)    → Demo ilanlar (Danışman + Kategori'ye bağımlı)
```

### DatabaseSeeder.php Yapısı

```php
public function run(): void
{
    // 1. ROLES & PERMISSIONS
    $this->call([RoleSeeder::class]);

    // 2. CORE SYSTEM
    $this->call([
        AdminUserSeeder::class,
        IlanKategoriSeeder::class,
        YayinTipiSeeder::class,
        KategoriYayinTipiPivotSeeder::class,
        OzellikKategoriSeeder::class,
        PropertyHubOzelliklerSeeder::class,
        SmartFormsCanonicalSeeder::class,
        ExpenseItemSeeder::class,
    ]);

    // 3. LOCATION + POI DATA
    $this->call([
        TurkiyeLocationSeeder::class,
        BodrumPoiSeeder::class,
    ]);

    // 4. TEST PERSONAS (Local/Dev/Test only)
    if (app()->environment(['local', 'development', 'testing'])) {
        $this->call([
            DanismanSeeder::class,
            MusteriSeeder::class,
            DemoIlanSeeder::class,
        ]);
    }
}
```

---

## 🔒 HARDCODED ID POLİTİKASI

### Kabul Edilebilir Durumlar

Hardcoded ID **sadece** şu durumlarda kabul edilir:

#### 1. Coğrafi Veriler
```php
// ✅ KABUL EDİLEBİLİR: Türkiye'nin 81 ili sabit
['id' => 48, 'il_adi' => 'Muğla', 'plaka_kodu' => '48']
```

#### 2. Temel Enum Değerleri
```php
// ✅ KABUL EDİLEBİLİR: Sistem genelinde referans alınan sabit değerler
['id' => 1, 'name' => 'Satılık', 'slug' => 'satilik']
['id' => 2, 'name' => 'Kiralık', 'slug' => 'kiralik']
```

#### 3. Uluslararası Standartlar
```php
// ✅ KABUL EDİLEBİLİR: ISO ülke kodları
['id' => 1, 'ulke_adi' => 'Türkiye', 'ulke_kodu' => 'TR']
```

### Kabul Edilemez Durumlar

```php
// ❌ YASAK: İş mantığı verileri için hardcoded ID
['kategori_id' => 5, 'name' => 'Daire']

// ✅ DOĞRU: Slug-bazlı lookup
$konut = IlanKategori::where('slug', 'konut')->first();
['parent_id' => $konut->id, 'name' => 'Daire', 'slug' => 'daire']
```

---

## ✅ BEST PRACTICES

### 1. Veri Validasyonu

```php
public function run(): void
{
    $kategori = IlanKategori::where('slug', 'konut')->first();

    if (!$kategori) {
        $this->command->error('❌ Konut kategorisi bulunamadı!');
        return;
    }

    // Devam et...
}
```

### 2. Progress Feedback

```php
public function run(): void
{
    $this->command->info('🌱 Özellikler yükleniyor...');

    $added = 0;
    $skipped = 0;

    foreach ($ozellikler as $o) {
        if (!Ozellik::where('slug', $o['slug'])->exists()) {
            Ozellik::create($o);
            $added++;
        } else {
            $skipped++;
        }
    }

    $this->command->info("✅ {$added} eklendi, {$skipped} atlandı.");
}
```

### 3. Batch Operations

Büyük veri setleri için chunk kullanın:

```php
DB::table('ozellikler')->insert($ozellikler); // Tek seferde
// veya
collect($ozellikler)->chunk(100)->each(function ($chunk) {
    DB::table('ozellikler')->insert($chunk->toArray());
});
```

### 4. Transaction Kullanımı

```php
public function run(): void
{
    DB::transaction(function () {
        // Tüm seeding işlemleri
    });
}
```

### 5. Soft Delete Temizliği

```php
// Test kayıtlarını temizle
Ozellik::where('slug', 'like', 'test-%')->forceDelete();
```

---

## 📝 SEEDER ŞABLONU

### Minimal Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\MyModel;
use Illuminate\Database\Seeder;

/**
 * MyModel Canonical Seeder
 *
 * Açıklama: Bu seeder ne yapar
 *
 * Context7: C7-MYMODEL-2026-05-25
 */
class MyModelSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 MyModel verileri yükleniyor...');

        $data = [
            [
                'name' => 'Örnek',
                'slug' => 'ornek',
                'aktiflik_durumu' => true,
                'display_order' => 1,
            ],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($data as $item) {
            if (!MyModel::where('slug', $item['slug'])->exists()) {
                MyModel::create($item);
                $this->command->info("  ✅ {$item['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ MyModel: {$added} eklendi, {$skipped} atlandı.");
        $this->command->info('📊 Toplam: ' . MyModel::count());
    }
}
```

### İlişkili Seeder (FK ile)

```php
<?php

namespace Database\Seeders;

use App\Models\Parent;
use App\Models\Child;
use Illuminate\Database\Seeder;

/**
 * Child Canonical Seeder
 *
 * Parent modeline bağımlı seeder
 *
 * Context7: C7-CHILD-2026-05-25
 */
class ChildSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Child verileri yükleniyor...');

        // Parent lookup (slug-bazlı)
        $parent = Parent::where('slug', 'parent-slug')->first();

        if (!$parent) {
            $this->command->error('❌ Parent bulunamadı!');
            return;
        }

        $children = [
            [
                'parent_id' => $parent->id,
                'name' => 'Child 1',
                'slug' => 'child-1',
            ],
        ];

        foreach ($children as $child) {
            Child::updateOrCreate(
                ['slug' => $child['slug']],
                $child
            );
        }

        $this->command->info('✅ Child verileri yüklendi.');
    }
}
```

---

## 🧪 TEST VE DOĞRULAMA

### 1. Seeder Testi

```bash
# Tüm seederları çalıştır
php artisan db:seed

# Belirli bir seeder'ı çalıştır
php artisan db:seed --class=MyModelSeeder

# Fresh migration + seed
php artisan migrate:fresh --seed
```

### 2. İdempotency Testi

```bash
# Aynı seeder'ı 2 kez çalıştır
php artisan db:seed --class=MyModelSeeder
php artisan db:seed --class=MyModelSeeder

# Kayıt sayısı değişmemeli
php artisan tinker
>>> MyModel::count()
```

### 3. FK Integrity Testi

```bash
# Foreign key constraint'leri kontrol et
php artisan db:table my_models --schema
```

### 4. Context7 Uyumluluk Testi

```bash
# Seeder dosyalarında Context7 ID kontrolü
grep -r "Context7:" database/seeders/

# Hardcoded ID kontrolü
grep -r "'id' =>" database/seeders/
```

---

## 📊 SEEDER SAĞLIK KONTROL LİSTESİ

Yeni bir seeder oluştururken kontrol edin:

- [ ] Context7 ID eklenmiş mi?
- [ ] İdempotent mi (updateOrCreate kullanılıyor mu)?
- [ ] Slug-bazlı lookup kullanılıyor mu?
- [ ] FK bağımlılıkları doğru mu?
- [ ] Progress feedback var mı?
- [ ] Error handling var mı?
- [ ] DatabaseSeeder.php'ye doğru sırada eklenmiş mi?
- [ ] Environment-aware mı (gerekiyorsa)?
- [ ] Test edildi mi?

---

## 🚨 YASAKLAR

### Kesinlikle Yapılmaması Gerekenler

```php
// ❌ YASAK: env() kullanımı
$apiKey = env('API_KEY');

// ❌ YASAK: Hardcoded business logic ID
'kategori_id' => 5

// ❌ YASAK: Non-idempotent insert
Model::create($data);

// ❌ YASAK: Silent failure
try {
    Model::create($data);
} catch (\Exception $e) {
    // Boş catch
}

// ❌ YASAK: Production'da test verisi
DemoIlanSeeder::class // Her zaman environment check ile
```

---

## 📚 REFERANSLAR

- [Laravel Seeding Docs](https://laravel.com/docs/seeding)
- [Context7 Naming Authority](docs/technical/NAMING-AUTHORITY.md)
- [SAB Constitution](docs/SAB.md)
- [Seed Audit Report](docs/technical/SEED_AUDIT_REPORT.md)

---

**Son Güncelleme:** 2026-05-25
**Sorumlu:** Yalıhan Bekçi Governance Team
**Durum:** ACTIVE & ENFORCED
