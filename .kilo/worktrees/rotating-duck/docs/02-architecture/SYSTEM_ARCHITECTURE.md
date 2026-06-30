# YALIHAN OS — İş Sistemleri Mimarisi

> Bu doküman, YALIHAN OS'nin dört temel iş sistemini mimari ve mühendis gözüyle açıklar.
> Her sistemin: varlıkları, ilişkileri, iş akışları ve AI entegrasyon noktaları belgelenir.

---

## SİSTEM 1: CRM — Müşteri İlişkileri Yönetimi

### 1.1 Temel Varlıklar

```
┌─────────────────────────────────────────────────────────┐
│                        MÜŞTERİ (Tenant/Client)           │
│  ├── Genel Bilgiler                                    │
│  │   ├── uuid, name, domain                            │
│  │   ├── email, telefon                                 │
│  │   └── aktiflik_durumu (aktif/pasif)                │
│  │                                                    │
│  ├── Abonelik (SaaS)                                  │
│  │   ├── plan (free/pro/enterprise)                    │
│  │   ├── başlangıç/bitiş tarihi                       │
│  │   ├── kredi bakiyesi (AI için)                     │
│  │   └── billing_geçmişi                              │
│  │                                                    │
│  └── Operasyonel Context                               │
│      ├── tenant_id (çoklu tenant desteği)              │
│      └── config (JSON — özelleştirmeler)               │
└─────────────────────────────────────────────────────────┘
```

### 1.2 Müşteri Kategorileri

| Tip | Açıklama | Örnek |
|-----|----------|-------|
| **Bireysel Sahip** | Kendi villası olan | Bodrum'da 1-2 villa |
| **Emlak Danışmanı** | Birden fazla villa yönetir | Portföy = 5-20 villa |
| **Acente** | Profesyonel yönetim şirketi | 20+ villa, ekip ile |

### 1.3 Kişi (Kişi) Varlığı

```
┌─────────────────────────────────────────────────────────┐
│                          KİŞİ (Person)                   │
│  ├── Kimlik                                            │
│  │   ├── uuid, ad, soyad                              │
│  │   ├── email, telefon                                 │
│  │   └── tip (mülk_sahibi | danışman | misafir | ...) │
│  │                                                    │
│  ├── Mülk Sahibi Bilgileri                            │
│  │   ├── mülkler (Villa[])                             │
│  │   ├── tercihler (detaylı)                           │
│  │   └── ödeme bilgileri                               │
│  │                                                    │
│  ├── İletişim Geçmişi                                  │
│  │   ├── notlar (AI ile analiz edilmiş)                │
│  │   ├── görüşmeler                                   │
│  │   └── mesajlar (Telegram/Email)                    │
│  │                                                    │
│  └── Etiketler                                        │
│      ├── segment (VIP/Standard/Potansiyel)             │
│      └── özellik_etiketleri []                         │
└─────────────────────────────────────────────────────────┘
```

### 1.4 CRM İş Akışları

#### Akış 1: Müşteri Edinme
```
Yeni Kişi Oluştur
    ↓
Kategori Belirle (Sahibi/Danışman/Misafir)
    ↓
İletişim Bilgileri Topla
    ↓
Etiketle ve Segment'e Ekle
    ↓
Hoşgeldin Notu / Otomatik Mesaj (Telegram)
    ↓
İlk Görüşme Planla
```

#### Akış 2: Mülk Sahibi Takibi
```
Villa Ekle
    ↓
Mülk Sahibini Bağla
    ↓
Hangi Platformlarda Yayınlanacak? (Airbnb/Booking/...)
    ↓
AI İlan Asistanı → İlan Oluştur
    ↓
İlan Yayında mı? → Takip Et
    ↓
Rezervasyon Geldi
    ↓
Gelir/Rapor → Sahibiye Bildirim
```

#### Akış 3: AI Destekli Müşteri Analizi
```
Kişi Notları + Mesajlar
    ↓
AI ile Analiz Et
├── Tercih Analizi (fiyat aralığı, lokasyon, özellikler)
├── Davranış Analizi (nasıl karar veriyor?)
└── Otomatik Not Oluştur
    ↓
Sonuç: "Bu müşteri fiyat odaklı değil, konum öncelikli"
```

### 1.5 AI Entegrasyonu

