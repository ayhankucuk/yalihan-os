#!/usr/bin/env node

/**
 * Wizard Environment Generator (Precheck v3 — Hardened)
 */

import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { getPresetByKategoriId } from './category-presets.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const OUTPUT_FILE = path.join(__dirname, '../../.precheck/wizard-env.latest.json');
const API_BASE = process.env.API_BASE || 'http://127.0.0.1:8002';

console.log('🔍 Wizard Environment Precheck v3 (Hardened)');
console.log(`📍 API Base: ${API_BASE}`);

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// ──────────────── API ────────────────

async function fetchContextAPI(kategoriId, junctionId) {
    let attempts = 0;
    const maxAttempts = 5;
    while (attempts < maxAttempts) {
        try {
            const url = `${API_BASE}/api/v1/wizard/context?kategori_id=${kategoriId}&junction_id=${junctionId}`;
            // Use -i to include headers so we can check status code if needed, but simple body check works too
            const response = execSync(`curl -s "${url}"`, {
                encoding: 'utf-8',
                maxBuffer: 10 * 1024 * 1024,
            });

            if (!response || !response.trim()) {
                throw new Error('Empty response');
            }

            const data = JSON.parse(response);

            // Check for Rate Limit or Error
            if (data.error || data.message?.includes('Rate limit')) {
                const retryAfter = data.retry_after ? parseInt(data.retry_after) : 5;
                console.warn(
                    `  ⚠️ Rate Limit Hit for junction ${junctionId}. Waiting ${retryAfter}s...`
                );
                await sleep((retryAfter + 1) * 1000);
                attempts++;
                continue;
            }

            if (!data || typeof data !== 'object') throw new Error('Invalid JSON');
            return data;
        } catch (error) {
            attempts++;
            if (attempts >= maxAttempts) {
                console.warn(`  ❌ API Final Failure for junction ${junctionId}: ${error.message}`);
                return null;
            }
            const backoff = attempts * 1000;
            console.warn(
                `  ⚠️ API Retry ${attempts}/${maxAttempts} for junction ${junctionId} (waiting ${backoff}ms)...`
            );
            await sleep(backoff);
        }
    }
    return null;
}

// ──────────────── Safe Accessors ────────────────

function safeCtx(contextData) {
    return contextData?.context || null;
}
function safeTemplateFields(ctx) {
    return ctx?.template?.fields || {};
}
function safeFeatureSchema(ctx) {
    return ctx?.features?.feature_schema || {};
}
function safeFeatureGroups(ctx) {
    return ctx?.features?.feature_groups || [];
}

// ──────────────── Health Score ────────────────

function calculateHealthScore(contextData, thresholds = {}) {
    const defaults = {
        expected_template_min: 7,
        expected_features_min: 3,
        expected_required_keys: ['baslik', 'fiyat'],
    };

    const config = { ...defaults, ...thresholds };
    const ctx = safeCtx(contextData);

    if (!ctx) {
        return {
            score: 0,
            violations: ['no_context_data'],
            missing: {
                template_fields: [],
                features: [],
                required_keys: config.expected_required_keys,
            },
        };
    }

    let score = 0;
    const violations = [];
    const missing = { template_fields: [], features: [], required_keys: [] };

    const templateFields = Object.keys(safeTemplateFields(ctx));
    const templateCount = templateFields.length;

    if (templateCount >= config.expected_template_min) {
        score += 40;
    } else {
        score += Math.min(templateCount * 4, 40);
        violations.push('insufficient_template_fields');
        missing.template_fields.push(
            `Expected ${config.expected_template_min}, got ${templateCount}`
        );
    }

    const featureKeys = Object.keys(safeFeatureSchema(ctx));
    const featureCount = featureKeys.length;

    if (featureCount >= config.expected_features_min) {
        score += 40;
    } else {
        score += Math.min(featureCount * 4, 40);
        if (
            featureCount === 0 &&
            templateCount > 0 &&
            ctx.template?.id &&
            config.expected_features_min > 0
        ) {
            violations.push('feature_whitelist_drop');
        } else {
            violations.push('insufficient_features');
        }
    }

    const requiredFields = ctx.template?.required || [];
    const missingRequired = config.expected_required_keys.filter(
        (key) => !requiredFields.includes(key) && !templateFields.includes(key)
    );

    if (missingRequired.length === 0) {
        score += 20;
    } else {
        score += Math.max(0, 20 - missingRequired.length * 5);
        violations.push('missing_required_keys');
        missing.required_keys = missingRequired;
    }

    return {
        score: Math.min(score, 100),
        violations: violations.length > 0 ? violations : null,
        missing: violations.length > 0 ? missing : null,
    };
}

