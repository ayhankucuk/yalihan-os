# Tech Debt Registry

**Format:** Technical debt inventory with ownership
**Last Updated:** 2026-09-03
**SSOT:** Bu dosya = Teknik borç ana kaynağı

---

## GHOST FIELDS (Model-Schema Drift)

| ID | Model | Field | Type | Severity | Owner | Evidence | Solution | Closure Criteria |
|----|-------|-------|------|----------|-------|---------|---------|----------------|
| GF-001 | `YayinTipi` | `adi` | $fillable ghost | CRITICAL | Wenox | REPO_VERIFIED | Model'den kaldır | $fillable'dan çıkarıldığı test edildi |
| GF-002 | `Ilan` | `is_active` | $fillable+$casts phantom | HIGH | Wenox | REPO_VERIFIED | Model'den kaldır | $fillable/$casts'tan çıkarıldı |
| GF-003 | `Ozellik` | `aciklama` | $fillable phantom | MEDIUM | Wenox | REPO_VERIFIED | Kaldır veya DB'ye ekle | Karar verildi |
| GF-004 | `Ozellik` | `veri_secenekleri` | $casts phantom | MEDIUM | Wenox | REPO_VERIFIED | Kaldır veya DB'ye ekle | Karar verildi |
| GF-005 | `FeaturePack` | `display_order` | $fillable+$casts phantom | MEDIUM | Wenox | REPO_VERIFIED | Kaldır veya DB'ye ekle | Karar verildi |
| GF-006 | `Feature` | `deprecated_at` | $casts phantom | LOW | Wenox | REPO_VERIFIED | Kaldır veya DB'ye ekle | Karar verildi |

---

## SCHEMA DRIFT (83 Tables)

**Status: INFERRED — DO NOT TREAT AS FACT**

| ID | Table | Status | Migration Source | Production Exists | Canonical/Legacy | Action |
|----|-------|--------|-----------------|------------------|------------------|--------|
| SD-001 | `ai_abuse_signals` | UNKNOWN | ? | ? | ? | Inventory gerekli |
| SD-002 | `ai_call_analyses` | UNKNOWN | ? | ? | ? | Inventory gerekli |
| SD-003 | `ai_category_analytics` | UNKNOWN | ? | ? | ? | Inventory gerekli |
| ... | (80 more) | UNKNOWN | ? | ? | ? | Inventory gerekli |

**NOT:** Bu tabloların "eksik migration" olduğu varsayımı HENÜZ KANITLANMADI.
Olası sebepler:
- SQL dump ile oluşturulmuş olabilir
- Eski baseline'dan gelmiş olabilir
- Başka migration adıyla yaratılmış olabilir

**Required Action:** Her tablo için envanter çıkarılacak (production'da var mı, migration sahibi var mı, silinecek mi korunacak mı)

---

## DEPRECATED USAGE

| ID | Location | Type | Count | Severity | Owner | Solution |
|----|---------|------|-------|----------|-------|---------|
| DEP-001 | Global | `@deprecated` annotation | 108 | LOW | All | Planlı kaldırma |
| DEP-002 | AI Providers | `openai` v0.x | 98 | MEDIUM | Cortex | Upgrade planı |

---

## CODE QUALITY

| ID | Issue | Count | Severity | Owner | Solution |
|----|-------|-------|----------|-------|---------|
| CQ-001 | PHPStan errors | 23 | LOW | All | Düzelt veya ignore ekle |
| CQ-002 | TODO/FIXME comments | 30 | LOW | All | Takvim ekle veya kaldır |
| CQ-003 | Rate limiting | 2 | HIGH | Wenox | Yaygınlaştır |

---

## CONFIGURATION DRIFT

| ID | Issue | Severity | Owner | Solution |
|----|-------|----------|-------|---------|
| CD-001 | APP_DEBUG=true in .env | HIGH | All | Production'da false olmalı |
| CD-002 | 86 config dosyası | MEDIUM | All | Temizlik gerekli |

---

## EVIDENCE LABELS FOR THIS FILE

| Label | Meaning |
|-------|---------|
| `REPO_VERIFIED` | Kod/şema analizi ile doğrulandı |
| `TEST_VERIFIED` | Test ile doğrulandı |
| `PRODUCTION_VERIFIED` | Production'da görüldü |
| `INFERRED` | Çıkarım, kanıt yok |
| `UNKNOWN` | Bilinmiyor |

---

## OWNERSHIP

| Domain | Owner |
|--------|-------|
| Schema/Migration | Wenox |
| Model ($fillable/$casts) | Wenox |
| AI/Cortex | Cortex |
| Frontend | Google |
| Security | Antigravity |

---

*Generated: 2026-09-03T13:50:00Z*
*Update when: new debt discovered, debt resolved, ownership changes*