| AI Yeteneği | Kullanım Alanı |
|-------------|----------------|
| **AI Not Analizi** | Görüşme notlarından müşteri tercihlerini çıkar |
| **AI Mesaj Özetleme** | Uzun mesaj trafiğini özetle |
| **AI Kişi Segmentasyonu** | Müşteriyi otomatik segmente et |
| **AI Takvim Önerisi** | Müsait zamanları AI öner |
| **AI Sohbet Asistanı** | CRM'e doğal dilde sorgula |

### 1.6 Tablo Yapısı

```sql
kişiler
├── id, uuid
├── tenant_id
├── tip (mulk_sahibi | danisman | misafir | tedarikci)
├── ad, soyad
├── email, telefon
├── aktiflik_durumu
├── tercihler (JSON)
├── segment (VIP | Standard | Potansiyel)
├── son_gorusme_tarihi
├── olusturan_kullanici_id
├── created_at, updated_at
└── deleted_at

kisi_mulk_iliskileri
├── id
├── kisi_id
├── ilan_id (villa)
└── iliski_tipi (sahip | yonetici | ...)

kisi_notlar
├── id
├── kisi_id
├── kullanici_id
├── not_icerigi
├── ai_analiz_sonucu (JSON)
├── etiketler (JSON)
└── tarih

kisi_iletisim_gecmisi
├── id
├── kisi_id
├── yon (gelen | giden)
├── kanal (email | telegram | telefon | whatsapp)
├── icerik
├── tarih
└── ai_ozet (TEXT)
```

---

## SİSTEM 2: FİNANS — Gelir-Gider ve Ödeme Yönetimi

### 2.1 Temel Varlıklar

```
┌─────────────────────────────────────────────────────────┐
│                   FİNANS HİZMETİ (Finance)              │
│                                                          │
│  ┌──────────────────┐    ┌──────────────────────────┐   │
│  │  GELİR (Income)  │    │  GİDER (Expense)         │   │
│  │                  │    │                          │   │
│  │  - Rezervasyon   │    │  - Temizlik              │   │
│  │  - Depozito      │    │  - Bakım                 │   │
│  │  - Ek Hizmet     │    │  - Komisyon              │   │
│  │  - Gecikme Ücreti│    │  - AirBnb Fee            │   │
│  └──────────────────┘    │  - Vergi                 │   │
│           │              │  - Fatura                │   │
│           │              │  - Diğer                 │   │
│           ▼              └──────────────────────────┘   │
│  ┌──────────────────────────────────────────────────┐ │
│  │              ÖDEME KAYDI (PaymentRecord)             │ │
│  │  ├── amount (miktar)                               │ │
│  │  ├── currency (TL | EUR | USD)                      │ │
│  │  ├── status (bekliyor | odendi | iade)             │ │
│  │  ├── yontem (banka | kredi | nakit | havale)      │   │
│  │  └── tarih                                        │   │
│  └──────────────────────────────────────────────────┘ │
│                          │                              │
│                          ▼                              │
│  ┌──────────────────────────────────────────────────┐ │
│  │              FATURA (Invoice)                       │ │
│  │  ├── fatura_no                                     │ │
│  │  ├── tur (satış | alış | gider)                   │ │
│  │  ├── tarih                                        │ │
│  │  ├── vade_tarihi                                   │ │
│  │  ├── durum (özet | odendi | gecilmis)             │ │
│  │  └── kalemler (JSON)                              │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Gelir Kategorileri

| Gelir Tipi | Açıklama | Örnek |
|------------|----------|-------|
| **Rezervasyon Geliri** | Konaklama bedeli | 5 gece × 2000 TL |
| **Temizlik Ücreti** | Misafirden alınan | 500 TL |
| **Depozito** | Güvence bedeli (iade edilebilir) | 3000 TL |
| **Gecikme Ücreti** | Geç check-out | 500 TL |
| **Ek Hizmet Geliri** | Transfer, araç kiralama | 1000 TL |
| **Cezai Şart** | Sözleşme ihlali | 2000 TL |

### 2.3 Gider Kategorileri

| Gider Tipi | Açıklama | Otomatik? |
|------------|----------|-----------|
| **Airbnb/Booking Komisyonu** | Platform kesintisi | ✅ Evet (API) |
| **Temizlik** | Her checkout'ta | ✅ n8n otomasyonu |
| **Bakım/Onarım** | Planlı veya acil | ❌ Manuel |
| **Elektrik/Su/Internet** | Aylık faturalar | ❌ Manuel giriş |
| **Vergi** | Stopaj, KDV | ⚠️ Muhasebe entegrasyonu |
| **Sigorta** | Konut sigortası | ❌ Manuel |

### 2.4 Finans İş Akışları

#### Akış 1: Rezervasyon Geliri Kaydı
```
Rezervasyon Onaylandı
    ↓
