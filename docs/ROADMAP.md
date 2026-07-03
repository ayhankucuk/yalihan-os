# Yalıhan Emlak — Sistem Yol Haritası

**Versiyon:** 3.0.0
**Son güncelleme:** 2026-07-03 (Oturum 67 — YSOS Era Başladı)
**YSOS:** v1.0 ACTIVE | **SAAB:** v7.0 | **Durum:** YSOS ENGINEERING STANDARD

---

## YSOS Era Başlangıcı (2026-07-03)

**YSOS — Yalıhan Sprint Operating System** resmi olarak devreye girdi.

YSOS = Platform Engineering + Context Engineering + Agent Engineering birleşimi.
Bu proje artık sadece kod değil — **metodoloji** üretiyor.

### YSOS Bileşenleri

| Bileşen | Durum |
|---------|--------|
| YSOS Framework | ✅ ACTIVE |
| SAAB v7 (Governance) | ✅ ACTIVE |
| Sprint Lifecycle | ✅ ACTIVE |
| Quality Gates | ✅ ACTIVE |
| Context Engineering | ✅ ACTIVE |
| Evidence Standard | ✅ ACTIVE |
| Certification Standard | ✅ ACTIVE |
| Handoff Standard | ✅ ACTIVE |
| Sprint Template | ✅ ACTIVE |
| Artisan Commands (design) | 📋 Designed |

---

## Mevcut Durum

Sprint 4.2 tamamlandı. Owner Portal CRUD Lifecycle fonksiyonel.
Sprint 4.3 (AI Workforce Zinciri) planlanıyor.
**Yeni mühendislik standardı:** YSOS v1.0

---

## SPRINT 1 — ✅ TAMAMLANDI (2026-05-10/12)

- [x] FIX-01: Dead `IlanController` import silindi
- [x] FIX-02: Dead commented routes temizlendi
- [x] Mail entegrasyonu: `Mail::to()` aktif
- [x] N8N Webhook: `.env`'de mevcut
- [x] Owner Report migration'ları: `rows` + `metrics` + `exports` tabloları
- [x] GovernanceDecision hash migration
- [x] APP_DEBUG=false + APP_ENV=production
- [x] FieldMCP auth:sanctum+tenant
- [x] SetTenantContext middleware
- [x] CI PHP 8.2 → production match

---

## SPRINT 2 — ✅ TAMAMEN KAPANDI (2026-06-15)

| Görev | Commit | Durum |
|-------|--------|-------|
| #19 YalihanCortex God Object dekompoze | `5004346` | ✅ |
| #28 app/Domains/ → app/Domain/ birleştirme | `6909772` | ✅ |
| #58 DriftDetectionService kanonik seçim | `a8cf352` | ✅ |
| #60 ModuleServiceProvider isim çakışması | `6125ca3` | ✅ |
| #61 yalihan-bekci/ MCP dizin denetimi | `b68a7c9` | ✅ |
| B-006 Deprecated ghost model temizliği | `a947d80c` | ✅ |

---

## SPRINT 3 — ✅ KAPANDI (2026-06-15/16)

### Tamamlanan
- [x] Kisi.php Context7 email→eposta — `6923cf73`
- [x] Ilan.php + Kisi.php pivot aktiflik_durumu fix
- [x] IlanCrudService Split-Brain fix (handleVerticalDetails) — Seçenek A
- [x] MD Audit: 432 → 195 dosya, docs/ kökü 12 SSOT'a indirildi
- [x] PROGRESS-TRACKER kırık referanslar temizlendi
- [x] known-debt.md 35 maddeye güncellendi

---

## SPRINT 4 — 🔄 FAZ 2 ÜRÜN AŞAMASI (2026-06-25 → )

Risk: HIGH. ADR + tam test coverage şart.

| # | Görev | Durum |
|---|-------|-------|
| Sprint 4.0 | Reliability Hardening | ✅ KAPANDI |
| Sprint 4.1 | Alpine.js UI Stabilization | ✅ KAPANDI |
| Sprint 4.2 | Real CRUD Certification | ✅ KAPANDI |
| Sprint 4.3 | AI Workforce Zinciri | ⏳ Planlanıyor |
| Sprint 4.4 | Dashboard + Event Monitoring | ⏳ Planlanıyor |
| Sprint 4.5 | Telegram Entegrasyonu | ⏳ Planlanıyor |

---

## SPRINT 4.2 — ✅ KAPANDI (2026-07-03)

**YSOS Sprint Standardı ilk uygulama.**

