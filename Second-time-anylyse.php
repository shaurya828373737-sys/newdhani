<?php
/**
 * Second-time-anylyse.php — DHANI WIN
 * Algorithm #2: Recency-Weighted Score
 * Level 1 → standard recency weight
 * Level 2 → split window comparison (first5 vs last5)
 * Level 3 → MASTER pure last-3 momentum lock
 */

function secondTimeAnalyse(array $trends, int $level = 1): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 1) {
        return ['prediction' => 'BIG', 'score' => 50, 'algorithm' => 'second', 'reason' => 'No data'];
    }

    // ── LEVEL 1: Standard recency weight ────────────────────────
    if ($level === 1) {
        $wBig = 0.0; $wSml = 0.0; $totalW = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $w = $i + 1; $totalW += $w;
            $types[$i] === 'BIG' ? $wBig += $w : $wSml += $w;
        }
        $pred  = ($wBig >= $wSml) ? 'BIG' : 'Small';
        $dom   = $totalW > 0 ? max($wBig, $wSml) / $totalW : 0.5;
        $score = (int) round(50 + ($dom - 0.5) * 70);
        return ['prediction' => $pred, 'score' => min(max($score, 50), 82),
                'reason' => "L1 Weight BIG={$wBig} Sml={$wSml} → {$pred}", 'algorithm' => 'second'];
    }

    // ── LEVEL 2: Split window — first5 vs last5 ─────────────────
    if ($level === 2) {
        $first5 = array_slice($types, 0, 5);
        $last5  = array_slice($types, -5);

        $f5Big = count(array_filter($first5, fn($t) => $t === 'BIG'));
        $l5Big = count(array_filter($last5,  fn($t) => $t === 'BIG'));
        $l5Sml = 5 - $l5Big;

        // Momentum shift: if last5 is MORE of one side than first5 → follow last5
        $shift = $l5Big - $f5Big; // positive = shifting BIG

        if (abs($shift) >= 2) {
            $pred  = ($shift > 0) ? 'BIG' : 'Small';
            $score = 65 + abs($shift) * 4;
            $reason = "L2 Momentum shift={$shift} → {$pred}";
        } else {
            // No strong shift → follow overall last5 dominance
            $pred   = ($l5Big >= $l5Sml) ? 'BIG' : 'Small';
            $score  = 60 + abs($l5Big - $l5Sml) * 3;
            $reason = "L2 Last5 {$l5Big}B/{$l5Sml}S → {$pred}";
        }

        return ['prediction' => $pred, 'score' => min($score, 84), 'reason' => $reason, 'algorithm' => 'second'];
    }

    // ── LEVEL 3: MASTER — pure last 3 momentum, no second-guess ─
    if ($level === 3) {
        $last3  = array_slice($types, -3);
        $l3Big  = count(array_filter($last3, fn($t) => $t === 'BIG'));
        $l3Sml  = 3 - $l3Big;

        // Check transition in last 3: are they all same or mixed?
        $allSame = count(array_unique($last3)) === 1;

        if ($allSame) {
            // All same last 3 → REVERSAL is the master call
            $pred  = ($last3[0] === 'BIG') ? 'Small' : 'BIG';
            $score = 85;
            $reason = "L3 MASTER last3 all {$last3[0]} → reverse {$pred}";
        } else {
            // Mixed → follow the LAST entry (momentum continuation)
            $lastEntry = $types[$n - 1];
            $pred      = $lastEntry;
            $score     = 75;
            $reason    = "L3 MASTER last3 mixed → follow last={$pred}";
        }

        return ['prediction' => $pred, 'score' => $score, 'reason' => $reason, 'algorithm' => 'second'];
    }

    return ['prediction' => 'BIG', 'score' => 55, 'reason' => 'fallback', 'algorithm' => 'second'];
}

if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(secondTimeAnalyse($input['trends'] ?? [], (int)($input['level'] ?? 1)));
}
