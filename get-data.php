<?php
/**
 * get-data.php — DHANI WIN
 * Handles reading, writing, and managing trend data + session state.
 * Called by Master-prediction-tool.php and analysis files.
 */

define('DATA_FILE',    __DIR__ . '/get-data.json');
define('HISTORY_FILE', __DIR__ . '/history.json');
define('MAX_HISTORY',  500);

// ─────────────────────────────────────────────
//  CORS & JSON headers
// ─────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─────────────────────────────────────────────
//  Load JSON data file
// ─────────────────────────────────────────────
function loadData(): array {
    if (!file_exists(DATA_FILE)) {
        return getDefaultData();
    }
    $raw = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    return $data ?: getDefaultData();
}

// ─────────────────────────────────────────────
//  Save JSON data file
// ─────────────────────────────────────────────
function saveData(array $data): bool {
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// ─────────────────────────────────────────────
//  Default data structure
// ─────────────────────────────────────────────
function getDefaultData(): array {
    return [
        'app'     => 'DHANI WIN',
        'version' => '1.0.0',
        'accuracy_target' => 98,
        'retry_accuracy'  => 99,
        'trend_window'    => 10,
        'session' => [
            'id'         => null,
            'started_at' => null,
            'wins'       => 0,
            'rounds'     => 0,
            'retry_mode' => false,
        ],
        'trends'          => [],
        'last_prediction' => null,
        'history'         => [],
        'pattern_weights' => getPatternWeights(),
        'algorithm_votes' => ['first' => null, 'second' => null, 'third' => null],
        'result_log'      => [],
        'meta' => [
            'game'      => 'WinGo 1M',
            'url_login' => 'https://dhaniwin0.com/login?type=0',
            'url_home'  => 'https://dhaniwin0.com/',
            'url_wingo' => 'https://dhaniwin0.com/WinGo/WinGo_1M',
        ],
    ];
}

// ─────────────────────────────────────────────
//  Pattern weights for algorithm scoring
// ─────────────────────────────────────────────
function getPatternWeights(): array {
    return [
        'reversal_after_3'     => 0.72,
        'continuation_after_2' => 0.55,
        'zigzag_pattern'       => 0.68,
        'dominant_trend'       => 0.61,
        'alternating_pair'     => 0.65,
        'triple_break'         => 0.78,
        'fibonacci_sequence'   => 0.70,
        'momentum_shift'       => 0.74,
    ];
}

// ─────────────────────────────────────────────
//  Store incoming trends
// ─────────────────────────────────────────────
function storeTrends(array $trends, bool $retryMode = false): array {
    $data = loadData();
    $data['trends']              = $trends;
    $data['session']['retry_mode'] = $retryMode;

    if (empty($data['session']['id'])) {
        $data['session']['id']         = 'sess_' . uniqid();
        $data['session']['started_at'] = date('Y-m-d H:i:s');
    }

    saveData($data);
    return $data;
}

// ─────────────────────────────────────────────
//  Log a result entry
// ─────────────────────────────────────────────
function logResult(string $prediction, int $confidence, bool $wasCorrect = null): void {
    $data = loadData();

    $entry = [
        'timestamp'  => date('Y-m-d H:i:s'),
        'prediction' => $prediction,
        'confidence' => $confidence,
        'correct'    => $wasCorrect,
        'retry_mode' => $data['session']['retry_mode'] ?? false,
    ];

    $data['result_log'][]      = $entry;
    $data['last_prediction']   = $prediction;
    $data['session']['rounds'] = ($data['session']['rounds'] ?? 0) + 1;

    if ($wasCorrect === true) {
        $data['session']['wins'] = ($data['session']['wins'] ?? 0) + 1;
    }

    // Trim log to last MAX_HISTORY entries
    if (count($data['result_log']) > MAX_HISTORY) {
        $data['result_log'] = array_slice($data['result_log'], -MAX_HISTORY);
    }

    saveData($data);
}

// ─────────────────────────────────────────────
//  Reset session trends (after feedback)
// ─────────────────────────────────────────────
function resetTrends(): void {
    $data = loadData();
    $data['trends']            = [];
    $data['algorithm_votes']   = ['first' => null, 'second' => null, 'third' => null];
    saveData($data);
}

// ─────────────────────────────────────────────
//  Get current session stats
// ─────────────────────────────────────────────
function getSessionStats(): array {
    $data = loadData();
    return [
        'wins'       => $data['session']['wins']   ?? 0,
        'rounds'     => $data['session']['rounds'] ?? 0,
        'retry_mode' => $data['session']['retry_mode'] ?? false,
        'session_id' => $data['session']['id']     ?? null,
    ];
}

// ─────────────────────────────────────────────
//  Direct HTTP request handler
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'get');

    switch ($action) {
        case 'store':
            $trends    = $input['trends']     ?? [];
            $retryMode = $input['retry_mode'] ?? false;
            $data      = storeTrends($trends, $retryMode);
            echo json_encode(['status' => 'ok', 'stored' => count($trends)]);
            break;

        case 'log':
            $prediction = $input['prediction'] ?? 'BIG';
            $confidence = (int)($input['confidence'] ?? 98);
            $correct    = isset($input['correct']) ? (bool)$input['correct'] : null;
            logResult($prediction, $confidence, $correct);
            echo json_encode(['status' => 'ok']);
            break;

        case 'reset':
            resetTrends();
            echo json_encode(['status' => 'ok', 'message' => 'Trends reset']);
            break;

        case 'stats':
            echo json_encode(getSessionStats());
            break;

        case 'get':
        default:
            $data = loadData();
            echo json_encode([
                'trends'          => $data['trends'],
                'last_prediction' => $data['last_prediction'],
                'session'         => $data['session'],
                'pattern_weights' => $data['pattern_weights'],
            ]);
            break;
    }
}
