# knowledge/ — Bilgi Tabanı

> AI ve agent'lar için tampon bilgi tabanı
> Otomatik oluşturulan ve güncellenen dosyalar

## İçindekiler

| Alt Klasör | İçerik | Güncelleme |
|------------|---------|-------------|
| `learning/` | Öğrenme kayıtları | MCP veya agent oturumlarında |
| `patterns/` | Mimari pattern'ler | Yeni pattern keşfedildiğinde |
| `agents/` | Agent-spesifik notlar | Agent çalışmasında |

## Yapı

```
knowledge/
├── README.md           ← Bu dosya
├── learning/          ← MCP/agent öğrenmeleri
│   └── *.json        ← Oturum kayıtları
├── patterns/          ← Mimari pattern'ler
│   └── *.md
└── agents/            ← Agent notları
    └── *.md
```

## Kaynak (Yalihan-Bekci)

Bu klasörün ana kaynağı `../yalihan-bekci/`:

```
yalihan-bekci/
├── knowledge/         → Node MCP öğrenmeleri
├── learning/         → PHP Audit öğrenmeleri
└── ideas/            → AI tarafından üretilen fikirler
```

## Kullanım

```bash
# Yeni öğrenme keşfedildiğinde
"knowledge/learning/ dosyasına otomatik kaydet"

# Pattern keşfedildiğinde
"knowledge/patterns/ altına ekle"
```

## Diğer Klasörlerle İlişkisi

- `../memory/` — Kalıcı hafıza (daha uzun ömürlü)
- `../agents/` — Agent instruction dosyaları
- `../yalihan-bekci/` — Asıl knowledge kaynağı

## Güncelleme Kuralları

1. Yeni öğrenme = JSON dosyası olarak `learning/` altına
2. Yeni pattern = Markdown olarak `patterns/` altına
3. Büyük öğrenme = `memory/LEARNED_PATTERNS.md`'ye transfer et
