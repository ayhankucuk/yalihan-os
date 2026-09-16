# YALIHAN OS — "Tamamlandı" Tanımı

> **Prensip**: "Tamamlandı" tek bir an değildir. Bir görevin 4 aşaması vardır.
> Her aşamanın kendi kanıtı olmalıdır. Aşama atlamak yasaktır.

---

## 4 Aşama Tanımı

| Aşama | İsim | Anlamı | Kanıt Gereksinimi |
|-------|------|--------|-------------------|
| **S1** | KOD YAZILDI | Kod commit edildi, PR açıldı | `git log` commit hash |
| **S2** | TEST GEÇTİ | İlgili test suite geçti, regression yok | `php artisan test` çıktısı |
| **S3** | RC'YE ALINDI | Release candidate branch'inde merge edildi | RC2 branch commit |
| **S4** | PRODUCTION'DA DOĞRULANDI | VPS'de migration ran, index/var exists, smoke test | SSH çıktısı, kanıt |

---

## Aşama Detayları

### S1 — KOD YAZILDI
- Kod `git commit` ile commit edildi
- Commit mesajı açık ve izlenebilir
- Pre-commit hook (secret scanner) geçti
- Conflict guard acquire/release yapıldı

**Kanıt**: Commit hash + `git show --stat <hash>`

### S2 — TEST GEÇTİ
- İlgili test dosyası çalıştırıldı ve PASS verdi
- Regression testleri çalıştırıldı (0 yeni failure)
- Coverage eklendi (yeni davranış için)

**Kanıt**: `php artisan test <test-file>` çıktısı — PASS sayısı + assertion sayısı

### S3 — RC'YE ALINDI
- Kod `release-candidate/RC2` branch'ine merge edildi
- Merge conflict'leri çözüldü
- RC2 GitHub'a push edildi
- RC2'de full test suite çalıştırıldı (known failures dokümante edildi)

**Kanıt**: `git log --oneline release-candidate/RC2` + GitHub push çıktısı

### S4 — PRODUCTION'DA DOĞRULANDI
- VPS (157.180.116.63) SSH ile bağlanıldı
- Migration `php artisan migrate:status` ile doğrulandı
- DB index/column/var exists kontrolü yapıldı
- Smoke test (route erişimi, API response) yapıldı

**Kanıt**: SSH komut çıktısı + `migrate:status` + `SHOW INDEX` çıktısı

---

## Örnek: BACKLOG-9 (Lead Unique Index)

| Aşama | Durum | Kanıt |
|-------|-------|-------|
| S1 | ✅ | Commit `37144cd7` — migration + model cherry-pick |
| S2 | ✅ | LeadTenantBoundaryTest 11/11 PASS (30 assertions) |
| S3 | ✅ | RC2 merge `f1ae1219`, push to GitHub |
| S4 | ✅ | VPS: migration [18] Ran, `leads_tenant_platform_user_unique` index EXISTS |

---

## Örnek: GAP-03 (ChannelSynchronizationException)

| Aşama | Durum | Kanıt |
|-------|-------|-------|
| S1 | ✅ | Commit `a5a50824` — retryable check + exception class |
| S2 | ✅ | AvailabilitySynchronizationServiceTest 11/11 PASS |
| S3 | ✅ | RC2'de mevcut, GitHub'a push edildi |
| S4 | ⏳ | Production'da henüz doğrulanmadı (RC2 deploy sonrası) |

---

## Yasaklar

1. **S2 olmadan S3 yasak** — test geçmeden RC'ye merge edilemez
2. **S3 olmadan S4 yasak** — RC'de olmayan kod production'da doğrulanamaz
3. **S4 olmadan "Tamamlandı" denmez** — production doğrulaması olmadan görev kapatılamaz
4. **Aşama atlamak yasak** — her aşama sırayla gitmeli

---

## İstisnalar

- **Sadece dokümantasyon değişiklikleri**: S2 atlanabilir (test yok)
- **Sadece .gitignore değişiklikleri**: S2 ve S4 atlanabilir
- **Config dosyası ekleme**: S4 için production'da config cache temizliği yeterli

---

## Bakım

Bu doküman her oturumda güncellenir. Görev kapatılırken aşama tablosu doldurulur.
