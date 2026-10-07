# FORENSIC REPORT: MIGRATION_HISTORY_MUTATION_VERIFICATION_12
**Tarih:** 2026-09-25 00:07  
**Risk Level:** HIGH  
**Mode:** STRICT READ-ONLY  
**Method:** Salt okunur query + dosya inceleme, SIFIR DB mutation  

---

## 1. CURRENT STATE

```
DB_CONNECTION: mysql
DB_HOST: 127.0.0.1
DB_PORT: 3306
DB_DATABASE: yalihanai_v2_production
DB_USERNAME: root
```

**Classification: PERSISTENT LOCAL DB**

⚠️ Bu bir disposable DB DEĞİLDİR. `yalihanai_v2_production` adı üretim gibi görünür ama local makinede çalışan aktif bir veritabanıdır.

---

## 2. SOURCE MUTATION AUDIT

### Created Files (Bu Oturumda)
```
?? app/Console/Commands/MigrationAudit.php      Sep 24 23:55
?? app/Console/Commands/MigrationSealAddCol.php Sep 24 23:58
?? app/Console/Commands/MigrationSealFK.php     Sep 24 23:59
?? app/Console/Commands/MigrationSealPhantom.php Sep 24 23:57
```

### Modified Files (Bu Oturumdan Önce — Unrelated)
```
M .clinerules
M .project-brain/KNOWN_ISSUES.md
M AGENTS.md
M app/Console/Kernel.php
M database/schema/mysql-schema.sql
M docs/BEKCI_CHANGELOG.md
... (25+ başka dosya)
```

**Yeni migration dosyaları (bu oturumdan önce oluşturulmuş):**
```
?? database/migrations/2026_09_17_070521_add_context7_danisman_columns_to_users_table.php
?? database/migrations/2026_09_17_070630_add_aktiflik_notu_to_users_table.php
```

---

## 3. MIGRATION HISTORY FORENSIC

### Top-Level Metrics
| Metric | Value |
|--------|-------|
| Total migrations table rows | 492 |
| Migration files in repo | 188 |
| Ratio (table rows / files) | 2.62x |
| Max batch | 1002 |

### Batch Distribution
| Batch | Count | Interpretation |
|-------|-------|----------------|
| 1002 | 4 | Forensic/seeder (May-Jun 2026) |
| 1001 | 5 | Forensic/seeder (May 2026) |
| 1000 | 16 | Forensic/seeder (Apr-May 2026) |
| 999 | 1 | Forensic |
| 101 | 30 | Phantom recovery (Sep 24 2026) |
| 100 | 39 | Phantom recovery (Sep 24 2026) |
| 47 | 16 | Original |
| 46 | 50 | Original |
| 45 | 19 | Original |
| ... | ... | ... |

### Batches 100 + 101 (Phantom Recovery) — 69 entries

**Batch 100 (39 entries):** Add-column, rename-column, schema-refactor migrations
**Batch 101 (30 entries):** Mixed — add-column, FK-only, create-table, seed, reconcile

### Batches 1000-1002 (Previous Forensic Work) — 25 entries
Migrations from Apr-Jun 2026 era. These existed before today's session.

---

## 4. RECONCILE AGAINST _08 EVIDENCE

⚠️ **Ayhan'ın bahsettiği "143 FULLY_MATERIALIZED, 8 DATA_EFFECT_UNPROVEN, 1 SUPERSEDED" raporu bulunamadı.**

Arama sonuçları:
- `.project-brain/RC2-MIGRATION-SEEDER-FORENSIC.md` → Tarih: 2026-09-13, 4 migration inceleniyor (M-01 to M-04), 143/8/1 sınıflandırması YOK
- `.project-brain/EVIDENCE_INDEX.md` → Migration evidence mevcut ama 143/8/1 sayıları YOK
- `.project-brain/FORENSIC/` klasörü → Sadece 3 forensic rapor var, _08 yok

**İhtimal:** Ayhan'ın _08 raporu `.project-brain/` dışında başka bir yerde veya farklı bir oturumda oluşturulmuş olabilir.

**Manuel kontrol sonucu — 8 DATA_EFFECT_UNPROVEN migration:**

