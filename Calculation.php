<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Calculation.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * TRIPLE ALGORITHM AGGREGATOR & CONSENSUS VOTING
 * 
 * This is the core calculation engine that:
 * 1. Runs all three algorithms independently
 * 2. Collects their predictions and confidence scores
 * 3. Applies consensus voting (3/3, 2/3, or highest-score)
 * 4. Handles level-specific logic (BET 1, BET 2, BET 3 MASTER)
 * 5. Applies Master Override for Level 3
 * 
 * Voting System:
 * - 3/3 agree: Highest confidence (average scores + bonus)
 * - 2/3 agree: Good confidence (average of agreeing scores)
 * - Split: Trust highest-scoring algorithm
 * 
 * Level 3 Master Override:
 * - If previous 2 predictions lost on SAME side → bet OPPOSITE
 * - This catches "stuck" patterns where the game favors one side
 * 
 * © DHANI WIN 2025
 */

require_once __DIR__ . '/First-time-trend-anylyse.php';
require_once __DIR__ . '/Second-time-anylyse.php';
require_once __DIR__ . '/Third-time-anylyse.php';

/**
 * Run the complete triple-algorithm calculation
 *
 * @param array $trends             Array of trend objects with 'type' (BIG|Small)
 * @param int   $level              1=BET 1, 2=BET 2, 3=BET 3 MASTER
 * @param array $prevWrongPredictions Previous wrong predictions for L3 override
 * @return array                    Complete prediction result
 */
