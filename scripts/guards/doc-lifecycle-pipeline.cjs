/**
 * Documentation Lifecycle Pipeline — Sprint 21 Step 2
 * Enforces valid document lifecycle state transitions.
 *
 * State machine:
 *   DRAFT → REVIEW → APPROVED → CANONICAL → SUPERSEDED → ARCHIVED
 *
 * Each status has required metadata fields before transitioning.
 *
 * Usage: node scripts/guards/doc-lifecycle-pipeline.cjs [--verbose] [--json]
 * Output: docs/_reports/lifecycle_pipeline_report.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const REPORT_PATH = path.join(REPORT_DIR, 'lifecycle_pipeline_report.json');

// ─── Lifecycle State Machine ──────────────────────────────────────────────────

const LIFECYCLE_STATES = ['draft', 'review', 'approved', 'canonical', 'superseded', 'archived'];

// Valid transitions: from → [allowed targets]
const TRANSITIONS = {
  draft:       ['review', 'archived'],
  review:      ['approved', 'draft', 'archived'],
  approved:    ['canonical', 'review', 'archived'],
  canonical:  ['superseded', 'archived'],
  superseded:  ['archived', 'canonical'],
  archived:   [],
};

// Fields required to ENTER each state
const REQUIRED_FOR_STATE = {
  draft:       ['id'],
  review:      ['id', 'owner', 'domain'],
  approved:    ['id', 'owner', 'domain', 'reviewed_at'],
  canonical:   ['id', 'owner', 'domain', 'reviewed_at', 'evidence'],
  superseded: ['id', 'superseded_by', 'evidence'],
  archived:   ['id'],
};

// Fields required to REMAIN in each state (invariant)
const REQUIRED_IN_STATE = {
  canonical: ['id', 'schema_version', 'version', 'status', 'owner', 'domain', 'reviewed_at'],
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) return { parsed: null, error: 'NO_FRONTMATTER' };
  try {
    const parsed = {};
    for (const line of match[1].split(/\r?\n/)) {
      const idx = line.indexOf(':');
      if (idx === -1) continue;
      const key = line.slice(0, idx).trim();
      const raw = line.slice(idx + 1).trim();
      parsed[key] = (raw === '' || raw === '[]' || raw === '{}') ? [] : raw;
    }
    return { parsed, error: null };
  } catch (e) {
    return { parsed: null, error: 'YAML_PARSE_ERROR' };
  }
}

function validateTransition(currentState, targetState, filePath) {
  const allowed = TRANSITIONS[currentState] || [];
  if (!allowed.includes(targetState)) {
    return {
      valid: false,
      error: `INVALID_TRANSITION: '${currentState}' → '${targetState}' — allowed: [${allowed.join(', ') || 'none'}]`,
    };
  }
  return { valid: true, error: null };
}

function validateRequiredFields(parsed, targetState, filePath) {
  const required = REQUIRED_FOR_STATE[targetState] || [];
  const missing = [];

  for (const field of required) {
    const value = parsed[field];
    if (value === undefined || value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
      missing.push(field);
    }
  }

  if (missing.length > 0) {
    return {
      valid: false,
      error: `MISSING_FIELDS_FOR_${targetState.toUpperCase()}: [${missing.join(', ')}]`,
      missing,
    };
  }
  return { valid: true, error: null, missing: [] };
}

function validateStateInvariant(parsed, filePath) {
  const state = parsed.status;
  const required = REQUIRED_IN_STATE[state] || [];
  const broken = [];

  for (const field of required) {
    const value = parsed[field];
    if (value === undefined || value === null || value === '') {
      broken.push(field);
    }
  }

  if (broken.length > 0) {
    return {
      valid: false,
      error: `INVARIANT_BROKEN_IN_${state.toUpperCase()}: [${broken.join(', ')}]`,
      broken,
    };
  }
  return { valid: true, broken: [] };
}

function getClassification(filePath) {
  const rel = path.relative(PROJECT_ROOT, filePath);
  if (/^docs\/(SAB|governance\/EVIDENCE_MODEL|governance\/CERTIFICATION)/.test(rel)) return 'canonical';
  if (/^docs\/(plans|sprints|governance)/.test(rel)) return 'governance';
  if (/^docs\/adr\//.test(rel)) return 'adr';
  if (/^memory\//.test(rel)) return 'memory';
  if (/^docs\//.test(rel)) return 'other';
  return null;
}

// ─── Scanner ─────────────────────────────────────────────────────────────────

function scanMarkdownFiles(dir, results = [], verbose = false) {
  if (!fs.existsSync(dir)) return results;

  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      if (/^(node_modules|vendor|storage|bootstrap|resources|tests)/.test(entry.name)) continue;
      if (/^\./.test(entry.name)) continue;
      scanMarkdownFiles(fullPath, results, verbose);
    } else if (entry.name.endsWith('.md')) {
      const rel = path.relative(PROJECT_ROOT, fullPath);
      const content = fs.readFileSync(fullPath, 'utf8');
      const { parsed, error } = parseFrontmatter(content);
      const classify = getClassification(fullPath);

      const issues = [];
      const state = parsed ? parsed.status : null;

      if (error) {
        issues.push({ type: error, severity: 'error', message: error });
      } else {
        // Validate state is known
        if (state && !LIFECYCLE_STATES.includes(state)) {
          issues.push({ type: 'INVALID_STATUS', severity: 'error', message: `INVALID_STATUS: '${state}' not in lifecycle` });
        }

        // Validate state invariant for canonical docs
        if (classify === 'canonical' && state === 'canonical') {
          const inv = validateStateInvariant(parsed, fullPath);
          if (!inv.valid) {
            issues.push({ type: 'INVARIANT_BROKEN', severity: 'critical', message: inv.error });
          }
        }

        // Check required fields for current state
        if (state) {
          const reqCheck = validateRequiredFields(parsed, state, fullPath);
          if (!reqCheck.valid) {
            issues.push({ type: 'MISSING_FIELDS', severity: 'warning', message: reqCheck.error, missing: reqCheck.missing });
          }
        }
      }

      const level = issues.length === 0 ? 'OK'
        : issues.some(i => i.severity === 'critical' || i.severity === 'error') ? 'INVALID'
        : 'WARNING';

      results.push({
        file: rel,
        type: classify,
        status: parsed ? parsed.status : null,
        level,
        issues,
        metadata: parsed,
      });

      if (verbose || level !== 'OK') {
        const icon = level === 'OK' ? '✅' : level === 'WARNING' ? '⚠️ ' : '❌';
        console.log(`  ${icon} [${(state || 'NULL').padEnd(12)}] ${rel}`);
        for (const issue of issues) {
          console.log(`         ${issue.severity === 'critical' ? '✗' : '~'} ${issue.message}`);
        }
      }
    }
  }

  return results;
}

// ─── Report ───────────────────────────────────────────────────────────────────

function generateReport(results) {
  const byLevel = { OK: [], WARNING: [], INVALID: [] };
  for (const r of results) byLevel[r.level].push(r);

  const summary = {
    scanned_at: new Date().toISOString(),
    total_files: results.length,
    schema_version: '1.0',
    lifecycle_states: LIFECYCLE_STATES,
    allowed_transitions: TRANSITIONS,
    by_level: {
      OK: byLevel.OK.length,
      WARNING: byLevel.WARNING.length,
      INVALID: byLevel.INVALID.length,
    },
    by_status: {},
  };

  for (const r of results) {
    const s = r.status || 'NULL';
    summary.by_status[s] = (summary.by_status[s] || 0) + 1;
  }

  summary.files = results.map(r => ({
    file: r.file,
    type: r.type,
    status: r.status,
    level: r.level,
    issues: r.issues.map(i => ({ type: i.type, severity: i.severity, message: i.message })),
  }));

  return summary;
}

// ─── Entry Point ─────────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const showAll = process.argv.includes('--all');
const jsonOnly = process.argv.includes('--json');

console.log('🔄 Documentation Lifecycle Pipeline — Sprint 21 Step 2');
console.log('   Lifecycle: DRAFT → REVIEW → APPROVED → CANONICAL → SUPERSEDED → ARCHIVED');
console.log('   Root:      docs/ + memory/');
console.log('');

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

const docsResults = scanMarkdownFiles(path.join(PROJECT_ROOT, 'docs'), [], verbose || showAll);
const memoryResults = scanMarkdownFiles(path.join(PROJECT_ROOT, 'memory'), [], verbose || showAll);
const allResults = [...docsResults, ...memoryResults];

const report = generateReport(allResults);
fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));

// Console summary
console.log('');
console.log('─── Summary ───────────────────────────────────────────');
console.log(`  Total files scanned:     ${report.total_files}`);
console.log(`  ✅ OK:                   ${report.by_level.OK}`);
console.log(`  ⚠️  WARNING:            ${report.by_level.WARNING}`);
console.log(`  ❌ INVALID:             ${report.by_level.INVALID}`);
console.log('');
console.log('  Status distribution:');
for (const [status, count] of Object.entries(report.by_status)) {
  console.log(`    ${status.padEnd(12)}: ${count} files`);
}
console.log('');
console.log(`  Report: ${REPORT_PATH}`);
console.log('');

if (report.by_level.INVALID > 0 || report.by_level.WARNING > 0) {
  console.log('  ⚠️  Lifecycle issues detected. Run with --verbose for details.');
}

if (jsonOnly) console.log(JSON.stringify(report, null, 2));

process.exit(report.by_level.INVALID > 0 ? 1 : 0);
