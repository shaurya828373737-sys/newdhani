<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * get-data.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * DATA MANAGEMENT & SESSION HANDLING
 * 
 * This file manages:
 * - Trend data storage and retrieval
 * - Session state (wins, rounds, retry mode)
 * - Result logging for accuracy tracking
 * - Pattern weight configuration
 * 
 * API Actions:
 * - GET:    Get current trends and session state
 * - store:  Store new trends
 * - log:    Log a prediction result
 * - reset:  Reset trends for new prediction
 * - stats:  Get session statistics
 * - clear:  Clear all session data
 * 
 * © DHANI WIN 2025
 */

// ═══════════════════════════════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════════
define('DATA_FILE',    __DIR__ . '/get-data.json');
define('HISTORY_FILE', __DIR__ . '/history.json');
define('LOG_FILE',     __DIR__ . '/prediction_log.json');
define('MAX_HISTORY',  500);
define('MAX_LOG',      1000);

// ═══════════════════════════════════════════════════════════════════════════════
// CORS & HEADERS
// ═══════════════════════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// DATA LOADING
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Load main data file
 */
function loadData(): array {
    if (!file_exists(DATA_FILE)) {
        $default = getDefaultData();
        saveData($default);
        return $default;
    }
    
    $raw = @file_get_contents(DATA_FILE);
    if (!$raw) {
        return getDefaultData();
    }
    
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return getDefaultData();
    }
    
    // Ensure all required keys exist
    return array_merge(getDefaultData(), $data);
}

/**
 * Save main data file
 */
