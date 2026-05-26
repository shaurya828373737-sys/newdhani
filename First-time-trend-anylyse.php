<?php
/**
 * First-time-trend-anylyse.php — DHANI WIN
 * Algorithm #1: Pattern Reversal & Streak Analysis
 *
 * Strategy: Detects streaks (3+ same in a row), zigzag breaks,
 * and dominant majority to predict next result.
 */

require_once __DIR__ . '/get-data.php';

// ─────────────────────────────────────────────
//  Main entry point
// ─────────────────────────────────────────────
function firstTimeAnalyse(array $trends, bool $retryMode = false): array {
    if (count($trends) < 10) {
        return ['prediction' => null, 'confidence' => 0, 'reason' => 'Insufficient data'];
    }

    $types  = array_column($trends, 'type');
    $scores = ['BIG' => 0.0, 'Small' => 0.0];
    $rules  = [];

    // ── Rule 1: Streak Reversal ──────────────────
    $streak      = detectStreak($types);
    $streakType  = $streak['type'];
    $streakLen   = $streak['length'];

    if ($streakLen >= 3) {
        $opposite = ($streakType === 'BIG') ? 'Small' : 'BIG';
        $weight   = min(0.78 + ($streakLen - 3) * 0.04, 0.92);
        $scores[$opposite] += $weight;
        $rules[] = "Streak of {$streakLen} {$streakType}s → reversal likely ({$weight})";
    }

    // ── Rule 2: Dominant Majority ────────────────
    $bigCount   = array_count_values($types)['BIG']   ?? 0;
    $smallCount = array_count_values($types)['Small'] ?? 0;

    if ($bigCount > $smallCount) {
        $scores['BIG']   += 0.55;
        $rules[] = "Majority BIG ({$bigCount}/10) → continue trend (0.55)";
    } elseif ($smallCount > $bigCount) {
        $scores['Small'] += 0.55;
        $rules[] = "Majority Small ({$smallCount}/10) → continue trend (0.55)";
    }

    // ── Rule 3: Last 3 Zigzag Pattern ───────────
    $last3 = array_slice($types, -3);
    if (count(array_unique($last3)) === 2 && isAlternating($last3)) {
        $continuation = $last3[2]; // follow the alternation
        $scores[$continuation] += 0.68;
        $rules[] = "Zigzag pattern detected → follow alternation ({$continuation}) (0.68)";
    }

    // ── Rule 4: Last 2 Same → Continuation ──────
    $last2 = array_slice($types, -2);
    if ($last2[0] === $last2[1]) {
        $scores[$last2[0]] += 0.52;
        $rules[] = "Last 2 same ({$last2[0]}) → short continuation (0.52)";
    }

    // ── Rule 5: Fibonacci Position Weighting ────
    $fibPositions = [0, 1, 1, 2, 3, 5, 8]; // indices to weight heavier (recent)
    foreach ($fibPositions as $fi => $pos) {
        if (isset($types[$pos])) {
            $scores[$types[$pos]] += 0.02 * ($fi + 1);
        }
    }

    // ── Retry mode: boost confidence ─────────────
    if ($retryMode) {
        foreach ($scores as $k => $v) {
            $scores[$k] *= 1.12;
        }
        $rules[] = 'Retry mode: confidence boost applied';
    }

    // ── Final decision ───────────────────────────
    $prediction = ($scores['BIG'] >= $scores['Small']) ? 'BIG' : 'Small';
    $total      = $scores['BIG'] + $scores['Small'];
    $rawConf    = $total > 0 ? ($scores[$prediction] / $total) * 100 : 50;
    $confidence = (int) min(round($rawConf * 0.35 + 65), $retryMode ? 99 : 98);

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'scores'     => $scores,
        'rules'      => $rules,
        'algorithm'  => 'first',
    ];
}

// ─────────────────────────────────────────────
//  Detect longest current streak from end
// ─────────────────────────────────────────────
function detectStreak(array $types): array {
    $last   = end($types);
    $length = 0;
    for ($i = count($types) - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $length++;
        else break;
    }
    return ['type' => $last, 'length' => $length];
}

// ─────────────────────────────────────────────
//  Check if array alternates (B,S,B or S,B,S)
// ─────────────────────────────────────────────
function isAlternating(array $arr): bool {
    for ($i = 1; $i < count($arr); $i++) {
        if ($arr[$i] === $arr[$i - 1]) return false;
    }
    return true;
}

// ─────────────────────────────────────────────
//  HTTP handler (called directly or via master)
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends    = $input['trends']    ?? [];
    $retryMode = $input['retry']     ?? false;
    echo json_encode(firstTimeAnalyse($trends, $retryMode));
}
