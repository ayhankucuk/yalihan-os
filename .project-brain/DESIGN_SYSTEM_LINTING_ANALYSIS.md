# Design System Linting Raporu

**Tarih:** 2026-10-09
**Konu:** Design System Linting Durumu

---

## 1. Mevcut Durum

### Pre-Commit Hook
`.git/hooks/pre-commit` — AKTIF
- `secret-scan.sh` — Secret tarama
- `conflict-guard.sh` — Branch conflict
- `schema-parity-guard.sh` — Schema parity
- `antigravity-preflight.sh` — 10 Golden Rules

### Golden Rules (antigravity-preflight.sh)

| # | Kural | Durum |
|---|-------|-------|
| 1 | FontAwesome Ban | ✅ |
| 2 | Blade FQCN Facade | ✅ |
| 3 | Hardcoded URL Ban | ✅ |
| 4 | Vite MIX_ prefix | ✅ |
| 5 | Determinism check | ✅ |
| 6 | Deprecated APIs | ✅ |
| 7 | Env outside config | ✅ |
| 8 | Silent Catch Ban | ✅ |

---

## 2. Design System Kuralları

### Otomatik Kontrol Edilebilir
| Kural | Araç | Durum |
|-------|------|-------|
| FontAwesome icons | grep | ✅ antigravity-preflight |
| Triple class detection | grep | ❌ Yok |
| Hardcoded colors | grep | ❌ Manuel |
| Type attributes | grep | ❌ Manuel |

### Manuel Kontrol Gerekli
| Kural | Açıklama |
|-------|----------|
| Border color (gray-700) | Regex ile tespit edilebilir ama false positive riski yüksek |
| Triple class cleanup | Her class ayrı değerlendirilmeli |
| Dark mode consistency | Context'e bağlı |

---

## 3. Oneriler

### Kisa Vadeli (Yapilabilir)
1. **Triple class detection** — Script eklenebilir
```bash
grep -n "dark:bg-slate.*dark:bg-slate\|dark:text-slate.*dark:text-slate" resources/views/**/*.blade.php
```

2. **Border consistency check** — Grep ile
```bash
grep -rn "border-slate-" resources/views/admin --include="*.blade.php"
```

### Orta Vadeli
3. **CI/CD pipeline** — GitHub Actions ile her PR'da preflight çalıştırma

---

## 4. Sonuc

**Mevcut kurulum yeterli.** Ek olarak:
- Pre-commit hook zaten 8 kural kontrol ediyor
- Design System için özel linting manuel/analiz ile yapılıyor
- CI/CD entegrasyonu opsiyonel

**Yeni script gerekmiyor.**

---

## Rapor Durumu
**Analiz tamam — script/onerme yok.**