Rezervasyon Detayları:
├── Villa
├── Tarih Aralığı
├── Gece Sayısı
├── Günlük Fiyat
├── Temizlik Ücreti
└── Toplam
    ↓
AI ile Gelir Projeksiyonu Hesapla
    ↓
Gelir Kaydı Oluştur (state: bekliyor)
    ↓
Ödeme Geldi Bildirimi
    ↓
Gelir Kaydını Güncelle (state: odendi)
    ↓
Kişiye Otomatik Makbuz / Bildirim
```

#### Akış 2: Gider Takibi
```
Checkout → Temizlik Bildirimi (n8n)
    ↓
Temizlik Tamamlandı
    ↓
Gider Kalemi Girildi:
├── Miktar
├── Kategori
├── Villa
├── Tedarikçi (Temizlik Şirketi)
└── Tarih
    ↓
Fatura / Fiş Ekle (görsel)
    ↓
Muhasebe Kaydı
    ↓
Raporla → Villa Karlılık
```

#### Akış 3: Mülk Sahibi Ödemesi
```
Ay Sonu → Kapanan Rezervasyonlar
    ↓
Gelir Topla (Rezervasyon gelirleri)
    ↓
Gider Topla (Temizlik, komisyon, bakım)
    ↓
Net Gelir Hesapla
    ↓
Mülk Sahibi Payını Hesapla
    ↓
Ödeme Talebi Oluştur
    ↓
Onay → Ödeme Yap
    ↓
Sahipliye Rapor Git (Telegram/Email)
```

### 2.5 AI Entegrasyonu

| AI Yeteneği | Kullanım Alanı |
|-------------|----------------|
| **AI Gelir Projeksiyonu** | Gelecek ay tahmini gelir |
| **AI Gider Analizi** | Anormal gider uyarısı |
| **AI Villa Karlılık** | Hangi villa daha karlı? |
| **AI Fatura Okuma** | PDF/fotoğraftan veri çıkar |
| **AI Ödeme Tahmini** | Ödeme gecikme riski |

### 2.6 Tablo Yapısı

```sql
finans_gelirler
├── id
├── tenant_id
├── villa_id
├── rezervasyon_id (nullable)
├── kategori (rezervasyon | temizlik | depozito | ek_hizmet | diger)
├── miktar
├── kur (TL | EUR | USD)
├── durum (bekliyor | odendi | iade)
├── odeme_yontemi
├── odeme_tarihi
├── aciklama
├── fatura_id (nullable)
└── created_at

finans_giderler
├── id
├── tenant_id
├── villa_id
├── kategori (temizlik | bakim | komisyon | fatura | vergi | diger)
├── tedarikci_id (nullable)
├── miktar
├── kur
├── durum (bekliyor | odendi)
├── odeme_tarihi
├── fatura_url
├── ai_analiz (JSON — gider kalemi analizi)
└── created_at

finans_odemeler
├── id
├── tenant_id
├── tur (gelir_odemesi | gider_odemesi | sahip_odemesi)
├── ilgili_id (gelir_id | gider_id | kisi_id)
├── miktar
├── yontem (banka | nakit | kredi_karti)
├── referans_no
├── tarih
└── created_at

