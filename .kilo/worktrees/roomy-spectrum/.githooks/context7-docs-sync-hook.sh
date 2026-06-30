#!/bin/bash

if git diff --cached --name-only | grep -q '\.md$'; then
    echo "📄 Markdown dosyası değişikliği tespit edildi"
    echo "🔄 Context7 dokümantasyon senkronizasyonu çalıştırılıyor..."

    php scripts/context7-docs-sync.php

    git add docs/README.md .sab/authority.json

    echo "✅ Dokümantasyon otomatik senkronize edildi"
fi

exit 0
