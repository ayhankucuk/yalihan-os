# ═══════════════════════════════════════════════════════════════════════════════
# 📋 YALIHAN APEX CUTOVER CHECKLIST
# ═══════════════════════════════════════════════════════════════════════════════
#
# Amaç: yalihanemlak.com.tr + www → Hetzner YALIHAN OS
# Sıra: Deployment → Nginx vHost → DNS Cutover → Smoke Test → Meta
#
# ⚠️  DNS DEĞİŞTİRME ADIMINA KADAR MEVCUT SITE ÇALIŞMAYA DEVAM EDER
#
# ═══════════════════════════════════════════════════════════════════════════════

## ✅ ADIM 0 — Pre-Flight (Yerinde Doğrulama)

```bash
# 1. Legal sayfaların endpointleri doğru
curl https://api.yalihanemlak.com.tr/legal/privacy  # → HTTP 200
curl https://api.yalihanemlak.com.tr/legal/terms    # → HTTP 200
curl https://api.yalihanemlak.com.tr/legal/data-deletion  # → HTTP 200

# 2. Commit'lerin production branch'inde olduğunu doğrula
git log origin/integration/era-v-phase2a-e01 --oneline | grep -E "legal|apex"
# Beklenen:
#   0c21387 feat(apex): add coming-soon landing page for yalihanemlak.com.tr cutover
#   4bc9c93 chore(ops): add Cloudflare legal pages bypass script for Meta compliance
#   1213a14 feat(legal): add Meta App Publish compliance pages

# 3. Mevcut API regression
curl https://api.yalihanemlak.com.tr/api/v1/health/database  # → HTTP 200
```

---

## ✅ ADIM 1 — Rollback Evidence Kaydet (DNS Değişikliği ÖNCESİ)

> ⚠️ Bu adım DNS değişikliği YAPILMADAN önce mutlaka tamamlanmalı.

```bash
# Cloudflare Dashboard'dan kaydet (manuel):

# A kayıtları (mevcut origin IP)
yalihanemlak.com.tr     → <MEVCUT_ORIGIN_IP>
www.yalihanemlak.com.tr → <MEVCUT_ORIGIN_IP>

# MX, TXT, SPF kayıtları NOT: Bu kayıtlara dokunulmayacak
# SPF: ip4:157.180.116.63 eklenmeyecek (karar: web sunucusu mail göndermeyecek)

# Cloudflare:
# 1. Cloudflare Dashboard → yalihanemlak.com.tr → DNS
# 2. Mevcut A kayıtlarının IP'lerini bir yere not edin
# 3. "Proxy status" (DNS-only / Proxied) durumunu not edin
# 4. Rollback gerektiğinde bu IP'lere geri dönülecek
```

**Cloudflare Panel Kısayolu:** https://dash.cloudflare.com/yalihanemlak.com.tr/dns

**Rollback Komutu (Cloudflare API ile):**
```bash
# Cloudflare API erişimi varsa:
curl -X PUT "https://api.cloudflare.com/client/v4/zones/{ZONE_ID}/dns_records/{RECORD_ID}" \
  -H "Authorization: Bearer $CF_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"type":"A","name":"yalihanemlak.com.tr","content":"<ROLLBACK_IP>","proxied":true}'
```

---

## ✅ ADIM 2 — Production Deployment (Hetzner)

> Deployment mekanizması: `/opt/yalihan-os-production/` release yapısı
> NOT: /var/www/yalihanai KULLANILMAYACAK

```bash
# Hetzner sunucusuna SSH ile gir
ssh root@<HETZNER_SERVER_IP>

# Mevcut release'i kontrol et
ls -la /opt/yalihan-os-production/current
ls -la /opt/yalihan-os-production/releases/ | tail -5

# Yeni release checkout (integration/era-v-phase2a-e01)
cd /opt/yalihan-os-production
git fetch origin
git checkout origin/integration/era-v-phase2a-e01

# veya release script kullan (varsa)
# /opt/yalihan-os-production/scripts/deploy.sh

# Artisan komutları
cd /opt/yalihan-os-production/current
php artisan migrate --force
php artisan cache:clear
php artisan route:clear
php artisan config:cache  # production'da

# VEYA deploy-production.sh scripti kullan
# bash /opt/yalihan-os-production/scripts/deploy-production.sh
```

**Deploy Commit'leri:**
| Commit | Açıklama |
|--------|----------|
| `0c21387` | Apex landing page |
| `4bc9c93` | Cloudflare legal bypass script |
| `1213a14` | Legal pages (privacy, terms, data-deletion) |

---

## ✅ ADIM 3 — Nginx vHost Kurulumu (Hetzner)

> api.yalihanemlak.com.tr vHost DEĞİŞTİRİLMEYECEK

