# 🔄 NotebookLM Canlı Senkronizasyon

## 🎯 Özet

Proje dosyalarındaki değişiklikleri otomatik olarak NotebookLM'e senkronize etmek için 3 yöntem:

1. **Manuel Sync** - İhtiyaç olduğunda çalıştır
2. **Watch Mode** - Terminal'de sürekli izle
3. **Launchd Service** - Arka planda otomatik (her 5 dakika)

---

## 📋 Senkronize Edilen Dosyalar

### Phase 1: Core Governance
- [`docs/SAB.md`](../docs/SAB.md)
- [`docs/governance/CLAUDE_MEMORY.md`](../docs/governance/CLAUDE_MEMORY.md)
- [`docs/governance/LEARNED_PATTERNS.json`](../docs/governance/LEARNED_PATTERNS.json)
- [`docs/ROO_CAPABILITIES.md`](../docs/ROO_CAPABILITIES.md)

### Phase 2: AI Collaboration
- [`docs/AI_COLLABORATION_DESIGN.md`](../docs/AI_COLLABORATION_DESIGN.md)
- [`docs/GEMINI_ENGINEER_PLAN.md`](../docs/GEMINI_ENGINEER_PLAN.md)
- [`docs/NOTEBOOKLM_INTEGRATION.md`](../docs/NOTEBOOKLM_INTEGRATION.md)
- [`docs/BEKCI_CHANGELOG.md`](../docs/BEKCI_CHANGELOG.md)

**Toplam:** 8 dosya

---

## 🚀 Kullanım

### 1. Manuel Sync (Tek Seferlik)

```bash
# Dosyaları sync dizinine kopyala
./scripts/ops/notebooklm-sync.sh

# Output:
# 🔄 NotebookLM Sync Script
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# ✓ docs/SAB.md (güncellendi)
# • docs/governance/CLAUDE_MEMORY.md (değişiklik yok)
# ✓ docs/NOTEBOOKLM_INTEGRATION.md (güncellendi)
# 
# ✅ 2 dosya güncellendi
# 
# 📝 Manuel Adım:
# 1. NotebookLM'e git: https://notebooklm.google.com/notebook/...
# 2. Değişen dosyaları yeniden upload et
```

**Ne zaman kullan:**
- Dosyalarda büyük değişiklik yaptıktan sonra
- NotebookLM'i manuel güncellemek istediğinde

### 2. Watch Mode (Sürekli İzleme)

```bash
# Terminal'de sürekli çalıştır (60 saniyede bir kontrol)
./scripts/ops/notebooklm-sync.sh --watch

# Output:
# 👀 Watch mode aktif (Ctrl+C ile çık)
# 
# ✅ 0 dosya güncellendi
# ⏳ 60 saniye bekleniyor...
```

**Ne zaman kullan:**
- Aktif geliştirme yaparken
- Dokümantasyon yazarken
- Hızlı iterasyon istediğinde

**Durdurma:** `Ctrl+C`

### 3. Launchd Service (Otomatik Arka Plan)

```bash
# Service'i yükle (her 5 dakikada bir çalışır)
launchctl load ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist

# Service'i durdur
launchctl unload ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist

# Service durumunu kontrol et
launchctl list | grep notebooklm

# Log'ları izle
tail -f logs/notebooklm-sync.log
tail -f logs/notebooklm-sync.err
```

**Ne zaman kullan:**
- Production ortamında
- Sürekli güncel tutmak istediğinde
- Unutmamak için

---

## 🔧 Kurulum

### 1. Script'i Test Et

```bash
cd /Users/macbookpro/dev/yalihan2026

# İlk sync
./scripts/ops/notebooklm-sync.sh

# Sync dizinini kontrol et
ls -la storage/notebooklm-sync/
```

### 2. Launchd Service Kur

```bash
# Plist dosyasını LaunchAgents'a kopyala
cp launchd/com.yalihan.notebooklm-sync.plist ~/Library/LaunchAgents/

# Service'i yükle
launchctl load ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist

# Hemen çalıştır (test için)
launchctl start com.yalihan.notebooklm-sync

# Log'u kontrol et
tail -f logs/notebooklm-sync.log
```

### 3. NotebookLM'de İlk Upload

```bash
# 1. Sync'i çalıştır
./scripts/ops/notebooklm-sync.sh

# 2. NotebookLM'e git
open https://notebooklm.google.com/notebook/317f976e-6e6a-47e9-97c5-c4ca4f8ecae5

# 3. Mevcut source'ları sil (28 adet)

# 4. Yeni dosyaları upload et
# "Add source" → "Upload file" → storage/notebooklm-sync/ dizininden seç
```

---

## 📊 Workflow

