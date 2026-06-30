# 📝 Refactoring & Governance Log

## 🏗️ 1. YalihanCortex Domain Decomposition (2026-05-14)
`YalihanCortex.php` monolitik yapısı parçalanarak domain bazlı servislere dağıtıldı.

- **Ölçülebilir Sonuç:** 5.827 satırdan **3.139 satıra** düşürüldü (~2.688 satır tahliye edildi).
- **Constructor:** 44 bağımlılıktan **36 bağımlılığa** indirildi.
- **Domain Servisleri:**
    - `CortexQualityService`: İlan kalite validasyonu + `suggestCategory`
    - `CortexIntelligenceService`: Lead değerlendirme, portföy analizi, raporlama + `analyzeMarketTrends`
    - `CortexPredictionService`: Satış/gelir tahmini, churn analizi + `getTopChurnRisks`
    - `CortexContentService`: Çok dilli içerik ve başlık üretimi

**Commits:** `4d61b98`, `f157217`

## 🛡️ 2. Governance & Git Noise Reduction (2026-05-14)
- `docs/_reports/` ve `docs/API_CONTRACT.md` → `.gitignore` kapsamına alındı.
- Git önbelleğinden temizlendi; timestamp gürültüsü kalıcı olarak kesildi.

**Commit:** `208a02d`

## 🔍 3. GovernanceCore Modül Denetimi (2026-05-15)
`app/Modules/` dizini denetlendi. **5 yetim modül** tespit edildi.

### Düzeltilen Sorunlar
- **#57** GovernanceCore kayıtsızdı → `GovernanceCoreServiceProvider` oluşturuldu (16 sınıf singleton olarak DI'a bağlandı)
- **#57** `GovernanceEngineInterface` implementasyonu yoktu → `GovernanceEngine` oluşturuldu (10 metod)
- **#59** Talep + TalepAnaliz yetimdi → `ModuleServiceProvider`'a kayıt edildi

### Tespit Edilen / Ertelenen Sorunlar
- **#58** İki `DriftDetectionService` (`Core\` vs `Services\`) — kanonik seçim Sprint 1'e bırakıldı
- **#60** `App\Providers\ModuleServiceProvider` vs `App\Modules\ModuleServiceProvider` isim çakışması — Sprint 1
- `Talep`/`TalepAnaliz` `config/app.php`'den çıkarıldı (çift kayıt düzeltmesi)

**Commit:** `bc48b72`

## 🛡️ 4. Tenant Isolation & CI (2026-05-15, commit bekliyor)
- `SetTenantContext` middleware oluşturuldu
- `Kernel.php` → `tenant.context` alias eklendi
- `field-mcp.php` → `auth:sanctum` + `tenant.context` eklendi
- CI → PHP 8.2 production eşleşmesi
- N8N webhook fallback null düzeltmesi

**Commit:** Yerel terminalde çalıştırılacak (`.git/index.lock` kısıtı nedeniyle)

## ⏳ 5. Pending Items
- `processVoiceSearch` → Integration Domain
- `sendNotification` → Integration Domain
- `processN8nJob` → Integration Domain
- `checkAiHealth` / `switchAiProvider` → Meta Domain
- 12 legacy `kategori_id` referansı temizliği
- `ListingAIResponseValidatorTest` güncelleme (healing vs rejection)

---
## 🛡️ 6. Yalıhan Bekçi v2.1 — AST Revolution (2026-05-15)
Yalıhan Bekçi denetim motoru regex tabanlı yüzeysel taramadan, PHP-Parser tabanlı anlamsal (AST) analize taşındı.

- **Bilişsel Muhafız**: `NamingAuthorityAstRule` ve `SilentCatchAstRule` (AST tabanlı) implemente edildi.
- **Gözlemlenebilirlik**: Regex'in kaçırdığı 42 adet "Anlamsal Hayalet" (Silent Catch) tespit edildi ve haritalandırıldı.
- **Yaşayan Bellek**: `LEARNED_PATTERNS.json` ve `bekci:pattern:learn` ile hatalardan ders çıkaran regresyon koruması kuruldu.
- **Anayasa**: SAB v1.1.0 (Bilişsel Mühür) ile mimari standartlar kod düzeyinde donduruldu.

**Commit:** `bekci-v2.1-ast-release`

---
## 🛡️ 7. Phase 12 — Monetization Core Foundation (2026-05-15)
Yalıhan AI OS'un ticari otoritesini sağlayan multi-tenant finansal altyapı ve AI kredi denetim sistemi kuruldu.

- **Finansal Sertleştirme**: `FinancialLedgerService` ve modelleri (`LedgerAccount` vb.) `tenant_id` bazlı izolasyonla güçlendirildi.
- **Kanonik İsimlendirme**: `BillingLedgerEntry` şeması SAB uyumlu `islem_tutari` ve `islem_turu` alanlarına taşındı.
- **AI Kredi Sigortası**: `AiBudgetGuard` (Circuit Breaker) ve `AiCreditBalance` ile kredili AI kullanım denetimi sağlandı.
- **Güvenlik Katmanları**: `SubscriptionMiddleware` ve `WebhookSignatureGuard` (Stripe/Iyzico) devreye alındı.

**Commit:** `phase-12-monetization-core-v1`

---
*Bu log dosyası mimari dönüşümün takibi amacıyla oluşturulmuştur.*
