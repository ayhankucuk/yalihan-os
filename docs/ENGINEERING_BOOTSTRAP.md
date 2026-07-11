# YALIHAN OS Engineering Bootstrap

> Otomatik geliştirme ortamı kurulum sistemi

## Genel Bakış

YALIHAN OS Engineering Bootstrap, her yeni Kilo worktree için geliştirme ortamını tamamen otomatik olarak yapılandırır.

## Mimari

```
.kilo/setup-script
├── Phase 1: Environment Bootstrap
│   ├── .env kontrolü/kopyalama
│   ├── APP_KEY kontrolü/üretimi
│   ├── Storage klasörleri
│   ├── composer install
│   ├── npm install
│   ├── storage:link
│   └── optimize:clear
├── Phase 2: Repository Health
│   └── php artisan bekci:health --detailed
├── Phase 3: Architecture Validation
│   └── php artisan sab:integrity-scan
├── Phase 4: Migration Validation
│   └── php artisan migrate:status
├── Phase 5: Smoke Tests
│   └── php artisan test --filter=WizardSchemaStep2Test
├── Phase 6: Performance Metrics
└── Phase 7: Summary Report
```

## Durum Kodları

| Kod | Anlam |
|-----|-------|
| PASS | Başarılı |
| WARN | Uyarı (devam ediyor) |
| FAIL | Başarısız (durdu) |

## Kural Seti

### Idempotency
Bootstrap birden fazla kez çalıştırılabilir. Her çalıştırma güvenli ve deterministik sonuç üretir.

### Database Bağımsızlığı
Database erişilemiyorsa `WARN` verir, `FAIL` vermez.

### Smoke Test Toleransı
Smoke test başarısız olursa `WARN` verir, bootstrap devam eder.

### Log Rotasyonu
Log dosyaları otomatik olarak son 100 satıra kırpılır.

## Log Dosyaları

| Dosya | İçerik |
|-------|--------|
| `storage/logs/bootstrap-health.log` | bekci:health çıktısı |
| `storage/logs/bootstrap-integrity.log` | sab:integrity-scan çıktısı |
| `storage/logs/bootstrap-migrations.log` | migrate:status çıktısı |
| `storage/logs/bootstrap-smoke.log` | Test sonuçları |

## Süreç Metrikleri

| Metrik | Açıklama |
|--------|----------|
| Bootstrap Duration | Toplam bootstrap süresi |
| Composer Duration | Composer install süresi |
| NPM Duration | npm install süresi |
| Laravel Duration | Artisan komutları süresi |
| Tests Duration | Smoke test süresi |

## Environment Variables

| Değişken | Açıklama |
|----------|----------|
| `WORKTREE_PATH` | Worktree dizini (varsayılan: `.`) |
| `REPO_PATH` | Ana repo dizini (varsayılan: `..`) |

## Örnek Çıktı

```
====================================
YALIHAN ENGINEERING BOOTSTRAP
====================================

Environment        PASS
Laravel           PASS
Database          WARN
Architecture      PASS
Smoke Tests       PASS

Bootstrap Duration: 45s
Composer Duration: 12s
NPM Duration:      8s
Laravel Duration:  10s
Tests Duration:    15s

====================================
YALIHAN OS worktree bootstrap complete.
====================================
```

## Test Komutları

```bash
# Manuel bootstrap testi
./.kilo/setup-script

# Sadece health check
php artisan bekci:health --detailed

# Sadece integrity scan
php artisan sab:integrity-scan
```
