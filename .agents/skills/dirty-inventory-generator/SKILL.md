---
name: dirty-inventory-generator
description: Dirty git tree'yi otomatik sınıflandırır, öncelik/risk atar, karar önerileri üretir. .project-brain/ altında envanter dosyası üretir.
---

# Dirty Inventory Generator

## Amaç

Mevcut dirty tree'yi (modified + untracked) okur, kategorize eder, önceliklendirir. Çıktı olarak bir envanter raporu üretir. Bu rapor agent'a "ne yapılabilir" konusunda karar verir — agent değil.

## Tetikleyiciler

- Oturum başında `git status` kirli ise
- Kullanıcı "envanter çıkar", "dirty dosyaları analiz et" derse
- Yeni branch'te çalışmaya başlarken
- Başka bir agent'tan devralırken (session devralımı)

## Çalışma sırası

### Adım 1 — Durumu yakala

```bash
git status --short
git diff --stat
git log --oneline -3
git diff --staged --stat
```

### Adım 2 — Her dosyayı sınıflandır

Her modified/untracked dosyayı şu kategorilerden birine koy:

| Kategori | Kriter | Öncelik |
|----------|--------|---------|
| **Sabit (Known-Good)** | Daha önce envanterde geçmiş, karar verilmiş | Düşük |
| **Güvenli (Safe)** | Brain, SAB, BEKCI, skill, test, docs | Düşük |
| **Değişiklik (Changed)** | Değişiklik yapılmış, intent biliniyor | Orta |
| **Bilinmeyen (Unknown)** | Kim yaptı, ne için — bilinmiyor | Yüksek |
| **Hot-Spot** | Conflict Guard korumalı dosya | Kritik |
| **Gezgin (Orphan)** | Araştırma, geçici dosya | Düşük |

### Adım 3 — Öncelik matrisini oluştur

```
Yüksek Öncelik + Düşük Risk = Hemen commit et
Yüksek Öncelik + Yüksek Risk = Askıya al, intent sor
Düşük Öncelik + Düşük Risk = Güvenli paketle commit et
Düşük Öncelik + Yüksek Risk = Sil veya askıya al
```

### Adım 4 — Envanter dosyası üret

`.project-brain/RC2-DIRTY-INVENTORY.md` veya tarihli yeni dosya oluştur:

```markdown
# Dirty Envanter — [Tarih]

**Branch:** [branch-ismi]
**Commit:** [HEAD hash]
**Dirty dosya:** [sayı]
**Untracked:** [sayı]

## Yönetici Özet

| Durum | Sayı | Öncelik |
|-------|------|---------|
| Güvenli | X | Düşük |
| Değişiklik | Y | Orta |
| Bilinmeyen | Z | Yüksek |
| Hot-Spot | W | Kritik |
| Gezgin | V | Düşük |

## Dosya Listesi

[Her dosya için tablo]

## Önerilen Paketleme

| Paket | Dosya | Öncelik | Risk | Not |
|-------|-------|---------|------|-----|
| PAKET-A | a.php, b.php | HIGH | Düşük | Güvenli — commit et |
| PAKET-B | c.php | HIGH | Orta | Intent doğrula |
| PAKET-C | d.php | MEDIUM | Yüksek | Askıya al |

## Bilinen Tutarsızlıklar

[Varsa mevcut envanter/doküman ile farklar]

## Sonraki Adımlar

1. [ ] PAKET-A commit et
2. [ ] Hot-spot lock'ları al
3. [ ] Bilinmeyenleri intent doğrula
```

### Adım 5 — Karar ver

Raporu verdikten sonra kullanıcıya sor:

```
Toplam X dosya tespit edildi:
- Y tanesi güvenli → Hemen commit edebilirim
- Z tanesi hot-spot → Lock gerekli
- W tanesi bilinmeyen → Intent sor
```

## Kaçınılması gereken

- Envanter çıkarıp karar vermeden bırakmak (rapor = iş yapılmış değil)
- Dosyaları yanlış kategoriye koymak (migration'ı "güvenli" sanmak)
- Raporu commit etmemek (başka bir agent aynı analizi tekrarlar)

## Çıktı

Rapor + öneri. Agent raporu okur ve action alır.
