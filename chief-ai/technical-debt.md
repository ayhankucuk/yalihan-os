# Technical Debt

> Chief AI — Teknik borç envanteri ve hesaplaması
> Son güncelleme: 2026-08-30 (Reconciliation — docs/known-debt.md ile karşılaştırıldı)

---

## Borç Hesaplama Sistemi

```
Puan = Etki × Görünürlük × Çözüm Zorluğu

Etki:
  1 = İzolasyon — sadece tek modülü etkiler
  3 = Sistemik — birden fazla modülü etkiler
  5 = Kritik — üretim riski var

Görünürlük:
  1 = Gizli — test covera yansımıyor
  3 = Orta — uyarı veriyor ama çalışıyor
  5 = Açık — hata veya warning veriyor

Çözüm Zorluğu:
  1 = Basit — 1-2 satır düzeltme
  3 = Orta — 1-2 dosya, test gerekli
  5 = Karmaşık — çok dosya, migration, test, deploy

Toplam Puan:
  1-10  🟢 Takip et
  11-25 🟡 Planla (bu sprint sonra)
  26-50 🟠 Acil (önümüzdeki sprint)
  51+   🔴 Bu borç Sprint'i durdurur
```

---

## Aktif Teknik Borç Envanteri

| ID | Borç | Puan | Etki Alanı | Çözüm | Sprint |
|----|------|------|-----------|-------|--------|
| TD-01 | 301 fail test (2615 passed, 3010 total, 13740 assertions, 1626s) — tam suite 2026-08-30'da çalıştırıldı | 5×5×3=**75** | Test suite | Fail testleri kategorize et → öncelik matrisi → düzelt | Sprint 3.x 🔴 |
| TD-02 | Naming Authority 175 ihlal | 4×4×3=**48** | Governance | context7-ignore ya da düzelt | Sprint 3.1 🟠 |
| TD-03 | SSH bloker (deploy) | 5×5×5=**125** | DevOps | İnsan müdahalesi | Sprint 4 🔴 |
| TD-04 | JSONB göçü — Phase 1 (write path) ✅ ÇÖZÜLDÜ, Phase 2 (read path) AÇIK | 3×3×3=**27** (düşürüldü) | Database | Reader servisleri JSONB okuyacak şekilde güncellenecek | Sprint 4 🟡 |
| TD-05 | Controller büyüklüğü (28+ method) | 3×3×4=**36** | Backend | Refactor | Sprint 5 🟡 |
| TD-06 | AI workspace otomasyon eksik | 2×3×2=**12** | Chief AI | chief-ai/ aktivasyonu | Sprint 6 🟢 |
| TD-07 | CI pre-existing gate failures | 2×4×2=**16** | CI/CD | İzleme + baseline | Sprint 5 🟡 |
| TD-08 | Legacy naming (is_active, status, type) | 3×3×3=**27** | Domain | Naming standard | Sprint 3.1 🟡 |
| TD-09 | MCP entegrasyonu eksik testi | 2×3×2=**12** | MCP | Test senaryosu | Sprint 6 🟢 |
| TD-10 | DriveWorkspaceService Context7 'type' usage (false positive) | 1×2×1=**2** | Context7 Guard | `@sab-ignore` veya refactor | Backlog 🟢 |
| TD-11 | **Secret exposure** — APP_KEY, DB_PASSWORD, REDIS_PASSWORD git history + remote'da | 5×5×4=**100** 🔴 | Security | Secret rotation + history rewrite (BFG/filter-repo) | ACİL 🔴 |
| TD-12 | **Hermes Workforce test coverage** — 5 agent için unit test + chain integration test eksik | 4×3×3=**36** | Testing | Workforce zinciri için test paketi | Sprint 14/15 🟠 |

---

## Toplam Teknik Borç Skoru

```
╔══════════════════════════════════════════════════════╗
║  TOPLAM TEKNİK BORÇ PUANI                            ║
║                                                      ║
║  TD-01:  75 █████████████████████████  🔴 VERIFIED (301 fail)
║  TD-02:  48 ██████████                               ║
║  TD-03: 125 ██████████████████████████████  🔴        ║
║  TD-04:  27 ██████ (64→27 düşürüldü — Phase 1 çözüldü)║
║  TD-05:  36 ████████                                 ║
║  TD-06:  12 ███                                       ║
║  TD-07:  16 ████                                      ║
║  TD-08:  27 ██████                                    ║
║  TD-09:  12 ███                                       ║
║  TD-10:   2 █                                         ║
║  TD-11: 100 ███████████████████████████  🔴 YENİ      ║
║  TD-12:  36 ████████                        YENİ      ║
║                                                      ║
║  TOPLAM:  516 / 1000                                 ║
║  KABUL EDİLEBİLİR LİMİT: 100                         ║
║  DURUM: 🔴 KABUL EDİLEMEZ — Sprint durdur             ║
║                                                      ║
║  Not: TD-01 doğrulandı — 301 fail (2615 passed).     ║
║  Puan 105→75 (etki 7→5: production riskü düşük,      ║
║  test altyapısı borcu).                              ║
╚══════════════════════════════════════════════════════╝
```

