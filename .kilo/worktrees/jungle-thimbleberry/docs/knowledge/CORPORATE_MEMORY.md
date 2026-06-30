# CORPORATE_MEMORY.md
## YALIHAN PLATFORM v2.0 — Kurumsal Hafıza Mimarisi

> **Tarih:** 2026-06-28
> **Sürüm:** 1.0.0
> **Yazar:** Chief Knowledge Officer (CKO)
> **Mevcut Durum:** Katman 1 mevcut — Katman 2-4 eksik

---

## 1. MEVCUT DURUM ANALİZİ

### 1.1 Bugün Ne Var?

```
memory/
├── PROJECT_BRAIN.md       ✅ Kalıcı metrikler (son güncelleme: 2026-06-25)
├── CHANGELOG_AGENT.md    ✅ Agent değişiklikleri (2026-06-27)
├── SESSION_NOTES.md      ✅ Oturum notları (2026-06-27)
├── LEARNED_PATTERNS.md   ✅ 7 kalıp dokümante
├── DECISIONS.md          ✅ Mimari kararlar (2026-06-27)
├── WHERE_IS_WHAT.md      ✅ Hızlı referans haritası
├── HOW_IT_WORKS.md       ✅ Sistem nasıl çalışır
├── CHIEF_AI_VISION.md    ✅ Chief AI vizyonu
├── PROJECT_STATE.yaml     ✅ Makine-okunabilir durum
└── sessions/             ⚠️ Dizin var, içerik boş
```

**Toplam:** 10 dosya + 1 dizin

### 1.2 Eksik Olanlar

| Eksik | Öncelik | Etki |
|-------|---------|------|
| Zaman-bazlı memory (daily/, weekly/, monthly/) | P1 | Geçmiş bilgi kayboluyor |
| Sprint-bazlı memory (sprint/) | P1 | Sprint hafızası yok |
| Görev-bazlı memory (task-graph/) | P2 | Görev geçmişi siliniyor |
| chief/ çıktıları (chief/decisions.json) | P2 | Chief AI çıktısı kaydedilmiyor |
| Bilgi erişim sıklığı takibi | P2 | Hangi belleğin değerli olduğu bilinmiyor |

---

## 2. HEDEF: 5-KATMANLI KURUMSAL HAFIZA

