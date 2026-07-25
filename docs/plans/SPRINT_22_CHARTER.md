---
id: sprint-22-charter
schema_version: 1.0
version: "1.0"
status: approved
owner: saab
domain: architecture
created_at: 2026-07-25
reviewed_at: 2026-07-25
review_due: 2026-08-25
supersedes: []
superseded_by: []
evidence:
  commits: []
  tests: []
  adr: []
  changelog:
    - Oturum 114
tags:
  - sprint
  - sprint-22
  - design-system
  - yds
  - frontend
  - property-command-center
---

# Sprint 22 Charter: YDS v1.0 — YALIHAN Design System

**Sprint:** Sprint 22
**Status:** `APPROVED`
**Role Scope:** Frontend Architecture & Design System
**Evidence Model:** [EVIDENCE_MODEL.md v1.2](docs/governance/EVIDENCE_MODEL.md)

---

## 1. İş Hedefi (Business Value)

YALIHAN OS frontend'inin mühendislik kalitesi, kod standartları kadar olgun bir görsel ve etkileşim standardına kavuşmasını sağlamak. Mevcut admin panel yaklaşımından Property-merkezli, veri yoğun, AI-destekli bir işletim sistemi arayüzüne geçişin tasarım temelini atmak.

---

## 2. Mimari Hedef

Üç katmanı birbirine karıştırmadan ilerletmek:

```
Information Architecture   → Ekran envanteri, kullanıcı akışı, menü yapısı
         ↓
Design System             → Design Tokens, bileşen standartları, durumlar
         ↓
Frontend Implementation  → Vue 3 / TypeScript / Inertia.js / Tailwind
```

**Geçiş stratejisi:** Tüm sistemi bir anda Vue'ya çevirmek DEĞİL:

```
Mevcut Blade ekranlar
        ↓
Yeni ortak Design Tokens (YDS v1.0)
        ↓
Ortak UI Components
        ↓
Property Command Center (Vue — ilk referans ekran)
        ↓
Reservation Calendar (Vue)
        ↓
Action Center / Hermes Panel (Vue)
        ↓
Diğer karmaşık ekranlar
```

**Basit ekranlar Blade'de kalabilir.** Karmaşık, etkileşimli alanlar Vue ile modernize edilir.

---

## 3. Tasarım İlkeleri (YDS v1.0 Temel Kararları)

```
Less Decoration
More Information
Clear Actions
Consistent States
```

### Yasaklananlar

| Yasak | Doğrusu |
|-------|---------|
| Büyük boşluklu dekoratif alanlar | Bilgi yoğun, temiz layout |
| Sürekli gradient/efektler | Property-merkezli işlevsellik |
| Aynı iş için farklı buton stilleri | Tek Button standardı |
| Farklı input/dropdown davranışları | Tek Field standardı |
| Tutarsız status renkleri | Sistem genelinde tek Status standardı |

### Hedef

Kullanıcı ilk bakışta mevcut durumu ve sonraki aksiyonu görür.

---

## 4. Property Command Center — Information Architecture

Kullanıcı modüller arasında DOLAŞTIRILMAZ. Bir mülk açıldığında çalışma alanı:

```
🏠 Villa Betül (Property Command Center)
├── 📋 Genel Bakış       — KPI, son aktiviteler, uyarılar
├── 📡 Yayınlar          — Airbnb, Booking, Sahibinden durumu
├── 📅 Rezervasyonlar    — Takvim, check-in/out, durumlar
├── ⚙️ Operasyon         — Temizlik, bakım, görevler
├── 📄 Belgeler          — Tapu, sözleşmeler, ruhsatlar
├── 🖼️ Medya            — Fotoğraflar, videolar
├── 💰 Finans            — Gelir/gider, ödemeler
├── ⏱️ Timeline         — Olay kaydı, audit trail
└── 🤖 AI                — Hermes önerileri, approval
```

**İlke:** Kullanıcı başka uygulamalara veya onlarca menüye geçmeden mülkün sahiplik, yayın, rezervasyon, operasyon ve eksiklerini tek merkezden görür.

---

## 5. YDS v1.0 — İlk Kapsam (10 Bölüm)

| # | Bölüm | İçerik |
|---|--------|---------|
| 1 | Design Tokens | Renk, tipografi, spacing, shadow, border, motion |
| 2 | Form & Field Standard | Input, textarea, label, helper, error, validation |
| 3 | Select, Dropdown, Autocomplete | Tek standart, tüm varyantları |
| 4 | Button & Action Hierarchy | Primary, Secondary, Ghost, Danger + icon kombinasyonları |
| 5 | Table & Data Grid | Sortable, filterable, paginated, bulk actions |
| 6 | Status Badge System | Sistem genelinde tek status rengi standardı |
| 7 | Sidebar & Command Bar | Navigation, breadcrumb, context actions |
| 8 | Drawer, Modal, Confirmation | Kullanım senaryosuna göre ayrım |
| 9 | Empty, Loading, Error, Success | Durum bileşenleri (state components) |
| 10 | Hermes & AI Approval | AI öneri kartı, approval flow, agent status |

---

## 6. Teknoloji Staki

```
Laravel 10
  + Vue 3 (Composition API)
  + TypeScript
  + Inertia.js
  + Tailwind CSS
  + Vite
```

