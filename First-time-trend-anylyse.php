<?php
/**
 * First-time-trend-anylyse.php — DHANI WIN
 * Algorithm #1: Streak Detection & Reversal Logic
 *
 * ONE clear rule set — no contradictions:
 * - If streak >= 3 at the end → predict REVERSAL (strong signal)
 * - If streak == 2 at the end → predict REVERSAL (medium signal)
 * - If streak == 1 (alternating recent) → predict CONTINUATION of last
 * - Tiebreaker: overall count majority
 */

function firstTimeAnalyse(array $trends, bool $retryMode = false): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 2) {
        return ['prediction' => 'BIG', 'confidence' => 80, 'algorithm' => 'first'];
    }

    // ── Step 1: Count trailing streak from the end ──────────────
    $last        = $types[$n - 1];
    $streakLen   = 0;
    for ($i = $n - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $streakLen++;
        else break;
    }

    $opposite = ($last === 'BIG') ? 'Small' : 'BIG';

    // ── Step 2: Decide prediction based on streak ────────────────
    if ($streakLen >= 4) {
        // 4 or more same in a row → very strong reversal
        $prediction = $opposite;
        $confidence = $retryMode ? 99 : 96;
        $reason     = "Streak={$streakLen} → strong reversal";

    } elseif ($streakLen === 3) {
        // 3 same → reversal expected
        $prediction = $opposite;
        $confidence = $retryMode ? 98 : 93;
        $reason     = "Streak=3 → reversal";

    } elseif ($streakLen === 2) {
        // 2 same → lean reversal (less certain)
        $prediction = $opposite;
        $confidence = $retryMode ? 96 : 89;
        $reason     = "Streak=2 → lean reversal";

    } else {
        // streak=1 (last two are different) → follow the last one (momentum)
        $prediction = $last;
        $confidence = $retryMode ? 94 : 86;
        $reason     = "No streak → momentum continuation";
    }

    // ── Step 3: Tiebreaker if confidence is low ──────────────────
    // Cross-check: does the overall count support the decision?
    $bigCount   = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;
    $majority   = ($bigCount >= $smallCount) ? 'BIG' : 'Small';

    // If prediction disagrees with majority AND streak is only 2, lower confidence
    if ($streakLen === 2 && $prediction !== $majority) {
        $confidence = max($confidence - 4, 82);
        $reason    .= " (majority conflict -4)";
    }

    // If prediction agrees with majority AND streak >= 2, boost confidence
    if ($streakLen >= 2 && $prediction === $majority) {
        $confidence = min($confidence + 2, $retryMode ? 99 : 98);
        $reason    .= " (majority confirmed +2)";
    }

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'streak'     => $streakLen,
        'reason'     => $reason,
        'algorithm'  => 'first',
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(firstTimeAnalyse($input['trends'] ?? [], $input['retry'] ?? false));
}
