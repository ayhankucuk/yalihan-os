# knowledge/ — Bilgi Tabanı

> Yalıhan Emlak AI OS — Bilgi Tabanı ve Kurumsal Hafıza Yönetimi
> Chief Knowledge Officer (CKO) tarafından yönetilir

## Yapı

```
knowledge/
├── README.md                      ← Bu dosya — giriş noktası
├── KNOWLEDGE_BLUEPRINT.md         ← Stratejik bilgi mimarisi planı
├── CORPORATE_MEMORY.md            ← Kurumsal hafıza katmanları detayı
├── NOTEBOOKLM_STRUCTURE.md        ← AI bilgi çıkarım (NotebookLM) mimarisi
├── DRIVE_STRUCTURE.md            ← Google Drive organizasyonu
├── KNOWLEDGE_GAP_REPORT.md        ← Bilgi açıkları + öneriler
│
├── patterns/                    ← Mimari pattern'lar
│   └── *.md
│
└── learning/                    ← MCP/agent öğrenmeleri
    └── *.json
```

## 4 Katmanlı Bilgi Mimarisi

| Katman | Depo | İçerik | Yönetici |
|--------|-------|---------|----------|
| **1 — Operasyonel Hafıza** | `memory/` | Agent oturum belleği | Chief AI |
| **2 — Teknik Bilgi** | `docs/` + NotebookLM | Kod, mimari, SAB, ADR | Architect |
| **3 — Proje Bilgi Havuzu** | `Drive/02-PRODUCT/` | Sprint, feature, raporlar | Product Owner |
| **4 — Kurumsal Arşiv** | `Drive/03-CLIENTS/` + `04-ARCHIVE/` | Müşteri, hukuk, yasal | CKO |

Bkz: `KNOWLEDGE_BLUEPRINT.md` — Tüm katmanların detaylı açıklaması.

## Temel Dokümanlar

| Doküman | Ne İçin | Okunması Gereken |
|---------|---------|------------------|
| `KNOWLEDGE_BLUEPRINT.md` | Stratejik plan, 4 katman, yol haritası | İlk önce — toplam 10 dakika |
| `CORPORATE_MEMORY.md` | memory/ katmanları, oturum protokolleri | Agent geliştiriciler |
| `NOTEBOOKLM_STRUCTURE.md` | 5 notebook kataloğu, sync pipeline | AI coordinator |
| `DRIVE_STRUCTURE.md` | Drive klasör hiyerarşisi | Drive admin |
| `KNOWLEDGE_GAP_REPORT.md` | 10 açık, 9 öneri, 90 gün yol haritası | CTO + Product Owner |

## Kurumsal Hafıza Durumu

| Bileşen | Durum | Son Güncelleme |
|---------|-------|----------------|
| `memory/PROJECT_BRAIN.md` | ✅ Aktif | 2026-06-25 |
| `memory/CHANGELOG_AGENT.md` | ✅ Aktif | 2026-06-27 |
| `memory/SESSION_NOTES.md` | ✅ Aktif | 2026-06-27 |
| `memory/LEARNED_PATTERNS.md` | ✅ Aktif | 2026-06-25 |
| `memory/DECISIONS.md` | ✅ Aktif | 2026-06-27 |
| `memory/daily/` | ⚠️ Kısmi | Oluşturulacak |
| `memory/weekly/` | ⚠️ Kısmi | Oluşturulacak |
| Google Drive | ❌ Yok | Sprint 1'de başlanacak |
| NotebookLM sync | ⚠️ Manuel | Sprint 2'de otomatize edilecek |

## Quick Start — Agent Oturumu

```bash
# 1. Oturum başı (zorunlu)
cat memory/PROJECT_BRAIN.md          # Proje durumunu anla
cat memory/SESSION_NOTES.md         # Son oturumları oku

# 2. Görev öncesi kontrol
php artisan bekci:health --detailed # Sistem sağlığı
php artisan sab:integrity-scan      # Mimari ihlal var mı?

# 3. Görev çalışması...

# 4. Oturum sonu (zorunlu)
# memory/CHANGELOG_AGENT.md → Yeni kayıt ekle
# memory/SESSION_NOTES.md → Oturum özeti ekle
# memory/daily/YYYY-MM-DD.md → Günlük not (Sprint 1+)
```

## Doğrulama Komutları

```bash
# Bilgi katmanı sağlığı
php artisan bekci:health --detailed

# SAB bütünlük kontrolü
php artisan sab:integrity-scan

# Memory dosyaları kontrolü
ls -la memory/

# ADR sayısı
find docs/adr -name "*.md" | wc -l

# Drive sync durumu (kurulduktan sonra)
ps aux | grep notebooklm-sync
```

