<?php
/**
 * Master-prediction-tool.php — DHANI WIN
 * 3-Level Win System entry point.
 *
 * Request body:
 *   trends      — array of 10 trend objects
 *   level       — 1 (normal), 2 (1st loss retry), 3 (2nd loss master)
 *   prev_wrong  — array of previous wrong prediction strings e.g. ["BIG","BIG"]
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

// ── Read inputs ───────────────────────────────────────────────────
$rawTrends   = $input['trends']     ?? [];
$level       = max(1, min(3, (int)($input['level'] ?? 1)));
$prevWrong   = $input['prev_wrong'] ?? [];   // e.g. ["BIG","BIG"]

// ── Validate ─────────────────────────────────────────────────────
$validation = validateTrends($rawTrends);
if (!$validation['valid']) {
    echo json_encode(['status' => 'error', 'errors' => $validation['errors']]);
    exit;
}
$trends = $validation['cleaned'];

// ── Run calculation with level ────────────────────────────────────
$result = runCalculation($trends, $level, $prevWrong);

// ── Edge correction (all-10-same / last-5-same) ───────────────────
$result = applyEdgeCorrection($result, $trends);

// ── Level-specific label & UI hint ───────────────────────────────
$levelMeta = [
    1 => ['label' => 'BET 1',   'color' => '#00e676', 'hint' => 'First prediction — standard analysis'],
    2 => ['label' => 'BET 2',   'color' => '#f0b90b', 'hint' => 'Deeper analysis after 1st loss — stronger signal'],
    3 => ['label' => 'BET 3 🔥','color' => '#ff1744', 'hint' => 'MASTER RESULT — maximum depth, must win'],
];
$meta = $levelMeta[$level];

// ── Build response ────────────────────────────────────────────────
echo json_encode([
    'status'      => 'ok',
    'prediction'  => $result['prediction'],
    'confidence'  => $result['confidence'],
    'level'       => $level,
    'level_label' => $meta['label'],
    'level_color' => $meta['color'],
    'level_hint'  => $meta['hint'],
    'agreed'      => $result['agreed'],
    'votes'       => $result['votes'],
    'note'        => $result['note']            ?? '',
    'edge'        => $result['edge_correction'] ?? null,
    'detail'      => $result['algorithm_results'],
    'timestamp'   => $result['timestamp'],
]);
