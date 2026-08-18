#!/usr/bin/env bash
#
# ═══════════════════════════════════════════════════════════════════════════
# 🌐 Cloudflare Legal Pages Bypass — Meta App Publish Compliance
# ═══════════════════════════════════════════════════════════════════════════
#
# KULLANIM:
#   ./scripts/ops/cloudflare-legal-bypass.sh
#
# AÇIKLAMA:
#   Meta App Center crawler'ının /legal/* endpointlerine challenge
#   almadan erişebilmesi için Cloudflare Page Rule yapılandırır.
#
#   İki yöntem:
#     1. Cloudflare API (otomatik) — CF_API_EMAIL + CF_API_KEY gerekli
#     2. Manuel Dashboard — adımlar için --manual flag'ini kullanın
#
# GEREKSİNİMLER:
#   - CF_API_EMAIL     : Cloudflare hesap e-postası
#   - CF_API_KEY       : Cloudflare Global API Key
#   - CF_ZONE_ID       : yalihanemlak.com.tr zone ID
#   - CF_ACCOUNT_ID    : Cloudflare account ID
#
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail
IFS=$'\n\t'

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DOMAIN="yalihanemlak.com.tr"
LEGAL_PATHS=("legal/privacy" "legal/terms" "legal/data-deletion")

