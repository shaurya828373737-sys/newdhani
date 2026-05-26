<?php
/**
 * Second-time-anylyse.php — DHANI WIN
 * Algorithm #2: Recency-Weighted Score
 *
 * ONE clear rule:
 * - Weight each trend by position (most recent = highest weight)
 * - Whichever side scores higher = prediction
 * - Additional: check last 5 trends window separately for recent momentum
 */

function secondTimeAnalyse(array $trends, bool $retryMode = false): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 2) {
        return ['prediction' => 'BIG', 'confidence' => 80, 'algorithm' => 'second'];
    }

    // ── Step 1: Full recency-weighted scoring ────────────────────
    // Position weight: trend[0]=1pt, trend[1]=2pt ... trend[9]=10pt
    $scoreBIG   = 0.0;
    $scoreSmall = 0.0;

    for ($i = 0; $i < $n; $i++) {
        $weight = $i + 1; // 1-based, most recent = highest
        if ($types[$i] === 'BIG') $scoreBIG   += $weight;
        else                       $scoreSmall += $weight;
    }

    $totalScore  = $scoreBIG + $scoreSmall;
    $prediction  = ($scoreBIG >= $scoreSmall) ? 'BIG' : 'Small';
    $winScore    = max($scoreBIG, $scoreSmall);
    $dominance   = $totalScore > 0 ? ($winScore / $totalScore) : 0.5;

    // ── Step 2: Last 5 window — recent momentum check ───────────
    $last5     = array_slice($types, -5);
    $last5Big  = count(array_filter($last5, fn($t) => $t === 'BIG'));
    $last5Sml  = 5 - $last5Big;
    $recentWin = ($last5Big >= $last5Sml) ? 'BIG' : 'Small';

    // ── Step 3: Combine full score + recent momentum ─────────────
    // If both agree → high confidence
    // If they disagree → trust recent window (last 5) override
    if ($recentWin !== $prediction) {
        // Recent momentum overrides full score
        $prediction = $recentWin;
        $dominance  = max($dominance - 0.08, 0.50); // slight confidence penalty
        $reason     = "Recent momentum override (last5={$recentWin})";
    } else {
        $reason = "Full score + recent agree ({$prediction})";
    }

    // ── Step 4: Map dominance to confidence ──────────────────────
    // dominance range: 0.5 (50/50) → 1.0 (all same side)
    // map to confidence: 82 → 97
    $confidence = (int) round(82 + ($dominance - 0.5) * 30);
    $confidence = min($confidence, $retryMode ? 99 : 97);
    $confidence = max($confidence, 82);

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'score_big'  => round($scoreBIG, 1),
        'score_sml'  => round($scoreSmall, 1),
        'dominance'  => round($dominance, 3),
        'reason'     => $reason,
        'algorithm'  => 'second',
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(secondTimeAnalyse($input['trends'] ?? [], $input['retry'] ?? false));
}
