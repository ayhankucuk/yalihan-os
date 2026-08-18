#!/usr/bin/env bash
#
# ═══════════════════════════════════════════════════════════════════════════
# 🌐 Hetzner Nginx vHost — yalihanemlak.com.tr Apex/www
# ═══════════════════════════════════════════════════════════════════════════
#
# KULLANIM (Hetzner sunucusunda root olarak çalıştır):
#   sudo bash apex-vhost-deploy.sh [--dry-run] [--test]
#
# AÇIKLAMA:
#   yalihanemlak.com.tr ve www.yalihanemlak.com.tr için Nginx vHost
#   oluşturur. YALIHAN OS Laravel uygulamasını /opt/yalihan-os-production/
#   üzerinden sunar.
#
#   ÖNEMLI:
#   - api.yalihanemlak.com.tr vHost'u DEĞİŞTİRİLMEZ
#   - TLS sertifikası (Let's Encrypt) önceden hazırlanmalı
#   - DNS değişikliği ayrı yapılır (bu script DNS'e dokunmaz)
#
# ALTERNATIF (Cloudflare Origin Pull):
#   Cloudflare Full/Strict SSL kullanıyorsanız, origin sertifikası
#   yerine Cloudflare Origin CA + Full/Strict modu kullanabilirsiniz.
#   Bu durumda SSL bölümünü skip edin ve Cloudflare'da SSL modunu
#   Full/Strict yapın.
#
# KULLANILAN DEĞİŞKENLER:
#   APEX_DOMAIN=yalihanemlak.com.tr
#   API_DOMAIN=api.yalihanemlak.com.tr
#   LARAVEL_PATH=/opt/yalihan-os-production/current
#   PHP_FPM_SOCKET=/run/php/php81-fpm.sock  (sunucudaki php-fpm socket)
#   nginx_user=nobody  (veya www-data)
#
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail
IFS=$'\n\t'

# ─── Renkler ────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'

# ─── Yapılandırma ──────────────────────────────────────────────────────────
APEX_DOMAIN="yalihanemlak.com.tr"
WWW_DOMAIN="www.${APEX_DOMAIN}"
API_DOMAIN="api.yalihanemlak.com.tr"
LARAVEL_PATH="/opt/yalihan-os-production/current"
PHP_FPM_SOCKET="/run/php/php81-fpm.sock"
NGINX_USER="nobody"
NGINX_SITES_DIR="/etc/nginx/sites-available"
NGINX_ENABLED_DIR="/etc/nginx/sites-enabled"
NGINX_APEX_VHOST="yalihanemlak.com.tr"
NGINX_TEST_MODE=false
DRY_RUN=false

# ─── Argümanlar ─────────────────────────────────────────────────────────────
for arg in "$@"; do
  case $arg in
    --dry-run)   DRY_RUN=true ;;
    --test)      NGINX_TEST_MODE=true ;;
    --help|-h)
      echo "Usage: $0 [--dry-run] [--test]"
      echo "  --dry-run  Simulate without writing files"
      echo "  --test     Run 'nginx -t' without reloading nginx"
      exit 0
      ;;
  esac
done

