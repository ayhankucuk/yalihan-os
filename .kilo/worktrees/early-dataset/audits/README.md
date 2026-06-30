# audits/ — Audit Raporları

> Bekçi ve diğer otomatik denetim raporları

## İçindekiler

| Dosya | Kullanım | Ne Zaman Güncellenir |
|--------|-----------|----------------------|
| `README.md` | Klasör açıklaması | — |

## Rapor Formatı

```
audit-YYYY-MM-DD-{type}.md
bekci-audit-YYYY-MM-DD.json
```

## Audit Türleri

| Tür | Açıklama | Komut |
|-----|----------|-------|
| `sab` | SAB integrity scan | `php artisan sab:integrity-scan` |
| `context7` | Naming violations | `php artisan sab:integrity-scan` |
| `tenant` | Tenant isolation | `php artisan guard:cqrs` |
| `security` | Security boundary | `php artisan guard:security` |
| `mcp` | MCP health | `php artisan bekci:health` |

## Yapı

```
audits/
├── README.md              ← Bu dosya
├── audit-YYYY-MM-DD-*.md ← Rapor dosyaları
└── bekci-audit-*.json   ← JSON raporlar
```

## Rapor Oluşturma

```bash
# SAB scan
php artisan sab:integrity-scan > audits/sab-audit-$(date +%Y-%m-%d).md

# MCP health
php artisan bekci:health > audits/mcp-health-$(date +%Y-%m-%d).md
```

## Diğer Klasörlerle İlişkisi

- `../memory/` — Audit sonuçları memory'de özetlenir
- `../workflows/` — Audit CI/CD'nin parçası
- `../yalihan-bekci/` — Bekçi kaynakları

## Güncelleme Kuralları

1. Audit raporu = `audit-YYYY-MM-DD-{scope}.md`
2. JSON rapor = `bekci-audit-YYYY-MM-DD.json`
3. Önemli bulgu = `memory/LEARNED_PATTERNS.md`'ye ekle
4. Mimari sorun = `memory/DECISIONS.md`'ye ekle