```
┌─────────────────────────────────────────────────────────────────────┐
│                  KURUMSAL HAFIZA MİMARİSİ                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  KURUMSAL HAFIZA (Corporate Memory — Kalıcı)                         │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  KATMAN 5 — PROTOKOL HAFIZASI                                  │  │
│  │  Olay kayıtları, incident raporları, karar tutanakları           │  │
│  │  Dosya: memory/protocols/                                       │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  KURUMSAL HAFIZA (Corporate Memory — Yarı-Kalıcı)                   │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  KATMAN 4 — GÖREV HAFIZASI                                     │  │
│  │  Görev listesi, öncelikler, durumlar                           │  │
│  │  Dosya: memory/tasks/                                         │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  KURUMSAL HAFIZA (Corporate Memory — Dönemsel)                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  KATMAN 3 — ZAMAN BAZLI HAFIZA                                 │  │
│  │  Günlük, haftalık, aylık, sprint notları                      │  │
│  │  Dosya: memory/daily/, memory/weekly/, memory/sprint/         │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  OPERASYONEL HAFIZA (Operational Memory — Aktif)                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  KATMAN 2 — ÖNERİ HAFIZASI                                    │  │
│  │  Learned patterns, best practices, anti-patterns                  │  │
│  │  Dosya: memory/LEARNED_PATTERNS.md                            │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  OPERASYONEL HAFIZA (Operational Memory — Birincil)                  │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  KATMAN 1 — KURUCU HAFIZA                                    │  │
│  │  Proje kimliği, mimari, metrikler                             │  │
│  │  Dosya: memory/PROJECT_BRAIN.md                               │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. KATMAN DETAYLARI

### 3.1 Katman 1 — Kurucu Hafıza (PROJECT_BRAIN)

**Amaç:** Projenin DNA'sı — kimlik, metrikler, mimari

**Dosya:** `memory/PROJECT_BRAIN.md`

**İçerik Kuralları:**
- Proje kimliği (isim, stack, mimari)
- Doğrulanmış metrikler (her oturum başı güncellenir)
- Sistem durumu (SAB sürümü, health score)
- Aktif sprint bilgisi
- Açık riskler ve öncelikler
- AI Workspace yapısı
- Chief AI yönetim katmanı

**Güncelleme Tetikleyicileri:**
- Her oturum başı (zorunlu okuma + güncelleme)
- Her sprint kapanışında (metrik güncelleme)
- Her major kararda (karar + metrik güncelleme)

**Öncelik:** KRİTİK — Hiçbir oturum bu dosyasız başlamaz

---

### 3.2 Katman 2 — Öneri Hafızası (LEARNED_PATTERNS)

**Amaç:** Tekrarlanan hatalar ve düzeltmeler — öğrenilen dersler

**Dosya:** `memory/LEARNED_PATTERNS.md`

**İçerik Kuralları:**
- Kalıp ID (LP-001, LP-002, ...)
- Tarih ve oturum
- Sorun açıklaması
- Düzeltme çözümü
- Koruma mekanizması (nasıl tekrarlanmaz)
- İlgili context7 naming kuralları

**Güncelleme Tetikleyicileri:**
- Yeni hata bulunduğunda (hata analizi sonrası)
- Tekrarlanan hata düzeltildiğinde
- Yeni best practice keşfedildiğinde

**Öncelik:** KRİTİK — Yeni bir hata düzeltildiğinde mutlaka güncellenir

---

### 3.3 Katman 3 — Zaman Bazlı Hafıza

**Amaç:** Geçmiş oturumların izlenebilirliği

```
memory/
├── daily/                    ← Günlük notlar (30 gün saklanır)
│   ├── 2026-06-28.md
│   ├── 2026-06-27.md
│   └── ...
├── weekly/                   ← Haftalık özetler (12 hafta saklanır)
│   ├── 2026-W26.md
│   ├── 2026-W25.md
│   └── ...
├── monthly/                  ← Aylık raporlar (12 ay saklanır)
│   ├── 2026-06.md
│   ├── 2026-05.md
│   └── ...
├── sprint/                   ← Sprint bazlı notlar (süresiz saklanır)
│   ├── sprint-3.1.md
│   ├── sprint-3.2.md
│   └── sprint-3.3.md
└── quarterly/               ← Çeyreklik özetler (5 yıl saklanır)
    ├── 2026-Q1.md
    └── 2026-Q2.md
```

**daily/ Format:**

```markdown
# 2026-06-28 — Oturum 49

## Katılımcılar
- Kilo (agent)

## Özet
Bu oturumda yapılan iş...

## Kararlar
- [ ] Karar 1: ...

## Değişen Dosyalar
- `app/...` — Açıklama

## Metrics
- Health: 91.85%
- SAB: v24.2.0

## Next Steps
- [ ] Görev 1
```

**weekly/ Format:**

```markdown
# 2026-W26 — Hafta 24

## Özet
Bu hafta tamamlanan iş...

## Metrikler
- Tamamlanan görevler: 12
- Toplam oturum: 5
- Kod değişikliği: 450 satır

## Kararlar
- Sprint 3.4 tamamlandı
- AI Listing Assistant üretime hazır

## Haftalık MVP
- Kilo: Sprint 3.4.5 AI Description Pipeline

## Sonraki Hafta Hedefleri
- Sprint 3.5 başlat
```

---

### 3.4 Katman 4 — Görev Hafızası (TASK BRAIN)

**Amaç:** Görevlerin durumu, öncelikleri ve bağımlılıkları

```
memory/
├── tasks/
│   ├── TASK_MANIFEST.md      ← Tüm görevlerin listesi
│   ├── sprint-4-manifest.json ← Sprint 4 görev listesi
│   └── backlog.json          ← Gelecek görevler
```

**TASK_MANIFEST.md Format:**

```markdown
# Task Manifest — 2026-06-28

## Active Sprint: Sprint 4

