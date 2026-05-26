<?php
/**
 * Master-prediction-tool.php — DHANI WIN
 * ==========================================
 * Main entry point. Called by the frontend cart via POST.
 *
 * Pipeline (simple & clean):
 *   1. Parse + validate input
 *   2. Edge correction check (all-same, last-5-same)
 *   3. Run Calculation (3 algorithms → majority vote)
 *   4. Apply edge override if needed
 *   5. Return JSON to cart
 *
 * No Python dependency. No file writes needed for basic operation.
 */

require_once __DIR__ . '/Help-to-take-accurate.php';
require_once __DIR__ . '/Calculation.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

// ── 1. Parse input ───────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$retryMode = (bool)($input['retry'] ?? false);
$rawTrends = $input['trends'] ?? [];

// ── 2. Validate ──────────────────────────────────────────────────
$validation = validateTrends($rawTrends);

if (!$validation['valid']) {
    echo json_encode([
        'status' => 'error',
        'errors' => $validation['errors'],
    ]);
    exit;
}

$trends = $validation['cleaned'];

// ── 3. Run 3-algorithm calculation ──────────────────────────────
$result = runCalculation($trends, $retryMode);

// ── 4. Apply edge correction ─────────────────────────────────────
$result = applyEdgeCorrection($result, $trends);

// ── 5. Return final result to cart ──────────────────────────────
echo json_encode([
    'status'     => 'ok',
    'prediction' => $result['prediction'],
    'confidence' => $result['confidence'],
    'agreed'     => $result['agreed'],
    'votes'      => $result['votes'],
    'retry_mode' => $retryMode,
    'timestamp'  => date('Y-m-d H:i:s'),
    'detail'     => $result['algorithm_results'],
    'edge'       => $result['edge_correction'] ?? null,
]);
