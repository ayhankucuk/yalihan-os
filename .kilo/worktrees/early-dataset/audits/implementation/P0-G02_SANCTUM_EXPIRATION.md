# IMPLEMENTATION PACKAGE — P0-G02
## expiration: null → 10080
### config/sanctum.php — Token Lifecycle

**Bulgu:** `config/sanctum.php` — Token expiration `null`
**Severity:** CRITICAL
**Status:** VERIFIED
**Audit Ref:** `audits/SECURITY_GAP_ANALYSIS.md` — SEC-001, SEC-002

---

## 1. DOĞRULAMA

### Dosya
`/Users/macbookpro/dev/yalihan2026/config/sanctum.php`

### Satır
`50`

### Mevcut Kod
```php
// config/sanctum.php — SATIR 50
'expiration' => null,
```

### Token Üretim Noktaları
| Dosya | Satır | Kod | Expiration Kullanımı |
|-------|-------|-----|---------------------|
| `app/Http/Controllers/Api/V2/AuthController.php` | 62 | `$kullanici->createToken('api-token')` | Config'e bağlı (`null` = süresiz) |
| `app/Http/Controllers/Api/V2/AuthController.php` | 105 | `$user->createToken('api-token')` | Config'e bağlı (`null` = süresiz) |

### Risk Senaryosu
```
Mevcut durum: Token expiration = null
    ↓
Token hiçbir zaman geçersiz olmaz
    ↓
Senaryo 1: Token sızdırılır (log, XSS, MITM)
    → Saldırgan süresiz erişir
    → AI cüzdan bakiyesi çalınabilir
    → Mevcut kullanıcının tüm verilerine erişim

Senaryo 2: Kullanıcı cihaz kaybeder
    → Token cihazda kalır
    → Cihaz bulan kişi süresiz giriş yapabilir
    → Uzaktan token iptali MÜMKÜN DEĞİL (revocation mekanizması yok)
```

---

## 2. ÖNERİLEN DEĞİŞİKLİK

### Değişiklik A — Minimum (önerilen)
```php
// config/sanctum.php — SATIR 50
'expiration' => 10080,  // 7 gün (7 × 24 × 60 dakika)
```

### Değişiklik B — Ortam değişkeni (opsiyonel)
```php
// config/sanctum.php — SATIR 50
'expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION_MINUTES', 10080),
```

`.env` üzerinden:
```
SANCTUM_TOKEN_EXPIRATION_MINUTES=10080
```

---

## 3. MOBİL / CLIENT ETKİ ANALİZİ — KRİTİK

### Mevcut Durum Tespiti

| Alan | Bulundu | Not |
|------|---------|-----|
| `refreshToken()` çağrısı | ❌ Bulunamadı | Kod tabanında refreshToken implementasyonu yok |
| Token expiry middleware | ⚠️ Kısmi | Sanctum middleware `expiration` kullanıyor ama `null` = kontrol atlanıyor |
| Refresh endpoint | ❌ Bulunamadı | `/api/v1/auth/refresh` yok |
| Client-side token storage | ❌ Görünmüyor | Backend token üretiyor, client saklıyor |

### Mobil Client Bağımlılık Matrisi

| Client Tip | Token Saklama | Refresh Kapasitesi | Etki Risk |
|-----------|--------------|-------------------|-----------|
| V2 Mobile API (AuthController) | Plain text token döndürülüyor | ❌ Yok | **YÜKSEK** |
| Web SPA | LocalStorage/SessionStorage | ❌ Belirsiz | **YÜKSEK** |
| Admin Dashboard | Cookie-based | ⚠️ Belirsiz | **ORTA** |
| Third-party integrations (n8n, webhooks) | API key/token | ⚠️ n/a | **ORTA** |

### Ciddi Uyarı
> **P0-G02'yi deploy etmeden önce mobil client token refresh kapasitesi doğrulanmalıdır.**
>
> Eğer mobil client'lar token refresh implementasyonuna sahip DEĞİLSE:
> - 7 gün sonra TÜM kullanıcılar logout olacak
> - Kullanıcılar tekrar login yapmak zorunda kalacak
> - Session kaybı = iş kesintisi
>
> **Bu kritik bir UXBreaking change olabilir.**

---

## 4. MOBİL CLIENT DOĞRULAMA PROTOKOLÜ

### Adım 1: Client türlerini tespit et
```bash
# V2 API kullanıcılarını analiz et
grep -r "api/v2" --include="*.tsx" --include="*.jsx" --include="*.swift" --include="*.kt" .
# veya
# API log'larından X-API-Client header'ını kontrol et
```

### Adım 2: Token refresh endpoint var mı?
```bash
grep -r "refresh|renewToken|token.*renew" --include="*.php" routes/api/
# Beklenen: /api/v1/auth/refresh veya benzeri
```

### Adım 3: Client refresh kapasitesi belirsizse — GEÇİŞ STRATEJİSİ

**Strateji A — Uzatılmış geçiş (önerilen)**
1. `expiration: 43200` (30 gün) ile başla
2. Mobil client'ı gözle (2 hafta)
3. Sorun yoksa `expiration: 10080` (7 güne düşür)
4. Sonra `expiration: 1440` (1 gün) — optimum güvenlik

