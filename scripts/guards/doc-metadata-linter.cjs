/**
 * Documentation Metadata Linter — Sprint 21 Step 1
 * Validates YAML frontmatter against the Documentation Metadata Standard v1.0
 *
 * Usage: node scripts/guards/doc-metadata-linter.cjs [--verbose] [--json]
 * Output: docs/_reports/metadata_lint_report.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const REPORT_PATH = path.join(REPORT_DIR, 'metadata_lint_report.json');

// ─── Schema Definition ────────────────────────────────────────────────────────

const VALID_STATUSES = ['draft', 'review', 'approved', 'canonical', 'superseded', 'archived'];
const VALID_OWNERS = ['saab', 'developer', 'agent', 'system'];
const VALID_DOMAINS = [
  'governance', 'architecture', 'workspace', 'reservation',
  'crm', 'finance', 'publishing', 'operations',
  'documentation', 'platform', 'calendar', 'listing', 'security'
];
const VALID_SCHEMA_VERSIONS = ['1.0'];

// Required fields per document type
const REQUIRED_FIELDS = ['id', 'schema_version', 'version', 'status', 'owner', 'domain'];
const OPTIONAL_FIELDS = ['created_at', 'reviewed_at', 'review_due', 'supersedes', 'superseded_by', 'evidence', 'tags'];

// ─── Helper Functions ──────────────────────────────────────────────────────────

function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) return { raw: null, parsed: null, error: 'NO_FRONTMATTER' };

  try {
    const parsed = {};
    const lines = match[1].split(/\r?\n/);

    for (const line of lines) {
      const colonIdx = line.indexOf(':');
      if (colonIdx === -1) continue;

      const key = line.slice(0, colonIdx).trim();
      const value = line.slice(colonIdx + 1).trim();

      if (key === 'supersedes' || key === 'superseded_by') {
        parsed[key] = value === '[]' ? [] : value;
      } else if (key === 'evidence') {
        parsed[key] = value === '{}' || value === '' ? {} : value;
      } else if (key === 'tags') {
        parsed[key] = value === '[]' ? [] : value;
      } else if (value === '' || value === null) {
        parsed[key] = null;
      } else {
        parsed[key] = value;
      }
    }

    return { raw: match[0], parsed, error: null };
  } catch (e) {
    return { raw: match[0], parsed: null, error: 'YAML_PARSE_ERROR' };
  }
}

function validateFrontmatter(parsed, filePath) {
  const errors = [];

  // 1. Check required fields
  for (const field of REQUIRED_FIELDS) {
    if (!parsed.hasOwnProperty(field) || parsed[field] === null || parsed[field] === '') {
      errors.push(`MISSING_REQUIRED: ${field}`);
    }
  }

  // 2. Validate schema_version
  const schemaVer = String(parsed.schema_version || '').replace(/"/g, '');
  if (parsed.schema_version && !VALID_SCHEMA_VERSIONS.includes(schemaVer)) {
    errors.push(`INVALID_SCHEMA_VERSION: '${schemaVer}' — expected one of ${VALID_SCHEMA_VERSIONS.join(', ')}`);
  }

  // 3. Validate status
  if (parsed.status && !VALID_STATUSES.includes(parsed.status)) {
    errors.push(`INVALID_STATUS: '${parsed.status}' — expected one of ${VALID_STATUSES.join(', ')}`);
  }

  // 4. Validate owner
  if (parsed.owner && !VALID_OWNERS.includes(parsed.owner)) {
    errors.push(`INVALID_OWNER: '${parsed.owner}' — expected one of ${VALID_OWNERS.join(', ')}`);
  }

  // 5. Validate domain
  if (parsed.domain && !VALID_DOMAINS.includes(parsed.domain)) {
    errors.push(`INVALID_DOMAIN: '${parsed.domain}' — expected one of ${VALID_DOMAINS.join(', ')}`);
  }

  // 6. Validate id format (non-empty string)
  if (parsed.id && typeof parsed.id !== 'string') {
    errors.push(`INVALID_ID_TYPE: id must be a string`);
  }

    // 7. Validate version format
    if (parsed.version && !/^\d+\.\d+/.test(String(parsed.version).replace(/"/g, ''))) {
      errors.push(`INVALID_VERSION_FORMAT: '${parsed.version}' — expected format: X.Y`);
    }

  // 8. Check for unexpected fields (warning, not error)
  const allFields = [...REQUIRED_FIELDS, ...OPTIONAL_FIELDS];
  const extraFields = Object.keys(parsed).filter(k => !allFields.includes(k));
  const warnings = extraFields.map(f => `UNKNOWN_FIELD: '${f}'`);

  return { errors, warnings };
}

function classifyFile(filePath) {
  const rel = path.relative(PROJECT_ROOT, filePath);

  // Immutable/canonical governance files — always required
  if (/^(docs\/SAB|docs\/governance\/EVIDENCE_MODEL|docs\/governance\/CERTIFICATION)/.test(rel)) {
    return 'canonical';
  }
  // Sprint charters and progress trackers
  if (/^docs\/(plans|sprints|governance|memory)/.test(rel)) {
    return 'governance';
  }
  // ADRs
  if (/^docs\/adr\//.test(rel)) {
    return 'adr';
  }
  // Memory files
  if (/^memory\//.test(rel)) {
    return 'memory';
  }
  // Regular markdown
  if (rel.endsWith('.md')) {
    return 'other';
  }
  return null;
}

function getInconsistencyLevel(parsed, filePath, classify) {
  if (!parsed) return 'CRITICAL'; // No frontmatter at all
  if (classify === 'canonical' || classify === 'governance' || classify === 'adr') {
    const { errors } = validateFrontmatter(parsed, filePath);
    return errors.length > 0 ? 'CRITICAL' : 'OK';
  }
  if (classify === 'other' || classify === 'memory') {
    const { errors } = validateFrontmatter(parsed, filePath);
    return errors.length > 0 ? 'INCONSISTENT' : 'OK';
  }
  return 'OK';
}

function getLabel(inconsistencyLevel, errors, warnings) {
  if (inconsistencyLevel === 'OK') return '✅ VALID';
  if (inconsistencyLevel === 'CRITICAL') return `❌ CRITICAL (${errors.length} error${errors.length !== 1 ? 's' : ''})`;
  if (inconsistencyLevel === 'INCONSISTENT') return `⚠️  INCONSISTENT (${errors.length} error${errors.length !== 1 ? 's' : ''})`;
  if (inconsistencyLevel === 'NO_FRONTMATTER') return '❌ NO_FRONTMATTER';
  if (inconsistencyLevel === 'YAML_PARSE_ERROR') return '❌ YAML_PARSE_ERROR';
  return '⚠️  UNKNOWN';
}

// ─── Main Scanner ────────────────────────────────────────────────────────────

function scanDirectory(dir, results = [], verbose = false) {
  if (!fs.existsSync(dir)) return results;

  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      // Skip certain directories
      if (/^(node_modules|vendor|storage|bootstrap|resources|tests)/.test(entry.name)) continue;
      if (/^\./.test(entry.name)) continue; // skip hidden dirs
      scanDirectory(fullPath, results, verbose);
    } else if (entry.name.endsWith('.md')) {
      const content = fs.readFileSync(fullPath, 'utf8');
      const rel = path.relative(PROJECT_ROOT, fullPath);
      const classify = classifyFile(fullPath);
      const { raw, parsed, error } = parseFrontmatter(content);

      let inconsistencyLevel = 'OK';
      let errors = [];
      let warnings = [];

      if (error) {
        inconsistencyLevel = error;
        errors.push(error);
      } else {
        const validation = validateFrontmatter(parsed, fullPath);
        errors = validation.errors;
        warnings = validation.warnings;
        inconsistencyLevel = getInconsistencyLevel(parsed, fullPath, classify);
      }

      const result = {
        file: rel,
        type: classify,
        level: inconsistencyLevel,
        errors,
        warnings,
        has_frontmatter: !!raw,
        raw_frontmatter: raw,
        metadata: parsed,
      };

      results.push(result);

      if (verbose || inconsistencyLevel !== 'OK') {
        const label = getLabel(inconsistencyLevel, errors, warnings);
        console.log(`  ${label}  ${rel}`);
        for (const e of errors) console.log(`         ✗ ${e}`);
        for (const w of warnings) console.log(`         ~ ${w}`);
      }
    }
  }

  return results;
}

// ─── Report Generation ───────────────────────────────────────────────────────

function generateReport(results) {
  const byLevel = { CRITICAL: [], INCONSISTENT: [], NO_FRONTMATTER: [], YAML_PARSE_ERROR: [], OK: [] };

  for (const r of results) {
    byLevel[r.level] = byLevel[r.level] || [];
    byLevel[r.level].push(r);
  }

  const total = results.length;
  const critical = (byLevel.CRITICAL || []).length;
  const inconsistent = (byLevel.INCONSISTENT || []).length;
  const noFm = (byLevel.NO_FRONTMATTER || []).length;
  const yamlErr = (byLevel.YAML_PARSE_ERROR || []).length;
  const ok = (byLevel.OK || []).length;

  const summary = {
    scanned_at: new Date().toISOString(),
    total_files: total,
    schema_version: '1.0',
    summary: {
      OK: ok,
      INCONSISTENT: inconsistent,
      CRITICAL: critical,
      NO_FRONTMATTER: noFm,
      YAML_PARSE_ERROR: yamlErr,
    },
    health_score: total > 0 ? Math.round((ok / total) * 100) : 100,
    files: results.map(r => ({
      file: r.file,
      type: r.type,
      level: r.level,
      errors: r.errors,
      warnings: r.warnings,
      has_frontmatter: r.has_frontmatter,
    })),
  };

  return summary;
}

// ─── Entry Point ─────────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const jsonOnly = process.argv.includes('--json');
const showAll = process.argv.includes('--all');

console.log('📋 Documentation Metadata Linter — Sprint 21 Step 1');
console.log('   Schema: Documentation Metadata Standard v1.0');
console.log('   Root:  docs/ + memory/');
console.log('');

// Ensure report directory exists
if (!fs.existsSync(REPORT_DIR)) {
  fs.mkdirSync(REPORT_DIR, { recursive: true });
}

const docsResults = scanDirectory(path.join(PROJECT_ROOT, 'docs'), [], verbose || showAll);
const memoryResults = scanDirectory(path.join(PROJECT_ROOT, 'memory'), [], verbose || showAll);
const allResults = [...docsResults, ...memoryResults];

const report = generateReport(allResults);

// Write JSON report
fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));

// Console summary
console.log('');
console.log('─── Summary ───────────────────────────────────────────');
console.log(`  Total files scanned: ${report.total_files}`);
console.log(`  ✅ OK:              ${report.summary.OK}`);
console.log(`  ⚠️  INCONSISTENT:   ${report.summary.INCONSISTENT}`);
console.log(`  ❌ CRITICAL:        ${report.summary.CRITICAL}`);
console.log(`  ❌ NO_FRONTMATTER:  ${report.summary.NO_FRONTMATTER}`);
console.log(`  ❌ YAML_PARSE_ERROR:${report.summary.YAML_PARSE_ERROR}`);
console.log(`  Health Score:       ${report.health_score}/100`);
console.log(`  Report:            ${REPORT_PATH}`);
console.log('');

if (report.health_score < 100) {
  console.log('  ⚠️  Metadata inconsistencies detected. Run with --verbose for details.');
}

if (jsonOnly) {
  console.log(JSON.stringify(report, null, 2));
}

// Exit code: 0 if all files are OK, 1 if any issues
const hasIssues = report.summary.CRITICAL > 0 || report.summary.YAML_PARSE_ERROR > 0 || report.summary.NO_FRONTMATTER > 0;
process.exit(hasIssues ? 1 : 0);