## Bilgi Sahipliği

| Bilgi Tipi | Birincil Sahip | Yedek Sahip |
|-----------|----------------|-------------|
| SAB anayasası | CTO | Chief AI |
| ADR'ler | Architect | CTO |
| memory/* | Chief AI | Tüm agent'lar |
| NotebookLM | AI Coordinator | CTO |
| Drive/02-PRODUCT | Product Owner | Scrum Master |
| Drive/03-CLIENTS | CFO | İlgili danışman |
| Drive/04-ARCHIVE | CKO | — |

Bkz: `KNOWLEDGE_BLUEPRINT.md#5` — Tam sahiplik matrisi.

## Saklama Süreleri

| Katman | Saklama | Arşiv Konumu |
|--------|---------|--------------|
| memory/ (core) | Süresiz | Asla silinmez |
| memory/daily/ | 30 gün | Drive/04-ARCHIVE |
| memory/weekly/ | 12 hafta | Drive/04-ARCHIVE |
| memory/sprint/ | Süresiz | Asla silinmez |
| Drive/01-GOVERNANCE | Süresiz | Asla silinmez |
| Drive/03-CLIENTS | 7-10 yıl | KVKK uyumu |
| Drive/04-ARCHIVE | 10+ yıl | Asla silinmez |

Bkz: `KNOWLEDGE_BLUEPRINT.md#6` — Tam yaşam döngüsü.
Bkz: `DRIVE_STRUCTURE.md#7` — Drive saklama kuralları.

## Chief AI Memory Otomasyonu

```
Oturum Başı:
  1. READ: PROJECT_BRAIN.md, SESSION_NOTES.md (son 3)
  2. ANALYZE: health, risks, sprint
  3. DECIDE: Bu oturumun önceliği
  4. EXECUTE: Görev

Oturum Sonu:
  1. WRITE: CHANGELOG_AGENT.md
  2. WRITE: SESSION_NOTES.md
  3. WRITE: daily/YYYY-MM-DD.md
  4. UPDATE: PROJECT_STATE.yaml (varsa)
  5. UPDATE: TASK_MANIFEST (görev değişikliği varsa)
```

Bkz: `CORPORATE_MEMORY.md#4` — Oturum protokol detayları.

## 90 Gün Yol Haritası Özeti

| Dönem | Öncelik | Öneriler |
|-------|---------|---------|
| **Sprint 1** (Hafta 1-2) | P0 | Drive kurulumu, müşteri dokümantasyonu, bilgi sahipliği |
| **Sprint 2** (Hafta 3-4) | P1 | Corporate Memory otomasyonu, NotebookLM sync, onboarding |
| **Sprint 3** (Hafta 5-6) | P2 | Yaşam döngüsü, sürümleme, arşiv |

Bkz: `KNOWLEDGE_GAP_REPORT.md#B4` — Öncelik sıralaması.
Bkz: `KNOWLEDGE_BLUEPRINT.md#4` — Yol haritası detayı.

## Kaynak (Yalihan-Bekci)

Bu klasörün birincil kaynağı `../yalihan-bekci/`:

```
yalihan-bekci/
├── knowledge/   → Node MCP öğrenmeleri
├── learning/    → PHP Audit öğrenmeleri
└── ideas/      → AI tarafından üretilen fikirler
```

## Bağlantılar

| Dosya | Ne İçin |
|--------|---------|
| `KNOWLEDGE_BLUEPRINT.md` | Stratejik plan — başla burdan |
| `CORPORATE_MEMORY.md` | Katman 1 detayı |
| `NOTEBOOKLM_STRUCTURE.md` | Katman 2 detayı (NotebookLM) |
| `DRIVE_STRUCTURE.md` | Katman 3-4 detayı |
| `KNOWLEDGE_GAP_REPORT.md` | 10 açık + 9 öneri |
| `memory/PROJECT_BRAIN.md` | Kalıcı proje belleği |
| `docs/SAB.md` | Bilgi kalitesi standardı |
| `docs/adr/README.md` | ADR yönetim süreci |

## Güncelleme Kuralları

1. Yeni öğrenme = JSON olarak `learning/` altına
2. Yeni pattern = Markdown olarak `patterns/` altına
3. Büyük öğrenme = `memory/LEARNED_PATTERNS.md`'ye transfer et
4. Mimari karar = `docs/adr/` + `memory/DECISIONS.md`'ye kaydet
5. Sprint kapanışı = `memory/sprint/` + Drive sync
6. Her 6 ayda bir = Knowledge Gap raporunu güncelle
