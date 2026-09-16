---
name: multi-agent-worktree-sandbox
description: Git worktree izolasyonu, test DB ayrımı ve ajanlar arası handoff. Multi-agent çakışmalarını önler, her ajanın kendi branch'te çalışmasını garantiler.
---

# Multi Agent Worktree Sandbox

Codex, Antigravity, Kilo veya yerel geliştirici aynı projede çalışırken kaynak kodu, test verisini ve kanıt dosyalarını birbirinden ayırır.

---

## Başlangıç Sözleşmesi (Her Görev Öncesi)

Her görev başlamadan **önce** şu kontrolü yap:

```bash
git branch --show-current
git status --short
git worktree list
git rev-parse HEAD
```

Çıktıyı yorumla:
- **Dirty worktree** → üzerine yazma, sahibini belirle
- **Başka agent'ın branch'i** → o branch'e dokunma
- **Ana worktree'de kayıt varsa** → kapsamı ayır veya bekle

---

## Worktree Kuralları

### Yazma İzni
| Durum | Yapabilir |
|-------|----------|
| Ana worktree (`/repos/yalihan-os`) | Read-only |
| Kendi worktree'n + kendi branch | Full write |
| Başka ajanın worktree/branch | **Asla yazma** |

### Commit Disiplini
- Sadece **görev kapsamındaki dosyaları** stage et
- `git diff --staged --name-only` → beklenen kapsamla birebir karşılaştır
- Başka ajana ait değişiklik, kanıt PNG/JSON, log veya storage dosyasını **taşıma, silme, stage etme**
- Commit öncesi `git diff --check` + secret scan çalıştır

### Geri Alma Komutları (Sadece Bunlar)
```bash
# ✅ İZİNLİ
git restore -- <hedef-dosya>
git checkout HEAD -- <hedef-dosya>

# ❌ YASAK
git reset --hard
git clean -fd        # Geniş silme
rm -rf               # Folders
```

---

## Test DB İzolasyonu

```bash
# ❌ YASAK — Aynı database.sqlite'da paralel test
php artisan test

# ✅ DOĞRU — Her test koşusu için ayrı geçici DB
php artisan test --env=testing
# veya
DB_DATABASE=testing_db.sqlite php artisan test
```

Her test koşusu:
1. Geçici DB/şema oluştur veya test:refresh-database kullan
2. Fixture ID'lerini sabitle (hash'li geçici ID)
3. Tenant correlation ID'yi test çıktısında taşı
4. Test tamamlandığında geçici DB'yi temizle veya kanıt amacıyla saklandığını belirt

---

## Handoff Protokolü

Handoff mesajı **şu alanları içermeli**:

| Alan | İçerik |
|------|--------|
| Owner | Hangi ajan/session üretti |
| Worktree/Branch | Hangi worktree ve branch |
| Değişen Dosyalar | `git diff --staged --name-only` listesi |
| Commit | Varsa commit hash |
| Test Komutu | `php artisan test --filter=...` |
| Sonuç | Ham test çıktısı veya kanıt dosyası |
| Bilinen Blokaj | Ne bekliyor |
| Sonraki Doğrulama | Kim ne yapmalı |

```markdown
## HANDOFF — [Görev Adı]

**Branch:** feature/my-task
**Commit:** abc123f
**Worktree:** /repos/yalihan-os/worktree-agent-x/

### Değişen Dosyalar (6)
- app/Services/IlanCrudService.php
- tests/Feature/Ilan/IlanCrudTest.php

### Çalıştırılan Komut
php artisan test --filter=IlanCrudTest

### Test Sonucu
PASS — 12/12 assertions

### Bilinen Blokaj
Yok

### Sonraki Adım
Bekçi: sab:integrity-scan sonucunu kontrol et → agent-y'ye devir
```

> Agent raporu repository/test/browser/production kanıtı **yerine geçmez**.

---

## İki Ajan Aynı Dosyada Çalışırsa

1. **Dosyayı kilitle**: İlgili ajanın `PROJECT_STATE.md`'ye yazmasını iste
2. **Conflict resolution**: Sadece ilgili ajan çözüm üretir, diğeri bekle
3. **Ortak API sözleşmesi bozuksa**: İkisinin de testini ayrı çalıştır, `200` + `422` sonuçlarını sessizce kabul eden assertion **YASAK**

---

## Sınırlar

Bu yetenek ajanlar arasında koordinasyon sağlar, **kullanıcı adına production migration, seed, deploy, veri backfill veya silme yapmaz**.
