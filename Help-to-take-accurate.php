<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Help-to-take-accurate.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * INPUT VALIDATION & EDGE CASE CORRECTION
 * 
 * This file handles:
 * - Trend data validation (exactly 10 trends, valid types)
 * - Edge case detection and correction
 * - Statistical anomaly handling
 * 
 * Edge Cases Handled:
 * 1. All 10 trends same (e.g., all BIG) → Force reversal (highest confidence)
 * 2. Last 5 all same → Strong reversal signal
 * 3. Last 7+ same → Very strong reversal signal
 * 4. Perfect alternation → Continue pattern
 * 
 * © DHANI WIN 2025
 */

/**
 * Validate trend input data
 *
 * @param array $trends Raw trend data from client
 * @return array ['valid' => bool, 'errors' => [], 'cleaned' => []]
 */
function validateTrends(array $trends): array {
    $errors  = [];
    $cleaned = [];
    
    // ─────────────────────────────────────────────────────────────────
    // CHECK COUNT
    // ─────────────────────────────────────────────────────────────────
    $count = count($trends);
    
    if ($count === 0) {
        return [
            'valid'   => false,
            'errors'  => ['No trends provided. Please enter 10 trends.'],
            'cleaned' => [],
        ];
    }
    
    if ($count !== 10) {
        $errors[] = "Need exactly 10 trends, got {$count}. Please enter exactly 10 trends.";
        // Continue to validate what we have
    }
    
    // ─────────────────────────────────────────────────────────────────
    // VALIDATE EACH TREND
    // ─────────────────────────────────────────────────────────────────
    $validTypes = ['BIG', 'Small'];
    
    foreach ($trends as $index => $trend) {
        $trendNum = $index + 1;
        
        // Check if trend is array
        if (!is_array($trend)) {
            $errors[] = "Trend #{$trendNum}: Invalid format (not an object)";
            continue;
        }
        
        // Extract and sanitize type
        $type = isset($trend['type']) ? trim((string)$trend['type']) : '';
        
        // Normalize type (handle case variations)
        $typeNormalized = $type;
        if (strtolower($type) === 'big') {
            $typeNormalized = 'BIG';
        } elseif (strtolower($type) === 'small') {
            $typeNormalized = 'Small';
        }
        
        // Validate type
        if (!in_array($typeNormalized, $validTypes, true)) {
            $errors[] = "Trend #{$trendNum}: Invalid type '{$type}'. Must be 'BIG' or 'Small'.";
            continue;
        }
        
        // Extract number (optional, defaults to position)
        $number = isset($trend['number']) ? (int)$trend['number'] : $trendNum;
        $number = max(1, min($number, 10));
        
        // Add to cleaned array
        $cleaned[] = [
            'type'   => $typeNormalized,
            'number' => $number,
            'index'  => $index,
        ];
    }
    
    // ─────────────────────────────────────────────────────────────────
    // FINAL VALIDATION
    // ─────────────────────────────────────────────────────────────────
    $isValid = empty($errors) && count($cleaned) === 10;
    
    return [
        'valid'   => $isValid,
        'errors'  => $errors,
        'cleaned' => $cleaned,
        'count'   => count($cleaned),
    ];
}

/**
 * Apply edge case corrections to prediction result
 *
 * @param array $result  Prediction result from Calculation.php
 * @param array $trends  Cleaned trend data
 * @return array         Modified result with edge corrections applied
 */
