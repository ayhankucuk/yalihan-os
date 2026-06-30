#!/usr/bin/env node
/**
 * G1 — Command Registry Guard (Phase A: detect/report only)
 *
 * Non-blocking by design:
 * - Always exits 0.
 * - Produces machine + markdown reports.
 * - Supports optional baseline bootstrap with --write-baseline.
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = process.cwd();
const BASELINE_PATH = path.join(ROOT, '.sab', 'command-registry.manifest.json');
const SNAPSHOT_PATH = path.join(
  ROOT,
  'storage',
  'app',
  'governance',
  'generated',
  'command_registry.current.json'
);
const REPORT_JSON_PATH = path.join(
  ROOT,
  'storage',
  'logs',
  'command-registry-drift.json'
);
const REPORT_MD_PATH = path.join(ROOT, 'docs', '_reports', 'COMMAND_REGISTRY_DRIFT_REPORT.md');

const WRITE_BASELINE = process.argv.includes('--write-baseline');

function ensureDir(filePath) {
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
}

function run(command) {
  try {
    return {
      ok: true,
      out: execSync(command, {
        cwd: ROOT,
        stdio: ['ignore', 'pipe', 'pipe'],
        encoding: 'utf8',
      }),
      err: '',
    };
  } catch (error) {
    return {
      ok: false,
      out: '',
      err: String(error.stderr || error.message || ''),
    };
  }
}

function parseJsonList(raw) {
  const parsed = JSON.parse(raw);

  if (Array.isArray(parsed)) {
    return parsed
      .map((entry) => ({
        name: String(entry.name || '').trim(),
        description: String(entry.description || '').trim(),
      }))
      .filter((entry) => entry.name);
  }

  if (parsed && Array.isArray(parsed.commands)) {
    return parsed.commands
      .map((entry) => ({
        name: String(entry.name || '').trim(),
        description: String(entry.description || '').trim(),
      }))
      .filter((entry) => entry.name);
  }

  if (parsed && typeof parsed === 'object') {
    return Object.entries(parsed)
      .map(([name, value]) => ({
        name: String(name).trim(),
        description: String((value && value.description) || '').trim(),
      }))
      .filter((entry) => entry.name);
  }

  return [];
}

function parseRawList(raw) {
  return raw
    .split('\n')
    .map((line) => line.trimEnd())
    .filter(Boolean)
    .map((line) => {
      const match = line.match(/^(\S+)\s{2,}(.*)$/);
      if (match) {
        return {
          name: match[1].trim(),
          description: match[2].trim(),
        };
      }
      const [name, ...rest] = line.split(/\s+/);
      return {
        name: String(name || '').trim(),
        description: rest.join(' ').trim(),
      };
    })
    .filter((entry) => entry.name && entry.name !== 'list');
}

function readCurrentCommands() {
  const jsonRun = run('php artisan list --format=json --no-ansi');
  if (jsonRun.ok) {
    try {
      return {
        parserMode: 'json',
        commands: parseJsonList(jsonRun.out),
        rawError: null,
      };
    } catch (error) {
      // fall through to raw mode
    }
  }

  const rawRun = run('php artisan list --raw --no-ansi');
  if (!rawRun.ok) {
    return {
      parserMode: 'failed',
      commands: [],
      rawError: jsonRun.err || rawRun.err || 'Unable to read artisan command list',
    };
  }

  return {
    parserMode: 'raw_fallback',
    commands: parseRawList(rawRun.out),
    rawError: jsonRun.ok ? null : (jsonRun.err || null),
  };
}

function normalizeCommands(items) {
  const unique = new Map();
  for (const item of items) {
    const name = String(item.name || '').trim();
    if (!name) continue;
    unique.set(name, {
      name,
      description: String(item.description || '').trim(),
    });
  }
  return Array.from(unique.values()).sort((a, b) => a.name.localeCompare(b.name));
}

function readBaseline() {
  if (!fs.existsSync(BASELINE_PATH)) {
    return { exists: false, commands: [] };
  }
  try {
    const parsed = JSON.parse(fs.readFileSync(BASELINE_PATH, 'utf8'));
    const commands = Array.isArray(parsed.commands) ? parsed.commands : [];
    return { exists: true, commands: normalizeCommands(commands) };
  } catch {
    return { exists: true, commands: [] };
  }
}

function namesSet(list) {
  return new Set(list.map((x) => x.name));
}

function mapByDescription(list) {
  const map = new Map();
  for (const item of list) {
    const key = item.description || '';
    if (!map.has(key)) map.set(key, []);
    map.get(key).push(item.name);
  }
  return map;
}

function diffCommands(baseline, current) {
  const baseSet = namesSet(baseline);
  const curSet = namesSet(current);

  const added = current.filter((x) => !baseSet.has(x.name));
  const removed = baseline.filter((x) => !curSet.has(x.name));

  const renamed = [];
  const baseByDesc = mapByDescription(removed);
  const curByDesc = mapByDescription(added);
  for (const [desc, oldNames] of baseByDesc.entries()) {
    if (!desc) continue;
    const newNames = curByDesc.get(desc) || [];
    if (oldNames.length === 1 && newNames.length === 1) {
      renamed.push({
        from: oldNames[0],
        to: newNames[0],
        description: desc,
      });
    }
  }

  const renamedFrom = new Set(renamed.map((x) => x.from));
  const renamedTo = new Set(renamed.map((x) => x.to));

  return {
    added: added.filter((x) => !renamedTo.has(x.name)),
    removed: removed.filter((x) => !renamedFrom.has(x.name)),
    renamed,
  };
}

function buildManifest(commands, parserMode) {
  const namespaces = {};
  for (const cmd of commands) {
    const [ns] = cmd.name.split(':');
    namespaces[ns] = (namespaces[ns] || 0) + 1;
  }

  return {
    schema_version: 1,
    generated_at: new Date().toISOString(),
    source: {
      command: 'php artisan list',
      parser_mode: parserMode,
    },
    summary: {
      total_commands: commands.length,
      namespace_count: Object.keys(namespaces).length,
    },
    namespaces,
    commands,
  };
}

function writeMarkdownReport(payload) {
  const lines = [];
  lines.push('# Command Registry Drift Report');
  lines.push('');
  lines.push(`- Generated At: ${payload.generated_at}`);
  lines.push(`- Parser Mode: \`${payload.parser_mode}\``);
  lines.push(`- Baseline Exists: \`${payload.baseline_exists}\``);
  lines.push(`- Baseline Commands: \`${payload.baseline_total}\``);
  lines.push(`- Current Commands: \`${payload.current_total}\``);
  lines.push('');
  lines.push('## Diff Summary');
  lines.push('');
  lines.push(`- Added: **${payload.diff.added.length}**`);
  lines.push(`- Removed: **${payload.diff.removed.length}**`);
  lines.push(`- Renamed: **${payload.diff.renamed.length}**`);
  if (payload.parser_error) {
    lines.push(`- Parser Error (json mode): \`${payload.parser_error.replace(/\s+/g, ' ').trim()}\``);
  }
  lines.push('');

  function writeSection(title, items, mapper) {
    lines.push(`## ${title}`);
    lines.push('');
    if (!items.length) {
      lines.push('- none');
      lines.push('');
      return;
    }
    for (const item of items) {
      lines.push(`- ${mapper(item)}`);
    }
    lines.push('');
  }

  writeSection('Added Commands', payload.diff.added, (x) => `\`${x.name}\` — ${x.description || '(no description)'}`);
  writeSection('Removed Commands', payload.diff.removed, (x) => `\`${x.name}\` — ${x.description || '(no description)'}`);
  writeSection('Renamed Commands (Heuristic)', payload.diff.renamed, (x) => `\`${x.from}\` -> \`${x.to}\` — ${x.description || '(no description)'}`);

  ensureDir(REPORT_MD_PATH);
  fs.writeFileSync(REPORT_MD_PATH, `${lines.join('\n')}\n`, 'utf8');
}

function main() {
  const currentRead = readCurrentCommands();
  const currentCommands = normalizeCommands(currentRead.commands);
  const manifest = buildManifest(currentCommands, currentRead.parserMode);

  ensureDir(SNAPSHOT_PATH);
  fs.writeFileSync(SNAPSHOT_PATH, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');

  if (WRITE_BASELINE) {
    ensureDir(BASELINE_PATH);
    fs.writeFileSync(BASELINE_PATH, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
  }

  const baseline = readBaseline();
  const diff = diffCommands(baseline.commands, currentCommands);

  const reportPayload = {
    generated_at: new Date().toISOString(),
    parser_mode: currentRead.parserMode,
    parser_error: currentRead.rawError,
    baseline_exists: baseline.exists,
    baseline_total: baseline.commands.length,
    current_total: currentCommands.length,
    diff,
    files: {
      baseline: path.relative(ROOT, BASELINE_PATH),
      current_snapshot: path.relative(ROOT, SNAPSHOT_PATH),
      markdown_report: path.relative(ROOT, REPORT_MD_PATH),
    },
  };

  ensureDir(REPORT_JSON_PATH);
  fs.writeFileSync(REPORT_JSON_PATH, `${JSON.stringify(reportPayload, null, 2)}\n`, 'utf8');
  writeMarkdownReport(reportPayload);

  console.log('🧭 Command Registry Guard (Phase A: detect/report)');
  console.log(`- Parser Mode: ${reportPayload.parser_mode}`);
  console.log(`- Baseline Exists: ${reportPayload.baseline_exists}`);
  console.log(`- Added: ${reportPayload.diff.added.length}`);
  console.log(`- Removed: ${reportPayload.diff.removed.length}`);
  console.log(`- Renamed: ${reportPayload.diff.renamed.length}`);
  console.log(`- Snapshot: ${reportPayload.files.current_snapshot}`);
  console.log(`- Report: ${reportPayload.files.markdown_report}`);

  // Phase A detect/report => always non-blocking.
  process.exit(0);
}

main();

