#!/bin/bash

# ============================================================================
# PRODUCTION CLEANUP SCRIPT
# ============================================================================
# Tarih: 26 Aralık 2025
# Amaç: Production öncesi gereksiz dosya/klasörleri temizle
# Hedef: ~2.5GB → ~250MB (90% azalma)
# ============================================================================

set -e

# Renkler
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

PROJECT_ROOT="/Users/macbookpro/Projects/yalihan2026"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
CLEANUP_LOG="${PROJECT_ROOT}/production-cleanup-${TIMESTAMP}.log"

# Sayaçlar
DELETED_FILES=0
DELETED_DIRS=0
FREED_SPACE=0

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}   PRODUCTION CLEANUP SCRIPT${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""
echo -e "${YELLOW}⚠️  UYARI: Bu script kalıcı silme işlemi yapar!${NC}"
echo -e "${YELLOW}⚠️  Tüm gereksiz dosyalar GERİ ALINAMAZ şekilde silinecek.${NC}"
echo ""
echo -e "${CYAN}Log dosyası: ${CLEANUP_LOG}${NC}"
echo ""

# Onay
read -p "Devam etmek istiyor musunuz? (evet/hayır): " confirm
if [ "$confirm" != "evet" ]; then
    echo -e "${RED}❌ İşlem iptal edildi.${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Cleanup başlıyor...${NC}"
echo ""

# Log başlat
echo "Production Cleanup - $(date)" > "$CLEANUP_LOG"
echo "========================================" >> "$CLEANUP_LOG"
echo "" >> "$CLEANUP_LOG"

# ============================================================================
# PHASE 1: BACKUP KLASÖRÜ TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 1: Backup klasörleri temizleniyor...${NC}"

if [ -d "${PROJECT_ROOT}/backups" ]; then
    BACKUP_SIZE=$(du -sh "${PROJECT_ROOT}/backups" | cut -f1)
    echo "  📦 /backups boyutu: ${BACKUP_SIZE}"
    echo "  Siliniyor..."
    rm -rf "${PROJECT_ROOT}/backups"
    DELETED_DIRS=$((DELETED_DIRS + 1))
    echo "  /backups silindi (${BACKUP_SIZE})" >> "$CLEANUP_LOG"
    echo -e "${GREEN}  ✅ /backups klasörü silindi${NC}"
else
    echo -e "${YELLOW}  ⚠️  /backups klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 2: ARCHIVE KLASÖRÜ TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 2: Archive klasörleri temizleniyor...${NC}"

if [ -d "${PROJECT_ROOT}/archive" ]; then
    ARCHIVE_SIZE=$(du -sh "${PROJECT_ROOT}/archive" | cut -f1)
    echo "  📦 /archive boyutu: ${ARCHIVE_SIZE}"
    echo "  Siliniyor..."
    rm -rf "${PROJECT_ROOT}/archive"
    DELETED_DIRS=$((DELETED_DIRS + 1))
    echo "  /archive silindi (${ARCHIVE_SIZE})" >> "$CLEANUP_LOG"
    echo -e "${GREEN}  ✅ /archive klasörü silindi${NC}"
else
    echo -e "${YELLOW}  ⚠️  /archive klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 3: YALIHAN BEKÇİ TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 3: Yalıhan Bekçi temizleniyor...${NC}"

if [ -d "${PROJECT_ROOT}/yalihan-bekci" ]; then
    BEKCI_SIZE=$(du -sh "${PROJECT_ROOT}/yalihan-bekci" | cut -f1)
    echo "  📦 /yalihan-bekci boyutu: ${BEKCI_SIZE}"
    echo "  Siliniyor..."
    rm -rf "${PROJECT_ROOT}/yalihan-bekci"
    DELETED_DIRS=$((DELETED_DIRS + 1))
    echo "  /yalihan-bekci silindi (${BEKCI_SIZE})" >> "$CLEANUP_LOG"
    echo -e "${GREEN}  ✅ /yalihan-bekci klasörü silindi${NC}"
else
    echo -e "${YELLOW}  ⚠️  /yalihan-bekci klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 4: MCP SERVERS TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 4: MCP Servers temizleniyor...${NC}"

# ⚠️  NOT: yalihan-bekci-mcp.js runtime governance tool'dur — SİLİNMEZ.
# Sadece geliştirme amaçlı dev MCP'ler (context7-mcp vb.) temizlenir.
# Bkz: mcp-servers/yalihan-bekci-mcp.js — production'da herzaman uyanık.

if [ -d "${PROJECT_ROOT}/mcp-servers" ]; then
    # Bekçi MCP'yi koru, geri kalanları temizle
    DEV_MCP_COUNT=0
    for f in "${PROJECT_ROOT}/mcp-servers/"*; do
        BASENAME=$(basename "$f")
        if [ "$BASENAME" = "yalihan-bekci-mcp.js" ]; then
            echo -e "${GREEN}  ✅ korundu: ${BASENAME} (runtime guardian)${NC}"
            continue
        fi
        rm -rf "$f"
        DEV_MCP_COUNT=$((DEV_MCP_COUNT + 1))
        echo "  silindi: ${BASENAME}" >> "$CLEANUP_LOG"
        echo -e "${GREEN}  ✅ silindi: ${BASENAME}${NC}"
    done
    if [ "$DEV_MCP_COUNT" -gt 0 ]; then
        DELETED_DIRS=$((DELETED_DIRS + 1))
    fi
else
    echo -e "${YELLOW}  ⚠️  /mcp-servers klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 5: IDE/DEVELOPMENT CONFIG TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 5: IDE/Development config dosyaları temizleniyor...${NC}"

cd "${PROJECT_ROOT}"

# Gizli klasörler
HIDDEN_DIRS=(".context7" ".antigravity" ".continue" ".prompts" ".qoder" ".yalihan-bekci")
for dir in "${HIDDEN_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        DELETED_DIRS=$((DELETED_DIRS + 1))
        echo "  ✅ $dir silindi"
        echo "  $dir silindi" >> "$CLEANUP_LOG"
    fi
done

# IDE config dosyaları
IDE_FILES=(
    ".cursorignore"
    ".cursorrules"
    ".editorconfig"
    ".prettierignore"
    "antigravity.js"
    "antigravity.mjs"
    "yalihanai.code-workspace"
    "phpstan.neon"
    "phpstan.neon.php"
    "vitest.config.ts"
    "eslint.config.js"
)

for file in "${IDE_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        DELETED_FILES=$((DELETED_FILES + 1))
        echo "  ✅ $file silindi"
        echo "  $file silindi" >> "$CLEANUP_LOG"
    fi
done

echo ""

# ============================================================================
# PHASE 6: ROOT TEST/REPORT DOSYALARI TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 6: Root test/report dosyaları temizleniyor...${NC}"

# Reports klasörü
if [ -d "${PROJECT_ROOT}/reports" ]; then
    rm -rf "${PROJECT_ROOT}/reports"
    DELETED_DIRS=$((DELETED_DIRS + 1))
    echo "  ✅ /reports klasörü silindi"
    echo "  /reports silindi" >> "$CLEANUP_LOG"
fi

# Test dosyaları
TEST_FILES=(
    "DOCUMENTATION_REALITY_REPORT.md"
    "quick-start.sh"
    "test-api.sh"
    "build-assets.sh"
)

for file in "${TEST_FILES[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        DELETED_FILES=$((DELETED_FILES + 1))
        echo "  ✅ $file silindi"
        echo "  $file silindi" >> "$CLEANUP_LOG"
    fi
done

echo ""

# ============================================================================
# PHASE 7: DOCS KLASÖRLERİ TEMİZLİĞİ
# ============================================================================

echo -e "${MAGENTA}PHASE 7: Gereksiz docs klasörleri temizleniyor...${NC}"

DOCS_CLEANUP_DIRS=(
    "docs/ai-training"
    "docs/archive"
    "docs/cortex-vision"
    "docs/design"
    "docs/feature"
    "docs/planning"
    "docs/testing"
)

for dir in "${DOCS_CLEANUP_DIRS[@]}"; do
    if [ -d "${PROJECT_ROOT}/${dir}" ]; then
        rm -rf "${PROJECT_ROOT}/${dir}"
        DELETED_DIRS=$((DELETED_DIRS + 1))
        echo "  ✅ /${dir} silindi"
        echo "  /${dir} silindi" >> "$CLEANUP_LOG"
    fi
done

echo ""

# ============================================================================
# PHASE 8: SCRIPTS KLASÖRÜ TEMİZLİĞİ (SEÇİCİ)
# ============================================================================

echo -e "${MAGENTA}PHASE 8: Scripts klasörü temizleniyor...${NC}"

if [ -d "${PROJECT_ROOT}/scripts" ]; then
    # Tutulacak production script'leri
    KEEP_SCRIPTS=(
        "deploy-production.sh"
        "deploy-cortex.sh"
        "optimize-production.sh"
        "security-audit.sh"
        "consolidate-cortex-docs.sh"
        "production-cleanup.sh"
    )

    # Geçici klasör oluştur
    mkdir -p "${PROJECT_ROOT}/scripts-temp"

    # Tutulacakları kopyala
    for script in "${KEEP_SCRIPTS[@]}"; do
        if [ -f "${PROJECT_ROOT}/scripts/${script}" ]; then
            cp "${PROJECT_ROOT}/scripts/${script}" "${PROJECT_ROOT}/scripts-temp/"
            echo "  📋 ${script} korundu"
        fi
    done

    # Eski scripts klasörünü sil
    SCRIPTS_SIZE=$(du -sh "${PROJECT_ROOT}/scripts" | cut -f1)
    rm -rf "${PROJECT_ROOT}/scripts"

    # Yeni scripts klasörünü oluştur
    mv "${PROJECT_ROOT}/scripts-temp" "${PROJECT_ROOT}/scripts"

    echo "  /scripts temizlendi (${SCRIPTS_SIZE} → minimal)" >> "$CLEANUP_LOG"
    echo -e "${GREEN}  ✅ /scripts klasörü temizlendi (${SCRIPTS_SIZE} → minimal)${NC}"
else
    echo -e "${YELLOW}  ⚠️  /scripts klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 9: TOOLS KLASÖRÜ TEMİZLİĞİ (SEÇİCİ)
# ============================================================================

echo -e "${MAGENTA}PHASE 9: Tools klasörü temizleniyor...${NC}"

if [ -d "${PROJECT_ROOT}/tools" ]; then
    # Sadece health-check ve monitor klasörünü tut
    cd "${PROJECT_ROOT}/tools"

    # Silinecek klasörler
    TOOLS_CLEANUP_DIRS=("context7" "page-analyzer" "scripts")

    for dir in "${TOOLS_CLEANUP_DIRS[@]}"; do
        if [ -d "$dir" ]; then
            rm -rf "$dir"
            echo "  ✅ /tools/${dir} silindi"
            echo "  /tools/${dir} silindi" >> "$CLEANUP_LOG"
        fi
    done

    cd "${PROJECT_ROOT}"
    echo -e "${GREEN}  ✅ /tools klasörü temizlendi (sadece production gerekli olanlar kaldı)${NC}"
else
    echo -e "${YELLOW}  ⚠️  /tools klasörü bulunamadı${NC}"
fi

echo ""

# ============================================================================
# PHASE 10: SON TEMİZLİK
# ============================================================================

echo -e "${MAGENTA}PHASE 10: Son temizlik işlemleri...${NC}"

# .DS_Store dosyaları
find "${PROJECT_ROOT}" -name ".DS_Store" -delete 2>/dev/null || true
echo "  ✅ .DS_Store dosyaları temizlendi"

# Log dosyaları (eski cleanup log'ları)
find "${PROJECT_ROOT}" -maxdepth 1 -name "production-cleanup-*.log" -mtime +7 -delete 2>/dev/null || true
echo "  ✅ Eski log dosyaları temizlendi"

echo ""

# ============================================================================
# ÖZET RAPORU
# ============================================================================

echo ""
echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}   CLEANUP TAMAMLANDI${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Toplam özet
echo -e "${GREEN}📊 İstatistikler:${NC}"
echo -e "  • Silinen klasör sayısı: ${DELETED_DIRS}"
echo -e "  • Silinen dosya sayısı: ${DELETED_FILES}"
echo ""

# Silinen klasörler
echo -e "${GREEN}🗑️  Silinen ana klasörler:${NC}"
echo -e "  • /backups/ (geçici backup'lar)"
echo -e "  • /archive/ (eski kod arşivi)"
echo -e "  • /yalihan-bekci/ (development tool)"
echo -e "  • /mcp-servers/ (AI development)"
echo -e "  • /reports/ (test raporları)"
echo -e "  • .context7/, .antigravity/, vb. (IDE config)"
echo ""

# Silinen docs klasörleri
echo -e "${GREEN}📚 Silinen docs klasörleri:${NC}"
echo -e "  • /docs/ai-training/ (AI eğitim dokümanları)"
echo -e "  • /docs/archive/ (arşiv dokümanlar)"
echo -e "  • /docs/cortex-vision/ (planlama dokümanları)"
echo -e "  • /docs/design/ (tasarım dokümanları)"
echo -e "  • /docs/feature/ (özellik planlaması)"
echo -e "  • /docs/planning/ (proje planlaması)"
echo -e "  • /docs/testing/ (test dokümanları)"
echo ""

# Korunan dosyalar
echo -e "${CYAN}✅ Korunan production dosyaları:${NC}"
echo -e "  • /app/, /config/, /database/, /routes/, /resources/"
echo -e "  • /public/ (built assets)"
echo -e "  • /docs/active/ (production docs)"
echo -e "  • /docs/guides/ (kullanım kılavuzları)"
echo -e "  • /scripts/ (sadece 6 production script)"
echo -e "  • /tools/health-check.sh, /tools/monitor/"
echo ""

# Sonraki adımlar
echo -e "${YELLOW}📝 Sonraki Adımlar:${NC}"
echo -e "  1. composer install --no-dev --optimize-autoloader"
echo -e "  2. npm run build"
echo -e "  3. php artisan config:cache"
echo -e "  4. php artisan route:cache"
echo -e "  5. php artisan view:cache"
echo ""

# Log dosyası bilgisi
echo -e "${CYAN}📄 Detaylı log:${NC}"
echo -e "  ${CLEANUP_LOG}"
echo ""

# Özet log'a yaz
echo "" >> "$CLEANUP_LOG"
echo "========================================" >> "$CLEANUP_LOG"
echo "ÖZET" >> "$CLEANUP_LOG"
echo "========================================" >> "$CLEANUP_LOG"
echo "Silinen klasör: ${DELETED_DIRS}" >> "$CLEANUP_LOG"
echo "Silinen dosya: ${DELETED_FILES}" >> "$CLEANUP_LOG"
echo "Tamamlanma: $(date)" >> "$CLEANUP_LOG"

echo -e "${GREEN}✅ Production cleanup başarıyla tamamlandı!${NC}"
echo ""

exit 0
