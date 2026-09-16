# P0-1 RC2 Dirty Inventory — 2026-09-12

## Durum Kararı
```
P0-1: PARTIAL
WRITE_GATE: BLOCKED
DIRTY_TOTAL: 53 (31 M + 22 ??)
```

## Tam Dirty Envanter — 53 öğe

### Tracked Files (31 M)
| # | Dosya | İş Paketi | Domain | Handoff | Sahip |
|---|---|---|---|---|---|
| 1 | `.project-brain/EVIDENCE_INDEX.md` | Bekçi audit | Governance | OWNED_WIP | Bu oturum |
| 2 | `.project-brain/KNOWN_ISSUES.md` | Bekçi audit | Governance | OWNED_WIP | Bu oturum |
| 3 | `.project-brain/PROJECT_STATE.md` | Checkpoint | Meta | OWNED_WIP | Bu oturum |
| 4 | `.sab/authority.json` | Checkpoint | Governance | OWNED_WIP | Bu oturum |
| 5 | `AGENTS.md` | Checkpoint | Meta | OWNED_WIP | Bu oturum |
| 6 | `app/Console/Commands/YalihanBekciHealthCommand.php` | Bekçi audit | Governance | **HANDOFF_REQUIRED** | Bu oturum |
| 7 | `app/Services/Bekci/AuditMcpServer.php` | Bekçi audit | Governance | **HANDOFF_REQUIRED** | Bu oturum |
| 8 | `app/Services/Bekci/Scanners/ArchitectureGuardScanner.php` | Bekçi audit | Governance | OWNED_WIP | Bu oturum |
| 9 | `app/Http/Controllers/Api/IlanWizardController.php` | Strangler Fig | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 (01eec131) |
| 10 | `app/Http/Controllers/Api/V1/LocationPoiController.php` | Unknown | Location | **UNKNOWN_OWNER** | — |
| 11 | `app/Http/Requests/Owner/StoreOwnerIlanRequest.php` | Unknown | Wizard | **UNKNOWN_OWNER** | — |
| 12 | `app/Services/CRM/KisiScoringService.php` | Unknown | CRM | **UNKNOWN_OWNER** | — |
| 13 | `app/Services/Location/PoiService.php` | Unknown | Location | **UNKNOWN_OWNER** | — |
| 14 | `app/Services/Wizard/FieldEngine/FieldResolver.php` | Hexagonal DDD | Wizard | **HANDOFF_REQUIRED** | d592f404 |
| 15 | `config/feature-flags.php` | Unknown | Config | **UNKNOWN_OWNER** | — |
| 16 | `config/location.php` | Unknown | Config | **UNKNOWN_OWNER** | — |
| 17 | `database/migrations/2026_08_04_*_kategori_yayin_tipi_field_dependencies` | Hexagonal DDD | Ilan | **HANDOFF_REQUIRED** | d592f404 |
| 18 | `database/migrations/2026_08_23_000002_create_c51_settlement_domain_tables` | Hexagonal DDD | C51 | **HANDOFF_REQUIRED** | d592f404 |
| 19 | `database/migrations/2026_08_23_000004_create_bank_accounts_table` | Hexagonal DDD | Financial | **HANDOFF_REQUIRED** | d592f404 |
| 20 | `database/migrations/2026_08_24_000001_create_workforce_executions_table` | Hexagonal DDD | Workforce | **HANDOFF_REQUIRED** | d592f404 |
| 21 | `database/migrations/2026_09_04_*_add_unique_composite_index_to_ilan_fotograflari` | Unknown | Ilan | **UNKNOWN_OWNER** | — |
| 22 | `database/seeders/DatabaseSeeder.php` | Unknown | Seeder | **UNKNOWN_OWNER** | — |
| 23 | `docs/BEKCI_CHANGELOG.md` | Checkpoint | Governance | OWNED_WIP | Bu oturum |
| 24 | `docs/PROGRESS-TRACKER.md` | Checkpoint | Meta | OWNED_WIP | Bu oturum |
| 25 | `resources/js/app.js` | Unknown | Frontend | **UNKNOWN_OWNER** | — |
| 26 | `resources/views/frontend/ilanlar/show.blade.php` | Unknown | Frontend | **UNKNOWN_OWNER** | — |
| 27 | `resources/views/owner/ilanlar/create.blade.php` | Wizard | Wizard | **UNKNOWN_OWNER** | — |
| 28 | `routes/admin.php` | Unknown | Routes | **UNKNOWN_OWNER** | — |
| 29 | `routes/admin/talepler.php` | CRM | Routes | **UNKNOWN_OWNER** | — |