| ID | Görev | Öncelik | Durum | Atanan | Sprint |
|----|-------|---------|-------|--------|--------|
| T-001 | Hetzner Deploy | P0 | blocked | — | Sprint 4 |
| T-002 | JSONB Migration | P0 | blocked | — | Sprint 4 |
| T-003 | Naming Authority Cleanup | P1 | active | Kilo | Sprint 3.1 |
```

**Güncelleme Tetikleyicileri:**
- Yeni görev oluşturulduğunda
- Görev durumu değiştiğinde
- Sprint başında/sonunda

---

### 3.5 Katman 5 — Protokol Hafızası

**Amaç:** Olay kayıtları, incident raporları, karar tutanakları

```
memory/
├── protocols/
│   ├── incidents/           ← Incident raporları
│   │   ├── INC-2026-0625-R08.md
│   │   └── INC-2026-0627-MCP-health.md
│   ├── decisions/           ← Mimari karar tutanakları
│   │   └── decisions-2026-06.json
│   └── retrospectives/     ← Sprint retrospektifleri
│       ├── sprint-3.4-retro.md
│       └── sprint-3.3-retro.md
```

**incident/ Format:**

```markdown
# Incident: MCP Health Config Issue

**ID:** INC-2026-0627-MCP-health
**Tarih:** 2026-06-27
**Risk:** P0
**Durum:** RESOLVED

## Özet
MCP health komutu yanlış path kullanıyordu.

## Kök Neden
MCP config dosyası farklı path'teydi.

## Düzeltme
Config path güncellendi.

## Lessons Learned
Agent oturumu başında config dosyaları kontrol edilmeli.

## Sonraki Adımlar
- Config doğrulama otomatize edilmeli
```

---

## 4. OTOMATİK GÜNCELLEME PROTOKOLÜ

### 4.1 Oturum Başı Protokolü

```bash
# Her oturum başında çalışır:
# 1. memory/PROJECT_BRAIN.md oku
# 2. memory/SESSION_NOTES.md güncelle
# 3. memory/PROJECT_STATE.yaml kontrol et
# 4. Todo listesi güncelle
```

**Chief AI Oturum Başı:**

```
1. READ: PROJECT_BRAIN.md, SESSION_NOTES.md (son 3 oturum)
2. ANALYZE: Bekci health, open risks, aktif sprint
3. DECIDE: Bu oturumun önceliği nedir?
4. PLAN: Hangi görev ele alınacak?
5. EXECUTE: Görevi tamamla
6. WRITE: SESSION_NOTES.md, CHANGELOG_AGENT.md, (gerekirse) PROJECT_BRAIN.md
```

### 4.2 Oturum Sonu Protokolü

```bash
# Her oturum sonunda çalışır:
# 1. memory/CHANGELOG_AGENT.md → Yeni kayıt ekle
# 2. memory/SESSION_NOTES.md → Oturum özeti ekle
# 3. memory/daily/YYYY-MM-DD.md → Günlük not ekle
# 4. memory/PROJECT_STATE.yaml → Metrikleri güncelle
# 5. memory/tasks/ → Görev durumlarını güncelle
```

### 4.3 Haftalık Protokol (Cuma)

```bash
# Her Cuma otomatik çalışır:
# 1. memory/daily/ klasöründeki son 5 günü özetle
# 2. memory/weekly/YYYY-WXX.md oluştur
# 3. memory/PROJECT_STATE.yaml haftalık metriklerini güncelle
# 4. chief-ai/ haftalık rapor oluştur
```

### 4.4 Sprint Sonu Protokolü

```bash
# Her sprint kapanışında çalışır:
# 1. memory/sprint/SPRINT-XX.md → Sprint özeti oluştur
# 2. memory/protocols/retrospectives/ → Retrospektif ekle
# 3. memory/CHANGELOG_AGENT.md → Sprint kaydı ekle
# 4. docs/adr/ → Yeni kararları kaydet
# 5. Drive → 02-PRODUCT/SPRINTS/ klasörüne sync et
```

---

## 5. HAFIZA OKUMA/YAZMA MATRİSİ

| Dosya | Oturum Başı | Oturum İçi | Oturum Sonu | Günlük | Haftalık | Sprint |
|-------|------------|------------|------------|--------|----------|--------|
| `PROJECT_BRAIN.md` | R+W | R | R | R | R | R+W |
| `SESSION_NOTES.md` | R | R | W | — | — | — |
| `CHANGELOG_AGENT.md` | R | — | W | — | — | — |
| `LEARNED_PATTERNS.md` | R | W (gerekirse) | — | — | — | — |
| `DECISIONS.md` | R | W (gerekirse) | — | — | — | — |
| `daily/YYYY-MM-DD.md` | — | — | W | — | — | — |
| `weekly/YYYY-WXX.md` | — | — | — | W | — | — |
| `monthly/YYYY-MM.md` | — | — | — | — | W | — |
| `sprint/SPRINT-XX.md` | — | — | — | — | — | W |
| `tasks/TASK_MANIFEST.md` | R | R/W | R/W | — | — | W |
| `protocols/incidents/` | R | W | — | — | — | — |

**R = Okunur | W = Yazılır | — = İşlenmez**

---

## 6. BİLGİ KALİTE STANDARTLARI

### 6.1 Zaman Damgası Kuralı

Her memory dosyası **ilk satırda** tarih içermelidir:

```markdown
# Oturum Adı — YYYY-MM-DD
# 2026-06-28 | Oturum 49 | Sprint 3.5
```

### 6.2 Kalıcı Bağlantı Kuralı

Önemli kararlar veya görevler **birden fazla yerde** referans edilmelidir:

```
memory/DECISIONS.md (ana kayıt)
        │
        ├───► memory/protocols/decisions/decisions-2026-06.json
        ├───► memory/sprint/sprint-3.5.md
        └───► docs/adr/adr-022-new-architecture.md
