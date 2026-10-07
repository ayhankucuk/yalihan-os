# YALIHAN OS — DATABASE ARCHITECTURE MAP

**LAST_VERIFIED_HEAD:** e9d0d611
**Evidence Level:** REPO_VERIFIED (Migration Analysis)

---

## Core Baseline Tables (2024_01_01)

### Authentication & Users
| Table | Purpose | Key Columns | Notes |
|-------|---------|------------|-------|
| users | User accounts | tenant_id, role_id | Spatie RBAC |
| roles | Role definitions | - | super-admin, danisman, editor |
| permissions | Permission definitions | - | - |
| role_has_permissions | Role-permission mapping | - | - |
| model_has_roles | Model-role mapping | - | - |
| model_has_permissions | Model-permission mapping | - | - |

### Core Entities
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| ulkeler | Countries | - | Master data |
| iller | Provinces | ulke_id | Master data |
| ilceler | Districts | il_id | Master data |
| mahalleler | Neighborhoods | ilce_id | Master data |

### CRM Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| kisiler | Contacts/Leads | tenant_id, danisman_id, crm_surec_asamasi | 1:N Talep, Etkilesim |
| kisi_etkilesimler | Interaction logs | kisi_id, kullanici_id | - |
| talepler | Demands | tenant_id, kisi_id, ilan_id | - |
| etiketler | Tags | - | M:N kisiler |
| etiket_kisi | Kisi-Tag pivot | kisi_id, etiket_id | - |
| leads | Lead tracking | tenant_id | - |
| lead_activities | Lead activities | lead_id | - |

### Property/Listing Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| ilan_kategorileri | Listing categories | - | - |
| ilanlar | Listings | tenant_id, property_id, danisman_id | 1:N Photos, Features |
| ilan_fotograflari | Listing photos | ilan_id | - |
| ilan_videolari | Listing videos | ilan_id | - |
| ozellik_kategorileri | Feature categories | - | - |
| ozellikler | Features | - | - |
| feature_categories | Feature categories (v2) | - | - |
| features | Features (v2) | - | - |
| feature_assignments | Feature-Ilan mapping | ilan_id, feature_id | - |
| ilan_feature | Listing-feature pivot | ilan_id | - |

### Property Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| projeler | Projects | tenant_id | - |
| properties | Property assets | tenant_id, tkgm_id, uuid | 1:N Ilan |

### Rental/Reservation Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| yazlik_rezervasyonlar | Vacation reservations | - | Legacy |
| property_reservations | **Current reservations** | tenant_id, property_id, ilan_id | - |

### Finance Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| ledger_accounts | Chart of accounts | - | - |
| ledger_balances | Account balances | - | - |
| ledger_entries | Journal entries | - | - |
| ledger_transactions | Transactions | - | - |
| finansal_islemler | Financial operations | ilan_id, kisi_id, gorev_id | ⚠️ NO tenant_id |

### AI Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| ai_logs | AI operation logs | tenant_id, ai_provider | - |
| ai_feature_usages | AI feature usage | - | - |
| ai_deneyler | AI experiments | - | - |
| ai_saglayici_profilleri | AI provider profiles | - | - |
| ai_provider_profiles | AI provider profiles (v2) | - | - |
| cortex_neural_connections | Cortex connections | - | - |
| embedding_baseline | Embedding data | - | - |
| ai_workspace_wallets | AI credit tracking | - | - |
| ai_transactions | AI transactions | - | - |
| tkgm_learning_patterns | TKGM patterns | - | - |
| tkgm_queries | TKGM query logs | - | - |

### Notifications Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| notifications | System notifications | - | - |
| outbound_notifications | Notification audit | - | - |
| notification_templates | Message templates | - | - |

### Tasks/Workflow Domain
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| gorevler | Tasks/Assignments | tenant_id, lead_id | - |

### Other
| Table | Purpose | Key Columns | Relations |
|-------|---------|------------|-----------|
| settings | Application settings | - | Key-value store |
| activity_log | Audit trail | - | - |
| ilan_notlari | Listing notes | ilan_id | - |
| ilan_taslaklar | Listing drafts | ilan_id | - |
| saved_searches | User saved searches | - | - |
| demirbaslar | Inventory | - | - |
| languages | Language definitions | - | - |
| currencies | Currency definitions | - | - |
| yayin_tipleri | Publication types | - | - |
| yayin_tipi_sablonlari | Publication templates | - | - |
| yayin_tipi_pivot_atamalari | Type assignments | - | - |
| yazlik_details | Vacation property details | - | - |
| yazlik_fiyatlandirma | Vacation pricing | - | - |
| governance_decisions | Governance audit | - | - |
| governance_audit_logs | Governance logs | tenant_id, ulke_id | - |
| system_learning_transactions | ML learning | - | - |
| property_engine_shadow_events | Shadow events | - | - |
| template_audit_logs | Template changes | - | - |
| test_entities | Test data | tenant_id | - |
| agent_runs | Agent execution logs | - | - |
| alt_kategori_yayin_tipi | Alt category-type mapping | - | - |

