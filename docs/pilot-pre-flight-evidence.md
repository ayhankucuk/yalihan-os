# EX-001 + EX-002 — Pilot Pre-Flight Evidence

**Date:** 2026-08-07
**Prepared by:** WenOX (Execution Agent)
**Commit:** `1ce419b`
**Branch:** `feature/ex-002-finance-agent`

---

## Pilot Report Standard Format

> Bu format EX-001, EX-002 ve sonraki tüm pilotlar için zorunludur.

```
Pilot ID:        EX-001 / EX-002 / EX-003
Mission:          [Capability name]
Based On Commit:  [git commit hash]
Pilot Start:     [YYYY-MM-DD]
Pilot End:       [YYYY-MM-DD]
Status:          IN_PROGRESS / PASS / FAIL
Confidence:       HIGH / MEDIUM / LOW

Evidence:
  ☐ Runtime Logs reviewed
  ☐ Audit Records verified
  ☐ Queue Metrics checked
  ☐ Business Validation passed
  ☐ Manual = System % match

Result: PASS / FAIL
SAAB Decision: [Commit hash of SAAB decision]
```

---

## Özet

EX-001 (Guest Communication Agent) ve EX-002 (Finance Agent) pilota hazır hale getirildi. Bu belge, pilot aktivasyonu öncesi teknik doğrulama kanıtlarını içerir.

---

## Düzeltmeler (Pre-Flight)

### 1. Pilot Config Bug Fix — config/guest_communication.php

**Sorun:** `pilot.tenants` ve `pilot.properties` hardcoded boş array idi. `.env`'den `GUEST_PILOT_TENANTS` ve `GUEST_PILOT_PROPERTIES` değerleri okunmuyordu.

**Düzeltme:**
```php
'tenants' => array_filter(
    array_map('intval', explode(',', env('GUEST_PILOT_TENANTS', '')))
),
'properties' => array_filter(
    array_map('intval', explode(',', env('GUEST_PILOT_PROPERTIES', '')))
),
```

### 2. Log Kanalları — config/logging.php

**Sorun:** `guest_communication` ve `finance_agent` log kanalları tanımlı değildi.

**Düzeltme:** İki kanal eklendi:
- `guest_communication` → `storage/logs/guest_communication-*.log` (90 gün)
- `finance_agent` → `storage/logs/finance_agent-*.log` (365 gün)

---

## Doğrulama Sonuçları

### EX-001 Feature Flag Simülasyonu

| Kontrol | Sonuç |
|---------|-------|
| Kill switch (OFF) | ✅ DISABLED doğru çalışıyor |
| Pilot tenant 1 + property 101 | ✅ PASS |
| Non-pilot tenant 99 + property 101 | ✅ Blocked correctly |
| Pilot tenant 1 + non-pilot property 999 | ✅ Blocked correctly |
| Airbnb kanalı | ✅ ENABLED |
| Max retries | ✅ 3 |
| Retry backoff | ✅ 60s |
| Audit log yazıldı | ✅ |

**Simülasyon sonucu: 8/8 PASS**

### Log Kanalı Doğrulaması

```
[2026-08-07 08:27:20] production.INFO: EX-001 Pilot: Log kanalı açık {"test":true,...}
[2026-08-07 08:31:08] production.INFO: EX-001 Pilot Simulation {"step":"feature_flag_check","status":"PASS",...}
```

**Audit log: ✅ ACTIVE**

---

## Pilot Aktivasyon Talimatları

### EX-001 — Guest Communication Agent

Production `.env` dosyasına eklenecekler:

```env
# EX-001 Pilot Aktivasyonu
GUEST_COMMUNICATION_ENABLED=true
GUEST_AIRBNB_ENABLED=true
GUEST_WELCOME_ENABLED=true
GUEST_PILOT_STRICT=true
GUEST_PILOT_TENANTS=1
GUEST_PILOT_PROPERTIES=101
```

