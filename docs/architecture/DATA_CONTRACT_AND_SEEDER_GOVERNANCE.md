# Yalıhan Bekçi — Data Contract & Seeder Yönetişim Anayasası

**Tarih:** 2026-09-12  
**Statü:** KANONİK MİMARİ STANDART  
**Kapsam:** Schema, Seeder, Domain/Application/Infrastructure Katman Sınırları ve Form Şeması Yönetişimi  

---

## 1. 7 Halkalı Veri Sözleşmesi Zinciri (Data Contract Gate)

Yalıhan OS'ta hiçbir migration veya seeder bağımsız bir dosya değildir. Her veri değişikliği şu 7 halkalı zincir üzerinden doğrulanır:

$$\mathbf{Schema \longrightarrow Seed \longrightarrow Model \longrightarrow Runtime \longrightarrow Test \longrightarrow Tenant \longrightarrow Rollback}$$

> **Kural:** Halkalardan biri bilinmiyorsa veya kanıtlanmamışsa sonuç `PASS` olamaz; durum `VALIDATION_PENDING` veya `BLOCKED` olarak işaretlenir.

---

## 2. Üçlü Katman Sorumluluk Ayrımı

```text
Migration
  → Tabloları, kolonları, indexleri ve foreign key kısıtlamalarını oluşturur/değiştirir.

Seeder
  → Bu yapıların kullanacağı başlangıç, referans veya fixture verisini üretir.

Runtime
  → Bu veriyi okuyarak wizard alanlarını, kategori seçeneklerini,
    tenant görünürlüğünü, fiyatlandırmayı ve iş akışını belirler.
```

*Migration olmadan seeder tabloya yazamaz. Seeder olmadan migration başarılı olabilir ama uygulama boş veya eksik veriyle çalışır.*

---

## 3. Seeder Sınıflandırma Matrisi

Tüm seeder dosyaları aşağıdaki 6 sınıftan biriyle etiketlenmeli ve bu politikaya göre çalıştırılmalıdır:

| Sınıf | Tanım ve Kapsam | Çalışma Ortamı & Politikası |
|---|---|---|
| **`CORE_MASTER`** | Ülke (`UlkeSeeder`), Kategori (`IlanKategoriSeeder`), Yayın Tipi (`YayinTipiSeeder`), Kanonik Form Şeması. | Her zaman çalışır (`php artisan db:seed`). Prodüksiyonda güvenli ve idempotenttir. |
| **`DOMAIN_REFERENCE`** | Feature Matrix (`FeatureAssignmentSeeder`), Finans & Kur Kuralları, Lokasyon Hiyerarşisi (`TurkiyeLocationSeeder`). | Master veri zincirinin parçasıdır; güvenli referans verisi üretir. |
| **`DEV_FIXTURE`** | Danışman/Müşteri personaları, Demo İlanlar, Yatırımcı Senaryoları, Bulk Test Verileri (`RentalPropertyBulkSeeder`). | **Yalnızca** `local`, `development`, `testing` ortamlarında çalıştırılabilir. Prodüksiyonda kesinlikle yasaktır. |
| **`ON_DEMAND`** | Diller (`Language`), Para Birimleri (`Currency`), Bakım ve Tanılama Verileri. | İhtiyaç halinde spesifik CLI parametresiyle tetiklenir. |
| **`HISTORICAL_DATA_MIGRATION`** | Canlı veriyi dönüştüren tek seferlik veri migration'ları (Örn: `2026_08_25_000001_seed_villa_feature_assignments.php`). | Migration pipeline'ında bir defalık çalışır; seeder klasörüne taşınmaz veya silinmez. |
| **`LEGACY_QUARANTINED`** | Eski özellik seed'leri (`database/seeders/legacy/`). | Otomatik akıştan çıkarılmış, referans ve geriye dönük uyumluluk için karantinaya alınmış. |

> **Altın Kural:** `DatabaseSeeder.php` yalnızca **`CORE_MASTER`** ve doğrulanmış **`DOMAIN_REFERENCE`** seed'lerini çalıştırabilir.

---

## 4. Form & Wizard Motoru: 4 Katmanlı DDD Ayrımı

```text
Domain
  → FieldDefinition (Değer Nesnesi)
  → FieldKey (Kanonik Alan Kimliği: snake_case)
  → ValidationRule & Option (Alan Doğrulama Kuralları)
  → CategoryFieldPolicy ("villa + satilik → oda_sayisi, brut_m2, bina_yasi")

Application
  → FieldResolver / WizardSchemaResolver
  → Step 2 Wizard Şeması Çözümleme Akışı

Infrastructure & Bootstrap
  → Eloquent Modelleri (KategoriYayinTipiFieldDependency)
  → Migration'lar
  → CategoryFieldSchemaSeeder (Kanonik Domain kurallarını tabloya yazar)

Presentation
  → Controller (WizardController, API)
  → Blade / Alpine.js State / JSON Yanıt Zarfları
```

### Temel İlkeler:
1. **Seeder domain değildir:** Seeder yalnızca kanonik domain tanımlarını veritabanına taşıyan bir altyapı aracıdır.
2. **Domain'e taşınacak varlıklar:** Domain katmanına veritabanı satırları değil, **alan kimliği (`FieldKey`)**, **validasyon kuralları (`ValidationRule`)** ve **politikalar (`CategoryFieldPolicy`)** taşınır.
3. **Kanonik Form Geçiş Sırası:**
   $$\text{Domain Policy / Contract} \longrightarrow \text{Tek Master Seeder} \longrightarrow \text{Schema Tablosu} \longrightarrow \text{Tek Resolver} \longrightarrow \text{Wizard UI}$$

---

## 5. Zorunlu Bekçi Değerlendirme Çıktısı (Data Contract Envelope)

Bekçi her seeder/migration denetiminde aşağıdaki raporu üretir:

```yaml
seeder: [SeederAdı]
classification: [CORE_MASTER | DOMAIN_REFERENCE | DEV_FIXTURE | ON_DEMAND | HISTORICAL_DATA_MIGRATION | LEGACY_QUARANTINED]
target_tables:
  - [hedef_tablo_1]
migration_prerequisite: [verified | missing | pending]
tenant_policy: [isolated | global | missing]
idempotency: [safe | unsafe]
enum_policy: [canonical_compliant | invalid_value_detected]
foreign_key_safety: [slug_resolved | hardcoded_id_risk]
runtime_consumers: [bu_veriyi_okuyan_servis_ve_resolver_listesi]
test_coverage: [verified | unverified]
production_allowed: [true | false]
decision: [READY_FOR_SCOPED_WRITE | VALIDATION_PENDING | BLOCKED]
```
