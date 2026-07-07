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

## 2. HEDEF DURUM — 6 KATMANLI BİLGİ MİMARİSİ (ADR-022)

> **Resmi Kaynak:** `docs/adr/2026-07-05-knowledge-platform-adoption.md`
> **Karar:** SAAB Board, 2026-07-05 tarihinde Knowledge Platform'u dördüncü katman olarak kabul etti.
> **Era:** ERA III → ERA IV Geçiş

```
┌──────────────────────────────────────────────────────────────────────────────┐
│              YALIHAN PLATFORM — 4 KATMAN (ERA III)                        │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  KATMAN 4 — Kurumsal Arşiv (Google Drive — 06-ARCHIVE)                    │
│  • Stratejik planlar, sözleşmeler, raporlar                                │
│  • Müşteri dökümanları (KVKK: 7 yıl)                                       │
│  • Yasal, finansal — uzun ömürlü                                           │
│                                                                              │
│  KATMAN 3 — Kurumsal Bilgi (Google Drive — 02+04+05)                      │
│  • 02-PRODUCT: Sprint, feature spec, UX, KPI                               │
│  • 04-OPERATIONS: SOP, şablonlar, onboarding                              │
│  • 05-KNOWLEDGE: AI okunabilir knowledge base                              │
│                                                                              │
│  KATMAN 2 — Teknik Bilgi (Repository + NotebookLM — 6 Notebook)            │
│  • docs/ — SAB, ADR, mimari dokümanlar                                     │
│  • NB-1..NB-6 — AI bilgi çıkarımı (Governance/Architecture/Product/       │
│                  Domain/Onboarding/Market Intelligence)                      │
│                                                                              │
│  KATMAN 1 — Operasyonel Hafıza (memory/)                                  │
│  • PROJECT_BRAIN.md — Kalıcı proje belleği                                  │
│  • CHANGELOG_AGENT.md — Agent değişiklikleri                               │
│  • SESSION_NOTES.md — Oturum notları                                       │
│  • LEARNED_PATTERNS.md — Öğrenilen kalıplar                                │
│  • DECISIONS.md — Mimari kararlar                                           │
│  • daily/ + weekly/ + sprint/ — Zaman-bazlı hafıza                        │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

DÖRDÜNCÜ KATMAN = Knowledge Platform
ERA IV hedefi: Autonomous Property Company
Bilgi = Platformun birinci sınıf vatandaşı (K-1)
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

## 4. ADIM YOL HARİTASI (ERA IV Sprint 5.x)

> **Resmi Kaynak:** `docs/SAB.md#🔒-BİLGİ-YÖNETİMİ-UYGULAMASI`
> **Sprint 5.x:** Knowledge Engine inşası ERA IV'ün temelidir.

### Sprint 5.1 — Corporate Memory Index

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| memory/daily/ klasörü + oturum sonu protokol | Kilo | 1 gün |
| memory/weekly/ formatı + Cuma otomatik | Kilo | 1 gün |
| memory/sprint/ klasörü + sprint kapanış protokol | Kilo | 1 gün |
| memory-cleanup.sh (30/12/12 saklama) | Kilo | 1 gün |
| chief-ai oturum başı/sonu protokolü kodla | Kilo | 2 gün |

### Sprint 5.2 — Knowledge Search

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| Google Drive hesabı oluştur | İnsan | 1 gün |
| Drive klasör hiyerarşisi kur | İnsan + Kilo | 2 gün |
| Drive API erişimi yapılandır | Kilo | 1 gün |
| NotebookLM auto-sync script | Kilo | 2 gün |
| Agent prompt güncelleme | Kilo | 1 gün |
| Test + doğrulama | Kilo | 1 gün |

### Sprint 5.3 — NotebookLM Sync Engine

| GÖREV | SORUMLU | SÜRE |
|-------|---------|-------|
| Git hook → Drive upload | Kilo | 2 gün |
| ADR pipeline (otomatik) | Kilo | 2 gün |
| Sprint report → Drive sync | Kilo | 1 gün |
| Memory metrik → Dashboard | Kilo | 1 gün |
| Yaşam döngüsü policy | İnsan | 1 gün |
| Sahiplik matrisi | İnsan | 1 gün |
| Test + doğrulama | Kilo | 1 gün |

### Sprint 5.4 — Drive Sync Engine

### Sprint 5.5 — Embedding & Vector Search

### Sprint 5.6 — Knowledge Verification

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