---

## Reconciliation Notları (2026-08-30)

### TD-01 — Fail Test Sayısı VERIFIED (2026-08-30)
- Eski değer: 89 fail test (2026-07-07)
- Güncel değer: 301 fail, 2615 passed, 3010 total, 13740 assertions, 1626s
- Tam suite 2026-08-30'da çalıştırıldı
- Puan 105→75 (etki 7→5: production runtime riskü düşük, borç test altyapısında)
- Öne çıkan fail kategorileri: SQLite schema gap (property_availability), UniqueConstraintViolation (iller.id), Workspace submission, Repository write hardening
- **Aksiyon:** Fail testleri kategorize et → öncelik matrisi → düzelt

### TD-04 — JSONB Göçü Puan Düşürüldü
- Eski değer: 64 puan (tam göç eksik)
- docs/known-debt.md #34'ye göre Phase 1 (write path) ✅ ÇÖZÜLDÜ (2026-06-24)
- Phase 2 (read path — CortexROIEngine vb.) hala açık
- Puan 64 → 27'ye düşürüldü (etki 4→3, görünürlük 4→3, zorluk 4→3)

### TD-11 — YENİ: Secret Exposure (2026-08-30)
- `.env.backup_before_session_fix` ve `.env.bak` dosyaları APP_KEY (59 char), DB_PASSWORD (12 char), REDIS_PASSWORD içeriyor
- `43904ff` (Initial commit) ve `01f8b84` commit'lerinde eklendi
- Remote branch'lerde mevcut (`origin/main`, `origin/integration/era-v-phase2a-e01`)
- `git rm --cached` ile tracking'den çıkarıldı (commit `c746469`) ama history'de erişilebilir
- **Aksiyon:** Secret rotation zorunlu. History rewrite (BFG/filter-repo) opsiyonel.

### TD-12 — YENİ: Hermes Workforce Test Coverage (2026-08-30)
- Kaynak: audits/HERMES_DEEP_AUDIT_REPORT.md, docs/known-debt.md #39
- Runtime wiring çözülmüş (PropertyScoreAgent PSR-4, DriveAgent constructor, NotificationAgent event zinciri, PortfolioAgent dead code temiz)
- 5 workforce agent için unit test + chain integration test eksik
- **Aksiyon:** Workforce zinciri test paketi yazılmalı

---

## Chief AI Aksiyon Planı

### Acil (Secret rotation öncesi)

| ID | Aksiyon | Süre | Kim |
|----|---------|------|-----|
| TD-11 | Security owner ile secret exposure ve rotation kararı | ACİL | Security owner |
| TD-11 | APP_KEY rotation — şifreli alan etkisi + rollback planı | ACİL | Security owner |
| TD-11 | DB_PASSWORD + REDIS_PASSWORD rotation | ACİL | Security owner |
| TD-11 | History rewrite gerekip gerekmediğine karar ver | ACİL | Ekip kararı |

### Acil (Secret rotation sonrası, push öncesi)

| ID | Aksiyon | Süre | Kim |
|----|---------|------|-----|
| TD-01 | 301 fail testi kategorize et → öncelik matrisi çıkar | 1 saat | Chief AI |
| TD-08 | Naming Authority 27 puanlık borcu 48 puanla birleştir | 2 saat | Chief AI |
| TD-02 | 175 ihlal + 27 puan → Sprint 3.1 backlog'a ekle | 30 dk | Chief AI |

### Planlı (Sprint-sonu review)

| ID | Aksiyon | Süre | Kim |
|----|---------|------|-----|
| TD-01 | Fail test öncelik matris çıkar | 1 saat | Chief AI |
| TD-07 | CI pre-existing baseline drift izleme dashboard | 1 saat | Chief AI |
| TD-12 | Hermes Workforce test planı oluştur | 1 saat | Chief AI |

---

## Chief AI Notu

> Teknik borç toplamı 546 puan (TD-01 UNVERIFIED) — kabul edilemez seviyede.
> TD-11 (Secret exposure) ACİL — push yapılmadan önce rotation şart.
> TD-03 (SSH bloker) insan müdahalesi gerektiriyor.
> Chief AI borç hesaplaması yapar, çözüm üretir, agent'a atar.
> Chief AI kod yazmaz.
>
> Current truth etiketi: DOCUMENTED / RECONCILIATION_REQUIRED
