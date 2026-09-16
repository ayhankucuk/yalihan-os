# Araştırma Raporu — Oturum 150-151

**Tarih:** 2026-09-04
**Kapsam:** 9/9 Remediation Backlog tamamlandı, 5 araştırma alanı incelendi.

---

## 1. Test Coverage Gap (ARAŞTIRMA-1)

**Durum:** Test suite çalıştırıldı — sonuç bekleniyor (20+ dakika).
**Önceki veri:** 301 fail test (2026-05-16 analizinden, 4 ay eski).
**Not:** Güncel fail count için `php artisan test` tamamlanmalı.

---

## 2. Secret Exposure (ARAŞTIRMA-2)

**Sonuç:** ✅ TEMİZ

| Kontrol | Sonuç |
|---------|-------|
| Config dosyaları | Tüm secret'lar `env()` ile alınıyor, hardcoded değer yok |
| `.env.example` | Gerçek secret yok (sk-, ghp_, AKIA, Bearer, private_key — hiçbiri yok) |
| `.env` git history | `.env` git history'de yok (.gitignore'da) |
| `storage/logs/` | Security log dosyalarında gerçek secret yok — sadece "yapılandırılmamış" hata mesajları |
| BACKLOG-7 fix | SecurityMiddleware log maskeleme çalışıyor — eski loglarda bile secret yok |

**Sonuç:** Secret exposure riski düşük. BACKLOG-7 fix'i ve Antigravity'nin secret-scan.sh pre-commit hook'u koruma sağlıyor.

---

## 3. Migration Drift (ARAŞTIRMA-3)

**Sonuç:** ⚠️ KRİTİK BULGU — Split-Brain Tablo

### 3.1 Çift Migration — AI Provider Profiles

İki migration aynı kavram için iki ayrı tablo yaratıyor:

| Migration | Tablo | Kolon Dili | Kullanan Model | Kullanan Service |
|-----------|-------|------------|----------------|------------------|
| `2026_01_17_093641` | `ai_provider_profiles` | İngilizce (provider, window, accept_rate) | `AiProviderProfile` | `ProviderOptimizationService`, `AiRecomputeProviderProfiles` |
| `2026_01_17_093700` | `ai_saglayici_profilleri` | Türkçe (saglayici, ort_gecikme_ms) | `AiSaglayiciProfili` | `ProviderSelectorService` |

**Risk:** İki service aynı kavram için farklı tablo kullanıyor — veri tutarsızlığı, çift kayıt, skor hesaplama hatası.
**Context7 Violation:** `ai_saglayici_profilleri` Türkçe tablo adı — `ai_provider_profiles` olmalı.
**Çözüm:** `AiSaglayiciProfili` modelini ve `ai_saglayici_profilleri` tablosunu deprecate et, `AiProviderProfile`'a migrate et.

### 3.2 kapak_mi vs kapak_fotografi Schema Drift

| Yer | Kolon Adı |
|-----|-----------|
| Migration (`2024_01_01_000000`) | `kapak_mi` |
| Model (`IlanFotografi`) | `kapak_fotografi` (fillable + cast) |
| Tüm kod (41 dosya) | `kapak_fotografi` |

**Risk:** Migration dosyası güncel değil — production'da kolon rename edilmiş olabilir ama migration dosyası güncellenmemiş.
**Çözüm:** Migration dosyasını `kapak_mi` → `kapak_fotografi` olarak güncelle veya rename migration ekle.

---

## 4. Naming Authority Drift (ARAŞTIRMA-4)

**Sonuç:** ✅ FIX'LENDİ (3 dosya)

### 4.1 sira → display_order (FIX'LENDİ)

| Dosya | Satır | Eski | Yeni | Commit |
|-------|-------|------|------|--------|
| `IlanPhotoService.php` | 119 | `update(['sira' => ...])` | `update(['display_order' => ...])` | `a3d53d4` |
| `IlanPublicResource.php` | 98-101 | `$foto->sira`, `sortBy('sira')` | `$foto->display_order`, `sortBy('display_order')` | `1d360dd` |
| `IlanInternalResource.php` | 124-129 | `$foto->sira`, `sortBy('sira')` | `$foto->display_order`, `sortBy('display_order')` | `1d360dd` |

**Not:** `CortexVisionService.php:87-88` — `sira` kolonu var mı kontrol edip `sira` veya `display_order` kullanıyor. Bu defensive pattern (doğru).

### 4.2 Diğer sira Kullanımları (Farklı Tablolar — OK)

Aşağıdaki `sira` kullanımları farklı tablolarda, `ilan_fotograflari` ile ilişkisiz:
- `ProjeGorsel` tablosu — `sira` kolonu var (doğru)
- `IlanNoGenerator` — `sira` ilan no üretiminde kullanılıyor (doğru)
- `SmartFieldGenerationService` — `orderBy('sira')` farklı tablo (doğru)

---

## 5. updatePhotoSequence Bug (ARAŞTIRMA-5)

**Sonuç:** ✅ FIX'LENDİ (commit `a3d53d4`)

`IlanPhotoService::updatePhotoSequence()` metodu `sira` kolonuna update yapıyordu ama `ilan_fotograflari` tablosunda `sira` kolonu yok, `display_order` var. Düzeltildi.

---

## Özet

| Araştırma | Durum | Aksiyon |
|-----------|-------|---------|
| Test Coverage Gap | Beklemede | Test suite sonucu bekleniyor |
| Secret Exposure | ✅ TEMİZ | Ek aksiyon gerekmez |
| Migration Drift | ⚠️ KRİTİK | `ai_saglayici_profilleri` split-brain çözülmeli |
| Naming Drift | ✅ FIX'LENDİ | 3 dosya düzeltildi (commit a3d53d4, 1d360dd) |
| updatePhotoSequence | ✅ FIX'LENDİ | commit a3d53d4 |

## Yeni Teknik Borç Kayıtları

| ID | Açıklama | Öncelik |
|----|----------|---------|
| TD-13 | `ai_saglayici_profilleri` split-brain tablo — `ai_provider_profiles` ile birleştir | P1 |
| TD-14 | `kapak_mi` → `kapak_fotografi` migration drift — migration dosyası güncellenmeli | P2 |