// ──────────────── Generator ────────────────

async function generateWizardEnv() {
    console.log('📦 Fetching all junctions from database...');

    let allJunctions = [];
    try {
        const raw = execSync(`php scripts/get-junctions.php`, {
            cwd: path.join(__dirname, '..'),
            encoding: 'utf-8',
        });
        allJunctions = JSON.parse(raw.trim());
    } catch (e) {
        console.error('❌ Failed to fetch junctions:', e.message);
        process.exit(1);
    }

    const results = [];
    console.log(`🚀 Starting audit of ${allJunctions.length} junctions...\n`);

    for (const combo of allJunctions) {
        const contextData = await fetchContextAPI(combo.kategori_id, combo.junction_id);
        await sleep(50); // Small breathing room for local server
        const ctx = safeCtx(contextData);
        const preset = getPresetByKategoriId(combo.kategori_id);
        const healthResult = calculateHealthScore(contextData, preset);

        const templateFieldKeys = Object.keys(safeTemplateFields(ctx));
        const featureSchemaKeys = Object.keys(safeFeatureSchema(ctx));

        const result = {
            name: combo.name,
            kategori_id: combo.kategori_id,
            junction_id: combo.junction_id,
            publish_type_id: ctx?.yayin_tipi?.id ?? null,
            health_score: healthResult.score,
            health_state:
                healthResult.score >= 50
                    ? 'healthy'
                    : healthResult.score > 0
                      ? 'partial'
                      : 'failed',
            violations: healthResult.violations,
            missing: healthResult.missing,
            template_fields_count: templateFieldKeys.length,
            template_field_keys: templateFieldKeys,
            feature_schema_count: featureSchemaKeys.length,
            feature_schema_keys: featureSchemaKeys,
            category_name: ctx?.category?.name || combo.name.split(' + ')[0],
            yayin_tipi_name: ctx?.yayin_tipi?.name || combo.name.split(' + ')[1],
            template_id: ctx?.template?.id || null,
            timestamp: new Date().toISOString(),
        };

        const violationFlag = result.violations ? ` ⚠️ ${result.violations.join(', ')}` : '';
        console.log(
            `🔍 [${result.health_state.toUpperCase()}] ${combo.name} (${combo.junction_id}): ${result.health_score}%${violationFlag}`
        );
        results.push(result);
    }

    const bestCandidate = results.reduce(
        (best, current) => (current.health_score > best.health_score ? current : best),
        results[0] || { health_score: 0 }
    );

    const output = {
        generated_at: new Date().toISOString(),
        generator_version: 'v3-all-junctions',
        api_base: API_BASE,
        ssot_param: 'junction_id',
        best_candidate: bestCandidate,
        all_combinations: results,
        summary: {
            total: results.length,
            healthy: results.filter((r) => r.health_state === 'healthy').length,
            partial: results.filter((r) => r.health_state === 'partial').length,
            failed: results.filter((r) => r.health_state === 'failed').length,
            has_whitelist_drop: results.some((r) =>
                r.violations?.includes('feature_whitelist_drop')
            ),
        },
    };

    const dir = path.dirname(OUTPUT_FILE);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(OUTPUT_FILE, JSON.stringify(output, null, 2), 'utf-8');

    console.log(`\n✅ Generated: ${OUTPUT_FILE}`);
    console.log(
        `📈 Summary: ${output.summary.healthy} healthy, ${output.summary.partial} partial, ${output.summary.failed} failed`
    );
    return output;
}

if (import.meta.url === `file://${process.argv[1]}`) {
    generateWizardEnv()
        .then(() => process.exit(0))
        .catch((err) => {
            console.error('❌ Generation failed:', err);
            process.exit(1);
        });
}

export { generateWizardEnv, calculateHealthScore };