---

## Critical Schema Observations

### ⚠️ TENANT ISOLATION ISSUES

| Table | Issue | Risk |
|-------|-------|------|
| finansal_islemler | NO tenant_id column | **CRITICAL** - Cross-tenant exposure |
| ledger_* tables | NO tenant_id | **CRITICAL** - Financial data exposure |
| yazlik_rezervasyonlar | NO tenant_id | High risk |
| gorevler | HAS tenant_id | OK |

### ✅ PROPERLY ISOLATED

| Table | Evidence |
|-------|----------|
| kisiler | tenant_id + BelongsToTenant |
| talepler | tenant_id + BelongsToTenant |
| ilanlar | tenant_id + BelongsToTenant |
| properties | tenant_id + BelongsToTenant |
| ai_logs | tenant_id |
| users | tenant_id |

---

## Schema Relationships Map

```
TENANT (root)
  ├── users (tenant_id)
  ├── kisiler (tenant_id)
  │     ├── talepler (kisi_id, tenant_id)
  │     └── kisi_etkilesimler (kisi_id)
  ├── ilanlar (tenant_id, property_id, danisman_id)
  │     ├── ilan_fotograflari (ilan_id)
  │     ├── ilan_videolari (ilan_id)
  │     ├── ilan_feature (ilan_id)
  │     ├── ilan_notlari (ilan_id)
  │     └── ilan_taslaklar (ilan_id)
  ├── properties (tenant_id)
  │     └── ilanlar (property_id) [1:N]
  ├── property_reservations (tenant_id, property_id, ilan_id)
  ├── gorevler (tenant_id, lead_id)
  ├── ai_logs (tenant_id)
  ├── leads (tenant_id)
  ├── etiketler
  ├── ozellikler
  └── settings

FINANCE (⚠️ NO TENANT ISOLATION)
  ├── finansal_islemler (NO tenant_id)
  ├── ledger_accounts
  ├── ledger_balances
  ├── ledger_entries
  └── ledger_transactions
```

---

## Data Flow Architecture

### Reservation Flow
```
Ilan (ilan_id)
  └── property_reservations (property_id)
        └── guest_info: guest_name, guest_phone, guest_email

No automatic Finance creation (manual FinansalIslem)
```

### CRM Flow
```
Kisi (kisiler)
  └── Talep (talepler) → ilan_id
  └── KisiEtkilesim (kisi_etkilesimler)
  └── Gorev (gorevler) → lead_id

No automatic Reservation creation from CRM
```

---

## Foreign Key Relationships

### Ilan-centric
```
ilanlar
  ├── ilan_fotograflari.ilan_id
  ├── ilan_videolari.ilan_id
  ├── ilan_notlari.ilan_id
  ├── ilan_taslaklar.ilan_id
  ├── talepler.ilan_id
  └── property_reservations.ilan_id (LEGACY - should be property_id)
```

### Property-centric (Preferred)
```
properties
  └── ilanlar.property_id [SHOULD BE PRIMARY]
  └── property_reservations.property_id [PRIMARY]
```

---

## Canonical Table Definitions

| Concept | Canonical Table | Canonical Column | Notes |
|---------|---------------|-----------------|-------|
| Reservation dates | property_reservations | start_date, end_date | Not ilan_reservations |
| Ilan identity | ilanlar | id | Primary |
| Property identity | properties | id, tkgm_id | tkgm_id immutable |
| Kisi identity | kisiler | id | - |
| Talep identity | talepler | id | - |
| Tenant identity | tenants | id | Root entity |

---

## Unknown/Missing Tables

| Table | Status | Notes |
|-------|--------|-------|
| tenant_invitations | NOT FOUND | - |
| user_sessions | NOT FOUND | - |
| api_tokens | NOT FOUND | Laravel Sanctum |
| cache_* | NOT FOUND | Redis abstracted |
| jobs | NOT FOUND | Laravel queue |

---

## Recommendations

### P0 - Critical
1. Add tenant_id to finansal_islemler
2. Add tenant_id to ledger_* tables
3. Verify yazlik_rezervasyonlar tenant isolation

### P1 - High
4. Standardize property_reservations ↔ ilanlar relationship
5. Add audit trail to financial operations

### P2 - Medium
6. Document legacy table deprecation plan (yazlik_*)
7. Verify test_entities usage in production