sahip_odemeleri
├── id
├── tenant_id
├── kisi_id (mulk_sahibi)
├── donem (2026-06)
├── toplam_gelir
├── toplam_gider
├── net_tutar
├── durum (hazirlandi | onaylandi | odendi)
├── odeme_tarihi
├── notlar
└── created_at
```

---

## SİSTEM 3: TAKVİM — Zamanlama ve Rezervasyon Yönetimi

### 3.1 Temel Varlıklar

```
┌─────────────────────────────────────────────────────────┐
│                     TAKVİM (Calendar)                    │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │                 REZERVASYON (Reservation)           │  │
│  │  ├── uuid                                         │  │
│  │  ├── villa_id                                     │  │
│  │  ├── kisi_id (misafir)                            │  │
│  │  ├── giris_tarihi                                 │  │
│  │  ├── cikis_tarihi                                 │  │
│  │  ├── gece_sayisi                                  │  │
│  │  ├── durum                                        │  │
│  │  │    (onay_bekliyor | onaylandi | iptal | tamamlandi) │
│  │  ├── kaynak (airbnb | booking | manuel | telefon) │  │
│  │  ├── toplam_tutar                                 │  │
│  │  ├── depozito                                     │  │
│  │  └── ai_analiz (JSON — kalabalık/verimlilik)     │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │                 TAKVİM_ETKİNLİĞİ (Event)           │  │
│  │  ├── tur (temizlik | bakim | kontrol | toplanti) │  │
│  │  ├── villa_id                                     │  │
│  │  ├── baslangic                                   │  │
│  │  ├── bitis                                       │  │
│  │  ├── ilgili_kisi_ids []                          │  │
│  │  ├── durum (planlandi | tamamlandi | iptal)     │  │
│  │  └── notlar                                       │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Rezervasyon Durumları

| Durum | Açıklama | AI Kullanımı |
|-------|----------|---------------|
| **onay_bekliyor** | İstek geldi, onay bekliyor | AI → "Bu misafir güvenilir mi?" |
| **onaylandi** | Ödeme bekleniyor veya alındı | AI → Check-in hazırlığı başlat |
| **checkin_yapildi** | Misafir villa'da | AI → Günlük bildirimler |
| **iptal** | İptal edildi | AI → İptal nedeni analizi |
| **tamamlandi** | Misafir ayrıldı | AI → Cleanup talimatları |

### 3.3 Takvim İş Akışları

#### Akış 1: Airbnb'den Rezervasyon
```
Airbnb Webhook (n8n)
    ↓
Yeni Rezervasyon Verisi
├── Misafir Bilgileri
├── Tarihler
├── Tutar
└── Mesaj
    ↓
AI ile Validasyon
├── Tarih çakışması var mı?
├── Fiyat doğru mu?
└── Mesaj analizi (özel istekler)
    ↓
Rezervasyon Oluştur (onay_bekliyor)
    ↓
Villa Kullanılabilirlik Kontrolü
    ↓
Mülk Sahibine Bildirim (Telegram)
    ↓
Onay Bekle
    ↓
Onay → Takvime Ekle + Airbnb'ye Sync
```

#### Akış 2: Check-in Otomasyonu
```
Rezervasyon Onaylandı
    ↓
T-3 Gün: Misafire Hatırlatma (Telegram)
    ↓
T-1 Gün: Check-in Detayları Gönder
├── Villa Adresi
├── Kilit Kodu
├── Wifi Şifresi
└── Önemli Bilgiler
    ↓
T-0 Gün (Check-in Günü)
├── Kilit Kodu Aktif
├── Villa Kontrol (gerekirse)
└── Misafire Hoş Geldin Mesajı
```

#### Akış 3: Temizlik Planlama
```
Rezervasyon Çıkış Tarihi Belirlendi
    ↓
AI otomatik Temizlik Etkinliği Oluştur
├── Tarih: Çıkış + 1 (veya aynı gün)
├── Süre: 3-5 saat (villa boyutuna göre)
├── Malzeme: Standart listeye eklemeler
└── Kişi: Temizlik ekibi / Ekip
    ↓
Telegram ile Temizlik Ekibine Bildirim
    ↓
Temizlik Tamam → Kontrol Etkinliği Oluştur
    ↓
Kontrol Tamam → Bir Sonraki Rezervasyona Hazır
```

### 3.4 AI Entegrasyonu

| AI Yeteneği | Kullanım Alanı |
|-------------|----------------|
| **AI Takvim Önerisi** | "Bu tarihte 3 gün boş, fiyatı düşür" |
| **AI Fiyat Optimizasyonu** | Rakip analizi → dinamik fiyat |
| **AI Mesaj Analizi** | Airbnb mesajlarından özel istekleri çıkar |
| **AI Temizlik Planlama** | Çıkış + optimal temizlik zamanı |
| **AI Doluluk Analizi** | Doluluk oranı, trend, tahmin |

### 3.5 Tablo Yapısı

