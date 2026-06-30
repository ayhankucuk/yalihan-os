#!/bin/bash

# 🛡️ DOKÜMANTASYON GÜVENLİK BEKÇİSİ (Context7 Compliance)
# Bu script, kod değişikliklerinin dokümantasyon ile senkronize olmasını sağlar.

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}🔍 Dokümantasyon bütünlüğü kontrol ediliyor...${NC}"

# 1. Değişen dosyaları bul
if [ "$GITHUB_ACTIONS" == "true" ]; then
    echo -e "${YELLOW}🤖 CI Otoritesi algılandı. Branş farkları kontrol ediliyor...${NC}"
    # CI'da genellikle main/master ile karşılaştırma yapılır
    TARGET_BRANCH="origin/main"
    if ! git rev-parse --verify "$TARGET_BRANCH" >/dev/null 2>&1; then
        TARGET_BRANCH="origin/master"
    fi
    DEGISIKLIKLER=$(git diff --name-only "$TARGET_BRANCH"...HEAD | sort -u)
else
    # Yerel geliştirme (staged + unstaged)
    DEGISIKLIKLER=$( (git diff --name-only; git diff --cached --name-only) | sort -u )
fi

KOD_DEGISTI=false
DOKUMAN_DEGISTI=false

for dosya in $DEGISIKLIKLER; do
    if [[ $dosya == app/* ]] || [[ $dosya == routes/* ]]; then
        KOD_DEGISTI=true
        echo -e "  💻 Kod değişti: $dosya"
    fi
    if [[ $dosya == docs/* ]]; then
        DOKUMAN_DEGISTI=true
        echo -e "  📄 Doküman değişti: $dosya"
    fi
done

# 2. Eğer kod değişmiş ama doküman değişmemişse (kritik uyarı)
if [ "$KOD_DEGISTI" = true ] && [ "$DOKUMAN_DEGISTI" = false ]; then
    echo -e "\n${RED}🚨 HATA: Kod değişikliği tespit edildi ancak dokümantasyon güncellenmemiş!${NC}"
    echo -e "${RED}Dokümantasyon güncellenmeden mühürleme yapılamaz!${NC}"
    echo -e "Lütfen 'docs/' altındaki ilgili teknik dokümanı veya GÜNLÜK_RAPOR'u güncelleyin."
    exit 1
fi

if [ "$KOD_DEGISTI" = true ] && [ "$DOKUMAN_DEGISTI" = true ]; then
    echo -e "\n${GREEN}✅ Harika! Kod ve dokümantasyon senkronize.${NC}"
else
    echo -e "\n${GREEN}✅ Dokümantasyon kontrolü başarılı.${NC}"
fi

exit 0
