<?php
/**
 * Help-to-take-accurate.php — DHANI WIN
 * Input validation + edge-case override.
 * Kept minimal and clean — no false boosting.
 */

// ── Validate trend input ─────────────────────────────────────────
function validateTrends(array $trends): array {
    $errors  = [];
    $cleaned = [];

    if (count($trends) !== 10) {
        $errors[] = 'Need exactly 10 trends, got ' . count($trends);
        return ['valid' => false, 'errors' => $errors, 'cleaned' => []];
    }

    foreach ($trends as $i => $t) {
        $type = trim($t['type'] ?? '');
        if (!in_array($type, ['BIG', 'Small'], true)) {
            $errors[] = "Trend #{$i}: invalid type '{$type}'";
            continue;
        }
        $num       = max(1, min((int)($t['number'] ?? $i + 1), 10));
        $cleaned[] = ['type' => $type, 'number' => $num];
    }

    return [
        'valid'   => empty($errors),
        'errors'  => $errors,
        'cleaned' => $cleaned,
    ];
}

// ── Edge-case override ───────────────────────────────────────────
// Only applies in very obvious edge cases — keeps prediction clean
function applyEdgeCorrection(array $result, array $trends): array {
    $types = array_column($trends, 'type');

    // Edge 1: All 10 same → force reversal (this is near-certain)
    if (count(array_unique($types)) === 1) {
        $forced = ($types[0] === 'BIG') ? 'Small' : 'BIG';
        $result['prediction']      = $forced;
        $result['confidence']      = 97;
        $result['edge_correction'] = "All 10 same ({$types[0]}) → forced reversal";
        return $result;
    }

    // Edge 2: Last 5 all same → strong reversal signal, bump confidence
    $last5 = array_slice($types, -5);
    if (count(array_unique($last5)) === 1) {
        $forced = ($last5[0] === 'BIG') ? 'Small' : 'BIG';
        if ($result['prediction'] === $forced) {
            // Already correct — just boost confidence
            $result['confidence']      = min($result['confidence'] + 3, 98);
            $result['edge_correction'] = "Last 5 same → confidence boosted";
        } else {
            // Override if wrong
            $result['prediction']      = $forced;
            $result['confidence']      = 93;
            $result['edge_correction'] = "Last 5 same ({$last5[0]}) → override to {$forced}";
        }
    }

    return $result;
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'validate');
    if ($action === 'validate') {
        echo json_encode(validateTrends($input['trends'] ?? []));
    } else {
        echo json_encode(['error' => 'Unknown action']);
    }
}