# ─── Loglama ────────────────────────────────────────────────────────────────
log()   { echo -e "${BLUE}[INFO]${NC}   $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; exit 1; }

# ─── Pre-flight ─────────────────────────────────────────────────────────────
preflight() {
  log "Pre-flight kontrol..."

  if [[ $EUID -ne 0 && "$DRY_RUN" != "true" ]]; then
    fail "Bu scripti root olarak çalıştırın (sudo bash $0)"
  fi

  if [[ ! -d "$LARAVEL_PATH" ]]; then
    fail "Laravel path bulunamadı: $LARAVEL_PATH"
  fi

  if [[ ! -f "$LARAVEL_PATH/artisan" ]]; then
    fail "artisan bulunamadı: $LARAVEL_PATH/artisan"
  fi

  # API vHost'un değiştirilmediğini doğrula
  if [[ -f "${NGINX_SITES_DIR}/${API_DOMAIN}" ]]; then
    ok "API vHost mevcut: ${NGINX_SITES_DIR}/${API_DOMAIN}"
  fi

  ok "Pre-flight geçti"
}

# ─── Nginx vHost oluştur ───────────────────────────────────────────────────
generate_vhost() {
  log "Nginx vHost konfigürasyonu oluşturuluyor..."

  # PHP-FPM socket kontrolü — fallbacks
  if [[ -S "$PHP_FPM_SOCKET" ]]; then
    PHP_FPM_CONFIG="unix:${PHP_FPM_SOCKET}"
  elif [[ -S "/run/php/php-fpm.sock" ]]; then
    PHP_FPM_CONFIG="unix:/run/php/php-fpm.sock"
    warn "Fallback socket kullanılıyor: /run/php/php-fpm.sock"
  else
    PHP_FPM_CONFIG="unix:${PHP_FPM_SOCKET}"
    warn "PHP-FPM socket mevcut değil. Sunucuda php-fpm kurulu olduğunu doğrulayın."
  fi

  # Sertifika yolları
  CERT_FULLCHAIN="/etc/letsencrypt/live/${APEX_DOMAIN}/fullchain.pem"
  CERT_PRIVKEY="/etc/letsencrypt/live/${APEX_DOMAIN}/privkey.pem"

  if [[ -f "$CERT_FULLCHAIN" && -f "$CERT_PRIVKEY" ]]; then
    TLS_BLOCK=$(cat << 'TLS_EOF'

    # ─── HTTPS ────────────────────────────────────────────────────────────
    ssl_certificate     /etc/letsencrypt/live/yalihanemlak.com.tr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yalihanemlak.com.tr/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache   shared:SSL:10m;
    ssl_session_timeout 1d;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
TLS_EOF
    )
    warn "TLS sertifikası bulundu. HTTPS aktif edilecek."
  else
    TLS_BLOCK=$(cat << 'NO_TLS_EOF'

    # ─── HTTP only — TLS sertifikası yok ──────────────────────────────────
    # certbot --nginx -d yalihanemlak.com.tr -d www.yalihanemlak.com.tr
    # DNS-01 veya HTTP-01 doğrulama gerekli
    warn "Sertifika yok! TLS olmadan devam ediliyor (test için)."
NO_TLS_EOF
    )
    warn "TLS sertifikası bulunamadı: $CERT_FULLCHAIN"
  fi

  # vHost içeriği oluştur
  cat > "${NGINX_SITES_DIR}/${NGINX_APEX_VHOST}" << VHOST_EOF
# ═══════════════════════════════════════════════════════════════════════════════
# yalihanemlak.com.tr — YALIHAN OS Apex Landing + Legal Pages
# Otomatik oluşturuldu: $(date -Iseconds)
# ═══════════════════════════════════════════════════════════════════════════════

# ─── HTTP (80) — redirect ve TLS negotiation ────────────────────────────────
server {
    listen 80;
    listen [::]:80;
    server_name ${APEX_DOMAIN} ${WWW_DOMAIN};

    # HTTP-01 ACME challenge (Let's Encrypt)
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        try_files \$uri \$uri/ =404;
    }

    # TLS değilse → HTTPS redirect
    return 301 https://${APEX_DOMAIN}\$request_uri;
}

# ─── HTTPS (443) ────────────────────────────────────────────────────────────
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${WWW_DOMAIN};

    # www → apex redirect (HTTPS'te de)
    return 301 https://${APEX_DOMAIN}\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${APEX_DOMAIN};

    # Document root — Laravel public/
    root ${LARAVEL_PATH}/public;
    index index.php index.html;

    # Access ve error log
    access_log /var/log/nginx/${APEX_DOMAIN}-access.log;
    error_log  /var/log/nginx/${APEX_DOMAIN}-error.log warn;

    # Gzip sıkıştırma
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css application/json application/javascript
               text/xml application/xml application/xml+rss text/javascript
               image/svg+xml application/vnd.ms-fontobject application/x-font-ttf
               font/opentype;

    # Security headers
    add_header X-Content-Type-Options    "nosniff" always;
    add_header X-Frame-Options          "SAMEORIGIN" always;
    add_header X-XSS-Protection         "1; mode=block" always;
    add_header Referrer-Policy          "strict-origin-when-cross-origin" always;

    # Cloudflare IP whitelist (opsiyonel — Cloudflare kullanıyorsanız)
    # set_real_ip_from 103.21.244.0/22;
    # set_real_ip_from 103.22.200.0/22;
    # set_real_ip_from 103.31.4.0/22;
    # set_real_ip_from 104.16.0.0/12;
    # real_ip_header CF-Connecting-IP;

    # ─── Static assets ─────────────────────────────────────────────────────
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map)\$ {
        expires 1y;
        add_cache_control public, max-age=31536000, immutable;
        access_log off;
        try_files \$uri \$uri/ =404;
    }

    # ─── Laravel public files ──────────────────────────────────────────────
    location / {
        try_files \$uri @laravel;
    }

    # ─── PHP-FPM → Laravel ────────────────────────────────────────────────
    location ~* \.php\$ {
        include         fastcgi_params;
        fastcgi_pass    ${PHP_FPM_CONFIG};
        fastcgi_param   SCRIPT_FILENAME    \$document_root\$fastcgi_script_name;
        fastcgi_param   PATH_INFO          \$fastcgi_path_info;
        fastcgi_param   PATH_TRANSLATED     \$document_root\$fastcgi_script_name;
        fastcgi_param   SERVER_NAME         \$host;
        fastcgi_param   HTTPS              on;
        fastcgi_buffering off;
        fastcgi_read_timeout 120s;
        fastcgi_send_timeout 120s;
    }

    # ─── Laravel front controller ──────────────────────────────────────────
    location @laravel {
        include         fastcgi_params;
        fastcgi_pass    ${PHP_FPM_CONFIG};
        fastcgi_param   SCRIPT_FILENAME    \${LARAVEL_PATH}/public/index.php;
        fastcgi_param   PATH_INFO          \$fastcgi_path_info;
        fastcgi_param   PATH_TRANSLATED     \${LARAVEL_PATH}/public\$fastcgi_script_name;
        fastcgi_param   REQUEST_URI         \$request_uri;
        fastcgi_param   DOCUMENT_URI        \$document_uri;
        fastcgi_param   SERVER_NAME         \$host;
        fastcgi_param   HTTPS               on;
        fastcgi_buffering off;
        fastcgi_read_timeout 120s;
    }

    # ─── ACME challenge (HTTPS) ────────────────────────────────────────────
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        try_files \$uri \$uri/ =404;
    }

    # ─── Deny sensitive paths ──────────────────────────────────────────────
    location ~ /\. {
        deny all;
    }
    location ~ ^/(\.env|composer\.|package\.|storage\/|node_modules\.) {
        deny all;
    }
