# YALIHAN OS — Stabilizasyon ve Güvenilirlik Yol Haritası

> **Versiyon:** 1.0
> **Tarih:** 2026-09-09
> **Kaynak:** `known_issues.md` + `tech_debt_registry.md` + `feature_matrix.md` + `evidence_index.md`
> **Durum:** AKTİF — Onay bekliyor

---

## 📋 YOL HARİTASI ÖZETİ

```
┌─────────────────────────────────────────────────────────────────┐
│  BLOK 1 (Bu Sprint)         │  BLOK 2 (Sonraki)               │
│  Güvenilirlik Temeli        │  Özellik Stabilizasyonu         │
│  ─────────────────────      │  ──────────────────────         │
│  ① Tenant Veri Denetimi     │  ⑤ Konum JS Hatası Düzelt      │
│  ② Tenant Guard Merkezi     │  ⑥ İlan Akışı E2E Sertifikasyon │
│  ③ Ghost Field Temizliği    │  ⑦ Schema/Kod Uyumsuzluk        │
│  ④ Route & Controller Audit │  ⑧ Property Hub Sertifikasyon   │
├─────────────────────────────┴─────────────────────────────────┤
│  BLOK 3 (Gelecek)                                              │
│  Kontrollü Çıkış + Sprint 16                                   │
│  ────────────────────────────────────                          │
│  ⑨ Sprint 14 Kapat (kanıtla)   ⑬ Sonraki özellik planı        │
│  ⑩ Sprint 15 Kapat (kanıtla)   ⑭ Golden commit belirle        │
│  ⑪ RC adayı oluştur           ⑮ Production migration plan     │
│  ⑫ VPS sync + health check                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## BLOK 1 — Güvenilirlik Temeli (Bu Sprint)

### ① Tenant Veri Denetimi — En Yüksek Öncelik

**Neden:** Orphan kayıtlar üzerine kurulan her özellik yanlış veri üretir.

| Görev | Dosya/Komut | Kanıt Standardı | Durum |
|-------|------------|-----------------|-------|
| `tenant_id` NULL/0 kayıtlarını listele | `scripts/tools/tenant-orphan-audit.sh` (yazılacak) | Audit raporu | ⬜ |
| Sahiplik çıkarılabilir kayıtları işaretle | SQL + seed analizi | `quarantineandidates.csv` | ⬜ |
| Çıkarılamayanları ayrı tabloya taşı | Migration | Clone DB test → onay → prod | ⬜ |
| `tenant_id` backfill (sahipli) | `IlanCrudService` mutation | Test + prod doğrulama | ⬜ |

**Yapılmaması Gereken:** `tenant_id = 1` atayarak sorunu gizlemek.
**Ön Koşul:** Mevcut `tenant_backfill` worktree (`c669bcad`, `fe17dd5c`) ile koordinasyon.
**Kanıt:** Her adım için `.project-brain/EVIDENCE_INDEX.md`'ye kayıt.

---

### ② Tenant Guard Merkezi — Merkezi Yetkilendirme

**Neden:** `generateDescription` P0 açığı (2026-08-31) manuel controller düzeltmesi gerektirdi. Kalıcı çözüm: merkezi policy/guard.

| Görev | Dosya | Kanıt Standardı | Durum |
|-------|-------|-----------------|-------|
| `App\\Policies\\TenantResourcePolicy` oluştur | `app/Policies/` | Policy test: cross-tenant 404/403 | ⬜ |
| `authorizeTenantAccess($model, $user)` metodu | Policy içi | Mock test: tenant A → tenant B kayıt | ⬜ |
| Global middleware: `EnsureTenantContext` | `bootstrap/app.php` veya `Kernel.php` | Request test: null tenant → 403 | ⬜ |
| API controller'lara tek tek değil, policy tabanlı yetkilendirme | Controller refactor | Tüm API endpoint'leri policy üzerinden | ⬜ |

**Kapsam Dışı:** Web controller'lar (sonraki sprint'te).
**Mevcut Durum:** `SAAB 4.5` sertifikalı ama merkezi guard yok — her controller ayrı manuel kontrol.

---

### ③ Ghost Field Temizliği — Model/Schema Drift

**Kaynak:** `tech_debt_registry.md` — GF-001 ÷ GF-006

| ID | Model | Field | Eylem | Test |
|----|-------|-------|-------|------|
| GF-001 | `YayinTipi` | `adi` | `$fillable`'dan kaldır | Unit test |
| GF-002 | `Ilan` | `is_active` | `$fillable` + `$casts`'tan kaldır | Feature test |
| GF-003 | `Ozellik` | `aciklama` | Karar: DB'ye ekle veya kaldır | DB şema kontrolü |
| GF-004 | `Ozellik` | `veri_secenekleri` | Karar: DB'ye ekle veya kaldır | DB şema kontrolü |
| GF-005 | `FeaturePack` | `display_order` | Karar: DB'ye ekle veya kaldır | DB şema kontrolü |
| GF-006 | `Feature` | `deprecated_at` | `$casts`'tan kaldır | Unit test |

**Ön Koşul:** Her biri için DB'de gerçekten var mı kontrolü (`DESCRIBE table_name`).
**Karar Verilmemiş (GF-003/004/005):** `ilanlar` veya `ozellikler` tablosunda `aciklama`/`veri_secenekleri`/`display_order` kolonu olup olmadığı kontrol edilecek. Yoksa model'den kaldırılacak. Varsa korunacak.
**Kanıt:** `php artisan test --filter=GhostField` veya ilgili model testi.

---

### ④ Route & Controller Audit — Çift Route ve Ölü Kod

**Kaynak:** `known_issues.md` — *"Repository has a large and historically layered route/controller/service surface; route ownership and duplicate legacy paths need a dedicated drift audit"*

| Görev | Komut | Kanıt Standardı | Durum |
|-------|-------|-----------------|-------|
| Duplicate route tespiti | `./scripts/tools/antigravity-route-check.sh --duplicates` | Rapor: kaç duplicate, hangi dosyalarda | ⬜ |
| Hiçbir yerde kullanılmayan controller method'ları | `get_orphan_methods.php` | Fonksiyon listesi | ⬜ |
| Kaldırılacak controller'ları işaretle | Manual | Grep: route tanımlanmış mı? | ⬜ |
| `route:list` → `routes/admin.php` audit | `php artisan route:list --path=admin` | Admin route sayısı + kime ait | ⬜ |

**Yapılmaması Gereken:** İlk oturumda toptan silme. Her route ayrı doğrulanacak.
**Kanıt:** Audit raporu + commit bazlı kaldırma.

---

## BLOK 2 — Özellik Stabilizasyonu (Sonraki Sprint)

### ⑤ Konum JavaScript Hatası — Leaflet Load Order

**Kaynak:** `known_issues.md` — *"leaflet-draw.js `L is not defined`"*

| Adım | Dosya | Kanıt Standardı | Durum |
|------|-------|-----------------|-------|
| 1. Asset build order analizi | `vite.config.js`, `package.json` | Asset sırası dokümante | ⬜ |
| 2. `leaflet-draw.js` load sequence | `resources/js/app.js` | `L` global tanımlı | ⬜ |
| 3. Edit blade'de L eksikliği | `resources/views/admin/ilanlar/edit.blade.php` | Harita render | ⬜ |
| 4. Playwright test | `tests/e2e/golden-thread-wizard.spec.ts` | TC-GT-07: edit screen location save | ⬜ |

**Kök Neden:** Muhtemelen üç sorunun birleşimi — Vite build order, npm package load, Blade component sırası.
**Test Hedefi:** Playwright ile Step 4 konum seçimi → save → veritabanında `lat`/`lng` doğrulama.

---

### ⑥ İlan Akışı E2E Sertifikasyonu — 6/6 → 10/10

**Kaynak:** `known_issues.md` — *"TC-GT-06 submit: HTTP 422 — fixture eksik alanlar"*
**Kanıt:** `evidence_index.md` — `4f195599` commit, TC-GT-06 **6/6 PASS**

| Test | Kapsam | Mevcut Durum | Hedef |
|------|--------|-------------|-------|
| TC-GT-01..06 | Yazlık Kiralık wizard | 6/6 PASS ✅ | 6/6 PASS |
| TC-GT-07 | Edit screen → konum kaydet | ⬜ | 1/1 PASS |
| TC-GT-08 | Draft → publish akışı | ⬜ | 1/1 PASS |
| TC-GT-09 | Arsa Kiralık wizard | 4/4 PASS ✅ | 4/4 PASS |
| TC-GT-10 | İşyeri Devren wizard | 4/4 PASS ✅ | 4/4 PASS |

**Eksik:** TC-GT-07/08 — fixture tamamlama + Playwright testi.
**Hedef:** 12/12 E2E PASS.

---

### ⑦ Schema/Kod Uyumsuzluk — Field ve Kolon Eşleştirme

| Alan | Model/Service | DB Kolon | Uyumsuzluk |
|------|--------------|----------|-----------|
| `kategori_id` | `IlanCrudService` | `ilanlar.kategori_id` | Tümü kontrol edilecek |
| `yayin_tipi_id` | `IlanCrudService` | `ilanlar.yayin_tipi_id` | Tümü kontrol edilecek |
| `display_order` | `FeaturePack` | `feature_packs.display_order` | DB'de var mı? (GF-005) |
| `adi` | `YayinTipi` | `yayin_tipleri.adi` | Varlık kontrolü |
| `deprecated_at` | `Feature` | `features.deprecated_at` | DB'de var mı? (GF-006) |

**Komut:**
```bash
php artisan tinker --execute="DB::select('DESCRIBE ilanlar')"
php artisan tinker --execute="DB::select('DESCRIBE feature_packs')"
php artisan tinker --execute="DB::select('DESCRIBE features')"
```

---

### ⑧ Property Hub Sertifikasyonu

**Kaynak:** `feature_matrix.md` — *"36 feature records listed — 30 active / 0 passive counters inconsistent"*

| Görev | Durum | Kanıt |
|-------|-------|-------|
| Feature counter uyuşmazlığı analizi | ⬜ | Admin dashboard'da sayılar tutarlı |
| Turistik Tesisler `0 Alan` sorunu | ⬜ | `kategori_id=5` feature assignment kontrolü |
| Template Manager boş şablonlar | ⬜ | `kategori_id=0` URL düzeltme |
| Dependency Rules yüzey kontrolü | ⬜ | 0 kural — kasıtlı mı? |

---

## BLOK 3 — Kontrollü Çıkış + Sprint 15/16 Kapatma

### ⑨ Sprint 14 Kapatma — Property Command Center

| Madde | Kanıt | Durum |
|-------|-------|-------|
| Dashboard HTTP 200 | Browser fetch | ⬜ |
| İlan listesi render | Browser screenshot | ⬜ |
| Filtreleme çalışıyor | Playwright test | ⬜ |
| Tenant izolasyonu | Test | 30/30 PASS ✅ |

---

### ⑩ Sprint 15 Kapatma — Action Center

| Phase | Durum | Kanıt |
|-------|-------|-------|
| Phase 1: Event→Gorev listeners | ✅ Tamam | 11 listener wired |
| Phase 2: Auto-assignment + API | ✅ Tamam | `114802bd` |
| Phase 3: action_evidence | ✅ Tamam | `d121b3da` |

**Eksik:** Action Center → gerçek tenant altında görev üretme + kanıt toplama.

---

### ⑪ RC Adayı Oluştur

| Adım | Komut | Kanıt |
|------|-------|-------|
| Golden commit seç | `git log --oneline -20` | Commit hash kaydı |
| Changelog üret | `git log --format` | `CHANGELOG.md` güncelleme |
| Migration planı | `php artisan migrate:status` | Hangi migration'lar çalışacak |
| Geri dönüş planı | Döküman | `docs/production/ROLLBACK.md` |

---

### ⑫ VPS Sync + Health Check

| Adım | Komut | Hedef |
|------|-------|-------|
| Son commit'leri çek | `git pull` | `157.180.116.63` |
| Health kontrol | `php artisan bekci:health` | %70+ hedef |
| Migration çalıştır | `php artisan migrate` | Schema senkronizasyonu |
| Route listesi | `php artisan route:list` | 195+ route aktif |

---

### ⑬ Sonraki Özellik Planı

| Sprint | İçerik | Ön Koşul |
|--------|--------|---------|
| Sprint 16 | Knowledge Core AI | Blok 1+2+3 tamam |
| Sprint 17 | Hermes bağlantıları | Action Center sertifikalı |
| Sprint 18 | AI Worker zinciri | Tenant guard merkezi |

---

## 📊 Öncelik Matrisi

```
             ETKİ (Sisteme etkisi)
               YÜKSEK        DÜŞÜK
         ┌──────────────┬──────────────┐
  ACİL   │ ① Veri Sahipliği │ ④ Route Audit  │
         │ ② Tenant Guard   │              │
         ├──────────────┼──────────────┤
  GEÇİCİ │ ⑤ Konum JS Hata │ ⑥ E2E Test   │
         │ ③ Ghost Field   │ ⑦ Schema Uy. │
         └──────────────┴──────────────┘
