# YALIHAN OS — Mimari Sorunlar Özeti

**Tarih:** 2026-01-08
**Kaynak:** Sistem analizi

---

## 🚨 KRITIK SORUNLAR (24 adet)

### 1. SPLIT BRAIN — Aynı Kavram Birden Fazla Yerde

| Kavram | Konum 1 | Konum 2 |
|--------|---------|---------|
| Ilan Model | `app/Models/Ilan.php` | `app/Models/V2/Ilan.php` |
| User Model | `app/Models/User.php` | `app/Models/V2/User.php` |
| IlanFactory | `database/factories/IlanFactory.php` | `database/factories/V2/IlanFactory.php` |
| Rate Limiting | `AIRateLimitMiddleware.php` | `EnsureAiRateLimit.php` | `ApiRateLimitMiddleware.php` |

### 2. GOD CLASS — Aşırı Büyük Dosyalar

| Dosya | Satır |
|-------|-------|
| `ProcessGuestMessageJob.php` | 9,688 |
| `YalihanCortex.php` | 2,409 |
| `admin/ilanlar/edit.blade.php` | 2,127 |
| `AIController.php` | 1,114 |

### 3. DUPLICATE — Gereksiz Dosyalar

- Boş route dosyaları: `routes/location.php`, `routes/web-clean.php`
- Boş view dizinleri: `resources/views/auth/`, `resources/views/crm/`
- V2 route explosion: 4+ dosya

---

## 🟠 YÜKSEK ÖNCELİKLİ SORUNLAR (14 adet)

### 4. NAMING TUTARSIZLIĞI

| Pattern | Sorun |
|---------|-------|
| `analytics/` vs `analitik/` | Aynı içerik farklı isim |
| `finance/` vs `finans/` | Aynı içerik farklı isim |
| `users/` vs `kullanicilar/` | Aynı içerik farklı isim |

### 5. LEGACY/DEPRECATED İşaretli Kod

- 11+ LEGACY Service
- 2 Deprecated Models
- 20+ TODO/SKIP Test

### 6. MİDDLEWARE EXPLOSION

52 toplam middleware:
- 5 rate limiting middleware (SPLIT)
- 3 OpenClaw middleware (SPLIT)

### 7. VIEW DİZIN PATLAMASI

```
81 alt dizin resources/views/admin/ altında
├── analytics/ + analitik/
├── finance/ + finans/
├── users/ + kullanicilar/
```

---

## 🟡 ORTA ÖNCELİKLİ SORUNLAR (12 adet)

### 8. TEST COVERAGE DENGESİZLİĞİ

- Toplam Test: 617
- Coverage tahmini: ~25%
- Controller başına düşen test: ~1.7

### 9. SERVICE EXPLOSION

- 90+ dizin app/Services/ altında
- 30 "SINGLE" dizin (1 dosya + 1 dizin pattern)
- PropertyService (0 referans, ORPHAN)

---

## 📊 SORUN İSTATİSTİKLERİ

| Kategori | Kritik | Yüksek | Orta | Toplam |
|----------|--------|--------|------|--------|
| SPLIT BRAIN | 10 | 2 | 4 | 16 |
| GOD CLASS | 9 | 0 | 0 | 9 |
| DUPLICATE | 3 | 4 | 3 | 10 |
| NAMING | 0 | 3 | 1 | 4 |
| LEGACY | 0 | 2 | 2 | 4 |
| EXPLOSION | 2 | 3 | 2 | 7 |
| **TOPLAM** | **24** | **14** | **12** | **50** |

---

## 🎯 ÖNERİLEN ÖNCELİK SIRASI

### Bu Sprint (1-2 hafta)
1. V2/ modelleri kaldır veya canonical seç
2. Rate limiting middleware'leri birleştir
3. Boş dosyaları sil
4. ProcessGuestMessageJob'ı ayır

### Bu Çeyrek (1-3 ay)
5. UserController duplicate'leri birleştir
6. God class'ları refactor et
7. View dizin split'lerini birleştir
8. Contract/Enum dizinlerini consolidate et

### Bu Yıl (6-12 ay)
9. Test coverage artır (25% → 60%+)
10. Legacy kodları migrate et veya kaldır
11. Service architecture'ı sadeleştir

---

## 📈 MİMARİ SAĞLIK SKORU

```
Genel Skor: 3.8 / 10

Kod Organizasyonu:    3/10
Naming Tutarlılığı:    3/10
Test Coverage:         5/10
Service Design:        2/10
Controller Design:     3/10
Async Design:          5/10
Middleware Design:     2/10
Event Design:          6/10
```

---

**Not:** Toplam ~2,500+ PHP dosya, ~150,000+ kod satırı
## Model Base Class Tutarsızlığı

8 model BaseModel yerine Model'den extends ediyor:
- ActionEvidence
- Address
- DanismanYorum
- GuestMessage
- OwnerLoginToken
- PropertyWorkspace
- WorkspaceExecution
- BaseModel (tanım dosyası)

Öneri: Bu modeller BaseModel'e geçmeli.
Priorite: Düşük



---

## 🟢 DÜŞÜK ÖNCELİKLİ SORUNLAR

### Model Base Class Tutarsızlığı

8 model BaseModel yerine Model'den extends ediyor:
- ActionEvidence, Address, DanismanYorum, GuestMessage, OwnerLoginToken, PropertyWorkspace, WorkspaceExecution

Priorite: Düşük
