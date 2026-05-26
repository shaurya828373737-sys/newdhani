<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Python-algo.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * PYTHON BRIDGE: Executes Python prediction scripts and returns results.
 * Falls back gracefully to PHP logic if Python is unavailable.
 * 
 * © DHANI WIN 2025
 */

define('PYTHON_SCRIPT', __DIR__ . '/predict.py');
define('PYTHON_BIN', 'python3');

/**
 * Run Python algorithm script
 */
function runPythonAlgo(array $trends, int $level = 1): array {
    // Check if Python and script are available
    if (!isPythonAvailable() || !file_exists(PYTHON_SCRIPT)) {
        return runPhpFallback($trends, $level);
    }

    $payload = json_encode(['trends' => $trends, 'level' => $level]);
    $escaped = escapeshellarg($payload);
    $command = PYTHON_BIN . ' ' . escapeshellarg(PYTHON_SCRIPT) . ' ' . $escaped . ' 2>&1';
    $output  = @shell_exec($command);

    if (empty($output)) {
        return runPhpFallback($trends, $level);
    }

    $result = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($result)) {
        return runPhpFallback($trends, $level);
    }

    $result['source'] = 'python';
    return $result;
}

/**
 * Check Python availability
 */
function isPythonAvailable(): bool {
    if (!function_exists('shell_exec')) return false;
    $check = @shell_exec(PYTHON_BIN . ' --version 2>&1');
    return !empty($check) && stripos($check, 'python') !== false;
}

/**
 * PHP Fallback prediction with full algorithm support
 */
function runPhpFallback(array $trends, int $level): array {
    $types = array_column($trends, 'type');
    $n = count($types);

    if ($n === 0) {
        return ['prediction' => 'BIG', 'confidence' => 55, 'source' => 'php_fallback'];
    }

    // ── Algorithm 1: Streak Detection ──
    $last = $types[$n - 1];
    $opposite = ($last === 'BIG') ? 'Small' : 'BIG';
    $streak = 0;
    for ($i = $n - 1; $i >= 0 && $types[$i] === $last; $i--) $streak++;

    // ── Algorithm 2: Recency Weighted ──
    $wBig = 0.0; $wSml = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $w = $i + 1;
        $types[$i] === 'BIG' ? $wBig += $w : $wSml += $w;
    }

    // ── Algorithm 3: Pattern ──
    $last3 = array_slice($types, -3);
    $patternPred = 'BIG';
    if (count(array_unique($last3)) === 1) {
        $patternPred = ($last3[0] === 'BIG') ? 'Small' : 'BIG';
    }

    // ── Consensus ──
    $pred1 = ($streak >= 3) ? $opposite : (($wBig >= $wSml) ? 'BIG' : 'Small');
    $pred2 = ($wBig >= $wSml) ? 'BIG' : 'Small';
    $pred3 = $patternPred;

    $votes = ['BIG' => 0, 'Small' => 0];
    $votes[$pred1]++; $votes[$pred2]++; $votes[$pred3]++;

    $finalPred = ($votes['BIG'] >= $votes['Small']) ? 'BIG' : 'Small';
    $agreed = ($votes['BIG'] === 3 || $votes['Small'] === 3);
    
    // Confidence calculation
    $conf = 60;
    if ($agreed) $conf = 75;
    if ($streak >= 4) $conf = 80;
    if ($level === 3) $conf = min($conf + 5, 85);

    return [
        'prediction' => $finalPred,
        'confidence' => $conf,
        'source' => 'php_fallback',
        'agreed' => $agreed,
        'votes' => $votes,
        'streak' => $streak,
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $trends = $input['trends'] ?? [];
    $level = (int)($input['level'] ?? 1);
    echo json_encode(runPythonAlgo($trends, $level));
}
