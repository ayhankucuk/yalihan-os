---
name: vps-deployment-recovery
description: Deploy and recover the YALIHAN OS Laravel application on its Docker-based VPS production environment, including Git checkout, image rebuild, migration diagnosis, and safe verification.
---

# YALIHAN OS VPS Deployment & Recovery

Use for production deployment, Docker rebuilds, Laravel migrations, or recovery of partial migrations.

## Environment

- Checkout: `/opt/yalihan2026/current`
- Branch: `integration/era-v-phase2a-e01`
- Compose file: `docker-compose.production.yml`
- Services: `yalihanai-app-v2`, `yalihanai-nginx-v2`, `yalihanai-queue-v2`
- Laravel version observed: 10.50.2
- Never expose `.env`, credentials, tokens, or private keys.

## Deploy

```bash
cd /opt/yalihan2026/current
git fetch origin
git checkout -B integration/era-v-phase2a-e01 origin/integration/era-v-phase2a-e01
git log -1 --oneline
docker compose -f docker-compose.production.yml build yalihanai-app-v2 yalihanai-nginx-v2
docker compose -f docker-compose.production.yml up -d --force-recreate \
  yalihanai-app-v2 yalihanai-nginx-v2 yalihanai-queue-v2
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}'
docker exec yalihanai-app-v2 php artisan --version
```

Run migrations only after the app is healthy:

```bash
docker exec yalihanai-app-v2 php artisan migrate --force
```

An image build is not deployment success; `migrate --force` must finish without `FAIL`.

## Partial migration recovery

Use the imported facade in Tinker. A bare `DB::...` is invalid:

```bash
docker exec yalihanai-app-v2 php artisan tinker --execute='
$db = \Illuminate\Support\Facades\DB::connection();
echo "DB=" . $db->getDatabaseName() . PHP_EOL;
foreach (["provider_settlements","settlement_allocations","bank_transactions","reconciliation_executions"] as $table) {
    try { echo $table . ": " . $db->table($table)->count() . PHP_EOL; }
    catch (Throwable $e) { echo $table . ": MISSING" . PHP_EOL; }
}
'
```

Only remove a table when the migration is still `Pending`, the table belongs to that failed migration, and its contents are explicitly verified empty/disposable. For the known C5 failure, the verified empty partial tables were `provider_settlements` and `settlement_allocations`:

```bash
docker exec yalihanai-app-v2 php artisan tinker --execute="
\Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS settlement_allocations');
\Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS provider_settlements');
echo 'PARTIAL_TABLES_REMOVED' . PHP_EOL;
"
```

Never use `migrate:fresh`, `DROP DATABASE`, or broad deletion for this recovery.

## Feature assignment migration rule

The villa feature seed can run before `tenant_id` exists. It must check `Schema::hasColumn('feature_assignments', 'tenant_id')` and omit that field from match/insert arrays when absent. Expected order:

1. `2026_08_25_000001_seed_villa_feature_assignments`
2. `2026_08_25_150345_add_tenant_id_to_feature_assignments_table`
3. `2026_08_25_150439_add_tenant_aware_unique_index_to_feature_assignments`

## Verify

```bash
docker exec yalihanai-app-v2 php artisan optimize:clear
docker exec yalihanai-app-v2 php artisan migrate:status | grep -E 'c51|settlement|feature_assignment'
docker ps --format 'table {{.Names}}\t{{.Status}}'
```

All relevant migrations must show `Ran`; containers must remain healthy. Do not run an extra feature seeder unless the migration/seeder design explicitly requires it, to avoid duplicates.

## Git and reporting

Commit migration fixes narrowly, preserve unrelated work, push the requested branch, and record the exact deployed commit hash. Do not claim completion without the full migration output.
