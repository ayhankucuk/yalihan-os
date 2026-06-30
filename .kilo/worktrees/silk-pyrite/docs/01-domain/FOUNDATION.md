# YALIHAN OS — Kuruluş Vizyonu

> **Tarih:** 2026-06-27
> **Oturum:** 44
> **Unvan:** Kuruluş Oturumu
> **Karar:** Faz 2 başlıyor — ürün vizyonu netlești.

---

## Oturum 44 — Unvan: "Kuruluş Oturumu"

> Bu oturumun sonunda sadece kod değil, ürünün düşünme biçimi değiști.

---

## YALIHAN OS Tanımı

```
YALIHAN OS, AI destekli bir Gayrimenkul İşletim Sistemi'dir.

CRM bunun bir modülüdür.
İlan bunun bir modülüdür.
Airbnb bunun bir modülüdür.
Takvim bunun bir modülüdür.
Finans bunun bir modülüdür.

AI ise bunların ortak zekâ katmanıdır.
```

### Bu Ayrım Neden Önemli?

> AI modül DEĞİL — her modülün içine ișlemiș ortak zekâ katmanıdır.

```
CRM
  └── AI
Portföy
  └── AI
Airbnb
  └── AI
Finans
  └── AI

AI hiçbir verinin sahibi değildir.
Sadece analiz eder ve öneri üretir.
```

---

## AI Mimarisi — Netlești

```
                 YALIHAN OS
           Business Layer
                 │
           Domain Layer
                 │
           AI Orchestrator
                 │
      ┌──────────┼──────────┐
      │          │          │
  OpenClaw     OpenAI     Gemini
      │
  NotebookLM
      │
n8n / Telegram / Drive
```

**OpenClaw artık sistemin merkezi değil — çalıştırıcılarından biri (executor).**

Bu, gelecekte farklı AI motorlarına geçiși de kolaylaştırır.

---

## Geliştirme Zinciri — Değişmez Kural

> Artık her yeni özellik bu zinciri takip etmeli.

```
İş Problemi
      ↓
Domain Model
      ↓
Use Case
      ↓
Acceptance Criteria      ← ⬅️ YENİ EKLENDİ
      ↓
Implementation
      ↓
Test
      ↓
Demo                  ← ⬅️ YENİ EKLENDİ
```

---

## Sprint Definition of Done — Yeni Kural

> Bir sprint ancak şu beș madde ile tamamlanmıș sayılır.

| # | Madde | Açıklama |
|---|-------|-----------|
| 1 | Domain Model güncellendi | Gerekiyorsa entity/ilişki eklendi |
| 2 | Kod tamamlandı | Sprint hedefine ulaşıldı |
| 3 | Testler geçti | CI pipeline temiz |
| 4 | Demo senaryosu çalıştı | Gösterilebilir durumda |
| 5 | **Gerçek kullanıcı kullandı** | ⬅️ **En önemlisi** |

**Son madde en önemlisi:** Sadece "çalışıyor" değil — gerçek insan gerçekten kullanabiliyor.

---

## Sprint 3.4 Demo Senaryosu

> Sprint 3.4 sonunda tek bir demo istenir.

```
1. Danışman giriş yapar.
2. Yeni portföy oluşturur.
3. Fotoğraf yükler.
4. AI analiz başlatır.
5. Sistem eksikleri listeler.
6. AI ilan açıklamasını üretir.
7. "Yayına Hazır" skoru gösterilir.
8. Taslak kaydedilir.

Henüz yayınlama yok. (Sprint 3.5'te.)
```

**Eğer bu senaryo çalışıyorsa = Sprint 3.4 başarılı.**

---

## Faz 1 Başarısı

> Faz 1'de en büyük başarı:

**Mühendislik disiplini oluşturuldu.**

---

## Faz 2 Başarı Ölçütü

> Her sprint sonunda sorulacak soru:

```
"Bu sprint sonunda YALIHAN Emlak ekibi
ertesi sabah gerçekten kullanabileceği
yeni bir yetenek kazanıyor mu?"
```

**Eğer bu sorunun cevabı her sprint sonunda "evet" olursa:**

YALIHAN OS sadece iyi tasarlanmış bir yazılım OLMAYACAK;
gerçek operasyonu dönüştüren,
sürekli değer üreten bir ürün HALİNE GELECEK.

**Bundan sonraki tüm kararların pusulası bu olmalı.**

---

## Ürün Vizyonu — Kısa Tanım

```
YALIHAN OS = AI destekli Gayrimenkul İşletim Sistemi

• CRM = Modül
• İlan = Modül
• Airbnb = Modül
• Takvim = Modül
• Finans = Modül
• AI = Ortak Zekâ Katmanı (her yerde)
```

---

*Kuruluş Vizyonu: 2026-06-27*
*Oturum: 44*
*Karar Verildi: Faz 2 başlıyor — ürün vizyonu netlești.*
