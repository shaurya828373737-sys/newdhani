<?php
/**
 * First-time-trend-anylyse.php — DHANI WIN
 * Algorithm #1: Streak Detection
 *
 * Reads trailing streak, outputs a REAL score (0–100)
 * based purely on how strong the pattern is.
 * NO hardcoded fake numbers.
 */

function firstTimeAnalyse(array $trends): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 2) {
        return ['prediction' => 'BIG', 'score' => 50, 'algorithm' => 'first', 'reason' => 'Not enough data'];
    }

    // Count trailing streak
    $last      = $types[$n - 1];
    $streak    = 0;
    for ($i = $n - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $streak++;
        else break;
    }
    $opposite = ($last === 'BIG') ? 'Small' : 'BIG';

    // Overall count
    $bigCount   = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;

    // ── Decision logic ───────────────────────────────────────────
    // streak >= 3  → reversal is likely     → score based on streak length
    // streak == 2  → weak reversal signal   → score based on streak + majority check
    // streak == 1  → no streak              → follow overall majority

    if ($streak >= 3) {
        $prediction = $opposite;
        // score = 60 base + 5 per extra streak length, capped at 85
        $score = min(60 + ($streak * 5), 85);
        $reason = "Streak {$streak}× {$last} → reversal to {$opposite}";

    } elseif ($streak === 2) {
        $majorityFavors = ($bigCount > $smallCount) ? 'BIG' : 'Small';
        if ($majorityFavors === $opposite) {
            // majority also agrees with reversal → stronger signal
            $prediction = $opposite;
            $score      = 68;
            $reason     = "Streak 2× {$last} + majority → reversal to {$opposite}";
        } else {
            // majority disagrees → weak signal
            $prediction = $opposite;
            $score      = 57;
            $reason     = "Streak 2× {$last} → weak reversal (majority conflict)";
        }

    } else {
        // No streak — follow overall majority
        if ($bigCount > $smallCount) {
            $prediction = 'BIG';
            $score      = 50 + (int)(($bigCount - $smallCount) * 4);
        } elseif ($smallCount > $bigCount) {
            $prediction = 'Small';
            $score      = 50 + (int)(($smallCount - $bigCount) * 4);
        } else {
            // Perfect tie
            $prediction = $last; // last seen
            $score      = 50;
        }
        $score  = min($score, 78);
        $reason = "No streak — majority ({$bigCount}B / {$smallCount}S) → {$prediction}";
    }

    return [
        'prediction' => $prediction,
        'score'      => $score,   // raw score 50–85, used by Calculation.php
        'streak'     => $streak,
        'reason'     => $reason,
        'algorithm'  => 'first',
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(firstTimeAnalyse($input['trends'] ?? []));
}