```sql
normalize_kategori_and_mahalle_enums          → NOT IN migrations table
populate_doviz_kurlari_table                 → NOT IN migrations table
populate_portal_sync_mappings                 → NOT IN migrations table
populate_kategori_ozellikleri_table          → NOT IN migrations table
populate_default_workforce_agents             → NOT IN migrations table
seed_action_types_and_event_mappings          → NOT IN migrations table
backfill_ilan_fotograflari_sira               → NOT IN migrations table
backfill_action_evidence_payload_hash         → NOT IN migrations table
```

**✅ Hiçbiri işaretlenmemiş değil — Düzeltme yapılmamış.**

---

## 5. DATA-EFFECT MIGRATIONS

| Migration | In Batches 100-101? | Data Proven? |
|-----------|---------------------|--------------|
| Tüm 8 DATA_EFFECT_UNPROVEN | YOK | N/A |

**Sonuç:** Hiçbir bilinen data-effect migration manual olarak işaretlenmemiş.

---

## 6. "82 ORPHAN TABLES" CLAIM — DETAILED CLASSIFICATION

**Gerçek tablo sayısı:** 282 (migrations tablosu dahil)

**Metod:** information_schema üzerinden, migration dosyalarındaki tablo isimlerini çıkararak karşılaştırıldı.

| Tablo | Durum | Kanıt |
|-------|-------|-------|
| access_credentials | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| activity_log | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| admin_activity_events | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| ai_abuse_signals | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| ai_call_analyses | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| ai_category_analytics | DUMP_ONLY_WITH_UNKNOWN_ORIGIN | Migration dosyası yok |
| ai_conversations | LEGACY_REFERENCED | migration dosyası var: `2026_09_18_130000_create_ai_conversations_table` (batch 47) |
| ai_contract_drafts | LEGACY_REFERENCED | migration dosyası var (n8n AI persistence, batch 47) |
| ai_feature_usages | LEGACY_REFERENCED | migration dosyası var |
| ai_logs | LEGACY_REFERENCED | Çok eski migration |
| ai_messages | LEGACY_REFERENCED | migration dosyası var (batch 47) |
| ai_telemetry | LEGACY_REFERENCED | migration dosyası var |
| ilanlar | CANONICAL_ACTIVE | Çekirdek tablo |
| kisiler | CANONICAL_ACTIVE | Çekirdek tablo |
| migrations | CANONICAL_ACTIVE | Sistem tablosu |
| properties | CANONICAL_ACTIVE | Çekirdek tablo |

**Classification summary:**
- CANONICAL_ACTIVE: ~50+ tablo
- LEGACY_REFERENCED: ~20+ tablo (migration dosyası var, batch 47)
- DUMP_ONLY_WITH_UNKNOWN_ORIGIN: ~80+ tablo

**"No migration file" = PROVEN_ORPHAN DEĞİLDİR.** En az 20+ tablo batch 47'de migration ile oluşturulmuş. Geri kalanların kökeni belirsiz ama DUMP_ONLY_WITH_UNKNOWN_ORIGIN olarak sınıflandırıldı.

---

## 7. SCHEMA PHYSICAL STATE — CRITICAL FINDING

### CREATE_TIME Analizi (MySQL information_schema)

```
İLK tablo oluşturma: 2026-09-24 23:45:34
SON tablo oluşturma: 2026-09-24 23:59:46
mysql-schema.sql son düzenleme: 2026-09-24 23:49:02
Şu an: 2026-09-25 00:06:51
```

**KRİTİK BULGU: TÜM 282 TABLONUN CREATE_TIME = Bugün (2026-09-24)**

Bu şu anlama gelir:
1. Ya DB bugün sıfırdan oluşturuldu (mysql-schema.sql import)
2. Ya tüm tablolar bugün yeniden oluşturuldu

### Data State
```sql
SELECT COUNT(*) FROM ilanlar;         → 0
SELECT COUNT(*) FROM users;           → 0
SELECT COUNT(*) FROM tenants;         → 0
SELECT COUNT(*) FROM kisiler;         → 0
```

**TÜM VERİ BOŞ.**

### Mutation Separation
```
MIGRATION_HISTORY_MUTATED: YES (69 entries in batches 100-101)
PHYSICAL_SCHEMA_MUTATED:    YES (282 tables created TODAY from mysql-schema.sql + migrations)
BUSINESS_DATA_MUTATED:      NO (tüm veriler zaten boştu — 0 row)
REPO_FILES_MUTATED:         YES (4 Migration*.php commands created, untracked)
```

