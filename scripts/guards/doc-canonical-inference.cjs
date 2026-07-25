/**
 * Canonical Inference Engine — Sprint 21 Step 3
 * Produces evidence-based SSOT candidate scores.
 *
 * NO hardcoded canonical classifications.
 * NO final canonical decisions — tool proposes, governance decides.
 *
 * Scoring signals:
 *   - Metadata signals (status, evidence, supersedes, owner, domain)
 *   - Content signals (reference density — markdown links)
 *   - Lifecycle signals (age, review history)
 *
 * Output: ranked candidates with scores, reasons, and inference confidence.
 * Not a definitive canonical declaration.
 *
 * Usage: node scripts/guards/doc-canonical-inference.cjs [--verbose] [--json]
 * Output: docs/_reports/canonical_inference_report.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const REPORT_PATH = path.join(REPORT_DIR, 'canonical_inference_report.json');

// ─── Signal Weights ────────────────────────────────────────────────────────────

const SIGNAL_WEIGHTS = {
  // Metadata signals (0–40 points)
  status_is_canonical:      40,  // status === 'canonical'
  has_reviewed_at:          8,  // reviewed_at exists
  has_evidence_commits:    12,  // evidence.commits has entries
  has_evidence_tests:       4,  // evidence.tests has entries
  has_evidence_adr:          4,  // evidence.adr has entries
  supersedes_has_items:    -20,  // has superseded predecessors (normal transition)
  superseded_by_has_items:  -30,  // is superseded — strong decanonicalization signal
  owner_is_saab:           8,  // owner === 'saab'
  domain_is_governance:     6,  // domain === 'governance'
  domain_is_architecture:   5,  // domain === 'architecture'

  // Content signals (0–30 points)
  has_cross_references:    15,  // has markdown links to other docs
  reference_density_high:  15,  // >5 references per 1000 words

  // Lifecycle signals (0–30 points)
  age_under_90_days:      10,  // created_at within 90 days
  age_under_365_days:       5,  // created_at within 365 days
  review_due_future:        5,  // review_due is in the future
  review_due_past:         -5,  // review_due is in the past
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) return { parsed: null, frontmatter: null };
  try {
    const parsed = {};
    for (const line of match[1].split(/\r?\n/)) {
      const idx = line.indexOf(':');
      if (idx === -1) continue;
      const key = line.slice(0, idx).trim();
      const raw = line.slice(idx + 1).trim();
      if (raw === '' || raw === '[]' || raw === '{}') {
        parsed[key] = [];
      } else if (/^\d+-\d+-\d+$/.test(raw)) {
        parsed[key] = raw;
      } else {
        parsed[key] = raw;
      }
    }
    return { parsed, frontmatter: match[0] };
  } catch (e) {
    return { parsed: null, frontmatter: match[0] };
  }
}

function scoreMetadataSignals(parsed) {
  const signals = [];
  let score = 0;

  if (!parsed) return { score: 0, signals: ['NO_FRONTMATTER'], maxPossible: 80 };

  const maxPossible = 80; // metadata signals max

  if (parsed.status === 'canonical') {
    signals.push({ signal: 'status_is_canonical', value: true, weight: SIGNAL_WEIGHTS.status_is_canonical, reason: "status='canonical'" });
    score += SIGNAL_WEIGHTS.status_is_canonical;
  }
  if (parsed.status === 'superseded') {
    signals.push({ signal: 'status_is_superseded', value: true, weight: SIGNAL_WEIGHTS.superseded_by_has_items, reason: "status='superseded'" });
    score += SIGNAL_WEIGHTS.superseded_by_has_items;
  }

  if (parsed.reviewed_at) {
    signals.push({ signal: 'has_reviewed_at', value: true, weight: SIGNAL_WEIGHTS.has_reviewed_at, reason: 'reviewed_at exists' });
    score += SIGNAL_WEIGHTS.has_reviewed_at;
  }

  const evidence = parsed.evidence;
  if (evidence && evidence !== '[]' && evidence !== '{}') {
    if (Array.isArray(evidence.commits) && evidence.commits.length > 0) {
      signals.push({ signal: 'has_evidence_commits', value: true, weight: SIGNAL_WEIGHTS.has_evidence_commits, reason: `evidence.commits: ${evidence.commits.length} entries` });
      score += SIGNAL_WEIGHTS.has_evidence_commits;
    }
    if (Array.isArray(evidence.tests) && evidence.tests.length > 0) {
      signals.push({ signal: 'has_evidence_tests', value: true, weight: SIGNAL_WEIGHTS.has_evidence_tests, reason: `evidence.tests: ${evidence.tests.length} entries` });
      score += SIGNAL_WEIGHTS.has_evidence_tests;
    }
    if (Array.isArray(evidence.adr) && evidence.adr.length > 0) {
      signals.push({ signal: 'has_evidence_adr', value: true, weight: SIGNAL_WEIGHTS.has_evidence_adr, reason: `evidence.adr: ${evidence.adr.length} entries` });
      score += SIGNAL_WEIGHTS.has_evidence_adr;
    }
  }

  if (parsed.supersedes && Array.isArray(parsed.supersedes) && parsed.supersedes.length > 0) {
    signals.push({ signal: 'supersedes_has_items', value: true, weight: SIGNAL_WEIGHTS.supersedes_has_items, reason: `supersedes: ${parsed.supersedes.length} predecessors` });
    score += SIGNAL_WEIGHTS.supersedes_has_items;
  }

  if (parsed.superseded_by && Array.isArray(parsed.superseded_by) && parsed.superseded_by.length > 0) {
    signals.push({ signal: 'superseded_by_has_items', value: true, weight: SIGNAL_WEIGHTS.superseded_by_has_items, reason: `superseded_by: ${parsed.superseded_by.length} successors` });
    score += SIGNAL_WEIGHTS.superseded_by_has_items;
  }

  if (parsed.owner === 'saab') {
    signals.push({ signal: 'owner_is_saab', value: true, weight: SIGNAL_WEIGHTS.owner_is_saab, reason: "owner='saab'" });
    score += SIGNAL_WEIGHTS.owner_is_saab;
  }

  if (parsed.domain === 'governance') {
    signals.push({ signal: 'domain_is_governance', value: true, weight: SIGNAL_WEIGHTS.domain_is_governance, reason: "domain='governance'" });
    score += SIGNAL_WEIGHTS.domain_is_governance;
  } else if (parsed.domain === 'architecture') {
    signals.push({ signal: 'domain_is_architecture', value: true, weight: SIGNAL_WEIGHTS.domain_is_architecture, reason: "domain='architecture'" });
    score += SIGNAL_WEIGHTS.domain_is_architecture;
  }

  return { score, signals, maxPossible };
}

function scoreContentSignals(content, wordCount) {
  const signals = [];
  let score = 0;

  const linkMatches = content.match(/\[([^\]]+)\]\(([^)]+)\)/g) || [];
  const referenceCount = linkMatches.length;
  const refPer1000 = wordCount > 0 ? (referenceCount / wordCount) * 1000 : 0;

  if (referenceCount > 0) {
    signals.push({ signal: 'has_cross_references', value: true, weight: SIGNAL_WEIGHTS.has_cross_references, reason: `${referenceCount} markdown links found` });
    score += SIGNAL_WEIGHTS.has_cross_references;
  }

  if (refPer1000 > 5) {
    signals.push({ signal: 'reference_density_high', value: true, weight: SIGNAL_WEIGHTS.reference_density_high, reason: `Reference density: ${refPer1000.toFixed(1)}/1000 words` });
    score += SIGNAL_WEIGHTS.reference_density_high;
  }

  return { score, signals, maxPossible: 30 };
}

function scoreLifecycleSignals(parsed) {
  const signals = [];
  let score = 0;

  if (!parsed) return { score: 0, signals, maxPossible: 30 };

  const maxPossible = 30;

  if (parsed.created_at) {
    const created = new Date(parsed.created_at);
    const now = new Date();
    const ageDays = (now - created) / (1000 * 60 * 60 * 24);
    if (ageDays <= 90) {
      signals.push({ signal: 'age_under_90_days', value: true, weight: SIGNAL_WEIGHTS.age_under_90_days, reason: `Age: ${Math.round(ageDays)} days (< 90)` });
      score += SIGNAL_WEIGHTS.age_under_90_days;
    } else if (ageDays <= 365) {
      signals.push({ signal: 'age_under_365_days', value: true, weight: SIGNAL_WEIGHTS.age_under_365_days, reason: `Age: ${Math.round(ageDays)} days (< 365)` });
      score += SIGNAL_WEIGHTS.age_under_365_days;
    }
  }

  if (parsed.review_due) {
    const due = new Date(parsed.review_due);
    const now = new Date();
    if (due > now) {
      signals.push({ signal: 'review_due_future', value: true, weight: SIGNAL_WEIGHTS.review_due_future, reason: `review_due in future: ${parsed.review_due}` });
      score += SIGNAL_WEIGHTS.review_due_future;
    } else {
      signals.push({ signal: 'review_due_past', value: true, weight: SIGNAL_WEIGHTS.review_due_past, reason: `review_due past: ${parsed.review_due}` });
      score += SIGNAL_WEIGHTS.review_due_past;
    }
  }

  return { score, signals, maxPossible };
}

function classifyCandidate(score, maxPossible) {
  const pct = (score / maxPossible) * 100;
  if (pct >= 75) return 'HIGH';
  if (pct >= 40) return 'MEDIUM';
  if (score > 0) return 'LOW';
  return 'NONE';
}

function computeWordCount(content) {
  if (!content) return 0;
  return content.split(/\s+/).filter(w => w.length > 0).length;
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
      const { parsed, frontmatter } = parseFrontmatter(content);
      const wordCount = computeWordCount(content);

      const metadataResult = scoreMetadataSignals(parsed);
      const contentResult = scoreContentSignals(content, wordCount);
      const lifecycleResult = scoreLifecycleSignals(parsed);

      const totalScore = metadataResult.score + contentResult.score + lifecycleResult.score;
      const maxPossible = metadataResult.maxPossible + contentResult.maxPossible + lifecycleResult.maxPossible;
      const allSignals = [
        ...metadataResult.signals,
        ...contentResult.signals,
        ...lifecycleResult.signals,
      ];
      const candidate = classifyCandidate(totalScore, maxPossible);

      // Normalize to 0–100
      const normalizedScore = maxPossible > 0 ? Math.max(0, Math.min(100, Math.round((totalScore / maxPossible) * 100))) : 0;

      const result = {
        file: rel,
        score: normalizedScore,
        raw_score: totalScore,
        max_possible: maxPossible,
        candidate,
        status: parsed ? parsed.status : null,
        owner: parsed ? parsed.owner : null,
        domain: parsed ? parsed.domain : null,
        has_frontmatter: !!parsed,
        signals: allSignals.map(s => ({ signal: s.signal, reason: s.reason, contribution: s.weight })),
        superseded_by: parsed && Array.isArray(parsed.superseded_by) ? parsed.superseded_by : null,
        supersedes: parsed && Array.isArray(parsed.supersedes) ? parsed.supersedes : null,
      };

      results.push(result);

      if (verbose || candidate === 'HIGH') {
        const icon = candidate === 'HIGH' ? '🥇' : candidate === 'MEDIUM' ? '🥈' : candidate === 'LOW' ? '🥉' : '  ';
        console.log(`  ${icon} [${String(normalizedScore).padStart(3)}] [${candidate.padEnd(6)}] ${rel}`);
        for (const sig of allSignals.slice(0, 3)) {
          console.log(`         +${sig.contribution} ${sig.reason}`);
        }
      }
    }
  }

  return results;
}

// ─── Report ─────────────────────────────────────────────────────────────────

function generateReport(results) {
  const byCandidate = { HIGH: [], MEDIUM: [], LOW: [], NONE: [] };
  for (const r of results) byCandidate[r.candidate].push(r);

  // Sort HIGH candidates by score descending
  for (const bucket of Object.values(byCandidate)) {
    bucket.sort((a, b) => b.score - a.score);
  }

  // Count superseded vs canonical candidates
  const supersededFiles = results.filter(r => r.status === 'superseded');
  const canonicalFiles = results.filter(r => r.status === 'canonical');

  const summary = {
    scanned_at: new Date().toISOString(),
    total_files: results.length,
    canonical_candidates: {
      HIGH: byCandidate.HIGH.length,
      MEDIUM: byCandidate.MEDIUM.length,
      LOW: byCandidate.LOW.length,
      NONE: byCandidate.NONE.length,
    },
    superseded_count: supersededFiles.length,
    canonical_count: canonicalFiles.length,
    score_distribution: {
      high_75_100: byCandidate.HIGH.length,
      medium_40_74: byCandidate.MEDIUM.length,
      low_1_39: byCandidate.LOW.length,
      none_0: byCandidate.NONE.length,
    },
    top_candidates: byCandidate.HIGH.slice(0, 10).map(r => ({
      file: r.file,
      score: r.score,
      status: r.status,
      owner: r.owner,
      domain: r.domain,
      supersedes: r.supersedes,
      superseded_by: r.superseded_by,
    })),
    candidates: results.map(r => ({
      file: r.file,
      score: r.score,
      candidate: r.candidate,
      status: r.status,
      owner: r.owner,
      domain: r.domain,
      has_frontmatter: r.has_frontmatter,
      supersedes: r.supersedes,
      superseded_by: r.superseded_by,
    })),
  };

  return summary;
}

// ─── Entry Point ─────────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const showAll = process.argv.includes('--all');
const jsonOnly = process.argv.includes('--json');

console.log('🥇 Canonical Inference Engine — Sprint 21 Step 3');
console.log('   Principle: Tool proposes candidates. Governance decides.');
console.log('   Root:      docs/ + memory/');
console.log('');

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

const docsResults = scanMarkdownFiles(path.join(PROJECT_ROOT, 'docs'), [], verbose || showAll);
const memoryResults = scanMarkdownFiles(path.join(PROJECT_ROOT, 'memory'), [], verbose || showAll);
const allResults = [...docsResults, ...memoryResults];

const report = generateReport(allResults);
fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));

console.log('');
console.log('─── Summary ───────────────────────────────────────────');
console.log(`  Total files scanned:  ${report.total_files}`);
console.log(`  🥇 HIGH candidates:  ${report.canonical_candidates.HIGH}`);
console.log(`  🥈 MEDIUM candidates: ${report.canonical_candidates.MEDIUM}`);
console.log(`  🥉 LOW candidates:   ${report.canonical_candidates.LOW}`);
console.log(`     NONE (no score):  ${report.canonical_candidates.NONE}`);
console.log('');
console.log(`  Canonical status:     ${report.canonical_count} files`);
console.log(`  Superseded status:    ${report.superseded_count} files`);
console.log('');
console.log(`  Report: ${REPORT_PATH}`);

if (report.top_candidates.length > 0) {
  console.log('');
  console.log('  Top SSOT Candidates (HIGH):');
  for (const c of report.top_candidates.slice(0, 10)) {
    const supersedes = c.supersedes && c.supersedes.length > 0 ? ` supersedes ${c.supersedes.length} docs` : '';
    const supersededBy = c.superseded_by && c.superseded_by.length > 0 ? ` ← superseded by ${c.superseded_by.length}` : '';
    console.log(`    [${String(c.score).padStart(3)}] ${c.file}${supersedes}${supersededBy}`);
  }
}

console.log('');
console.log('  ⚠️  Tool proposes. Governance decides.');
console.log('  Final canonical classification requires SAAB approval.');

if (jsonOnly) console.log(JSON.stringify(report, null, 2));

process.exit(0); // Always exits 0 — tool proposes, never rejects