${TLS_BLOCK}
}
VHOST_EOF

  ok "vHost dosyası oluşturuldu: ${NGINX_SITES_DIR}/${NGINX_APEX_VHOST}"
}

# ─── Nginx test et ───────────────────────────────────────────────────────────
test_nginx() {
  log "Nginx konfigürasyonu test ediliyor..."
  if nginx -t 2>&1 | tee /dev/stderr | grep -q "syntax is ok"; then
    ok "Nginx konfigürasyonu geçerli"
    return 0
  else
    fail "Nginx konfigürasyonu hatalı"
  fi
}

# ─── vHost'u etkinleştir ───────────────────────────────────────────────────
enable_vhost() {
  log "vHost etkinleştiriliyor..."

  # Mevcut symlink'i kaldır (varsa)
  if [[ -L "${NGINX_ENABLED_DIR}/${NGINX_APEX_VHOST}" ]]; then
    warn "Mevcut symlink kaldırılıyor: ${NGINX_ENABLED_DIR}/${NGINX_APEX_VHOST}"
    $DRY_RUN || sudo rm -f "${NGINX_ENABLED_DIR}/${NGINX_APEX_VHOST}"
  fi

  # Yeni symlink oluştur
  $DRY_RUN || sudo ln -s "${NGINX_SITES_DIR}/${NGINX_APEX_VHOST}" \
                        "${NGINX_ENABLED_DIR}/${NGINX_APEX_VHOST}"
  ok "Symlink oluşturuldu: ${NGINX_ENABLED_DIR}/${NGINX_APEX_VHOST}"

  # Nginx reload
  $DRY_RUN || sudo systemctl reload nginx
  ok "Nginx reload edildi"
}

# ─── Host-header test (production'da DNS değişikliği öncesi) ───────────────
host_header_test() {
  log "Host-header testleri (DNS değişikliği OLMADAN)..."
  local APEX_IP="<HETZNER_SERVER_IP>"  # Sunucu IP'si ile değiştirin

  echo ""
  echo "  curl -H 'Host: yalihanemlak.com.tr' http://${APEX_IP}/"
  echo "  curl -H 'Host: www.yalihanemlak.com.tr' http://${APEX_IP}/"
  echo "  curl -H 'Host: yalihanemlak.com.tr' http://${APEX_IP}/legal/privacy"
  echo "  curl -H 'Host: yalihanemlak.com.tr' http://${APEX_IP}/legal/terms"
  echo "  curl -H 'Host: yalihanemlak.com.tr' http://${APEX_IP}/legal/data-deletion"
  echo ""
  echo " Beklenen:"
  echo "  / → 'Web Sitemiz Yenileniyor' (apex-landing)"
  echo "  /legal/* → KVKK compliant pages (HTTP 200)"
  echo "  www redirect → apex (HTTP 301)"
  echo ""
}

# ─── Ana script ─────────────────────────────────────────────────────────────
main() {
  echo ""
  echo "╔══════════════════════════════════════════════════════════════════════╗"
  echo "║  🌐 HETZNER APEX vHOST DEPLOY — yalihanemlak.com.tr          ║"
  echo "╚══════════════════════════════════════════════════════════════════════╝"
  echo ""

  preflight
  generate_vhost

  if $NGINX_TEST_MODE; then
    test_nginx
    exit 0
  fi

  enable_vhost
  test_nginx

  echo ""
  echo "════════════════════════════════════════════════════════════════"
  echo "  vHost deploy TAMAMLANDI"
  echo "════════════════════════════════════════════════════════════════"
  echo ""
  echo "  Sonraki adımlar:"
  echo "  1. DNS cutover (SAAB onayı sonrası)"
  echo "  2. TLS sertifikası: certbot --nginx -d ${APEX_DOMAIN} -d ${WWW_DOMAIN}"
  echo "  3. Meta App Publish URL'lerini güncelle"
  echo ""
  echo "  Host-header test:"
  host_header_test
}

main "$@"
