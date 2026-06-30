# Technical Debt

> Chief AI — Teknik borç envanteri ve hesaplaması
> Son güncelleme: 2026-06-25

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
| TD-01 | 89 fail test | 7×5×3=**105** | Test suite | Düzelt + yeni test | Sprint 3.x 🔴 |
| TD-02 | Naming Authority 175 ihlal | 4×4×3=**48** | Governance | context7-ignore +ya da düzelt | Sprint 3.1 🟠 |
| TD-03 | SSH bloker (deploy) | 5×5×5=**125** | DevOps | İnsan müdahalesi | Sprint 4 🔴 |
| TD-04 | JSONB göçü eksikliği | 4×4×4=**64** | Database | Migration + servis güncelleme | Sprint 4 🟠 |
| TD-05 | Controller büyüklüğü (28+ method) | 3×3×4=**36** | Backend | Refactor | Sprint 5 🟡 |
| TD-06 | AI workspace otomasyon eksik | 2×3×2=**12** | Chief AI | chief-ai/ aktivasyonu | Sprint 6 🟢 |
| TD-07 | CI pre-existing gate failures | 2×4×2=**16** | CI/CD | İzleme + baseline | Sprint 5 🟡 |
| TD-08 | Legacy naming (is_active, status, type) | 3×3×3=**27** | Domain | Naming standard | Sprint 3.1 🟡 |
| TD-09 | MCP entegrasyonu eksik testi | 2×3×2=**12** | MCP | Test senaryosu | Sprint 6 🟢 |

---

## Toplam Teknik Borç Skoru

```
╔══════════════════════════════════════════════════════╗
║  TOPLAM TEKNİK BORÇ PUANI                       ║
║                                              ║
║  TD-01: 105 ████████████████████████████  🔴    ║
║  TD-02:  48 ██████████                       ║
║  TD-03: 125 ██████████████████████████████  🔴    ║
║  TD-04:  64 ██████████████                   ║
║  TD-05:  36 ████████                        ║
║  TD-06:  12 ███                             ║
║  TD-07:  16 ████                            ║
║  TD-08:  27 ██████                          ║
║  TD-09:  12 ███                             ║
║                                              ║
║  TOPLAM:  445 / 1000                         ║
║  KABUL EDİLEBİLİR LİMİT: 100                 ║
║  DURUM: 🔴 KABUL EDILEMEZ — Sprint durdur     ║
╚══════════════════════════════════════════════╝
```

---

## Chief AI Aksiyon Planı

### Acil (Bu Sprint — Sprint Durmayı Gerektirmez Ama öncelik)

| ID | Aksiyon | Süre | Kim |
|----|---------|------|-----|
| TD-08 | Naming Authority 27 puanlık borcu 48 puanla birleştir — 175 dosyayı kategorize et | 2 saat | Chief AI (Kilo) |
| TD-02 | 175 ihlal + 27 puan → Sprint 3.1 backlog'a ekle | 30 dakika | Chief AI |

### Planlı (Sprint-sonu review)

| ID | Aksiyon | Süre | Kim |
|----|---------|------|-----|
| TD-01 | 89 fail test öncelik matris çıkar | 1 saat | Chief AI |
| TD-07 | CI pre-existing baseline drift izleme dashboard | 1 saat | Chief AI |

---

## Chief AI Notu

> Teknik borç toplamı 445 puan — kabul edilemez seviyede.
> Ancak TD-03 (SSH bloker) insan müdahalesi gerektiriyor.
> Chief AI borç hesaplaması yapar, çözüm üretir, agent'a atar.
> Chief AI kod yazmaz.
