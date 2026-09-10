# 📋 KLIO GÖREV ŞARTNAMESİ: KRONIK-1 Model İlişkilendirme

**Ajan:** Klio  
**Çalışma Alanı:** `kilo/kronik1-model-alignment`  
**Öncelik:** P0 / Yüksek  
**Tarih:** 2026-09-10  
**Kaynak:** `release-candidate/RC2` (ff89b98a)  
**Referans Doktor Raporu:** 20 ✔ | 7 ▲ | 0 ✖ | ACCEPTABLE

---

## 🏗️ Worktree Açılış Komutu

```bash
git worktree add ../yalihan-os.kilo-model-alignment -b kilo/kronik1-model-alignment release-candidate/RC2
```

> ⚠️ ANA KURAL: Bu worktree'de çalış. Ana `release-candidate/RC2` dalına **ASLA doğrudan yazma**, **ASLA commit atma**. Tüm commitler yalnızca `kilo/kronik1-model-alignment` branch'ine.

---

## 🎯 Görev Amacı

`fillable_alignment` ve `Ilan.php` modelindeki Context7 hayalet alan uyumsuzluklarını temizlemek.

---

## 📌 Adım 1: `app/Models/Ilan.php` Düzeltmesi

### 1a. Ghost Field `is_active` → `aktiflik_durumu`

**Sorun:** `Ilan.php`'de `is_active` referansı — veritabanında bu kolon yok, olması gereken `aktiflik_durumu`.

**Teşhis:**
```bash
grep -n "is_active" app/Models/Ilan.php
```

**Bilinmesi gereken:** `aktiflik_durumu` INT, Context7 kanonik isim.
- `is_active = true` → `aktiflik_durumu = 1`
- `is_active = false` → `aktiflik_durumu = 0`

**Düzeltilecek satırlar (tahmini):** 955–961. satırlar arası `where('is_active', ...)` clauses.

### 1b. `$fillable` Dizisi Eşitleme

**Sorun:** `$fillable` dizisinde veritabanında olmayan kolonlar (ghost fields) var.

**Doğrulama komutu:**
```bash
php artisan guard:schema --model=Ilan
```

**Beklenti:** `$fillable`'daki her alan veritabanında mevcut olmalı. Olmayan varsa çıkar.

---

## 📌 Adım 2: `app/Models/YayinTipi.php` Düzeltmesi

**Sorun:** `$fillable` içindeki `adi` / `name` uyuşmazlığı.

**Kontrol:**
```bash
grep -n "fillable" app/Models/YayinTipi.php
grep -n "adi\|name" app/Models/YayinTipi.php | head -20
```

**Kanonik kural:** Context7'ye göre Türkçe alan adları. `adi` → `adi` (Türkçe), `name` İngilizce olarak yalnızca seed/migration'da meşru.

---

## 📌 Adım 3: Doğrulama

```bash
php artisan system:env-drift-guard
```

**Beklenen sonuç:**
```
[PASS] fillable_alignment
[PASS] Model Ghost Field (Ilan)  # is_active uyarısı kaybolacak
```

---

## ✅ Başarı Kriteri

- `php artisan system:env-drift-guard --json` çıktısında `fillable_alignment` durumu `pass`
- Ghost field `is_active` referansı `app/Models/Ilan.php`'den çıkarılmış
- `$fillable` dizisi DB şeması ile tam eşleşiyor
- Tüm değişiklikler `kilo/kronik1-model-alignment` branch'inde commit'li

---

## 📊 Bağımsız Doğrulama

```bash
cd /Users/macbookpro/repos/yalihan-os
bash scripts/tools/yalihan-doctor.sh
```

**Beklenti:** 3 uyarı çözülmüş → toplam uyarı 7'den 4'e düşmüş.

---

## 🔒 Kısıtlamalar

1. Ana RC2 dalına **yazma yok**
2. Migration dosyası **oluşturma yok** (yalnızca model kodu düzeltülecek)
3. Veritabanı **değiştirme yok**
4. Unutulan mock/API çağrısı **bırakma yok** — test edilebilir kod