| Metric | Pre-Sprint | Post-Sprint |
|--------|-------------|-------------|
| OwnerIlanCrudTest | 9/20 pass | **12/15 pass** |
| Regression | — | **0 new failures** |
| Controller methods missing | 4 | **0** |
| Blade enum TypeError | 3 files | **0** |

**Değişiklikler:**
- `ucfirst()` → `->label()` (3 blade dosyası)
- `edit()`, `update()`, `destroy()`, `readiness()` eklendi
- Route model binding `{ilan}` aktif
- `IlanPolicy::update()` ownership fix

---

## SPRINT 4.3 — AI Workforce Zinciri (Planlanıyor)

```
Yeni İlan
    ↓
PortfolioCreated Event
    ↓
Hermes (Event Broker)
    ↓
Photo Agent (görsel analiz)
    ↓
Description Agent (içerik üretimi)
    ↓
Notification Agent (bildirim)
    ↓
Dashboard (sonuç görünümü)
    ↓
Telegram (opsiyonel bildirim)
```

---

## TEKNİK BORÇ

| # | Görev | Risk | Öncelik |
|---|-------|------|---------|
| #20-25 | Hetzner deploy (SSH bloker çözümü zorunlu) | 🔴 | P0 |
| T-UPS-V2-FULL | JSONB tam göçü (`ekstra_ozellikler` migration + 3 servis) | 🔴 | P1 |
| T-FAV-01 | `ilan_favorileri.user_id` vs pivot `kisi_id` FK uyumsuzluğu | 🟠 | P2 |
| FIX-06 | `AIController` CRM methods → `AICrmGatewayService` | 🟠 | P2 |
| FIX-07 | `PropertyHubController` AI methods → `PropertyAIService` | 🟠 | P2 |
| FIX-11 | `PropertyHubController` (28 method) → 4 controller | 🟠 | P3 |
| FIX-12 | `DecisionEngineController` (27 method) → 4 controller | 🟠 | P3 |
| #16 | FinanceProcessor OpenAI bağımlılığı kaldır | 🟡 | P3 |
| #17 | PortfolioProcessor whereBetween → Haversine | 🟡 | P3 |
| #18 | `yayin_durumu` 6 farklı string standardizasyonu | 🟡 | P3 |
| #26 | `bekci:pattern:sync` komutu | 🟡 | P4 |

---

## SPRINT 5+ — Mimari Olgunluk

Risk: VERY HIGH. Her iş ayrı ADR + tam test coverage şart.

### Dual Sistem Konsolidasyonu
- [ ] CRM V1 (`Musteri`) + V2 (`CRM\*`) → tek model (ayrı migration sprint)
- [ ] Finance modül çakışması: `FinansalIslem` vs `Finance\*`
- [ ] Yanlış namespace servisler: 4 Advisor + 2 CRM → doğru konuma taşı

### Template SSOT + Namespace Migration
- [ ] FIX-17: 11 controller → tek template hiyerarşisi (ADR gerekli)
- [ ] FIX-18: 14 controller Api/Admin namespace migration

---

## Kritik Kurallar (Asla Dokunma)

| Sınıf | Kural |
|-------|-------|
| [`IlanCrudService::store()`](../app/Services/Ilan/IlanCrudService.php) | DOKUNMA — tek write authority |
| [`StoreIlanRequest`](../app/Http/Requests/Ilan/StoreIlanRequest.php) | YÜKSEK RİSK |
| [`ListingStateMachine`](../app/StateMachines/ListingStateMachine.php) | BYPASS YASAK |
| `FeatureTemplateResolver` (Ups\) | SSOT koru |
| `.sab/authority.json` | Agent değiştiremez |

---

## Deploy Checklist (#20-25)

```bash
# Sunucu: Hetzner CX33 — 157.180.116.63
ssh ubuntu@157.180.116.63
# #20: PHP 8.2 + Nginx + MySQL + Redis + Supervisor
# #21: rsync ile Laravel gönder
# #22: composer install --no-dev && php artisan migrate --force && php artisan config:cache
# #23: Nginx config + Cloudflare Tunnel (panel subdomain)
# #24: supervisor + php artisan horizon:start
# #25: php artisan telegram:set-webhook
```

**N8N:** https://n8n.yalihanemlak.com.tr ✅ aktif
**Panel:** https://panel.yalihanemlak.com.tr (deploy bekliyor)

---

*Son güncelleme: 2026-06-16 | Bekçi herzaman uyanık.*
