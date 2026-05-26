<?php
/**
 * Python-help.php — DHANI WIN
 * Helper utilities for Python bridge operations:
 * environment checks, script generation, temp file management.
 */

// ─────────────────────────────────────────────
//  Generate predict.py script on the fly
// ─────────────────────────────────────────────
function generatePythonScript(): bool {
    $scriptPath = __DIR__ . '/predict.py';

    $code = <<<'PYCODE'
#!/usr/bin/env python3
"""
predict.py — DHANI WIN Python Prediction Engine
Receives JSON trend data, applies ML-style pattern analysis.
"""
import sys
import json
import math
from collections import Counter

def analyse(trends, retry_mode=False):
    types = [t.get('type', 'BIG') for t in trends]
    n = len(types)
    if n == 0:
        return {"prediction": "BIG", "confidence": 85}

    scores = {"BIG": 0.0, "Small": 0.0}

    # 1. Recency-weighted frequency
    for i, t in enumerate(types):
        weight = (i + 1) / n
        scores[t] += weight * 0.6

    # 2. Streak detection
    last = types[-1]
    streak = 0
    for t in reversed(types):
        if t == last:
            streak += 1
        else:
            break
    if streak >= 3:
        opposite = "Small" if last == "BIG" else "BIG"
        scores[opposite] += 0.75

    # 3. Entropy-based confidence
    c = Counter(types)
    total = sum(c.values())
    entropy = -sum((v/total) * math.log2(v/total) for v in c.values() if v > 0)

    if entropy < 0.7:
        dominant = max(c, key=c.get)
        scores[dominant] += (1 - entropy) * 0.5

    # 4. Last 3 pattern
    last3 = types[-3:]
    if len(set(last3)) == 1:
        opposite = "Small" if last3[0] == "BIG" else "BIG"
        scores[opposite] += 0.65
    elif last3[-1] != last3[-2]:
        scores[last3[-1]] += 0.45

    prediction = max(scores, key=scores.get)
    s_total = scores["BIG"] + scores["Small"]
    raw_conf = (scores[prediction] / s_total) if s_total > 0 else 0.5
    confidence = min(int(raw_conf * 35 + 64), 99 if retry_mode else 98)
    confidence = max(confidence, 82)

    return {
        "prediction": prediction,
        "confidence": confidence,
        "scores": {k: round(v, 4) for k, v in scores.items()},
        "entropy": round(entropy, 4),
        "streak": streak,
        "source": "python"
    }

if __name__ == "__main__":
    try:
        data = json.loads(sys.argv[1]) if len(sys.argv) > 1 else {}
        trends = data.get("trends", [])
        retry = data.get("retry", False)
        result = analyse(trends, retry)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e), "prediction": "BIG", "confidence": 85}))
PYCODE;

    return file_put_contents($scriptPath, $code) !== false;
}

// ─────────────────────────────────────────────
//  Check Python environment
// ─────────────────────────────────────────────
function checkPythonEnv(): array {
    $status = [
        'python3_available' => false,
        'python_version'    => null,
        'script_exists'     => file_exists(__DIR__ . '/predict.py'),
        'shell_exec_enabled'=> function_exists('shell_exec'),
    ];

    if ($status['shell_exec_enabled']) {
        $ver = shell_exec('python3 --version 2>&1');
        if (!empty($ver) && stripos($ver, 'python') !== false) {
            $status['python3_available'] = true;
            $status['python_version']    = trim($ver);
        }
    }

    return $status;
}

// ─────────────────────────────────────────────
//  Write temp trend file for Python processing
// ─────────────────────────────────────────────
function writeTempTrends(array $trends): string {
    $tmpFile = sys_get_temp_dir() . '/dhani_trends_' . uniqid() . '.json';
    file_put_contents($tmpFile, json_encode($trends));
    return $tmpFile;
}

// ─────────────────────────────────────────────
//  Clean up temp files older than 5 minutes
// ─────────────────────────────────────────────
function cleanupTempFiles(): void {
    $pattern = sys_get_temp_dir() . '/dhani_trends_*.json';
    $files   = glob($pattern);
    foreach ((array)$files as $file) {
        if (file_exists($file) && (time() - filemtime($file)) > 300) {
            @unlink($file);
        }
    }
}

// ─────────────────────────────────────────────
//  HTTP handler
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'check');

    switch ($action) {
        case 'generate_script':
            $ok = generatePythonScript();
            echo json_encode(['status' => $ok ? 'ok' : 'error', 'message' => $ok ? 'predict.py generated' : 'Failed']);
            break;
        case 'cleanup':
            cleanupTempFiles();
            echo json_encode(['status' => 'ok', 'message' => 'Temp files cleaned']);
            break;
        case 'check':
        default:
            echo json_encode(checkPythonEnv());
            break;
    }
}
