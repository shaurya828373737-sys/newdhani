<?php
/**
 * Python-get-prediction.php — DHANI WIN
 * Final Python prediction retriever: combines PHP Calculation engine
 * with optional Python signal for maximum accuracy.
 */

require_once __DIR__ . '/Python.php';
require_once __DIR__ . '/Calculation.php';

// ─────────────────────────────────────────────
//  Get combined PHP + Python prediction
// ─────────────────────────────────────────────
function getCombinedPrediction(array $trends, bool $retryMode = false): array {
    // Run PHP triple-algorithm calculation
    $phpResult = runCalculation($trends, $retryMode);

    // Run Python prediction
    $pyResult  = getPythonPrediction($trends, $retryMode);

    $phpPred   = $phpResult['prediction']  ?? 'BIG';
    $pyPred    = $pyResult['prediction']   ?? 'BIG';
    $phpConf   = $phpResult['confidence']  ?? 90;
    $pyConf    = $pyResult['confidence']   ?? 85;

    // ── Agreement bonus ──────────────────────────
    if ($phpPred === $pyPred) {
        $finalPrediction = $phpPred;
        $finalConf       = min((int)(($phpConf * 0.65) + ($pyConf * 0.35) + 3), $retryMode ? 99 : 98);
        $agreed          = true;
    } else {
        // PHP algorithms take precedence (higher weight)
        $finalPrediction = $phpPred;
        $finalConf       = (int)(($phpConf * 0.75) + ($pyConf * 0.25));
        $finalConf       = min($finalConf, $retryMode ? 99 : 98);
        $agreed          = false;
    }

    return [
        'prediction'  => $finalPrediction,
        'confidence'  => max($finalConf, 82),
        'agreed'      => $agreed,
        'php_result'  => [
            'prediction' => $phpPred,
            'confidence' => $phpConf,
            'votes'      => $phpResult['votes'] ?? [],
        ],
        'py_result'   => [
            'prediction' => $pyPred,
            'confidence' => $pyConf,
            'source'     => $pyResult['source'] ?? 'unknown',
        ],
        'retry_mode'  => $retryMode,
        'timestamp'   => date('Y-m-d H:i:s'),
    ];
}

// ─────────────────────────────────────────────
//  HTTP handler
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');

    $input     = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends    = $input['trends'] ?? [];
    $retryMode = $input['retry']  ?? false;

    if (empty($trends)) {
        echo json_encode(['error' => 'No trends provided']);
        exit;
    }

    echo json_encode(getCombinedPrediction($trends, $retryMode));
}
