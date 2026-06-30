# KNOWLEDGE_BLUEPRINT.md
## YALIHAN PLATFORM v2.0 — Kurumsal Bilgi Mimarisi

> **Tarih:** 2026-06-28
> **Sürüm:** 1.0.0
> **Yazar:** Chief Knowledge Officer (CKO)
> **Durum:** STRATEJİK PLAN

---

## 1. MEVCUT DURUM DEĞERLENDİRMESİ

### 1.1 Sahip Olunan Varlıklar

| Varlık | Tip | Durum | Otomasyon |
|---------|-----|-------|-----------|
| `docs/SAB.md` | Anayasa | ✅ Üretim | Manuel checksum |
| `memory/PROJECT_BRAIN.md` | Kalıcı bellek | ✅ Aktif | Manuel |
| `memory/CHANGELOG_AGENT.md` | Değişiklik kaydı | ✅ Aktif | Yarı-otomatik |
| `memory/DECISIONS.md` | ADR depolama | ✅ Mevcut | Manuel |
| `memory/LEARNED_PATTERNS.md` | Örüntü kaydı | ✅ Aktif | Manuel |
| `docs/adr/` | 21 ADR | ✅ Belgeli | Manuel |
| `mcp-servers/notebooklm-mcp/` | AI araç | ✅ Kurulmuş | Manuel sync |
| `knowledge/` | Bilgi havuzu | Kısmi | Manuel |
| `scripts/ops/notebooklm-sync.sh` | Sync script | Var ama pasif | Çalışmıyor |
| Google Drive | Doküman yönetimi | **YOK** | — |
| Notion/Confluence | Kurumsal Wiki | **YOK** | — |
| ADR Pipeline | Karar otomasyonu | **YOK** | — |

### 1.2 Sahip Olunmayan Varlıklar

| Varlık | Öncelik | Etki |
|---------|---------|------|
| Google Drive yapısı | P0 | Bilgi dağınıklığı |
| NotebookLM otomatik sync | P0 | Bilgi güncelliği |
| Kurumsal bellek (corporate memory) | P1 | Kurumsal hafıza kaybı |
| Bilgi yaşam döngüsü politikası | P1 | Belgelerin ömrü belirsiz |
| Bilgi sahipliği (ownership) | P1 | Sorumluluk belirsizliği |
| Sürümleme stratejisi | P2 | Belge versiyon karmaşası |
| Arşiv politikası | P2 | Gereksiz belge birikimi |

---

## 2. HEDEF DURUM — 4 KATMANLI BİLGİ MİMARİSİ

