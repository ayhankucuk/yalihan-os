# 📈 Analitik Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-ANALITIK-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

Analitik modülü, dashboard'lar, istatistikler, raporlar ve Context7 uyumluluk analizi sağlar.

## 🎯 Sorumluluklar

- **Dashboard:** Genel sistem dashboard'u, metrikler
- **İstatistikler:** İlan, kişi, görev istatistikleri
- **Raporlar:** Detaylı raporlar, export işlemleri
- **Context7 Analizi:** Context7 uyumluluk analizi, trendler

## 📁 Yapı

```
Analitik/
├── Controllers/
│   ├── Admin/
│   │   ├── DashboardController.php    # Dashboard yönetimi
│   │   ├── IstatistikController.php   # İstatistik yönetimi
│   │   └── RaporController.php        # Rapor yönetimi
│   └── API/
│       ├── DashboardApiController.php # Dashboard API
│       ├── IstatistikApiController.php # İstatistik API
│       └── RaporApiController.php     # Rapor API
├── database/
│   ├── migrations/                    # Veritabanı migration'ları
│   └── Seeders/
│       └── AnalitikDatabaseSeeder.php # Seed data
├── routes/
│   ├── web.php                        # Web route'ları
│   └── api.php                        # API route'ları
└── Services/                          # Analitik servisleri
```

## 🔗 Bağımlılıklar

- **Emlak Modülü:** İlan istatistikleri için
- **Crm Modülü:** Kişi istatistikleri için
- **TakimYonetimi Modülü:** Görev istatistikleri için
- **Finans Modülü:** Finansal istatistikler için

## 🚀 Kullanım

### Dashboard Verileri

```php
use App\Modules\Analitik\Controllers\Admin\DashboardController;

$controller = app(DashboardController::class);
$data = $controller->getDashboardData();
```

### İstatistik Raporu

```php
use App\Modules\Analitik\Controllers\Admin\IstatistikController;

$controller = app(IstatistikController::class);
$stats = $controller->getIstatistikler();
```

## 📊 Route'lar

### Web Routes

- `GET /admin/analytics` - Genel analytics
- `GET /admin/analytics/dashboard` - Analytics dashboard
- `GET /admin/reports` - Raporlar

### API Routes

- `GET /api/analytics/dashboard` - Dashboard API
- `GET /api/analytics/istatistikler` - İstatistik API
- `GET /api/analytics/raporlar` - Rapor API

## 🔧 Yapılandırma

Modül, `AnalitikServiceProvider` üzerinden yüklenir ve `ModuleServiceProvider` tarafından kaydedilir.

## 📝 Notlar

- **Context7 Dashboard:** Context7 uyumluluk analizi ve trend grafikleri
- **Real-Time Metrikler:** Canlı sistem metrikleri ve performans göstergeleri
- **Export Özellikleri:** PDF, Excel export desteği
- **Caching:** Dashboard verileri cache'lenir (performans için)

---

**Son Güncelleme:** 01 Aralık 2025