Ardından:
```bash
php artisan config:clear
php artisan queue:restart
tail -f storage/logs/guest_communication-$(date +%Y-%m-%d).log
```

### EX-002 — Finance Agent

Production `.env` dosyasına eklenecekler:

```env
# EX-002 Pilot Aktivasyonu
FINANCE_AGENT_ENABLED=true
FINANCE_AGENT_PILOT_STRICT=true
FINANCE_AGENT_PILOT_TENANTS=1
FINANCE_AGENT_AUTO_RECONCILE=false
FINANCE_AGENT_APPROVAL_REQUIRED=true
FINANCE_AGENT_DEFAULT_COMMISSION_RATE=10.0
```

Ardından:
```bash
php artisan migrate --path=database/migrations/2026_08_07_000001_create_finance_agent_tables.php
php artisan config:clear
tail -f storage/logs/finance_agent-$(date +%Y-%m-%d).log
```

---

## Kill Switch Prosedürü

```bash
# EX-001 acil durdurma
GUEST_COMMUNICATION_ENABLED=false
php artisan config:clear && php artisan queue:restart

# EX-002 acil durdurma
FINANCE_AGENT_ENABLED=false
php artisan config:clear
```

---

## Pilot Sırasında İzlenecek Log'lar

```bash
# EX-001
tail -f storage/logs/guest_communication-$(date +%Y-%m-%d).log

# EX-002
tail -f storage/logs/finance_agent-$(date +%Y-%m-%d).log

# Genel hata log'u
tail -f storage/logs/laravel.log | grep -E "ERROR|GuestCommunication|FinanceAgent"

# Queue
php artisan queue:work --once --verbose
```

---

## Production Certified Kapısı İçin Gerekli Kanıtlar

### EX-001

| Kanıt | Beklenen |
|-------|---------|
| İlk gerçek rezervasyon confirm'de welcome mesajı tetiklendi | ✅ |
| Dil seçimi doğru (TR/EN/AR) | ✅ |
| Airbnb adapter çağrıldı | ✅ |
| Audit log temiz | ✅ |
| Pilot dışı tenant'a gönderim olmadı | ✅ |

### EX-002

| Kanıt | Beklenen |
|-------|---------|
| İlk Airbnb payout import edildi | ✅ |
| Reconciliation doğru çalıştı | ✅ |
| Manuel hesap = sistem sonucu %100 | ✅ |
| Owner payout oluşturuldu | ✅ |
| Audit trail temiz | ✅ |

---

---

## Pilot Report — EX-001

```
Pilot ID:        EX-001
Mission:          Guest Communication Agent
Based On Commit:  [pending]
Pilot Start:      [pending]
Pilot End:        [pending]
Status:           IN_PROGRESS / PASS / FAIL
Confidence:        HIGH / MEDIUM / LOW

Evidence:
  ☐ Runtime Logs reviewed
  ☐ Audit Records verified
  ☐ Queue Metrics checked
  ☐ Business Validation passed
  ☐ Welcome message sent to real guest
  ☐ Airbnb adapter called
  ☐ Wrong tenant blocked correctly

Result: [PENDING — fill after pilot]
SAAB Decision: [commit hash — fill after SAAB review]
```

---

## Pilot Report — EX-002

```
Pilot ID:        EX-002
Mission:          Finance Agent
Based On Commit:  [pending]
Pilot Start:      [pending]
Pilot End:        [pending]
Status:           IN_PROGRESS / PASS / FAIL
Confidence:        HIGH / MEDIUM / LOW

Evidence:
  ☐ Runtime Logs reviewed
  ☐ Audit Records verified
  ☐ Queue Metrics checked
  ☐ Business Validation passed
  ☐ Airbnb payout imported
  ☐ Reconciliation completed
  ☐ Manual = System %100 match
  ☐ Owner payout prepared

Result: [PENDING — fill after pilot]
SAAB Decision: [commit hash — fill after SAAB review]
```

---

**Pre-Flight Status: ✅ READY FOR PILOT ACTIVATION**
