#!/bin/bash

# Production Optimizasyon Script
# Tarih: 2025-12-01
# Versiyon: 1.0.0
# Context7 Standardı: C7-PRODUCTION-OPTIMIZATION-2025-12-01

set -e

echo "⚡ Production Optimizasyonu Başlatılıyor..."
echo ""

# Renkler
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1. Route Cache
echo -e "${BLUE}📦 1. Route Cache Oluşturuluyor...${NC}"
php artisan route:cache
echo -e "${GREEN}✅ Route cache oluşturuldu (Route bulma %95-98 daha hızlı)${NC}"
echo ""

# 2. Config Cache
echo -e "${BLUE}⚙️  2. Config Cache Oluşturuluyor...${NC}"
php artisan config:cache
echo -e "${GREEN}✅ Config cache oluşturuldu${NC}"
echo ""

# 3. View Cache
echo -e "${BLUE}🎨 3. View Cache Oluşturuluyor...${NC}"
php artisan view:cache
echo -e "${GREEN}✅ View cache oluşturuldu${NC}"
echo ""

# 4. Event Cache
echo -e "${BLUE}📡 4. Event Cache Oluşturuluyor...${NC}"
php artisan event:cache
echo -e "${GREEN}✅ Event cache oluşturuldu${NC}"
echo ""

# 5. Composer Autoloader Optimize
echo -e "${BLUE}🎼 5. Composer Autoloader Optimize Ediliyor...${NC}"
composer dump-autoload --optimize --classmap-authoritative
echo -e "${GREEN}✅ Autoloader optimize edildi${NC}"
echo ""

# 6. Özet
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Production Optimizasyonu Tamamlandı!${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Beklenen Performans İyileştirmeleri:"
echo "   → Route bulma: %95-98 daha hızlı"
echo "   → Config yükleme: %80-90 daha hızlı"
echo "   → View render: %50-60 daha hızlı"
echo "   → Autoloader: %30-40 daha hızlı"
echo ""
echo "⚠️  NOT: Development'ta cache'i temizlemek için:"
echo "   php artisan optimize:clear"
echo ""

