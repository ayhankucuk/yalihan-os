# SAB — IDE Multi-Instance Çakışma Protokolü

**Version:** 1.0.0  
**Status:** Active  
**Date:** 24 Nisan 2026

---

## 🛡️ Protokol Amacı

Aynı workspace'de birden fazla IDE (VS Code, Antigravity, Cursor, vb.) çalıştığında oluşabilecek çakışmaları önlemek.

---

## 🚨 Tanımlanmış Riskler

| # | Risk | Seviye | Tetikleyici |
|---|------|--------|-------------|
| 1 | Port çakışması | 🟡 Orta | `php artisan serve` aynı port |
| 2 | Agent instruction yüklenememesi | 🔴 Yüksek | Büyük/bozuk `copilot-instructions.md` |
| 3 | MCP server port çakışması | 🟡 Orta | Birden fazla MCP aynı port |
| 4 | Dosya kilidi | 🟡 Orta | `.env`, `storage/logs/` |

---

## ✅ Çözüm Adımları

### 1. Port Yönetimi

```bash
# VS Code için
php artisan serve --port=8000

# Diğer IDE için
php artisan serve --port=8001
```

### 2. Agent Dosyası Testi

Eğer agent cevap vermiyorsa:

```bash
# Geçici devre dışı bırak
mv .github/copilot-instructions.md .github/copilot-instructions.md.bak

# Test et
# → Çalışırsa → dosya sorunlu
# → Çalışmazsa → başka sorun
```

### 3. MCP Port Kontrolü

```bash
lsof -i :3000  # Yalıhan Bekçi
lsof -i :3100  # Diğer MCP
```

### 4. Log Temizliği (Otomatik)

```bash
# Her hafta çalıştır
> storage/logs/laravel.log
find storage/logs/ -name "*.log" -mtime +7 -delete
```

---

## 🔧 Hızlı Müdahale

| Sorun | Komut |
|-------|-------|
| Agent çalışmıyor | `mv .github/copilot-instructions.md .github/copilot-instructions.md.bak` |
| Port dolu | `php artisan serve --port=8001` |
| Log şişti | `> storage/logs/laravel.log` |
| MCP çakıştı | `lsof -i :3000,3100` |

---

## 📋 Kontrol Listesi

- [ ] Farklı portlarda sunucu başlatıldı
- [ ] Agent instruction dosyası test edildi
- [ ] MCP portları kontrol edildi
- [ ] Log boyutu < 50MB