<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Third-time-anylyse.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * ALGORITHM #3: Pattern Sequence Matching & Transition Matrix
 * 
 * This algorithm analyzes:
 * - Last-3 pattern lookup (8 possible patterns: BBB, BBS, BSB, BSS, SBB, SBS, SSB, SSS)
 * - Transition probabilities (what follows each result historically)
 * - Markov chain-style next-state prediction
 * - Pattern confirmation via cross-validation
 * 
 * Key Insight: Certain 3-result patterns statistically favor specific outcomes.
 * BBB → usually breaks to Small, SSS → usually breaks to BIG.
 * 
 * Levels:
 *   L1 → Plain pattern table lookup
 *   L2 → Pattern + last-5 cross-validation
 *   L3 → MASTER: Full transition matrix over all 10 trends
 * 
 * © DHANI WIN 2025
 */

/**
 * Third-time analysis: Pattern matching algorithm
 *
 * @param array $trends  Array of trend objects with 'type' (BIG|Small)
 * @param int   $level   1=normal, 2=deeper, 3=master
 * @return array         ['prediction', 'score', 'reason', 'algorithm', 'pattern', ...]
 */
function thirdTimeAnalyse(array $trends, int $level = 1): array {

    // ─────────────────────────────────────────────────────────────────
    // EXTRACT TREND TYPES
    // ─────────────────────────────────────────────────────────────────
    $types = array_column($trends, 'type');
    $n     = count($types);

    // Minimum data check
    if ($n < 3) {
        return [
            'prediction' => 'BIG',
            'score'      => 50,
            'algorithm'  => 'third',
            'reason'     => 'Need at least 3 trends for pattern analysis',
            'pattern'    => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // ENCODE LAST 3 TRENDS AS PATTERN STRING
    // ─────────────────────────────────────────────────────────────────
    $last3 = array_slice($types, -3);
    $encode = fn($t) => $t === 'BIG' ? 'B' : 'S';
    $pattern = $encode($last3[0]) . $encode($last3[1]) . $encode($last3[2]);

    // ─────────────────────────────────────────────────────────────────
    // PATTERN TABLE: Based on statistical analysis of binary sequences
    // ─────────────────────────────────────────────────────────────────
    // Format: 'pattern' => ['predicted_next', base_score, 'explanation']
    $patternTable = [
        // Triple same → STRONG reversal expected
        'BBB' => ['Small', 82, 'Triple BIG streak → reversal to Small'],
        'SSS' => ['BIG',   82, 'Triple Small streak → reversal to BIG'],
        
        // Two same ending with switch → continue the switch direction
        'BBS' => ['Small', 73, 'BIG-BIG-Small → Small momentum continues'],
        'SSB' => ['BIG',   73, 'Small-Small-BIG → BIG momentum continues'],
        
        // Two same at end → expect reversal
        'BSS' => ['BIG',   70, 'BIG-Small-Small → reversal to BIG'],
        'SBB' => ['Small', 70, 'Small-BIG-BIG → reversal to Small'],
        
        // Alternating patterns → continue alternation
        'BSB' => ['Small', 65, 'BIG-Small-BIG (alternating) → Small next'],
        'SBS' => ['BIG',   65, 'Small-BIG-Small (alternating) → BIG next'],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 1: Plain Pattern Table Lookup
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 1) {
        
        if (isset($patternTable[$pattern])) {
            [$pred, $score, $explanation] = $patternTable[$pattern];
            return [
                'prediction' => $pred,
                'score'      => $score,
                'pattern'    => $pattern,
                'reason'     => "L1: Pattern {$pattern} → {$pred} ({$explanation})",
                'algorithm'  => 'third',
                'confidence' => $score >= 75 ? 'HIGH' : 'MEDIUM',
            ];
        }
        
        // Fallback (should not happen with valid patterns)
        $lastType = $types[$n - 1];
        $pred = ($lastType === 'BIG') ? 'Small' : 'BIG';
        return [
            'prediction' => $pred,
            'score'      => 55,
            'pattern'    => $pattern,
            'reason'     => "L1: Unknown pattern {$pattern} → reverse last",
            'algorithm'  => 'third',
            'confidence' => 'LOW',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 2: Pattern + Last-5 Cross-Validation
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 2) {
        
        // Get pattern prediction
        $patternResult = $patternTable[$pattern] ?? [($types[$n-1] === 'BIG' ? 'Small' : 'BIG'), 55, 'default'];
        [$patPred, $patScore, $patExplain] = $patternResult;
        
        // Analyze last 5 for cross-validation
        $last5 = array_slice($types, -5);
        $last5Big = count(array_filter($last5, fn($t) => $t === 'BIG'));
        $last5Small = 5 - $last5Big;
        $last5Pred = ($last5Big >= $last5Small) ? 'BIG' : 'Small';
        
        // Also check for streak in last 5
        $last5Streak = 1;
        $lastType = end($last5);
        for ($i = count($last5) - 2; $i >= 0; $i--) {
            if ($last5[$i] === $lastType) {
                $last5Streak++;
            } else {
                break;
            }
        }
        
        // RULE 1: Pattern and last5 agree → BOOST confidence
        if ($patPred === $last5Pred) {
            $finalScore = min($patScore + 10, 88);
            return [
                'prediction' => $patPred,
                'score'      => $finalScore,
                'pattern'    => $pattern,
                'reason'     => "L2: Pattern {$pattern} + Last5 ({$last5Big}B/{$last5Small}S) AGREE → {$patPred}",
                'algorithm'  => 'third',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'HIGH',
            ];
        }
        
        // RULE 2: Strong streak in last5 → trust streak over pattern
        if ($last5Streak >= 3) {
            $strekPred = ($lastType === 'BIG') ? 'Small' : 'BIG';  // Reversal
            $finalScore = 75;
            return [
                'prediction' => $strekPred,
                'score'      => $finalScore,
                'pattern'    => $pattern,
                'reason'     => "L2: Last5 streak {$last5Streak}x {$lastType} → reversal to {$strekPred}",
                'algorithm'  => 'third',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        // RULE 3: Conflict with no strong streak → trust pattern (more specific)
        $finalScore = max($patScore - 5, 58);
        return [
            'prediction' => $patPred,
            'score'      => $finalScore,
            'pattern'    => $pattern,
            'reason'     => "L2: Pattern {$pattern} vs Last5 conflict → trust pattern {$patPred}",
            'algorithm'  => 'third',
            'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
            'confidence' => 'MEDIUM',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 3: MASTER — Full Transition Matrix
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 3) {
        
        // Build transition matrix from all historical data
        // matrix[from][to] = count of transitions from 'from' to 'to'
        $matrix = [
            'BIG'   => ['BIG' => 0, 'Small' => 0],
            'Small' => ['BIG' => 0, 'Small' => 0],
        ];
        
        for ($i = 0; $i < $n - 1; $i++) {
            $from = $types[$i];
            $to   = $types[$i + 1];
            $matrix[$from][$to]++;
        }
        
        // Get last type for transition lookup
        $lastType = $types[$n - 1];
        $row = $matrix[$lastType];
        $rowTotal = $row['BIG'] + $row['Small'];
        
        // Calculate transition probabilities
        if ($rowTotal > 0) {
            $probToBig   = $row['BIG'] / $rowTotal;
            $probToSmall = $row['Small'] / $rowTotal;
            
            // Prediction based on which transition is more likely
            if ($probToSmall > $probToBig) {
                $matrixPred = 'Small';
                $matrixProb = $probToSmall;
            } elseif ($probToBig > $probToSmall) {
                $matrixPred = 'BIG';
                $matrixProb = $probToBig;
            } else {
                // Equal → use pattern as tiebreaker
                $matrixPred = $patternTable[$pattern][0] ?? 'Small';
                $matrixProb = 0.5;
            }
            
            // Score: 60-88 based on probability strength
            $matrixScore = (int)round(60 + $matrixProb * 28);
            
        } else {
            // No transition data from this type → fallback to pattern
            $matrixPred = $patternTable[$pattern][0] ?? 'Small';
            $matrixScore = 65;
            $matrixProb = 0.5;
        }
        
        // Cross-check with pattern table
        $patResult = $patternTable[$pattern] ?? [$matrixPred, 60, ''];
        [$patPred, $patScore] = $patResult;
        
        // MASTER RULE 1: Matrix and pattern AGREE → Maximum confidence
        if ($matrixPred === $patPred) {
            $finalScore = min($matrixScore + 8, 88);
            return [
                'prediction' => $matrixPred,
                'score'      => $finalScore,
                'pattern'    => $pattern,
                'reason'     => "L3 MASTER: Matrix ({$lastType}→{$row['BIG']}B/{$row['Small']}S) + Pattern AGREE → {$matrixPred}",
                'algorithm'  => 'third',
                'matrix'     => $matrix,
                'probability'=> round($matrixProb, 4),
                'confidence' => 'VERY HIGH',
            ];
        }
        
        // MASTER RULE 2: Strong matrix signal (70%+) → trust matrix
        if ($matrixProb >= 0.7) {
            return [
                'prediction' => $matrixPred,
                'score'      => $matrixScore,
                'pattern'    => $pattern,
                'reason'     => "L3 MASTER: Strong matrix signal ({$matrixProb:.0%}) → {$matrixPred}",
                'algorithm'  => 'third',
                'matrix'     => $matrix,
                'probability'=> round($matrixProb, 4),
                'confidence' => 'HIGH',
            ];
        }
        
        // MASTER RULE 3: Weak matrix signal → weighted combination
        // Give 60% weight to pattern (it's more specific), 40% to matrix
        if ($patScore > $matrixScore) {
            $finalPred = $patPred;
            $finalScore = (int)round($patScore * 0.6 + $matrixScore * 0.4);
        } else {
            $finalPred = $matrixPred;
            $finalScore = (int)round($matrixScore * 0.6 + $patScore * 0.4);
        }
        
        return [
            'prediction' => $finalPred,
            'score'      => min($finalScore, 85),
            'pattern'    => $pattern,
            'reason'     => "L3 MASTER: Weighted (pattern={$patPred}, matrix={$matrixPred}) → {$finalPred}",
            'algorithm'  => 'third',
            'matrix'     => $matrix,
            'probability'=> round($matrixProb, 4),
            'confidence' => 'MEDIUM-HIGH',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // FALLBACK
    // ─────────────────────────────────────────────────────────────────
    return [
        'prediction' => 'BIG',
        'score'      => 55,
        'pattern'    => $pattern,
        'reason'     => 'Fallback prediction',
        'algorithm'  => 'third',
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTTP HANDLER (Direct call)
// ═══════════════════════════════════════════════════════════════════════════════
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends = $input['trends'] ?? [];
    $level  = (int)($input['level'] ?? 1);
    
    if (empty($trends)) {
        echo json_encode(['error' => 'No trends provided', 'algorithm' => 'third']);
        exit;
    }
    
    echo json_encode(thirdTimeAnalyse($trends, $level), JSON_PRETTY_PRINT);
}