# ─── Renkli çıktı ───────────────────────────────────────────────────────
log()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success(){ echo -e "${GREEN}[OK]${NC}    $*"; }
warn()   { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()  { echo -e "${RED}[ERR]${NC}   $*"; exit 1; }

# ─── Argüman parse ───────────────────────────────────────────────────────
MANUAL_MODE=false
if [[ "${1:-}" == "--manual" || "${1:-}" == "-m" ]]; then
  MANUAL_MODE=true
fi

# ─── Cloudflare API check ─────────────────────────────────────────────────
check_cf_credentials() {
  if [[ -z "${CF_API_EMAIL:-}" || -z "${CF_API_KEY:-}" || -z "${CF_ZONE_ID:-}" ]]; then
    return 1
  fi
  return 0
}

# ─── Cloudflare API call ──────────────────────────────────────────────────
cf_api() {
  local method="$1"
  local endpoint="$2"
  local data="${3:-}"

  curl -s --max-time 30 \
    -X "$method" \
    -H "Authorization: Bearer $CF_API_KEY" \
    -H "Content-Type: application/json" \
    ${data:+-d "$data"} \
    "https://api.cloudflare.com/client/v4/$endpoint"
}

# ─── Zone ID'yi doğrula ──────────────────────────────────────────────────
verify_zone() {
  log "Cloudflare zone doğrulanıyor: $DOMAIN"
  local response
  response=$(cf_api "GET" "zones?name=$DOMAIN")

  local zone_id
  zone_id=$(echo "$response" | jq -r '.result[0].id // empty')

  if [[ -z "$zone_id" || "$zone_id" == "null" ]]; then
    error "Zone bulunamadı: $DOMAIN. Cloudflare dashboard'dan Zone ID'yi alın."
  fi

  if [[ "$zone_id" != "$CF_ZONE_ID" ]]; then
    warn "CF_ZONE_ID uyuşmuyor. Bulunan: $zone_id, Beklenen: $CF_ZONE_ID"
    warn "Bulantıya rağmen devam ediliyor..."
  fi

  success "Zone doğrulandı: $zone_id"
  echo "$zone_id"
}

# ─── Mevcut Page Rules'ları listele ──────────────────────────────────────
list_page_rules() {
  log "Mevcut Page Rules'lar listeleniyor..."
  cf_api "GET" "zones/$CF_ZONE_ID/pagerules" | jq '.result[] | {id, status, actions, targets}'
}

# ─── Legal bypass Page Rule oluştur ─────────────────────────────────────
create_legal_bypass() {
  log "Legal pages için bypass Page Rule oluşturuluyor..."

  # Page Rule oluştur
  local response
  response=$(cf_api "POST" "zones/$CF_ZONE_ID/pagerules" '{
    "status": "active",
    "targets": [
      {
        "target": "url",
        "constraint": {
          "operator": "matches",
          "value": "'"$DOMAIN/legal/*"'"
        }
      }
    ],
    "actions": [
      {
        "id": "disable_security",
        "value": {
          "disable_challenge": true
        }
      },
      {
        "id": "cache",
        "value": {
          "edge_cache_ttl": 86400,
          "respect_strong_etag": true,
          "cache": true
        }
      }
    ],
    "priority": 1,
    "description": "Meta App Publish Legal Pages Bypass — otomatik oluşturuldu"
  }')

  local rule_id
  rule_id=$(echo "$response" | jq -r '.result.id // empty')

  if [[ -z "$rule_id" || "$rule_id" == "null" ]]; then
    local errors
    errors=$(echo "$response" | jq '.errors')
    error "Page Rule oluşturulamadı: $errors"
  fi

  success "Page Rule oluşturuldu! ID: $rule_id"
  echo "$rule_id"
}

# ─── Cloudflare Workers bypass (daha esnek) ────────────────────────────────
create_worker_bypass() {
  log "Cloudflare Worker bypass kuralı oluşturuluyor..."

  local worker_script
  worker_script=$(cat << 'WORKER_EOF'
addEventListener("fetch", event => {
  const url = new URL(event.request.url);

  // Legal pages — bypass challenge
  if (url.pathname.startsWith("/legal/")) {
    // Challenge bypass headers
    const headers = new Headers(event.request.headers);
    headers.set("X-CF-Bypass", "true");

    return fetch(event.request, {
      headers,
      // Do not challenge these paths
      cf: { challengeTTL: 0 }
    });
  }

  // All other requests — normal Cloudflare processing
  return fetch(event.request);
});
WORKER_EOF

  # Worker'ı yükle
  local response
  response=$(cf_api "PUT" "accounts/$CF_ACCOUNT_ID/workers/scripts/legal-bypass" \
    "$(jq -n --arg script "$worker_script" '{script: $script}')"
  )

  local worker_name
  worker_name=$(echo "$response" | jq -r '.result.script // empty')

  if [[ -z "$worker_name" || "$worker_name" == "null" ]]; then
    warn "Worker yüklenemedi. Manuel kontrol gerekebilir."
    return 1
  fi

  success "Worker yüklendi: $worker_name"
  echo "$worker_name"
}

# ─── Sonucu doğrula ───────────────────────────────────────────────────────
verify_bypass() {
  log "Bypass doğrulaması yapılıyor..."
  sleep 5  # Cloudflare propagate için bekle

  for path in "${LEGAL_PATHS[@]}"; do
    local url="https://$DOMAIN/$path"
    local status
    status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "$url" 2>/dev/null || echo "FAIL")

    if [[ "$status" == "200" ]]; then
      success "$url → HTTP $status ✅"
    elif [[ "$status" == "404" ]]; then
      warn "$url → HTTP $status (henüz deploy edilmemiş)"
    else
      warn "$url → HTTP $status (incele)"
    fi
  done
}

# ─── Manuel dashboard talimatları ─────────────────────────────────────────
show_manual_instructions() {
  echo ""
  echo "╔══════════════════════════════════════════════════════════════════════╗"
  echo "║  📋 MANUEL CLOUDFLARE DASHBOARD TALİMATLARI                    ║"
  echo "╚══════════════════════════════════════════════════════════════════════╝"
  echo ""
  echo "  1. Cloudflare Dashboard'a gir: https://dash.cloudflare.com"
  echo "  2. Domain'i seç: $DOMAIN"
  echo "  3. Sol menüden: Rules → Page Rules"
  echo "  4. Create Page Rule butonuna tıkla"
  echo ""
  echo "  📌 PAGE RULE AYARLARI:"
  echo "  ─────────────────────────────────────────"
  echo "  URL (matches format):"
  echo "    *$DOMAIN/legal/*"
  echo ""
  echo "  Then the settings:"
  echo "    • Cache Rule: Cache everything"
  echo "    • Edge Cache TTL: 1 day"
  echo "    • Disable Security: ON (Challenge pages)"
  echo "    • Automatic HTTPS Rewrites: ON"
  echo ""
  echo "  Priority: 1"
  echo "  Status: Active"
  echo ""
  echo "  ─────────────────────────────────────────"
  echo "  5. Save and Deploy"
  echo ""
  echo "  ⚠️  DEĞİŞİKLİKLERİN AKTİF OLMASI İÇİN 30-60 SANİYE BEKLEYİN"
  echo ""
}

# ─── Ana script ───────────────────────────────────────────────────────────
main() {
  echo ""
  echo "╔══════════════════════════════════════════════════════════════════════╗"
  echo "║  🌐 CLOUDFLARE LEGAL BYPASS — Meta App Publish Compliance      ║"
  echo "╚══════════════════════════════════════════════════════════════════════╝"
  echo ""

  # Manuel mod
  if $MANUAL_MODE; then
    show_manual_instructions
    exit 0
  fi

  # API credential kontrolü
  if ! check_cf_credentials; then
    warn "Cloudflare API credential'ları bulunamadı (.env)"
    warn "Manuel mod kullanılıyor..."
    echo ""
    show_manual_instructions
    exit 0
  fi

  # Zone doğrulama
  log "Cloudflare API bağlantısı test ediliyor..."
  if ! cf_api "GET" "user" | jq -e '.success' > /dev/null 2>&1; then
    error "Cloudflare API'ye bağlanılamadı. API key'i kontrol edin."
  fi
  success "Cloudflare API bağlantısı başarılı"

  # Zone doğrula
  verify_zone

  # Mevcut rules kontrol et
  log "Mevcut Page Rules kontrol ediliyor..."
  local existing
  existing=$(cf_api "GET" "zones/$CF_ZONE_ID/pagerules")
  local legal_existing
  legal_existing=$(echo "$existing" | jq -r '.result[] | select(.targets[0].constraint.value | contains("legal")) | .id' 2>/dev/null || true)

  if [[ -n "$legal_existing" ]]; then
    success "Legal bypass Page Rule zaten mevcut: $legal_existing"
    verify_bypass
    exit 0
  fi

  # Page Rule oluştur
  create_legal_bypass

  # Doğrula
  verify_bypass

  echo ""
  success "Cloudflare bypass kuralı aktif! Meta crawler erişimi açık."
  echo ""
}

main "$@"
