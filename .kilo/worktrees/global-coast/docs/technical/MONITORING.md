# 🔍 EmlakPro Analysis & Monitoring System

Bu sistem, yaptığımız manuel analizi otomatikleştiren ve genişleten bir araç setidir. Laravel admin sayfalarını real-time olarak izler, analiz eder ve raporlar sunar.

## 🏗️ Sistem Bileşenleri

### 1. **Laravel Artisan Command**

```bash
php artisan analyze:pages
```

- Static code analysis
- Controller implementation check
- Context7 compliance validation
- Otomatik scoring sistemi

### 2. **Web Dashboard**

```
/admin/page-analyzer
```

- Real-time monitoring interface
- Interactive charts ve metrics
- Alert management system
- Recommendation engine

### 3. **Health Check Script**

```bash
./tools/health-check.sh
```

- Automated system health validation
- Controller implementation checks
- Database connectivity tests
- Permission verification

### 4. **Node.js Monitor Service**

```bash
cd tools/monitor && npm start
```

- Real-time page monitoring
- WebSocket updates
- Alert notifications
- Daily reports

## 🚀 Kurulum ve Kullanım

### Laravel Components

1. **Service'i Laravel'e kaydedin:**

```php
// config/app.php
'providers' => [
    // ...
    App\Services\Analysis\PageAnalyticsService::class,
],
```

2. **Command'ı test edin:**

```bash
php artisan analyze:pages --page=my-listings
```

3. **Web dashboard'a erişin:**

```
http://localhost:8001/admin/page-analyzer
```

### Monitor Service

1. **Dependencies yükleyin:**

```bash
cd tools/monitor
npm install
```

2. **Service'i başlatın:**

```bash
npm start
# veya development için:
npm run dev
```

3. **Monitor dashboard:**

```
http://localhost:3001
```

### Health Check Script

```bash
# Sistem sağlığını kontrol et
./tools/health-check.sh

# Çıktı örneği:
🔍 EmlakPro Page Analyzer - Automated Health Check
==================================================

ℹ️  Checking Laravel environment...
✅ Laravel is ready
ℹ️  Checking database connectivity...
✅ Database connection successful
ℹ️  Checking controller implementations...
❌ MyListingsController: Not implemented
❌ AnalyticsController: Not implemented
⚠️  2 controller(s) have issues
📊 Summary: 3/5 pages healthy
```

## 📊 Örnek Çıktılar

### Console Analysis Report

```
📊 EmlakPro Page Analysis Report
================================

🔴 CRITICAL ISSUES (2)
- my-listings: Score 3.0/10
  • Method index is not implemented
- analytics: Score 4.0/10
  • Method index is not implemented

⚠️ WARNING ISSUES (1)
- adres-yonetimi: Score 6.0/10

✅ HEALTHY PAGES (2)
- telegram-bot: Score 8.0/10
- notifications: Score 7.5/10

💡 RECOMMENDATIONS
1. Implement missing controllers (Priority: Critical)
2. Add schema migrations (Priority: High)
3. Enhance monitoring (Priority: Medium)
```

### Real-time Metrics JSON

```json
{
    "timestamp": "2025-10-15T10:30:00.000Z",
    "page_performance": {
        "telegram_bot": {
            "avg_response_time": 145,
            "success_rate": 98.5,
            "active_users": 12
        },
        "my_listings": {
            "avg_response_time": 650,
            "success_rate": 45.0,
            "last_error": "Controller not implemented"
        }
    },
    "overall_health": {
        "score": 72.3,
        "status": "fair"
    }
}
```

## 🎯 Kullanım Senaryoları

### 1. **Günlük Monitoring**

```bash
# Sabah system check
./tools/health-check.sh

# Real-time monitoring başlat
cd tools/monitor && npm start
```

### 2. **Development Workflow**

```bash
# Kod değişikliklerinden sonra
php artisan analyze:pages --page=my-listings

# Dashboard'dan sonuçları gör
open http://localhost:8001/admin/page-analyzer
```

### 3. **CI/CD Pipeline**

```yaml
# .github/workflows/health-check.yml
- name: Run Health Check
  run: ./tools/health-check.sh

- name: Generate Analysis Report
  run: php artisan analyze:pages --format=json --output=report.json
```

### 4. **Production Monitoring**

```bash
# Cron job her 5 dakikada
*/5 * * * * /path/to/project/tools/health-check.sh

# PM2 ile monitor service
pm2 start tools/monitor/monitor.js --name emlakpro-monitor
```

## 🔧 Konfigürasyon

### Environment Variables

```bash
# .env
MONITOR_URL=http://localhost:3001
ANALYSIS_THRESHOLD=5.0
ALERT_EMAIL=admin@yalihanemlak.com
```

### Monitor Service Config

```javascript
// tools/monitor/.env
PORT=3001
LARAVEL_URL=http://localhost:8001
CHECK_INTERVAL=30000
ALERT_THRESHOLD=2000
```

## 📈 Geliştirebilecek Özellikler

### Yakın Vadede (1-2 Hafta)

- [ ] **E-mail alerts** sistem kritik durumlar için
- [ ] **Slack/Discord integration** team notifications
- [ ] **Database monitoring** query performance
- [ ] **API response validation** content checks

### Orta Vadede (1-2 Ay)

- [ ] **Machine learning** pattern detection
- [ ] **Performance regression** detection
- [ ] **Automated fixing** suggestions
- [ ] **Load testing** integration

### Uzun Vadede (3-6 Ay)

- [ ] **Multi-environment** support
- [ ] **Custom metrics** definition
- [ ] **Grafana integration** advanced dashboards
- [ ] **AI-powered** optimization recommendations

## 🤝 Katkıda Bulunma

Bu sistem sürekli gelişiyor. Yeni özellik önerileri, bug reports ve iyileştirmeler için:

1. Issue açın
2. Feature branch oluşturun
3. Test edin
4. Pull request gönderin

## 📝 Notlar

- **Performance**: Monitor service minimum sistem kaynağı kullanır
- **Security**: Production'da authentication ekleyin
- **Scaling**: Microservice architecture'a uygun
- **Monitoring**: Kendi kendini monitor eden sistem

---

**Sonuç**: Artık manual analiz sürecimizi otomatik hale getirdik! 🎉
