<?php
/**
 * Calculation.php — DHANI WIN
 * Core calculation engine: aggregates votes from all 3 algorithms,
 * resolves conflicts, and computes final weighted confidence score.
 */

require_once __DIR__ . '/First-time-trend-anylyse.php';
require_once __DIR__ . '/Second-time-anylyse.php';
require_once __DIR__ . '/Third-time-anylyse.php';

// ─────────────────────────────────────────────
//  Run all 3 algorithms and aggregate
// ─────────────────────────────────────────────
function runCalculation(array $trends, bool $retryMode = false): array {
    // ── Run all three in sequence (simulated parallel) ──
    $r1 = firstTimeAnalyse($trends, $retryMode);
    $r2 = secondTimeAnalyse($trends, $retryMode);
    $r3 = thirdTimeAnalyse($trends, $retryMode);

    $results = [$r1, $r2, $r3];

    // ── Vote tally ───────────────────────────────
    $votes     = ['BIG' => 0, 'Small' => 0];
    $confSum   = ['BIG' => 0.0, 'Small' => 0.0];

    foreach ($results as $r) {
        $pred = $r['prediction'] ?? null;
        $conf = $r['confidence'] ?? 0;
        if ($pred) {
            $votes[$pred]++;
            $confSum[$pred] += $conf;
        }
    }

    // ── Consensus check ──────────────────────────
    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);

    // ── Weighted final prediction ────────────────
    // Algorithm weights: First=30%, Second=35%, Third=35%
    $weights    = [0.30, 0.35, 0.35];
    $weightedBIG   = 0.0;
    $weightedSmall = 0.0;

    foreach ($results as $i => $r) {
        $pred = $r['prediction'] ?? 'BIG';
        $conf = ($r['confidence'] ?? 50) / 100;
        if ($pred === 'BIG')   $weightedBIG   += $weights[$i] * $conf;
        else                    $weightedSmall += $weights[$i] * $conf;
    }

    $finalPrediction = ($weightedBIG >= $weightedSmall) ? 'BIG' : 'Small';

    // ── Confidence calculation ───────────────────
    $leadScore   = max($weightedBIG, $weightedSmall);
    $totalScore  = $weightedBIG + $weightedSmall;
    $rawConf     = $totalScore > 0 ? ($leadScore / $totalScore) : 0.5;

    // Agreement bonus
    if ($agreed) {
        $agreementBonus = $retryMode ? 0.08 : 0.05;
    } else {
        $agreementBonus = 0;
    }

    $finalConf = (int) min(
        round(($rawConf * 0.35 + 0.63 + $agreementBonus) * 100),
        $retryMode ? 99 : 98
    );
    $finalConf = max($finalConf, 82); // floor

    // ── Resolve conflict if algorithms disagree ──
    $conflictResolution = null;
    if (!$agreed && $votes['BIG'] !== $votes['Small']) {
        $majority = ($votes['BIG'] > $votes['Small']) ? 'BIG' : 'Small';
        if ($majority !== $finalPrediction) {
            $finalPrediction    = $majority;
            $conflictResolution = "Majority override: 2/3 algorithms agree on {$majority}";
        }
    } elseif ($votes['BIG'] === $votes['Small']) {
        // Perfect 1.5/1.5 split (impossible with 3, but safeguard)
        // Use weighted score as tiebreaker — already handled above
        $conflictResolution = "Tie resolved by weighted confidence scores";
    }

    return [
        'prediction'          => $finalPrediction,
        'confidence'          => $finalConf,
        'agreed'              => $agreed,
        'votes'               => $votes,
        'weighted'            => [
            'BIG'   => round($weightedBIG,   4),
            'Small' => round($weightedSmall, 4),
        ],
        'algorithm_results'   => [
            'first'  => ['prediction' => $r1['prediction'], 'confidence' => $r1['confidence']],
            'second' => ['prediction' => $r2['prediction'], 'confidence' => $r2['confidence']],
            'third'  => ['prediction' => $r3['prediction'], 'confidence' => $r3['confidence']],
        ],
        'conflict_resolution' => $conflictResolution,
        'retry_mode'          => $retryMode,
        'timestamp'           => date('Y-m-d H:i:s'),
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

    if (empty($trends)) {
        echo json_encode(['error' => 'No trends provided']);
        exit;
    }

    echo json_encode(runCalculation($trends, $retryMode));
}
