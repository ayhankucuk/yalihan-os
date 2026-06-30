# FEATURE: Telegram Outbound Service Implementation [SAB PROBABLY]

## 🏗 Mimari Plan

Bu plan, Yalıhan Emlak platformu için merkezi bir Telegram bildirim servisi (`TelegramOutboundService`) kurulumunu içerir. Sistem SAB ve Context7 standartlarına %100 uyumlu olacaktır.

## 🔒 SAB Kuralları Uyumu

- **Status Yasağı:** `gonderim_durumu` (tinyint) kullanılacak.
- **Aktif Yasağı:** `aktiflik_durumu` kullanılacak.
- **Service Layer:** Tüm Telegram API çağrıları `TelegramOutboundService` üzerinden soyutlanacak.
- **Audit:** Her bildirim `audit_logs` veya özel bir tabloya işlenecek.
- **Country-Aware:** Bildirimler `ulke_id` bazlı loglanacak.

## 🛠 Değişiklikler

### 1. Database & Models

#### [NEW] `telegram_notifications`

- `id`, `ulke_id`, `user_id` (alıcı), `mesaj_tipi`, `icerik`, `gonderim_durumu`, `hata_mesaji`, `deneme_sayisi`.

### 2. Service Layer

#### [NEW] `App\Services\Notification\TelegramOutboundService`

- `sendMessageToUser(User $user, string $text)`
- `sendLeadNotification(Lead $lead)`
- `sendReservationReminder(PropertyReservation $reservation)`

### 3. Asynchronous Jobs

#### [NEW] `App\Jobs\TelegramOutboundJob`

- Rate-limiting (SAB: Queue Stress logic) uyumlu gönderim.

## 🧪 Test Planı

- `php artisan test tests/Feature/TelegramOutboundTest.php`
- iCal ve CRM entegrasyonu simülasyonu.

**Mühürlendi: Telegram Outbound Architecture PROPOSED.**
