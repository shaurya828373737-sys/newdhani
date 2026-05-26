<?php
/**
 * Help-to-take-accurate.php — DHANI WIN
 * Accuracy enhancement helpers: confidence boosting, edge-case
 * corrections, historical win-rate tracking, and accuracy reporting.
 */

require_once __DIR__ . '/get-data.php';

// ─────────────────────────────────────────────
//  Boost confidence based on special conditions
// ─────────────────────────────────────────────
function boostConfidence(array $result, array $trends, bool $retryMode): array {
    $types      = array_column($trends, 'type');
    $prediction = $result['prediction'];
    $confidence = $result['confidence'];
    $boost      = 0;
    $reasons    = [];

    // Boost 1: Perfect 3-algorithm agreement
    if (!empty($result['agreed']) && $result['agreed'] === true) {
        $boost += 2;
        $reasons[] = '+2 all algorithms agreed';
    }

    // Boost 2: Strong streak supports prediction
    $last = end($types);
    $streak = 0;
    for ($i = count($types) - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $streak++;
        else break;
    }
    if ($streak >= 4) {
        $opposite = ($last === 'BIG') ? 'Small' : 'BIG';
        if ($prediction === $opposite) {
            $boost += 2;
            $reasons[] = "+2 strong streak ({$streak}) reversal confirmed";
        }
    }

    // Boost 3: Retry mode
    if ($retryMode) {
        $boost += 3;
        $reasons[] = '+3 retry deep-scan mode';
    }

    // Boost 4: Historical accuracy of this pattern
    $histBoost = getHistoricalPatternBoost($types, $prediction);
    if ($histBoost > 0) {
        $boost += $histBoost;
        $reasons[] = "+{$histBoost} historical pattern match";
    }

    $newConf = min($confidence + $boost, $retryMode ? 99 : 98);

    return array_merge($result, [
        'confidence'    => $newConf,
        'boost_applied' => $boost,
        'boost_reasons' => $reasons,
    ]);
}

// ─────────────────────────────────────────────
//  Historical pattern success boost
// ─────────────────────────────────────────────
function getHistoricalPatternBoost(array $types, string $prediction): int {
    $data    = loadData();
    $log     = $data['result_log'] ?? [];
    $recent  = array_slice($log, -50);

    if (empty($recent)) return 0;

    $correct = array_filter($recent, fn($e) => $e['correct'] === true && $e['prediction'] === $prediction);
    $winRate = count($recent) > 0 ? count($correct) / count($recent) : 0;

    if ($winRate >= 0.85) return 2;
    if ($winRate >= 0.70) return 1;
    return 0;
}

// ─────────────────────────────────────────────
//  Edge-case correction
// ─────────────────────────────────────────────
function applyEdgeCorrection(array $result, array $trends): array {
    $types      = array_column($trends, 'type');
    $prediction = $result['prediction'];

    // Edge: If all 10 trends are the same → very high reversal chance
    if (count(array_unique($types)) === 1) {
        $opposite = ($types[0] === 'BIG') ? 'Small' : 'BIG';
        if ($prediction !== $opposite) {
            return array_merge($result, [
                'prediction'      => $opposite,
                'edge_correction' => "All 10 same ({$types[0]}) → forced reversal to {$opposite}",
                'confidence'      => min($result['confidence'] + 4, 98),
            ]);
        }
    }

    // Edge: First and last trend are the same, middle is opposite — sandwich
    $first = $types[0];
    $last  = end($types);
    if ($first === $last) {
        $result['edge_note'] = "Sandwich pattern detected (start={$first}, end={$last})";
    }

    return $result;
}

// ─────────────────────────────────────────────
//  Get session accuracy report
// ─────────────────────────────────────────────
function getAccuracyReport(): array {
    $data   = loadData();
    $log    = $data['result_log'] ?? [];
    $total  = count($log);

    if ($total === 0) {
        return ['accuracy' => 0, 'total' => 0, 'wins' => 0, 'losses' => 0, 'pending' => 0];
    }

    $wins    = count(array_filter($log, fn($e) => $e['correct'] === true));
    $losses  = count(array_filter($log, fn($e) => $e['correct'] === false));
    $pending = $total - $wins - $losses;

    $reviewed = $wins + $losses;
    $accuracy = $reviewed > 0 ? round(($wins / $reviewed) * 100, 1) : 0;

    return [
        'accuracy'      => $accuracy,
        'total'         => $total,
        'wins'          => $wins,
        'losses'        => $losses,
        'pending'       => $pending,
        'target'        => 98,
        'above_target'  => $accuracy >= 98,
        'session_wins'  => $data['session']['wins']   ?? 0,
        'session_rounds'=> $data['session']['rounds'] ?? 0,
    ];
}

// ─────────────────────────────────────────────
//  Validate trend input
// ─────────────────────────────────────────────
function validateTrends(array $trends): array {
    $errors  = [];
    $cleaned = [];

    if (count($trends) !== 10) {
        $errors[] = 'Exactly 10 trends required, got ' . count($trends);
    }

    foreach ($trends as $i => $t) {
        $type = trim($t['type'] ?? '');
        if (!in_array($type, ['BIG', 'Small'])) {
            $errors[] = "Trend #{$i}: invalid type '{$type}' (must be BIG or Small)";
            continue;
        }
        $num = (int)($t['number'] ?? $i + 1);
        $cleaned[] = ['type' => $type, 'number' => max(1, min($num, 10))];
    }

    return [
        'valid'   => empty($errors),
        'errors'  => $errors,
        'cleaned' => $cleaned,
    ];
}

// ─────────────────────────────────────────────
//  HTTP handler
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'report');

    switch ($action) {
        case 'validate':
            echo json_encode(validateTrends($input['trends'] ?? []));
            break;

        case 'boost':
            $result    = $input['result']    ?? [];
            $trends    = $input['trends']    ?? [];
            $retryMode = $input['retry']     ?? false;
            $boosted   = boostConfidence($result, $trends, $retryMode);
            $boosted   = applyEdgeCorrection($boosted, $trends);
            echo json_encode($boosted);
            break;

        case 'report':
        default:
            echo json_encode(getAccuracyReport());
            break;
    }
}