### Untracked Files (18 ??)
| # | Dosya | İş Paketi | Domain | Handoff | Sahip |
|---|---|---|---|---|---|
| 30 | `YALIHAN_OS_RESEARCH/` | Araştırma | Meta | OWNED_WIP | Araştırma |
| 31 | `app/Application/Ilan/` | Strangler Fig | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 (01eec131) |
| 32 | `app/Domain/Ilan/Policies/CategoryFieldPolicy.php` | Form Domain | Wizard | **UNKNOWN_OWNER** | Sahiplik iddiası — kabul edilmedi |
| 33 | `app/Domain/Ilan/ValueObjects/FieldDefinition.php` | Form Domain | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 (01eec131) |
| 34 | `app/Domain/Ilan/ValueObjects/FieldKey.php` | Form Domain | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 (01eec131) |
| 35 | `app/Domain/Ilan/ValueObjects/ValidationRule.php` | Form Domain | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 (01eec131) |
| 36 | `app/Domain/Location/` | Unknown | Location | **UNKNOWN_OWNER** | — |
| 37 | `app/Listeners/Wizard/` | Unknown | Wizard | **UNKNOWN_OWNER** | — |
| 38 | `config/exchange.php` | Unknown | Config | **UNKNOWN_OWNER** | — |
| 39 | `database/seeders/legacy/` | Legacy cleanup | Seeder | **UNKNOWN_OWNER** | — |
| 40 | `docs/SAB/` | SAB refresh | Governance | **UNKNOWN_OWNER** | — |
| 41 | `docs/adr/2026-09-12-adr043-*.md` | ADR-043 | Governance | **UNKNOWN_OWNER** | — |
| 42 | `docs/architecture/DATA_CONTRACT_AND_SEEDER_GOVERNANCE.md` | ADR-043 | Governance | **UNKNOWN_OWNER** | — |
| 43 | `docs/architecture/YALIHAN_OS_ENTERPRISE_TAXONOMY.md` | Unknown | Meta | **UNKNOWN_OWNER** | — |
| 44 | `scripts/services/bekci-mcp-lifecycle.sh` | Bekçi audit | Governance | OWNED_WIP | Bu oturum |
| 45 | `tests/Feature/Location/` | Unknown | Tests | **UNKNOWN_OWNER** | — |
| 46 | `tests/Feature/Wizard/FormFieldContractParityTest.php` | Form Domain | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 |
| 47 | `tests/Unit/Domain/Ilan/` | Form Domain | Wizard | **HANDOFF_REQUIRED** | RC2 Paket 1 |
| 48 | `.project-brain/P0-1-RC2-INVENTORY.md` | Bu rapor | Meta | OWNED_WIP | Bu oturum |
| 49 | `.project-brain/TENANT-FEATURE-ASSIGNMENT-01A.md` | Discovery | Meta | OWNED_WIP | Bu oturum |
| 50 | `D database/seeders/OzellikKategoriSeeder.php` | Karantina | Seeder | KARANTİNA | Sahipsiz |
| 51 | `D database/seeders/PropertyHubOzelliklerSeeder.php` | Karantina | Seeder | KARANTİNA | Sahipsiz |
| 52 | `.project-brain/SECURITY-WIZARD-FEATURE-SUGGESTIONS-01.md` | SECURITY-WIZARD | Governance | OWNED_WIP | Bu oturum |

## Karantina Özeti
| Dosya | Durum | Not |
|---|---|---|
| `database/seeders/OzellikKategoriSeeder.php` | DELETED | Karantina — sahibi belirsiz |
| `database/seeders/PropertyHubOzelliklerSeeder.php` | DELETED | Karantina — sahibi belirsiz |

## Gate Durumu
- Gate: ❌ **FAIL** (1 yeni bloklayıcı)
- Bloklayıcı: `app/Domain/Ilan/Policies/CategoryFieldPolicy.php:301` — Code Style, Line too long
- Sahibi: `UNKNOWN_OWNER` (RC2 Paket 1 iddiası — sahiplik kabul edilmedi) — ayrı worktree'de düzeltilmeli

## Tenant Raw-DB Envanteri (Düzeltilmiş Sınıflandırma)

