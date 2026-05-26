<?php
/**
 * Help-to-take-accurate.php — DHANI WIN
 * Input validation + edge-case override only.
 * Does NOT inflate confidence with fake numbers.
 */

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
        $cleaned[] = [
            'type'   => $type,
            'number' => max(1, min((int)($t['number'] ?? $i + 1), 10)),
        ];
    }

    return ['valid' => empty($errors), 'errors' => $errors, 'cleaned' => $cleaned];
}

function applyEdgeCorrection(array $result, array $trends): array {
    $types = array_column($trends, 'type');

    // All 10 same → force reversal, the math already gives this a good score
    // but we make it definitive here
    if (count(array_unique($types)) === 1) {
        $forced = ($types[0] === 'BIG') ? 'Small' : 'BIG';
        $result['prediction']      = $forced;
        $result['edge_correction'] = "All 10 same ({$types[0]}) — forced reversal to {$forced}";
        // Don't inflate confidence — the score already reflects this
        return $result;
    }

    // Last 5 all same → override if the result disagrees
    $last5 = array_slice($types, -5);
    if (count(array_unique($last5)) === 1) {
        $forced = ($last5[0] === 'BIG') ? 'Small' : 'BIG';
        if ($result['prediction'] !== $forced) {
            $result['prediction']      = $forced;
            $result['confidence']      = min($result['confidence'], 72);
            $result['edge_correction'] = "Last 5 same ({$last5[0]}) — prediction corrected to {$forced}";
        }
    }

    return $result;
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(validateTrends($input['trends'] ?? []));
}
