---
id: sprint-21-charter
schema_version: 1.0
version: "1.0"
status: approved
owner: saab
domain: governance
created_at: 2026-07-25
reviewed_at: 2026-07-25
review_due: 2026-08-25
supersedes: []
superseded_by: []
evidence:
  commits:
    - a3e86ac
  tests: []
  adr: []
  changelog:
    - Oturum 113
tags:
  - sprint
  - charter
  - sprint-21
  - governance
---

# 📜 Sprint 21 Charter: Documentation Governance Modernization

**Sprint:** Sprint 21  
**Status:** `DRAFT`  
**Governance Protocol:** SAAB v8 / SAAB v11.1 (Governance Conflict Active)
**Role Scope:** Documentation & Governance Auditor (Antigravity v2)
**Evidence Model:** [EVIDENCE_MODEL.md v1.2](docs/governance/EVIDENCE_MODEL.md) — Sprint Packaging Standard v1.2 referans alınmıştır. Sprint teslimatları dört kanıt katmanı (Implementation / Execution / Documentation / Certification) ile sunulacaktır.  

---

## 🎯 1. İş Hedefi (Business Value)
Yalıhan OS projesinin karmaşıklığını azaltmak, Geliştiricilerin ve AI iş gücünün (Hermes) adaptasyon hızını artırmak ve dokümantasyon-kod uyumsuzluğunu (cognitive drift) engellemek amacıyla, otomatik ve denetlenebilir bir dokümantasyon yaşam döngüsü altyapısı kurmak.

---

## 🏛️ 2. Mimari Hedef
Repository içindeki tüm Markdown belgelerini statik ve bağımsız metin dosyaları olmaktan çıkarıp; standart üstbilgiler (metadata), yaşam döngüsü kuralları ve risk modelleri barındıran, sorgulanabilir bir **Bilgi Mimarisi Ağına (Knowledge Graph)** dönüştürmek.

---

## 🛠️ 3. 6 Temel Yetenek (Capabilities)

### **Capability 1: Canonical Inference Engine**
- Hardcoded canonical dosya listelerini ortadan kaldırır.
- Referans sıklığı, metadata etiketleri ve geçmiş resolution ilişkilerini analiz ederek kanıta dayalı **SSOT Adayları (SSOT Candidates)** üretir.

### **Capability 2: Semantic Duplicate Engine**
- MD5 hash çakışmalarının ötesine geçerek; başlık benzerliği, içerik örtüşmesi ve anlamsal (semantic/structural) yakınlıkları tespit eder.

### **Capability 3: Documentation Risk Engine**
- Agresif silme kararları yerine, belgelerin sistem içi bağlantılarına göre risk seviyelerini belirler:
  - `SAFE` — Bağımsız, bağı yok.
  - `REVIEW` — Düşük referans, güncellik şüpheli.
  - `ARCHIVE` — Geçmiş sprint veya taslak belgesi.
  - `GOVERNANCE` — Board kararı ve imza gerektiren belge.
  - `BLOCKED` — Kod içinden veya derleyicilerden doğrudan `@see` bağlantısı olan, silinemez belge.

### **Capability 4: Documentation Health Score Dashboard**
- Gelişimi izlemek için alt metriklerden oluşan bir sağlık puanı sunar:
  - **Overall Health** (Genel Sağlık Skoru)
  - **Canonical Coverage** (SSOT Kapsama Oranı)
  - **Duplicate Risk** (Kopyalanma Riski)
  - **Orphan Risk** (Referanssız Belge Riski)
  - **Broken Links** (Kırık Bağlantı Sayısı)
  - **Governance Conflicts** (Çelişkili Yönetişim Kayıtları)
  - **Draft Backlog** (Bekleyen Taslak Oranı)

### **Capability 5: Documentation Lifecycle Pipeline**
- Belgelerin durum geçişlerini kurallı hale getirir:
  $$\text{DRAFT} \longrightarrow \text{REVIEW} \longrightarrow \text{APPROVED} \longrightarrow \text{CANONICAL} \longrightarrow \text{SUPERSEDED} \longrightarrow \text{ARCHIVED}$$

### **Capability 6: Documentation Metadata Standard**
- Tüm canonical ve aktif belgelerde ortak bir YAML frontmatter standardı dayatır.

---

## 🗂️ 4. Dokümantasyon Metadata Şablonu (Metadata Standard)

Her canonical veya aktif belgenin başında bulunması zorunlu olan YAML yapısı:

```yaml
---
id: [uuid-or-unique-slug]
schema_version: 1.0
version: 1.0
status: [draft | review | approved | canonical | superseded | archived]
owner: [saab | developer | agent]
domain: [governance | architecture | workspace | reservation | crm | finance | publishing | operations | documentation | platform]
created_at: 2026-07-25
reviewed_at: 2026-07-25
review_due: 2026-10-25
supersedes: []
superseded_by: []
evidence:
  commits: []
  tests: []
  adr: []
  changelog: []
tags: []
---
```

