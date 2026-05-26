<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * Python-help.php — DHANI WIN
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Helper utilities for Python bridge operations:
 * - Environment checks
 * - Script generation
 * - Temp file management
 * 
 * © DHANI WIN 2025
 */

/**
 * Generate predict.py script on the fly
 */
function generatePythonScript(): bool {
    $scriptPath = __DIR__ . '/predict.py';

    $code = <<<'PYCODE'
#!/usr/bin/env python3
"""
predict.py — DHANI WIN Python Prediction Engine v2.0
Advanced pattern analysis with statistical methods.
"""
import sys
import json
import math
from collections import Counter

def analyse(trends, level=1):
    types = [t.get('type', 'BIG') for t in trends]
    n = len(types)
    
    if n == 0:
        return {"prediction": "BIG", "confidence": 55, "source": "python"}
    
    scores = {"BIG": 0.0, "Small": 0.0}
    
    # 1. Recency-weighted frequency (newer = higher weight)
    for i, t in enumerate(types):
        weight = (i + 1) / n
        scores[t] += weight * 0.5
    
    # 2. Streak detection with reversal
    last = types[-1]
    streak = 0
    for t in reversed(types):
        if t == last:
            streak += 1
        else:
            break
    
    if streak >= 3:
        opposite = "Small" if last == "BIG" else "BIG"
        scores[opposite] += 0.7 + (streak * 0.1)
    
    # 3. Entropy-based pattern detection
    c = Counter(types)
    total = sum(c.values())
    entropy = -sum((v/total) * math.log2(v/total) for v in c.values() if v > 0)
    
    if entropy < 0.8:  # Low entropy = one side dominant
        dominant = max(c, key=c.get)
        # Expect reversal when heavily imbalanced
        opposite = "Small" if dominant == "BIG" else "BIG"
        scores[opposite] += (1 - entropy) * 0.4
    
    # 4. Last 3 pattern analysis
    if n >= 3:
        last3 = types[-3:]
        if len(set(last3)) == 1:  # All same
            opposite = "Small" if last3[0] == "BIG" else "BIG"
            scores[opposite] += 0.6
        elif last3[-1] != last3[-2]:  # Recent switch
            scores[last3[-1]] += 0.3
    
    # 5. Level-specific adjustments
    if level >= 2:
        # At higher levels, trust streak reversal more
        if streak >= 2:
            opposite = "Small" if last == "BIG" else "BIG"
            scores[opposite] += 0.2 * level
    
    # Calculate final prediction
    prediction = max(scores, key=scores.get)
    s_total = scores["BIG"] + scores["Small"]
    raw_conf = (scores[prediction] / s_total) if s_total > 0 else 0.5
    
    # Confidence: 55-88 range
    confidence = min(int(raw_conf * 40 + 55), 88)
    confidence = max(confidence, 55)
    
    return {
        "prediction": prediction,
        "confidence": confidence,
        "scores": {k: round(v, 4) for k, v in scores.items()},
        "entropy": round(entropy, 4) if n > 1 else 0,
        "streak": streak,
        "level": level,
        "source": "python"
    }

if __name__ == "__main__":
    try:
        data = json.loads(sys.argv[1]) if len(sys.argv) > 1 else {}
        trends = data.get("trends", [])
        level = data.get("level", 1)
        result = analyse(trends, level)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e), "prediction": "BIG", "confidence": 55, "source": "python_error"}))
PYCODE;

    return @file_put_contents($scriptPath, $code) !== false;
}

/**
 * Check Python environment
 */
function checkPythonEnv(): array {
    $status = [
        'python3_available' => false,
        'python_version' => null,
        'script_exists' => file_exists(__DIR__ . '/predict.py'),
        'shell_exec_enabled' => function_exists('shell_exec'),
    ];

    if ($status['shell_exec_enabled']) {
        $ver = @shell_exec('python3 --version 2>&1');
        if (!empty($ver) && stripos($ver, 'python') !== false) {
            $status['python3_available'] = true;
            $status['python_version'] = trim($ver);
        }
    }

    return $status;
}

/**
 * Write temp trend file for Python processing
 */
function writeTempTrends(array $trends): string {
    $tmpFile = sys_get_temp_dir() . '/dhani_trends_' . uniqid() . '.json';
    @file_put_contents($tmpFile, json_encode($trends));
    return $tmpFile;
}

/**
 * Clean up temp files older than 5 minutes
 */
function cleanupTempFiles(): int {
    $pattern = sys_get_temp_dir() . '/dhani_trends_*.json';
    $files = glob($pattern);
    $cleaned = 0;
    
    foreach ((array)$files as $file) {
        if (file_exists($file) && (time() - filemtime($file)) > 300) {
            if (@unlink($file)) $cleaned++;
        }
    }
    
    return $cleaned;
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? ($_GET['action'] ?? 'check');

    switch ($action) {
        case 'generate_script':
            $ok = generatePythonScript();
            echo json_encode(['status' => $ok ? 'ok' : 'error', 'message' => $ok ? 'predict.py generated' : 'Failed']);
            break;
        case 'cleanup':
            $count = cleanupTempFiles();
            echo json_encode(['status' => 'ok', 'cleaned' => $count]);
            break;
        case 'check':
        default:
            echo json_encode(checkPythonEnv());
            break;
    }
}