```

---

## 📅 Tahmini Süre

| Paket | Oturum (tahmini) | Toplam |
|-------|-----------------|--------|
| ① Tenant Veri Denetimi | 2-3 | |
| ② Tenant Guard Merkezi | 2 | |
| ③ Ghost Field Temizliği | 1 | |
| ④ Route Audit | 1-2 | |
| ⑤ Konum JS Hatası | 1-2 | |
| ⑥ E2E Sertifikasyon | 1-2 | |
| ⑦ Schema/Kod Uyumsuzluk | 1 | |
| ⑧ Property Hub Sertifikasyonu | 1-2 | |
| ⑨-⑫ Kontrollü Çıkış | 2 | |
| **TOPLAM** | **12-18 oturum** | ~3-5 hafta |

---

## ✅ Kapatma Kriterleri

Her blok için:

1. **Test:** `vendor/bin/phpunit` → yeşil
2. **E2E:** Playwright → ilgili TC PASS
3. **Browser:** HTTP 200 (authenticated)
4. **Kanıt:** `EVIDENCE_INDEX.md`'ye kayıt
5. **Dokümantasyon:** `BEKCI_CHANGELOG.md` güncelleme

---

## 🔒 Bloke Edici Koşullar

| Koşul | Kim Karar Verir | Durum |
|-------|----------------|-------|
| Veri backfill stratejisi | Operator onayı | ⬜ Bekliyor |
| Ghost field karar (GF-003/004/005) | Tech lead onayı | ⬜ Bekliyor |
| Migration çalıştırma onayı (prod) | Operator onayı | ⬜ Bekliyor |
| RC adayı onayı | Operator onayı | ⬜ Bekliyor |

---

*Bu belge, `known_issues.md`, `tech_debt_registry.md`, `feature_matrix.md` ve `evidence_index.md`'deki somut kanıtlara dayanmaktadır. Spekülatif maddeler içermez.*
