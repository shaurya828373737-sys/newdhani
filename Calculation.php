<?php
/**
 * Calculation.php — DHANI WIN
 * Aggregates results from all 3 algorithms.
 *
 * Rule:
 * - Run all 3 independently
 * - If 3/3 agree → use that, high confidence
 * - If 2/3 agree → use the majority, medium confidence
 * - If 1/3 (impossible with 3 algos) → use algo2 (recency) as tiebreaker
 * - Final confidence = average of agreeing algorithms
 */

require_once __DIR__ . '/First-time-trend-anylyse.php';
require_once __DIR__ . '/Second-time-anylyse.php';
require_once __DIR__ . '/Third-time-anylyse.php';

function runCalculation(array $trends, bool $retryMode = false): array {

    // Run all 3
    $r1 = firstTimeAnalyse($trends,  $retryMode);
    $r2 = secondTimeAnalyse($trends, $retryMode);
    $r3 = thirdTimeAnalyse($trends,  $retryMode);

    $p1 = $r1['prediction'];
    $p2 = $r2['prediction'];
    $p3 = $r3['prediction'];

    $c1 = $r1['confidence'];
    $c2 = $r2['confidence'];
    $c3 = $r3['confidence'];

    // ── Vote count ───────────────────────────────────────────────
    $votes = ['BIG' => 0, 'Small' => 0];
    $votes[$p1]++;
    $votes[$p2]++;
    $votes[$p3]++;

    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);

    // ── Determine winner ─────────────────────────────────────────
    if ($agreed) {
        // All 3 agree — use their average confidence
        $finalPrediction = $p1;
        $finalConfidence = (int) round(($c1 + $c2 + $c3) / 3);

    } elseif ($votes['BIG'] === 2) {
        // BIG wins 2-1 — average only the BIG voters
        $finalPrediction = 'BIG';
        $bigConfs = [];
        if ($p1 === 'BIG') $bigConfs[] = $c1;
        if ($p2 === 'BIG') $bigConfs[] = $c2;
        if ($p3 === 'BIG') $bigConfs[] = $c3;
        $finalConfidence = (int) round(array_sum($bigConfs) / count($bigConfs));

    } else {
        // Small wins 2-1 — average only the Small voters
        $finalPrediction = 'Small';
        $smlConfs = [];
        if ($p1 === 'Small') $smlConfs[] = $c1;
        if ($p2 === 'Small') $smlConfs[] = $c2;
        if ($p3 === 'Small') $smlConfs[] = $c3;
        $finalConfidence = (int) round(array_sum($smlConfs) / count($smlConfs));
    }

    // ── Clamp confidence ─────────────────────────────────────────
    $max = $retryMode ? 99 : 98;
    $finalConfidence = min(max($finalConfidence, 82), $max);

    return [
        'prediction'       => $finalPrediction,
        'confidence'       => $finalConfidence,
        'agreed'           => $agreed,
        'votes'            => $votes,
        'algorithm_results' => [
            'first'  => ['prediction' => $p1, 'confidence' => $c1, 'reason' => $r1['reason'] ?? ''],
            'second' => ['prediction' => $p2, 'confidence' => $c2, 'reason' => $r2['reason'] ?? ''],
            'third'  => ['prediction' => $p3, 'confidence' => $c3, 'reason' => $r3['reason'] ?? ''],
        ],
        'retry_mode'  => $retryMode,
        'timestamp'   => date('Y-m-d H:i:s'),
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['trends'])) { echo json_encode(['error' => 'No trends']); exit; }
    echo json_encode(runCalculation($input['trends'], $input['retry'] ?? false));
}
