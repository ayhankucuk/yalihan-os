# 📋 KLIO GÖREV ŞARTNAMESİ: KRONIK-1 Kanıt Tabanlı İnceleme & Model Uyumu

**Ajan:** Klio  
**Çalışma Alanı:** `kilo/kronik1-model-alignment`  
**Öncelik:** P0 / Yüksek  
**Tarih:** 2026-09-10 (güncellendi)  
**Kaynak:** `release-candidate/RC2` (ff89b98a → 1a0c14f0)  
**Referans Doktor Raporu:** 20 ✔ | 7 ▲ | 0 ✖ | ACCEPTABLE

---

## 🏗️ Worktree Açılış Komutu

```bash
git worktree add ../yalihan-os.kilo-model-alignment -b kilo/kronik1-model-alignment release-candidate/RC2
```

> ⚠️ ANA KURAL: Sadece izole worktree'de çalış. Ana `release-candidate/RC2` dalına **ASLA doğrudan commit yok**. Tüm commitler yalnızca `kilo/kronik1-model-alignment` branch'ine.

---

## 🎯 Görev Amacı

`fillable_alignment` ve `is_active` uyarılarını ezbere susturmak yerine, **şema–ilişki–model zincirini doğrulayarak** güvenli hale getirmek.

---

## ⚠️ Zorunlu Kanıt Protokolü (Ezbere Değişiklik Yasağı)

### Kanıt Zinciri Kuralı — HER değişiklik için 3 adım zorunlu:

```
Step 1: Fiziksel tablo şemasını doğrula
         ↓
Step 2: İlişki / subquery zincirini takip et
         ↓
Step 3: Model kodunu güncelle
```

---

## 📌 Adım 1: Ghost Field `is_active` İncelemesi (Kritik)

### 1a. Sorgunun Hedef Tablosunu Tespit Et

**Yapılacak:** 955–961. satırlardaki `where('is_active', ...)` sorgusunun hangi tabloya baktığını belirle.

**İhtimaller:**
- **İhtimal A:** Doğrudan `ilanlar` tablosu → kolon `aktiflik_durumu` olmalı
- **İhtimal B:** Bir `relation` / `subquery` / pivot tablo → o tablonun şemasını kontrol et
- **İhtimal C:** Polymorphic veya scope → ilişki zincirini takip et

**Doğrulama komutları:**
```bash
# İlgili migration dosyasını bul
grep -rn "is_active" database/migrations/ | head -20

# Şema kanıtı — fiziksel tabloda bu kolon var mı?
./scripts/tools/antigravity-schema-check.sh ilanlar is_active
./scripts/tools/antigravity-schema-check.sh ilanlar aktiflik_durumu

# İlişki zinciri — sorgu hangi tabloya JOIN ediyor?
grep -n "is_active" app/Models/Ilan.php
```

### 1b. Şema Kanıtı Topla

```bash
php artisan db:table ilanlar 2>/dev/null | grep -i "active\|durum" || echo "DB erişilemez"
```

> ⚠️ Eğer `ilanlar` tablosunda `is_active` kolonu gerçekten varsa ve `aktiflik_durumu` yoksa — BU BİR BUG DEĞİL, KANONIK İHLALDIR. Sorguyu değil, şemayı düzelt.
>
> ⚠️ Eğer `is_active` farklı bir tabloya ait (örneğin pivot veya `yayin_tipleri`), o zaman sorguyu değiştirme — yanlış tabloyu düzeltirsin.

### 1c. Karar Matrisi

| Şema bulgusu | Eylem |
|---|---|
| `ilanlar.is_active` mevcut, `aktiflik_durumu` yok | Migration gerekli — raporla, düzeltme yapma |
| `ilanlar.aktiflik_durumu` mevcut, `is_active` yok | Modeldeki `is_active` → `aktiflik_durumu` güncelle |
| `is_active` farklı bir tabloda | Sorguyu değiştirme — o tabloyu incele |
| Her iki kolon da mevcut | Kanonik olana yönlendir, eskiyi kaldır |

---

## 📌 Adım 2: `YayinTipi.php` `$fillable` İncelemesi

### 2a. Fiziksel Tablo Şemasını Doğrula

```bash
./scripts/tools/antigravity-schema-check.sh yayin_tipleri adi
./scripts/tools/antigravity-schema-check.sh yayin_tipleri name
./scripts/tools/antigravity-schema-check.sh yayin_tipleri isim
```

**Fiziksel tablo kanıtı olmadan `$fillable` değişikliği YAPILMAZ.**

### 2b. `$fillable` Eşitleme

Eğer tabloda `adi` kolonu varsa → `$fillable` dizisinde `adi` olmalı.
Eğer tabloda `name` kolonu varsa → `$fillable` dizisinde `name` olmalı.

---

## 📌 Adım 3: Doğrulama

```bash
php artisan system:env-drift-guard
```

**Beklenen sonuç:**
```
[PASS] fillable_alignment
[PASS] Model Ghost Field (Ilan)  # ghost field uyarısı kaybolmalı
```

---

## 📌 Adım 4: Regresyon Testi (Zorunlu)

```bash
php artisan test --filter=Ilan
```

Her model değişikliğinden sonra ilgili testler çalıştırılır. Regresyon tespit edilirse değişiklik reddedilir.

---

## ✅ Başarı Kriteri

- [ ] Her değişiklik için **Model – İlişki – Şema kanıtı** raporda mevcut
- [ ] İlgili Feature/Unit testleri `TEST_VERIFIED`
- [ ] `php artisan system:env-drift-guard` çıktısında hedef uyarı `pass`
- [ ] Tüm değişiklikler `kilo/kronik1-model-alignment` branch'inde commit'li
- [ ] Regresyon tespit edildiğinde değişiklik reddedilir (bypass yok)

---

## 🔒 Kısıtlamalar

1. Ana RC2 dalına **yazma yok**
2. Migration dosyası **oluşturma yok** (sadece model kodu)
3. Veritabanı **değiştirme yok**
4. Kanıtsız değişiklik **reddeder** — her hamle schema kanıtı gerektirir
5. Relation/pivot tablolarındaki `is_active` **değiştirilmez** — sadece `ilanlar` hedef alınır
