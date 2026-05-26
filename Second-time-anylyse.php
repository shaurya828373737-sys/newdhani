<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Second-time-anylyse.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * ALGORITHM #2: Recency-Weighted Score & Momentum Analysis
 * 
 * This algorithm analyzes:
 * - Position-weighted frequency (recent trends have higher weight)
 * - Momentum: comparing first half vs second half of trends
 * - Velocity: rate of change in recent results
 * - Exponential decay weighting for recency
 * 
 * Key Insight: Recent results are more predictive than older ones.
 * A shift in momentum (e.g., was BIG-heavy, now Small-heavy) signals change.
 * 
 * Levels:
 *   L1 → Standard linear recency weighting
 *   L2 → Split-window comparison (first5 vs last5) for momentum shift
 *   L3 → MASTER: Pure last-3 analysis with momentum lock
 * 
 * © DHANI WIN 2025
 */

/**
 * Second-time analysis: Recency-weighted momentum algorithm
 *
 * @param array $trends  Array of trend objects with 'type' (BIG|Small)
 * @param int   $level   1=normal, 2=deeper, 3=master
 * @return array         ['prediction', 'score', 'reason', 'algorithm', ...]
 */
function secondTimeAnalyse(array $trends, int $level = 1): array {

    // ─────────────────────────────────────────────────────────────────
    // EXTRACT TREND TYPES
    // ─────────────────────────────────────────────────────────────────
    $types = array_column($trends, 'type');
    $n     = count($types);

    // Minimum data check
    if ($n < 1) {
        return [
            'prediction' => 'BIG',
            'score'      => 50,
            'algorithm'  => 'second',
            'reason'     => 'No data provided',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 1: Standard Linear Recency Weighting
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 1) {
        
        // Weight formula: position (1-indexed) → older=1, newer=10
        // This gives exponentially more importance to recent results
        $weightBig   = 0.0;
        $weightSmall = 0.0;
        $totalWeight = 0.0;
        
        for ($i = 0; $i < $n; $i++) {
            $weight = $i + 1;  // Position weight: 1, 2, 3, ... 10
            $totalWeight += $weight;
            
            if ($types[$i] === 'BIG') {
                $weightBig += $weight;
            } else {
                $weightSmall += $weight;
            }
        }
        
        // Calculate dominance ratio
        $dominance = $totalWeight > 0 ? max($weightBig, $weightSmall) / $totalWeight : 0.5;
        
        // Prediction based on weighted majority
        $pred = ($weightBig >= $weightSmall) ? 'BIG' : 'Small';
        
        // Score calculation: 50 (neutral) + bonus for dominance
        // Dominance ranges from 0.5 (even) to ~0.73 (heavy one side)
        $score = (int)round(50 + ($dominance - 0.5) * 80);
        $score = min(max($score, 50), 82);
        
        // Additional check: if weights are very close, reduce confidence
        $weightDiff = abs($weightBig - $weightSmall);
        if ($weightDiff < 5) {
            $score = max($score - 5, 50);
        }
        
        return [
            'prediction' => $pred,
            'score'      => $score,
            'reason'     => "L1: Weighted BIG={$weightBig} Small={$weightSmall} → {$pred}",
            'algorithm'  => 'second',
            'weights'    => ['BIG' => round($weightBig, 2), 'Small' => round($weightSmall, 2)],
            'dominance'  => round($dominance, 4),
            'confidence' => $dominance > 0.6 ? 'MEDIUM' : 'LOW',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 2: Split-Window Momentum Analysis
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 2) {
        
        // Split into first half and second half (or first5/last5 for 10 trends)
        $midpoint = (int)floor($n / 2);
        $firstHalf = array_slice($types, 0, $midpoint);
        $lastHalf  = array_slice($types, $midpoint);
        
        // For exactly 10 trends, use 5/5 split
        if ($n === 10) {
            $firstHalf = array_slice($types, 0, 5);
            $lastHalf  = array_slice($types, 5, 5);
        }
        
        // Count BIG in each half
        $firstBig = count(array_filter($firstHalf, fn($t) => $t === 'BIG'));
        $lastBig  = count(array_filter($lastHalf, fn($t) => $t === 'BIG'));
        
        $firstSmall = count($firstHalf) - $firstBig;
        $lastSmall  = count($lastHalf) - $lastBig;
        
        // Calculate momentum shift
        // Positive shift = moving toward BIG, Negative = moving toward Small
        $shift = $lastBig - $firstBig;
        
        // Also check last 3 for immediate direction
        $last3 = array_slice($types, -3);
        $last3Big = count(array_filter($last3, fn($t) => $t === 'BIG'));
        $last3Small = 3 - $last3Big;
        
        // RULE 1: Strong momentum shift (3+ difference)
        if (abs($shift) >= 3) {
            $pred  = ($shift > 0) ? 'BIG' : 'Small';
            $score = 72 + abs($shift) * 3;
            return [
                'prediction' => $pred,
                'score'      => min($score, 85),
                'reason'     => "L2: Strong momentum shift={$shift} → {$pred}",
                'algorithm'  => 'second',
                'shift'      => $shift,
                'first_half' => ['BIG' => $firstBig, 'Small' => $firstSmall],
                'last_half'  => ['BIG' => $lastBig, 'Small' => $lastSmall],
                'confidence' => 'HIGH',
            ];
        }
        
        // RULE 2: Medium momentum shift (2 difference)
        if (abs($shift) === 2) {
            $pred  = ($shift > 0) ? 'BIG' : 'Small';
            $score = 68;
            
            // If last3 agrees, boost
            $last3Pred = ($last3Big > $last3Small) ? 'BIG' : 'Small';
            if ($last3Pred === $pred) {
                $score += 5;
            }
            
            return [
                'prediction' => $pred,
                'score'      => $score,
                'reason'     => "L2: Medium shift={$shift} → {$pred}",
                'algorithm'  => 'second',
                'shift'      => $shift,
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        // RULE 3: Weak/no shift → analyze last half dominance
        $pred   = ($lastBig >= $lastSmall) ? 'BIG' : 'Small';
        $lastDiff = abs($lastBig - $lastSmall);
        $score  = 55 + $lastDiff * 4;
        
        // Check for alternating pattern in last half
        $alternating = true;
        for ($i = 1; $i < count($lastHalf); $i++) {
            if ($lastHalf[$i] === $lastHalf[$i - 1]) {
                $alternating = false;
                break;
            }
        }
        
        if ($alternating && count($lastHalf) >= 3) {
            // Alternating pattern → predict continuation of alternation
            $lastType = end($lastHalf);
            $pred = ($lastType === 'BIG') ? 'Small' : 'BIG';
            $score = 65;
            return [
                'prediction' => $pred,
                'score'      => $score,
                'reason'     => "L2: Alternating pattern detected → next is {$pred}",
                'algorithm'  => 'second',
                'confidence' => 'MEDIUM',
            ];
        }
        
        return [
            'prediction' => $pred,
            'score'      => min($score, 75),
            'reason'     => "L2: Last half {$lastBig}B/{$lastSmall}S → {$pred}",
            'algorithm'  => 'second',
            'shift'      => $shift,
            'confidence' => $lastDiff >= 2 ? 'MEDIUM' : 'LOW',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEVEL 3: MASTER — Pure Last-3 Momentum Lock
    // ═══════════════════════════════════════════════════════════════════
    if ($level === 3) {
        
        // Focus entirely on last 3 results for maximum recency
        $last3 = array_slice($types, -3);
        $last3Big = count(array_filter($last3, fn($t) => $t === 'BIG'));
        $last3Small = 3 - $last3Big;
        
        // Get last 5 for cross-validation
        $last5 = array_slice($types, -5);
        $last5Big = count(array_filter($last5, fn($t) => $t === 'BIG'));
        $last5Small = 5 - $last5Big;
        
        // Check if last 3 are all same
        $allSame = count(array_unique($last3)) === 1;
        
        // MASTER RULE 1: All 3 same → STRONG REVERSAL
        if ($allSame) {
            $reverseTo = ($last3[0] === 'BIG') ? 'Small' : 'BIG';
            return [
                'prediction' => $reverseTo,
                'score'      => 86,
                'reason'     => "L3 MASTER: Last 3 all {$last3[0]} → STRONG reversal to {$reverseTo}",
                'algorithm'  => 'second',
                'last3'      => $last3,
                'last5_stats'=> ['BIG' => $last5Big, 'Small' => $last5Small],
                'confidence' => 'VERY HIGH',
            ];
        }
        
        // MASTER RULE 2: 2 out of 3 same → follow that side with check
        if ($last3Big === 2 || $last3Small === 2) {
            $dominant = ($last3Big === 2) ? 'BIG' : 'Small';
            $lastEntry = $types[$n - 1];
            
            // If last entry matches dominant → might continue OR reverse
            // Check last 5 for validation
            if ($lastEntry === $dominant) {
                // Last is same as dominant 2/3 → check if streak is building
                $streak = 0;
                for ($i = $n - 1; $i >= 0 && $types[$i] === $lastEntry; $i--) {
                    $streak++;
                }
                
                if ($streak >= 2) {
                    // Streak building → might reverse soon
                    $pred = ($dominant === 'BIG') ? 'Small' : 'BIG';
                    $score = 72;
                } else {
                    // Continue momentum
                    $pred = $dominant;
                    $score = 70;
                }
            } else {
                // Last entry is minority → pattern might be shifting
                $pred = $lastEntry;  // Follow the new direction
                $score = 68;
            }
            
            return [
                'prediction' => $pred,
                'score'      => $score,
                'reason'     => "L3 MASTER: Last3 has 2x {$dominant}, last={$lastEntry} → {$pred}",
                'algorithm'  => 'second',
                'last3'      => $last3,
                'confidence' => 'MEDIUM-HIGH',
            ];
        }
        
        // MASTER RULE 3: Mixed (should rarely happen with 3 items)
        // Follow the most recent entry
        $lastEntry = $types[$n - 1];
        return [
            'prediction' => $lastEntry,
            'score'      => 65,
            'reason'     => "L3 MASTER: Mixed last3 → follow most recent {$lastEntry}",
            'algorithm'  => 'second',
            'last3'      => $last3,
            'confidence' => 'MEDIUM',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // FALLBACK
    // ─────────────────────────────────────────────────────────────────
    return [
        'prediction' => 'BIG',
        'score'      => 55,
        'reason'     => 'Fallback prediction',
        'algorithm'  => 'second',
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
        echo json_encode(['error' => 'No trends provided', 'algorithm' => 'second']);
        exit;
    }
    
    echo json_encode(secondTimeAnalyse($trends, $level), JSON_PRETTY_PRINT);
}
