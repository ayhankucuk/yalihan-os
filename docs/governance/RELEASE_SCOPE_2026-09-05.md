# YALIHAN OS — Release Kapsamı ve Entegrasyon Sahipliği

> **Tarih**: 2026-09-05 (Oturum 157)
> **Prensip**: Tek release adayı, tek entegrasyon sahibi

---

## Release Adayı

**Branch**: `release-candidate/RC2`
**Durum**: Tek release adayı. Tüm dallar RC2'ye merge edildi.

### Branch Durumu (2026-09-05)

| Branch | RC2'ye göre | Durum |
|--------|-------------|-------|
| `release-candidate/RC2` | — | **TEK RELEASE ADAYI** |
| `integration/era-v-phase2a-e01` | 0 commit ahead | ✅ Tamamen RC2'ye merge edildi |
| `main` | 2 commit (içerik zaten RC2'de) | ✅ İçerik aynı, cherry-pick empty |
| `production-sync` | N/A | ❌ Artık kullanılmıyor |

### RC2 İçerik Özeti

- 389 commit ahead of `main`
- Integration branch tamamen merge edildi (commit `e67b151b`)
- `main`'deki 2 legal compliance commit içeriği zaten RC2'de mevcut
- Tüm BACKLOG-1/9 remediation işleri RC2'de
- GAP-03 fix RC2'de
- BACKLOG-5/9 (Lead tenant boundary) RC2'de
- #37 SQLite schema gap çözüldü
- #38 GAP-03 DTO retryable fix çözüldü

---

## Entegrasyon Sahipliği

### Tek Entegrasyon Sahibi: **Codex (Code Mode)**

Codex, RC2 branch'inin tek entegrasyon sahibidir. Sorumluluklar:

1. **Merge yetkisi**: Sadece Codex RC2'ye merge yapar
2. **Conflict çözme**: Merge conflict'leri Codex çözer
3. **Test kapısı**: Merge öncesi test suite çalıştırma
4. **Push yetkisi**: RC2'ye push sadece Codex tarafından yapılır
5. **Durum raporu**: Her merge sonrası BEKCI_CHANGELOG güncellenir

### Görev Dağılımı

| Rol | Agent | Sorumluluk |
|-----|-------|------------|
| **Entegrasyon Sahibi** | Codex | RC2 merge, conflict, push, changelog |
| **Implementasyon** | Kilo/Cline | Kod yazma, test yazma, PR açma |
| **Bağımsız Doğrulama** | Wenox | Test çalıştırma, güvenlik doğrulama |
| **Risk Denetimi** | Antigravity | Data/architecture risk audit |

### Ayrım Kuralı

**Aynı agent hem implementasyon hem final onay yapamaz.**

- Kod yazan agent (Kilo/Cline) test geçse bile "Tamamlandı" diyemez
- Wenox bağımsız olarak testleri çalıştırır ve doğrular
- Codex entegrasyonu yapar ve RC2'ye alır
- Production doğrulaması (S4) ayrı bir adım olarak yapılır

---

## Release Kapsamı — RC2

### Dahil Edilen İşler

| İş | Aşama | Kanıt |
|----|-------|-------|
| BACKLOG-1 (Secret Scanner) | S1-S3 | Pre-commit hook aktif |
| BACKLOG-2 (Conflict Guard) | S1-S3 | `scripts/tools/conflict-guard.sh` |
| BACKLOG-3 (Backend Guard) | S1-S3 | `.agents/skills/SKILL_INDEX.md` |
| BACKLOG-4 (Auth Boundary CI) | S1-S3 | `.github/workflows/security-auth-boundary.yml` |
| BACKLOG-5 (Lead Tenant Boundary) | S1-S4 | VPS'de index doğrulandı |
| BACKLOG-6 (Rate-Limit Race) | S1-S3 | Atomic cache operations |
| BACKLOG-7 (Security Log Mask) | S1-S3 | SecurityMiddleware mask |
| BACKLOG-8 (Photo Race Condition) | S1-S3 | Unique composite index |
| BACKLOG-9 (Lead Unique Index) | S1-S4 | VPS'de migration [18] Ran |
| GAP-03 (Retryable Channel) | S1-S3 | ChannelSynchronizationException |
| #37 (SQLite Schema Gap) | S1-S3 | Tests 11/11 + 4/4 PASS |
| #38 (GAP-03 Debt) | S1-S3 | Exception class + retryable check |
| #39 (Hermes Wiring) | S1-S3 | AnalyticsHandler + EventBus |
| TD-14 (kapak_fotografi) | S1-S3 | Migration + model fix |
| Canonical Tables | S1-S3 | `config/canonical_tables.php` |
| Model Schema Contract | S1-S3 | `ModelSchemaContractTest.php` |

### RC2 Dışı Borç (Açık)

| Borç | Sahip | Kapanış Koşulu | Öncelik |
|------|-------|----------------|---------|
| PublishGuardTest fixture drift | Codex | Fixture score=99 üretecek | P2 |
| ModelSchemaContractTest drift (7 fail) | Codex | Model $fillable/$casts ↔ DB hizala | P2 |
| SAB Legacy Violations (153) | Known | known-debt #3 altında | P3 |
| SAB Context7 Violations (175) | Known | known-debt #14 altında | P3 |
| TD-13 (ai_saglayici_profilleri) | Known | known-debt #40 altında | P2 |

---

## Release Sonrası Adımlar

1. **S4 Production Doğrulama**: RC2 production'a deploy edilir
2. **Migration doğrulama**: `php artisan migrate:status` VPS'de
3. **Smoke test**: Kritik route'lar test edilir
4. **User flow doğrulama**: İlan oluşturma → fotoğraf → yayınlama → CRM
5. **Bekçi health**: `bekci:health --detailed` ile sistem sağlığı kontrol
6. **Main'e merge**: RC2 doğrulandıktan sonra `main`'e merge edilir