---

## 🔄 5. Geliştirme ve Yürütme Sıralaması

Rework (yeniden çalışma) riskini sıfırlamak için geliştirme şu sıralamayla yapılacaktır:

1. **Adım 1: Metadata Standard** — Temel veri modelinin ve doğrulayıcı (linter) kurallarının kurulması.
2. **Adım 2: Documentation Lifecycle Pipeline** — Durum geçiş kontrol sisteminin yazılması.
3. **Adım 3: Canonical Inference Engine** — Metadata ve referans yoğunluğu üzerinden SSOT adaylarının puanlanması.
4. **Adım 4: Documentation Risk Engine** — Inference ve link analizlerine dayanarak risk sınıflarının belirlenmesi.
5. **Adım 5: Semantic Duplicate Engine** — Dosya benzerlik analizi algoritmasının kurulması.
6. **Adım 6: Documentation Health Score Dashboard** — Tüm metriklerin konsolide edilerek raporlanması.

---

## 🎯 6. Başarı Kriterleri ve Kabul Koşulları

| Hedef Kriter | Metrik / Kabul Koşulu |
|---|---|
| **Sıfır Hardcoded Liste** | Antigravity v2 motorunda hiçbir statik dosya adı listesi yer almayacak. |
| **Metadata Doğrulama** | Eksik veya hatalı YAML üstbilgisi içeren dosyalar `INCONSISTENT` olarak işaretlenecek. |
| **Risk Analizi Uyum** | Her dosya için 5 risk etiketinden biri atanacak. |
| **Otomatik Raporlama** | Her tarama sonucunda güncel `DOCUMENTATION_INVENTORY.md` ve Sağlık Skoru otomatik üretilecek. |

---

## ⚙️ 7. Quality Gates

Tüm capability'ler Sprint 21 tamamlanmadan önce aşağıdaki kapılardan geçer:

| Gate | Kriter | Araç |
|------|--------|------|
| Gate 1 | Metadata linter hiçbir hata vermeden tüm canonical dosyaları geçer | `node antigravity lint` |
| Gate 2 | Semantic duplicate algılama >%80 precision (elle doğrulanan örnekler üzerinde) | Test suite |
| Gate 3 | Health Score Dashboard tüm 7 alt metriği raporlar | `docs/_reports/documentation_health.json` |
| Gate 4 | Sprint Packaging Standard v1.2 teslimat paketi tamdır | SAAB incelemesi |

---

## 🛡️ 8. Kapsam ve Kapsam Dışı

- **Kapsam İçi:**
  - `Antigravity v2` analiz motoru tasarımı ve implementasyonu.
  - Markdown dosyalarının taranması, YAML şemalarının doğrulanması.
  - Altı capability'nin test suite ile entegrasyonu.
- **Kapsam Dışı:**
  - Repository'deki dosyaların otomatik olarak silinmesi veya taşınması (Sistem her zaman **Salt-Okur** öneriler üretecektir).
  - PHP/Laravel iş mantığı veya veri tabanı şema değişiklikleri.

---

## 🔍 9. Riskler ve Bağımlılıklar

- **Risk 1 — Mevcut belgelerin YAML geçişi:** Sprint 12B/C gibi geniş kapsamlı dokümantasyon geçişleri zaman alıcı olabilir. **Azaltma:** Mevcut belgelerin metadata geçişi Sprint 21 sonrası backlog'a taşınabilir; motor önce yeni belgeler için zorunlu kılınır.
- **Risk 2 — Metadata Standard v1.0 yetersiz kalması:** Sprint 21 sonunda eklenmesi gereken yeni alanlar ortaya çıkabilir. **Azaltma:** v1.0 = minimum viable schema; genişletme v1.1 ile yapılır.
- **Bağımlılık 1 — Antigravity v2 analiz motoru:** Mevcut Antigravity kod tabanına bağımlı. Mevcut test suite çalışır durumda olmalı.
- **Bağımlılık 2 — Evidence Model v1.2:** Sprint 21 teslimatları Sprint Packaging Standard v1.2'ye uygun olmalıdır (Section 8).

---

## 📊 10. Evidence ve Definition of Done (DoD)

Sprint 21'in tamamlanmış sayılması için aşağıdaki kanıt zinciri sunulmalıdır:
1. **Implementation Evidence:** Geliştirilen analiz sınıfları ve metadata doğrulayıcı testlerinin PHPUnit/Node çıktısı.
2. **Execution Evidence:** Yeni motor tarafından otomatik olarak üretilen ve güncellenen `docs/_reports/documentation_health.json` raporu.
3. **Documentation Evidence:** Bu sprint kapsamında güncellenen tüm canonical dosyaların yeni YAML standardına geçirilmesi.
