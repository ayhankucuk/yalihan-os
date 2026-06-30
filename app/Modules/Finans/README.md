# 💰 Finans Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-FINANS-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

Finans modülü, finansal işlemler, komisyon yönetimi ve AI destekli finansal analiz sağlar.

## 🎯 Sorumluluklar

- **Finansal İşlemler:** Gelir, gider, transfer işlemleri
- **Komisyon Yönetimi:** Satış komisyonları, ödeme takibi
- **AI Analiz:** Finansal risk analizi, tahmin, özet üretimi

## 📁 Yapı

```
Finans/
├── Controllers/
│   ├── FinansalIslemController.php    # Finansal işlem yönetimi
│   └── KomisyonController.php         # Komisyon yönetimi
├── Models/
│   ├── FinansalIslem.php              # Finansal işlem modeli
│   └── Komisyon.php                   # Komisyon modeli
├── Services/
│   ├── FinansService.php              # Finansal iş mantığı
│   └── KomisyonService.php            # Komisyon iş mantığı
├── routes/
│   ├── web.php                        # Web route'ları
│   └── api.php                        # API route'ları (AI endpoints)
└── database/
    └── migrations/                   # Veritabanı migration'ları
```

## 🔗 Bağımlılıklar

- **Emlak Modülü:** İlan-Komisyon ilişkileri için
- **Crm Modülü:** Kişi-Finansal işlem ilişkileri için
- **Auth Modülü:** Kullanıcı (User) ilişkileri için
- **AI Services:** Finansal analiz için

## 🚀 Kullanım

### Finansal İşlem Oluşturma

```php
use App\Modules\Finans\Models\FinansalIslem;

$islem = FinansalIslem::create([
    'tip' => 'gelir',
    'tutar' => 50000,
    'aciklama' => 'Satış komisyonu',
    'tarih' => now(),
    // ...
]);
```

### AI Finansal Analiz

```php
use App\Modules\Finans\Controllers\FinansalIslemController;

// API endpoint: POST /api/finans/islemler/ai/analyze
$response = Http::post('/api/finans/islemler/ai/analyze', [
    'islem_id' => 1,
    'analiz_tipi' => 'risk',
]);
```

## 📊 Route'lar

### Web Routes

- `GET /admin/finans/islemler` - Finansal işlem listesi
- `GET /admin/finans/islemler/create` - Yeni işlem oluştur
- `GET /admin/finans/komisyonlar` - Komisyon listesi

### API Routes (AI-Powered)

- `POST /api/finans/islemler/ai/analyze` - Finansal analiz
- `POST /api/finans/islemler/ai/predict` - Gelir tahmini
- `POST /api/finans/islemler/ai/risk` - Risk analizi
- `POST /api/finans/islemler/ai/summary` - Özet üretimi

## 🔧 Yapılandırma

Modül, `FinansServiceProvider` üzerinden yüklenir ve `ModuleServiceProvider` tarafından kaydedilir.

## 📝 Notlar

- **AI Entegrasyonu:** Finansal analiz için AI servisleri kullanılır
- **Onay Sistemi:** Finansal işlemler için onay workflow'u mevcuttur
- **Komisyon Hesaplama:** Otomatik komisyon hesaplama algoritması içerir
- **Raporlama:** Finansal raporlar ve özetler üretilir

---

**Son Güncelleme:** 01 Aralık 2025
