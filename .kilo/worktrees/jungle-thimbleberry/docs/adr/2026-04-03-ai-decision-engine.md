# ADR-011: AI Decision Engine (sab-decide.sh)

## Context

SAB governance pipeline (propose → watch → run → apply → sync) çalışıyor ancak tüm proposallar manuel üretiliyor. Sistem sorun tespit edemiyor, sadece emir alıyor.

## Decision

`scripts/sab-decide.sh` — Guarded Auto Mode ile çalışan, 7 kurallı AI Decision Engine oluşturuldu.

**Risk katmanı:**
- `low` → otomatik proposal üretir, watcher apply eder
- `medium` → proposal üretir + warn loglar
- `high` → proposal üretmez, sadece loglar (manuel onay gerekir)

**7 kural:** authority sync drift, required fields, enforcement level, forbidden fields guard, history hygiene, version freshness, MCP config.

## Consequences

- Sistem kendi kendine sorun tespit edip çözüm üretebiliyor
- Her karar `_meta` bloğunda reason, risk, rule, engine, timestamp ile kayıt altında
- High-risk kararlar otomatik bloklanıyor (güvenlik katmanı)
- Decision log ayrı dosyada: `.sab/history/decisions.log`

## Alternatives Considered

- **Full auto (risk ayrımı yok):** Reddedildi — production'da tehlikeli
- **Sadece suggest (proposal üretmeden):** Reddedildi — mevcut pipeline'dan faydalanmıyor
- **Laravel Artisan command:** Reddedildi — SAB pipeline bash-native, dependency eklememek için bash'te kaldı
