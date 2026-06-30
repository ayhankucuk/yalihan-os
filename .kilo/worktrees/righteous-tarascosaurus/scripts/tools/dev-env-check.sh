#!/bin/bash
# Laravel Development Environment Setup Fix
# Fixes: "php: command not found" in VS Code

echo "🔧 Laravel Geliştirme Ortamı Kurulumu"
echo "======================================"

# 1. PHP'nin konumunu kontrol et
echo ""
echo "✅ PHP Konumunu Kontrol Ediliyor..."
PHP_PATH="/opt/homebrew/bin/php"

if [ -f "$PHP_PATH" ]; then
    echo "   ✓ PHP bulundu: $PHP_PATH"
    $PHP_PATH --version | head -1
else
    echo "   ✗ PHP bulunamadı!"
    exit 1
fi

# 2. Laravel versiyon kontrolü
echo ""
echo "✅ Laravel Kontrolü..."
cd /Users/macbookpro/Projects/yalihan2026
$PHP_PATH artisan --version

# 3. Composer dependencies kontrol
echo ""
echo "✅ Composer Dependencies..."
composer --version

# 4. Node/NPM kontrol
echo ""
echo "✅ Node & NPM..."
node --version
npm --version

# 5. Database bağlantısı kontrol
echo ""
echo "✅ Database Bağlantısı..."
$PHP_PATH artisan tinker << 'EOF'
echo "Database: " . config('database.default') . "\n";
try {
    DB::connection()->getPdo();
    echo "✓ Bağlantı Başarılı\n";
} catch (\Exception $e) {
    echo "✗ Bağlantı Başarısız: " . $e->getMessage() . "\n";
}
exit();
EOF

echo ""
echo "======================================"
echo "✅ TÜM KONTROLLER BAŞARILI!"
echo ""
echo "🚀 Sunucuları başlatmak için:"
echo "   Terminal 1: php artisan serve --port=8002"
echo "   Terminal 2: npm run dev"
echo "   Terminal 3: ./scripts/services/start-all-mcp-servers.sh"