```sql
rezervasyonlar
├── id
├── uuid
├── tenant_id
├── villa_id
├── kisi_id (misafir)
├── mülk_sahibi_id
├── giris_tarihi
├── cikis_tarihi
├── gece_sayisi
├── durum (onay_bekliyor | onaylandi | iptal | tamamlandi)
├── kaynak (airbnb | booking | manuel | telefon | websitesi)
├── toplam_tutar
├── kur
├── depozito
├── odeme_durumu (odenmedi | kismi | tam | iade)
├── airbnb_rezervasyon_id (nullable)
├── airbnb_urun_kodu
├── misafir_sayisi
├── ai_analiz (JSON)
├── ozel_notlar (TEXT)
└── created_at

takvim_etkinlikleri
├── id
├── uuid
├── tenant_id
├── villa_id
├── tur (temizlik | bakim | kontrol | toplanti | checkin | diger)
├── baslangic_tarihi
├── bitis_tarihi
├── ilgili_rezervasyon_id (nullable)
├── ilgili_kisi_ids (JSON)
├── durum (planlandi | tamamlandi | iptal)
├── tamamlama_notlari (TEXT)
├── atanan_kullanici_id
├── ai_onerilen (boolean)
└── created_at

villa_takvimleri
├── id
├── villa_id
├── tarih
├── durum (bos | dolu | bakim | rezerve_edildi)
├── rezervasyon_id (nullable)
├── notlar
└── created_at
```

---

## SİSTEM 4: YAZLIK KİRALAMA — Villa Operasyon Yönetimi

### 4.1 Temel Varlıklar

```
┌─────────────────────────────────────────────────────────┐
│          YAZLIK KİRALAMA (Villa Rental Operations)     │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │                     VİLLA (Listing)                │  │
│  │  ├── Kimlik                                       │  │
│  │  │   ├── uuid, baslik                            │  │
│  │  │   ├── il, ilce, mahalle                       │  │
│  │  │   ├── lat, lng (koordinatlar)                 │  │
│  │  │   └── aktiflik_durumu                         │  │
│  │  │                                                │  │
│  │  ├── Detaylar                                    │  │
│  │  │   ├── kategori (villa | apart | butik)        │  │
│  │  │   ├── kapasite (kişi sayısı)                  │  │
│  │  │   ├── oda_sayisi                              │  │
│  │  │   ├── yatak_odalari                           │  │
│  │  │   ├── banyo_sayisi                            │  │
│  │  │   └── alan (m²)                               │  │
│  │  │                                                │  │
│  │  ├── Özellikler (JSON / İlişkili Tablo)         │  │
│  │  │   ├── havuz, jakuzi, sauna                    │  │
│  │  │   ├── klima, wifi, tv                         │  │
│  │  │   └── denize_yakinlik (m)                     │  │
│  │  │                                                │  │
│  │  ├── Fiyatlandırma                              │  │
│  │  │   ├── yaz_sezonu_gunluk (TL)                 │  │
│  │  │   ├── kis_sezonu_gunluk (TL)                  │  │
│  │  │   ├── temizlik_ucreti (TL)                   │  │
│  │  │   └── minumum_konaklama (gece)                │  │
│  │  │                                                │  │
│  │  ├── Medya                                       │  │
│  │  │   ├── kapak_resmi                             │  │
│  │  │   ├── resimler []                             │  │
│  │  │   └── videolar []                             │  │
│  │  │                                                │  │
│  │  ├── AI Analiz                                   │  │
│  │  │   ├── eksik_bilgiler []                       │  │
│  │  │   ├── ai_aciklama (TEXT)                      │  │
│  │  │   ├── fiyat_analiz (JSON)                     │  │
│  │  │   └── rekabet_analizi (JSON)                  │  │
│  │  │                                                │  │
│  │  └── Platform Durumu                              │  │
│  │      ├── airbnb_ilan_id                          │  │
│  │      ├── booking_ilan_id                         │  │
│  │      └── websitesi_yayinda_mi                     │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │                 VİLLA YÖNETİM (Operations)        │  │
│  │  ├── Bakım Talepleri                             │  │
│  │  ├── Arıza Bildirimleri                          │  │
│  │  ├── Kontrol Listeleri (Check-in / Check-out)   │  │
│  │  └── Malzeme/Stok Yönetimi                       │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 4.2 Villa Durumları

| Durum | Açıklama | Takvim Rengi |
|-------|----------|-------------|
| **aktif** | Kiralama için uygun | Yeşil |
| **bakimda** | Bakım/ tadilat var | Sarı |
| **pasif** | Kiralanmıyor | Gri |
| **satilik** | Satışta (kiralama yok) | Kırmızı |

### 4.3 Yazlık Kiralama İş Akışları

#### Akış 1: Yeni Villa Ekleme
```
Villa Bilgileri Girişi
├── Temel bilgiler (ad, adres, kapasite)
├── Özellikler (havuz, klima, vs.)
├── Mevcut Fotoğraflar
└── Mülk Sahibi Bağlantısı
    ↓
