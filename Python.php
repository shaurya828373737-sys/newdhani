<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Python.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Orchestrator: Initialises Python environment, generates script
 * if missing, and exposes a unified prediction interface.
 * 
 * © DHANI WIN 2025
 */

require_once __DIR__ . '/Python-help.php';
require_once __DIR__ . '/Python-algo.php';

/**
 * Boot Python environment
 */
function bootPython(): array {
    $env = checkPythonEnv();

    // Auto-generate predict.py if missing
    if (!$env['script_exists']) {
        $generated = generatePythonScript();
        $env['script_generated'] = $generated;
        $env['script_exists'] = $generated;
    }

    return $env;
}

/**
 * Get prediction via Python (with auto-boot)
 */
function getPythonPrediction(array $trends, int $level = 1): array {
    bootPython();
    return runPythonAlgo($trends, $level);
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends = $input['trends'] ?? [];
    $level = (int)($input['level'] ?? 1);
    $action = $input['action'] ?? ($_GET['action'] ?? 'predict');

    switch ($action) {
        case 'boot':
            echo json_encode(bootPython());
            break;
        case 'predict':
        default:
            if (empty($trends)) {
                echo json_encode(['error' => 'No trends provided']);
                exit;
            }
            echo json_encode(getPythonPrediction($trends, $level));
            break;
    }
}
