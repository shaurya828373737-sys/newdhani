<?php
/**
 * Master-prediction-tool.php — DHANI WIN
 * ==========================================
 * THE MAIN ORCHESTRATOR
 * Receives trend data from the frontend Cart,
 * runs all analysis engines in sequence,
 * resolves consensus, and returns the final prediction.
 *
 * Flow:
 *   1. Validate input
 *   2. Store trends (get-data.php)
 *   3. Run PHP triple analysis (Calculation.php)
 *   4. Run Python analysis (Python-get-prediction.php)
 *   5. Apply accuracy helpers (Help-to-take-accurate.php)
 *   6. Return final JSON prediction to Cart
 */

require_once __DIR__ . '/get-data.php';
require_once __DIR__ . '/Calculation.php';
require_once __DIR__ . '/Python-get-prediction.php';
require_once __DIR__ . '/Help-to-take-accurate.php';

// ── CORS & Headers ───────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Only accept POST ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST method required']);
    exit;
}

// ── Parse input ──────────────────────────────
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$trends    = $input['trends'] ?? [];
$retryMode = (bool)($input['retry'] ?? false);

// ─────────────────────────────────────────────
//  STEP 1: Validate
// ─────────────────────────────────────────────
$validation = validateTrends($trends);
if (!$validation['valid']) {
    echo json_encode([
        'error'  => 'Validation failed',
        'issues' => $validation['errors'],
    ]);
    exit;
}
$trends = $validation['cleaned'];

// ─────────────────────────────────────────────
//  STEP 2: Store trends
// ─────────────────────────────────────────────
storeTrends($trends, $retryMode);

// ─────────────────────────────────────────────
//  STEP 3: PHP Triple Algorithm (Calculation)
// ─────────────────────────────────────────────
$phpResult = runCalculation($trends, $retryMode);

// ─────────────────────────────────────────────
//  STEP 4: Python + Combined prediction
// ─────────────────────────────────────────────
$combinedResult = getCombinedPrediction($trends, $retryMode);

// ─────────────────────────────────────────────
//  STEP 5: Accuracy boosting & edge correction
// ─────────────────────────────────────────────
$finalResult = boostConfidence($combinedResult, $trends, $retryMode);
$finalResult = applyEdgeCorrection($finalResult, $trends);

// ─────────────────────────────────────────────
//  STEP 6: Log result
// ─────────────────────────────────────────────
logResult(
    $finalResult['prediction'],
    $finalResult['confidence'],
    null // feedback not known yet
);

// ─────────────────────────────────────────────
//  STEP 7: Build final response for Cart
// ─────────────────────────────────────────────
$response = [
    'status'     => 'ok',
    'prediction' => $finalResult['prediction'],
    'confidence' => $finalResult['confidence'],
    'agreed'     => $finalResult['agreed']       ?? false,
    'retry_mode' => $retryMode,
    'timestamp'  => date('Y-m-d H:i:s'),

    // Detailed breakdown (visible in dev tools / logs)
    'detail' => [
        'php_votes'      => $phpResult['algorithm_results'] ?? [],
        'python_source'  => $combinedResult['py_result']['source'] ?? 'unknown',
        'boost_applied'  => $finalResult['boost_applied']   ?? 0,
        'edge_correction'=> $finalResult['edge_correction'] ?? null,
    ],
];

echo json_encode($response);