AI ile Analiz
├── Eksik bilgi var mı?
├── Fotoğraf kalitesi yeterli mi?
├── Fiyatlandırma önerisi
└── Rekabet analizi (bölgedeki diğer villalar)
    ↓
Eksikler Düzeltildi
    ↓
AI Airbnb Açıklaması Oluştur
├── Türkçe + İngilizce
├── SEO optimize
└── Öne çıkan özellikler
    ↓
Platformlara Yayınla
├── Airbnb
├── Booking
├── Türkiye'deki platformlar
└── Web sitesi
```

#### Akış 2: Günlük Villa Operasyonu
```
Sabah: Bugünkü Etkinlikleri Gör
├── Check-in var mı? (tarih = bugün)
├── Check-out var mı?
└── Temizlik planlandı mı?
    ↓
Checkout Sonrası Kontrol
├── Temizlik tamam mı?
├── Bir hasar var mı?
├── Eksik malzeme var mı?
    ↓
Hasar Tespit Edildi
├── Fotoğraf / Video kaydet
├── Mülk Sahibine Bildirim
├── Hasar Tutanağı Oluştur
└── Depozito'dan düşüş planı
    ↓
Bir Sonraki Rezervasyon Hazırlığı
├── Malzeme kontrol
├── Temizlik (gerekirse)
└── Check-in paketi hazırla
```

#### Akış 3: AI Destekli İlan Yönetimi
```
AI Haftalık / Aylık Analiz
├── Hangi villalar az görüntüleniyor?
├── Fiyat rekabetçi mi?
├── Eksik bilgi nedeniyle kayıp var mı?
└── "Bu villanın 3 yeni fotoğrafı olmalı" önerisi
    ↓
AI Önerileri Gözden Geçir
├── Uygulanabilir mi?
├── zaman / maliyet ne?
└── Öncelik Belirle
    ↓
AI ile Açıklama Güncelle
    ↓
Sonuç: Daha fazla görüntülenme, daha fazla rezervasyon
```

### 4.4 AI Entegrasyonu

| AI Yeteneği | Kullanım Alanı |
|-------------|----------------|
| **AI İlan Oluşturucu** | Fotoğraftan ilan taslağı |
| **AI Eksik Tespiti** | Hangi bilgi/fotoğraf eksik? |
| **AI Fiyat Önerisi** | Rakamlara göre optimum fiyat |
| **AI Açıklama Yazma** | Airbnb/Booking için çekici metin |
| **AI Hasar Tespiti** | Fotoğraftan hasar analizi |
| **AI Rakip Analizi** | Bölgedeki fiyatlar, özellikler |

### 4.5 Tablo Yapısı

```sql
-- İlanlar (Villalar)
ilanlar
├── id
├── uuid
├── tenant_id
├── baslik
├── slug
├── ilan_tipi (villa | apart | butik | site_ic)
├── durum (yayinda | yayinda_degil | pasif)
├── yayin_durumu (yayinda | taslak | bekliyor)

-- Konum
├── il_id, il_adi
├── ilce_id, ilce_adi
├── mahalle
├── adres_detay
├── lat, lng

-- Kapasite
├── kapasite (kişi)
├── oda_sayisi
├── yatak_odalari
├── banyo_sayisi
├── alan_m2

-- Fiyatlandırma
├── yaz_sezonu_gunluk
├── kis_sezonu_gunluk
├── dongu_sezonu_gunluk
├── temizlik_ucreti
├── depozito
├── minumum_konaklama_gece

-- AI Alanları
├── ai_aciklama_tr (TEXT)
├── ai_aciklama_en (TEXT)
├── ai_seo_basligi (TEXT)
├── ai_fiyat_analiz (JSON)
├── ai_eksik_bilgiler (JSON)
├── ai_rekabet_analiz (JSON)

-- Platform ID'leri
├── airbnb_ilan_id
├── booking_ilan_id
├── TRT_ilan_id

-- Medya
├── kapak_resmi_id
├── resimler (JSON array)
├── videolar (JSON array)

-- İlişkiler
├── sahip_id (kisi_id)
├── olusturan_kullanici_id

