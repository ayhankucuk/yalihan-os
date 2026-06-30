#!/bin/bash

# NotebookLM Sync Script
# Proje dosyalarını NotebookLM'e senkronize eder
# Usage: ./scripts/ops/notebooklm-sync.sh [--watch]

set -e

PROJECT_ROOT="/Users/macbookpro/dev/yalihan2026"
SYNC_DIR="$PROJECT_ROOT/storage/notebooklm-sync"
NOTEBOOK_URL="https://notebooklm.google.com/notebook/317f976e-6e6a-47e9-97c5-c4ca4f8ecae5"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔄 NotebookLM Sync Script${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Sync dizinini oluştur
mkdir -p "$SYNC_DIR"

# Senkronize edilecek dosyalar
declare -a FILES=(
    # Phase 1: Core Governance
    "docs/SAB.md"
    "docs/governance/CLAUDE_MEMORY.md"
    "docs/governance/LEARNED_PATTERNS.json"
    "docs/ROO_CAPABILITIES.md"
    
    # Phase 2: AI Collaboration
    "docs/AI_COLLABORATION_DESIGN.md"
    "docs/GEMINI_ENGINEER_PLAN.md"
    "docs/NOTEBOOKLM_INTEGRATION.md"
    "docs/BEKCI_CHANGELOG.md"
    
    # Phase 3: Architecture (opsiyonel)
    # "README.md"
    # "CONTRIBUTING.md"
)

# Dosyaları sync dizinine kopyala
sync_files() {
    echo -e "${YELLOW}📋 Dosyalar kopyalanıyor...${NC}"
    
    local changed=0
    
    for file in "${FILES[@]}"; do
        local source="$PROJECT_ROOT/$file"
        local dest="$SYNC_DIR/$(basename $file)"
        
        if [ -f "$source" ]; then
            # Dosya değişmiş mi kontrol et
            if [ ! -f "$dest" ] || ! cmp -s "$source" "$dest"; then
                cp "$source" "$dest"
                echo -e "${GREEN}✓${NC} $file (güncellendi)"
                ((changed++))
            else
                echo -e "${BLUE}•${NC} $file (değişiklik yok)"
            fi
        else
            echo -e "${RED}✗${NC} $file (bulunamadı)"
        fi
    done
    
    echo ""
    echo -e "${GREEN}✅ $changed dosya güncellendi${NC}"
    
    if [ $changed -gt 0 ]; then
        echo ""
        echo -e "${YELLOW}📝 Manuel Adım:${NC}"
        echo "1. NotebookLM'e git: $NOTEBOOK_URL"
        echo "2. Değişen dosyaları yeniden upload et:"
        echo "   - 'Add source' → 'Upload file'"
        echo "   - Sync dizininden dosyaları seç: $SYNC_DIR"
        echo ""
    fi
}

# Watch mode
watch_mode() {
    echo -e "${BLUE}👀 Watch mode aktif (Ctrl+C ile çık)${NC}"
    echo ""
    
    while true; do
        sync_files
        echo -e "${BLUE}⏳ 60 saniye bekleniyor...${NC}"
        echo ""
        sleep 60
    done
}

# Ana işlem
if [ "$1" == "--watch" ]; then
    watch_mode
else
    sync_files
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Sync tamamlandı${NC}"