function applyEdgeCorrection(array $result, array $trends): array {
    $types = array_column($trends, 'type');
    $n     = count($types);
    
    if ($n < 5) {
        return $result; // Not enough data for edge detection
    }
    
    // ─────────────────────────────────────────────────────────────────
    // EDGE CASE 1: ALL TRENDS ARE THE SAME
    // This is the strongest edge case — probability of continuation is very low
    // ─────────────────────────────────────────────────────────────────
    $uniqueTypes = array_unique($types);
    
    if (count($uniqueTypes) === 1) {
        $allSameType = $types[0];
        $forcedPrediction = ($allSameType === 'BIG') ? 'Small' : 'BIG';
        
        $result['prediction']      = $forcedPrediction;
        $result['confidence']      = max($result['confidence'] ?? 70, 85);
        $result['edge_correction'] = "CRITICAL: All {$n} trends are {$allSameType} — FORCED reversal to {$forcedPrediction}";
        $result['edge_type']       = 'all_same';
        $result['edge_strength']   = 'MAXIMUM';
        
        return $result;
    }
    
    // ─────────────────────────────────────────────────────────────────
    // EDGE CASE 2: LAST 7+ ARE THE SAME
    // Very strong reversal signal
    // ─────────────────────────────────────────────────────────────────
    $last7 = array_slice($types, -7);
    if (count(array_unique($last7)) === 1 && count($last7) === 7) {
        $streakType = $last7[0];
        $forcedPrediction = ($streakType === 'BIG') ? 'Small' : 'BIG';
        
        // Force correction if result disagrees
        if ($result['prediction'] !== $forcedPrediction) {
            $result['prediction']      = $forcedPrediction;
            $result['confidence']      = max($result['confidence'] ?? 70, 82);
            $result['edge_correction'] = "STRONG: Last 7 all {$streakType} — corrected to {$forcedPrediction}";
            $result['edge_type']       = 'last_7_same';
            $result['edge_strength']   = 'VERY_HIGH';
        } else {
            // Result agrees — boost confidence
            $result['confidence'] = min(($result['confidence'] ?? 70) + 5, 88);
            $result['edge_correction'] = "CONFIRMED: Last 7 all {$streakType} — {$forcedPrediction} prediction boosted";
            $result['edge_type']       = 'last_7_same';
            $result['edge_strength']   = 'VERY_HIGH';
        }
        
        return $result;
    }
    
    // ─────────────────────────────────────────────────────────────────
    // EDGE CASE 3: LAST 5 ARE THE SAME
    // Strong reversal signal
    // ─────────────────────────────────────────────────────────────────
    $last5 = array_slice($types, -5);
    if (count(array_unique($last5)) === 1) {
        $streakType = $last5[0];
        $forcedPrediction = ($streakType === 'BIG') ? 'Small' : 'BIG';
        
        if ($result['prediction'] !== $forcedPrediction) {
            $result['prediction']      = $forcedPrediction;
            $result['confidence']      = min(max($result['confidence'] ?? 70, 72), 80);
            $result['edge_correction'] = "Last 5 all {$streakType} — corrected to {$forcedPrediction}";
            $result['edge_type']       = 'last_5_same';
            $result['edge_strength']   = 'HIGH';
        } else {
            $result['confidence'] = min(($result['confidence'] ?? 70) + 3, 85);
            $result['edge_correction'] = "Last 5 all {$streakType} — {$forcedPrediction} prediction confirmed";
            $result['edge_type']       = 'last_5_same';
            $result['edge_strength']   = 'HIGH';
        }
        
        return $result;
    }
    
    // ─────────────────────────────────────────────────────────────────
    // EDGE CASE 4: PERFECT ALTERNATION
    // If pattern is BSBSBS... or SBSBSB..., predict continuation
    // ─────────────────────────────────────────────────────────────────
    $isAlternating = true;
    for ($i = 1; $i < $n; $i++) {
        if ($types[$i] === $types[$i - 1]) {
            $isAlternating = false;
            break;
        }
    }
    
    if ($isAlternating && $n >= 6) {
        $lastType = $types[$n - 1];
        $nextInPattern = ($lastType === 'BIG') ? 'Small' : 'BIG';
        
        if ($result['prediction'] !== $nextInPattern) {
            // Correct to follow alternation
            $result['prediction']      = $nextInPattern;
            $result['confidence']      = 68;
            $result['edge_correction'] = "Perfect alternation detected — next should be {$nextInPattern}";
            $result['edge_type']       = 'alternating';
            $result['edge_strength']   = 'MEDIUM';
        } else {
            // Already correct
            $result['edge_correction'] = "Alternation pattern confirmed — {$nextInPattern}";
            $result['edge_type']       = 'alternating';
            $result['edge_strength']   = 'MEDIUM';
        }
        
        return $result;
    }
    
    // ─────────────────────────────────────────────────────────────────
    // EDGE CASE 5: HEAVY IMBALANCE (8+ of one type)
    // ─────────────────────────────────────────────────────────────────
    $bigCount = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;
    
    if ($bigCount >= 8) {
        // 8+ BIG out of 10 → expect Small
        if ($result['prediction'] !== 'Small') {
            $result['prediction']      = 'Small';
            $result['confidence']      = min(max($result['confidence'] ?? 70, 70), 78);
            $result['edge_correction'] = "Heavy BIG imbalance ({$bigCount}/10) — corrected to Small";
            $result['edge_type']       = 'imbalance';
            $result['edge_strength']   = 'MEDIUM-HIGH';
        }
        return $result;
    }
    
    if ($smallCount >= 8) {
        // 8+ Small out of 10 → expect BIG
        if ($result['prediction'] !== 'BIG') {
            $result['prediction']      = 'BIG';
            $result['confidence']      = min(max($result['confidence'] ?? 70, 70), 78);
            $result['edge_correction'] = "Heavy Small imbalance ({$smallCount}/10) — corrected to BIG";
            $result['edge_type']       = 'imbalance';
            $result['edge_strength']   = 'MEDIUM-HIGH';
        }
        return $result;
    }
    
    // ─────────────────────────────────────────────────────────────────
    // NO EDGE CASE — Return unmodified
    // ─────────────────────────────────────────────────────────────────
    return $result;
}