-- Timestamps
├── yayin_tarihi
├── created_at, updated_at
└── deleted_at

-- Özellik Pivot
ilan_ozellikler
├── ilan_id
├── ozellik_id
└── deger (boolean | string | number)

ozellikler
├── id
├── kategori
├── ad
├── tip (boolean | select | number)
└── birim (nullable)
```

---

## BÜTÜNLEŞTİRME: Sistemler Arası İlişkiler

```
                    ┌──────────────────────────────────────────┐
                    │           YALIHAN OS — Tam Sistem         │
                    └──────────────────────────────────────────┘

                    ┌──────────────────────────────────────────┐
                    │                   CRM                     │
                    │  Kişi ←→ Mülk Sahibi ←→ İletişim        │
                    └────────────────────┬─────────────────────┘
                                         │
              ┌──────────────────────────┼──────────────────────────┐
              │                          │                          │
              ▼                          ▼                          ▼
    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
    │    FİNANS        │    │   TAKVİM        │    │  YAZLIK KİRA.  │
    │                 │    │                 │    │                 │
    │ Gelir ← Villa   │    │ Rezervasyon ←→Villa│   │ Villa ← Sahip   │
    │ Gider ← Villa   │    │ Etkinlik ←→Villa  │    │ İlan ← Platform │
    │ Ödeme ← Kişi    │    │ Takvim ←→ Kişi    │    │ Operasyon ←→CRM │
    └─────────────────┘    └─────────────────┘    └─────────────────┘
              │                    │                    │
              │                    │                    │
              └────────────────────┼────────────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────────────────┐
                    │         AI ORCHESTRATOR                 │
                    │                                          │
                    │  AI İlan Asistanı                        │
                    │  AI Müşteri Analizi                      │
                    │  AI Fiyat Optimizasyonu                  │
                    │  AI Takvim Önerisi                       │
                    │  AI Hasar Tespiti                        │
                    └──────────────────────────────────────────┘
                                   │
              ┌─────────────────────┼─────────────────────┐
              │                     │                     │
              ▼                     ▼                     ▼
    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
    │      n8n         │    │    Telegram     │    │   NotebookLM     │
    │  (Otomasyon)      │    │  (Bildirim)     │    │  (Bilgi Saklı)  │
    └─────────────────┘    └─────────────────┘    └─────────────────┘
```

---

## AI YETENEKLERİ ÖZETİ

| Sistem | AI Yeteneği | Değer |
|--------|-------------|-------|
| **CRM** | Müşteri analizi, segmentasyon | Müşteri kaybı önleme |
| **Finans** | Gelir/gider tahmini | Karlılık artışı |
| **Takvim** | Fiyat optimizasyonu | Doluluk artışı |
| **Yazlık** | İlan oluşturma, açıklama | Daha fazla rezervasyon |

---

## TEKNOLOJİ ENTEGRASYON MATRİSİ

| Platform | Tür | Kullanım |
|----------|-----|----------|
| Airbnb | Kanal Yöneticisi | Rezervasyon sync, mesajlaşma |
| Booking.com | Kanal Yöneticisi | Rezervasyon sync |
| Türkiye platformları | Kanal Yöneticisi | (gelecek) |
| n8n | Otomasyon | Check-in bildirimleri, temizlik planlama |
| Telegram | Bildirim | Anlık bildirimler, raporlar |
| NotebookLM | Bilgi Saklama | Villa bilgileri, müşteri notları |
| OpenClaw | Agent Framework | (gelecek) Gelişmiş otomasyon |
| Google Workspace | Email, Calendar | (gelecek) |

---

## MİMARİ NOTLAR

### Tek Write Authority
Tüm DB write işlemleri: `IlanCrudService` üzerinden. Bu kural SAB tarafından zorunlu kılınmıştır.

### Tenant Isolation
Tüm sorgular `tenant_id` scope'unda çalışır. Cross-tenant erişim kesinlikle yasaktır.

### AI Pipeline
```
Kullanıcı İsteği
       ↓
AI Orchestrator
       ↓
Cortex Agent Seçimi
       ↓
DeepSeek / OpenAI / Ollama
       ↓
Sonuç + Analiz
       ↓
Kullanıcıya Dönüş
```

---

*Son güncelleme: 2026-06-27*
*Oturum: 44*
*Durum: Mimari dokümantasyon tamamlandı*
