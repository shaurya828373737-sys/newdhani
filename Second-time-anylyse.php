<?php
/**
 * Second-time-anylyse.php — DHANI WIN
 * Algorithm #2: Momentum & Weighted Recency Analysis
 *
 * Strategy: Applies exponential weighting to recent trends,
 * detects momentum shifts, and cross-validates with pattern pairs.
 */

require_once __DIR__ . '/get-data.php';

// ─────────────────────────────────────────────
//  Main entry point
// ─────────────────────────────────────────────
function secondTimeAnalyse(array $trends, bool $retryMode = false): array {
    if (count($trends) < 10) {
        return ['prediction' => null, 'confidence' => 0, 'reason' => 'Insufficient data'];
    }

    $types  = array_column($trends, 'type');
    $scores = ['BIG' => 0.0, 'Small' => 0.0];
    $rules  = [];

    // ── Rule 1: Exponential Recency Weighting ────
    // Most recent trends carry more weight (exponential decay backwards)
    $decayBase = 0.88;
    for ($i = count($types) - 1; $i >= 0; $i--) {
        $posFromEnd = count($types) - 1 - $i;
        $weight     = pow($decayBase, $posFromEnd) * 0.5;
        $scores[$types[$i]] += $weight;
    }
    $rules[] = 'Exponential recency weighting applied (decay=0.88)';

    // ── Rule 2: Momentum Shift Detection ────────
    // Compare first half vs second half
    $firstHalf  = array_slice($types, 0, 5);
    $secondHalf = array_slice($types, 5, 5);

    $h1Big = array_count_values($firstHalf)['BIG']   ?? 0;
    $h2Big = array_count_values($secondHalf)['BIG']  ?? 0;

    $momentumShift = $h2Big - $h1Big; // positive = shifting BIG, negative = shifting Small

    if (abs($momentumShift) >= 2) {
        $momentumFavor = ($momentumShift > 0) ? 'BIG' : 'Small';
        $weight        = 0.74 + (abs($momentumShift) - 2) * 0.05;
        $scores[$momentumFavor] += min($weight, 0.90);
        $rules[] = "Momentum shifting to {$momentumFavor} (Δ={$momentumShift}) ({$weight})";
    }

    // ── Rule 3: Pair Pattern Analysis ───────────
    // Count consecutive pairs: BB, SS, BS, SB
    $pairs = ['BB' => 0, 'SS' => 0, 'BS' => 0, 'SB' => 0];
    for ($i = 0; $i < count($types) - 1; $i++) {
        $key = substr($types[$i], 0, 1) . substr($types[$i+1], 0, 1);
        if (isset($pairs[$key])) $pairs[$key]++;
    }

    $lastTwo = array_slice($types, -2);
    $lastPair = substr($lastTwo[0], 0, 1) . substr($lastTwo[1], 0, 1);

    // Based on last pair, determine follow-up probability
    $pairFollowMap = [
        'BB' => ['Small' => 0.72, 'BIG'   => 0.28],
        'SS' => ['BIG'   => 0.72, 'Small' => 0.28],
        'BS' => ['BIG'   => 0.58, 'Small' => 0.42],
        'SB' => ['Small' => 0.58, 'BIG'   => 0.42],
    ];

    if (isset($pairFollowMap[$lastPair])) {
        foreach ($pairFollowMap[$lastPair] as $type => $prob) {
            $scores[$type] += $prob * 0.65;
        }
        $rules[] = "Last pair {$lastPair} → follow probability applied (0.65 weight)";
    }

    // ── Rule 4: Triple Pattern ───────────────────
    $last3 = array_slice($types, -3);
    $key3  = implode('', array_map(fn($t) => substr($t, 0, 1), $last3));

    $tripleMap = [
        'BBB' => 'Small', 'SSS' => 'BIG',
        'BBS' => 'BIG',   'SSB' => 'Small',
        'BSB' => 'Small', 'SBS' => 'BIG',
        'BSS' => 'BIG',   'SBB' => 'Small',
    ];

    if (isset($tripleMap[$key3])) {
        $favor = $tripleMap[$key3];
        $scores[$favor] += 0.70;
        $rules[] = "Triple pattern {$key3} → {$favor} (0.70)";
    }

    // ── Rule 5: Number-weighted analysis ────────
    // Use trend numbers if available
    $numbers = array_column($trends, 'number');
    $numBig  = 0; $numSmall = 0;
    foreach ($trends as $t) {
        $n = (int)($t['number'] ?? 0);
        if ($t['type'] === 'BIG')   $numBig   += $n;
        else                         $numSmall += $n;
    }
    if ($numBig > $numSmall) {
        $scores['BIG']   += 0.40;
        $rules[] = "Number weight favors BIG (sum={$numBig} vs {$numSmall}) (0.40)";
    } elseif ($numSmall > $numBig) {
        $scores['Small'] += 0.40;
        $rules[] = "Number weight favors Small (sum={$numSmall} vs {$numBig}) (0.40)";
    }

    // ── Retry boost ──────────────────────────────
    if ($retryMode) {
        foreach ($scores as $k => $v) { $scores[$k] *= 1.14; }
        $rules[] = 'Retry mode: deeper momentum scan, confidence boosted';
    }

    // ── Final decision ───────────────────────────
    $prediction = ($scores['BIG'] >= $scores['Small']) ? 'BIG' : 'Small';
    $total      = $scores['BIG'] + $scores['Small'];
    $rawConf    = $total > 0 ? ($scores[$prediction] / $total) * 100 : 50;
    $confidence = (int) min(round($rawConf * 0.36 + 64), $retryMode ? 99 : 98);

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'scores'     => $scores,
        'rules'      => $rules,
        'algorithm'  => 'second',
    ];
}

// ─────────────────────────────────────────────
//  HTTP handler
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends    = $input['trends'] ?? [];
    $retryMode = $input['retry']  ?? false;
    echo json_encode(secondTimeAnalyse($trends, $retryMode));
}
