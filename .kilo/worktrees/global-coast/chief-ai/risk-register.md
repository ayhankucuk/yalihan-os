# Risk Register

> Chief AI — Risk puanları ve durum takibi
> Son güncelleme: 2026-06-25

## Risk Skorlama Sistemi

```
Puanlama: 1-10
  1-3   🟢 Düşük      — izlenebilir
  4-6   🟡 Orta       — planlanmalı
  7-8   🟠 Yüksek    — acil müdahale
  9-10  🔴 Kritik    — sprint durdur
```

---

## Açık Riskler

| ID | Risk | Puan | Durum | Tetikleyen | Mitigasyon |
|----|------|------|--------|-----------|------------|
| R01 | Hetzner deploy SSH bloker | 🔴8 | AKTIF | known_hosts engeli | SSH config + pre-check |
| R02 | 89 fail test backlog | 🟠7 | AKTIF | Legacy debt | Sprint 3.x öncelik |
| R03 | Naming Authority 175 ihlal | 🟠6 | AKTIF | Manuel temizlik gerekiyor | Sprint 3.1 otomasyon |
| R04 | JSONB göçü riskli | 🟠6 | YAKLAŞAN | Schema drift | Test coverage şart |
| R05 | Agent confusion (MCP çalışıyor ama tool çağrılmıyor) | 🟡4 | AKTIF | IDE/MCP config eksik | Ayrı oturumda test |
| R06 | Context7 baseline 4500+ ihlal | 🟡5 | DEVAM EDEN | Legacy kod | Kademeli temizlik |
| R07 | CI/CD gate unstable (pre-existing) | 🟡4 | DEVAM EDEN | bootstrap/providers env(), @sab-fa-intentional | İzleniyor |
| **R08** | ~~Parse Error RepositoryInstrumentation.php:65~~ | ~~🔴8~~ | **✅ FALSE POSITIVE** | **Verification: `php -l` clean** | **CLOSED** |
| **R09** | ~~Missing route: admin.ilanlarim.index~~ | ~~🟠7~~ | **✅ FALSE POSITIVE** | **Verification: Route EXISTS** | **CLOSED** |
| **R10** | ~~Missing route: admin.ilanlar.create-wizard~~ | ~~🟠7~~ | **✅ FALSE POSITIVE** | **Verification: Route EXISTS** | **CLOSED** |

---

## Kapalı Riskler (Son 30 gün)

| ID | Risk | Puan | Kapandı | Çözüm |
|----|------|------|----------|--------|
| R-K01 | bekci:health %36.85 düşük | 🟠6 → 🟢1 | 2026-06-25 | MCP + KB aktivasyonu |
| R-K02 | MCP server çalışmıyor | 🟠5 → 🟢1 | 2026-06-25 | PID 9568 start |
| R-K03 | AI workspace yapısı eksik | 🟡4 → 🟢1 | 2026-06-25 | 7 dosya + 6 README oluşturuldu |
| R-K04 | Memory sistemi eksik | 🟡4 → 🟢1 | 2026-06-25 | 7 memory dosyası aktif |
| R-K05 | Parse error RepositoryInstrumentation.php | 🔴8 → 🟢1 | 2026-06-25 | `php -l` clean - FALSE POSITIVE |
| R-K06 | Route eksik: admin.ilanlarim.index | 🟠7 → 🟢1 | 2026-06-25 | Route mevcut - FALSE POSITIVE |
| R-K07 | Route eksik: admin.ilanlar.create-wizard | 🟠7 → 🟢1 | 2026-06-25 | Route mevcut - FALSE POSITIVE |

---

## Chief AI Aksiyonları

### Risk 7+ — Acil Müdahale Gerekli

| ID | Aksiyon | Kim | Son Tarih |
|----|---------|-----|-----------|
| R01 | SSH bloker analizi + çözüm planı | Chief AI | 2026-06-26 |
| R02 | Fail test öncelik sıralaması | Chief AI | 2026-06-26 |

### Risk 5-6 — Planlama Gerekli

| ID | Aksiyon | Kim | Son Tarih |
|----|---------|-----|-----------|
| R03 | Naming Authority cleanup otomasyon | Chief AI | 2026-06-27 |
| R04 | JSONB göçü test planı | Chief AI | Sprint 4 başında |

---

## Risk Trendleri

```
R01 ████████████░░░░░░░░ 8/10 — KRITIK
R02 ███████░░░░░░░░░░░░░ 7/10 — YUKSEK
R03 ██████░░░░░░░░░░░░░░ 6/10 — ORTA
R04 ██████░░░░░░░░░░░░░░ 6/10 — YAKLASAN
R05 ████░░░░░░░░░░░░░░░░ 4/10 — DUSUK
R06 █████░░░░░░░░░░░░░░░ 5/10 — ORTA
R07 ████░░░░░░░░░░░░░░░░ 4/10 — DUSUK
```

---

## Chief AI Notu

> Risk R01 (SSH bloker) Sprint 4'ün önündeki tek engel.
> Chief AI önce bunu çözüm planlamalı.
> Agent ataması: Bu görev bir AI agent'a değil, insan müdahalesi gerektirir.
> Chief AI sadece koordinasyon ve takip yapar.
