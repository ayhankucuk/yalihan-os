# CI/CD Pipeline

> Yalıhan Emlak — Gold Line CI Pipeline

## Pipeline Sırası

```
1. php artisan test
2. php artisan sab:integrity-scan
3. php artisan bekci:wizard-contract
4. php artisan system:env-drift-guard
5. ./scripts/guards/quality-gate.sh
```

## GitHub Actions

- **Workflow**: `.github/workflows/gold-line.yml`
- **Bloklayıcı**: Herhangi bir gate FAIL = deployment blocked

## CI Guard Komutları

```bash
# Tenant isolation
php artisan guard:cqrs

# Routes
php artisan guard:routes:v2

# Schema drift
php artisan guard:schema

# Security boundary
php artisan guard:security
```

## Quality Gate

```bash
./scripts/guards/quality-gate.sh
```

## Fail Durumunda

1. Hata log'unu incele
2. `php artisan sab:integrity-scan --format=json` çalıştır
3. İhlalleri düzelt
4. Tekrar push et
