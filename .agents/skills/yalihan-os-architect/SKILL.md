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

## MCP Entegrasyon Noktaları

**Yalihan Bekçi MCP** (`yalihan-bekci-mcp.js`) mimari kararları destekler. Aşağıdaki adımlarda MCP tool çağır:

| Adım | MCP Tool | Ne için |
|------|----------|---------|
| Karar öncesi | `check_violation` | Kod snippet'inin guard ihlali içerip içermediğini kontrol et |
| Kural sorgulama | `get_authority` | `authority.json`'dan kural, yasak alan veya governance bilgisi al |
| Naming kontrol | `get_canonical` | Context7 kanonik isimler için: `"status" → "yayin_durumu"` |
| Ön-implementasyon tarama | `validate_file` | Değişiklik öncesi tüm guard'lardan geçir (tenant, URL, naming, exception) |
| Proje sağlık durumu | `get_project_health` | Tenant isolation skoru, violation sayısı, uncommitted dosyalar |
| Mimari karar kaydetme | `record_learning` | Önemli bir karar veya düzeltmeyi Bekçi knowledge base'e kaydet |
| Audit sonucu okuma | `get_audit_report` | En son Bekçi audit raporunu al |
| Öğrenme geçmişi | `get_learning_history` | Bekçi'nin birleşik öğrenme geçmişini incele |

> **Not:** MCP server subprocess olarak çalışır. Tool çağrıları `MCP client → yalihan-bekci-mcp.js → PHP Artisan` zinciri üzerinden gider. HTTP port gerekmez.

## MCP Entegrasyon Örnekleri

```
Soru: "Bu kod snippet'inde kural ihlali var mı?"
→ MCP: check_violation(code_snippet) → ihlal listesi veya "temiz"

Soru: "authority.json'da bu alan yasak mı?"
→ MCP: get_authority(field_name) → yasak/tanımlı/bilinmiyor

Soru: "Bu kod değişikliği güvenli mi?"
→ MCP: validate_file(path) → guard sonuçları + score

Soru: "Mimari bir karar verdim — nasıl kaydedeyim?"
→ MCP: record_learning(action_type, description, context)
```

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
php artisan bekci:audit
php artisan bekci:health
```

Schema/API/form değişikliklerinde `DATA_CONTRACT_CHECK.md`; runtime/release değişikliklerinde observability ve rollback belgeleri ayrıca uygulanır.
