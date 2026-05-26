<?php
/**
 * Calculation.php — DHANI WIN
 * 3-Level win system aggregator.
 *
 * Level 1 → Normal prediction
 * Level 2 → Deeper analysis after 1st loss (different strategies)
 * Level 3 → MASTER result after 2nd loss (must win)
 *
 * At Level 3, we also cross-check the previous two wrong predictions
 * and pick the OPPOSITE side they both got wrong on.
 */

require_once __DIR__ . '/First-time-trend-anylyse.php';
require_once __DIR__ . '/Second-time-anylyse.php';
require_once __DIR__ . '/Third-time-anylyse.php';

function runCalculation(array $trends, int $level = 1, array $prevWrongPredictions = []): array {

    $r1 = firstTimeAnalyse($trends,  $level);
    $r2 = secondTimeAnalyse($trends, $level);
    $r3 = thirdTimeAnalyse($trends,  $level);

    $p1 = $r1['prediction']; $s1 = $r1['score'];
    $p2 = $r2['prediction']; $s2 = $r2['score'];
    $p3 = $r3['prediction']; $s3 = $r3['score'];

    $votes = ['BIG' => 0, 'Small' => 0];
    $votes[$p1]++; $votes[$p2]++; $votes[$p3]++;
    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);

    // ── Standard vote resolution ─────────────────────────────────
    if ($agreed) {
        $finalPrediction = $p1;
        $confidence      = (int) round(($s1 + $s2 + $s3) / 3);
        $note = "3/3 agree (L{$level})";
    } elseif ($votes['BIG'] === 2) {
        $finalPrediction = 'BIG';
        $agreeScores = [];
        if ($p1 === 'BIG') $agreeScores[] = $s1;
        if ($p2 === 'BIG') $agreeScores[] = $s2;
        if ($p3 === 'BIG') $agreeScores[] = $s3;
        $confidence = (int) round(array_sum($agreeScores) / count($agreeScores)) - 5;
        $note = "2/3 BIG (L{$level})";
    } else {
        $finalPrediction = 'Small';
        $agreeScores = [];
        if ($p1 === 'Small') $agreeScores[] = $s1;
        if ($p2 === 'Small') $agreeScores[] = $s2;
        if ($p3 === 'Small') $agreeScores[] = $s3;
        $confidence = (int) round(array_sum($agreeScores) / count($agreeScores)) - 5;
        $note = "2/3 Small (L{$level})";
    }

    // ── LEVEL 3 MASTER OVERRIDE ───────────────────────────────────
    // If 2 previous predictions were wrong, we know what the game
    // has been favouring. Override with the opposite of both wrongs.
    if ($level === 3 && count($prevWrongPredictions) >= 2) {
        $wrong1 = $prevWrongPredictions[count($prevWrongPredictions) - 2];
        $wrong2 = $prevWrongPredictions[count($prevWrongPredictions) - 1];

        if ($wrong1 === $wrong2) {
            // Both previous bets were same side and both lost
            // → The game is strongly on the OTHER side
            $masterOverride = ($wrong1 === 'BIG') ? 'Small' : 'BIG';

            // Only override if our algorithms agree OR if confidence is low
            if ($masterOverride === $finalPrediction) {
                // Algorithms ALSO say the same — maximum confidence
                $confidence = max($confidence + 8, 82);
                $note      .= " | MASTER confirmed";
            } else {
                // Algorithms disagree — trust the master override
                $finalPrediction = $masterOverride;
                $confidence      = 80;
                $note           .= " | MASTER override: both wrongs were {$wrong1}";
            }
        } else {
            // Previous wrongs were different sides — game is chaotic
            // Trust the algorithms' majority but give extra weight to level-3 scores
            $topScore = max($s1, $s2, $s3);
            if ($topScore === $s1) $finalPrediction = $p1;
            elseif ($topScore === $s2) $finalPrediction = $p2;
            else $finalPrediction = $p3;
            $confidence = (int) round(($topScore - 4));
            $note      .= " | MASTER highest-score algo wins";
        }
    }

    $confidence = max($confidence, 50);

    return [
        'prediction'        => $finalPrediction,
        'confidence'        => $confidence,
        'agreed'            => $agreed,
        'level'             => $level,
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

if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input['trends'])) { echo json_encode(['error' => 'No trends']); exit; }
    echo json_encode(runCalculation(
        $input['trends'],
        (int)($input['level'] ?? 1),
        $input['prev_wrong'] ?? []
    ));
}
