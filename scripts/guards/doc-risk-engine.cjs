/**
 * Documentation Risk Engine — Sprint 21 Step 4
 * Produces risk classifications for documentation.
 *
 * Consumes output from:
 *   - doc-metadata-linter.cjs      (metadata signals)
 *   - doc-canonical-inference.cjs  (inference scores)
 *
 * Risk levels:
 *   SAFE       — Independent, no references, current status
 *   REVIEW     — Low references, review_due past or missing, age > 365 days
 *   ARCHIVE    — Superseded, old sprint/phase docs, orphaned content
 *   GOVERNANCE — SAAB documents, board resolutions, immutable by charter
 *   BLOCKED    — Referenced by @see in code or heavy cross-link dependency
 *
 * NO deletion or archiving decisions are made.
 * Tool classifies risk; governance decides action.
 *
 * Usage: node scripts/guards/doc-risk-engine.cjs [--verbose] [--json]
 * Output: docs/_reports/risk_engine_report.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const LINT_REPORT = path.join(REPORT_DIR, 'metadata_lint_report.json');
const INFERENCE_REPORT = path.join(REPORT_DIR, 'canonical_inference_report.json');
const RISK_REPORT = path.join(REPORT_DIR, 'risk_engine_report.json');

// ─── Risk Level Definitions ────────────────────────────────────────────────────

const RISK_LEVELS = {
  SAFE:       { priority: 1, label: 'SAFE',       symbol: '🟢' },
  REVIEW:     { priority: 2, label: 'REVIEW',     symbol: '🟡' },
  ARCHIVE:    { priority: 3, label: 'ARCHIVE',    symbol: '🟠' },
  GOVERNANCE: { priority: 4, label: 'GOVERNANCE', symbol: '🔵' },
  BLOCKED:    { priority: 5, label: 'BLOCKED',    symbol: '🔴' },
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) return null;
  try {
    const parsed = {};
    for (const line of match[1].split(/\r?\n/)) {
      const idx = line.indexOf(':');
      if (idx === -1) continue;
      const key = line.slice(0, idx).trim();
      const raw = line.slice(idx + 1).trim();
      if (raw === '' || raw === '[]' || raw === '{}') parsed[key] = [];
      else parsed[key] = raw;
    }
    return parsed;
  } catch (e) { return null; }
}

function getDocAge(createdAt) {
  if (!createdAt) return Infinity;
  const created = new Date(createdAt);
  const now = new Date();
  return Math.max(0, (now - created) / (1000 * 60 * 60 * 24));
}

function isGovernancePath(file) {
  return /^(docs\/SAB|docs\/governance\/|docs\/plans\/SPRINT_\d|docs\/adr\/|memory\/)/.test(file);
}

function isSuperseded(metadata) {
  return metadata && (
    metadata.status === 'superseded' ||
    metadata.status === 'archived' ||
    (Array.isArray(metadata.superseded_by) && metadata.superseded_by.length > 0)
  );
}

function isOldSprintOrPhase(file) {
  return /\/SPRINT_\d|Sprint_\d|Sprint\d|OTURUM_\d|PHASE_\d/i.test(file);
}

function isDraftOrReview(metadata) {
  return metadata && (metadata.status === 'draft' || metadata.status === 'review');
}

function hasMissingFrontmatter(file, lintReport) {
  if (!lintReport || !lintReport.files) return false;
  const entry = lintReport.files.find(f => f.file === file);
  return entry && (entry.level === 'NO_FRONTMATTER' || entry.level === 'YAML_PARSE_ERROR');
}

function getInferenceScore(file, inferenceReport) {
  const entry = inferenceReport.candidates.find(c => c.file === file);
  return entry ? entry.score : 0;
}

function getReferenceCount(content) {
  const links = content.match(/\[([^\]]+)\]\(([^)]+)\)/g) || [];
  return links.length;
}

function isCodeReferenced(file) {
  // Check if any PHP/JS file contains a @see or reference to this doc
  return false; // Deferred — requires AST analysis; not in Sprint 21 scope
}

// ─── Broken Link Analysis ────────────────────────────────────────────────────

function buildFileIndex(root) {
  const index = new Set();
  function walk(dir) {
    if (!fs.existsSync(dir)) return;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const e of entries) {
      const full = path.join(dir, e.name);
      if (e.isDirectory()) {
        if (/^(node_modules|vendor|bootstrap|resources|tests|storage)/.test(e.name)) continue;
        if (/^\./.test(e.name)) continue;
        walk(full);
      } else {
        index.add(path.relative(PROJECT_ROOT, full));
      }
    }
  }
  walk(root);
  return index;
}

function findBrokenLinks(file, content, fileIndex) {
  const links = content.match(/\[([^\]]+)\]\(([^)]+)\)/g) || [];
  const broken = [];

  for (const link of links) {
    const urlMatch = link.match(/\]\(([^)]+)\)/);
    if (!urlMatch) continue;
    const target = urlMatch[1];

    // Skip external URLs and anchors
    if (/^https?:\/\//.test(target) || target.startsWith('#')) continue;

    // Resolve relative path
    const baseDir = path.dirname(file);
    const resolved = path.normalize(path.join(baseDir, target));

    // Check if resolved file exists (with common extensions)
    const extensions = ['', '.md', '.blade.php', '.php', '.js'];
    let found = false;
    for (const ext of extensions) {
      const checkPath = path.relative(PROJECT_ROOT, resolved + ext);
      if (fileIndex.has(checkPath) || fs.existsSync(path.join(PROJECT_ROOT, checkPath))) {
        found = true;
        break;
      }
    }
    if (!found) {
      broken.push({ link, target, resolved: path.relative(PROJECT_ROOT, resolved) });
    }
  }

  return broken;
}

// ─── Risk Classification ─────────────────────────────────────────────────────

function classifyRisk(file, metadata, brokenLinks, refCount, inferenceScore, isReferencedByCode) {
  const reasons = [];

  // GOVERNANCE: governance documents are high-value, low-risk for deletion
  if (isGovernancePath(file)) {
    reasons.push('Governance document (SAAB/ADR/Sprint Charter)');
    return { risk: 'GOVERNANCE', reasons, priority: RISK_LEVELS.GOVERNANCE.priority };
  }

  // BLOCKED: referenced by code or extremely high cross-reference density
  if (isReferencedByCode) {
    reasons.push('Referenced by source code (@see annotation)');
    return { risk: 'BLOCKED', reasons, priority: RISK_LEVELS.BLOCKED.priority };
  }

  // Superseded status
  if (isSuperseded(metadata)) {
    reasons.push(`Status is '${metadata.status}' or superseded_by set`);
    return { risk: 'ARCHIVE', reasons, priority: RISK_LEVELS.ARCHIVE.priority };
  }

  // Old sprint/phase document
  if (isOldSprintOrPhase(file)) {
    reasons.push('Historical sprint or phase document');
    return { risk: 'ARCHIVE', reasons, priority: RISK_LEVELS.ARCHIVE.priority };
  }

  // Missing frontmatter with no references = orphaned
  if (hasMissingFrontmatter(file, lintReport) && refCount === 0) {
    reasons.push('No frontmatter + no internal references (orphaned)');
    return { risk: 'REVIEW', reasons, priority: RISK_LEVELS.REVIEW.priority };
  }

  // Broken links = maintenance risk
  if (brokenLinks.length > 0) {
    reasons.push(`${brokenLinks.length} broken link(s)`);
    // If more than 2 broken links, elevate to ARCHIVE
    if (brokenLinks.length >= 3) {
      reasons.push(`${brokenLinks.length} broken links → ARCHIVE candidate`);
      return { risk: 'ARCHIVE', reasons, priority: RISK_LEVELS.ARCHIVE.priority };
    }
  }

  // Age + review_due past
  const age = getDocAge(metadata ? metadata.created_at : null);
  if (age > 365 && !metadata) {
    reasons.push(`No metadata + age > 365 days (${Math.round(age)} days)`);
    return { risk: 'REVIEW', reasons, priority: RISK_LEVELS.REVIEW.priority };
  }

  if (metadata) {
    if (metadata.review_due) {
      const due = new Date(metadata.review_due);
      if (due < new Date()) {
        reasons.push(`review_due past: ${metadata.review_due}`);
      }
    }
    if (!metadata.review_due && (metadata.status === 'canonical' || metadata.status === 'approved')) {
      reasons.push('Canonical/Approved doc with no review_due set');
    }
    if (!metadata.review_due && age > 180) {
      reasons.push('No review_due + age > 180 days');
    }
  }

  if (reasons.length > 0) {
    return { risk: 'REVIEW', reasons, priority: RISK_LEVELS.REVIEW.priority };
  }

  // SAFE: has frontmatter, current status, reasonable references, no issues
  if (metadata && (metadata.status === 'canonical' || metadata.status === 'approved') && refCount > 0) {
    reasons.push('Current canonical status + internal references');
    return { risk: 'SAFE', reasons, priority: RISK_LEVELS.SAFE.priority };
  }

  // Default: no strong risk signals
  if (!metadata) {
    reasons.push('No frontmatter (inconclusive)');
  } else {
    reasons.push(`Status: ${metadata.status || 'null'}, age: ${Math.round(age)} days, refs: ${refCount}`);
  }
  return { risk: 'SAFE', reasons, priority: RISK_LEVELS.SAFE.priority };
}

// ─── Scanner ────────────────────────────────────────────────────────────────

function scanMarkdownFiles(dir, fileIndex, lintReport, inferenceReport, results = [], verbose = false) {
  if (!fs.existsSync(dir)) return results;

  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      if (/^(node_modules|vendor|bootstrap|resources|tests|storage)/.test(entry.name)) continue;
      if (/^\./.test(entry.name)) continue;
      scanMarkdownFiles(fullPath, fileIndex, lintReport, inferenceReport, results, verbose);
    } else if (entry.name.endsWith('.md')) {
      const rel = path.relative(PROJECT_ROOT, fullPath);
      const content = fs.readFileSync(fullPath, 'utf8');
      const metadata = parseFrontmatter(content);
      const refCount = getReferenceCount(content);
      const brokenLinks = findBrokenLinks(rel, content, fileIndex);
      const inferenceScore = getInferenceScore(rel, inferenceReport);
      const isReferencedByCode = isCodeReferenced(rel);

      const { risk, reasons, priority } = classifyRisk(rel, metadata, brokenLinks, refCount, inferenceScore, isReferencedByCode);

      const result = {
        file: rel,
        risk,
        risk_priority: priority,
        reasons,
        metadata_status: metadata ? metadata.status : null,
        owner: metadata ? metadata.owner : null,
        domain: metadata ? metadata.domain : null,
        has_frontmatter: !!metadata,
        ref_count: refCount,
        broken_links: brokenLinks.length,
        broken_link_samples: brokenLinks.slice(0, 3).map(b => b.target),
        inference_score: inferenceScore,
        age_days: metadata ? Math.round(getDocAge(metadata.created_at)) : null,
        is_governance: isGovernancePath(rel),
        is_superseded: isSuperseded(metadata),
        is_old_sprint: isOldSprintOrPhase(rel),
      };

      results.push(result);

      if (verbose || brokenLinks.length > 0 || risk === 'BLOCKED' || risk === 'ARCHIVE') {
        const sym = RISK_LEVELS[risk].symbol;
        console.log(`  ${sym} [${risk.padEnd(10)}] ${rel}`);
        for (const r of reasons) console.log(`         → ${r}`);
        if (brokenLinks.length > 0) console.log(`         🔗 ${brokenLinks.length} broken link(s)`);
      }
    }
  }

  return results;
}

// ─── Report ────────────────────────────────────────────────────────────────

function generateReport(results) {
  const byRisk = { SAFE: [], REVIEW: [], ARCHIVE: [], GOVERNANCE: [], BLOCKED: [] };
  for (const r of results) byRisk[r.risk].push(r);

  const summary = {
    scanned_at: new Date().toISOString(),
    total_files: results.length,
    by_risk: {
      SAFE:       byRisk.SAFE.length,
      REVIEW:     byRisk.REVIEW.length,
      ARCHIVE:    byRisk.ARCHIVE.length,
      GOVERNANCE: byRisk.GOVERNANCE.length,
      BLOCKED:    byRisk.BLOCKED.length,
    },
    broken_link_summary: {
      files_with_broken_links: results.filter(r => r.broken_links > 0).length,
      total_broken_links: results.reduce((sum, r) => sum + r.broken_links, 0),
    },
    governance_docs: byRisk.GOVERNANCE.length,
    archive_candidates: byRisk.ARCHIVE.length,
    review_candidates: byRisk.REVIEW.length,
    files: results.sort((a, b) => a.risk_priority - b.risk_priority).map(r => ({
      file: r.file,
      risk: r.risk,
      reasons: r.reasons,
      metadata_status: r.metadata_status,
      owner: r.owner,
      has_frontmatter: r.has_frontmatter,
      ref_count: r.ref_count,
      broken_links: r.broken_links,
      inference_score: r.inference_score,
      age_days: r.age_days,
    })),
  };

  return summary;
}

// ─── Entry Point ───────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const showAll = process.argv.includes('--all');
const jsonOnly = process.argv.includes('--json');

console.log('🔍 Documentation Risk Engine — Sprint 21 Step 4');
console.log('   Principle: Tool classifies risk. Governance decides action.');
console.log('   Root:     docs/ + memory/');
console.log('');

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

// Load dependent reports
let lintReport = { files: [] };
let inferenceReport = { candidates: [] };

try {
  lintReport = JSON.parse(fs.readFileSync(LINT_REPORT, 'utf8'));
  console.log(`  📋 Loaded: metadata_lint_report.json (${lintReport.total_files} files)`);
} catch (e) {
  console.log(`  ⚠️  metadata_lint_report.json not found — run doc-metadata-linter.cjs first`);
}

try {
  inferenceReport = JSON.parse(fs.readFileSync(INFERENCE_REPORT, 'utf8'));
  console.log(`  📋 Loaded: canonical_inference_report.json (${inferenceReport.total_files} files)`);
} catch (e) {
  console.log(`  ⚠️  canonical_inference_report.json not found — run doc-canonical-inference.cjs first`);
}

console.log('');

// Build file index for broken link detection
console.log('  🔗 Building file index...');
const fileIndex = buildFileIndex(PROJECT_ROOT);
console.log(`  📁 ${fileIndex.size} files indexed`);

const docsResults = scanMarkdownFiles(
  path.join(PROJECT_ROOT, 'docs'), fileIndex, lintReport, inferenceReport, [], verbose || showAll
);
const memoryResults = scanMarkdownFiles(
  path.join(PROJECT_ROOT, 'memory'), fileIndex, lintReport, inferenceReport, [], verbose || showAll
);
const allResults = [...docsResults, ...memoryResults];

const report = generateReport(allResults);
fs.writeFileSync(RISK_REPORT, JSON.stringify(report, null, 2));

console.log('');
console.log('─── Summary ───────────────────────────────────────────');
console.log(`  Total files scanned:  ${report.total_files}`);
console.log(`  🟢 SAFE:           ${report.by_risk.SAFE}`);
console.log(`  🟡 REVIEW:         ${report.by_risk.REVIEW}`);
console.log(`  🟠 ARCHIVE:        ${report.by_risk.ARCHIVE}`);
console.log(`  🔵 GOVERNANCE:     ${report.by_risk.GOVERNANCE}`);
console.log(`  🔴 BLOCKED:        ${report.by_risk.BLOCKED}`);
console.log('');
console.log(`  🔗 Files with broken links:  ${report.broken_link_summary.files_with_broken_links}`);
console.log(`  🔗 Total broken links:        ${report.broken_link_summary.total_broken_links}`);
console.log('');
console.log(`  Report: ${RISK_REPORT}`);
console.log('');
console.log('  ⚠️  Tool classifies risk. Governance decides action.');
console.log('  No files will be deleted or archived by this tool.');

if (jsonOnly) console.log(JSON.stringify(report, null, 2));

process.exit(0); // Risk classification is advisory — never fails