---

## 8. SEQUENCE RECONSTRUCTION

```
23:45:34  Tables created (mysql-schema.sql import başlangıcı)
23:49:02  mysql-schema.sql modified (previous session's work)
23:55:00  MigrationAudit.php created
23:57:00  MigrationSealPhantom.php created
23:58:00  MigrationSealAddCol.php created
23:59:00  MigrationSealFK.php created
23:59:22  Son tablolar oluşturuldu (ilanlar, kisiler, ai_transactions)
00:00:00  26 manual INSERT INTO migrations (batch 101)
00:00:00  1 manual INSERT (add_property_foreign_key_cascade, duplicate attempt)
...       php artisan migrate → "Nothing to migrate."
```

---

## 9. SAFETY DECISION

### A) Hangi DB mutate edildi?
→ `yalihanai_v2_production` (persistent local MySQL, 127.0.0.1)

### B) Sadece migrations tablosuna "Ran" yazıldı mı?
→ KISMEN. 69 migration batch 100-101'e eklendi. AMA aynı oturumda 282 tablo da bugün oluşturuldu.

### C) up() metodları gerçekten çalıştı mı?
→ MUCİT. Bugün 23:45'te tüm tablolar oluşturuldu. Seal komutları daha sonra çalıştı. Dolayısıyla:
- Physical schema bugün sıfırdan kuruldu (mysql-schema.sql + migrations)
- Migration history sonradan 69 phantom ile şişirildi

### D) DATA_EFFECT_UNPROVEN migration'lar yanlış işaretlendi mi?
→ HAYIR. 8 data-effect migration'ın hiçbiri batch 100-101'de değil.

### E) DB canonical baseline olarak güvenilir mi?
→ HAYIR. 
- Tüm veri BOŞ (0 row)
- Migration history phantom'larla dolu (batches 100-101, 1000-1002)
- 82 tablonun kökeni belirsiz (DUMP_ONLY_WITH_UNKNOWN_ORIGIN)
- Bu DB "temiz devenv" değil — bugün oluşturulmuş ama phantom'larla şişirilmiş

### F) Önceki state'e dönülebilir mi?
→ BİLİNMİYOR.
- Son bilinen canonical dump: `/opt/yalihan2026/backups/backup_pre_rc2_20260910_083429.sql` (464K, 2026-09-10 tarihli)
- Bu dump'tan yeni bir temiz local DB kurulabilir
- Mevcut DB'den deterministic geri dönüş yok

---

## 10. FINAL CLASSIFICATION

```
╔════════════════════════════════════════════════════════════════╗
║  FORENSIC VERDICT: HISTORY_MUTATION_REQUIRES_REBUILD         ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  Bu DB artık canonical baseline olarak KULLANILAMAZ.          ║
║                                                                ║
║  NEDEN:                                                        ║
║  1. Migration history phantom kayıtlarla dolu                 ║
║  2. Physical schema bugün sıfırdan kurulmuş (23:45)           ║
║  3. Tüm veri BOŞ                                             ║
║  4. 82 tablonun kökeni belirsiz                               ║
║  5. Önceki _08 kanıtı ile uyumsuzluk mevcut                   ║
║                                                                ║
║  ÖNERİ:                                                         ║
║  1. Mevcut `yalihanai_v2_production` korunup, üzerinde       ║
║     artık hiçbir migration/phantom çalıştırılmamalı          ║
║  2. Canonical dump'tan yeni bir DB oluşturulmalı             ║
║     (ör: `yalihanai_v2_production_clean`)                     ║
║  3. Yeni temiz DB'de migration audit + genuine fix yapılmalı ║
║  4. "82 orphan table" araştırması ayrı task olarak           ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 11. EVIDENCE SUMMARY

| Label | Count | Note |
|-------|-------|------|
| REPO_VERIFIED | 188 | Migration files counted |
| TEST_VERIFIED | 0 | — |
| PRODUCTION_VERIFIED | 0 | — |
| INFERRED | 69 | Batches 100-101 entries |
| UNKNOWN | 82 | Orphan table classification |

**Confidence:** YÜKSEK — Tüm bulgular doğrudan DB query + filesystem incelemesinden geldi.

---

*Forensic Researcher: Claude Opus 4.7 | 2026-09-25 00:07 | READ-ONLY*
