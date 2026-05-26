<?php
/**
 * Third-time-anylyse.php — DHANI WIN
 * Algorithm #3: Pattern Sequence Matching
 * Level 1 → last-3 pattern table
 * Level 2 → last-3 + last-5 cross-validation
 * Level 3 → MASTER full transition matrix over all 10 trends
 */

function thirdTimeAnalyse(array $trends, int $level = 1): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 3) {
        return ['prediction' => 'BIG', 'score' => 50, 'algorithm' => 'third', 'reason' => 'Need 3+ trends'];
    }

    $last3  = array_slice($types, -3);
    $enc    = fn($t) => $t === 'BIG' ? 'B' : 'S';
    $pat    = $enc($last3[0]) . $enc($last3[1]) . $enc($last3[2]);

    // Pattern table — signal strengths
    $table = [
        'BBB' => ['Small', 80], 'SSS' => ['BIG',   80],
        'BBS' => ['Small', 72], 'SSB' => ['BIG',   72],
        'BSS' => ['BIG',   68], 'SBB' => ['Small', 68],
        'BSB' => ['Small', 63], 'SBS' => ['BIG',   63],
    ];

    // ── LEVEL 1: Plain pattern table ────────────────────────────
    if ($level === 1) {
        if (isset($table[$pat])) {
            [$pred, $sig] = $table[$pat];
        } else {
            $pred = ($types[$n-1] === 'BIG') ? 'Small' : 'BIG';
            $sig  = 52;
        }
        return ['prediction' => $pred, 'score' => $sig,
                'pattern' => $pat, 'reason' => "L1 Pattern {$pat} → {$pred}", 'algorithm' => 'third'];
    }

    // ── LEVEL 2: Pattern + last5 cross-validation ───────────────
    if ($level === 2) {
        [$patPred, $patSig] = $table[$pat] ?? [($types[$n-1]==='BIG'?'Small':'BIG'), 52];

        $last5    = array_slice($types, -5);
        $l5Big    = count(array_filter($last5, fn($t) => $t === 'BIG'));
        $l5Sml    = 5 - $l5Big;
        $l5Pred   = ($l5Big >= $l5Sml) ? 'BIG' : 'Small';

        if ($patPred === $l5Pred) {
            // Both agree → boost
            $score  = min($patSig + 8, 86);
            $reason = "L2 Pattern+Last5 agree → {$patPred}";
            $pred   = $patPred;
        } else {
            // Disagree → trust pattern over last5 (pattern is more specific)
            $score  = max($patSig - 4, 56);
            $pred   = $patPred;
            $reason = "L2 Pattern vs Last5 conflict → trust pattern {$pred}";
        }

        return ['prediction' => $pred, 'score' => $score,
                'pattern' => $pat, 'reason' => $reason, 'algorithm' => 'third'];
    }

    // ── LEVEL 3: MASTER — full transition matrix ─────────────────
    // Build: for each unique pair (A→B) count how often it happened
    if ($level === 3) {
        $matrix = [
            'BIG'   => ['BIG' => 0, 'Small' => 0],
            'Small' => ['BIG' => 0, 'Small' => 0],
        ];
        for ($i = 0; $i < $n - 1; $i++) {
            $from = $types[$i]; $to = $types[$i + 1];
            $matrix[$from][$to]++;
        }

        $lastType = $types[$n - 1];
        $row      = $matrix[$lastType];
        $rowTotal = $row['BIG'] + $row['Small'];

        if ($rowTotal > 0) {
            // Which transition happened more after $lastType?
            $pred  = ($row['Small'] >= $row['BIG']) ? 'Small' : 'BIG';
            $prob  = max($row['Small'], $row['BIG']) / $rowTotal;
            $score = (int) round(60 + $prob * 28); // 60–88 range
            $reason = "L3 MASTER matrix after {$lastType}: BIG={$row['BIG']} Sml={$row['Small']} → {$pred}";
        } else {
            // No transitions seen from last type → use pattern
            [$pred, $score] = $table[$pat] ?? ['Small', 65];
            $reason = "L3 MASTER no matrix data → pattern {$pred}";
        }

        // Cross-check with pattern: if they agree, +5 bonus
        [$patPred] = $table[$pat] ?? [$pred];
        if ($patPred === $pred) {
            $score = min($score + 5, 88);
            $reason .= " +pattern agrees";
        }

        return ['prediction' => $pred, 'score' => $score,
                'pattern' => $pat, 'reason' => $reason, 'algorithm' => 'third'];
    }

    return ['prediction' => 'BIG', 'score' => 55, 'pattern' => $pat, 'reason' => 'fallback', 'algorithm' => 'third'];
}

if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(thirdTimeAnalyse($input['trends'] ?? [], (int)($input['level'] ?? 1)));
}
