---
name: location-data-reconciliation
description: Yalıhan OS location hierarchy reconciliation, orphan FK analysis, and evidence-based migration planning.
---

# Location Data Reconciliation

## Amaç

`iller → ilceler → mahalleler` hiyerarşisini veri kaybı ve referans kırılması olmadan analiz eder. Golden Thread Step 4 lokasyon akışını tamamlamak için migration/seeder kararını kanıta dayalı verir.

## Değişmez sınırlar

- Production veritabanında read-only inceleme yapmadan yazma işlemi başlatma.
- `TRUNCATE`, toplu silme ve körlemesine ID overwrite kullanma.
- Mevcut `il_id`, `ilce_id` ve `mahalle_id` referanslarını önce say ve listele.
- Orphan kayıtların nasıl oluştuğunu foreign key metadata’sı ile doğrula.
- Seeder’ı migration yerine kullanma; mevcut seed davranışını ve doğal anahtarlarını incele.
- Plaka kodu veya doğrulanmış doğal anahtarlarla idempotent eşleştirme tercih et.
- Migration, seed ve veri düzeltmesini ayrı operasyonlar olarak raporla.

## İnceleme sırası

1. İlgili migration, model, seeder ve schema dump’larını bul.
2. Tablo kolonlarını, index’leri ve foreign key’leri doğrula.
3. Production’da tablo/kayıt/referans durumunu read-only kontrol et.
4. Kullanılan ve kullanılmayan ID’leri ayır.
5. Reconciliation planını; insert, update, orphan repair ve rollback olarak yaz.
6. Önce local/test ortamında doğrula.
7. Production değişikliği için açık kullanıcı onayı bekle.

## Güvenli doğrulama ölçütleri

- Duplicate doğal anahtar yok.
- Orphan foreign key sayısı artmıyor.
- Mevcut ilan ve kişi referansları değişmeden kalıyor.
- İlçe ve mahalle parent ilişkileri geçerli.
- Wizard cascade doğru JSON verisini döndürüyor.
- TC-GT-05 ve TC-GT-06 gerçek test çıktısıyla geçiyor.

## Kanıt raporu

`REPO_VERIFIED`, `TEST_VERIFIED`, `PRODUCTION_VERIFIED`, `DOCUMENTED`, `INFERRED` ve `UNKNOWN` etiketlerini birbirine karıştırma. Seed dosyasının varlığı production verisinin hazır olduğunu kanıtlamaz.

Schema/API değişikliğinde `.project-brain/DATA_CONTRACT_CHECK.md`; production değişikliğinde `.project-brain/OBSERVABILITY_PLAN.md` ve `.project-brain/ROLLBACK_SIMULATOR.md` uygulanır.
