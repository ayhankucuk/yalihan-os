# agents/ — Agent Instruction Dosyaları

> AI agent'lar için proje kuralları ve talimatlar

## İçindekiler

| Dosya | Kim İçin | Ne Zaman Güncellenir |
|--------|-----------|----------------------|
| `backend.md` | Backend geliştirici | PHP/Laravel değişikliğinde |
| `frontend.md` | Frontend geliştirici | Blade/CSS değişikliğinde |
| `laravel.md` | Laravel uzmanı | Framework spesifik |
| `governance.md` | Governance takımı | SAB/authority değişikliğinde |
| `mcp.md` | MCP kullanıcıları | MCP config değişikliğinde |

## Kullanım

Her agent oturumunda ilk olarak okunmalı:

```bash
# Kilo oturumunda
"agents/backend.md dosyasını oku ve kuralları uygula"
```

## Yapı

```
agents/
├── README.md              ← Bu dosya
├── backend.md            ← Backend geliştirme kuralları
├── frontend.md           ← Frontend geliştirme kuralları
├── laravel.md            ← Laravel spesifik
├── governance.md          ← SAB ve governance
└── mcp.md              ← MCP server
```

## Diğer Klasörlerle İlişkisi

- `../prompts/` — Prompt ve template dosyaları
- `../memory/` — Agent hafızası
- `../workflows/` — Deploy ve CI/CD workflow'ları

## Güncelleme Kuralları

1. Yeni agent oluşturulduğunda buraya ekle
2. Kural değiştiğinde `memory/CHANGELOG_AGENT.md`'ye kaydet
3. Büyük değişikliklerde `memory/DECISIONS.md`'ye ekle
