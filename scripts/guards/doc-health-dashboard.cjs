/**
 * Documentation Health Dashboard — Sprint 21 Step 6
 * Unified view consuming all five capability reports.
 *
 * Sources consumed:
 *   - metadata_lint_report.json          (Step 1)
 *   - lifecycle_pipeline_report.json    (Step 2)
 *   - canonical_inference_report.json   (Step 3)
 *   - risk_engine_report.json           (Step 4)
 *   - semantic_duplicate_report.json     (Step 5)
 *
 * NO new analysis logic.
 * Dashboard aggregates; governance decides.
 *
 * Usage: node scripts/guards/doc-health-dashboard.cjs [--verbose] [--json]
 * Output: docs/_reports/documentation_health.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const DASHBOARD_PATH = path.join(REPORT_DIR, 'documentation_health.json');

// ─── Source Reports ─────────────────────────────────────────────────────────

const SOURCES = [
  { name: 'Metadata Linter',      path: 'metadata_lint_report.json',        key: 'lint' },
  { name: 'Lifecycle Pipeline',    path: 'lifecycle_pipeline_report.json',   key: 'lifecycle' },
  { name: 'Canonical Inference',   path: 'canonical_inference_report.json',  key: 'inference' },
  { name: 'Risk Engine',          path: 'risk_engine_report.json',         key: 'risk' },
  { name: 'Semantic Duplicate',    path: 'semantic_duplicate_report.json',   key: 'duplicate' },
];

// ─── Health Score Weights ───────────────────────────────────────────────────

const WEIGHTS = {
  metadataCoverage:    15,  // % of docs with valid frontmatter
  lifecycleValid:     20,  // % of docs with valid lifecycle status
  canonicalCoverage:   20,  // % of governance docs that are canonical
  riskScore:          25,  // inverse: SAFE% - BLOCKED%
  brokenLinkPenalty: 10,  // penalty per broken link (scaled)
  duplicatePenalty:   10,  // penalty per significant cluster
};

// ─── Load Source Reports ────────────────────────────────────────────────────

function loadReports() {
  const reports = {};
  for (const src of SOURCES) {
    const fullPath = path.join(REPORT_DIR, src.path);
    try {
      reports[src.key] = JSON.parse(fs.readFileSync(fullPath, 'utf8'));
      console.log(`  ✅ ${src.name}: ${reports[src.key].total_files || reports[src.key].scanned_at} loaded`);
    } catch (e) {
      reports[src.key] = null;
      console.log(`  ⚠️  ${src.name}: not found — run source capability first`);
    }
  }
  return reports;
}

// ─── Metric Calculations ────────────────────────────────────────────────────

function computeMetrics(reports) {
  const r = reports;

  // ── Metadata Coverage (Step 1)
  const total = r.lint ? r.lint.total_files : 0;
  const withFm = r.lint ? (r.lint.summary.OK + r.lint.summary.INCONSISTENT) : 0;
  const fmCoverage = total > 0 ? Math.round((withFm / total) * 100) : 0;

  // ── Lifecycle Validity (Step 2)
  const withStatus = r.lifecycle ? Object.values(r.lifecycle.by_status || {}).reduce((s, v) => s + v, 0) : 0;
  const lifecycleValid = total > 0 ? Math.round((withStatus / total) * 100) : 0;
  const canonicalCount = r.lifecycle ? (r.lifecycle.by_status.canonical || 0) : 0;
  const governanceCount = total > 0 ? total - (r.lifecycle ? r.lifecycle.by_status.NULL || 0 : 0) : 0;
  const canonicalCoverage = governanceCount > 0 ? Math.round((canonicalCount / governanceCount) * 100) : 0;

  // ── Inference / SSOT Candidates (Step 3)
  const ssotHigh = r.inference ? r.inference.canonical_candidates.HIGH : 0;
  const ssotMedium = r.inference ? r.inference.canonical_candidates.MEDIUM : 0;

  // ── Risk Distribution (Step 4)
  const safeCount = r.risk ? (r.risk.by_risk.SAFE || 0) : 0;
  const reviewCount = r.risk ? (r.risk.by_risk.REVIEW || 0) : 0;
  const archiveCount = r.risk ? (r.risk.by_risk.ARCHIVE || 0) : 0;
  const govCount = r.risk ? (r.risk.by_risk.GOVERNANCE || 0) : 0;
  const blockedCount = r.risk ? (r.risk.by_risk.BLOCKED || 0) : 0;
  const riskTotal = safeCount + reviewCount + archiveCount + govCount + blockedCount;
  const riskScore = riskTotal > 0
    ? Math.round(((safeCount * 100) / riskTotal) - ((blockedCount * 50) / riskTotal) - ((archiveCount * 5) / riskTotal))
    : 0;
  const riskScoreClamped = Math.max(0, Math.min(100, riskScore));

  // ── Broken Links (Step 4)
  const brokenLinkCount = r.risk ? r.risk.broken_link_summary.total_broken_links : 0;
  const filesWithBrokenLinks = r.risk ? r.risk.broken_link_summary.files_with_broken_links : 0;
  const brokenLinkPenalty = Math.min(WEIGHTS.brokenLinkPenalty, Math.round(brokenLinkCount * 0.05));

  // ── Semantic Clusters (Step 5)
  const totalClusters = r.duplicate ? r.duplicate.clusters_found : 0;
  const significantClusters = r.duplicate ? (
    (r.duplicate.by_classification.DUPLICATE_CANDIDATE || 0) +
    (r.duplicate.by_classification.EVOLVED || 0) +
    (r.duplicate.by_classification.REVISION || 0)
  ) : 0;
  const duplicatePenalty = Math.min(WEIGHTS.duplicatePenalty, Math.round(significantClusters * 2));

  // ── Overall Health Score (0–100)
  const healthScore = Math.max(0, Math.min(100, Math.round(
    (fmCoverage * WEIGHTS.metadataCoverage / 100) +
    (lifecycleValid * WEIGHTS.lifecycleValid / 100) +
    (canonicalCoverage * WEIGHTS.canonicalCoverage / 100) +
    (riskScoreClamped * WEIGHTS.riskScore / 100) -
    brokenLinkPenalty -
    duplicatePenalty
  )));

  return {
    total,
    with_frontmatter: withFm,
    metadata_coverage: fmCoverage,
    lifecycle_valid: lifecycleValid,
    canonical_count: canonicalCount,
    canonical_coverage: canonicalCoverage,
    governance_docs: govCount,
    ssot_candidates: { high: ssotHigh, medium: ssotMedium },
    risk_distribution: {
      SAFE: safeCount,
      REVIEW: reviewCount,
      ARCHIVE: archiveCount,
      GOVERNANCE: govCount,
      BLOCKED: blockedCount,
      total: riskTotal,
    },
    risk_score: riskScoreClamped,
    broken_links: {
      total: brokenLinkCount,
      files_affected: filesWithBrokenLinks,
    },
    duplicate_clusters: {
      total: totalClusters,
      significant: significantClusters,
    },
    penalties: {
      broken_link_penalty: brokenLinkPenalty,
      duplicate_penalty: duplicatePenalty,
    },
    health_score: healthScore,
  };
}

// ─── Sub-scores for Dashboard ───────────────────────────────────────────────

function computeSubScores(metrics) {
  return {
    overall_health_score: {
      value: metrics.health_score,
      grade: metrics.health_score >= 80 ? 'A' : metrics.health_score >= 60 ? 'B' : metrics.health_score >= 40 ? 'C' : metrics.health_score >= 20 ? 'D' : 'F',
      label: metrics.health_score >= 80 ? 'Excellent' :
             metrics.health_score >= 60 ? 'Good' :
             metrics.health_score >= 40 ? 'Fair' :
             metrics.health_score >= 20 ? 'Poor' : 'Critical',
    },
    metadata_coverage_score: {
      value: metrics.metadata_coverage,
      label: metrics.metadata_coverage >= 80 ? 'Excellent' :
             metrics.metadata_coverage >= 60 ? 'Good' :
             metrics.metadata_coverage >= 40 ? 'Fair' : 'Needs Work',
      weight: WEIGHTS.metadataCoverage,
    },
    lifecycle_score: {
      value: metrics.lifecycle_valid,
      label: metrics.lifecycle_valid >= 80 ? 'Excellent' :
             metrics.lifecycle_valid >= 60 ? 'Good' :
             metrics.lifecycle_valid >= 40 ? 'Fair' : 'Needs Work',
      weight: WEIGHTS.lifecycleValid,
    },
    canonical_coverage_score: {
      value: metrics.canonical_coverage,
      label: metrics.canonical_coverage >= 80 ? 'Excellent' :
             metrics.canonical_coverage >= 60 ? 'Good' :
             metrics.canonical_coverage >= 40 ? 'Fair' : 'Needs Work',
      weight: WEIGHTS.canonicalCoverage,
    },
    risk_score: {
      value: metrics.risk_score,
      label: metrics.risk_score >= 80 ? 'Low Risk' :
             metrics.risk_score >= 60 ? 'Moderate Risk' :
             metrics.risk_score >= 40 ? 'Elevated Risk' : 'High Risk',
      weight: WEIGHTS.riskScore,
    },
  };
}

// ─── Action Items ────────────────────────────────────────────────────────────

function generateActionItems(metrics, reports) {
  const actions = [];

  if (metrics.metadata_coverage < 50) {
    actions.push({
      priority: 'HIGH',
      area: 'Metadata Coverage',
      message: `${metrics.metadata_coverage}% of docs have frontmatter. Target: 50%+`,
      suggested: 'Run `npm run docs:metadata:lint -- --verbose` to identify missing frontmatter files',
    });
  }

  if (metrics.broken_links.total > 0) {
    actions.push({
      priority: metrics.broken_links.total > 100 ? 'HIGH' : 'MEDIUM',
      area: 'Broken Links',
      message: `${metrics.broken_links.total} broken links in ${metrics.broken_links.files_affected} files`,
      suggested: 'Review risk_engine_report.json for affected files',
    });
  }

  if (metrics.risk_distribution.ARCHIVE > 20) {
    actions.push({
      priority: 'MEDIUM',
      area: 'Archive Candidates',
      message: `${metrics.risk_distribution.ARCHIVE} documents classified as ARCHIVE risk`,
      suggested: 'SAAB review recommended for archival decisions',
    });
  }

  if (metrics.duplicate_clusters.significant > 0) {
    actions.push({
      priority: 'LOW',
      area: 'Duplicate Clusters',
      message: `${metrics.duplicate_clusters.significant} significant duplicate/revision clusters found`,
      suggested: 'Review semantic_duplicate_report.json for cluster details',
    });
  }

  if (metrics.risk_distribution.BLOCKED > 0) {
    actions.push({
      priority: 'HIGH',
      area: 'Blocked Documents',
      message: `${metrics.risk_distribution.BLOCKED} documents classified as BLOCKED risk`,
      suggested: 'SAAB must review before any changes to these documents',
    });
  }

  return actions;
}

// ─── Report Generation ───────────────────────────────────────────────────────

function generateReport(metrics, subScores, actions) {
  const timestamp = new Date().toISOString();

  return {
    generated_at: timestamp,
    sprint: 'Sprint 21',
    step: 'Step 6 — Documentation Health Dashboard',
    principle: 'Dashboard aggregates. Governance decides.',
    sources: SOURCES.map(s => ({ name: s.name, key: s.key, path: s.path })),
    overall_health_score: subScores.overall_health_score,
    sub_scores: {
      metadata_coverage_score: subScores.metadata_coverage_score,
      lifecycle_score: subScores.lifecycle_score,
      canonical_coverage_score: subScores.canonical_coverage_score,
      risk_score: subScores.risk_score,
    },
    raw_metrics: {
      total_docs: metrics.total,
      with_frontmatter: metrics.with_frontmatter,
      metadata_coverage_pct: metrics.metadata_coverage,
      lifecycle_valid_pct: metrics.lifecycle_valid,
      canonical_count: metrics.canonical_count,
      canonical_coverage_pct: metrics.canonical_coverage,
      governance_docs: metrics.governance_docs,
      ssot_candidates: metrics.ssot_candidates,
      risk_distribution: metrics.risk_distribution,
      risk_score_pct: metrics.risk_score,
      broken_links: metrics.broken_links,
      duplicate_clusters: metrics.duplicate_clusters,
      penalties: metrics.penalties,
    },
    action_items: actions,
    evidence_files: SOURCES.map(s => s.path),
  };
}

// ─── Entry Point ─────────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const jsonOnly = process.argv.includes('--json');

console.log('');
console.log('📊 Documentation Health Dashboard — Sprint 21 Step 6');
console.log('   Principle: Dashboard aggregates. Governance decides.');
console.log('   Sources:  5 capability reports (Steps 1–5)');
console.log('');

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

console.log('  Loading source reports...');
const reports = loadReports();
console.log('');

const metrics = computeMetrics(reports);
const subScores = computeSubScores(metrics);
const actions = generateActionItems(metrics, reports);
const dashboard = generateReport(metrics, subScores, actions);

fs.writeFileSync(DASHBOARD_PATH, JSON.stringify(dashboard, null, 2));

// ── Console Output ──────────────────────────────────────────────────────────

const score = metrics.health_score;
const grade = score >= 80 ? '🟢' : score >= 60 ? '🟡' : score >= 40 ? '🟠' : '🔴';

console.log('─── Documentation Health Dashboard ─────────────────────');
console.log('');
console.log(`  ${grade} Overall Health Score: ${score}/100 (${subScores.overall_health_score.label})`);
console.log('');
console.log(`  Sub-scores:`);
console.log(`    Metadata Coverage:    ${metrics.metadata_coverage}% ${subScores.metadata_coverage_score.label}`);
console.log(`    Lifecycle Validity:  ${metrics.lifecycle_valid}% ${subScores.lifecycle_score.label}`);
console.log(`    Canonical Coverage:  ${metrics.canonical_coverage}% ${subScores.canonical_coverage_score.label}`);
console.log(`    Risk Score:          ${metrics.risk_score}% ${subScores.risk_score.label}`);
console.log('');
console.log(`  Detail:`);
console.log(`    Total docs:          ${metrics.total}`);
console.log(`    With frontmatter:    ${metrics.with_frontmatter}`);
console.log(`    Canonical docs:       ${metrics.canonical_count}`);
console.log(`    GOVERNANCE docs:      ${metrics.risk_distribution.GOVERNANCE}`);
console.log(`    ARCHIVE candidates:   ${metrics.risk_distribution.ARCHIVE}`);
console.log(`    BLOCKED docs:         ${metrics.risk_distribution.BLOCKED}`);
console.log(`    Broken links:         ${metrics.broken_links.total} (${metrics.broken_links.files_affected} files)`);
console.log(`    Duplicate clusters:   ${metrics.duplicate_clusters.significant} significant`);
console.log('');
console.log(`  Penalties applied:`);
console.log(`    Broken link penalty:  -${metrics.penalties.broken_link_penalty}`);
console.log(`    Duplicate penalty:    -${metrics.penalties.duplicate_penalty}`);
console.log('');

if (actions.length > 0) {
  console.log('  Action items:');
  for (const a of actions) {
    const icon = a.priority === 'HIGH' ? '🔴' : a.priority === 'MEDIUM' ? '🟡' : '🟢';
    console.log(`  ${icon} [${a.priority}] ${a.area}: ${a.message}`);
    if (verbose) console.log(`         → ${a.suggested}`);
  }
  console.log('');
}

console.log(`  Dashboard: ${DASHBOARD_PATH}`);
console.log('');
console.log('  ⚠️  Dashboard aggregates. Governance decides.');
console.log('  No automatic actions will be taken by this tool.');

if (jsonOnly) console.log(JSON.stringify(dashboard, null, 2));

process.exit(0); // Dashboard always succeeds
