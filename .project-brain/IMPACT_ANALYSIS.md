# Change Impact Analysis

Bu kayıt, her material değişiklikten önce doldurulur. Amaç yalnızca değişen dosyayı değil, etkilenen sistemi görmek.

## Active analysis

### Change: `ilanlar.bina_yasi` YEAR → yaş tamsayısı dönüşüm güvenliği

- Date: 2026-08-29
- Request/goal: MySQL YEAR sınır değerlerini veri kaybı olmadan yaş tamsayısına dönüştürmek ve gerçek rollback sağlamak.
- Baseline commit: `a71b5c6`
- Proposed files: `database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php`, `.project-brain/IMPACT_ANALYSIS.md`

## Dependency impact

- Routes: NONE
- Controllers: NONE
- Services/actions: `IlanCrudService::syncFeatures()` sonraki kod paketinde yaş tamsayısı bekliyor.
- Models: `Ilan::$casts['bina_yasi'] = integer` ile uyumlu.
- Migrations/tables: `ilanlar.bina_yasi`; migration rollback yedeği için geçici kalıcı yardımcı tablo.
- API contracts: `bina_yasi` yaş tamsayısı semantiğine geçiyor.
- Views/CSS/JS: Wizard `bina-yasi` seçenek aralıkları.
- Jobs/queue/events/Hermes: NONE
- External integrations: İlan feed tüketicileri yaş semantiğini kullanabilir.

## Risk impact

- Tenant isolation: LOW — aynı tablo genel DDL; satır kimlikleri yedeklenir.
- Authentication/session: NONE
- Data/migration: HIGH — ALTER TABLE ve mevcut değer dönüşümü.
- Queue/cache: LOW
- Security/secrets: NONE
- Performance: MEDIUM — DDL tablo kilidi oluşturabilir.
- Rollback complexity: HIGH — eski YEAR değerleri birebir yedekten geri yüklenmelidir.

## Verification plan

- Focused tests: İzole MySQL üzerinde 1970–1999, 2000–2069, NULL ve sınır dışı değerlerle gerçek `up()`/`down()`.
- Regression tests: `IlanCrudFeatureNormalizationTest`.
- Build/assets: N/A
- HTTP checks: N/A
- Browser checks: N/A
- Production checks: Yalnız ayrı production onayı sonrası kolon tipi, değer dağılımı, migration kaydı ve rollback yedeği.

## Decision

- Proceed / revise / stop: PROCEED TO COMMIT
- Reason: Sınır aralıkları düzeltildi; birebir rollback yedeği ve DDL öncesi geçersiz değer kontrolü gerçek izole MySQL döngüsünde doğrulandı.
- Required approval: Yerel düzeltme ve izole test yetkili; production migration için ayrıca açık onay gerekir.

## Result record

- Actual changed files: `database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php`, `.project-brain/IMPACT_ANALYSIS.md`
- Test result: TEST_VERIFIED — izole MySQL `up()`/`down()`; 1970–1999 ve 2000–2069 aralıkları, NULL, yeni satırlar ve geçersiz değer preflight kontrolü geçti.
- Build result: N/A
- Git commit: PENDING
- VPS deployed commit: NONE
- HTTP/browser result: N/A
- Remaining risks: Production DDL lock süresi ve production değer dağılımı read-only preflight ile ayrıca doğrulanmalı.
- Brain files updated: `.project-brain/IMPACT_ANALYSIS.md`
