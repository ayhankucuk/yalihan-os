---
name: conflict-guard-preflight
description: Commit öncesi hot-spot dosyaları tespit eder, Conflict Guard lock durumunu kontrol eder, önlem alır.
---

# Conflict Guard Preflight

## Amaç

Commit etmeden önce Conflict Guard hot-spot ihlallerini önceden tespit eder. Lock alınmamış hot-spot dosyaları stage edilirse pre-commit blocker patlar — bunu **önceden** bil ve engelle.

## Tetikleyiciler

- `git add` sonrası, `git commit`之前
- Yeni config veya route dosyası eklenirken
- Hot-spot korumalı dosyalarda değişiklik yaparken
- Kullanıcı "commit'ten önce kontrol et" derse

## Çalışma sırası

### Adım 1 — Hot-spot listesi kontrol et

Conflict Guard korumalı dosyalar (pre-commit hook tarafından kontrol edilir):

```
# Sabit hot-spot'lar (her zaman korumalı)
.sab/authority.json
.sab/sab-baseline.json

# Dinamik hot-spot'lar (pattern ile korumalı)
config/feature-flags.php
config/exchange.php
routes/admin.php
routes/*.php                    # Tüm route dosyaları
config/*.php                    # Tüm yeni config dosyaları potansiyel hot-spot
```

### Adım 2 — Staged dosyalarda hot-spot taraması

```bash
# Hot-spot dosyalar staged'de mi?
git diff --staged --name-only | grep -E '^\.sab/|^config/|^routes/'

# Hot-spot var mı kontrol et
./scripts/tools/conflict-guard.sh --check "config/feature-flags.php"
./scripts/tools/conflict-guard.sh --check "routes/admin.php"
```

### Adım 3 — Lock durumunu kontrol et

```bash
# Lock mevcut mu?
grep "HOTSPOT_LOCK" .project-brain/PROJECT_STATE.md | grep "feature-flags.php"
grep "HOTSPOT_LOCK" .project-brain/PROJECT_STATE.md | grep "routes/admin.php"

# Lock'un süresi dolmuş mu?
# Format: HOTSPOT_LOCK:<file>:<agent>:<timestamp>:<ttl>
# TTL: saniye cinsinden (3600 = 1 saat)
```

### Adım 4 — Lock yoksa: lock al veya çıkar

**Seçenek A: Lock al (başkası değiştiriyorsa)**
```bash
./scripts/tools/conflict-guard.sh --acquire "config/feature-flags.php" "<agent-ismi>" 3600
```

**Seçenek B: Dosyayı staged'den çıkar (başkası halledecekse)**
```bash
git restore --staged <hot-spot-dosya>
```

**Seçenek C: Dosyayı ayrı commit et (intent netse)**
```bash
git add <hot-spot-dosya>
git commit -m 'chore: update <dosya> -- HOTSPOT_LOCK acquired'
```

### Adım 5 — COMMIT ET

Conflict Guard pre-commit hook'u otomatik çalışır. Hot-spot lock'luysa geçer, lock'suzsa bloke eder.

## Bilinen Hot-Spot Kaskadı

Bir commit'te yeni config/route dosyası eklemek, başka bir hot-spot'u tetikleyebilir. Sıralı kontrol et:

```
Yeni bir config dosyası → conflict-guard blocker
Yeni bir route dosyası → conflict-guard blocker
Yeni bir .sab dosyası → authority.json baseline tetiklenebilir
```

## Hot-Spot Lock Formatı

`PROJECT_STATE.md`'deki lock kaydı:
```
HOTSPOT_LOCK:<file_pattern>:<agent>:<ISO_timestamp>:<ttl_seconds>
```

Örnek:
```
HOTSPOT_LOCK:config/feature-flags.php:cline:2026-09-14T10:00:00Z:3600
```

## Kaçınılması gereken

- Hot-spot dosyayı "sessizce" staged'den çıkarıp unutmak
- Lock TTL'si dolduktan sonra commit denemek
- Birden fazla agent'ın aynı hot-spot'a lock almaya çalışması (önce kontrol et)
- Lock'u timeline'da süresiz uzatmak

## MCP Entegrasyon

```text
Soru: "Bu dosya hot-spot mu?"
→ MCP: check_violation(file_path) → hot-spot/önemli/değiştirilmiş

Soru: "Lock durumu ne?"
→ manual: grep "HOTSPOT_LOCK" .project-brain/PROJECT_STATE.md
```
