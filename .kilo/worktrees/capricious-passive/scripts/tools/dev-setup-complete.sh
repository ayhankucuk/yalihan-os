#!/bin/bash
# 🚀 Yalıhan Emlak - Tam Setup Script
# Laravel + Vite + MCP Servers'ı otomatik başlatır

set -e

PROJECT_DIR="/Users/macbookpro/Projects/yalihan2026"
cd "$PROJECT_DIR"

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     🚀 Yalıhan Emlak - Geliştirme Ortamı Kurulumu          ║"
echo "╚════════════════════════════════════════════════════════════╝"

# PHP Kontrol
echo ""
echo "1️⃣  PHP Kontrol Ediliyor..."
PHP_PATH="/opt/homebrew/bin/php"
if [ -f "$PHP_PATH" ]; then
    echo "   ✅ PHP bulundu: $PHP_PATH"
else
    echo "   ❌ PHP bulunamadı!"
    exit 1
fi

# Composer dependencies
echo ""
echo "2️⃣  Composer Dependencies Kontrol Ediliyor..."
if [ -d "vendor" ]; then
    echo "   ✅ Vendor dizini var"
else
    echo "   ℹ️  composer install çalıştırılıyor..."
    composer install
fi

# NPM dependencies
echo ""
echo "3️⃣  NPM Dependencies Kontrol Ediliyor..."
if [ -d "node_modules" ]; then
    echo "   ✅ node_modules dizini var"
else
    echo "   ℹ️  npm install çalıştırılıyor..."
    npm install
fi

# Environment file
echo ""
echo "4️⃣  Environment Dosyası Kontrol Ediliyor..."
if [ ! -f ".env" ]; then
    echo "   ℹ️  .env dosyası oluşturuluyor..."
    cp .env.example .env
    $PHP_PATH artisan key:generate
fi

# Database
echo ""
echo "5️⃣  Database Kontrol Ediliyor..."
$PHP_PATH artisan migrate --pretend > /dev/null 2>&1 || {
    echo "   ℹ️  Migration'lar çalıştırılıyor..."
    $PHP_PATH artisan migrate --seed
}

# Port kontrolü
echo ""
echo "6️⃣  Portlar Kontrol Ediliyor..."
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null ; then
        return 0
    else
        return 1
    fi
}

PORT_8002_OPEN=$(check_port 8002 && echo "1" || echo "0")
PORT_5173_OPEN=$(check_port 5173 && echo "1" || echo "0")

echo "   Port 8002 (Laravel):  $([ $PORT_8002_OPEN -eq 1 ] && echo '🟢 AÇIK' || echo '🔴 KAPAL')"
echo "   Port 5173 (Vite):     $([ $PORT_5173_OPEN -eq 1 ] && echo '🟢 AÇIK' || echo '🔴 KAPAL')"

# Services start
echo ""
echo "════════════════════════════════════════════════════════════"
echo "7️⃣  SERVİSLER BAŞLATILIYOR..."
echo "════════════════════════════════════════════════════════════"

# Laravel Server
if [ $PORT_8002_OPEN -eq 0 ]; then
    echo ""
    echo "📍 Laravel Server başlatılıyor (Port 8002)..."
    $PHP_PATH artisan serve --port=8002 > /tmp/laravel.log 2>&1 &
    LARAVEL_PID=$!
    sleep 2
    echo "   ✅ PID: $LARAVEL_PID"
else
    echo "   ⚠️  Port 8002 zaten kullanılıyor, SKIP"
fi

# Vite Dev Server
if [ $PORT_5173_OPEN -eq 0 ]; then
    echo ""
    echo "📍 Vite Dev Server başlatılıyor (Port 5173)..."
    npm run dev > /tmp/vite.log 2>&1 &
    VITE_PID=$!
    sleep 3
    echo "   ✅ PID: $VITE_PID"
else
    echo "   ⚠️  Port 5173 zaten kullanılıyor, SKIP"
fi

# MCP Servers
echo ""
echo "📍 MCP Servers başlatılıyor..."
bash ./scripts/services/start-all-mcp-servers.sh > /tmp/mcp.log 2>&1 &
MCP_PID=$!
sleep 2
echo "   ✅ PID: $MCP_PID"

# Final result
echo ""
echo "════════════════════════════════════════════════════════════"
echo "✅ KURULUM BAŞARILI!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "🌐 Erişim Noktaları:"
echo "   • Laravel API:    http://localhost:8002"
echo "   • Vite HMR:       http://localhost:5173"
echo "   • Dashboard:      http://localhost:8002/admin"
echo ""
echo "📊 Log Dosyaları:"
echo "   • Laravel:  tail -f /tmp/laravel.log"
echo "   • Vite:     tail -f /tmp/vite.log"
echo "   • MCP:      tail -f /tmp/mcp.log"
echo ""
echo "🛑 Sunucuları Durdurmak:"
echo "   bash ./scripts/services/stop-all-mcp-servers.sh"
echo "   pkill -f 'php artisan serve'"
echo "   pkill -f 'vite'"
echo ""
echo "💡 İpucu: Ctrl+C ile bu script'i durdurabilirsin"
echo ""

# Keep script running
wait