function runCalculation(array $trends, int $level = 1, array $prevWrongPredictions = []): array {
    
    // ─────────────────────────────────────────────────────────────────
    // INPUT VALIDATION
    // ─────────────────────────────────────────────────────────────────
    if (empty($trends)) {
        return createErrorResult('No trends provided', $level);
    }
    
    $types = array_column($trends, 'type');
    $n = count($types);
    
    if ($n < 3) {
        return createErrorResult('Need at least 3 trends for analysis', $level);
    }
    
    // ─────────────────────────────────────────────────────────────────
    // RUN ALL THREE ALGORITHMS
    // ─────────────────────────────────────────────────────────────────
    $result1 = firstTimeAnalyse($trends, $level);
    $result2 = secondTimeAnalyse($trends, $level);
    $result3 = thirdTimeAnalyse($trends, $level);
    
    // Extract predictions and scores
    $pred1 = $result1['prediction'] ?? 'BIG';
    $score1 = $result1['score'] ?? 50;
    
    $pred2 = $result2['prediction'] ?? 'BIG';
    $score2 = $result2['score'] ?? 50;
    
    $pred3 = $result3['prediction'] ?? 'BIG';
    $score3 = $result3['score'] ?? 50;
    
    // ─────────────────────────────────────────────────────────────────
    // CONSENSUS VOTING
    // ─────────────────────────────────────────────────────────────────
    $votes = ['BIG' => 0, 'Small' => 0];
    $votes[$pred1]++;
    $votes[$pred2]++;
    $votes[$pred3]++;
    
    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);
    
    // ─────────────────────────────────────────────────────────────────
    // DETERMINE FINAL PREDICTION
    // ─────────────────────────────────────────────────────────────────
    
    // CASE 1: All 3 algorithms agree → Highest confidence
    if ($votes['BIG'] === 3) {
        $finalPrediction = 'BIG';
        $avgScore = (int)round(($score1 + $score2 + $score3) / 3);
        $confidence = min(max($avgScore + 5, 65), 88);  // +5 bonus for agreement
        $note = "3/3 algorithms AGREE → BIG (Level {$level})";
        $voteResult = 'unanimous';
        
    } elseif ($votes['Small'] === 3) {
        $finalPrediction = 'Small';
        $avgScore = (int)round(($score1 + $score2 + $score3) / 3);
        $confidence = min(max($avgScore + 5, 65), 88);
        $note = "3/3 algorithms AGREE → Small (Level {$level})";
        $voteResult = 'unanimous';
        
    // CASE 2: 2/3 algorithms agree → Good confidence
    } elseif ($votes['BIG'] === 2) {
        $finalPrediction = 'BIG';
        $agreeScores = [];
        if ($pred1 === 'BIG') $agreeScores[] = $score1;
        if ($pred2 === 'BIG') $agreeScores[] = $score2;
        if ($pred3 === 'BIG') $agreeScores[] = $score3;
        $avgScore = (int)round(array_sum($agreeScores) / count($agreeScores));
        $confidence = min(max($avgScore - 3, 55), 82);  // -3 penalty for disagreement
        $note = "2/3 algorithms favor BIG (Level {$level})";
        $voteResult = 'majority_big';
        
    } elseif ($votes['Small'] === 2) {
        $finalPrediction = 'Small';
        $agreeScores = [];
        if ($pred1 === 'Small') $agreeScores[] = $score1;
        if ($pred2 === 'Small') $agreeScores[] = $score2;
        if ($pred3 === 'Small') $agreeScores[] = $score3;
        $avgScore = (int)round(array_sum($agreeScores) / count($agreeScores));
        $confidence = min(max($avgScore - 3, 55), 82);
        $note = "2/3 algorithms favor Small (Level {$level})";
        $voteResult = 'majority_small';
        
    // CASE 3: All different (shouldn't happen with binary choice, but handle it)
    } else {
        // Trust highest score
        $scores = [
            'algo1' => ['pred' => $pred1, 'score' => $score1],
            'algo2' => ['pred' => $pred2, 'score' => $score2],
            'algo3' => ['pred' => $pred3, 'score' => $score3],
        ];
        
        $maxScore = max($score1, $score2, $score3);
        foreach ($scores as $algo => $data) {
            if ($data['score'] === $maxScore) {
                $finalPrediction = $data['pred'];
                break;
            }
        }
        
        $confidence = min(max($maxScore - 8, 50), 75);  // Lower confidence for split
        $note = "Split vote — trusting highest score algorithm (Level {$level})";
        $voteResult = 'split';
    }
    
    // ─────────────────────────────────────────────────────────────────
    // LEVEL 3: MASTER OVERRIDE LOGIC
    // ─────────────────────────────────────────────────────────────────
    // If we're at Level 3 and the previous TWO predictions were both
    // wrong on the SAME side, the game is clearly favoring the opposite.
    // Override the prediction to bet the opposite of the lost bets.
    // ─────────────────────────────────────────────────────────────────
    $masterOverrideApplied = false;
    $masterOverrideNote = '';
    
    if ($level === 3 && count($prevWrongPredictions) >= 2) {
        $wrong1 = $prevWrongPredictions[count($prevWrongPredictions) - 2];
        $wrong2 = $prevWrongPredictions[count($prevWrongPredictions) - 1];
        
        // Both previous bets lost on the SAME side
        if ($wrong1 === $wrong2) {
            $masterOverride = ($wrong1 === 'BIG') ? 'Small' : 'BIG';
            
            if ($masterOverride === $finalPrediction) {
                // Algorithms ALSO predict this side → MAXIMUM confidence
                $confidence = min($confidence + 10, 88);
                $masterOverrideNote = "MASTER BOOST: Both previous losses on {$wrong1}, algorithms agree → {$finalPrediction}";
                $note .= " | MASTER confirmed";
            } else {
                // Algorithms predicted different → Trust MASTER override
                $finalPrediction = $masterOverride;
                $confidence = 84;
                $masterOverrideNote = "MASTER OVERRIDE: Both losses on {$wrong1} → forcing {$masterOverride}";
                $note = "MASTER OVERRIDE: {$wrong1} lost twice → bet {$masterOverride}";
            }
            $masterOverrideApplied = true;
            
        } else {
            // Previous wrongs were on DIFFERENT sides → game is chaotic
            // Trust the algorithm with highest score (already calculated)
            $topScore = max($score1, $score2, $score3);
            
            if ($topScore === $score1) {
                $finalPrediction = $pred1;
                $note = "L3: Mixed losses, trusting Algorithm 1 (score={$score1})";
            } elseif ($topScore === $score2) {
                $finalPrediction = $pred2;
                $note = "L3: Mixed losses, trusting Algorithm 2 (score={$score2})";
            } else {
                $finalPrediction = $pred3;
                $note = "L3: Mixed losses, trusting Algorithm 3 (score={$score3})";
            }
            $confidence = min(max($topScore - 5, 55), 82);
            $masterOverrideNote = "L3: Previous losses were mixed ({$wrong1}, {$wrong2}) — using highest score";
        }
    }
    
    // ─────────────────────────────────────────────────────────────────
    // LEVEL 2: Enhanced Analysis
    // ─────────────────────────────────────────────────────────────────
    if ($level === 2 && !$masterOverrideApplied) {
        // At level 2, give slight boost if 3/3 agree (retry situation)
        if ($agreed) {
            $confidence = min($confidence + 3, 86);
            $note .= " | L2 agreement boost";
        }
    }
    
    // ─────────────────────────────────────────────────────────────────
    // FINAL SAFETY CLAMP
    // ─────────────────────────────────────────────────────────────────
    $confidence = min(max($confidence, 50), 88);
    
    // ─────────────────────────────────────────────────────────────────
    // BUILD COMPLETE RESULT
    // ─────────────────────────────────────────────────────────────────
    return [
        'prediction'        => $finalPrediction,
        'confidence'        => $confidence,
        'agreed'            => $agreed,
        'level'             => $level,
        'votes'             => $votes,
        'vote_result'       => $voteResult,
        'note'              => $note,
        'master_override'   => $masterOverrideApplied,
        'master_note'       => $masterOverrideNote,
        'algorithm_results' => [
            'first'  => [
                'name'       => 'Streak Detection',
                'prediction' => $pred1,
                'score'      => $score1,
                'reason'     => $result1['reason'] ?? '',
                'confidence' => $result1['confidence'] ?? 'N/A',
            ],
            'second' => [
                'name'       => 'Recency Weight',
                'prediction' => $pred2,
                'score'      => $score2,
                'reason'     => $result2['reason'] ?? '',
                'confidence' => $result2['confidence'] ?? 'N/A',
            ],
            'third'  => [
                'name'       => 'Pattern Match',
                'prediction' => $pred3,
                'score'      => $score3,
                'reason'     => $result3['reason'] ?? '',
                'pattern'    => $result3['pattern'] ?? '',
                'confidence' => $result3['confidence'] ?? 'N/A',
            ],
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Create an error result
 */
function createErrorResult(string $message, int $level): array {
    return [
        'prediction'        => 'BIG',
        'confidence'        => 50,
        'agreed'            => false,
        'level'             => $level,
        'votes'             => ['BIG' => 0, 'Small' => 0],
        'vote_result'       => 'error',
        'note'              => "Error: {$message}",
        'error'             => true,
        'error_message'     => $message,
        'algorithm_results' => [],
        'timestamp'         => date('Y-m-d H:i:s'),
    ];
}

/**
 * Log prediction request for analytics
 */
function logPredictionRequest(array $entry): void {
    $logFile = __DIR__ . '/prediction_log.json';
    
    try {
        // Read existing log
        $existing = [];
        if (file_exists($logFile)) {
            $content = @file_get_contents($logFile);
            if ($content) {
                $existing = json_decode($content, true) ?? [];
            }
        }
        
        // Append new entry
        $existing[] = $entry;
        
        // Keep only last 1000 entries
        if (count($existing) > 1000) {
            $existing = array_slice($existing, -1000);
        }
        
        // Write back
        @file_put_contents($logFile, json_encode($existing, JSON_PRETTY_PRINT));
        
    } catch (Exception $e) {
        // Silently fail — don't disrupt main operation
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTTP HANDLER (Direct call)
// ═══════════════════════════════════════════════════════════════════════════════
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    if (empty($input['trends'])) {
        echo json_encode(['error' => 'No trends provided']);
        exit;
    }
    
    $trends = $input['trends'];
    $level  = (int)($input['level'] ?? 1);
    $prevWrong = $input['prev_wrong'] ?? [];
    
    $result = runCalculation($trends, $level, $prevWrong);
    
    echo json_encode($result, JSON_PRETTY_PRINT);
}
