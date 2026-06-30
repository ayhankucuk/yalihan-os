# Governance Agent Instructions

> Yalıhan Emlak — SAB ve Governance kuralları.

## SAB (Production Seal)

SAB projenin teknik anayasasıdır. Uygulanmadan iş "Done" kabul edilmez.

### Bağlayıcı Kurallar

1. Core (Ledger/CRM) **IMMUTABLE**
2. Core'a direkt write **YASAK**
3. Observer bypass **YASAK**
4. Silent catch **YASAK** (AST Bekçi v2.1 ile denetlenir)
5. Raw DB write **YASAK** (migration hariç)
6. Projection tabloları **sadece Read**
7. Context7 ihlal toleransı = **0**
8. DLQ **zorunlu**

### Phase 12 Ek Kurallar

- `tenant_id` finansal query'lerde **zorunlu**
- AI Circuit Breaker (`AiBudgetGuard`) **aktif**
- Bakiye mutasyonları sadece `recordDoubleEntry` ile

## Governance Komutları

```bash
# Mimari ihlal tara
php artisan sab:integrity-scan

# Strict CI modu
php artisan sab:guard

# Health kontrol
php artisan bekci:health --detailed

# Context7 validation
php artisan context7:validate-migration --all

# Env drift kontrol
php artisan system:env-drift-guard
```

## CI Pipeline (Gold Line)

```
test → sab:integrity-scan → bekci:wizard-contract → env-drift-guard → quality-gate.sh
```

## Authority SSOT

`.sab/authority.json` — Governance kurallarının tek kaynağı.
Kural çakışmasında referans.

## Naming Authority (Context7)

Domain model alanları Türkçe olmalı:

| ❌ Yasak | ✅ Kanonik |
|---------|-----------|
| `status` | `yayin_durumu` |
| `active` | `aktiflik_durumu` |
| `type` | `tip` |
| `description` | `aciklama` |

Bypass: `// context7-ignore`

## Quality Gate

Değişiklik sonrası:

```bash
./scripts/tools/antigravity-full-gate.sh
php artisan sab:integrity-scan
```

## SAB Checksum

`docs/SAB.md` değiştiyse checksum yenilenmeli:

```bash
php artisan sab:baseline
```

## Definition of Done

İş Done sayılır ancak:
- Context7 PASS (0 ihlal)
- Governance PASS
- Drift Detection PASS
- Test PASS
- Registry güncel

## Kaynaklar

- SAB: `docs/SAB.md`
- Authority: `.sab/authority.json`
- Changelog: `docs/BEKCI_CHANGELOG.md`
- Progress: `docs/PROGRESS-TRACKER.md`
