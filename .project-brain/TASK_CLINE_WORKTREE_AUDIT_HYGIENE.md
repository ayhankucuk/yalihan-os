# 📋 CLINE GÖREV ŞARTNAMESİ: Worktree Hijyeni & Analizi

**Ajan:** Cline  
**Çalışma Alanı:** `cline/worktree-audit-hygiene`  
**Öncelik:** P1 / Orta  
**Tarih:** 2026-09-10  
**Kaynak:** `release-candidate/RC2` (ff89b98a → 1a0c14f0)  
**Referans Doktor Raporu:** 20 ✔ | 7 ▲ | 0 ✖ | ACCEPTABLE | 14/38 worktree kirli

---

## 🏗️ Worktree Açılış Komutu

```bash
git worktree add ../yalihan-os.cline-worktree-audit -b cline/worktree-audit-hygiene release-candidate/RC2
```

> ⚠️ ANA KURAL: **SİLME YOK.** `git worktree remove` ve `git branch -D` **KESİNLİKLE YASAK**. Yalnızca oku, analiz et, raporla. Tüm raporlar `cline/worktree-audit-hygiene` branch'ine commit'lenir.

---

## 🎯 Görev Amacı

38 worktree'den (14'ü kirli) birleşmiş ve bayat olanları tespit edip güvenli budama planı çıkarmak.

---

## 📌 Adım 1: Worktree Envanter Analizi

```bash
git worktree list
```

**Her worktree için topla:**
- Dal adı (branch)
- Commit hash
- Çalışma dizini
- Kirli mü? (`git status --short` ile)
- Son commit tarihi (`git log -1 --format="%ci" <commit>`)
- Ana dala (`integration/...` veya `release-candidate/RC2`) merge edilmiş mi?

---

## 📌 Adım 2: Kirli Worktree'lerin Durum Analizi

14 kirli worktree için:

```bash
# Her kirli worktree'de:
cd <worktree-dizini>
git status --short
git log -1 --format="%ci %s"
git diff HEAD --stat
```

**Sorulacak:**
- Değişiklikler ne zamandan beri commit'siz duruyor?
- Commit'lenmemiş değişiklikler yeni kod mu, yoksa sadece config/local değişikliği mi?
- Worktree'nin branch'i ana dala entegre edilmiş mi?

---

## 📌 Adım 3: Rapor Dosyası Üret

Dosya: `.project-brain/WORKTREE_HYGIENE_REPORT.md`

**Format:**

```markdown
# Worktree Hijyen Raporu — 2026-09-10

## Özet
- Toplam worktree: 38
- Kirli: 14
- Bayat (>30 gün commit yok): X
- Birleştirilmiş (merged): X

## Kategoriler

### 🟢 BUDANABİLİR (Merged / Safe to Prune)
Açıklama: Branch ana RC2'ye entegre edilmiş veya tamamen bayat.
| Worktree | Branch | Son Commit | Neden |
|---|---|---|---|
| ... | ... | ... | ... |

### 🟡 BEKLEYEN DEĞİŞİKLİK (Dirty / Uncommitted)
Açıklama: Değişiklikler var, değerlendirilmeli.
| Worktree | Branch | Kirli Dosyalar | Değişiklik Özeti |
|---|---|---|---|
| ... | ... | ... | ... |

### 🔴 KORUNMALI (Aktif dal veya RC adayı)
Açıklama: Aktif RC/release dalları, silinemez.
| Worktree | Branch | Durum |
|---|---|---|
| release-candidate/RC2 | 1a0c14f0 | ANA DAL |
| release/era-v-phase2a-rc1 | 249cfc14 | RC dalı |
| ... | ... | ... |

## Önerilen Eylem Planı
1. ...
2. ...
3. (Sadece onay sonrası uygula)
```

---

## ✅ Başarı Kriteri

- `.project-brain/WORKTREE_HYGIENE_REPORT.md` dosyası oluşturulmuş
- Tüm 38 worktree kategorize edilmiş (BUDANABİLİR / BEKLEYEN DEĞİŞİKLİK / KORUNMALI)
- Her kirli worktree için değişiklik özeti mevcut
- Hiçbir worktree silinmemiş (salt okunur analiz)

---

## 🔒 Kısıtlamalar

1. **`git worktree remove` YASAK** — raporla, silme
2. **`git branch -D` YASAK** — raporla, silme
3. **Migration/deploy YASAK** — salt okunur analiz
4. Sadece rapor üret, kararı kullanıcı verir
