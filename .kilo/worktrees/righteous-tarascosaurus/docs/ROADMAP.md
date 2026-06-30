# Yalıhan Emlak — Sistem Yol Haritası

**Versiyon:** 1.0.0
**Tarih:** 16 Mayıs 2026
**SAB:** v6.1.1 | **Durum:** PRODUCTION READY — deploy bekliyor

---

## Mevcut Durum

Phase 4A · 4B · 4C tamamlandı. Governance mimarisi sağlam, kurallar uygulanıyor.
Owner Portal (D16) Task #14–18 tamamlandı. 87 uncommitted dosya var.

**Asıl sorun:** Mimari iyi, üstüne birikenler temizlenmemiş.

---

## AŞAMA 1 — Bu hafta · "Commit & Seal"

Sıfır risk. Hepsi standalone iş.

- [ ] Git commit → `bash docs/_commit.sh`
- [ ] `php artisan migrate` → 3 Owner Report tablosu + GovernanceDecision hash → **Global Seal**
- [x] FIX-01: Dead `IlanController` import silindi
- [x] FIX-02: Dead commented routes temizlendi (Etiket + SAB PURGE blokları)
- [x] Mail entegrasyonu: `Mail::to()` aktif, plain token log açığı kapatıldı
- [x] Task #19: Raporlar — export API, filtre formu, gerçek durum göstergesi
- [x] Task #20: Mobil hamburger, dark mode, Toast, max-width düzeltmesi
- [x] N8N Webhook: `.env`'de mevcut — kapatıldı (known-debt #10)
- [x] Owner Report migration'ları: `rows` + `metrics` + `exports` tabloları
- [ ] 89 fail test → yeşile çek (migrate sonrası azalacak)

---

## AŞAMA 2 — 2-4 hafta · "Temizlik & Yapı"

Risk: LOW-MEDIUM. Her fix ayrı PR.

### P2 Fix'leri (FIX-06~10)
- [ ] FIX-06: `AIController` CRM methods → `AICrmGatewayService`
- [ ] FIX-07: `PropertyHubController` AI methods → `PropertyAIService`
- [ ] FIX-08: `IlanAITitleDescriptionController` verify/mitigate
- [ ] FIX-09: `IlanAIQualityController` → `AIQualityController` rename
- [ ] FIX-10: `IlanQualityDashboardController` → `AIQualityDashboardController` rename

### Context7 Temizliği
- [ ] 175 violation → `sab:integrity-scan --auto-fix` ile batch temizlik
- [ ] 204 baseline violation → kademeli azalt, hedef: 0
- [ ] Yasaklı alanlar: `status` → `durum_kodu`, `active` → `aktif_mi` vb.

### Dual Sistem Konsolidasyonu
- [ ] CRM V1 (`Musteri`) + V2 (`CRM\*`) → tek model (ayrı migration sprint)
- [ ] Finance modül çakışması: `FinansalIslem` vs `Finance\*`
- [ ] Yanlış namespace servisler: 4 Advisor + 2 CRM → doğru konuma taşı

---

## AŞAMA 3 — 1-3 ay · "Mimari Olgunluk"

Risk: HIGH. ADR + tam test coverage şart. Phased approach zorunlu.

### YalihanCortex Decompose (EN ACİL — büyümeye devam ediyor)
- [ ] 35+ dep → domain servislerine bölün (şu an her ay büyüyor)
- [ ] FIX-16: `Api\AIController` → 4 domain controller'a split
- [ ] Büyümeyi HEMEN durdur: yeni dep eklemeyi freeze et

### P3 God Class Split'leri
- [ ] FIX-11: `PropertyHubController` (28 method) → 4 controller
- [ ] FIX-12: `DecisionEngineController` (27 method) → 4 controller
- [ ] FIX-13: `DanismanAIController` → Config + AI ayrı
- [ ] FIX-14: `PortfolioDoctorController` → AI prefix'e taşı
- [ ] FIX-15: `CRMController` → internal split

### Template SSOT + Namespace Migration
- [ ] FIX-17: 11 controller → tek template hiyerarşisi (ADR gerekli)
- [ ] FIX-18: 14 controller Api/Admin namespace migration

---

## Kritik Kurallar (Dokunma)

| Sınıf | Kural |
|-------|-------|
| `IlanCrudService::store()` | DOKUNMA — tek write authority |
| `StoreIlanRequest` | YÜKSEK RİSK |
| `ListingStateMachine` | BYPASS YASAK |
| `FeatureTemplateResolver` (Ups\) | SSOT koru |
| `authority.json` | Agent değiştiremez |

---

## Uyarı

> **YalihanCortex her geçen gün büyüyor (30 → 35+ dep).**
> Aşama 3'ü erteleme. Önce yeni dependency eklemeyi freeze et,
> sonra Cortex servislerini domain katmanlarına çıkar.

---

*Son güncelleme: 16 Mayıs 2026 | Bekçi herzaman uyanık.*
