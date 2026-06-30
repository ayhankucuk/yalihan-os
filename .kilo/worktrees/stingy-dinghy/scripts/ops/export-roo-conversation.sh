#!/bin/bash

# Roo Conversation Export Script
# Roo ile yapılan konuşmaları NotebookLM'e gönderilmek üzere export eder
# Usage: ./scripts/ops/export-roo-conversation.sh "conversation-title"

set -e

PROJECT_ROOT="/Users/macbookpro/dev/yalihan2026"
EXPORT_DIR="$PROJECT_ROOT/storage/notebooklm-sync/conversations"
TIMESTAMP=$(date +"%Y-%m-%d-%H%M%S")

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}📝 Roo Conversation Export${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Export dizinini oluştur
mkdir -p "$EXPORT_DIR"

# Conversation title
TITLE="${1:-roo-conversation}"
FILENAME="${TITLE}-${TIMESTAMP}.md"
FILEPATH="$EXPORT_DIR/$FILENAME"

echo -e "${YELLOW}📋 Conversation export ediliyor...${NC}"
echo ""
echo "Bu script'i çalıştırdıktan sonra:"
echo "1. Roo conversation'ını kopyala (Cmd+A, Cmd+C)"
echo "2. Aşağıdaki dosyaya yapıştır:"
echo "   $FILEPATH"
echo ""
echo "Veya:"
echo "echo 'conversation content' > $FILEPATH"
echo ""

# Template dosya oluştur
cat > "$FILEPATH" << 'EOF'
# Roo Conversation Export

**Date:** $(date +"%Y-%m-%d %H:%M:%S")
**Topic:** [Conversation topic]

---

## Context

[Conversation başlangıç context'i]

---

## Conversation

### User
[İlk soru/istek]

### Roo
[Roo'nun cevabı]

### User
[Follow-up soru]

### Roo
[Roo'nun cevabı]

---

## Summary

**Completed Tasks:**
- [ ] Task 1
- [ ] Task 2

**Key Decisions:**
- Decision 1
- Decision 2

**Learned Patterns:**
- Pattern 1
- Pattern 2

**Files Modified:**
- file1.php
- file2.md

---

## Next Steps

1. Next step 1
2. Next step 2
EOF

echo -e "${GREEN}✅ Template oluşturuldu: $FILENAME${NC}"
echo ""
echo -e "${YELLOW}📝 Manuel Adımlar:${NC}"
echo "1. Dosyayı düzenle: code $FILEPATH"
echo "2. Conversation'ı yapıştır"
echo "3. NotebookLM'e upload et:"
echo "   - NotebookLM'e git"
echo "   - 'Add source' → 'Upload file'"
echo "   - $FILEPATH dosyasını seç"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ Export hazır${NC}"
