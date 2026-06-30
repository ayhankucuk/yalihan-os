# workflows/ — Otomasyon Workflow'ları

> Deploy, CI/CD ve diğer operasyonel otomasyonlar

## İçindekiler

| Dosya | Kullanım | Ne Zaman Güncellenir |
|--------|-----------|----------------------|
| `deploy.md` | Production deploy prosedürü | Deploy stratejisi değiştiğinde |
| `ci-cd.md` | CI/CD pipeline | Pipeline değiştiğinde |

## Yapı

```
workflows/
├── README.md        ← Bu dosya
├── deploy.md        ← Hetzner deploy prosedürü
└── ci-cd.md        ← Gold Line CI pipeline
```

## Deploy (deploy.md)

```bash
# Hetzner CX33 — 157.180.116.63
ssh ubuntu@157.180.116.63
# Adımlar: Bkz. workflows/deploy.md
```

## CI/CD (ci-cd.md)

```bash
# Gold Line Pipeline
php artisan test
php artisan sab:integrity-scan
php artisan bekci:wizard-contract
php artisan system:env-drift-guard
./scripts/guards/quality-gate.sh
```

## Diğer Klasörlerle İlişkisi

- `../agents/governance.md` — Governance kuralları (CI/CD buraya refer)
- `../audits/` — Audit raporları
- `../memory/` — Workflow değişikliklerini kaydet

## Güncelleme Kuralları

1. Yeni workflow = Markdown olarak bu klasöre ekle
2. Deploy değişikliği = `memory/CHANGELOG_AGENT.md`'ye kaydet
3. Kritik workflow = `memory/DECISIONS.md`'ye ekle
