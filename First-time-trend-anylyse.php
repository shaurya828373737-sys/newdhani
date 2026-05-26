<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * First-time-trend-anylyse.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * ALGORITHM #1: Advanced Streak Detection & Reversal Analysis
 * 
 * This algorithm analyzes:
 * - Current trailing streak (how many consecutive same results)
 * - Historical streak patterns (when streaks broke in the past)
 * - Reversal probability based on streak length
 * - Statistical dominance (which side has more occurrences)
 * 
 * Key Insight: In random binary sequences, long streaks are rare.
 * After 3+ consecutive same results, probability of reversal increases.
 * 
 * Levels:
 *   L1 → Basic streak reversal (3+ streak = bet opposite)
 *   L2 → Historical reversal analysis (which side breaks streaks more)
 *   L3 → MASTER: Last-5 momentum with streak cross-validation
 * 
 * © DHANI WIN 2025
 */

/**
 * First-time analysis: Streak detection algorithm
 *
 * @param array $trends  Array of trend objects with 'type' (BIG|Small)
 * @param int   $level   1=normal, 2=deeper, 3=master
 * @return array         ['prediction', 'score', 'reason', 'algorithm', 'streak', ...]
 */
function firstTimeAnalyse(array $trends, int $level = 1): array {

    // ─────────────────────────────────────────────────────────────────
    // EXTRACT TREND TYPES
    // ─────────────────────────────────────────────────────────────────
    $types = array_column($trends, 'type');
    $n     = count($types);

    // Minimum data check
    if ($n < 2) {
        return [
            'prediction' => 'BIG',
            'score'      => 50,
            'algorithm'  => 'first',
            'reason'     => 'Insufficient data (need 2+ trends)',
            'streak'     => 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // CALCULATE BASIC STATISTICS
    // ─────────────────────────────────────────────────────────────────
    $last      = $types[$n - 1];                                    // Most recent result
    $opposite  = ($last === 'BIG') ? 'Small' : 'BIG';               // Opposite of last
    
    $bigCount   = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;
    
    // ─────────────────────────────────────────────────────────────────
    // COUNT TRAILING STREAK (consecutive same at end)
    // ─────────────────────────────────────────────────────────────────
    $streak = 0;
    for ($i = $n - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) {
            $streak++;
        } else {
            break;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // CALCULATE LONGEST STREAK IN HISTORY (for context)
    // ─────────────────────────────────────────────────────────────────
    $maxStreakBig = 0;
    $maxStreakSmall = 0;
    $currentStreakBig = 0;
    $currentStreakSmall = 0;
    
    foreach ($types as $t) {
        if ($t === 'BIG') {
            $currentStreakBig++;
            $currentStreakSmall = 0;
            $maxStreakBig = max($maxStreakBig, $currentStreakBig);
        } else {
            $currentStreakSmall++;
            $currentStreakBig = 0;
            $maxStreakSmall = max($maxStreakSmall, $currentStreakSmall);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 1: Basic Streak Reversal Logic
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 1) {
        
        // RULE 1: Long streak (4+) → STRONG reversal signal
        if ($streak >= 4) {
            $score = min(70 + ($streak * 4), 85);
            return [
                'prediction' => $opposite,
                'score'      => $score,
                'streak'     => $streak,
                'reason'     => "L1: Strong streak {$streak}x {$last} → reversal to {$opposite}",
                'algorithm'  => 'first',
                'confidence' => 'HIGH',
            ];
        }
        
        // RULE 2: Medium streak (3) → Good reversal signal
        if ($streak === 3) {
            $score = 72;
            return [
                'prediction' => $opposite,
                'score'      => $score,
                'streak'     => $streak,
                'reason'     => "L1: Streak 3x {$last} → reversal to {$opposite}",
                'algorithm'  => 'first',
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        // RULE 3: Short streak (2) → Weak reversal, check majority
        if ($streak === 2) {
            $majorityFavors = ($bigCount > $smallCount) ? 'BIG' : 'Small';
            
            // If majority agrees with opposite → stronger signal
            if ($majorityFavors === $opposite) {
                $score = 66;
                $reason = "L1: Streak 2x {$last} + majority favors {$opposite}";
            } else {
                $score = 58;
                $reason = "L1: Streak 2x {$last} → weak reversal to {$opposite}";
            }
            
            return [
                'prediction' => $opposite,
                'score'      => $score,
                'streak'     => $streak,
                'reason'     => $reason,
                'algorithm'  => 'first',
                'confidence' => 'MEDIUM',
            ];
        }
        
        // RULE 4: No streak → Follow overall majority
        if ($bigCount === $smallCount) {
            // Perfect tie: reverse last (slight edge)
            return [
                'prediction' => $opposite,
                'score'      => 52,
                'streak'     => $streak,
                'reason'     => "L1: Tie {$bigCount}/{$smallCount} → reverse last {$last}",
                'algorithm'  => 'first',
                'confidence' => 'LOW',
            ];
        }
        
        // Follow majority
        $pred  = ($bigCount > $smallCount) ? 'BIG' : 'Small';
        $diff  = abs($bigCount - $smallCount);
        $score = min(50 + ($diff * 5), 75);
        
        return [
            'prediction' => $pred,
            'score'      => $score,
            'streak'     => $streak,
            'reason'     => "L1: Majority {$pred} ({$bigCount}B/{$smallCount}S)",
            'algorithm'  => 'first',
            'confidence' => $diff >= 3 ? 'MEDIUM' : 'LOW',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 2: Historical Reversal Analysis
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 2) {
        
        // Count how many times each side BROKE a streak of 2+
        $reversals = ['BIG' => 0, 'Small' => 0];
        $streakBreaks = ['BIG' => [], 'Small' => []]; // Track lengths of broken streaks
        
        $prev = $types[0];
        $run  = 1;
        
        for ($i = 1; $i < $n; $i++) {
            if ($types[$i] === $prev) {
                $run++;
            } else {
                // Streak broke
                if ($run >= 2) {
                    $reversals[$types[$i]]++;
                    $streakBreaks[$types[$i]][] = $run;
                }
                $prev = $types[$i];
                $run  = 1;
            }
        }
        
        // Side that broke more streaks = more likely to break again
        $reversalLeader = ($reversals['Small'] >= $reversals['BIG']) ? 'Small' : 'BIG';
        $reversalDiff   = abs($reversals['BIG'] - $reversals['Small']);
        
        // Current streak prediction
        $streakPred = ($streak >= 2) ? $opposite : (($bigCount >= $smallCount) ? 'BIG' : 'Small');
        $streakScore = ($streak >= 4) ? 80 : (($streak >= 3) ? 72 : (($streak >= 2) ? 65 : 55));
        
        // If reversal history agrees with streak prediction → boost
        if ($reversalLeader === $streakPred) {
            $finalScore = min($streakScore + 8 + ($reversalDiff * 2), 86);
            return [
                'prediction' => $streakPred,
                'score'      => $finalScore,
                'streak'     => $streak,
                'reason'     => "L2: Streak + reversal history both favor {$streakPred} (reversals: B{$reversals['BIG']}/S{$reversals['Small']})",
                'algorithm'  => 'first',
                'reversals'  => $reversals,
                'confidence' => 'HIGH',
            ];
        }
        
        // Disagreement: trust reversal history if it has clear leader
        if ($reversalDiff >= 2) {
            return [
                'prediction' => $reversalLeader,
                'score'      => 64 + ($reversalDiff * 2),
                'streak'     => $streak,
                'reason'     => "L2: Reversal history favors {$reversalLeader} (B{$reversals['BIG']}/S{$reversals['Small']})",
                'algorithm'  => 'first',
                'reversals'  => $reversals,
                'confidence' => 'MEDIUM',
            ];
        }
        
        // Default to streak prediction
        return [
            'prediction' => $streakPred,
            'score'      => $streakScore,
            'streak'     => $streak,
            'reason'     => "L2: Mixed signals → trust streak analysis",
            'algorithm'  => 'first',
            'reversals'  => $reversals,
            'confidence' => 'MEDIUM',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 3: MASTER — Last-5 Momentum with Cross-Validation
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 3) {
        
        // Analyze last 5 for momentum
        $last5     = array_slice($types, -5);
        $last5Big  = count(array_filter($last5, fn($t) => $t === 'BIG'));
        $last5Small = 5 - $last5Big;
        
        // Analyze last 3 for immediate pattern
        $last3     = array_slice($types, -3);
        $last3Big  = count(array_filter($last3, fn($t) => $t === 'BIG'));
        $last3Small = 3 - $last3Big;
        
        // Check if last 3 are all same (strong reversal signal)
        $last3AllSame = count(array_unique($last3)) === 1;
        
        // MASTER RULE 1: Last 3 all same → DEFINITE reversal
        if ($last3AllSame) {
            $reversalTarget = ($last3[0] === 'BIG') ? 'Small' : 'BIG';
            return [
                'prediction' => $reversalTarget,
                'score'      => 85,
                'streak'     => $streak,
                'reason'     => "L3 MASTER: Last 3 all {$last3[0]} → DEFINITE reversal to {$reversalTarget}",
                'algorithm'  => 'first',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'VERY HIGH',
            ];
        }
        
        // MASTER RULE 2: Strong last-5 dominance (4-1 or 5-0)
        if ($last5Big >= 4) {
            // Heavy BIG in last 5 → momentum continues OR reversal imminent
            // If streak is also high → reversal
            if ($streak >= 3) {
                return [
                    'prediction' => 'Small',
                    'score'      => 82,
                    'streak'     => $streak,
                    'reason'     => "L3 MASTER: Last5 {$last5Big}B + streak {$streak} → reversal to Small",
                    'algorithm'  => 'first',
                    'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                    'confidence' => 'HIGH',
                ];
            }
            // Continue momentum
            return [
                'prediction' => 'BIG',
                'score'      => 75,
                'streak'     => $streak,
                'reason'     => "L3 MASTER: Last5 {$last5Big}B → momentum BIG",
                'algorithm'  => 'first',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        if ($last5Small >= 4) {
            if ($streak >= 3) {
                return [
                    'prediction' => 'BIG',
                    'score'      => 82,
                    'streak'     => $streak,
                    'reason'     => "L3 MASTER: Last5 {$last5Small}S + streak {$streak} → reversal to BIG",
                    'algorithm'  => 'first',
                    'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                    'confidence' => 'HIGH',
                ];
            }
            return [
                'prediction' => 'Small',
                'score'      => 75,
                'streak'     => $streak,
                'reason'     => "L3 MASTER: Last5 {$last5Small}S → momentum Small",
                'algorithm'  => 'first',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        // MASTER RULE 3: Balanced last-5 (3-2 or 2-3) → follow streak or recent
        if ($streak >= 2) {
            return [
                'prediction' => $opposite,
                'score'      => 70,
                'streak'     => $streak,
                'reason'     => "L3 MASTER: Balanced last5 + streak {$streak} → {$opposite}",
                'algorithm'  => 'first',
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'MEDIUM',
            ];
        }
        
        // Follow last-5 majority
        $masterPred = ($last5Big > $last5Small) ? 'BIG' : 'Small';
        return [
            'prediction' => $masterPred,
            'score'      => 68,
            'streak'     => $streak,
            'reason'     => "L3 MASTER: Last5 {$last5Big}B/{$last5Small}S → {$masterPred}",
            'algorithm'  => 'first',
            'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
            'confidence' => 'MEDIUM',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // FALLBACK (should not reach here)
    // ─────────────────────────────────────────────────────────────────
    return [
        'prediction' => $opposite,
        'score'      => 55,
        'streak'     => $streak,
        'reason'     => 'Fallback: reverse last',
        'algorithm'  => 'first',
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
        echo json_encode(['error' => 'No trends provided', 'algorithm' => 'first']);
        exit;
    }
    
    echo json_encode(firstTimeAnalyse($trends, $level), JSON_PRETTY_PRINT);
}
