# ADR-043: Kanonik Form Sözleşmesi ve Seeder Yönetişim Standardı

**Tarih:** 2026-09-12  
**Statü:** ACCEPTED — Mimari Standart  
**Karar Verici:** Product Owner / YALIHAN Baş Mimarlık  
**Kapsam:** Form Şeması, Wizard/Admin CRUD Alan Sözleşmeleri, Seeder Sınıflandırması ve Katman Ayrımı  

---

## 1. Bağlam ve Problem (Context)

Yalıhan OS'ta form alanı tanımları ve dinamik şemalar iki farklı seeder (`SmartFormsCanonicalSeeder` ve `CategoryFieldSchemaSeeder`) tarafından `kategori_yayin_tipi_field_dependencies` tablosuna yazılmaktaydı. Bu durum şu mimari riskleri doğurmuştur:
1. **İsimlendirme Çelişkisi:** Bir seeder `kebab-case` (`oda-sayisi`, `brut-metrekare`) kullanırken diğeri `snake_case` (`oda_sayisi`, `brut_m2`) kullanmakta; Eloquent veritabanı kolonları ise `snake_case` beklemektedir.
2. **Kategori Slug Uyuşmazlığı:** Master `IlanKategoriSeeder` `arsa-arazi` ve `yazlik-kiralama` kullanırken, seeder'lardan biri `arsa` ve `yazlik` kısaltmalarını kullanmaktadır.
3. **Katman Karışıklığı:** Seeder'lar saf iş kuralı (Domain) gibi konumlandırılmakta, ancak domain sözleşmesi olmadan doğrudan veritabanı satırı üretilmektedir.

---

## 2. Mimari Karar (Decision)

### 2.1. 4 Katmanlı DDD Ayrımı
Form ve alan motoru kesin olarak 4 katmana ayrılmıştır:

- **Domain Katmanı:**
  - `FieldKey`: Değer nesnesi (Value Object). Kesinlikle Context7 uyumlu **`snake_case`** (`oda_sayisi`, `brut_m2`, `bina_yasi`, `tapu_durumu`).
  - `ValidationRule` & `FieldOption`: Tip, min/max, zorunluluk ve seçenek kuralları.
  - `CategoryFieldPolicy`: Kategori ve yayın tipine göre zorunlu ve opsiyonel alan politikaları.
- **Application Katmanı:**
  - `FieldResolver` / `WizardSchemaResolver`: Form şemasını sorgulayan ve DTO'lara dönüştüren use case servisleri.
- **Infrastructure & Bootstrap Katmanı:**
  - `KategoriYayinTipiFieldDependency` Eloquent modeli ve migration'ları.
  - Seeder'lar (`CategoryFieldSchemaSeeder`): Domain politikalarını veritabanına taşıyan altyapı besleyicileri.
- **Presentation Katmanı:**
  - Controller'lar, Blade şablonları, Alpine.js reaktif state'leri ve JSON yanıt zarfları.

### 2.2. Seeder Sınıflandırma ve Çalışma Politikası
Seeder'lar 6 sınıfa ayrılmış olup, `DatabaseSeeder.php` yalnızca `CORE_MASTER` ve `DOMAIN_REFERENCE` sınıflarını çalıştırabilir:
1. `CORE_MASTER` (Kategori, Yayın Tipi, Ülke, Kanonik Form Şeması)
2. `DOMAIN_REFERENCE` (Feature Matrix, Finans & Kur, Lokasyon Hiyerarşisi)
3. `DEV_FIXTURE` (Demo İlanlar, Yatırımcı Senaryoları, Bulk Test Verileri — Prodüksiyonda YASAK)
4. `ON_DEMAND` (Diller, Para Birimleri, Bakım Verileri)
5. `HISTORICAL_DATA_MIGRATION` (Veri dönüştüren tek seferlik migration'lar)
6. `LEGACY_QUARANTINED` (Eski özellik seed'leri)

### 2.3. Data Contract Gate Zinciri
Her veri değişikliğinde şu 7 halka zorunludur:
$$\mathbf{Schema \longrightarrow Seed \longrightarrow Model \longrightarrow Runtime \longrightarrow Test \longrightarrow Tenant \longrightarrow Rollback}$$

---

## 3. Sonuçlar ve Getiriler (Consequences)

### Olumlu:
- Form alanları veritabanı kolonlarıyla birebir örtüşür (`snake_case`).
- Seeder'lar domain yerine geçmez; domain'in infrastructure adaptörü olur.
- Çakışan seeder'lar tek kanonik kaynak (`CategoryFieldSchemaSeeder`) altında konsolide edilir.
- `RentalPropertyBulkSeeder` gibi dev fixture'ların master akışa sızması engellenir.

### Ödünler & Dikkat Edilecekler:
- Kebab-case alan bekleyen eski frontend veya legacy API endpoint'leri varsa, geriye dönük uyumluluk için `FieldResolver` seviyesinde compatibility accessor/alias sağlanmalıdır.

---

## 4. Uygulama Adımları (Next Steps)

1. Ayrı bir Git worktree ve özel branch üzerinde `app/Domain/Listing/` veya `app/Domain/Form/` altında saf Domain sınıflarının (`FieldKey`, `ValidationRule`, `CategoryFieldPolicy`) inşa edilmesi.
2. `CategoryFieldSchemaSeeder`'ın master kategori slug'ları ile tam uyumlu hale getirilmesi.
3. Feature ve unit testlerle sözleşme eşitliğinin (`Contract Parity`) doğrulanması.
