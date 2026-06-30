# 💬 CRM Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-CRM-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

CRM modülü, müşteri ilişkileri yönetimi, aktivite takibi, etiket yönetimi ve randevu sistemi sağlar.

## 🎯 Sorumluluklar

- **Kişi Yönetimi:** Müşteri, aday, potansiyel müşteri yönetimi
- **Aktivite Takibi:** Müşteri aktiviteleri, notlar, görüşmeler
- **Etiket Sistemi:** Kişi etiketleme, kategorilendirme
- **Randevu Yönetimi:** Randevu oluşturma, takip, hatırlatmalar

## 📁 Yapı

```
Crm/
├── Controllers/
│   ├── KisiController.php         # Kişi yönetimi
│   ├── AktiviteController.php     # Aktivite yönetimi
│   ├── EtiketController.php       # Etiket yönetimi
│   └── RandevuController.php      # Randevu yönetimi
├── Models/
│   ├── Kisi.php                   # Kişi modeli (Context7: musteri değil)
│   ├── KisiNot.php                # Kişi notları
│   ├── Aktivite.php                # Aktivite modeli
│   ├── Etiket.php                  # Etiket modeli
│   └── Randevu.php                 # Randevu modeli
├── Services/
│   ├── KisiService.php            # Kişi iş mantığı
│   ├── AktiviteService.php        # Aktivite iş mantığı
│   └── EtiketService.php          # Etiket iş mantığı
├── routes/
│   ├── web.php                    # Web route'ları
│   └── api.php                    # API route'ları
└── Database/
    └── Migrations/                # Veritabanı migration'ları
```

## 🔗 Bağımlılıklar

- **Emlak Modülü:** İlan-Kişi ilişkileri için
- **Auth Modülü:** Kullanıcı (User) ilişkileri için
- **Talep Modülü:** Talep-Kişi eşleştirmeleri için

## 🚀 Kullanım

### Kişi Oluşturma

```php
use App\Modules\Crm\Models\Kisi;
use App\Modules\Crm\Services\KisiService;

$kisiService = app(KisiService::class);
$kisi = $kisiService->create([
    'adi' => 'Ahmet Yılmaz',
    'telefon' => '05551234567',
    'email' => 'ahmet@example.com',
    'kisi_tipi' => 'musteri',
    // ...
]);
```

### Aktivite Ekleme

```php
use App\Modules\Crm\Models\Aktivite;

$aktivite = Aktivite::create([
    'kisi_id' => 1,
    'tip' => 'gorusme',
    'aciklama' => 'Telefon görüşmesi yapıldı',
    'tarih' => now(),
    // ...
]);
```

## 📊 Route'lar

- `GET /admin/kisiler` - Kişi listesi
- `GET /admin/kisiler/create` - Yeni kişi oluştur
- `GET /admin/aktiviteler` - Aktivite listesi
- `GET /admin/randevular` - Randevu listesi

## 🔧 Yapılandırma

Modül, `CrmServiceProvider` üzerinden yüklenir ve aşağıdaki servisleri singleton olarak kaydeder:

- `KisiService`
- `AktiviteService`
- `EtiketService`

## 📝 Notlar

- **Context7 Uyumluluk:** `musteri` yerine `kisi` kullanılır
- Kişi modeli, çoklu ilişki desteği sağlar (İlan, Talep, Randevu)
- Aktivite sistemi, otomatik loglama özelliği içerir
- Etiket sistemi, polymorphic ilişkiler kullanır

---

**Son Güncelleme:** 01 Aralık 2025
