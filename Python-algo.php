<?php
/**
 * Python-algo.php — DHANI WIN
 * Bridge: Executes Python prediction scripts and returns results.
 * Falls back gracefully to PHP logic if Python is unavailable.
 */

define('PYTHON_SCRIPT', __DIR__ . '/predict.py');
define('PYTHON_BIN',    'python3');

// ─────────────────────────────────────────────
//  Run Python algorithm script
// ─────────────────────────────────────────────
function runPythonAlgo(array $trends, bool $retryMode = false): array {
    // Check if Python and script are available
    if (!isPythonAvailable() || !file_exists(PYTHON_SCRIPT)) {
        return runPhpFallback($trends, $retryMode);
    }

    $payload = json_encode(['trends' => $trends, 'retry' => $retryMode]);
    $escaped = escapeshellarg($payload);

    $command = PYTHON_BIN . ' ' . escapeshellarg(PYTHON_SCRIPT) . ' ' . $escaped . ' 2>&1';
    $output  = shell_exec($command);

    if (empty($output)) {
        return runPhpFallback($trends, $retryMode);
    }

    $result = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($result)) {
        return runPhpFallback($trends, $retryMode);
    }

    $result['source'] = 'python';
    return $result;
}

// ─────────────────────────────────────────────
//  Check Python availability
// ─────────────────────────────────────────────
function isPythonAvailable(): bool {
    $check = shell_exec(PYTHON_BIN . ' --version 2>&1');
    return !empty($check) && stripos($check, 'python') !== false;
}

// ─────────────────────────────────────────────
//  PHP Fallback prediction
// ─────────────────────────────────────────────
function runPhpFallback(array $trends, bool $retryMode): array {
    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n === 0) {
        return ['prediction' => 'BIG', 'confidence' => 85, 'source' => 'php_fallback'];
    }

    // Weighted score using position-based decay
    $scoreBig = 0.0; $scoreSmall = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $posWeight = ($i + 1) / $n; // recent = higher weight
        if ($types[$i] === 'BIG')   $scoreBig   += $posWeight;
        else                         $scoreSmall += $posWeight;
    }

    // Last item reversal heuristic
    $last = end($types);
    $streak = 0;
    for ($i = $n - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $streak++;
        else break;
    }
    if ($streak >= 3) {
        $opposite = ($last === 'BIG') ? 'Small' : 'BIG';
        if ($opposite === 'BIG')   $scoreBig   += 0.8;
        else                        $scoreSmall += 0.8;
    }

    $pred = ($scoreBig >= $scoreSmall) ? 'BIG' : 'Small';
    $conf = $retryMode ? 99 : (90 + rand(0, 7));

    return [
        'prediction' => $pred,
        'confidence' => (int) min($conf, $retryMode ? 99 : 98),
        'source'     => 'php_fallback',
        'scores'     => ['BIG' => round($scoreBig, 4), 'Small' => round($scoreSmall, 4)],
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
    echo json_encode(runPythonAlgo($trends, $retryMode));
}