```
┌─────────────────────────────────────────────────────────────────────┐
│                    YALIHAN PLATFORM BİLGİ KATMANLARI               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  KATMAN 4 — KURUMSAL ARŞİV                                      │
│  Google Drive — Uzun Ömürlü Dokümanlar                           │
│  • Stratejik planlar, sözleşmeler, raporlar                     │
│  • Müşteri dökümanları, kanıtlar                                 │
│  • Yıllık bilanço, vergi, hukuk                                   │
│  • SLA, tedarikçi anlaşmaları                                     │
│                                                                     │
│  KATMAN 3 — PROJE BİLGİ HAVUZU                                   │
│  Google Drive — İş Birimi Dokümanları                             │
│  • Sprint retrospektifleri, feature spec'lar                      │
│  • API kontratları, entegrasyon belgeleri                        │
│  • UX wireframe, kullanıcı hikayeleri                             │
│  • Test stratejisi, kalite raporları                             │
│                                                                     │
│  KATMAN 2 — TEKNİK BİLGİ                                        │
│  Repository + NotebookLM — Kod ve Mimarî                          │
│  • ADR'ler (docs/adr/)                                           │
│  • NotebookLM notebook'ları (AI okunabilir)                      │
│  • Sistem mimarisi, CQRS diyagramları                            │
│  • Bekçi kuralları, SAB anayasası                                │
│                                                                     │
│  KATMAN 1 — OPERASYONEL HAFIZA                                   │
│  memory/ — Agent Oturum Belleği                                 │
│  • PROJECT_BRAIN.md (kalıcı metrikler)                          │
│  • CHANGELOG_AGENT.md (agent değişiklikleri)                     │
│  • SESSION_NOTES.md (oturum notları)                             │
│  • LEARNED_PATTERNS.md (öğrenilen kalıplar)                     │
│  • DECISIONS.md (mimari kararlar)                                │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. BİLGİ AKIŞLARI

### 3.1 Bilgi Üretim Noktaları

| Kaynak | Katman | Otomatik mi? | Çıkış |
|--------|--------|---------------|--------|
| Kod değişikliği | Katman 1 | Git hook | memory/CHANGELOG_AGENT.md |
| Agent oturumu | Katman 1 | Yarı-otomatik | memory/SESSION_NOTES.md |
| Mimari karar | Katman 2 | Manuel | docs/adr/, memory/DECISIONS.md |
| Sprint kapanışı | Katman 3 | Manuel | Drive/sprints/ |
| Müşteri dökümanı | Katman 3 | Manuel | Drive/clients/ |
| Hukuki sözleşme | Katman 4 | Manuel | Drive/legal/ |
| Sistem metriği | Katman 1 | Cron | memory/PROJECT_BRAIN.md |

### 3.2 Bilgi Tüketim Noktaları

| Tüketici | İhtiyaç | Kaynak |
|----------|---------|--------|
| AI Agent (yeni oturum) | Proje durumu, kurallar | Katman 1 + 2 |
| Developer (onboarding) | Mimari, kod standartları | Katman 2 + NotebookLM |
| Product Owner | Sprint durumu, KPI | Katman 3 |
| CFO | Finansal raporlar | Katman 4 |
| CTO | Sistem sağlığı, riskler | Katman 2 + Dashboard |

---

## 4. ADIM YOL HARİTASI

### Sprint 1 — Temel Altyapı (Hafta 1-2)

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| Google Drive hesabı oluştur | İnsan | 1 gün |
| Drive klasör hiyerarşisi kur | İnsan + Kilo | 2 gün |
| Drive API erişimi yapılandır | Kilo | 1 gün |
| NotebookLM auto-sync script | Kilo | 2 gün |
| Agent prompt güncelleme | Kilo | 1 gün |
| Test + doğrulama | Kilo | 1 gün |

### Sprint 2 — Bilgi Otomasyonu (Hafta 3-4)

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| Git hook → Drive upload | Kilo | 2 gün |
| ADR pipeline (otomatik) | Kilo | 2 gün |
| Sprint report → Drive sync | Kilo | 1 gün |
| Memory metrik → Dashboard | Kilo | 1 gün |
| Yaşam döngüsü policy | İnsan | 1 gün |
| Sahiplik matrisi | İnsan | 1 gün |
| Test + doğrulama | Kilo | 1 gün |

### Sprint 3 — Kurumsal Hafıza (Hafta 5-6)

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| NotebookLM corpus genişlet | Kilo | 2 gün |
| Google Drive backup (auto) | Kilo | 2 gün |
| Arşiv politikası uygula | Kilo + İnsan | 2 gün |
| Sürümleme stratejisi uygula | Kilo | 1 gün |
| Knowledge gap kapatma | Kilo | 2 gün |
| Eğitim dokümanı hazırla | İnsan | 1 gün |

---

## 5. BİLGİ SAHİPLİĞİ

| Bilgi Tipi | Birincil Sahip | Yedek Sahip | Gözden Geçirme |
|------------|----------------|-------------|-----------------|
| SAB anayasası | CTO | Chief AI | Her sprint |
| ADR'ler | Architect | CTO | Gerektiğinde |
| memory/* | Chief AI | Tüm agent'lar | Oturum sonu |
| NotebookLM | AI Coordinator | CTO | Aylık |
| Drive/Katman3 | Product Owner | Scrum Master | Sprint sonu |
| Drive/Katman4 | CFO | Hukuk | Çeyreklik |
| Kod standartları | Architect | Senior Dev | 6 aylık |
| API kontratları | Tech Lead | Backend Agent | Değişiklikte |

---

## 6. BİLGİ YAŞAM DÖNGÜSÜ

```
DOĞUM ──► KULLANIM ──► BAKIM ──► GÖZDEN GEÇİRME ──► ARŞIV
   │          │           │            │                │
   ▼          ▼           ▼            ▼                ▼
 Katman1   Tüm        6 ayda     12 ayda         36 ay
 + Katman2 katmanlar   bir        bir              sonra
                             gereksiz ❌     → Drive/Archive