### Günlük Kullanım

```
1. Kod/dokümantasyon değişikliği yap
   ↓
2. Launchd otomatik sync yapar (5 dakika içinde)
   ↓
3. storage/notebooklm-sync/ dizinine kopyalar
   ↓
4. Terminal'de bildirim görürsün
   ↓
5. NotebookLM'e git, değişen dosyaları yeniden upload et
```

### Hızlı İterasyon

```
1. Watch mode başlat: ./scripts/ops/notebooklm-sync.sh --watch
   ↓
2. Dokümantasyon yaz (örn: SAB.md)
   ↓
3. 60 saniye içinde sync olur
   ↓
4. NotebookLM'e upload et
   ↓
5. Gemini'ye sor: "SAB'daki yeni kurallar doğru mu?"
```

---

## 🎨 Özelleştirme

### Farklı Dosyalar Ekle

[`scripts/ops/notebooklm-sync.sh`](./notebooklm-sync.sh) dosyasını düzenle:

```bash
declare -a FILES=(
    # Mevcut dosyalar
    "docs/SAB.md"
    "docs/governance/CLAUDE_MEMORY.md"
    
    # Yeni dosyalar ekle
    "app/Repositories/KisiRepository.php"
    "tests/Unit/Repositories/CRMScopedDeleteSafetyTest.php"
)
```

### Sync Aralığını Değiştir

[`launchd/com.yalihan.notebooklm-sync.plist`](../launchd/com.yalihan.notebooklm-sync.plist) dosyasını düzenle:

```xml
<!-- 5 dakika (300 saniye) -->
<key>StartInterval</key>
<integer>300</integer>

<!-- 10 dakika için: -->
<integer>600</integer>

<!-- 1 dakika için: -->
<integer>60</integer>
```

Değişiklikten sonra:

```bash
launchctl unload ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist
cp launchd/com.yalihan.notebooklm-sync.plist ~/Library/LaunchAgents/
launchctl load ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist
```

---

## 🚨 Sorun Giderme

### "Permission denied" Hatası

```bash
chmod +x scripts/ops/notebooklm-sync.sh
```

### Launchd Çalışmıyor

```bash
# Service'i kaldır ve yeniden yükle
launchctl unload ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist
launchctl load ~/Library/LaunchAgents/com.yalihan.notebooklm-sync.plist

# Log'u kontrol et
cat logs/notebooklm-sync.err
```

### Dosyalar Sync Olmuyor

```bash
# Manuel test
./scripts/ops/notebooklm-sync.sh

# Dosya path'lerini kontrol et
ls -la docs/SAB.md
ls -la docs/governance/CLAUDE_MEMORY.md
```

### NotebookLM'de Eski Versiyon Görünüyor

1. NotebookLM'de source'u sil
2. Yeniden upload et (storage/notebooklm-sync/ dizininden)
3. Gemini'ye test sorusu sor

---

## 📈 Gelecek İyileştirmeler

### Phase 1: Otomatik Upload (Planlı)

```bash
# NotebookLM API kullanarak otomatik upload
# (Şu anda NotebookLM'in public API'si yok)
```

### Phase 2: Git Hook Entegrasyonu

```bash
# Git commit'ten sonra otomatik sync
# hooks/post-commit
./scripts/ops/notebooklm-sync.sh
```

### Phase 3: Selective Sync

```bash
# Sadece değişen dosyaları sync et
git diff --name-only HEAD~1 | grep "^docs/" | xargs ./scripts/ops/notebooklm-sync.sh
```

---

## 🔗 İlgili Dosyalar

- [`notebooklm-sync.sh`](./notebooklm-sync.sh) - Sync script
- [`com.yalihan.notebooklm-sync.plist`](../launchd/com.yalihan.notebooklm-sync.plist) - Launchd config
- [`NOTEBOOKLM_SOURCES.md`](../docs/NOTEBOOKLM_SOURCES.md) - Kaynak dosya listesi
- [`NOTEBOOKLM_INTEGRATION.md`](../docs/NOTEBOOKLM_INTEGRATION.md) - Entegrasyon dokümantasyonu

---

## ✅ Checklist

- [ ] Script'i test et: `./scripts/ops/notebooklm-sync.sh`
- [ ] Sync dizinini kontrol et: `ls storage/notebooklm-sync/`
- [ ] NotebookLM'de mevcut source'ları sil
- [ ] Yeni dosyaları upload et
- [ ] Launchd service'i kur
- [ ] Log'ları kontrol et: `tail -f logs/notebooklm-sync.log`
- [ ] Gemini'ye test sorusu sor

---

**Status:** ✅ Production Ready  
**Last Updated:** 2026-05-16  
**Version:** 1.0.0
