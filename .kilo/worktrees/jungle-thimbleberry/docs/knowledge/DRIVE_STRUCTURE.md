# DRIVE_STRUCTURE.md
## YALIHAN PLATFORM v2.0 — Google Drive Organizasyonu

> **Tarih:** 2026-06-28
> **Sürüm:** 1.0.0
> **Yazar:** Chief Knowledge Officer (CKO)
> **Durum:** PLAN — Henüz uygulanmadı

---

## 1. MEVCUT DURUM

Google Drive yapısı **yok**. Tüm dokümantasyon repository'de (docs/) ve yerel memory'de (memory/) saklanıyor.

**Problem:**
- Dokümanlar dağınık (docs/, memory/, .sab/, agents/)
- Paylaşım zor (sadece developer'lar erişebiliyor)
- Sürüm kontrolü yok (Google Docs'un kendi history'si var ama Git ile senkronize değil)
- Yedekleme yok (tek kopya = risk)
- Müşteri dökümanları eksik (emlak sözleşmeleri, tapu bilgileri nerede?)

---

## 2. HEDEF: 4-SEVİYELİ HİYERARŞİ

```
yalihanai/
│
├── 📁 01-GOVERNANCE/           [Katman 2 — Sabit Referans]
├── 📁 02-PRODUCT/              [Katman 3 — İş Birimi]
├── 📁 03-CLIENTS/              [Katman 4 — Müşteri & Hukuk]
└── 📁 04-ARCHIVE/             [Arşiv — Otomatik]
```

### Seviye Mantığı

| Seviye | Erişim | Sahip | Amacı |
|--------|--------|-------|--------|
| 01-GOVERNANCE | Herkes | CTO | Kalıcı referans |
| 02-PRODUCT | Ekip | Product Owner | İş birimi dokümanları |
| 03-CLIENTS | Yetkili | İlgili danışman | Müşteri özel bilgisi |
| 04-ARCHIVE | CKO | CKO | Uzun süreli saklama |

---

## 3. KLASÖR DETAYLARI

### 3.1 01-GOVERNANCE/

**Amacı:** Teknik anayasa, mimari kararlar, standartlar — uzun ömürlü referans

```
01-GOVERNANCE/
│
├── 📄 README.md                    ← Bu klasörün açıklaması
│
├── 📁 STANDARDS/
│   ├── 📄 SAB.md                  ← (Kopya) Sistem anayasası
│   ├── 📄 CODING_STANDARDS.md    ← Kodlama standartları
│   ├── 📄 NAMING_CONVENTIONS.md  ← Kanonik isimlendirme
│   ├── 📄 API_CONTRACT.md        ← API kontratları
│   └── 📄 SECURITY_POLICY.md     ← Güvenlik politikası
│
├── 📁 ADR/
│   ├── 📄 README.md              ← ADR kataloğu
│   ├── 📄 adr-001-context7.md
│   ├── 📄 adr-002-perf-ci-gate.md
│   ├── ...
│   └── 📄 adr-021-bekci-v2.md
│
├── 📁 ARCHITECTURE/
│   ├── 📄 SYSTEM_OVERVIEW.md     ← Genel mimari
│   ├── 📄 DOMAIN_MODEL.md        ← Domain diyagramları
│   ├── 📄 CQRS_FLOWS.md         ← Event akışları
│   ├── 📄 AI_WORKFORCE.md        ← AI Workforce diyagramı
│   └── 📁 DIAGRAMS/
│       ├── 🖼️ architecture-flow.png
│       ├── 🖼️ cqrs-event-flow.png
│       └── 🖼️ ai-workforce.png
│
├── 📁 RELEASES/
│   ├── 📄 v1.0-CHANGELOG.md
│   ├── 📄 v1.5-CHANGELOG.md
│   ├── 📄 v2.0-CHANGELOG.md
│   └── 📄 v2.0-FEATURES.md
│
└── 📁 COMPLIANCE/
    ├── 📄 GDPR_COMPLIANCE.md
    ├── 📄 KVKK_COMPLIANCE.md     ← Türkiye KVKK uyumu
    └── 📄 DATA_RETENTION.md       ← Veri saklama politikası
```

**Erişim:** Tüm ekip (okuma + yazma)
**Sahiplik:** CTO
**Güncelleme:** Her sprint sonu (otomatik GitHub Actions sync)

---

### 3.2 02-PRODUCT/

**Amacı:** İş birimi dokümanları, sprint planları, feature spec'ler

```
02-PRODUCT/
│
├── 📄 README.md
│
├── 📁 SPRINTS/
│   ├── 📄 SPRINT-BACKLOG.md     ← Aktif sprint iş listesi
│   ├── 📁 2026/
│   │   ├── 📁 Q1/               ← 2026 Q1 sprint'leri
│   │   │   ├── 📄 SPRINT-3.1-RETRO.md
│   │   │   ├── 📄 SPRINT-3.2-RETRO.md
│   │   │   └── 📄 Q1-REVIEW.md
│   │   ├── 📁 Q2/
│   │   │   ├── 📄 SPRINT-3.3-RETRO.md
│   │   │   ├── 📄 SPRINT-3.4-RETRO.md
│   │   │   └── 📄 Q2-REVIEW.md
│   │   └── 📁 Q3/
│   │       ├── 📄 SPRINT-4.1-PLAN.md
│   │       └── 📄 Q3-PLAN.md
│   └── 📁 2025/
│       └── 📁 archived/         ← Önceki yıl sprint'leri
│
├── 📁 FEATURES/
│   ├── 📄 FEATURE-CATALOG.md    ← Tüm feature'ların listesi
│   ├── 📁 AI/
│   │   ├── 📄 AI-LISTING-ASSISTANT.md
│   │   ├── 📄 AI-CRM-ASSISTANT.md
│   │   ├── 📄 AI-OPERATIONS.md
│   │   └── 📄 AI-FINANCE.md
│   ├── 📁 PORTFOLIO/
│   │   ├── 📄 PORTFOLIO-CREATE.md
│   │   ├── 📄 PORTFOLIO-MANAGE.md
│   │   └── 📄 PORTFOLIO-AIRBNB.md
│   └── 📁 CRM/
│       ├── 📄 CRM-PIPELINE.md
│       └── 📄 CRM-CUSTOMER-JOURNEY.md
│
├── 📁 UX/
│   ├── 📁 WIREFRAMES/
│   │   ├── 🖼️ admin-dashboard.png
│   │   ├── 🖼️ owner-portal.png
│   │   └── 🖼️ mobile-app.png
│   ├── 📄 USER-FLOWS.md
│   └── 📄 ACCESSIBILITY.md
│
├── 📁 REPORTS/
│   ├── 📁 WEEKLY/
│   │   ├── 📄 WEEK-24-REPORT.md
│   │   └── 📄 WEEK-25-REPORT.md
│   ├── 📁 MONTHLY/
│   │   ├── 📄 JUNE-2026-REPORT.md
│   │   └── 📄 JULY-2026-REPORT.md
│   └── 📁 KPI/
│       ├── 📄 KPI-DASHBOARD.md
│       └── 📄 AI-USAGE-METRICS.md
│
└── 📁 INTEGRATIONS/
    ├── 📄 N8N-WORKFLOWS.md
    ├── 📄 TELEGRAM-BOT.md
    ├── 📄 AIRBNB-SYNC.md
    └── 📄 OPENCLAW-SETUP.md
```

**Erişim:** Product Owner (yazma), Ekip (okuma)
**Sahiplik:** Product Owner
**Güncelleme:** Her sprint sonu (manuel veya otomatik)

---

### 3.3 03-CLIENTS/

**Amacı:** Müşteri dökümanları, emlak dosyaları, sözleşmeler

```
03-CLIENTS/
│
├── 📄 README.md                    ← Gizlilik notu
│
├── 📁 [CLIENT-CODE]/              ← Her müşteri için alt klasör
│   ├── 📄 CUSTOMER-INFO.md       ← Müşteri profili, iletişim
│   ├── 📄 PROPERTY-DETAILS.md    ← Gayrimenkul detayları
│   ├── 📄 CONTRACTS/
│   │   ├── 📄 SATIS-SOZLESMESI.pdf
│   │   ├── 📄 KIRA-SOZLESMESI.pdf
│   │   └── 📄 AIRBNB-AGREEMENT.pdf
│   ├── 📄 DOCUMENTS/
│   │   ├── 📄 TAPU-KAYDI.pdf
│   │   ├── 📄 IMAR-DURUMU.pdf
│   │   └── 📄 SIGORTA.pdf
│   ├── 📁 PHOTOS/
│   │   ├── 📁 INTERIOR/
│   │   └── 📁 EXTERIOR/
│   ├── 📁 FINANCIAL/
│   │   ├── 📄 COMMISSION-TRACKING.md
│   │   ├── 📄 PAYMENT-HISTORY.md
│   │   └── 📄 OWNER-STATEMENT.pdf
│   └── 📄 NOTES.md                ← Danışman notları
│
└── 📁 TEMPLATES/
    ├── 📄 SATIS-SOZLESMESI-TEMPLATE.docx
    ├── 📄 KIRA-SOZLESMESI-TEMPLATE.docx
    └── 📄 MUSTERI-BILGI-FORMU.xlsx
```

**Erişim:** Sadece yetkili danışmanlar + CFO (financial)
**Şifreleme:** Drive'da "Drive'da sağlayın" (restriked sharing)
**Sahiplik:** İlgili danışman + CKO
**Güncelleme:** Müşteri toplantısı sonrası (manuel)

**Güvenlik Kuralları:**
- Müşteri klasörü sadece o müşteriyle ilişkili danışman + CFO erişebilir
- Paylaşım linki **asla** oluşturulmaz
- KVKK uyumlu saklama (7 yıl minimum)

---

### 3.4 04-ARCHIVE/

**Amacı:** Uzun süreli saklama, eski dokümanlar, yasal gereklilikler

```
04-ARCHIVE/
│
├── 📄 README.md                    ← Saklama politikası
│
├── 📁 2025/
│   ├── 📁 Q1-Q2/                 ← 2025 ilk yarı
│   │   ├── 📄 FINANCIAL-REPORT-2025-H1.pdf
│   │   └── 📄 SPRINT-REPORTS/
│   └── 📁 Q3-Q4/               ← 2025 ikinci yarı
│
├── 📁 2024/
│   ├── 📄 FINANCIAL-REPORT-2024.pdf
│   └── 📄 LEGAL/
│       ├── 📄 MUHASEBE-2024.pdf
│       └── 📄 VERGI-BEYANNAME-2024.pdf
│
├── 📁 LEGAL/
│   ├── 📁 CONTRACTS/
│   │   ├── 📄 TEDARIKCI-ANLASMALARI/
│   │   └── 📄 SIRKET-SOZLESMELERI/
│   ├── 📁 COMPLIANCE/
│   │   └── 📄 KVKK-DENETIM-2024.pdf
│   └── 📄 TERMS-OF-SERVICE.pdf
│
└── 📁 DRIVE-SYNC-LOGS/
    └── 📄 SYNC-HISTORY.md         ← Otomatik log
```

**Erişim:** Sadece CKO + CFO (yasal)
**Sahiplik:** CKO
**Güncelleme:** Yıllık gözden geçirme + Drive otomatik versioning

---

## 4. DRIVE-API ENTEGRASYONU

### 4.1 Drive API Yetkilendirme

```bash
# 1. Google Cloud Console'da Drive API etkinleştir
# 2. OAuth 2.0 credential oluştur
# 3. Credentials JSON'i indir → storage/app/google-drive-credentials.json

# 4. Environment variable
GOOGLE_DRIVE_CREDENTIALS=/app/credentials/google-drive-credentials.json
GOOGLE_DRIVE_FOLDER_ID=1ABC123xyz...  # YalihanAI root folder ID
```

### 4.2 Repository → Drive Sync

```bash
# GitHub Actions workflow: sync-governance.yml
name: Sync to Google Drive

on:
  push:
    branches: [main]
    paths:
      - 'docs/SAB.md'
      - 'docs/adr/**'
      - 'memory/**'

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Sync to Google Drive
        uses: some/google-drive-sync-action@v1
        with:
          credentials: ${{ secrets.GOOGLE_DRIVE_CREDENTIALS }}
          folder-id: ${{ vars.GOOGLE_DRIVE_FOLDER_ID }}
          local-path: docs/
          remote-path: 01-GOVERNANCE/
```

### 4.3 Drive → Repository Sync (Tek yön!)

```
UYARI: Drive → Repository sync YAPILMAZ!
Drive'da yapılan değişiklikler Drive'da kalır.
Repository tek kaynak (SSOT) olarak kalır.
```

---

## 5. PAYLAŞIM MATRİSİ

### 5.1 Erişim Seviyeleri

| Klasör | Developer | Product Owner | Danışman | CFO | CKO |
|--------|-----------|--------------|-----------|-----|-----|
| 01-GOVERNANCE | RW | R | R | R | RW |
| 02-PRODUCT | R | RW | R | — | RW |
| 03-CLIENTS | — | — | Own client | RW | RW |
| 04-ARCHIVE | — | — | — | R | RW |

**RW = Okuma + Yazma | R = Salt Okunur | — = Erişim Yok**

### 5.2 Paylaşım Grubu Tanımları

| Grup | Üyeler | Erişim |
|------|--------|--------|
| yalihan-developers | Kilo, Windsurf, Cursor, Claude | 01-GOVERNANCE (R), 02-PRODUCT (R) |
| yalihan-product | Product Owner, Scrum Master | 01-GOVERNANCE (R), 02-PRODUCT (RW) |
| yalihan-consultants | Tüm danışmanlar | 03-CLIENTS (kendi dosyaları) |
| yalihan-exec | CFO, CTO | Tümü (R) |
| yalihan-cko | CKO | Tümü (RW) |

---

## 6. OTOMATİK YEDEKLEME

### 6.1 Drive Backup Stratejisi

```
┌─────────────────────────────────────────────────────────────┐
│  Google Drive ──────────────────────────────────────────── │
│                                                             │
│  Daily Backup: Tüm dosyaların Drive versioning'inde        │
│  (Google otomatik: son 100 versiyon veya 30 gün)         │
│                                                             │
│  Weekly Backup: 04-ARCHIVE/ → Yedek Cloud Storage         │
│  (AWS S3 Glacier veya Google Cloud Storage)               │
│                                                             │
│  Monthly Backup: 03-CLIENTS/ → Offline HDD                 │
│  (Fiziksel yedekleme — KVKK uyumu için)                  │
└─────────────────────────────────────────────────────────────┘
```

### 6.2 Yedekleme Script

```bash
#!/bin/bash
# scripts/ops/drive-backup.sh

# Weekly archive backup to GCS
gsutil rsync -r \
  "gdrive:yalihanai/04-ARCHIVE/" \
  "gs://yalihan-backup-archive/2026/$(date +%Y-W%V)/"

# Log
echo "[$(date)] Archive backup completed: gs://yalihan-backup-archive/"
```

---

## 7. SAKLAMA VE ARŞİVLEME KURALLARI

### 7.1 Saklama Süreleri

| Klasör | Minimum | Maximum | Arşiv Tetikleyicisi |
|--------|---------|--------|---------------------|
| 01-GOVERNANCE/STANDARDS | Süresiz | — | Hiçbir zaman silinmez |
| 01-GOVERNANCE/ADR | Süresiz | — | Hiçbir zaman silinmez |
| 01-GOVERNANCE/RELEASES | 5 yıl | — | Versiyon +2 |
| 02-PRODUCT/SPRINTS | 2 yıl | — | Yıl değiştiğinde |
| 02-PRODUCT/FEATURES | 2 yıl | — | Feature deprecated |
| 02-PRODUCT/REPORTS | 3 yıl | — | Yıl değiştiğinde |
| 03-CLIENTS/ | 7 yıl | 10 yıl | Müşteri ilişkisi sona erdi + 7 yıl |
| 04-ARCHIVE/ | 10 yıl | — | Hiçbir zaman silinmez |

### 7.2 Silme Politikası

**ASLA silinmeyecek:**
- SAB.md ve tüm governance dokümanları
- ADR dosyaları
- Müşteri sözleşmeleri
- Finansal kayıtlar

**Silinebilir (otomatik veya manuel):**
- 3 aydan eski draft dokümanları (yayınlanmamış)
- 2 aydan eski sync log'ları
- Kullanımdan kaldırılmış feature spec'leri

---

## 8. KURULUM ADIMLARI

### Adım 1: Google Workspace Kurulumu (1 gün)

```
1. google.com/workplace adresine git
2. Business Standard plan seç (2TB, $12/kullanıcı/ay)
3. YalihanAI organizasyonu oluştur
4. Kullanıcıları ekle (developer, product, consultants)
5. Paylaşım gruplarını yapılandır
```

### Adım 2: Klasör Yapısı Kurulumu (1 gün)

```
1. YalihanAI root klasörünü oluştur
2. Yukarıdaki hiyerarşiyi kur (01-GOVERNANCE/, 02-PRODUCT/, vb.)
3. Erişim izinlerini yapılandır
4. İlk 5 dosyayı yükle
5. Paylaşım ayarlarını kilitle
```

### Adım 3: API Erişimi (1 gün)

```
1. Google Cloud Console → Drive API etkinleştir
2. OAuth 2.0 credential oluştur
3. Credentials JSON'i güvenli yere kaydet
4. GitHub Secrets'e credentials ekle
5. İlk sync test et
```

### Adım 4: Otomatik Sync (1 gün)

```
1. GitHub Actions workflow oluştur
2. Sync script'ini test et
3. Rate limit monitörünü kur
4. Error handling'i doğrula
5. Monitoring dashboard kur
```

---

## 9. MALİYET

| Kalem | Birim | Aylık Maliyet |
|-------|-------|--------------|
| Google Workspace Business Standard | 5 kullanıcı | $60 |
| Ek kullanıcı (her biri) | 1 kullanıcı | $12 |
| GCS Archive Backup (opsiyonel) | 100GB | ~$2 |
| **Toplam (5 kullanıcı)** | | **$60/ay** |

---

## 10. OTURUM DOĞRULAMA

```bash
# Drive sync durumu
gh run list --workflow=sync-governance.yml

# Drive API test
php artisan drive:check-connection

# Sync log kontrolü
cat logs/drive-sync.log

# Klasör yapısı doğrulama
php artisan drive:validate-structure
```

---

## 11. ÇAPRAZ REFERANSLAR

| Doküman | İlişki |
|---------|--------|
| `KNOWLEDGE_BLUEPRINT.md` | Ana blueprint |
| `NOTEBOOKLM_STRUCTURE.md` | NotebookLM — Drive'dan beslenir |
| `CORPORATE_MEMORY.md` | Katman 1 — Drive sync kaynağı |
| `KNOWLEDGE_BLUEPRINT.md#6` | Saklama süreleri detayları |
| `docs/SAB.md` | 01-GOVERNANCE/STANDARDS/SAB.md'nin kaynağı |

---

*Bu doküman Yalıhan Platform'un Google Drive organizasyonunun tasarımıdır. Chief Knowledge Officer tarafından yönetilir, Drive Admin tarafından uygulanır.*
