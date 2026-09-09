---
name: media-storage-lifecycle-guardian
description: İlan fotoğrafları ve diğer medya için DB kayıtlarını, fiziksel storage yollarını ve tenant sahipliğini çapraz denetler; yetim dosya ve cross-tenant sızıntılarını güvenli biçimde raporlar.
---

# Media Storage Lifecycle Guardian

Bu yetenek, medya yaşam döngüsünü salt-okunur keşif ve kanıtlı temizlik planı olarak yönetir.

## Denetim

- Fotoğraf/medya DB kayıtlarını fiziksel disk veya object storage yollarıyla karşılaştır: DB'de olmayan dosyalar, dosyası olmayan kayıtlar, bozuk symlink ve yinelenen yolları ayır.
- Her yolun ilan, tenant ve erişim ilişkisini çöz; `storage/app/public/ilan-fotograflari/{ilan_id}` gibi tenant'sız legacy yolları risk olarak işaretle.
- Silinen ilan, silinen fotoğraf ve test fixture medyasını birbirine karıştırma. Correlation ID veya fixture etiketi yoksa otomatik silme önerme.
- Dosya boyutu, MIME/uzantı, public URL ve erişim politikasını kontrol et; kullanıcı yüklemesinde path traversal ve ortak klasör yazımını reddet.
- Temizlik için önce sayım ve manifest üret; batch sınırı, karantina yolu, geri alma planı ve owner onayı olmadan `DELETE`/unlink çalıştırma.

## Kanıt

Disk/DB çapraz sayımı `REPO_VERIFIED` veya `TEST_VERIFIED` olabilir. Canlı storage ve tenant sahipliği için exact host, tarih, kapsam ve erişim kanıtı gerekir; production temizliği ayrı yetkilendirilir.

## Sınırlar

Bu yetenek dosya silmez, bucket lifecycle policy değiştirmez, migration/seed/deploy yapmaz ve tenant sahipliğini varsaymaz.
