<?php
/**
 * SAB Authority Patcher - Evolution System v1.0
 * Usage: php scripts/sab-apply-proposal.php <proposal.json> <authority.json>
 */

if ($argc < 3) {
    echo "Usage: php scripts/sab-apply-proposal.php <proposal.json> <authority.json>\n";
    exit(1);
}

$proposalFile = $argv[1];
$authorityFile = $argv[2];

if (!file_exists($proposalFile)) {
    echo "Error: Proposal file not found: $proposalFile\n";
    exit(1);
}

if (!file_exists($authorityFile)) {
    echo "Error: Authority file not found: $authorityFile\n";
    exit(1);
}

$proposal = json_decode(file_get_contents($proposalFile), true);
$authority = json_decode(file_get_contents($authorityFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in proposal or authority file.\n";
    exit(1);
}

// Target path traversal (e.g., "governance.sealed_domains")
$targetPath = $proposal['target'] ?? '';
$action = $proposal['action'] ?? 'append';
$value = $proposal['value'] ?? null;

if (!$targetPath || $value === null) {
    echo "Error: Target path or value missing in proposal.\n";
    exit(1);
}

$pathParts = explode('.', $targetPath);
$current = &$authority;

foreach ($pathParts as $part) {
    if (!isset($current[$part])) {
        $current[$part] = [];
    }
    $current = &$current[$part];
}

// Applying the action
switch ($action) {
    case 'append':
        if (!is_array($current)) {
            $current = (array)$current;
        }
        if (!in_array($value, $current)) {
            $current[] = $value;
        }
        break;
    case 'update':
        $current = $value;
        break;
    case 'merge':
        if (is_array($current) && is_array($value)) {
            $current = array_merge($current, $value);
        } else {
            $current = $value;
        }
        break;
    default:
        echo "Error: Unknown action: $action\n";
        exit(1);
}

// Write back
file_put_contents($authorityFile, json_encode($authority, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "SUCCESS: Authority patched at $targetPath via $action\n";
