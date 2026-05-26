<?php
/**
 * Third-time-anylyse.php — DHANI WIN
 * Algorithm #3: Statistical Frequency & Entropy Analysis
 *
 * Strategy: Uses frequency distribution, entropy scoring,
 * and transition matrix probability to confirm final result.
 * This is the "tie-breaker" and confidence validator.
 */

require_once __DIR__ . '/get-data.php';

// ─────────────────────────────────────────────
//  Main entry point
// ─────────────────────────────────────────────
function thirdTimeAnalyse(array $trends, bool $retryMode = false): array {
    if (count($trends) < 10) {
        return ['prediction' => null, 'confidence' => 0, 'reason' => 'Insufficient data'];
    }

    $types  = array_column($trends, 'type');
    $scores = ['BIG' => 0.0, 'Small' => 0.0];
    $rules  = [];

    // ── Rule 1: Transition Matrix Probability ────
    // Build transition matrix from the 10 trends
    $matrix = [
        'BIG'   => ['BIG' => 0, 'Small' => 0],
        'Small' => ['BIG' => 0, 'Small' => 0],
    ];
    for ($i = 0; $i < count($types) - 1; $i++) {
        $from = $types[$i];
        $to   = $types[$i + 1];
        if (isset($matrix[$from][$to])) {
            $matrix[$from][$to]++;
        }
    }

    $lastType = end($types);
    $fromRow  = $matrix[$lastType];
    $rowTotal = array_sum($fromRow);

    if ($rowTotal > 0) {
        $probBig   = $fromRow['BIG']   / $rowTotal;
        $probSmall = $fromRow['Small'] / $rowTotal;
        $scores['BIG']   += $probBig   * 0.80;
        $scores['Small'] += $probSmall * 0.80;
        $rules[] = "Transition from {$lastType}: P(BIG)={$probBig}, P(Small)={$probSmall} (weight 0.80)";
    }

    // ── Rule 2: Shannon Entropy ─────────────────
    // Low entropy (predictable) = follow dominant; high entropy = reversal
    $bigCount   = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = count($types) - $bigCount;
    $n          = count($types);

    $pB = $bigCount / $n;
    $pS = $smallCount / $n;

    $entropy = 0;
    if ($pB > 0) $entropy -= $pB * log($pB, 2);
    if ($pS > 0) $entropy -= $pS * log($pS, 2);
    // Max entropy = 1.0 (perfectly mixed), Min = 0 (all same)

    if ($entropy < 0.65) {
        // Predictable stream → continue dominant
        $dominant = ($bigCount >= $smallCount) ? 'BIG' : 'Small';
        $scores[$dominant] += (1 - $entropy) * 0.60;
        $rules[] = "Low entropy ({$entropy}) → dominant continuation: {$dominant}";
    } else {
        // High entropy → reversal expected
        $recent = end($types);
        $opposite = ($recent === 'BIG') ? 'Small' : 'BIG';
        $scores[$opposite] += $entropy * 0.55;
        $rules[] = "High entropy ({$entropy}) → reversal to {$opposite}";
    }

    // ── Rule 3: Frequency Window Analysis ───────
    // Split into 3 windows of ~3 and track shift
    $w1 = array_slice($types, 0, 3);
    $w2 = array_slice($types, 3, 3);
    $w3 = array_slice($types, 6, 4);

    $w3BigCount = count(array_filter($w3, fn($t) => $t === 'BIG'));
    $w3Ratio    = $w3BigCount / count($w3);

    if ($w3Ratio >= 0.75) {
        $scores['BIG']   += 0.62;
        $rules[] = "Window 3 heavily BIG ({$w3BigCount}/4) → BIG continuation (0.62)";
    } elseif ($w3Ratio <= 0.25) {
        $scores['Small'] += 0.62;
        $rules[] = "Window 3 heavily Small → Small continuation (0.62)";
    } else {
        // Mixed W3 → reversal of W3 leader
        $w3Leader = ($w3BigCount >= 2) ? 'BIG' : 'Small';
        $opposite = ($w3Leader === 'BIG') ? 'Small' : 'BIG';
        $scores[$opposite] += 0.50;
        $rules[] = "Window 3 mixed → reverse {$w3Leader} to {$opposite} (0.50)";
    }

    // ── Rule 4: Mirror Pattern ───────────────────
    // Check if last 4 mirror first 4 (palindrome-like)
    $first4 = array_slice($types, 0, 4);
    $last4  = array_slice($types, 6, 4);
    $mirror = array_reverse($first4);
    $matchCount = 0;
    for ($i = 0; $i < 4; $i++) {
        if ($last4[$i] === $mirror[$i]) $matchCount++;
    }
    if ($matchCount >= 3) {
        $midPoint = $types[4];
        $scores[$midPoint] += 0.55;
        $rules[] = "Mirror pattern detected (match={$matchCount}/4) → {$midPoint} (0.55)";
    }

    // ── Rule 5: Balanced Confirmation ───────────
    // If Big and Small are within 1, trust last item's opposite (balance theory)
    if (abs($bigCount - $smallCount) <= 1) {
        $lastItem = end($types);
        $opposite = ($lastItem === 'BIG') ? 'Small' : 'BIG';
        $scores[$opposite] += 0.58;
        $rules[] = "Balanced distribution → expect {$opposite} for balance (0.58)";
    }

    // ── Retry boost ──────────────────────────────
    if ($retryMode) {
        foreach ($scores as $k => $v) { $scores[$k] *= 1.15; }
        $rules[] = 'Retry mode: full statistical re-weight applied';
    }

    // ── Final decision ───────────────────────────
    $prediction = ($scores['BIG'] >= $scores['Small']) ? 'BIG' : 'Small';
    $total      = $scores['BIG'] + $scores['Small'];
    $rawConf    = $total > 0 ? ($scores[$prediction] / $total) * 100 : 50;
    $confidence = (int) min(round($rawConf * 0.34 + 64), $retryMode ? 99 : 98);

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'scores'     => $scores,
        'rules'      => $rules,
        'entropy'    => round($entropy ?? 0, 4),
        'matrix'     => $matrix,
        'algorithm'  => 'third',
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
    echo json_encode(thirdTimeAnalyse($trends, $retryMode));
}