| Dosya:-satır | İşlem | Tablo | Sınıflandırma | Not |
|---|---|---|---|---|
| `AiFieldSuggestionEngine:155` | READ | `features` | GLOBAL_REFERENCE | Scope yok, bilinçli okuma |
| `AiFieldSuggestionEngine:170` | READ | `feature_assignments` | READ_ONLY_MASTER | Scoped duplicate check, tenant_id nullable |
| `AiFieldSuggestionEngine:190` | READ | `feature_assignments` | READ_ONLY_MASTER | max() display_order |
| `AiFieldSuggestionEngine:196` | WRITE (Eloquent) | `feature_assignments` | **TENANT_CUSTOM_WRITE_UNPROVEN** | `::create()` Eloquent; tenant_id fillable'da YOK; tenant-context davranışı test edilmedi |
| `AiFieldSuggestionEngine:241` | READ | `feature_assignments` | READ_ONLY_MASTER | rollback duplicate check |
| `AiFieldSuggestionEngine:254` | WRITE (Query Builder) | `feature_assignments` | **TENANT_CUSTOM_WRITE_UNPROVEN** | rollback update; `tenant_id` fillable'da YOK |
| `YayinTipiSablonuResolver:64` | READ | `yayin_tipleri` | READ_ONLY_MASTER | FK validation, scope yok |
| `YayinTipiSablonuResolver:69` | READ | `yayin_tipi_sablonlari` | READ_ONLY_MASTER | Template lookup |
| `YayinTipiSablonuResolver:139` | READ | `yayin_tipi_sablonlari` | READ_ONLY_MASTER | exists() check |
| `YayinTipiSablonuResolver:160` | READ | `yayin_tipleri` | READ_ONLY_MASTER | Join data |
| `YayinTipiSablonuResolver:177` | READ | `yayin_tipi_sablonlari` | READ_ONLY_MASTER | ID resolution |
| `YayinTipiSablonuResolver:186` | READ | `yayin_tipleri` | READ_ONLY_MASTER | Slug resolution |
| `PropertyPublicationPolicy:95` | READ | `ilan_kategorileri` | READ_ONLY_MASTER | Policy check |
| `CategoryFeatureMatrixSeeder:36-408` | WRITE | `features`, `feature_assignments` | **UNKNOWN** | tenant_id fillable'da yok; focused test gerekli |
| `TenantBaselineSeeder:44` | WRITE | `tenants` | TENANT_SCOPED | Global tenant table |
| `AdminUserSeeder:24-27` | READ | `tenants`, `roles` | GLOBAL_REFERENCE | Seed setup |

### Kritik Tenant Bulgusu — TENANT_CUSTOM_WRITE_UNPROVEN
- Migration [46]/[47] çalışmış → **YEREL DB** için geçerli; production durumu ayrı kanıt ister
- Runtime önkoşul: `Schema::hasColumn('feature_assignments', 'tenant_id')` kontrol edilmeli
- `FeatureAssignment::$fillable`'da `tenant_id` **yok**
- `AiFieldSuggestionEngine` tenant context göndermiyor
- **→ TENANT_CUSTOM_WRITE_UNPROVEN** — focused test gerekli

## Migration Envanteri (migrate:status)
```
[45] 2026_08_25_000001_seed_villa_feature_assignments    Ran
[46] 2026_08_25_150345_add_tenant_id_to_feature_assignments Ran  ← tenant_id mevcut
[47] 2026_08_25_150439_add_tenant_aware_unique_index     Ran  ← tenant_id nullable + unique
```
Tam migration envanteri için `migrate:status` çıktısı ayrı çalıştırılmalıdır (sadece 3 dosya listelendi, tam envanter değil).

## Worktree Kaydı

| Worktree | Branch | Durum | İlgili Görev |
|---|---|---|---|
| `yalihan-os.worktrees/codex-security-wizard-01` | `codex/security-wizard-feature-suggestions-01` | `ACTIVE_DIRTY` | `SECURITY-WIZARD-FEATURE-SUGGESTIONS-01` (P0, DESIGN_APPROVED) |

## Write Gate İçin Gerekli Adımlar

- [ ] **HANDOFF_REQUIRED** dosyaların sahipleri teyit edilmeli
- [ ] **UNKNOWN_OWNER** dosyalar için sahip atanmalı veya karantina paketi açılmalı
- [ ] `CategoryFieldPolicy.php:301` Gate bloklayıcısı — ayrı worktree'de düzeltilmeli
- [ ] `AiFieldSuggestionEngine` tenant isolation — focused test sonucu beklenmeli