```

### 6.3 Otomatik Özet Kuralı

Her haftalık kapanışta, o haftanın memory dosyalarından **tek paragraf özet** üretilir:

```markdown
## Hafta 26 Özeti (Otomatik)

Sprint 3.4.5 tamamlandı. AI Description Pipeline üretime hazır. 
Bekci health 91.85%'den 93.10%'ye yükseldi. 3 yeni learned pattern 
eklendi (LP-008, LP-009, LP-010). MCP Server yeniden yapılandırıldı. 
Sprint 3.5 başlatıldı: AI CRM Assistant.
```

---

## 7. BİLGİ YOKSUNLUĞU ÖNLEME

### 7.1 "Bilgi Kaybolmasın" Protokolü

| Durum | Tetikleyici | Otomatik Eylem |
|-------|------------|-----------------|
| Session crash | 30 dk sessizlik | Son işlemler otomatik yazılır |
| Oturum yarım kaldı | `session_timeout` | `memory/SESSION_NOTES.md` son satırı okunur |
| Görev atlanıyor | `T-X` yok | Chief AI alert |
| Memory eski | 7 gün erişilmedi | Sahip bildirimi |

### 7.2 Kurtarma Protokolü

```
Session çökerse:
1. Git log son 5 commit kontrol et
2. CHANGELOG_AGENT.md son kayıtları oku
3. SESSION_NOTES.md son oturumu oku
4. Görev durumunu TASK_MANIFEST'ten oku
5. Devam et — geçmiş bilgi kaybolmaz
```

---

## 8. ÇAPRAZ BAŞVURU HARİTASI

```
memory/PROJECT_BRAIN.md
    │
    ├──► chief-ai/sprint-backlog.md (görev listesi)
    │        │
    │        └──► memory/tasks/TASK_MANIFEST.md (detay)
    │
    ├──► memory/SESSION_NOTES.md (oturum detayı)
    │        │
    │        └──► memory/daily/YYYY-MM-DD.md (günlük özet)
    │
    └──► memory/CHANGELOG_AGENT.md (değişiklik kayıtları)
             │
             └──► memory/LEARNED_PATTERNS.md (kalıplar)
                      │
                      └──► docs/SAB.md (kurallar)
