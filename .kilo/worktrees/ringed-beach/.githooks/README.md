# Git Hooks Dökümanı

**Tarih:** 1 Kasım 2025  
**Context7 Compliance:** %100  
**Yalıhan Bekçi:** ✅ Uyumlu

---

## 🔧 KURULU HOOK'LAR

### **pre-commit - Field Sync Validation**

**Dosya:** `.git/hooks/pre-commit`  
**Durum:** ✅ Aktif

**Ne Yapar:**

- Her commit öncesi `php artisan fields:validate` çalıştırır
- Tutarsızlık varsa uyarı verir
- Kullanıcıya commit'e devam edip etmeme seçeneği sunar

**Kullanım:**

```bash
# Normal commit
git add .
git commit -m "message"

# Hook çalışır:
# - Tutarsızlık yoksa → Commit başarılı
# - Tutarsızlık varsa → Uyarı + onay ister
```

**Geçici Devre Dışı Bırakma:**

```bash
# Hook'u atla (önerilmez)
git commit --no-verify -m "message"
```

---

## 📋 HOOK KURULUM KOMUTLARI

### **Manuel Kurulum:**

```bash
# Hook dosyasını kopyala
cp .githooks/pre-commit .git/hooks/pre-commit

# Executable yap
chmod +x .git/hooks/pre-commit

# Test et
.git/hooks/pre-commit
```

### **Otomatik Kurulum (Tüm team için):**

```bash
# Git hooks dizinini ayarla
git config core.hooksPath .githooks

# Tüm hook'ları executable yap
chmod +x .githooks/*
```

---

## 🧪 HOOK TEST ETME

```bash
# Manuel test
.git/hooks/pre-commit

# Çıktı:
# 🔍 Field Sync Validation çalıştırılıyor...
# ✅ Field sync OK - Commit devam ediyor...
```

---

## ⚙️ HOOK ÖZELLEŞTİRME

### **Strict Mode (Hata varsa commit engelle):**

`.git/hooks/pre-commit` dosyasını düzenle:

```bash
# Satır 27-28'i değiştir:
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Field sync OK - Commit devam ediyor..."
    exit 0
else
    echo "❌ Field sync hatası! Commit iptal edildi."
    echo ""
    echo "Düzeltmek için:"
    echo "  php artisan fields:validate --fix"
    exit 1  # Direkt iptal et (onay sorma)
fi
```

### **Silent Mode (Sadece hata varsa göster):**

```bash
# Satır 8'i değiştir:
# ÖNCEKI:
echo "🔍 Field Sync Validation çalıştırılıyor..."

# YENİ:
# echo "🔍 Field Sync Validation çalıştırılıyor..."  # Sessiz
```

---

## 🚀 DİĞER KULLANIŞLI HOOK'LAR

### **pre-push - Linter ve Tests**

```bash
#!/bin/bash
echo "🧪 Tests çalıştırılıyor..."
npm run lint
php artisan test --parallel

if [ $? -ne 0 ]; then
    echo "❌ Tests başarısız! Push iptal edildi."
    exit 1
fi
```

### **commit-msg - Conventional Commits**

```bash
#!/bin/bash
commit_msg=$(cat "$1")

# Conventional commit pattern
pattern="^(feat|fix|docs|style|refactor|test|chore)(\(.+\))?: .{1,50}"

if ! echo "$commit_msg" | grep -qE "$pattern"; then
    echo "❌ Commit mesajı Conventional Commits formatında değil!"
    echo ""
    echo "Format: type(scope): description"
    echo "Örnek: feat(arsa): cephe sayısı eklendi"
    exit 1
fi
```

---

## 📚 REFERANSLAR

- [Git Hooks Dökümanı](https://git-scm.com/docs/githooks)
- [Field Strategy Guide](../FIELD_STRATEGY.md)
- [Field Sync Validation Setup](../FIELD_SYNC_VALIDATION_SETUP.md)

---

**Son Güncelleme:** 1 Kasım 2025  
**Durum:** ✅ Aktif, Production Ready
