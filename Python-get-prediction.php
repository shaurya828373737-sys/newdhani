<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Python-get-prediction.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Final Python prediction retriever: combines PHP Calculation engine
 * with optional Python signal for cross-validation.
 * 
 * © DHANI WIN 2025
 */

require_once __DIR__ . '/Python.php';
require_once __DIR__ . '/Calculation.php';

/**
 * Get combined PHP + Python prediction
 */
function getCombinedPrediction(array $trends, int $level = 1, array $prevWrong = []): array {
    // Run PHP triple-algorithm calculation
    $phpResult = runCalculation($trends, $level, $prevWrong);

    // Run Python prediction
    $pyResult = getPythonPrediction($trends, $level);

    $phpPred = $phpResult['prediction'] ?? 'BIG';
    $pyPred = $pyResult['prediction'] ?? 'BIG';
    $phpConf = $phpResult['confidence'] ?? 70;
    $pyConf = $pyResult['confidence'] ?? 65;

    // Agreement analysis
    if ($phpPred === $pyPred) {
        // Both engines agree → boost confidence
        $finalPrediction = $phpPred;
        $finalConf = (int)(($phpConf * 0.7) + ($pyConf * 0.3) + 5);
        $finalConf = min($finalConf, 88);
        $agreed = true;
        $note = "PHP + Python engines AGREE → {$finalPrediction}";
    } else {
        // Disagreement → trust PHP (it has 3 algorithms)
        $finalPrediction = $phpPred;
        $finalConf = (int)(($phpConf * 0.8) + ($pyConf * 0.2));
        $finalConf = min($finalConf, 82);
        $agreed = false;
        $note = "Engines disagree (PHP={$phpPred}, Py={$pyPred}) → trust PHP";
    }

    $finalConf = max($finalConf, 55);

    return [
        'prediction' => $finalPrediction,
        'confidence' => $finalConf,
        'agreed' => $agreed,
        'note' => $note,
        'php_result' => [
            'prediction' => $phpPred,
            'confidence' => $phpConf,
            'votes' => $phpResult['votes'] ?? [],
            'level' => $level,
        ],
        'py_result' => [
            'prediction' => $pyPred,
            'confidence' => $pyConf,
            'source' => $pyResult['source'] ?? 'unknown',
        ],
        'level' => $level,
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends = $input['trends'] ?? [];
    $level = (int)($input['level'] ?? 1);
    $prevWrong = $input['prev_wrong'] ?? [];

    if (empty($trends)) {
        echo json_encode(['error' => 'No trends provided']);
        exit;
    }

    echo json_encode(getCombinedPrediction($trends, $level, $prevWrong), JSON_PRETTY_PRINT);
}
