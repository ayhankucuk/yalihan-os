# SAB FIX SPRINT — Production Stabilization Report
Tarih: 2026-04-04 | 3 kritik fix uygulandı

## PHASE 1 — STORE ROUTE
ACTIVE: POST admin/ilanlar → admin.ilanlar.store → IlanCrudController@store
ROUTE DEDUP: FIX-3 ile admin/ilanlar.php:10 duplicate store comment yapıldı
ROUTE:LIST SONUCU: Showing [1] routes — TEK kayıt

## PHASE 2 — TEMPLATE ENGINE
ACTIVE RESOLVER: TemplateResolverInterface (DI bind)
DISABLED: UpsTemplateManagerController, UpsTemplateController (önceki sprintte purged)
WIZARD SOURCE: SINGLE — EffectiveWizardSchemaResolver

## PHASE 3 — GHOST AI
price-advisor.wizard.api   → EXISTS ✅
generate-ai-title          → EXISTS ✅
telemetry.feature-action   → MISMATCH (non-critical, save etkilemiyor)

## PHASE 4 — HARDCODED URLS
FOUND: 0 | REMAINING: 0 | Tümü route() helper kullanıyor

## PHASE 5 — PEOPLE MODEL
ilan_sahibi_id → kisiler ✅
danisman_id    → users ✅
ilgili_kisi_id → kisiler ✅
CONSISTENT: YES

## PHASE 6 — CORTEX BLOCKER
FIX-2: :disabled kaldırıldı → WARNING MODE
cortexScore=0  → Taslak Olarak Kaydet (her zaman tıklanabilir)
cortexScore<40 → Düşük Skorla Kaydet (sarı)
cortexScore>=40 → Yayınla (yeşil)

## PHASE 7 — WIZARD vs BACKEND
KRİTİK MISMATCH: junction_id → yayin_tipi_id
FIX-1: StoreIlanRequest@prepareForValidation içine bridge eklendi
'yayin_tipi_id' => $this->input('yayin_tipi_id', $this->input('junction_id'))

## APPLIED FIXES
FIX-1: app/Http/Requests/Admin/Ilan/StoreIlanRequest.php:322 — junction_id bridge
FIX-2: resources/views/admin/ilanlar/create-wizard.blade.php:342 — disabled kaldırıldı
FIX-3: routes/admin/ilanlar.php:10 — duplicate store comment yapıldı

## FINAL DECISION
CAN_START_REAL_LISTING: YES ✅

SAFE CONDITIONS:
- yayin_tipi (junction_id) seçilmiş olmalı
- ilan_sahibi_id seçilmiş olmalı
- baslik doldurulmuş olmalı
- ana_kategori_id + alt_kategori_id seçilmiş olmalı
- Cortex offline → Taslak olarak kayıt yapılabilir
- Yayına almak için completion_score=100 + fotoğraf gerekli

KNOWN RISKS (kalan):
1. telemetry.feature-action route adı tutarsızlığı (non-critical)
2. completion_score=100 publish guard (beklenen davranış)
3. edit flow — ayrı test edilmeli
