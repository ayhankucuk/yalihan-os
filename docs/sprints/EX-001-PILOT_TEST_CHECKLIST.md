# EX-001 Pilot Test Checklist

> EX-001 Guest Communication Agent WAVE 1-2 tamamlandı. Pilot test öncesi kontrol listesi.

---

## Pilot Test Öncesi Kontroller

### 1. Environment

```bash
# Global flag OFF olmalı (test öncesi)
GUEST_COMMUNICATION_ENABLED=false

# Pilot mod açılacak (sadece tek tenant + tek property)
GUEST_PILOT_STRICT=true
GUEST_PILOT_TENANTS=1
GUEST_PILOT_PROPERTIES=101
```

### 2. Tenant/Property Seçimi

- [ ] Test için pilot tenant belirlendi
- [ ] Pilot property seçildi
- [ ] Tenant ID not edildi
- [ ] Property ID not edildi

### 3. Audit Log Hazırlığı

```bash
# Log kanalı açık mı?
tail -f storage/logs/guest_communication.log
```

### 4. Kill Switch

- [ ] Feature flag erişilebilir durumda
- [ ] Anlık kapatma mekanizması test edildi

---

## Pilot Test Senaryosu

### Adım 1: Feature Flag'i Aç

```bash
# .env.local veya config olarak
GUEST_COMMUNICATION_ENABLED=true
GUEST_AIRBNB_ENABLED=true
GUEST_WELCOME_ENABLED=true
```

### Adım 2: Event'i Tetikle

```php
// Tüm admin controller/service üzerinden değil, doğrudan event ile
event(new \App\Events\Reservation\ReservationConfirmedEvent(...));
```

### Adım 3: Log İzle

```bash
tail -f storage/logs/laravel.log | grep GuestCommunication
```

### Adım 4: Queue İşle

```bash
php artisan queue:work --once
```

---

## Başarı Kriterleri

| Kriter | Beklenen | Durum |
|---------|----------|--------|
| Event tetiklendi | ReservationConfirmedEvent log | ⏳ |
| Dil seçildi | TR/EN/AR | ⏳ |
| Queue işlendi | Job çalıştı | ⏳ |
| Adapter çağrıldı | AirbnbAdapter log | ⏳ |
| Audit yazıldı | guest_communication kanalı | ⏳ |
| Hata yok | Error log boş | ⏳ |

---

## Kill Switch Prosedürü

```bash
# Acil durdurma
GUEST_COMMUNICATION_ENABLED=false
php artisan config:clear

# Queue yeniden başlat
php artisan queue:restart
```

---

## Sonraki: Test Sonrası

- [ ] Pilot tenant/property allowlist'e göre filtre çalıştı
- [ ] Yanlış tenant'a gönderim engellendi
- [ ] Audit log temiz
- [ ] Sonuç: PASS / FAIL
