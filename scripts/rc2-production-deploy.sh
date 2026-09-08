#!/usr/bin/env bash
# =============================================================================
# YALIHAN OS — RC2 Production Deployment Script
# TASK-RC2-PROD-EXEC
# Branch: release-candidate/RC2
# Commit: 4f195599
# Date: 2026-09-08
# =============================================================================
#
# KULLANIM:
#   VPS'e SSH ile bağlanın, sonra:
#   bash scripts/rc2-production-deploy.sh
#
# veya adım adım kopyala-yapıştır (aşağıdaki komutları sırayla çalıştırın)
#
# ÖNEMLİ:
#   - Her adımın çıktısını kaydedin (PRODUCTION_VERIFIED kanıtı için)
#   - Herhangi bir adımda hata olursa DURUN ve rollback yapın
#   - Backfill migration non-reversible — DB backup ZORUNLU
# =============================================================================

set -euo pipefail

APP_DIR="/opt/yalihan2026/current"
BRANCH="release-candidate/RC2"
COMPOSE_FILE="docker-compose.production.yml"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/log/rc2-deploy-${TIMESTAMP}.log"

log() { echo "[$(date '+%H:%M:%S')] $1" | tee -a "$LOG_FILE"; }

# =============================================================================
# AŞAMA 0: DB BACKUP (ZORUNLU)
# =============================================================================
log "=== AŞAMA 0: DB Backup ==="
log "MySQL production DB backup alınıyor..."

