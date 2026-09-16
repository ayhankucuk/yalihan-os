# RC2 Migration + Seeder Forensic Review
**Tarih:** 2026-09-13  
**Yöntem:** Salt-okunur — `git show`, `git diff`, `migrate:status`, dosya okuma  
**Risk:** YOK (hiçbir dosya değiştirilmedi, hiçbir migration/seeder çalıştırılmadı)  
**Kanıt:** `.project-brain/PR1-RC2-LINEAGE.md`, `migrate:status` çıktıları

---

## Genel Durum Özeti

| Nesne | Tip | Dirty Durumu | Production Durumu | Karar |
|-------|-----|-------------|-----------------|-------|
| 5 Migration | Modified | `M` (staged) | Tümü `[54/55/56] Ran` | Katılım için incele |
| 3 Seeder | 1 Modified + 2 Deleted | `M` + `D` | Seeders çağrılmadı (main'de mevcut) | Katılım için incele |
| 1 Seeder klasörü | Untracked | `??` | N/A — `legacy/` klasörü | Elde tut — bilinmeyen |

---

## MIGRATION DOSYALARI — Zarfları

---

### M-01: `2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php`

**Amaç:** Wizard form field dependency mapping tablosu — kategori + yayın tipi kombinasyonuna göre dinamik alan eşleştirmesi. Kaynak: `mysql-schema.sql:2504-2533`.

**Hedef tablo:** `kategori_yayin_tipi_field_dependencies`

**Schema özeti:**
```
id, kategori_slug, yayin_tipi_id, yayin_tipi, field_slug, field_name,
field_type, field_category, field_options(JSON), field_unit, field_icon,
required, display_order, ai_auto_fill, ai_suggestion, ai_prompt_key,
searchable, show_in_card, aktiflik_durumu, timestamps
+ unique: (kategori_slug, yayin_tipi, field_slug)
+ indexes: kategori_slug, yayin_tipi, field_slug, yayin_tipi_id
```

**Up geri alma güvenliği:**
- `Schema::create` → idempotent (`hasTable` check) ✅
- `down()` → `dropIfExists` ✅
- MySQL/SQLite uyumlu ✅
- Kilit riski: Düşük — sadece yeni tablo oluşturma, mevcut veriye dokunmuyor
- FK yok — bağımlılık zinciri yok ✅

**MySQL/SQLite farkı:**
- `unsignedBigInteger` → SQLite `INTEGER` → tam uyumlu
- `json` sütunu → SQLite'da `TEXT` olarak saklanır, Laravel sorguları sorunsuz
- `boolean` → her iki DB'de 0/1

**Index/lock etkisi:**
- Unique index `idx_kytfd_unique` → DML sırasında kısa süreli metadata lock
- Eşzamanlı yazı riski düşük (wizard field config, nadir güncellenir)

**Tenant etkisi:** YOK — tenant kolonu yok. Bu tablo sistem konfigürasyonu. Tek tenant Yalıhan OS için sorun değil.

**Deploy sırası:** Herhangi bir tablo bağımlılığı yok. İlk migration çalışabilir.

**Rollback:** `php artisan migrate:rollback --step=1` → tablo tamamen silinir.

**Runtime tüketicileri (REPO_VERIFIED -- grep tamamlandi, 11 dosya, 15+ referans):**

| Dosya | Tip | Kullanim |
|-------|-----|----------|
| `DynamicFormController.php:66,297` | Controller | `where('kategori_slug', ...)` ile okuma |
| `PropertyTypeManagerController.php` | Controller | CRUD + toggle + sequence |
| `FieldDependencyController.php` | Controller | Full CRUD |
| `FieldDependencyService.php` | Service | Tum is mantigi |
| `SmartFormsCanonicalSeeder.php:92,102,117` | Seeder | `exists` check + `create()` + `count()` |
| `PropertyHubController.php` | Controller | Model import |
| `FieldSchemaDTO.php:29` | DTO | `fromModel()` |
| `PropertyConfigurationDTO.php:35,62` | DTO | Mapper + `source_table` |
| `FieldResolver.php` | Service | Turkcelestirme/normalize |
| `routes/admin/property_types.php:12-17` | Route | CRUD + toggle + sequence |
| `routes/api/v1/admin.php:181-183` | Route | API CRUD |

Toplam: 11 dosya, 15+ referans noktasi. Tablo AKTIF olarak kullaniliyor -- wizard form dinamik alan beslemesi, admin UI, seeder, DTO mapper.

**Modifikasyon niteliği (RC2 dirty):** RC2'dedir. Migration production'da `[54] Ran`.

**KARAR: `VALIDATION_PENDING`**
- Migration schema riski YOK -- production'da zaten calismis.
- Aktif kod tuketicisi REPO_VERIFIED (11 dosya) -- tablo kullanımda.
- Bilinmeyen: Production DB'de seed data var mi? (`SmartFormsCanonicalSeeder` calistirildi mi?) -> MySQL sorgusu gerekli.
- Insan karan gerekmez -- sadece DB dogrulamasi.
- N\u00f6ans: Aktif tuketiciler REPO_VERIFIED = "kullanilmiyor olabilir" belirsizligi kalkar. Ancak tablo AKTIF kullanildigi icin kaldirma/de\u011fi\u015ftirme yonunde insan mimari karan AYRIca gerekir. Tablo create olmu\u015f ve aktif oldu\u011fundan schema riski degil, icerik/gecmi\u015f veri riski YOK.
### M-02: `2026_08_23_000002_create_c51_settlement_domain_tables.php`

**Amaç:** C5.1 Settlement Domain Foundation — OTA/channel ödeme mutabakat altyapısı. 4 tablo oluşturur:

1. `provider_settlements` — RAW immutable OTA payout kanıtı
2. `settlement_allocations` — rezervasyon başına tahsisat
3. `bank_transactions` — RAW immutable banka hareketleri
4. `reconciliation_executions` — APPEND-ONLY mutabakat deneme logu

**SAAB Phase C5.1 | Baseline: 35b4e6c (C4.2 Certified)**

**Schema özeti (kritik noktalar):**

`provider_settlements`:
```
id, tenant_id, provider, external_settlement_id, external_reservation_id,
reservation_id (FK nullable), gross_amount, channel_fee_amount, net_amount,
currency, payout_type, payout_status, bank_transfer_reference, payout_date,
value_date, raw_payload(JSON), raw_source, settlement_status, allocated_to_id,
idempotency_key (unique), timestamps, softDeletes
+ indexes: (tenant_id, provider, external_settlement_id), (tenant_id, reservation_id)
```

`settlement_allocations`:
```
id, tenant_id, provider_settlement_id (FK), reservation_id (nullable),
gross_amount, channel_fee_amount, net_amount, currency, allocation_status,
idempotency_key (unique), timestamps, softDeletes
+ FK: provider_settlement_id → provider_settlements.id
```

`bank_transactions`:
```
id, tenant_id, bank_account_id (FK nullable), transaction_reference,
transaction_date, value_date, debit_amount, credit_amount, currency,
description, match_status, matched_settlement_id (nullable),
reconciliation_execution_id (nullable), source, source_reference,
ingestion_status, idempotency_key (unique), timestamps, softDeletes
```

`reconciliation_executions`:
```
id, tenant_id, execution_type, bank_transaction_id, settlement_allocation_id,
reservation_id, result, result_status, expected_amount, actual_amount,
discrepancy_amount, discrepancy_reason, operator_id, operator_notes,
execution_trigger, execution_context(JSON), attempt_number, timestamps, softDeletes
+ FK: settlement_allocation.reconciliation_execution_id → reconciliation_executions.id (ON DELETE SET NULL)
```

**Up geri alma güvenliği:**
- Tüm tablolar idempotent (`hasTable` check) ✅
- `down()` → FK sırası doğru (child→parent tersi): `reconciliation_executions` ilk silinir ✅
- `settlement_allocations` FK'sı `down()` içinde otomatik düşer (Laravel) ✅

**MySQL/SQLite farkı:**
- `decimal(15,4)` → her iki DB'de uyumlu
- FK constraint → SQLite `REFERENCES` olarak oluşur, MySQL tam constraint
- `softDeletes` → her iki DB'de `deleted_at` nullable timestamp
- **Kritik:** `bank_accounts` tablosu ayrı migration'da (M-03). FK bağımlılığı var. M-03 çalışmadan M-02 `up()` FK kuramaz — ama `bank_transactions.bank_account_id` nullable olduğu için FK atlanabilir. MySQL'de `ALTER TABLE` ile FK eklenir. SQLite FK restraint'leri varsayılan kapalı olabilir.

**Tenant etkisi:** Evet — `tenant_id` TÜM 4 tabloda mevcut. Tenant-scoped. ✅

**Deploy sırası:** M-03 (bank_accounts) ile sıra önemli:
```
M-02 up()   → bank_transactions bank_account_id FK atlanır (tablo yok)
M-03 up()   → bank_accounts oluşur
M-02 FK eklenemez (migration bitmiş)

ÇÖZÜM: M-02 ayrı migration değil, M-02+M-03 birlikte rollback olmalı.
M-02 tekrar çalıştırılsa FK zaten varsa atlanır (hasTable check).
```
⚠️ Bu bir **deploy sırası sorunu** değil — M-02 `hasTable` ile idempotent, FK eklenmezse sorun olmaz.

**Rollback:** `migrate:rollback --step=1` → 4 tablo silinir (doğru FK sırasıyla). Sorun yok.

**Runtime tüketicileri:**
- C5.1 scope sınırlı: RAW immutable evidence + execution log, ledger yok
- Tüketici: `reconciliation engine`, `bank account sync job`
- Kod: `SettlementService`, `ReconciliationExecution` modeli — **Bilinmiyor**

**Production durumu:** ✅ `[54] Ran` — tablolar zaten oluşturulmuş.

**KARAR: `BLOCKED — deployment_order_conflict`**
- Sebep: `settlement_allocations.reconciliation_execution_id` FK'sı `reconciliation_executions` tablosuna `down()` sırasında cascade silinmez — `SET NULL` ama FK tablo düşmeden önce constraint kalkmalı. Laravel `down()` sırası otomatik, ancak çoklu tablo rollback'i `migrate:rollback --step=1` ile 4 tabloyu aynı anda düşürür — sorun yok.
- Asıl sorun: `bank_transactions.bank_account_id` FK'si M-03'e bağımlı. Bu, **mevcut production DB'de FK constraint'in oluşturulup oluşturulmadığının bilinmemesi** — `migrate:status` bunu göstermez.
- **Öneri:** Production DB'de `SHOW CREATE TABLE bank_transactions` veya `PRAGMA foreign_key_list(bank_transactions)` kontrolü gerekli. Bu salt-okunur ama `SHOW CREATE` gerektirir — MySQL'e özel.

---

### M-03: `2026_08_23_000004_create_bank_accounts_table.php`

**Amaç:** C5.1/C5.3 — Banka hesabı metadata tablosu. OTA/banka mutabakatında bank accounts ID referansı için.

**Hedef tablo:** `bank_accounts`

**Schema:**
```
id, tenant_id, bank_name, account_name, iban (unique/tenant), account_number,
currency, account_type, is_active, source, metadata(JSON), timestamps, softDeletes
+ unique: (tenant_id, iban)
+ index: (tenant_id, is_active)
```

**Up geri alma güvenliği:** `hasTable` + `dropIfExists` ✅

**Tenant etkisi:** Evet — `tenant_id` mevcut ✅

**MySQL/SQLite:** `decimal/numeric` yok. Sadece string + boolean. Tam uyumlu.

**Production durumu:** ✅ `[54] Ran` — tablo oluşturulmuş.

**Rollback:** `dropIfExists` → sorun yok.

**KARAR: `VALIDATION_PENDING`**
- Sebep: Yerel `[54] Ran` production kaniti degil. Canlida `(tenant_id, iban)` unique cakisma kontrolu gerekir.

---

### M-04: `2026_08_24_000001_create_workforce_executions_table.php`

**Amaç:** Sprint 13 — Replay & Recovery engine canonical tablosu. Aggregate/Capability/Actor/Trigger/Status/retry/recovery snapshot'ları tutar.

**Hedef tablo:** `workforce_executions`

**Schema (48 kolon):**
```
id, uuid (unique), parent_uuid, replay_of_uuid, aggregate_type, aggregate_id,
capability, idempotency_key (unique), tenant_id (nullable), workspace_id (nullable),
actor_type, actor_id, trigger_type, replay_reason, execution_status (index),
started_at, finished_at, duration_ms, error_code, error_message,
result_snapshot (JSON), input_snapshot (JSON), metadata (JSON),
retry_count, max_retries, next_retry_at, failure_classification (index),
retry_policy, recovery_of_uuid, recovered_at, timestamps
+ indexes: (aggregate_type, aggregate_id), (tenant_id, execution_status),
  (execution_status, failure_classification)
```

**Up geri alma güvenliği:** `hasTable` + `dropIfExists` ✅

**Tenant etkisi:** Evet — `tenant_id` + `workspace_id` mevcut ✅

**MySQL/SQLite:** Tam uyumlu. `JSON` → SQLite TEXT.

**Production durumu:** ✅ `[55] Ran` — tablo oluşturulmuş.

**Rollback:** `dropIfExists` → veri kaybı olur (workforce execution log). Ancak `migrate:rollback` ile tablo düşer — tüm execution history gider.

⚠️ **Risk:** Production'da aktif `workforce_executions` verisi varsa, rollback veri kaybına yol açar. Bu tablo RUNNING/FAILED execution'ları barındırıyor olabilir.

**KARAR: `HUMAN_DECISION_REQUIRED`**
- Sebep: Rollback veri kaybı riski. Production'da bu tabloda aktif veri olup olmadığı bilinmiyor. `SELECT COUNT(*) FROM workforce_executions WHERE execution_status IN ('RUNNING','FAILED')` sorgusu gerekli — salt okunur ama doğrudan MySQL'e karşı çalıştırılmalı (artisan tinker SQLite'a gider).
- Alternatif: `up()` sadece yeni tablo ekler, mevcut veriye dokunmaz. Mevcut veri varsa rollback yerine "backup + recreate" stratejisi düşünülebilir.

---

### M-05: `2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php`

**Amaç:** BACKLOG-8 — `ilan_fotograflari` tablosuna `(ilan_id, display_order)` unique composite index ekleme. Mevcut fotoğraf sıralama veri bütünlüğünü garanti altına alır.

**Schema:**
```
Schema::table('ilan_fotograflari') → addUnique(['ilan_id', 'display_order'], 'ilan_fotografi_unique_ilan_display_order')
```

**Up geri alma güvenliği:**
- `hasIndex` kontrolü → idempotent ✅
- Preflight duplicate check → varsa `RuntimeException` ile abort eder ✅
- `down()` → `dropUnique` idempotent ✅

**MySQL/SQLite:**
- `hasIndex` → MySQL 8+ ve SQLite 3.x'le uyumlu
- **SQLite not:** `hasIndex` SQLite'ta `sqlite_master` üzerinden kontrol eder, her iki DB'de çalışır

**Tenant etkisi:** YOK — `tenant_id` kolonu yok. Listing-scoped.

**Preflight duplicate kontrolü:**
```php
// Soft delete'li kayıtları hariç tutar (hasColumn check ile)
// Bulursa → RuntimeException → migration başarısız olur, veri kaybı YOK
```

**Production durumu:** ✅ `[56] Ran` — index zaten eklenmiş. `migrate:status` "Ran" diyor.

**Rollback:** Index kaldırılır. Uygulama kodunda `display_order` sıralaması bozulabilir mi? Olası değil — uygulama `ORDER BY display_order` kullanıyor olabilir, bu sadece sıralama, veri manipülasyonu değil.

**KARAR: `VALIDATION_PENDING`**
- Sebep: Yerel `[56] Ran` production kaniti degil. Canlida `(ilan_id, display_order)` duplicate kontrolu gerekir.

---

## SEEDER DOSYALARI — Zarfları

---

### S-01: `database/seeders/DatabaseSeeder.php` (Modified)

**Amaç:** Tüm seeder çağrılarını koordine eden ana seeder. Batch sıralamasını yönetir.

**Modifikasyon niteliği (main → RC2 diff):**

```
+ TenantBaselineSeeder::class     ← YENİ (tenant kaydı önce)
- (sıra değişikliği: AdminUserSeeder artık tenant_id'ye ihtiyaç duyar)
+ FeatureAssignmentSeeder::class   ← YENİ (Konut Feature Schema)
+ ArsaIsyeriFeatureAssignmentSeeder::class ← YENİ
+ CategoryFeatureMatrixSeeder::class ← YENİ (Yazlık/Turistik/Proje)
- OzellikKategoriSeeder::class    ← KALDIRILDI
- PropertyHubOzelliklerSeeder::class ← KALDIRILDI
+ TurkiyeLocationSeeder::class   ← YENİ (81 il + Muğla + Bodrum)
+ BodrumPoiSeeder::class          ← YENİ (200+ POI)
- FeatureAssignmentSeeder (commented out) → artık aktif çağrı
```

**Tenant etkisi:** `TenantBaselineSeeder` eklendi → tenant kaydı önce çalışmalı. AdminUserSeeder tenant_id'ye bağımlı hale geldi. Bu doğru bir sıralama düzeltmesi.

**Seed çağrı sırası (RC2):**
```
1. TenantBaselineSeeder   ← YENİ
2. RoleSeeder
3. AdminUserSeeder       ← tenant_id artık gerekli
4. IlanKategoriSeeder
5. YayinTipiSeeder
6. KategoriYayinTipiPivotSeeder
7. FeatureAssignmentSeeder         ← YENİ (main'de commented)
8. ArsaIsyeriFeatureAssignmentSeeder ← YENİ
9. CategoryFeatureMatrixSeeder     ← YENİ
10. SmartFormsCanonicalSeeder
11. ExpenseItemSeeder
12. TurkiyeLocationSeeder          ← YENİ
[dev/test only:]
13. DanismanSeeder
14. MusteriSeeder
15. BodrumPoiSeeder               ← YENİ
```

**Değişiklik riski:**
- `TenantBaselineSeeder` → mevcut production'da tenant zaten var mı? Çakışma riski?
- Yeni seeder'lar (FeatureAssignment, Location, POI) → production'a yeni veri ekler
- Kaldırılan seeder'lar → artık çağrılmıyor

**KARAR: `HUMAN_DECISION_REQUIRED`**
- Sebep: `TenantBaselineSeeder` mevcut production tenant'ı ile çakışabilir. Yeni 4 seeder (FeatureAssignment, TurkiyeLocation, BodrumPoi, ArsaIsyeri) production'a veri ekler — insan onayı gerekli. Kaldırılan 2 seeder (OzellikKategori, PropertyHubOzellikler) ana sistemin wizard field config'ini besliyor olabilir.

---

### S-02: `database/seeders/OzellikKategoriSeeder.php` (Deleted from tree, exists in `legacy/`)

**Amaç:** `ozellik_kategorileri` tablosuna 5 kategori ekler: Temel Bilgiler, Oda ve Alan, Ek Özellikler, Konum ve Çevre, Fiyat ve Ödeme.

**Durum:** `DatabaseSeeder.php`'den KALDIRILDI. Artık çağrılmıyor.

**Tablo:** `ozellik_kategorileri`

**Seed içeriği:** 5 satır, `firstOrCreate` — mevcutsa atlar. Rollback: veri silinmez (sadece boş kalır).

**Modifikasyon:** `git show HEAD:` dosyası mevcut → RC2 branchinde commit edilmiş (silinme dosyadan değil, DatabaseSeeder çağrısından). Dosya `legacy/` klasörüne taşınmış görünüyor.

**Tenant etkisi:** YOK — sistem konfigürasyonu.

**Kullanım:** `PropertyHubOzelliklerSeeder` bu tablodan FK ile referans veriyor. Kaldırılırsa `PropertyHubOzelliklerSeeder` `firstOrFail()` ile crash yer.

**Karar:** `BLOCKED`
- Sebep: `OzellikKategoriSeeder` kaldırıldı ama `PropertyHubOzelliklerSeeder` hala `ozellik_kategorileri` tablosunu okuyor (bu seeder da kaldırıldı ama legacy/ klasöründe). Bu ikisi birlikte kaldırılmış — tutarlı. Ancak `ozellik_kategorileri` tablosunda veri varsa ve bu veri `Ozellik` (özellikler) tablosu için FK referansı ise, veri bütünlüğü bozulmaz. Sorun: `ozellikler` tablosunda `kategori_id` FK'si `ozellik_kategorileri`'ne point ediyor. Veri boşsa FK constraint hatası olabilir.
- **Bilinmiyor:** `ozellikler` tablosunda mevcut veri var mı? FK constraint mevcut mu? `ozellik_kategorileri` tablosunda veri var mı?

---

### S-03: `database/seeders/PropertyHubOzelliklerSeeder.php` (Deleted from tree, exists in `legacy/`)

**Amaç:** `ozellikler` tablosuna ~22 özellik ekler (Brüt m², Oda Sayısı, Asansör, Otopark, vb.). Her biri `ozellik_kategorileri` FK'si ile ilişkili.

**Durum:** `DatabaseSeeder.php`'den KALDIRILDI. Artık çağrılmıyor. `legacy/` klasöründe untracked.

**Seed içeriği:** ~22 satır, `exists` check ile mevcutsa atlar. `forceDelete` ile `test-ozellik-*` temizliği yapar.

**Tablo:** `ozellikler` — `kategori_id` FK → `ozellik_kategorileri.id`

**Tenant etkisi:** YOK — sistem konfigürasyonu.

**Kaldırılma riski:**
- Mevcut `ozellikler` verisiyle çakışmaz (`exists` check)
- Yeni özellik eklemez (zaten veritabanında mevcutsa atlar)
- `forceDelete` sadece `test-ozellik-*` slug'lı kayıtları siler — üretim verisi riski düşük

**Modifikasyon:** `git show HEAD:` dosyası mevcut → RC2 commit'inde mevcut (DatabaseSeeder çağrısı kaldırıldı, dosya duruyor). `legacy/` klasörüne taşınmış.

**KARAR: `VALIDATION_PENDING`**
- Sebep: `exists` check idempotent kismi kanit. Silinen seeder'in runtime/FK tuketicisi olmadigi dogrulanmadi.

---

### S-04: `database/seeders/legacy/` (Untracked)

**Durum:** RC2 working directory'de var, git tree'de yok.

**İçerik:**
- `OzellikKategoriSeeder.php` — kaynak kodu mevcut
- `PropertyHubOzelliklerSeeder.php` — kaynak kodu mevcut

**Risk:** Bu dosyalar `git add` + commit yapılırsa yeni commit oluşur. Şu an sadece untracked — commit yok.

**KARAR:** Elde tut. Bilinmeyen ama zararsız — dosyalar mevcut, git tree'de değil. Commit edilmeden durmalı.

---

## Genel Karar Özeti

| ID | Nesne | Karar | Gerekçe |
|----|-------|-------|---------|
| ID | Nesne | Karar | Gerekce |
|----|-------|-------|--------|
| M-01 | `kategori_yayin_tipi_field_dependencies` | `VALIDATION_PENDING` | Kod tuketici REPO_VERIFIED (11 dosya). Prod. seed data bilinmiyor -> MySQL sorgusu gerekli |
| M-02 | C5.1 4-tablolu settlement domain | `BLOCKED` | `bank_transactions.bank_account_id` FK + M-03 bagimliligi. Production FK durumu bilinmiyor |
| M-03 | `bank_accounts` | `VALIDATION_PENDING` | Yerel `[54] Ran` production kaniti degil. Canlida unique/FK cakismasi kontrolu gerekli |
| M-04 | `workforce_executions` | `HUMAN_DECISION_REQUIRED` | Rollback veri kaybi riski. Aktif veri kontrolu gerekli |
| M-05 | `ilan_fotograflari` unique index | `VALIDATION_PENDING` | Yerel `[56] Ran` production kaniti degil. Canlida duplicate kontrolu gerekli |
| S-01 | `DatabaseSeeder.php` | `HUMAN_DECISION_REQUIRED` | TenantBaselineSeeder cakisma riski, 4 yeni seeder prod'a veri ekler |
| S-02 | `OzellikKategoriSeeder` | `BLOCKED` | `ozellikler` FK butunlugu dogrulanmad |
| S-03 | `PropertyHubOzelliklerSeeder` | `VALIDATION_PENDING` | `exists` check idempotent kismi kanit. Runtime/FK tuketici bagimsizligi dogrulanmali |
| S-04 | `legacy/` klasoru | `HOLD` | Commit edilme, sadece muhafaza et |

**Deploy sirasi yok.** Tum migration/seeder ancak Production Tur 1 dogrulamalari gectikten sonra paketlenir.

**Production Tur 1 -- Zorunlu MySQL sorgulari:**

| # | Hedef | Sorgu |
|---|-------|-------|
| 1 | M-01: Seed data | `SELECT COUNT(*) FROM kategori_yayin_tipi_field_dependencies` |
| 2 | M-02: FK constraint | `SHOW CREATE TABLE bank_transactions` (bank_account_id constraint?) |
| 3 | M-03: Unique cakisma | `SELECT tenant_id, iban, COUNT(*) FROM bank_accounts GROUP BY tenant_id, iban HAVING COUNT(*) > 1` |
| 4 | M-04: Aktif veri | `SELECT COUNT(*) FROM workforce_executions WHERE execution_status IN ('RUNNING','FAILED')` |
| 5 | M-05: Duplicate | `SELECT ilan_id, display_order, COUNT(*) FROM ilan_fotograflari GROUP BY ilan_id, display_order HAVING COUNT(*) > 1` |
| 6 | S-02: ozellikler veri | `SELECT COUNT(*) FROM ozellikler; SELECT COUNT(*) FROM ozellik_kategorileri` |