function saveData(array $data): bool {
    $data['last_updated'] = date('Y-m-d H:i:s');
    return @file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Get default data structure
 */
function getDefaultData(): array {
    return [
        'app'             => 'DHANI WIN',
        'version'         => '2.0.0',
        'created_at'      => date('Y-m-d H:i:s'),
        'last_updated'    => date('Y-m-d H:i:s'),
        
        // Configuration
        'accuracy_target' => 85,
        'trend_window'    => 10,
        
        // Session state
        'session' => [
            'id'           => 'sess_' . uniqid(),
            'started_at'   => date('Y-m-d H:i:s'),
            'wins'         => 0,
            'losses'       => 0,
            'rounds'       => 0,
            'current_level'=> 1,
            'loss_streak'  => 0,
            'prev_wrong'   => [],
        ],
        
        // Current data
        'trends'          => [],
        'last_prediction' => null,
        'last_confidence' => null,
        'last_result'     => null,  // 'win' or 'loss'
        
        // History
        'history'         => [],
        'result_log'      => [],
        
        // Pattern weights (used by algorithms)
        'pattern_weights' => getPatternWeights(),
        
        // Metadata
        'meta' => [
            'game'      => 'WinGo 1M',
            'url_login' => 'https://dhaniwin0.com/login?type=0',
            'url_home'  => 'https://dhaniwin0.com/',
            'url_wingo' => 'https://dhaniwin0.com/WinGo/WinGo_1M',
        ],
    ];
}

/**
 * Get pattern weight configuration
 */
function getPatternWeights(): array {
    return [
        // Streak-based weights
        'streak_3_reversal'    => 0.75,
        'streak_4_reversal'    => 0.82,
        'streak_5_reversal'    => 0.88,
        
        // Pattern-based weights
        'pattern_BBB'          => 0.82,
        'pattern_SSS'          => 0.82,
        'pattern_BBS'          => 0.73,
        'pattern_SSB'          => 0.73,
        
        // Momentum weights
        'momentum_shift_strong'=> 0.78,
        'momentum_shift_weak'  => 0.60,
        
        // Edge case weights
        'edge_all_same'        => 0.95,
        'edge_last_5_same'     => 0.85,
        'edge_alternating'     => 0.68,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// TREND MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Store trends
 */
function storeTrends(array $trends, int $level = 1): array {
    $data = loadData();
    
    $data['trends'] = $trends;
    $data['session']['current_level'] = $level;
    
    // Generate session ID if missing
    if (empty($data['session']['id'])) {
        $data['session']['id'] = 'sess_' . uniqid();
        $data['session']['started_at'] = date('Y-m-d H:i:s');
    }
    
    saveData($data);
    
    return [
        'status'     => 'ok',
        'stored'     => count($trends),
        'session_id' => $data['session']['id'],
    ];
}

/**
 * Reset trends for new prediction
 */
function resetTrends(bool $keepSession = true): array {
    $data = loadData();
    
    $data['trends'] = [];
    $data['last_prediction'] = null;
    $data['last_confidence'] = null;
    
    if (!$keepSession) {
        $data['session'] = [
            'id'           => 'sess_' . uniqid(),
            'started_at'   => date('Y-m-d H:i:s'),
            'wins'         => 0,
            'losses'       => 0,
            'rounds'       => 0,
            'current_level'=> 1,
            'loss_streak'  => 0,
            'prev_wrong'   => [],
        ];
    }
    
    saveData($data);
    
    return [
        'status'  => 'ok',
        'message' => $keepSession ? 'Trends reset, session kept' : 'Full reset complete',
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// RESULT LOGGING
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Log a prediction result
 */
function logResult(string $prediction, int $confidence, ?bool $wasCorrect = null, int $level = 1): array {
    $data = loadData();
    
    $entry = [
        'timestamp'   => date('Y-m-d H:i:s'),
        'prediction'  => $prediction,
        'confidence'  => $confidence,
        'level'       => $level,
        'correct'     => $wasCorrect,
    ];
    
    // Update session stats
    $data['session']['rounds']++;
    
    if ($wasCorrect === true) {
        $data['session']['wins']++;
        $data['session']['loss_streak'] = 0;
        $data['session']['current_level'] = 1;  // Reset to level 1 after win
        $data['session']['prev_wrong'] = [];
        $data['last_result'] = 'win';
    } elseif ($wasCorrect === false) {
        $data['session']['losses']++;
        $data['session']['loss_streak']++;
        $data['session']['prev_wrong'][] = $prediction;
        
        // Keep only last 5 wrong predictions
        if (count($data['session']['prev_wrong']) > 5) {
            $data['session']['prev_wrong'] = array_slice($data['session']['prev_wrong'], -5);
        }
        
        // Escalate level (max 3)
        if ($data['session']['current_level'] < 3) {
            $data['session']['current_level']++;
        }
        $data['last_result'] = 'loss';
    }
    
    // Store last prediction info
    $data['last_prediction'] = $prediction;
    $data['last_confidence'] = $confidence;
    
    // Add to result log
    $data['result_log'][] = $entry;
    
    // Trim log to max size
    if (count($data['result_log']) > MAX_HISTORY) {
        $data['result_log'] = array_slice($data['result_log'], -MAX_HISTORY);
    }
    
    saveData($data);
    
    return [
        'status'       => 'ok',
        'logged'       => true,
        'session_stats'=> getSessionStats(),
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// STATISTICS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Get session statistics
 */
function getSessionStats(): array {
    $data = loadData();
    $session = $data['session'];
    
    $rounds = $session['rounds'] ?? 0;
    $wins   = $session['wins'] ?? 0;
    $losses = $session['losses'] ?? 0;
    
    $winRate = $rounds > 0 ? round(($wins / $rounds) * 100, 1) : 0;
    
    return [
        'session_id'    => $session['id'] ?? null,
        'started_at'    => $session['started_at'] ?? null,
        'rounds'        => $rounds,
        'wins'          => $wins,
        'losses'        => $losses,
        'win_rate'      => $winRate,
        'current_level' => $session['current_level'] ?? 1,
        'loss_streak'   => $session['loss_streak'] ?? 0,
        'prev_wrong'    => $session['prev_wrong'] ?? [],
    ];
}

/**
 * Get accuracy statistics from log
 */
function getAccuracyStats(): array {
    $data = loadData();
    $log = $data['result_log'] ?? [];
    
    $total = count($log);
    $correct = count(array_filter($log, fn($e) => $e['correct'] === true));
    $incorrect = count(array_filter($log, fn($e) => $e['correct'] === false));
    $unknown = $total - $correct - $incorrect;
    
    // By level
    $byLevel = [1 => ['total' => 0, 'correct' => 0], 2 => ['total' => 0, 'correct' => 0], 3 => ['total' => 0, 'correct' => 0]];
    foreach ($log as $entry) {
        $lvl = $entry['level'] ?? 1;
        if (isset($byLevel[$lvl])) {
            $byLevel[$lvl]['total']++;
            if ($entry['correct'] === true) {
                $byLevel[$lvl]['correct']++;
            }
        }
    }
    
    return [
        'total_predictions' => $total,
        'correct'           => $correct,
        'incorrect'         => $incorrect,
        'unknown'           => $unknown,
        'accuracy'          => $total > 0 ? round(($correct / max($total - $unknown, 1)) * 100, 1) : 0,
        'by_level'          => $byLevel,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTTP REQUEST HANDLER
// ═══════════════════════════════════════════════════════════════════════════════

if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'get');
    
    switch ($action) {
        
        case 'store':
            $trends = $input['trends'] ?? [];
            $level  = (int)($input['level'] ?? 1);
            echo json_encode(storeTrends($trends, $level));
            break;
        
        case 'log':
            $prediction = $input['prediction'] ?? 'BIG';
            $confidence = (int)($input['confidence'] ?? 75);
            $correct    = isset($input['correct']) ? (bool)$input['correct'] : null;
            $level      = (int)($input['level'] ?? 1);
            echo json_encode(logResult($prediction, $confidence, $correct, $level));
            break;
        
        case 'reset':
            $keepSession = $input['keep_session'] ?? true;
            echo json_encode(resetTrends((bool)$keepSession));
            break;
        
        case 'clear':
            echo json_encode(resetTrends(false));
            break;
        
        case 'stats':
            echo json_encode(getSessionStats());
            break;
        
        case 'accuracy':
            echo json_encode(getAccuracyStats());
            break;
        
        case 'get':
        default:
            $data = loadData();
            echo json_encode([
                'trends'          => $data['trends'],
                'last_prediction' => $data['last_prediction'],
                'last_confidence' => $data['last_confidence'],
                'last_result'     => $data['last_result'] ?? null,
                'session'         => $data['session'],
                'pattern_weights' => $data['pattern_weights'],
                'meta'            => $data['meta'],
            ]);
            break;
    }
}
