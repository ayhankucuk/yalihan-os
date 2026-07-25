/**
 * Semantic Duplicate Engine — Sprint 21 Step 5
 * Detects similar document clusters based on multiple similarity signals.
 *
 * Signals:
 *   - Title similarity (Levenshtein distance)
 *   - Content word overlap (Jaccard similarity)
 *   - Metadata proximity (same sprint/ADR, shared tags, same domain)
 *   - Revision relationship (supersedes/superseded_by)
 *
 * Output: document clusters with similarity scores.
 * NO automatic merge or deletion decisions.
 *
 * Principle: Tool clusters by similarity; governance decides action.
 *
 * Usage: node scripts/guards/doc-semantic-duplicate.cjs [--verbose] [--json]
 * Output: docs/_reports/semantic_duplicate_report.json
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '../..');
const REPORT_DIR = path.join(PROJECT_ROOT, 'docs/_reports');
const REPORT_PATH = path.join(REPORT_DIR, 'semantic_duplicate_report.json');

// ─── Similarity Thresholds ──────────────────────────────────────────────────────

const SIMILARITY = {
  TITLE_HIGH:  0.85,  // Likely duplicate
  TITLE_MEDIUM: 0.60,  // Possible duplicate / revision
  CONTENT_HIGH: 0.70,  // Significant content overlap
  CONTENT_MEDIUM: 0.40, // Some overlap
  CLUSTER_HIGH: 0.80,  // Cluster boundary
  CLUSTER_MEDIUM: 0.60,
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function levenshtein(a, b) {
  if (!a || !b) return 0;
  const m = a.length, n = b.length;
  const dp = Array.from({ length: m + 1 }, (_, i) => [i]);
  for (let j = 0; j <= n; j++) dp[0][j] = j;
  for (let i = 1; i <= m; i++) {
    for (let j = 1; j <= n; j++) {
      dp[i][j] = a[i - 1] === b[j - 1]
        ? dp[i - 1][j - 1]
        : 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
    }
  }
  return dp[m][n];
}

function titleSimilarity(a, b) {
  const la = a.toLowerCase().replace(/[^a-z0-9\s]/g, ' ');
  const lb = b.toLowerCase().replace(/[^a-z0-9\s]/g, ' ');
  if (la === lb) return 1;
  const maxLen = Math.max(la.length, lb.length);
  if (maxLen === 0) return 0;
  return 1 - levenshtein(la, lb) / maxLen;
}

function jaccardSimilarity(setA, setB) {
  if (setA.size === 0 && setB.size === 0) return 0;
  const intersection = new Set([...setA].filter(x => setB.has(x)));
  const union = new Set([...setA, ...setB]);
  return intersection.size / union.size;
}

function tokenizeText(content) {
  // Remove frontmatter
  const body = content.replace(/^---[\s\S]*?---\n/, '');
  // Remove markdown syntax
  const clean = body
    .replace(/#{1,6}\s+/g, ' ')
    .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
    .replace(/[*_`~]/g, '')
    .replace(/<[^>]+>/g, '');
  const words = clean.toLowerCase().split(/\s+/).filter(w => w.length > 3);
  return new Set(words);
}

function extractTitle(content) {
  const match = content.match(/^#\s+(.+)$/m);
  return match ? match[1].trim() : null;
}

function extractMetadata(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) return {};
  const meta = {};
  for (const line of match[1].split(/\r?\n/)) {
    const idx = line.indexOf(':');
    if (idx === -1) continue;
    const key = line.slice(0, idx).trim();
    const raw = line.slice(idx + 1).trim();
    meta[key] = raw;
  }
  return meta;
}

function parseSupersedesList(meta) {
  if (!meta.supersedes || meta.supersedes === '[]') return [];
  const match = meta.supersedes.match(/\[([^\]]+)\]/);
  if (!match) return [];
  return match[1].split(',').map(s => s.trim().replace(/['"]/g, '')).filter(Boolean);
}

function parseSupersededByList(meta) {
  if (!meta.superseded_by || meta.superseded_by === '[]') return [];
  const match = meta.superseded_by.match(/\[([^\]]+)\]/);
  if (!match) return [];
  return match[1].split(',').map(s => s.trim().replace(/['"]/g, '')).filter(Boolean);
}

function extractSlug(file) {
  return file.replace(/^docs\//, '').replace(/\//g, '_').replace(/\.md$/, '');
}

function getMetadataProximity(docA, docB) {
  let score = 0;
  // Same sprint/phase number
  const sprintA = docA.file.match(/Sprint[_\s]?\d|Sprint\d/i);
  const sprintB = docB.file.match(/Sprint[_\s]?\d|Sprint\d/i);
  if (sprintA && sprintB && sprintA[0] === sprintB[0]) score += 0.3;

  // Same domain
  if (docA.meta.domain && docA.meta.domain === docB.meta.domain) score += 0.2;

  // Shared tags
  if (docA.meta.tags && docB.meta.tags) {
    const tagsA = new Set(docA.meta.tags.replace(/[[\]]/g, '').split(',').map(t => t.trim()));
    const tagsB = new Set(docB.meta.tags.replace(/[[\]]/g, '').split(',').map(t => t.trim()));
    const shared = [...tagsA].filter(t => tagsB.has(t)).length;
    if (shared > 0) score += 0.2 * Math.min(shared, 3);
  }

  // Supersedes relationship
  const supA = parseSupersedesList(docA.meta);
  const supB = parseSupersedesList(docB.meta);
  const supByA = parseSupersededByList(docA.meta);
  const supByB = parseSupersededByList(docB.meta);

  if (supA.includes(extractSlug(docB.file)) || supB.includes(extractSlug(docA.file))) {
    score += 0.5; // docA supersedes docB or vice versa
  }
  if (supByA.includes(extractSlug(docB.file)) || supByB.includes(extractSlug(docA.file))) {
    score += 0.5; // docA is superseded by docB
  }

  return Math.min(score, 1);
}

// ─── Document Indexing ─────────────────────────────────────────────────────────

function indexDocuments(dir, verbose = false) {
  const docs = [];

  function walk(d) {
    if (!fs.existsSync(d)) return;
    const entries = fs.readdirSync(d, { withFileTypes: true });
    for (const e of entries) {
      const full = path.join(d, e.name);
      if (e.isDirectory()) {
        if (/^(node_modules|vendor|storage|bootstrap|resources|tests)/.test(e.name)) continue;
        if (/^\./.test(e.name)) continue;
        walk(full);
      } else if (e.name.endsWith('.md')) {
        const rel = path.relative(PROJECT_ROOT, full);
        const content = fs.readFileSync(full, 'utf8');
        const title = extractTitle(content);
        const meta = extractMetadata(content);
        const wordSet = tokenizeText(content);

        if (!title) continue; // Skip docs without any title

        docs.push({ file: rel, title, meta, wordSet });
      }
    }
  }

  walk(path.join(PROJECT_ROOT, 'docs'));
  walk(path.join(PROJECT_ROOT, 'memory'));

  if (verbose) console.log(`  📄 ${docs.length} documents indexed`);
  return docs;
}

// ─── Pairwise Similarity ───────────────────────────────────────────────────────

function computeSimilarity(docA, docB) {
  // Title similarity
  const tSim = titleSimilarity(docA.title, docB.title);

  // Content similarity
  const cSim = jaccardSimilarity(docA.wordSet, docB.wordSet);

  // Metadata proximity
  const mProx = getMetadataProximity(docA, docB);

  // Combined score: title is most important for duplicate detection
  const combined = (tSim * 0.5) + (cSim * 0.3) + (mProx * 0.2);

  return {
    file_a: docA.file,
    file_b: docB.file,
    title_a: docA.title,
    title_b: docB.title,
    title_similarity: Math.round(tSim * 100) / 100,
    content_similarity: Math.round(cSim * 100) / 100,
    metadata_proximity: Math.round(mProx * 100) / 100,
    combined_score: Math.round(combined * 100) / 100,
    relationship: docA.meta.status || docB.meta.status || null,
    supersedes: parseSupersedesList(docA.meta).includes(extractSlug(docB.file)) ? 'A_supersedes_B'
      : parseSupersedesList(docB.meta).includes(extractSlug(docA.file)) ? 'B_supersedes_A'
      : null,
  };
}

// ─── Clustering ────────────────────────────────────────────────────────────────

function clusterDocuments(docs, pairs, verbose = false) {
  // Union-Find for clustering
  const parent = {};
  const rank = {};

  function find(x) {
    if (!parent[x]) { parent[x] = x; rank[x] = 0; }
    if (parent[x] !== x) parent[x] = find(parent[x]);
    return parent[x];
  }

  function union(x, y) {
    const px = find(x), py = find(y);
    if (px === py) return;
    if (rank[px] < rank[py]) parent[px] = py;
    else if (rank[px] > rank[py]) parent[py] = px;
    else { parent[py] = px; rank[px]++; }
  }

  // Build clusters from significant pairs
  for (const pair of pairs) {
    if (pair.combined_score >= SIMILARITY.CLUSTER_MEDIUM) {
      union(pair.file_a, pair.file_b);
    }
  }

  // Group docs by cluster
  const clusters = {};
  for (const doc of docs) {
    const root = find(doc.file);
    if (!clusters[root]) {
      clusters[root] = [];
    }
    clusters[root].push(doc);
  }

  return clusters;
}

function classifyCluster(cluster, pairs) {
  const n = cluster.length;
  if (n === 1) return 'UNIQUE';

  const clusterPairs = pairs.filter(
    p => cluster.some(d => d.file === p.file_a) && cluster.some(d => d.file === p.file_b)
  );

  if (clusterPairs.length === 0) return 'UNIQUE';

  const avgScore = clusterPairs.reduce((s, p) => s + p.combined_score, 0) / clusterPairs.length;
  const maxTitleSim = Math.max(...clusterPairs.map(p => p.title_similarity));
  const hasSupersedes = clusterPairs.some(p => p.relationship === 'supersedes');

  if (hasSupersedes || maxTitleSim >= SIMILARITY.TITLE_HIGH) {
    return avgScore >= SIMILARITY.CLUSTER_HIGH ? 'EVOLVED' : 'REVISION';
  }
  if (avgScore >= SIMILARITY.CLUSTER_MEDIUM) {
    return maxTitleSim >= SIMILARITY.TITLE_MEDIUM ? 'DUPLICATE_CANDIDATE' : 'SIMILAR';
  }
  return 'WEAK_SIMILARITY';
}

function getClusterRecommendation(classification) {
  const map = {
    EVOLVED:          { recommendation: 'GOVERNANCE_REVIEW', urgency: 'LOW',  reason: 'Revision history detected — preserve all versions' },
    REVISION:         { recommendation: 'GOVERNANCE_REVIEW', urgency: 'LOW',  reason: 'Revision relationship — review for consolidation' },
    DUPLICATE_CANDIDATE: { recommendation: 'REVIEW',        urgency: 'MEDIUM', reason: 'High similarity — review for merge or archive' },
    SIMILAR:          { recommendation: 'NOTE',               urgency: 'LOW',  reason: 'Moderate similarity — document relationship' },
    WEAK_SIMILARITY:  { recommendation: 'IGNORE',               urgency: 'NONE', reason: 'Low similarity — likely independent documents' },
    UNIQUE:           { recommendation: 'IGNORE',               urgency: 'NONE', reason: 'Single document in cluster' },
  };
  return map[classification] || map.UNIQUE;
}

// ─── Report Generation ─────────────────────────────────────────────────────────

function generateReport(docs, pairs, clusters) {
  const clusterResults = [];
  const pairResults = [];

  for (const [root, docsInCluster] of Object.entries(clusters)) {
    if (docsInCluster.length === 1) continue;

    const clusterPairs = pairs.filter(
      p => docsInCluster.some(d => d.file === p.file_a) && docsInCluster.some(d => d.file === p.file_b)
    );

    const classification = classifyCluster(docsInCluster, clusterPairs);
    const recommendation = getClusterRecommendation(classification);
    const avgScore = clusterPairs.length > 0
      ? Math.round(clusterPairs.reduce((s, p) => s + p.combined_score, 0) / clusterPairs.length * 100) / 100
      : 0;
    const maxScore = clusterPairs.length > 0
      ? Math.round(Math.max(...clusterPairs.map(p => p.combined_score)) * 100) / 100
      : 0;

    clusterResults.push({
      cluster_id: root,
      files: docsInCluster.map(d => ({ file: d.file, title: d.title })),
      file_count: docsInCluster.length,
      classification,
      recommendation: recommendation.recommendation,
      urgency: recommendation.urgency,
      reason: recommendation.reason,
      avg_similarity: avgScore,
      max_similarity: maxScore,
      pair_count: clusterPairs.length,
    });
  }

  // Sort by max similarity
  clusterResults.sort((a, b) => b.max_similarity - a.max_similarity);

  const byClassification = { EVOLVED: [], REVISION: [], DUPLICATE_CANDIDATE: [], SIMILAR: [], WEAK_SIMILARITY: [], UNIQUE: [] };
  for (const c of clusterResults) byClassification[c.classification].push(c);

  const summary = {
    scanned_at: new Date().toISOString(),
    total_docs_indexed: docs.length,
    total_pairs_computed: pairs.length,
    significant_pairs: pairs.filter(p => p.combined_score >= SIMILARITY.CLUSTER_MEDIUM).length,
    clusters_found: clusterResults.length,
    by_classification: {
      EVOLVED: byClassification.EVOLVED.length,
      REVISION: byClassification.REVISION.length,
      DUPLICATE_CANDIDATE: byClassification.DUPLICATE_CANDIDATE.length,
      SIMILAR: byClassification.SIMILAR.length,
      WEAK_SIMILARITY: byClassification.WEAK_SIMILARITY.length,
    },
    clusters: clusterResults.map(c => ({
      cluster_id: c.cluster_id,
      file_count: c.file_count,
      files: c.files,
      classification: c.classification,
      recommendation: c.recommendation,
      urgency: c.urgency,
      avg_similarity: c.avg_similarity,
      max_similarity: c.max_similarity,
    })),
    thresholds: SIMILARITY,
  };

  return summary;
}

// ─── Entry Point ─────────────────────────────────────────────────────────────

const verbose = process.argv.includes('--verbose');
const showAll = process.argv.includes('--all');
const jsonOnly = process.argv.includes('--json');

console.log('🔬 Semantic Duplicate Engine — Sprint 21 Step 5');
console.log('   Principle: Tool clusters by similarity. Governance decides action.');
console.log('');

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

console.log('  📄 Indexing documents...');
const docs = indexDocuments(path.join(PROJECT_ROOT, 'docs'), verbose);
console.log(`  📄 ${docs.length} documents indexed`);

// Compute pairwise similarities (only for MEDIUM+ threshold)
console.log('  🔍 Computing pairwise similarities...');
const pairs = [];
const significantPairs = [];

for (let i = 0; i < docs.length; i++) {
  for (let j = i + 1; j < docs.length; j++) {
    const sim = computeSimilarity(docs[i], docs[j]);
    pairs.push(sim);
    if (sim.combined_score >= SIMILARITY.CLUSTER_MEDIUM) {
      significantPairs.push(sim);
    }
  }
}

console.log(`  🔍 ${pairs.length} pairs computed, ${significantPairs.length} significant`);

// Cluster
console.log('  🗂️  Clustering...');
const clusters = clusterDocuments(docs, significantPairs);
const significantClusters = Object.values(clusters).filter(c => c.length > 1);
console.log(`  🗂️  ${significantClusters.length} clusters found`);

const report = generateReport(docs, pairs, clusters);
fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));

console.log('');
console.log('─── Summary ───────────────────────────────────────────');
console.log(`  Total docs indexed:    ${report.total_docs_indexed}`);
console.log(`  Total pairs computed:  ${report.total_pairs_computed}`);
console.log(`  Significant pairs:   ${report.significant_pairs} (≥${SIMILARITY.CLUSTER_MEDIUM * 100}% similar)`);
console.log(`  Clusters found:        ${report.clusters_found}`);
console.log('');
console.log('  By classification:');
console.log(`    EVOLVED:             ${report.by_classification.EVOLVED}`);
console.log(`    REVISION:            ${report.by_classification.REVISION}`);
console.log(`    DUPLICATE_CANDIDATE: ${report.by_classification.DUPLICATE_CANDIDATE}`);
console.log(`    SIMILAR:             ${report.by_classification.SIMILAR}`);
console.log(`    WEAK_SIMILARITY:     ${report.by_classification.WEAK_SIMILARITY}`);
console.log('');
console.log(`  Report: ${REPORT_PATH}`);

if (significantClusters.length > 0) {
  console.log('');
  console.log('  Top clusters:');
  for (const c of report.clusters.slice(0, 5)) {
    const urgency = c.urgency === 'HIGH' ? '🔴' : c.urgency === 'MEDIUM' ? '🟡' : '🟢';
    console.log(`  ${urgency} [${(c.max_similarity * 100).toFixed(0)}%] [${c.classification.padEnd(18)}] ${c.file_count} files`);
    for (const f of c.files.slice(0, 3)) {
      console.log(`         ${f.file}`);
    }
    if (c.files.length > 3) console.log(`         ... +${c.files.length - 3} more`);
  }
}

console.log('');
console.log('  ⚠️  Tool clusters by similarity. Governance decides action.');
console.log('  No documents will be merged or deleted by this tool.');

if (jsonOnly) console.log(JSON.stringify(report, null, 2));

process.exit(0); // Similarity analysis is advisory — never fails
