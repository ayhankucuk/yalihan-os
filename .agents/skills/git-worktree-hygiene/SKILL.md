---
name: git-worktree-hygiene
description: Dirty git tree'yi temiz state'e getirir, kararları loglar, project-brain günceller. Oturum başında veya bitiminde çalışır.
---

# Git Worktree Hygiene

## Amaç

Kirli git tree'leri (unstaged + untracked) temizler. Her dosyaya karar verir: commit et / askıya al / tasfiye et. Kararları project-brain'e yazar.

## Tetikleyiciler

- Oturum başında `git status` → 10+ dirty dosya
- Kullanıcı "worktree temizle", "dirty dosyaları temizle" derse
- Mevcut branch'te bilinmeyen modified dosyalar varsa

## Çalışma sırası

### Adım 1 — Envanter oluştur

```bash
git status --short | grep '^ M '   # unstaged modified
git status --short | grep '^?? '   # untracked
git status --short | grep '^A  '   # staged (yeni eklenen)
git diff --staged --name-only       # staged dosyalar
git diff --staged --stat            # staged istatistik
```

### Adım 2 — Sınıflandır

Her dosyayı bu kategorilere ayır:

| Kategori | Kriter | Eylem |
|----------|--------|-------|
| **Güvenli** | Brain, SAB, BEKCI, agent skill, docs | Commit et |
| **Review gerekli** | Controller, migration, view, config, route | Askıya al |
| **Hot-spot** | Conflict Guard blocker dosyaları | Ayrı stage et, hot-spot'u çıkar |
| **Tasfiye** | Gezgin, superseded, araştırma | Sil |
| **Bilinmeyen** | Intent/ownership doğrulanamaz | Askıya al |

### Adım 3 — Önce hot-spot'ları tespit et

Hot-spot'lar Conflict Guard'ı tetikler. Bunları **ayrı commit** et veya staged'den çıkar:

```
Yaygın hot-spot dosyalar:
- .sab/authority.json
- .sab/sab-baseline.json
- config/feature-flags.php
- config/*.php           (yeni config dosyaları)
- routes/admin.php
- routes/*.php           (yeni route dosyaları)
```

Her yeni config/route dosyası potansiyel hot-spot'tur.

### Adım 4 — Güvenli dosyaları commit et

```bash
# Grup grup commit et
git add <güvenli-dosyalar>
git commit -m '<paket>: <kısa-açıklama>'
```

Mesaj formatı: `<type>: <kısa açıklama>`
- `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `test:`

### Adım 5 — Hot-spot'ları çıkar (varsa)

```bash
git restore --staged <hot-spot-dosya>
# Sonra: ayrı intent sor veya conflict-guard lock al
```

### Adım 6 — Askıya alınanları dokümante et

`RC2-DIRTY-INVENTORY.md` veya yeni bir inventory dosyası oluştur:
- Her askıya alınan dosya: kategori + askıya alınma nedeni
- Hot-spot'lar için: lock alma talimatı
- Review gerekenler için: kime sorulacağı

### Adım 7 — Brain güncelle

```bash
# EVIDENCE_INDEX.md'ye oturum kaydı ekle
# PROJECT_STATE.md güncelle (varsa)
```

Her commit sonrası brain güncellemesi commit et:
```bash
git add .project-brain/
git commit -m 'docs: update project brain'
```

## Karar şablonu

Her dosya için kaydedilen bilgi:

```
| Dosya | Kategori | Karar | Neden | Sahip/Bilgi |
|-------|----------|-------|-------|-------------|
| x.php | Güvenli | Commit edildi | Brain dokümanı güncelleme | - |
| y.php | Hot-spot | Askıya alındı | Conflict Guard blocker | Lock gerekli |
| z.php | Review | Askıya alındı | Intent doğrulanmadı | Kime sorulacak |
```

## Kaçınılması gereken

- Tüm dosyaları tek seferde commit etmeye çalışmak
- Hot-spot'ları "sessizce" staged'den çıkarmak (kayıtsız bırakma)
- Bilinmeyen dosyayı "ben anlarım" diye commit etmek
- Review gereken dosyayı atlamak yerine silmek

## MCP Entegrasyon

Bekçi MCP'den yararlan:

```text
Soru: "Bu dosya hot-spot mu?"
→ MCP: check_violation(file_path) → hot-spot/önemli/değiştirilmiş

Soru: "Bu değişiklik güvenli mi?"
→ MCP: validate_file(file_path) → score + guard sonuçları

Soru: "Kararı nasıl kaydedeyim?"
→ MCP: record_learning("git_hygiene_decision", description, context)
```