```bash
# Hetzner sunucusunda root olarak

# 1. apex-vhost-deploy.sh scriptini sunucuya kopyala
scp scripts/ops/apex-vhost-deploy.sh root@<HETZNER_IP>:/tmp/apex-vhost-deploy.sh

# 2. Sunucuda çalıştır (TLS olmadan önce test için)
sudo bash /tmp/apex-vhost-deploy.sh --dry-run
sudo bash /tmp/apex-vhost-deploy.sh --test

# 3. TLS sertifikası yoksa, Let's Encrypt ile al
sudo certbot --nginx -d yalihanemlak.com.tr -d www.yalihanemlak.com.tr \
  --non-interactive --agree-tos -m admin@yalihanemlak.com.tr

# 4. Veya Cloudflare Origin CA kullanıyorsanız:
#    Cloudflare Dashboard → SSL/TLS → Origin Server → Create Certificate
#    Private key + certificate'ı sunucuya kopyala
#    /etc/ssl/certs/yalihanemlak.com.tr.pem
#    /etc/ssl/private/yalihanemlak.com.tr.key

# 5. vHost'u etkinleştir
sudo bash /tmp/apex-vhost-deploy.sh
```

**vHost dosyası:** `/etc/nginx/sites-available/yalihanemlak.com.tr`
**Etkin:** `/etc/nginx/sites-enabled/yalihanemlak.com.tr` (symlink)

---

## ✅ ADIM 4 — Host-Header Testleri (DNS Değişikliği ÖNCESİ)

> DNS değişikliği YAPMADAN, doğrudan Hetzner IP'si üzerinden test

```bash
# Hetzner sunucusunun IP'si ile (DNS henüz değişmedi)
HETZNER_IP="<HETZNER_SERVER_IP>"

# Apex landing page (yalihanemlak.com.tr)
curl -H "Host: yalihanemlak.com.tr" http://${HETZNER_IP}/
# Beklenen: "Web Sitemiz Yenileniyor" + navy/gold tasarım

# www redirect
curl -H "Host: www.yalihanemlak.com.tr" http://${HETZNER_IP}/
# Beklenen: HTTP 301 → https://yalihanemlak.com.tr/

# Legal pages
curl -H "Host: yalihanemlak.com.tr" http://${HETZNER_IP}/legal/privacy
curl -H "Host: yalihanemlak.com.tr" http://${HETZNER_IP}/legal/terms
curl -H "Host: yalihanemlak.com.tr" http://${HETZNER_IP}/legal/data-deletion
# Beklenen: HTTP 200 + KVKK compliant content

# API regression (api subdomain değişmemiş olmalı)
curl https://api.yalihanemlak.com.tr/api/v1/health/database
# Beklenen: HTTP 200 + {"service":"database","state":"healthy"}
```

---

## ✅ ADIM 5 — TLS/SSL Doğrulama

```bash
# TLS handshake test
curl -v https://yalihanemlak.com.tr/ 2>&1 | grep -E "SSL|TLS|subject|issuer|expire"

# Sertifika bilgileri
openssl s_client -connect yalihanemlak.com.tr:443 -servername yalihanemlak.com.tr </dev/null 2>/dev/null | \
  openssl x509 -noout -subject -issuer -dates

# Beklenen:
#   subject=CN=yalihanemlak.com.tr
#   notBefore=<bugün>
#   notAfter=<~90 gün sonra>  (Let's Encrypt ise 90 gün)
```

**TLS Yöntemleri:**
| Yöntem | Durum | Not |
|--------|-------|-----|
| Let's Encrypt (HTTP-01) | ✅ Hazır | certbot --nginx ile otomatik |
| Cloudflare Origin CA | ✅ Alternatif | Full/Strict SSL modu gerekli |
| Cloudflare Universal SSL | ✅ | Cloudflare otomatik sağlar |

**DNS-01 (ACME DNS-01 challenge):** Gerekli değil — HTTP-01 yeterli.

---

## ✅ ADIM 6 — DNS Cutover (SAAB Onayı Sonrası)

> ⚠️ Tüm önceki adımlar TAMAMLANDIĞINDA ve SAAB onayı alındığında yapılacak

```bash
# Cloudflare Dashboard → DNS Records
# YALIHIMIZ: Sadece A kayıtlarını değiştir — MX, TXT, SPF DOKUNMA

# A kaydı değişikliği:
yalihanemlak.com.tr     A  → <HETZNER_IP>   (Proxied)
www.yalihanemlak.com.tr  A  → <HETZNER_IP>   (Proxied)

# MX, TXT, SPF kayıtları: DEĞİŞTİRME
```

**Cloudflare DNS-Only (geçici rollback modu):**
```
yalihanemlak.com.tr     A  → <HETZNER_IP>   DNS-only (proxy off)
```
DNS-only modda Cloudflare cache devre dışı = anında etki = rollback kolay.

---

## ✅ ADIM 7 — Public Smoke Test (DNS Cutover SONRASI)