/**
 * Get additional analysis insights
 *
 * @param array $trends Cleaned trend data
 * @return array Analysis metadata
 */
function getAnalysisInsights(array $trends): array {
    $types = array_column($trends, 'type');
    $n = count($types);
    
    if ($n === 0) {
        return ['error' => 'No trends to analyze'];
    }
    
    // Count types
    $bigCount = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;
    
    // Calculate streaks
    $maxStreakBig = 0;
    $maxStreakSmall = 0;
    $currentStreakBig = 0;
    $currentStreakSmall = 0;
    $currentStreak = 1;
    $currentStreakType = $types[0];
    
    foreach ($types as $i => $t) {
        if ($t === 'BIG') {
            $currentStreakBig++;
            $currentStreakSmall = 0;
            $maxStreakBig = max($maxStreakBig, $currentStreakBig);
        } else {
            $currentStreakSmall++;
            $currentStreakBig = 0;
            $maxStreakSmall = max($maxStreakSmall, $currentStreakSmall);
        }
        
        if ($i > 0) {
            if ($types[$i] === $types[$i - 1]) {
                $currentStreak++;
            } else {
                $currentStreak = 1;
                $currentStreakType = $types[$i];
            }
        }
    }
    
    // Count transitions
    $transitions = ['BIG_to_BIG' => 0, 'BIG_to_Small' => 0, 'Small_to_BIG' => 0, 'Small_to_Small' => 0];
    for ($i = 0; $i < $n - 1; $i++) {
        $key = $types[$i] . '_to_' . $types[$i + 1];
        if (isset($transitions[$key])) {
            $transitions[$key]++;
        }
    }
    
    // Determine pattern type
    $patternType = 'mixed';
    if ($bigCount === $n) {
        $patternType = 'all_big';
    } elseif ($smallCount === $n) {
        $patternType = 'all_small';
    } elseif ($maxStreakBig >= 5 || $maxStreakSmall >= 5) {
        $patternType = 'long_streak';
    } elseif (abs($bigCount - $smallCount) <= 1) {
        $patternType = 'balanced';
    } elseif ($bigCount >= 7) {
        $patternType = 'big_dominant';
    } elseif ($smallCount >= 7) {
        $patternType = 'small_dominant';
    }
    
    return [
        'total'            => $n,
        'big_count'        => $bigCount,
        'small_count'      => $smallCount,
        'big_percentage'   => round(($bigCount / $n) * 100, 1),
        'small_percentage' => round(($smallCount / $n) * 100, 1),
        'max_streak_big'   => $maxStreakBig,
        'max_streak_small' => $maxStreakSmall,
        'current_streak'   => $currentStreak,
        'current_streak_type' => $currentStreakType,
        'transitions'      => $transitions,
        'pattern_type'     => $patternType,
        'last_type'        => $types[$n - 1] ?? null,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTTP HANDLER (Direct call)
// ═══════════════════════════════════════════════════════════════════════════════
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'validate');
    $trends = $input['trends'] ?? [];
    
    switch ($action) {
        case 'insights':
            if (empty($trends)) {
                echo json_encode(['error' => 'No trends provided']);
            } else {
                $validation = validateTrends($trends);
                if ($validation['valid']) {
                    echo json_encode(getAnalysisInsights($validation['cleaned']));
                } else {
                    echo json_encode(['error' => 'Invalid trends', 'details' => $validation['errors']]);
                }
            }
            break;
            
        case 'validate':
        default:
            echo json_encode(validateTrends($trends));
            break;
    }
}
