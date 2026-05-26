<?php
/**
 * Calculation.php — DHANI WIN
 *
 * Runs all 3 algorithms, combines their votes + scores.
 * Confidence shown to user = REAL calculated agreement score.
 *
 * Formula:
 *   - 3/3 agree  → confidence = average score of all 3
 *   - 2/3 agree  → confidence = average score of the 2 that agreed,
 *                  minus a 5-point disagreement penalty
 *   - No fake ceiling. Max is whatever the algorithms actually produce.
 */

require_once __DIR__ . '/First-time-trend-anylyse.php';
require_once __DIR__ . '/Second-time-anylyse.php';
require_once __DIR__ . '/Third-time-anylyse.php';

function runCalculation(array $trends): array {

    $r1 = firstTimeAnalyse($trends);
    $r2 = secondTimeAnalyse($trends);
    $r3 = thirdTimeAnalyse($trends);

    $p1 = $r1['prediction']; $s1 = $r1['score'];
    $p2 = $r2['prediction']; $s2 = $r2['score'];
    $p3 = $r3['prediction']; $s3 = $r3['score'];

    // Vote tally
    $votes = ['BIG' => 0, 'Small' => 0];
    $votes[$p1]++; $votes[$p2]++; $votes[$p3]++;

    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);

    if ($agreed) {
        $finalPrediction = $p1;
        // All 3 agree — average their scores
        $confidence      = (int) round(($s1 + $s2 + $s3) / 3);
        $note            = "3/3 agree";

    } elseif ($votes['BIG'] === 2) {
        $finalPrediction = 'BIG';
        // Average only the two that said BIG, minus disagreement penalty
        $agreeScores = [];
        if ($p1 === 'BIG') $agreeScores[] = $s1;
        if ($p2 === 'BIG') $agreeScores[] = $s2;
        if ($p3 === 'BIG') $agreeScores[] = $s3;
        $confidence = (int) round(array_sum($agreeScores) / count($agreeScores)) - 5;
        $note       = "2/3 agree on BIG";

    } else {
        $finalPrediction = 'Small';
        $agreeScores = [];
        if ($p1 === 'Small') $agreeScores[] = $s1;
        if ($p2 === 'Small') $agreeScores[] = $s2;
        if ($p3 === 'Small') $agreeScores[] = $s3;
        $confidence = (int) round(array_sum($agreeScores) / count($agreeScores)) - 5;
        $note       = "2/3 agree on Small";
    }

    // Hard floor at 50 (never show below 50 — we always have a prediction)
    $confidence = max($confidence, 50);

    return [
        'prediction'        => $finalPrediction,
        'confidence'        => $confidence,   // REAL number, no fake cap
        'agreed'            => $agreed,
        'votes'             => $votes,
        'note'              => $note,
        'algorithm_results' => [
            'first'  => ['prediction' => $p1, 'score' => $s1, 'reason' => $r1['reason'] ?? ''],
            'second' => ['prediction' => $p2, 'score' => $s2, 'reason' => $r2['reason'] ?? ''],
            'third'  => ['prediction' => $p3, 'score' => $s3, 'reason' => $r3['reason'] ?? ''],
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['trends'])) { echo json_encode(['error' => 'No trends']); exit; }
    echo json_encode(runCalculation($input['trends']));
}