```

---

## 9. SAKLAMA VE TEMİZLİK

| Klasör/Dosya | Saklama | Otomatik Mi? | Temizlik Kuralı |
|--------------|---------|--------------|----------------|
| `memory/PROJECT_BRAIN.md` | Süresiz | — | Asla silinmez |
| `memory/CHANGELOG_AGENT.md` | Süresiz | — | Asla silinmez |
| `memory/LEARNED_PATTERNS.md` | Süresiz | — | Asla silinmez |
| `memory/DECISIONS.md` | Süresiz | — | Asla silinmez |
| `memory/WHERE_IS_WHAT.md` | Süresiz | — | Asla silinmez |
| `memory/HOW_IT_WORKS.md` | Süresiz | — | Asla silinmez |
| `memory/SESSION_NOTES.md` | 1 yıl | Evet | 1 yıldan eski silinir |
| `memory/daily/` | 30 gün | Evet | 30 günden eski silinir |
| `memory/weekly/` | 12 hafta | Evet | 12 haftadan eski → monthly/ |
| `memory/monthly/` | 12 ay | Evet | 12 aydan eski → quarterly/ |
| `memory/sprint/` | Süresiz | — | Asla silinmez |
| `memory/tasks/` | 3 ay | Evet | Tamamlanan görevler arşive taşınır |
| `memory/protocols/` | Süresiz | — | Asla silinmez |

**Otomatik temizlik script:** `scripts/ops/memory-cleanup.sh`

---

## 10. OTURUM DOĞRULAMA

```bash
# Memory dosyaları sağlığı
php artisan memory:health-check

# Çıktı:
# ✅ PROJECT_BRAIN.md       — Güncel (2026-06-25)
# ✅ CHANGELOG_AGENT.md    — Güncel (2026-06-27)
# ✅ SESSION_NOTES.md      — Güncel (2026-06-27)
# ✅ LEARNED_PATTERNS.md   — 7 kalıp dokümante
# ⚠️ daily/               — 2 gün eksik (2026-06-26, 2026-06-27)
# ✅ weekly/               — Güncel
# ✅ sprint/               — 3 sprint kayıtlı

# Temizlik kontrolü
./scripts/ops/memory-cleanup.sh --dry-run

# Bilgi bütünlüğü kontrolü
php artisan memory:integrity-check
```

---

## 11. ÇAPRAZ REFERANSLAR

| Doküman | İlişki |
|---------|--------|
| `KNOWLEDGE_BLUEPRINT.md` | Ana blueprint |
| `NOTEBOOKLM_STRUCTURE.md` | NotebookLM — memory/ dosyalarını okur |
| `DRIVE_STRUCTURE.md` | Drive — memory çıktılarını sync eder |
| `memory/PROJECT_BRAIN.md` | Katman 1 — mevcut, genişletilecek |
| `memory/CHANGELOG_AGENT.md` | Katman 2 — mevcut, derinleştirilecek |
| `memory/SESSION_NOTES.md` | Katman 3 — mevcut, yapılandırılacak |
| `memory/LEARNED_PATTERNS.md` | Katman 2 — mevcut, otomatize edilecek |

---

## 12. SONRAKI ADIMLAR

| # | Adım | Öncelik | Tahmini Süre |
|---|------|---------|-------------|
| 1 | `memory/daily/` klasörünü oturum sonu protokole ekle | P0 | 1 saat |
| 2 | `memory/weekly/` formatı + Cuma otomatik çalışacak | P1 | 2 saat |
| 3 | `memory/tasks/TASK_MANIFEST.md` oluştur | P1 | 2 saat |
| 4 | `scripts/ops/memory-cleanup.sh` yaz (30/12/12 saklama) | P1 | 2 saat |
| 5 | Chief AI oturum sonu protokolünü kodla | P1 | 4 saat |
| 6 | `memory/quarterly/` klasörü oluştur | P2 | 1 saat |
| 7 | Memory health dashboard oluştur | P2 | 4 saat |

---

*Bu doküman Yalıhan Platform'un kurumsal hafıza mimarisinin tasarımıdır. Chief Knowledge Officer tarafından yönetilir, Chief AI tarafından otomatize edilir.*
