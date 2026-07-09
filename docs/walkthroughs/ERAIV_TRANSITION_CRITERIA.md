# ERA IV Geçiş Kriterleri

**Document:** ERA IV Transition Criteria
**Date:** 2026-07-09
**ERA III Status:** ✅ RC1 (`v6.5-era-iii-rc1`)
**ERA IV Hedefi:** Production-ready + Real User Validation

---

## Geçiş Kriterleri Matrisi

| # | Kriter | Hedef | Durum | Kanıt |
|---|---------|-------|--------|-------|
| G1 | P0 Blocker kapatıldı | 0 P0 açık | ✅ Kapatıldı | `83bc43f8` — PropertyTemplateGeneratorService DB refactor |
| G2 | RC1 saha testi başarılı | E2E senaryo çalıştı | ⏳ | Test edilmedi |
| G3 | Bir gerçek ilan baştan sona hazırlandı | Airbnb/Sahibinden/Hepsiemlak payload üretildi | ⏳ | Kanıt yok |
| G4 | Manual süre ölçümü tamamlandı | Ölçüm raporu dolduruldu | ⏳ | Şablon hazır |
| G5 | Sprint 6.6 Execution Layer tamamlandı | Manuel export + audit trail | ⏳ | Başlanmadı |

**ERA IV geçişi için: Tüm 5 kriter yeşil olmalı.**

---

## G1 — P0 Blocker Kapatıldı ✅

**Durum:** ✅ TAMAMLANDI

**Fix:** `83bc43f8` — PropertyTemplateGeneratorService DB refactor
- JSON dosyası yerine `UpsTemplate::aktif()` DB modelinden okuyor
- `ups_templates` tablosu boş olsa bile RuntimeException fırlatmaz
- Feature flag `false` → LegacyGeneratorGuard zaten çağrıları bloke ediyor

---

## G2 — RC1 Saha Testi Başarılı ⏳

**Durum:** ⏳ BEKLEYEN

**Senaryo:**
```
Ayhan'ın Bodrum portföyünden bir villa seç
↓
"Yeni İlan" → Workspace oluştur
↓
Konum: Gündoğan seç (Location Intelligence)
↓
8 fotoğraf yükle (Media Intelligence)
↓
AI Vision analizi çalıştır
↓
Publishing Intelligence → 3 kanal payload'ı üret
↓
Dashboard'da görüntüle
```

**Başarı Kriteri:** Tüm adımlar kesintisiz tamamlanır.

**Test Eden:** Gerçek kullanıcı (Ayhan veya emlak danışmanı)

**Ölçülecek:**
- Toplam süre
- Manuel müdahale noktaları
- AI hızlandırma noktaları
- Bekleme/bekletme noktaları

---

## G3 — Gerçek İlan Baştan Sona Hazırlandı ⏳

**Durum:** ⏳ BEKLEYEN

**Kanıt Formatı:**
```
İlan ID: _________
İlan Başlığı: _________
Oluşturulma tarihi: _________

ADIM                          | SÜRE    | OTOMATİK?
Workspace oluşturma           | __ sn    | Evet/Hayır
Konum girişi                  | __ sn    | Evet/Hayır
Fotoğraf yükleme (8 adet)    | __ sn    | Kısmen
Oda tespiti (AI Vision)        | __ sn    | Evet (AI)
Başlık/Açıklama (AI Vision)  | __ sn    | Evet (AI)
Payload üretimi (3 kanal)    | __ sn    | Evet
Toplam                        | __ dk    |

Manüel giriş: ___
AI ile otomatik: ___
Otomasyon oranı: ___%
```

---

## G4 — Manual Süre Ölçümü Tamamlandı ⏳

**Durum:** ⏳ BEKLEYEN

**Şablon:** `docs/walkthroughs/ERAIII_RC1_RELEASE.md` içinde

**Gerekli:**
1. Eski (manuel) süre tahmini
2. Yeni (otomatik) süre gerçek ölçümü
3. Fark hesabı
4. Business Automation Index hesabı

---

## G5 — Sprint 6.6 Execution Layer Tamamlandı ⏳

**Durum:** ⏳ BAŞLANMADI

**Kapsam:**
- Manuel export (3 kanal)
- Execution log + audit trail
- Replay mekanizması
- Dashboard panel (channel readiness)

**Hedef:** Sprint 6.6 sonunda tüm feature testler yeşil

---

## ERA IV Hakkında

ERA IV başladığında:

1. **Omurga tamam** — Tüm AI pipeline'lar sertifikalı
2. **Execution hazır** — Manuel export aktif
3. **Kullanıcı doğrulandı** — Gerçek operasyonda test edilmiş
4. **KPI kanıtlandı** — İş süresi ölçümü raporlanmış

ERA IV hedefi: **Günlük operasyonların tam otomasyonu**

---

## Sonraki Adım

- [ ] G2: Ayhan ile E2E saha testi planla
- [ ] G3: Bir villa ile tam E2E senaryo çalıştır
- [ ] G4: Süre ölçüm raporunu doldur
- [ ] G5: Sprint 6.6 başlat