```bash
# DNS propagate bekledikten sonra (5-30 dakika)
sleep 30

# Apex landing
curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/
# Beklenen: HTTP 200

# Legal pages
curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/legal/privacy
curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/legal/terms
curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/legal/data-deletion
# Beklenen: HTTP 200 (üçü de)

# www redirect
curl -s -o /dev/null -w "%{http_code}" https://www.yalihanemlak.com.tr/
# Beklenen: HTTP 301 → https://yalihanemlak.com.tr/

# API regression
curl -s https://api.yalihanemlak.com.tr/api/v1/health/database
# Beklenen: HTTP 200 + {"service":"database","state":"healthy"}

# Meta crawler simulation
curl -s -A "facebookexternalhit/1.1" -o /dev/null -w "%{http_code}" \
  https://yalihanemlak.com.tr/legal/privacy
# Beklenen: HTTP 200 (Cloudflare challenge yok)
```

---

## ✅ ADIM 8 — Meta App Publish URL'lerini Güncelle

> DNS cutover tamamlandıktan ve smoke test geçtiğinde yapılacak

```
Privacy Policy URL:     https://yalihanemlak.com.tr/legal/privacy
Terms of Service URL:  https://yalihanemlak.com.tr/legal/terms
User data deletion:    https://yalihanemlak.com.tr/legal/data-deletion
```

**Meta App Review'da gönderilecek URL'ler:** (DOĞRULANACAK)
```
Privacy Policy:        https://yalihanemlak.com.tr/legal/privacy
Terms of Service:     https://yalihanemlak.com.tr/legal/terms
User data deletion:    https://yalihanemlak.com.tr/legal/data-deletion
```

---

## ✅ ADIM 9 — Rollback Prosedürü

> DNS cutover sonrası sorun olursa:

```bash
# Cloudflare Dashboard → DNS
yalihanemlak.com.tr     A  → <ORIJINAL_ORIGIN_IP>  (Step 1'de kaydedilen)
www.yalihanan.com.tr   A  → <ORIJINAL_ORIGIN_IP>

# Nginx rollback (Hetzner'de)
sudo rm /etc/nginx/sites-enabled/yalihanemlak.com.tr
sudo systemctl reload nginx

# Git rollback (varsa sorunlu commit)
cd /opt/yalihan-os-production
git reset --hard <PREVIOUS_COMMIT_SHA>
```

**Rollback Commit'i (pre-apex):** `4397bc0` — WHATSAPP_WEBHOOK_VERIFY_TOKEN

---

## ⚠️ YAPILMAYACAKLAR

| Yasak | Sebep |
|-------|-------|
| `/var/www/yalihanai` kullanma | Yanlış path — doğrusu `/opt/yalihan-os-production/` |
| `origin/main`'e deploy | Production branch: `integration/era-v-phase2a-e01` |
| SPF'ye `ip4:157.180.116.63` ekleme | Web sunucusu mail göndermeyecek — Google Workspace MX zaten ayrı |
| MX/TXT kayıtlarını değiştirme | Mevcut DNS kayıtları korunacak |
| Cloudflare genel "Disable Security" | Çok geniş — sadece gerekli IP whitelist (opsiyonel) |

---

## 📊 Commit Geçmişi

| Commit | Tarih | Açıklama |
|--------|-------|----------|
| `0c21387` | 2026-08-19 | feat(apex): coming-soon landing page |
| `4bc9c93` | 2026-08-18 | chore(ops): Cloudflare bypass script |
| `1213a14` | 2026-08-18 | feat(legal): privacy/terms/data-deletion |
| `4397bc0` | önceki | feat(production): WHATSAPP_WEBHOOK_VERIFY_TOKEN |

---

## ✅ DOĞRULAMA MATRİSİ

| Test | URL | Beklenen | Durum |
|------|-----|----------|--------|
| Apex landing | `https://yalihanemlak.com.tr/` | HTTP 200 + "Yenileniyor" | ⬜ |
| Privacy Policy | `https://yalihanemlak.com.tr/legal/privacy` | HTTP 200 + KVKK içerik | ⬜ |
| Terms | `https://yalihanemlak.com.tr/legal/terms` | HTTP 200 + şartlar | ⬜ |
| Data Deletion | `https://yalihanemlak.com.tr/legal/data-deletion` | HTTP 200 + talimat | ⬜ |
| www redirect | `https://www.yalihanemlak.com.tr/` | HTTP 301 → apex | ⬜ |
| API health | `https://api.yalihanemlak.com.tr/api/v1/health/database` | HTTP 200 + healthy | ⬜ |
| Meta crawler | `https://yalihanemlak.com.tr/legal/privacy` (Meta UA) | HTTP 200 (no challenge) | ⬜ |
| TLS cert | `https://yalihanemlak.com.tr/` | Valid cert | ⬜ |

---

_Document version: 2026-08-19 — YALIHAN OS Apex Cutover_
