# Tenant İzolasyonu — Yalıhan Mimari Bekçi

## Kapsam

`bekci:tenant-audit` komutu, Eloquent model kaynaklarını ve migration envanterini
salt-okunur olarak karşılaştırır. Migration veya production veritabanına bağlanmaz;
otomatik trait eklemez, backfill yapmaz ve şema değiştirmez.

## Kullanım

```text
php artisan bekci:tenant-audit
php artisan bekci:tenant-audit --json
php artisan bekci:tenant-audit --strict
```

Varsayılan mod `report-only`'dir. `--strict` yalnızca kritik kaynak bulguları
bulunduğunda başarısız çıkış kodu verir. `config/tenant-isolation.php` içindeki
global tablo listesi açık bir allowlist'tir; iş tabloları bu listeye eklenerek
uyarı gizlenmemelidir.

## Kanıt sınırı

- `DOCUMENTED`: statik kaynak taraması sonucu.
- `REPO_VERIFIED`: model/migration mekanizması kaynakta görüldü.
- `TEST_VERIFIED`: iki tenant'lı davranış testi geçildi.
- `PRODUCTION_VERIFIED`: deployed commit ve canlı davranış ayrıca doğrulandı.

Bu Bekçi denetimi tek başına tenant izolasyonunu veya production güvenliğini
sertifikalandırmaz.
