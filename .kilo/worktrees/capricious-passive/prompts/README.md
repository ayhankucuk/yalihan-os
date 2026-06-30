# prompts/ — AI Prompt ve Template Dosyaları

> AI agent'lar için prompt ve template dosyaları
> YalihanCortex ve diğer AI motorları için kaynak

## İçindekiler

| Dosya | Kullanım | Ne Zaman Güncellenir |
|--------|-----------|----------------------|
| `sab.md` | SAB özeti | SAB değiştiğinde |
| `context7.md` | Context7 naming standartları | Naming kuralı değiştiğinde |
| `cortex.md` | YalihanCortex pipeline | AI architecture değiştiğinde |

## Yapı

```
prompts/
├── README.md        ← Bu dosya
├── sab.md          ← SAB (Production Seal) özeti
├── context7.md     ← Context7 naming standartları
└── cortex.md      ← YalihanCortex AI pipeline
```

## Kullanım

```bash
# AI prompt oluştururken
"prompts/context7.md dosyasından naming kurallarını al"

# Yeni AI feature eklerken
"prompts/cortex.md dosyasını referans al"
```

## Diğer Klasörlerle İlişkisi

- `../agents/` — Agent instruction dosyaları (bu prompt'lar kim için?)
- `../memory/` — Öğrenilen kalıplar ve kararlar
- `../knowledge/` — Tampon bilgi tabanı

## Güncelleme Kuralları

1. Yeni AI feature için yeni prompt dosyası ekle
2. Prompt değişikliğini `memory/CHANGELOG_AGENT.md`'ye kaydet
3. Mimari değişikliklerde `memory/DECISIONS.md`'ye ekle