**Geçiş:** Mevcut Blade + Alpine.js korunur. Yeni ortak bileşenler Vue 3 + TypeScript ile yazılır. Kademeli adoptasyon.

---

## 7. Sprint 22 — Geliştirme Sırası

### Adım 1: Mevcut Ekran Envanteri (READ-ONLY)

Tüm mevcut admin/owner/blade ekranlarının navigasyon ve içerik envanterini çıkar.

**Çıktı:** `docs/ysos/SCREEN_INVENTORY.md`

### Adım 2: Property Command Center — Information Architecture

Kullanıcı akışı, menü yapısı, tab hiyerarşisi, mevcut ve hedef IA karşılaştırması.

**Çıktı:** `docs/ysos/INFORMATION_ARCHITECTURE.md`

### Adım 3: YDS v1.0 — Design Tokens

Tüm ortak tasarım atomları: renk, tipografi, spacing, shadow, border, motion, z-index, breakpoint.

**Çıktı:** `resources/js/ds/tokens/` dizini + token dokümantasyonu

### Adım 4: YDS v1.0 — Core Components

İlk referans bileşenler: Button, Input, Select, Badge, StatusIndicator, DataTable.

**Çıktı:** `resources/js/ds/components/` dizini

### Adım 5: Property Command Center — Referans Ekran (Blade + YDS)

İlk tam ekran: Property Genel Bakış. YDS bileşenlerinin canlı ortamda kullanıldığı ilk örnek.

**Çıktı:** Referans blade/page + tam ekran görünümü

---

## 8. Başarı Kriterleri ve Kabul Koşullar

| Hedef Kriter | Metrik / Kabul Koşulu |
|---|---|
| YDS Token Standardı | Tüm YDS token'ları tek dosyada tanımlı, mevcut Tailwind config ile uyumlu |
| Form Standardı | YDS Input/Field tüm varyantları dokümante edilmiş |
| Status Badge Standardı | Sistem genelinde tutarlı renk standardı tanımlı |
| Property Command Center IA | Envanter çıkarılmış, tab yapısı sabitlenmiş |
| Referans Ekran | Property Genel Bakış YDS bileşenleriyle inşa edilmiş |

---

## 9. Kapsam ve Kapsam Dışı

- **Kapsam İçi:**
  - YDS v1.0 token ve bileşen standardı
  - Property Command Center Information Architecture
  - İlk referans ekran (Property Genel Bakış)
  - Blade → Vue geçişi planı
- **Kapsam Dışı:**
  - Mevcut tüm Blade ekranların Vue'ya çevrilmesi
  - Backend API değişiklikleri
  - YDS v1.0 sonrası bileşen artırma (v1.1 için)
  - Mobil/responsive optimizasyonu (v1.0'da)

---

## 10. Riskler ve Bağımlılıklar

- **Risk 1 — YDS + Mevcut Tailwind Çakışması:** Mevcut bileşen stilleri YDS ile çakışabilir. **Azaltma:** YDS token'lar mevcut config'e override olarak eklenir, mevcut class'lar korunur.
- **Risk 2 — Ekran Envanteri Büyüklüğü:** Çok sayıda mevcut ekran envanter çıkarımını uzatabilir. **Azaltma:** Sadece Property Command Center ile ilgili ekranlar öncelikli alınır.
- **Bağımlılık 1 — Sprint 21 Governance Araçları:** `docs:governance:run` CI'da çalışır durumda olmalı.
- **Bağımlılık 2 — Premium Mediterranean UI:** Mevcut tasarım system'deki renk ve font kararları YDS'ye referans alınır.

---

## 11. Quality Gates

| Gate | Kriter | Araç |
|------|--------|------|
| Gate 1 | YDS Design Tokens: mevcut Tailwind config'e uyumlu | `npm run build` |
| Gate 2 | Property Command Center IA: tüm tab'lar ve navigasyon sabitlenmiş | SAAB incelemesi |
| Gate 3 | YDS Button + Input: tüm varyantlar dokümante edilmiş | Storybook veya mdBook |
| Gate 4 | Referans ekran: Property Genel Bakış mevcut Blade + YDS ile render ediliyor | `php artisan route:list` + browser |

---

## 12. Evidence Model v1.2 Uyumu

Sprint 22 teslimatları aşağıdaki paketlerle raporlanır:

1. **Implementation Evidence:** Commit hash, değişen dosyalar, YDS token dosyaları
2. **Execution Evidence:** Token derleme çıktısı, CI sonuçları, browser testleri
3. **Documentation Evidence:** YDS dokümantasyonu, SCREEN_INVENTORY.md, INFORMATION_ARCHITECTURE.md
4. **Certification Package:** SAAB değerlendirmesi, karar, açık riskler

---

## 13. Sonraki Yol Haritası (Sprint 23+)

```
Sprint 22 ✅ YDS v1.0
Sprint 23  Property Command Center — Rezervasyon Takvimi (Vue + YDS)
Sprint 24  Property Command Center — AI / Hermes Panel (Vue + YDS)
Sprint 25  Action Center / Operasyon (Vue + YDS)
Sprint 26  Mevcut karmaşık Blade ekranların kademeli Vue'ya geçişi
```

**Not:** Her sprint yeni bir ekran YDS ile modernize edilir. Basit ekranlar Blade'de kalır.
