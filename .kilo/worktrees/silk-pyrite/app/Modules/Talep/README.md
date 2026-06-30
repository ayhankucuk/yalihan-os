# 📋 Talep Modülü

**Versiyon:** 1.0.0  
**Context7 Standardı:** C7-TALEP-MODULE-2025-12-01  
**Durum:** ✅ Aktif

---

## 📋 Genel Bakış

Talep modülü, müşteri talepleri, ilan-talep eşleştirmeleri ve AI destekli talep analizi sağlar.

## 🎯 Sorumluluklar

- **Talep Yönetimi:** Müşteri taleplerinin oluşturulması, takibi
- **Eşleştirme:** İlan-Talep otomatik eşleştirme (SmartPropertyMatcherAI)
- **AI Analiz:** Talep analizi, skorlama, önceliklendirme

## 📁 Yapı

```
Talep/
├── Models/
│   ├── IlanTalepEslesme.php          # İlan-Talep eşleştirme modeli
│   └── TalepAnaliz.php               # Talep analiz modeli
├── Services/
│   └── AIAnalizService.php           # AI analiz servisi
├── routes/
│   ├── web.php                       # Web route'ları
│   └── api.php                       # API route'ları
├── Config/
│   └── talep.php                     # Talep yapılandırması
└── Database/
    └── Migrations/                   # Veritabanı migration'ları
```

## 🔗 Bağımlılıklar

- **Emlak Modülü:** İlan modeli için
- **Crm Modülü:** Kişi (Talep sahibi) modeli için
- **Cortex Modülü:** SmartPropertyMatcherAI için
- **AI Services:** Talep analizi için

## 🚀 Kullanım

### Talep Oluşturma

```php
use App\Models\Talep;

$talep = Talep::create([
    'kisi_id' => 1,
    'kategori' => 'daire',
    'min_fiyat' => 500000,
    'max_fiyat' => 2000000,
    'lokasyon' => 'Bodrum',
    // ...
]);
```

### AI Eşleştirme

```php
use App\Modules\Cortex\Services\SmartPropertyMatcherAI;

$matcher = app(SmartPropertyMatcherAI::class);
$matches = $matcher->findMatches($talep);
```

## 📊 Route'lar

- `GET /admin/talepler` - Talep listesi
- `GET /admin/talepler/create` - Yeni talep oluştur
- `GET /admin/eslesmeler` - Eşleştirme listesi
- `GET /admin/talep-portfolyo` - Talep-Portföy görünümü

## 🔧 Yapılandırma

Modül, `TalepServiceProvider` üzerinden yüklenir ve `ModuleServiceProvider` tarafından kaydedilir.

## 📝 Notlar

- **Smart Matching:** AI destekli otomatik ilan-talep eşleştirme
- **Skorlama Sistemi:** Eşleştirme skorları (0-100) hesaplanır
- **Urgency Levels:** ACİL, YÜKSEK, ORTA, DÜŞÜK öncelik seviyeleri
- **Telegram Entegrasyonu:** Yüksek skorlu eşleştirmeler Telegram'a bildirilir

---

**Son Güncelleme:** 01 Aralık 2025
