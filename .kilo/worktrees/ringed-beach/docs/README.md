# YALIHAN OS — Dokümantasyon

> Bu dizin, YALIHAN OS'nin tüm teknik dokümantasyonunu barındırır.
> Büyüyen proje için düzenli ve sürdürülebilir bir yapı.

---

## 📁 Dizin Yapısı

```
docs/
├── README.md                              ← Bu dosya
│
├── 01-domain/                             ← ⬅️ EN ÖNEMLİ
│   └── YALIHAN_OS_DOMAIN_MODEL.md         ← Projenin ortak dili
│
├── 02-architecture/
│   └── SYSTEM_ARCHITECTURE.md             ← Teknik altyapı
│
├── 03-modules/                            ← (Gelecek)
│   ├── CRM.md
│   ├── PROPERTY.md
│   ├── AIRBNB.md
│   └── FINANCE.md
│
├── 04-decisions/                          ← (Gelecek)
│   ├── ADR-0001-USE_TENANT_SCOPING.md
│   ├── ADR-0002-USE_AI_SERVICE_PATTERNS.md
│   └── ...
│
├── SAB.md                                 ← Teknik anayasa
└── BEKCI_CHANGELOG.md                    ← Agent oturum kayıtları
```

---

## 📖 Doküman Okuma Sırası

### Yeni Geliştirici İlk Hafta

```
1. YALIHAN_OS_DOMAIN_MODEL.md    ← Önce ürünü anla
2. SYSTEM_ARCHITECTURE.md        ← Sonra teknik yapıyı anla
3. SAB.md                       ← Kuralları bil
```

### Sprint Başında

```
1. Domain Model (ilgili bölüm)   ← Bu sprint hangi entity'leri etkiliyor?
2. Modül dokümanı               ← İlgili modülün detayları
3. ADR'lar                      ← Geçmiş kararlar
```

---

## 📋 Doküman Kılavuzu

### 01-domain — Domain Model

> ⬅️ **HER ŞEYİN BAŞLADIĞI YER**

| Dosya | İçerik |
|-------|---------|
| `YALIHAN_OS_DOMAIN_MODEL.md` | Entity'ler, ilişkiler, AI touchpoints, event flow, modül bağımlılıkları |

**Neden en önemli?** Kod değil — ürünün ortak dilini tanımlar.

---

### 02-architecture — Mimari

| Dosya | İçerik |
|-------|---------|
| `SYSTEM_ARCHITECTURE.md` | Laravel yapısı, CQRS, Event system, AI pipeline, service katmanı |

---

### 03-modules — Modül Dokümanları

> Gelecek sprintlerde oluşturulacak.

| Dosya | Durum | İçerik |
|-------|--------|---------|
| `CRM.md` | Planlandı | Müşteri, lead, fırsat, not yönetimi |
| `PROPERTY.md` | Planlandı | Villa, ilan, medya, fiyatlandırma |
| `AIRBNB.md` | Planlandı | Airbnb API, rezervasyon, temizlik otomasyonu |
| `FINANCE.md` | Planlandı | Gelir, gider, ödeme, fatura |

---

### 04-decisions — Mimari Karar Kayıtları (ADR)

> Önemli mimari kararların nedenleri ve sonuçları.

Format: `ADR-XXXX-KISA_BASLIK.md`

| ADR | Konu | Tarih |
|-----|-------|-------|
| ADR-0001 | Tenant Scoping | 2026-05-21 |
| ADR-0002 | AI Service Patterns | 2026-05-21 |
| ADR-0003 | Single Source of Truth | 2026-06-27 |

---

## 🏆 Proje Dokümantasyon Hiyerarşisi

```
YALIHAN_OS_DOMAIN_MODEL.md        ← En üstte (ÜRÜNÜN ORTAK DİLİ)
    │
    ▼
SYSTEM_ARCHITECTURE.md            ← Teknik altyapı (ÜRÜNÜ NASIL ÇALIŞIR)
    │
    ▼
SAB.md                            ← Kurallar (NASIL YAZILIR)
    │
    ▼
Modül Dokümanları                 ← Detaylı implementasyon (NEYİ NASIL YAPARIZ)
```

---

## 📝 Doküman Yazım Kuralları

1. **Domain Model** — İş nesnesi odaklı, teknik terimlerden kaçın
2. **Mimari** — Teknik ama anlaşılır
3. **Modül** — Developer'a yönelik, kod örnekleri içerir
4. **ADR** — Karar odaklı, "neden" önemli

---

## 🔄 Güncelleme Tetikleyicileri

| Tetik | Güncellenecek Doküman |
|-------|----------------------|
| Yeni entity eklendi | `01-domain/YALIHAN_OS_DOMAIN_MODEL.md` |
| Yeni modül başladı | `03-modules/[modul].md` |
| Mimari karar alındı | `04-decisions/ADR-XXXX.md` |
| AI entegrasyonu değişti | `02-architecture/SYSTEM_ARCHITECTURE.md` |
| Kural değişti | `SAB.md` |

---

## ⚖️ Doküman Beslenmesi — Oturum Protokolü

Her agent oturumunda:

1. **Oturum başı:** Domain Model ilgili bölümü oku
2. **Oturum içi:** Değişiklik yapılan entity/modül dokümanını güncelle
3. **Oturum sonu:** Oturum notu → `BEKCI_CHANGELOG.md`

---

*Son güncelleme: 2026-06-27*
*Oturum: 44*
*Yapı: 4 seviyeli dokümantasyon sistemi aktif*
