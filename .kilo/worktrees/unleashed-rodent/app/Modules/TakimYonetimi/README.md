# 👥 Takım Yönetimi Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-TAKIM-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

Takım Yönetimi modülü, görev yönetimi, takım üyeleri, performans takibi ve Telegram bot entegrasyonu sağlar.

## 🎯 Sorumluluklar

- **Görev Yönetimi:** Görev oluşturma, atama, takip, tamamlama
- **Takım Üyeleri:** Takım üyesi yönetimi, yetkilendirme
- **Performans Takibi:** Görev performansı, istatistikler
- **Telegram Bot:** Telegram üzerinden görev yönetimi, bildirimler

## 📁 Yapı

```
TakimYonetimi/
├── Controllers/
│   ├── Admin/
│   │   ├── TakimController.php        # Takım yönetimi
│   │   ├── GorevController.php       # Görev yönetimi
│   │   └── PerformansController.php   # Performans yönetimi
│   └── API/
│       ├── TakimApiController.php     # Takım API
│       └── GorevApiController.php      # Görev API
├── Models/
│   ├── Gorev.php                      # Görev modeli
│   ├── GorevTakip.php                 # Görev takip modeli
│   ├── GorevDosya.php                 # Görev dosya modeli
│   ├── TakimUyesi.php                 # Takım üyesi modeli
│   └── Proje.php                      # Proje modeli
├── Services/
│   ├── TelegramBotService.php         # Telegram bot servisi
│   └── GorevYonetimService.php        # Görev yönetim servisi
├── Policies/                          # Yetkilendirme policy'leri
├── routes/
│   ├── web.php                        # Web route'ları
│   └── api.php                        # API route'ları
└── Migrations/                        # Veritabanı migration'ları
```

## 🔗 Bağımlılıklar

- **Auth Modülü:** Kullanıcı (User) ilişkileri için
- **Crm Modülü:** Kişi-Görev ilişkileri için
- **Emlak Modülü:** İlan-Görev ilişkileri için
- **AI Services:** Voice-to-CRM, Telegram bot için

## 🚀 Kullanım

### Görev Oluşturma

```php
use App\Modules\TakimYonetimi\Models\Gorev;

$gorev = Gorev::create([
    'baslik' => 'Müşteri görüşmesi',
    'aciklama' => 'Ahmet Bey ile görüşme yapılacak',
    'atanan_user_id' => 1,
    'bitis_tarihi' => now()->addDays(7),
    'oncelik' => 'yuksek',
    // ...
]);
```

### Telegram Bot Kullanımı

```php
use App\Modules\TakimYonetimi\Services\TelegramBotService;

$telegramService = app(TelegramBotService::class);
$telegramService->sendMessage($chatId, 'Görev tamamlandı!');
```

## 📊 Route'lar

### Web Routes

- `GET /admin/takim-yonetimi/takim` - Takım üyeleri
- `GET /admin/takim-yonetimi/gorevler` - Görev listesi
- `GET /admin/takim-yonetimi/takim/performans` - Performans raporu

### API Routes

- `GET /api/takim-yonetimi/gorevler` - Görev API
- `POST /api/takim-yonetimi/gorevler` - Görev oluştur
- `PUT /api/takim-yonetimi/gorevler/{id}` - Görev güncelle

## 🔧 Yapılandırma

Modül, `TakimYonetimiServiceProvider` üzerinden yüklenir ve aşağıdaki servisleri kaydeder:

- `takim.gorev` → `GorevYonetimService`
- `takim.telegram` → `TelegramBotService`
- `takim.context7` → `Context7AIService`

## 📝 Notlar

- **Telegram Entegrasyonu:** Voice-to-CRM, görev yönetimi, bildirimler
- **Policy Sistemi:** Görev yetkilendirme için Laravel Policy'leri kullanılır
- **Performans Metrikleri:** Görev tamamlama oranı, süre analizi
- **Dosya Yönetimi:** Görevlere dosya ekleme özelliği

---

**Son Güncelleme:** 01 Aralık 2025
