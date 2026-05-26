<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Master-prediction-tool.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * MAIN API ENTRY POINT — 3-Level Win System
 * 
 * This is the main endpoint that the frontend calls to get predictions.
 * It orchestrates the entire prediction pipeline:
 * 
 * 1. Validates input (trends, level, prev_wrong)
 * 2. Runs triple-algorithm calculation
 * 3. Applies edge case corrections
 * 4. Returns comprehensive result with confidence
 * 
 * Request Format (POST JSON):
 * {
 *   "trends": [{"type":"BIG"}, {"type":"Small"}, ...],  // 10 trends required
 *   "level": 1|2|3,                                      // default: 1
 *   "prev_wrong": ["BIG", "Small"],                     // previous wrong predictions
 *   "session_id": "sess_xxx"                            // optional session tracking
 * }
 * 
 * Response Format:
 * {
 *   "status": "ok",
 *   "prediction": "BIG"|"Small",
 *   "confidence": 50-88,
 *   "level": 1|2|3,
 *   "level_label": "BET 1"|"BET 2"|"BET 3 🔥",
 *   "level_color": "#00e676"|"#f0b90b"|"#ff1744",
 *   "agreed": true|false,
 *   "votes": {"BIG": 2, "Small": 1},
 *   "note": "Explanation",
 *   "edge": "Edge case description"|null,
 *   "detail": { ... algorithm breakdown ... },
 *   "timestamp": "2025-01-15 14:30:45"
 * }
 * 
 * © DHANI WIN 2025
 */

require_once __DIR__ . '/Help-to-take-accurate.php';
require_once __DIR__ . '/Calculation.php';
require_once __DIR__ . '/get-data.php';

// ═══════════════════════════════════════════════════════════════════════════════
// CORS & HEADERS
// ═══════════════════════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ═══════════════════════════════════════════════════════════════════════════════
// PREFLIGHT HANDLING
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// METHOD CHECK
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'error'   => 'Method Not Allowed',
        'message' => 'Only POST requests are supported. Send JSON body with trends array.',
        'allowed' => ['POST', 'OPTIONS'],
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PARSE INPUT
// ═══════════════════════════════════════════════════════════════════════════════
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'error'   => 'Invalid JSON',
        'message' => 'Request body must be valid JSON object',
        'received'=> substr($rawInput, 0, 200),
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// EXTRACT & VALIDATE INPUTS
// ═══════════════════════════════════════════════════════════════════════════════
$rawTrends = $input['trends'] ?? [];
$level     = max(1, min(3, (int)($input['level'] ?? 1)));
$prevWrong = is_array($input['prev_wrong'] ?? null) ? $input['prev_wrong'] : [];
$sessionId = $input['session_id'] ?? 'sess_' . uniqid();

// Validate trends
$validation = validateTrends($rawTrends);

if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode([
        'status'   => 'error',
        'error'    => 'Validation Failed',
        'message'  => 'Trend data validation failed',
        'errors'   => $validation['errors'],
        'received' => count($rawTrends),
        'required' => 10,
    ]);
    exit;
}

$trends = $validation['cleaned'];

// ═══════════════════════════════════════════════════════════════════════════════
// LEVEL METADATA
// ═══════════════════════════════════════════════════════════════════════════════
$levelMeta = [
    1 => [
        'label'       => 'BET 1',
        'color'       => '#00e676',
        'hint'        => 'Standard triple-algorithm consensus analysis',
        'description' => 'First prediction using all three algorithms with equal weight',
        'emoji'       => '✅',
    ],
    2 => [
        'label'       => 'BET 2',
        'color'       => '#f0b90b',
        'hint'        => 'Enhanced momentum and reversal analysis after first loss',
        'description' => 'Deeper analysis with boosted confidence for agreeing algorithms',
        'emoji'       => '⚡',
    ],
    3 => [
        'label'       => 'BET 3 🔥',
        'color'       => '#ff1744',
        'hint'        => 'MASTER RESULT — Maximum depth analysis with override logic',
        'description' => 'Final prediction using full transition matrices and loss pattern analysis',
        'emoji'       => '🔥',
    ],
];

$meta = $levelMeta[$level];

// ═══════════════════════════════════════════════════════════════════════════════
// RUN CALCULATION
// ═══════════════════════════════════════════════════════════════════════════════
try {
    $result = runCalculation($trends, $level, $prevWrong);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'error'   => 'Calculation Error',
        'message' => 'An error occurred during prediction calculation',
        'detail'  => $e->getMessage(),
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// APPLY EDGE CORRECTIONS
// ═══════════════════════════════════════════════════════════════════════════════
$result = applyEdgeCorrection($result, $trends);

// ═══════════════════════════════════════════════════════════════════════════════
// LOG REQUEST (for analytics)
// ═══════════════════════════════════════════════════════════════════════════════
$logEntry = [
    'timestamp'    => $result['timestamp'],
    'session_id'   => $sessionId,
    'level'        => $level,
    'prediction'   => $result['prediction'],
    'confidence'   => $result['confidence'],
    'agreed'       => $result['agreed'],
    'vote_result'  => $result['vote_result'] ?? 'unknown',
    'edge_applied' => !empty($result['edge_correction']),
    'trends_count' => count($trends),
];

// Log asynchronously (don't block response)
@logPredictionRequest($logEntry);

// ═══════════════════════════════════════════════════════════════════════════════
// BUILD FINAL RESPONSE
// ═══════════════════════════════════════════════════════════════════════════════
$response = [
    'status'           => 'ok',
    
    // Core prediction
    'prediction'       => $result['prediction'],
    'confidence'       => $result['confidence'],
    
    // Level info
    'level'            => $level,
    'level_label'      => $meta['label'],
    'level_color'      => $meta['color'],
    'level_hint'       => $meta['hint'],
    'level_description'=> $meta['description'],
    'level_emoji'      => $meta['emoji'],
    
    // Voting details
    'agreed'           => $result['agreed'],
    'votes'            => $result['votes'],
    'vote_result'      => $result['vote_result'] ?? 'unknown',
    
    // Explanations
    'note'             => $result['note'] ?? 'Analysis complete',
    'edge'             => $result['edge_correction'] ?? null,
    'edge_type'        => $result['edge_type'] ?? null,
    'edge_strength'    => $result['edge_strength'] ?? null,
    
    // Master mode info
    'master_override'  => $result['master_override'] ?? false,
    'master_note'      => $result['master_note'] ?? null,
    
    // Algorithm breakdown
    'detail'           => [
        'first_algo'   => $result['algorithm_results']['first'] ?? [],
        'second_algo'  => $result['algorithm_results']['second'] ?? [],
        'third_algo'   => $result['algorithm_results']['third'] ?? [],
    ],
    
    // Metadata
    'session_id'       => $sessionId,
    'timestamp'        => $result['timestamp'],
    'version'          => '2.0.0',
    'engine'           => 'DHANI WIN Triple-Algorithm Engine',
];

// ═══════════════════════════════════════════════════════════════════════════════
// SEND RESPONSE
// ═══════════════════════════════════════════════════════════════════════════════
http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
