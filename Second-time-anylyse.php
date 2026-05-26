<?php
/**
 * Second-time-anylyse.php — DHANI WIN
 * Algorithm #2: Recency-Weighted Score
 *
 * Weights each trend by position (newest = highest weight).
 * Score is REAL — the ratio of weighted votes, scaled to 50–85.
 * NO fake numbers.
 */

function secondTimeAnalyse(array $trends): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 1) {
        return ['prediction' => 'BIG', 'score' => 50, 'algorithm' => 'second', 'reason' => 'No data'];
    }

    // Position weight: index 0 = weight 1, index 9 = weight 10
    $wBig = 0.0;
    $wSml = 0.0;
    $totalWeight = 0.0;

    for ($i = 0; $i < $n; $i++) {
        $w = $i + 1;
        $totalWeight += $w;
        if ($types[$i] === 'BIG') $wBig += $w;
        else                       $wSml += $w;
    }

    $prediction = ($wBig >= $wSml) ? 'BIG' : 'Small';
    $winWeight  = max($wBig, $wSml);

    // dominance: 0.5 = tie, 1.0 = all on one side
    $dominance = $totalWeight > 0 ? ($winWeight / $totalWeight) : 0.5;

    // Map dominance (0.5 → 1.0) to score (50 → 85)
    $score = (int) round(50 + ($dominance - 0.5) * 70);
    $score = min(max($score, 50), 85);

    $reason = sprintf(
        "Weighted: BIG=%.1f Small=%.1f → %s (dominance=%.2f)",
        $wBig, $wSml, $prediction, $dominance
    );

    return [
        'prediction' => $prediction,
        'score'      => $score,
        'w_big'      => round($wBig, 1),
        'w_sml'      => round($wSml, 1),
        'dominance'  => round($dominance, 3),
        'reason'     => $reason,
        'algorithm'  => 'second',
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(secondTimeAnalyse($input['trends'] ?? []));
}
