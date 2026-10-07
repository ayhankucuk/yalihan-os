# ATLAS — CANONICAL AUTHORITY MAP

**Tarih:** 2026-10-07
**HEAD:** c387e32a

---

## AMAÇ

> "Hangi soruda hangi kaynak authority?" sorusunun cevabı.

---

## CONCEPT → AUTHORITY MAPPING

### İş Kavramları

| Kavram | Canonical Authority | Kaynak | Evidence Level | Last Verified |
|--------|-------------------|--------|---------------|---------------|
| **Rezervasyon oluşturma** | IlanReservationService | app/Services | REPO_VERIFIED | c387e32a |
| **Tenant isolation** | BelongsToTenant trait | app/Traits | REPO_VERIFIED | c387e32a |
| **İlan fiyat** | Ilan model | app/Models | REPO_VERIFIED | c387e32a |
| **Kisi state** | KisiDurumu enum | app/Enums | REPO_VERIFIED | c387e32a |
| **Rezervasyon tarih** | Migration schema | database/migrations | REPO_VERIFIED | c387e32a |

### Mimari Kararlar

| Karar | Canonical Authority | Kaynak | Evidence Level |
|-------|-------------------|--------|---------------|
| **Layering** | AGENTS.md Gate 3 | AGENTS.md | INHERENT |
| **Migration stratejisi** | AGENTS.md Gate 6 | AGENTS.md | INHERENT |
| **Duplicate önleme** | AGENTS.md Gate 2 | AGENTS.md | INHERENT |
| **Tenant isolation** | AGENTS.md Gate 5 | AGENTS.md | INHERENT |

### Business Kararlar

| Karar | Canonical Authority | Kaynak | Evidence Level |
|-------|-------------------|--------|---------------|
| **Fiyat değişikliği** | Ayhan | Human Decision | AYHAN |
| **Rezervasyon iptal** | Ayhan + Policy | Human + IlanReservationPolicy | AYHAN |
| **Ödeme trigger** | Ayhan | Human Decision | AYHAN |

### Technical Gerçekler

| Gerçek | Canonical Authority | Kaynak | Evidence Level |
|--------|-------------------|--------|---------------|
| **Mevcut kod** | Code + Schema | Repository | TEST_VERIFIED |
| **Migration** | database/migrations | Repository | REPO_VERIFIED |
| **Test sonucu** | phpunit output | Runtime | TEST_VERIFIED |
| **Schema** | actual DB | Migration files | REPO_VERIFIED |

---

## CLAIM TYPE → AUTHORITY

| Claim Türü | Birincil Otorite | İkincil Otorite |
|------------|-----------------|-----------------|
| Business karar | Ayhan | - |
| Mimari gerekçe | DECISION_LOG / ADR | AGENTS.md |
| Repository implementation | CODE + SCHEMA | - |
| Test edilmiş davranış | Test output | Test file |
| Domain iş mantığı | DOMAIN_KNOWLEDGE | Code + Schema |
| Production durumu | Production evidence | - |
| Makine kuralı | `.sab/` config | - |
| Tenant mekanizması | BelongsToTenant trait | - |

---

## ÖNEMLİ KAVRAMLAR

### Tenant Isolation

| Kavram | Authority | Kanıt |
|--------|-----------|-------|
| Tenant mechanism | BelongsToTenant trait | app/Traits/BelongsToTenant.php |
| Schema | tenant_id column | Migration files |
| Query scope | TenantScope | app/Scopes/TenantScope.php |

### Reservation

| Kavram | Authority | Kanıt |
|--------|-----------|-------|
| Canonical model | IlanReservation | app/Models/IlanReservation.php |
| Date fields | start_date/end_date | Migration 2026_09_17_... |
| Service | IlanReservationService | app/Services |
| Policy | IlanReservationPolicy | app/Policies |

### Finans

| Kavram | Authority | Kanıt |
|--------|-----------|-------|
| Canonical model | FinansalIslem | app/Modules/Finans/Models/ |
| **tenant_id** | **EKSIK** | **REVALIDATED: c387e32a** |
| Service | FinansService | app/Services |

---

## REVALIDATION GEREKENLER

| Kavram | Son Bilinen | REVALIDATED? |
|--------|-------------|--------------|
| FinansalIslem tenant_id | EKSIK | Evet (c387e32a) |
| IlanReservation tenant | FIXED (e2c4c227) | Evet (c387e32a) |

---

## KURAL

```
Her yeni bulgu için:
  1. Canonical authority belirle
  2. Kaynağı işaretle
  3. Evidence level ata
  4. Last verified kaydet

Memory hiçbir zaman tek başına:
  "Repository'de X var" iddiası için yeterli değil.
  → CODE + SCHEMA doğrulaması gerekir.
```
