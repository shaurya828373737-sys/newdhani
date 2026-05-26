<?php
/**
 * Master-prediction-tool.php — DHANI WIN
 * Main entry point called by the cart via POST.
 *
 * Steps:
 *   1. Validate input
 *   2. Run 3-algorithm calculation
 *   3. Apply edge-case override (all-10-same / last-5-same)
 *   4. Return honest JSON — confidence is real, not fake
 */

require_once __DIR__ . '/Help-to-take-accurate.php';
require_once __DIR__ . '/Calculation.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { echo json_encode(['error' => 'POST required']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { echo json_encode(['error' => 'Invalid JSON']); exit; }

$rawTrends = $input['trends'] ?? [];

// 1. Validate
$validation = validateTrends($rawTrends);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'errors' => $validation['errors']]);
    exit;
}
$trends = $validation['cleaned'];

// 2. Calculate
$result = runCalculation($trends);

// 3. Edge override
$result = applyEdgeCorrection($result, $trends);

// 4. Respond — confidence is whatever the math produced, no fake inflation
echo json_encode([
    'status'     => 'ok',
    'prediction' => $result['prediction'],
    'confidence' => $result['confidence'],
    'agreed'     => $result['agreed'],
    'votes'      => $result['votes'],
    'note'       => $result['note']           ?? '',
    'edge'       => $result['edge_correction'] ?? null,
    'detail'     => $result['algorithm_results'],
    'timestamp'  => $result['timestamp'],
]);
