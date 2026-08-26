---
name: yalihan-os-architect
description: Yalıhan OS için kanıt-temelli mimari, güvenlik, E2E ve release hazırlığı desteği.
---

# Yalıhan OS Architect

## Amaç

Yalıhan OS üzerinde çalışan agent'ın mimari sınırları koruyarak ilerlemesini sağlar. Kod, test, Git ve production kanıtlarını birbirinden ayrı raporlar.

## Çalışma sırası

1. `.sab/authority.json`, `AGENTS.md` ve ilgili `.project-brain` dosyalarını oku.
2. Görevin kapsamını ve etkilenecek en fazla gerekli dosyaları belirle.
3. Kaynak kodu ve testleri incele; varsayımları `INFERRED` veya `UNKNOWN` olarak işaretle.
4. Değişiklikten önce etki, güvenlik ve rollback riskini değerlendir.
5. Minimum diff uygula.
6. İlgili testleri, kalite kapılarını ve gerekiyorsa browser/HTTP akışını doğrula.
7. `PROJECT_STATE.md`, `FEATURE_MATRIX.md`, `EVIDENCE_INDEX.md` ve `KNOWN_ISSUES.md` dosyalarını gerektiği kadar güncelle.

## Değişmez mimari kurallar

- Tenant izolasyonu her sorgu ve yazma işleminde korunur.
- Yazma zinciri: Controller → Service → IlanCrudService → Repository → DB.
- Controller içinde doğrudan Eloquent create/update/delete yapılmaz.
- Hermes yalnızca olay koordinasyonu ve dağıtımıdır; LLM çağırmaz.
- YalihanCortex bilişsel AI katmanıdır; AI önerileri açıklanabilir kaynak/provenance taşımalıdır.
- Context7 kanonik alan adları kullanılır: `yayin_durumu`, `aktiflik_durumu`, `il`, `ilce`, `mahalle`, `kapak_resmi`.
- Deterministik sorgularda `orderBy('id')->first()` kullanılır.
- Uygulama kodunda `env()` yerine `config()` kullanılır.
- Font Awesome ve harici görsel API'leri kullanılmaz; `<x-icon>` ve yerel varlıklar tercih edilir.
- Nginx public storage yalnızca güvenli raster görselleri sunar; private dosyalar dışarı açılmaz.

## Kanıt standardı

- `REPO_VERIFIED`: Mevcut repository kodu ile doğrulandı.
- `TEST_VERIFIED`: İlgili test çıktısı ile doğrulandı.
- `PRODUCTION_VERIFIED`: Tarihli canlı HTTP/browser/SSH kanıtı ile doğrulandı.
- `DOCUMENTED`: Dokümantasyonda belirtiliyor, uygulama kanıtı ayrıca gerekir.
- `INFERRED`: Kod veya bağlamdan çıkarım.
- `UNKNOWN`: Henüz doğrulanmadı.

Kodda bulunması veya PHPUnit'in geçmesi production çalışmasını kanıtlamaz.

## Golden Thread kontrolü

İlan oluşturma → Cortex zenginleştirme → fotoğraf/konum → taslak kaydetme → yönetici onayı → yayın → CRM eşleşmesi → danışman görevi.

Her adım için backend, frontend, veri bütünlüğü, tenant izolasyonu ve gerçek kullanıcı/browser kanıtı ayrı kontrol edilir.

## Release sınırı

Commit, deploy, migration, seed ve container restart için kullanıcıdan açık yetki gerekir. Yetki yoksa yalnızca local analiz, test, aday diff ve read-only production incelemesi yapılır. Sırlar, tokenlar, şifreler ve ham hassas loglar rapora veya hafızaya yazılmaz.

## Önerilen doğrulama komutları

```text
git diff --check
./scripts/tools/project-brain-gate.sh
./scripts/tools/antigravity-full-gate.sh --quick
php artisan sab:integrity-scan
```

Schema/API/form değişikliklerinde `DATA_CONTRACT_CHECK.md`; runtime/release değişikliklerinde observability ve rollback belgeleri ayrıca uygulanır.
