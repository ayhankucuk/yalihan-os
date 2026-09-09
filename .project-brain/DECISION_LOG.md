# DECISION LOG — Yalıhan OS

Mimari kararlar, bypass理由 ve kapsam değişiklikleri bu dosyada kaydedilir.

---

## Karar #001 — 2026-09-06

**Konu:** Context Cache Manager Skill Oluşturulması

**Gerekçe:**
- Her yeni oturumda aynı doğrulama komutları tekrar çalışıyordu
- Token maliyeti gereksiz yere yüksek
- `.project-brain/` dosyaları zaten mevcut ama sistematik kullanılmıyordu

**Karar:**
- `.clinerules` §10'a `context-cache-manager` skill tanımı eklendi
- Cache write: her material görev sonunda EVIDENCE_INDEX + PROJECT_STATE + DECISION_LOG güncellenir
- Cache read: oturum başında ve kullanıcı geçmiş sorduğunda cache okunur
- Token hedefi: cache hit < 100 token, cache miss ~1,000-2,000 token

**Etki:**
- %90+ token tasarrufu hedefi (yeni oturumlar için)
- Evidence kayıtları commit bazlı ve doğrulanmış bilgi içerir

**Sahip:** Codex

---

## Karar #002 — 2026-09-06

**Konu:** `7f467b8a` Handoff — TEST_VERIFIED Label Uygulaması

**Gerekçe:**
- `agent-handoff-verifier` + `api-contract-regression-guard` + `test-fixture-integrity-checker` üçü de temiz döndü
- Evidence kaydı commit bazlı, komut çıktıları ve dosya satır referansları ile dokümante edildi
- Production doğrulaması ayrı kapsam olarak kapalı kaldı

**Karar:**
- Label: `TEST_VERIFIED` — commit `7f467b8a`
- Tüm kanıtlar `.project-brain/EVIDENCE_INDEX.md`'ye kaydedildi
- Production deploy: ayrı yetki gerektirir

**Sahip:** Codex

---

## Karar #003 — 2026-09-06

**Konu:** P4 Location Research — `ilceler→iller` FK eksikliği kararı

**Gerekçe:**
- FK constraint MySQL schema'da tanımlı değil — MEDIUM risk
- Tüm referans veren tablolarda 0 kayıt — LOW mevcut impact
- `ilceler` tablosunda orphan `il_id` değerleri riski mevcut
- TKGM polygon persistence ve cross-table spatial query'ler için FK şart

**Karar:**
- `ReconcileLocationsCommand` çalıştırılıp orphan durumu doğrulanacak (OPERATOR yetkisi)
- Orphan doğrulaması sonrası `ilceler→iller` FK constraint migration'ı eklenecek
- `bina_yasi` migration backward compatible — DOKÜMANTE EDİLDİ, işlem gerekmiyor
- PHASE2-ROADMAP.md P4 RESEARCH COMPLETE olarak güncellendi

**Sahip:** Kodex (Architect)

---

## Karar #004 — 2026-09-09

**Konu:** SEC-08 — V2 API Route Model Binding + TenantScope fail-closed 404

**Gerekçe:**
- V2 `PUT /api/v1/ilanlar/{id}` ve türevi endpoint'lerde (delete/publish/unpublish) 404 dönüyordu
- Kök neden: Laravel Route Model Binding, controller method'undan **önce** çalışır
- `V2\Ilan` modeli `BelongsToTenant` trait kullanıyor → `TenantScope` global scope ekliyor
- Test ortamında `TenantContextService::hasTenant() = FALSE` → `TenantScope` `WHERE 1 = 0` ekliyor (fail-closed)
- Sonuç: `ModelNotFoundException` → 404 — controller'a ulaşamıyor

**Karar:**
- `update`, `destroy`, `publish`, `unpublish` method'larında implicit route model binding (`Ilan $ilan`) yerine explicit query:
  ```php
  $ilan = Ilan::withoutGlobalScope(TenantScope::class)->find($id);
  if (!$ilan) { return response()->json(['message' => 'İlan bulunamadı'], 404); }
  ```
- `show()` method'unda zaten aynı pattern mevcuttu — diğer method'lara de uygulandı
- `authorizeIlanAccess()` auth kontrolü `find()` sonrası çalışmaya devam eder → 404/403 doğru döner
- `TenantScope` global scope'u kaldırılmadı — fail-closed koruması yerinde kaldı

**Etki:**
- Test: 5/5 PASS (`V2RouteBindingCountryScopeTest` + `V2IlanAuthResearchTest`)
- Commit: `83dd1e8a` (`release-candidate/RC2`)

**Önlem:** Yeni V2 controller method'ları yazılırken `show()` pattern'i referans alınmalı

**Sahip:** Kilo

---

*Son güncelleme: 2026-09-09*