**Strateji B — Token revocation ile paralel**
1. Token revocation mekanizması kur (P1-G09)
2. Stale token'ları temizle
3. Sonra expiration düşür

**Strateji C — Breaking change kabul edilebilirse**
1. Değişikliği deploy et
2. Kullanıcıları bilgilendir
3. Mobil client'ı token refresh ile güncelle

---

## 5. TOKEN REVOCATION EKLEMESI

P0-G02 ile birlikte **P1-G09** (token revocation) planlanmalı. İkisi birlikte:

```
expiration: 10080  (7 gün)     ← otomatik sona erme
revokeOtherDevices()           ← Manuel iptal
revokeCurrentDevice()           ← Oturum kapatma
```

### Token İptal Kodu Eklenmeli
Token üretim sonrası kullanıcıya iptal bilgisi verilmeli:
```json
{
  "token": "...",
  "expires_at": "2026-07-06T14:36:00Z",  // ← YENİ
  "token_type": "Bearer"
}
```

---

## 6. GERİYE DÖNÜK UYUMLULUK ETKİSİ

| Alan | Etki | Not |
|------|------|-----|
| Mimari bozulması | **YOK** | Sadece token lifetime değişir |
| API breaking change | **OLASI** | Mevcut token'lar 7 gün sonra geçersiz |
| Migration gerekli | **YOK** | Sadece config |
| Cache etkisi | **Evet** | `config:cache` gerekli |
| Geri alma | **Kolay** | `expiration: null` geri konur |

---

## 7. MIGRATION PLANI

```bash
# 1. Test ortamında test et
php artisan config:cache

# 2. Review: config/sanctum.php
grep -n "expiration" config/sanctum.php

# 3. Development ortamında test
# - Login ol, token al
# - 1 dk beklet, tekrar dene (normalde çalışır)
# - Artificial: token'ı DB'den sil, tekrar dene (401 beklenir)

# 4. Staging'de 1 hafta gözlem

# 5. Production
php artisan config:cache
```

---

## 8. ROLLBACK PLANI

```bash
# 1. Anında rollback
git checkout HEAD -- config/sanctum.php

# 2. Config cache temizle
php artisan config:clear
php artisan cache:clear

# 3. Etki: Mevcut token'lar tekrar süresiz geçerli olur
```

---

## 9. TEST PLANI

### Değişiklik Öncesi Baseline
```bash
# 1. Mevcut token expiration analiz et
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$token = \$user->createToken('test');
echo 'Default expiration: ' . config('sanctum.expiration');
"

# 2. Auth test'lerini çalıştır
php artisan test --filter=AuthTest
```

### Değişiklik Sonrası
```bash
# 1. Config cache yenile
php artisan config:cache

# 2. Yeni token'ın expiration ile oluştuğunu doğrula
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$token = \$user->createToken('test');
echo 'Token expires_at: ' . \$token->accessToken->expires_at;
echo 'Expiration (minutes): ' . config('sanctum.expiration');
"

# 3. Auth test'leri — TÜMÜ geçmeli
php artisan test --filter=AuthTest

# 4. Mobil API testleri
php artisan test --filter=MobileTest

# 5. Manuel: Eski token (üretimden) çalışıyor mu?
#    → Çalışmaya devam etmeli (mevcut token'lar cached)
#    → 7 gün sonra otomatik geçersiz
```

### Başarı Kriterleri
- [ ] Token expires_at artık dolu (null değil)
- [ ] `php artisan test --filter=AuthTest` → PASS
- [ ] `php artisan test --filter=MobileTest` → PASS
- [ ] Mobil client login → token alınıyor → 7 gün içinde çalışıyor
- [ ] Eski production token'lar (varsa) → 7 gün sonra 401

---

## 10. BAŞARI KRİTERİ

| Kriter | Hedef | Doğrulama |
|--------|-------|-----------|
| `expiration` değeri | `10080` (7 gün) veya env | `grep -n "expiration" config/sanctum.php` |
| Token expires_at | Dolu datetime | Tinker ile doğrula |
| Auth test | PASS | `php artisan test --filter=AuthTest` |
| Mobil client | Logout yok (refresh var) veya planned | Client review |
| Token revocation | Implementasyon planı mevcut | P1-G09 backlog'ta |

---

## 11. ÖNCELİK SIRASI: 2 / 3

> **Dikkat:** Bu değişiklik mobil client etkisi YÜKSEK.
> P0-G01 (`after_commit`) en az riskli — önce o yapılmalı.
>
> **Önerilen sıra:**
> 1. P0-G01 (`after_commit`) — 1 saat, risksiz
> 2. P0-G02 (`Sanctum expiration`) — 30 dakika, orta risk
> 3. P0-G03 (IDOR) — 2 saat, hassas

**Tahmini emek:**
- Değişiklik: 30 dakika
- Client doğrulama: 2-4 saat (client tipine bağlı)
- Toplam: 3-5 saat