# MySQL bağlantı bilgilerini .env'den al
cd "$APP_DIR"
DB_NAME=$(grep "^DB_DATABASE=" .env | head -n1 | cut -d= -f2- | tr -d '"'\''')
DB_USER=$(grep "^DB_USERNAME=" .env | head -n1 | cut -d= -f2- | tr -d '"'\''')
DB_PASS=$(grep "^DB_PASSWORD=" .env | head -n1 | cut -d= -f2- | tr -d '"'\''')

BACKUP_FILE="/opt/yalihan2026/backups/backup_pre_rc2_${TIMESTAMP}.sql"
mkdir -p /opt/yalihan2026/backups

if [ -n "$DB_PASS" ]; then
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
else
    mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE"
fi
log "✅ DB backup: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

# =============================================================================
# AŞAMA 1: GIT CHECKOUT
# =============================================================================
log "=== AŞAMA 1: Git Checkout ==="
cd "$APP_DIR"
git fetch origin
git checkout -B "$BRANCH" "origin/$BRANCH"
CURRENT_COMMIT=$(git log -1 --format='%H')
log "✅ Checkout: $BRANCH @ $CURRENT_COMMIT"
# Beklenen: 4f195599ff610beb3f9a6cadd31b5a8a7c296da4

# =============================================================================
# AŞAMA 2: DOCKER IMAGE REBUILD
# =============================================================================
log "=== AŞAMA 2: Docker Image Rebuild ==="
docker compose -f "$COMPOSE_FILE" build --no-cache yalihanai-app-v2 yalihanai-nginx-v2
log "✅ Docker images rebuilt"

# =============================================================================
# AŞAMA 3: CONTAINER RESTART
# =============================================================================
log "=== AŞAMA 3: Container Restart ==="
docker compose -f "$COMPOSE_FILE" up -d --force-recreate \
    yalihanai-app-v2 yalihanai-nginx-v2 yalihanai-queue-v2

# Health check
log "App container health check..."
for i in $(seq 1 30); do
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' yalihanai-app-v2 2>/dev/null || echo "not_found")
    if [ "$STATUS" = "healthy" ]; then
        log "✅ App container healthy"
        break
    fi
    if [ $i -eq 30 ]; then
        log "❌ App container did not become healthy!"
        docker logs yalihanai-app-v2 --tail 50
        exit 1
    fi
    sleep 2
done

docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}' | tee -a "$LOG_FILE"
docker exec yalihanai-app-v2 php artisan --version | tee -a "$LOG_FILE"

# =============================================================================
# AŞAMA 4: MIGRATION (KRİTİK)
# =============================================================================
log "=== AŞAMA 4: Migration ==="
log "php artisan migrate --force çalıştırılıyor..."
log "Beklenen migration'lar:"
log "  1. 2026_09_01_000000_add_ulke_tenant_to_ilanlar_for_v2_api"
log "  2. 2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari"
log "  3. 2026_09_05_100000_add_missing_ci_schema_columns"
log "  4. 2026_09_06_000001_add_action_center_fields_to_gorevler"
log "  5. 2026_09_06_000001_add_ilceler_iller_fk_constraint"
log "  6. 2026_09_08_000001_add_tenant_id_to_cqrs_projection_tables"
log "  7. 2026_09_08_000002_backfill_tenant_id_null_records"
echo ""

docker exec yalihanai-app-v2 php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"

# Migration başarısız kontrolü
if grep -qi "FAIL\|Error\|Exception" "$LOG_FILE"; then
    log "❌ Migration FAILED! Rollback gerekebilir."
    log "Backup dosyası: $BACKUP_FILE"
    exit 1
fi
log "✅ Migration tamamlandı"

# =============================================================================
# AŞAMA 5: CACHE CLEAR
# =============================================================================
log "=== AŞAMA 5: Cache Clear ==="
docker exec yalihanai-app-v2 php artisan optimize:clear 2>&1 | tee -a "$LOG_FILE"
log "✅ Cache cleared"

# =============================================================================
# AŞAMA 6: VERIFICATION
# =============================================================================
log "=== AŞAMA 6: Verification ==="

# 6a: Migration status
log "--- Migration Status ---"
docker exec yalihanai-app-v2 php artisan migrate:status 2>&1 | grep -E "2026_09|tenant_id|backfill" | tee -a "$LOG_FILE"

# 6b: CQRS tenant_id kolonları
log "--- CQRS tenant_id Check ---"
docker exec yalihanai-app-v2 php artisan tinker --execute='
foreach (["listing_search_projection","listing_velocity_projections","market_trend_projections","buyer_interest_projections","talep_match_projection","buyer_intent_projection"] as $t) {
    echo $t . ": " . (\Illuminate\Support\Facades\Schema::hasColumn($t, "tenant_id") ? "HAS tenant_id" : "MISSING tenant_id") . "\n";
}
' 2>&1 | tee -a "$LOG_FILE"

# 6c: TenantScope fail-closed verification
log "--- TenantScope Fail-Closed Check ---"
docker exec yalihanai-app-v2 php artisan tinker --execute='
$nullIlanlar = \Illuminate\Support\Facades\DB::table("ilanlar")->whereNull("tenant_id")->count();
$nullUsers = \Illuminate\Support\Facades\DB::table("users")->whereNull("tenant_id")->count();
echo "tenant_id NULL — ilanlar: {$nullIlanlar}, users: {$nullUsers}\n";
echo "(Fail-closed: NULL kayıtlar erişilemez — beklenen davranış)\n";
' 2>&1 | tee -a "$LOG_FILE"

# 6d: HTTP health check
log "--- HTTP Health Check ---"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://yalihanemlak.com.tr/ --max-time 10 || echo "000")
log "Site HTTP: $HTTP_CODE"

# 6e: Container status
log "--- Container Status ---"
docker ps --format 'table {{.Names}}\t{{.Status}}' | tee -a "$LOG_FILE"

# =============================================================================
# AŞAMA 7: PRODUCTION_VERIFIED KANITI
# =============================================================================
log "=== AŞAMA 7: PRODUCTION_VERIFIED ==="
log "Deploy commit: $CURRENT_COMMIT"
log "Backup: $BACKUP_FILE"
log "Log: $LOG_FILE"
log ""
log "Kanıt için aşağıdaki çıktıları kopyalayın:"
log "  1. git log -1 --oneline"
log "  2. docker exec yalihanai-app-v2 php artisan migrate:status | grep 2026_09"
log "  3. docker exec yalihanai-app-v2 php artisan tinker --execute='...tenant_id NULL check...'"
log "  4. curl -s -o /dev/null -w '%{http_code}' https://yalihanemlak.com.tr/"
log ""
log "=== RC2 DEPLOYMENT COMPLETE ==="
