#!/bin/bash

BASE1="/Users/macbookpro/Projects/yalihan2026"
BASE2="/Users/macbookpro/Projects/yalihan2026"

echo "🔄 Senkronizasyon başlatılıyor: $BASE1 -> $BASE2"
echo "--------------------------------------------------------"

for dir in app routes config resources
do
  if [ -d "$BASE1/$dir" ]; then
    echo "📂 Syncing: $dir"
    rsync -av --delete "$BASE1/$dir" "$BASE2/"
  else
    echo "⚠️  Atlanıyor (Klasör bulunamadı): $dir"
  fi
done

echo "--------------------------------------------------------"
echo "✅ SYNC TAMAMLANDI!"