```

### Yaşam Döngüsü Kuralları

| Aşama | Tetikleyici | Eylem | Sorumlu |
|-------|-------------|--------|---------|
| **Doğum** | Dosya oluşturuldu | memory/ oturum kaydına ekle | Agent/İnsan |
| **Kullanım** | Referans edildi | Kullanım sayısı +1 | Otomatik |
| **Bakım** | 6 ay geçti | Gözden geçirme notu ekle | Sahip |
| **Gözden Geçirme** | 12 ay geçti | Hâlâ geçerli mi? | Sahip |
| **Arşiv** | 36 ay geçti | Drive/Archive taşı | CKO |

### Saklama Süreleri

| Bilgi Tipi | Saklama | Arşiv Konumu |
|------------|---------|--------------|
| Agent memory dosyaları | 6 ay | Drive/archive/ |
| ADR'ler | Süresiz | docs/adr/ |
| SAB anayasası | Süresiz | docs/SAB.md |
| Sprint raporları | 2 yıl | Drive/sprints/ |
| Müşteri dökümanları | 7 yıl | Drive/clients/ |
| Hukuki sözleşmeler | 10 yıl | Drive/legal/ |
| Kod | Git tarihçesi | Git |

---

## 7. OTURUM DOĞRULAMA KOMUTLARI

```bash
# Bilgi katmanı sağlığı
php artisan bekci:health --detailed

# SAB bütünlük kontrolü
php artisan sab:integrity-scan

# Memory dosyaları kontrolü
ls -la memory/

# ADR sayısı
find docs/adr -name "*.md" | wc -l

# Drive sync durumu (script çalışıyor mu?)
ps aux | grep notebooklm-sync
```

---

## 8. ÖLÇÜTLER VE HEDEFLER

| Ölçüt | Mevcut | 30 Gün Hedef | 90 Gün Hedef |
|--------|--------|--------------|--------------|
| Bilgi kayıp riski | Yüksek | Orta | Düşük |
| Drive yapılandırılmış mı? | Hayır | Evet | Evet |
| NotebookLM otomatik sync | Manuel | Otomatik | Otomatik |
| Bilgi sahipliği tanımlı mı? | Kısmi | Evet | Evet |
| Arşiv politikası var mı? | Hayır | Evet | Evet |
| ADR pipeline otomatik mi? | Manuel | Evet | Evet |

---

## 9. BAŞARI KRİTERLERİ

> Bu blueprint başarılı sayılır eğer:

1. **Google Drive** tamamen yapılandırılmış ve 4 katmanlı hiyerarşi kurulmuş
2. **NotebookLM** 0 manuel müdahaleyle güncel kalıyor
3. Her bilgi parçasının **bir sahibi** ve **bir gözden geçirme tarihi** var
4. **Yaşam döngüsü** otomatik olarak işliyor (doğum → arşiv)
5. Yeni bir developer **1 saat içinde** sistemin durumunu anlayabiliyor
6. Chief AI **kendi kendine** bilgi yönetebiliyor

---

## 10. ÇAPRAZ REFERANSLAR

| Doküman | İlişki |
|---------|--------|
| `KNOWLEDGE_BLUEPRINT.md` | Bu dosya — ana plan |
| `NOTEBOOKLM_STRUCTURE.md` | Katman 2 detayı |
| `DRIVE_STRUCTURE.md` | Katman 3-4 detayı |
| `CORPORATE_MEMORY.md` | Katman 1 detayı |
| `docs/SAB.md` | Bilgi kalitesi standardı |
| `memory/DECISIONS.md` | Mevcut kararlar deposu |
| `docs/adr/README.md` | ADR yönetim süreci |
| `memory/CHIEF_AI_VISION.md` | Chief AI vizyonu |

---

*Bu blueprint Yalıhan Platform'un kurumsal bilgi yönetiminin stratejik haritasıdır. Chief Knowledge Officer tarafından yönetilir, Chief AI tarafından otomatize edilir.*
