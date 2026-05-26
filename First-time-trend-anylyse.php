<?php
/**
 * First-time-trend-anylyse.php — DHANI WIN
 * Algorithm #1: Streak Detection
 * Level 1 → basic streak reversal
 * Level 2 → adds full-history streak scan
 * Level 3 → adds opposite-of-opposite confirmation (master mode)
 */

function firstTimeAnalyse(array $trends, int $level = 1): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 2) {
        return ['prediction' => 'BIG', 'score' => 50, 'algorithm' => 'first', 'reason' => 'Not enough data'];
    }

    $last     = $types[$n - 1];
    $opposite = ($last === 'BIG') ? 'Small' : 'BIG';

    // Trailing streak count
    $streak = 0;
    for ($i = $n - 1; $i >= 0; $i--) {
        if ($types[$i] === $last) $streak++;
        else break;
    }

    $bigCount   = count(array_filter($types, fn($t) => $t === 'BIG'));
    $smallCount = $n - $bigCount;

    // ── LEVEL 1: Normal streak logic ────────────────────────────
    if ($level === 1) {
        if ($streak >= 3) {
            return ['prediction' => $opposite, 'score' => min(60 + $streak * 5, 82),
                    'streak' => $streak, 'reason' => "L1 Streak {$streak}x {$last} → {$opposite}", 'algorithm' => 'first'];
        } elseif ($streak === 2) {
            $maj = ($bigCount > $smallCount) ? 'BIG' : 'Small';
            $score = ($maj === $opposite) ? 66 : 55;
            return ['prediction' => $opposite, 'score' => $score,
                    'streak' => $streak, 'reason' => "L1 Streak 2x {$last} → {$opposite}", 'algorithm' => 'first'];
        } else {
            if ($bigCount === $smallCount) {
                return ['prediction' => $opposite, 'score' => 52,
                        'streak' => $streak, 'reason' => "L1 Tie → reverse last {$last}", 'algorithm' => 'first'];
            }
            $pred  = ($bigCount > $smallCount) ? 'BIG' : 'Small';
            $score = 50 + abs($bigCount - $smallCount) * 4;
            return ['prediction' => $pred, 'score' => min($score, 76),
                    'streak' => $streak, 'reason' => "L1 Majority {$pred}", 'algorithm' => 'first'];
        }
    }

    // ── LEVEL 2: Deeper — count ALL streaks in history ──────────
    if ($level === 2) {
        // Find all streaks and see which side had more reversals
        $reversals = ['BIG' => 0, 'Small' => 0];
        $prev = $types[0]; $run = 1;
        for ($i = 1; $i < $n; $i++) {
            if ($types[$i] === $prev) { $run++; }
            else {
                if ($run >= 2) $reversals[$types[$i]]++; // this side broke the streak
                $prev = $types[$i]; $run = 1;
            }
        }
        // Side that broke streaks more often = likely to break again
        $predByReversal = ($reversals['Small'] >= $reversals['BIG']) ? 'Small' : 'BIG';

        // Also: last streak
        $streakScore = ($streak >= 3) ? 78 : ($streak === 2 ? 68 : 58);
        $streakPred  = ($streak >= 2) ? $opposite : ($bigCount >= $smallCount ? 'BIG' : 'Small');

        // Combine: if both agree → higher score
        if ($predByReversal === $streakPred) {
            return ['prediction' => $streakPred, 'score' => min($streakScore + 6, 84),
                    'streak' => $streak, 'reason' => "L2 Streak+Reversal agree → {$streakPred}", 'algorithm' => 'first'];
        } else {
            // Trust reversal history
            return ['prediction' => $predByReversal, 'score' => 62,
                    'streak' => $streak, 'reason' => "L2 Reversal history → {$predByReversal}", 'algorithm' => 'first'];
        }
    }

    // ── LEVEL 3: MASTER — switch opposite of current prediction ─
    // Logic: if L1 and L2 were both wrong, the pattern is inverted
    // So we take the OPPOSITE of what L1 would say = the side that keeps winning
    if ($level === 3) {
        // L1 and L2 were wrong twice → the game is running the opposite side
        // Master rule: follow the ACTUAL dominant recent side (last 5)
        $last5    = array_slice($types, -5);
        $last5Big = count(array_filter($last5, fn($t) => $t === 'BIG'));
        $last5Sml = 5 - $last5Big;

        // The side that dominated last 5 rounds is the momentum side
        if ($last5Big > $last5Sml) {
            $masterPred = 'BIG';
            $score = 72 + ($last5Big - $last5Sml) * 3;
        } elseif ($last5Sml > $last5Big) {
            $masterPred = 'Small';
            $score = 72 + ($last5Sml - $last5Big) * 3;
        } else {
            // True 50/50 — use last 3 streak reversal
            $masterPred = $opposite;
            $score = 70;
        }

        return ['prediction' => $masterPred, 'score' => min($score, 88),
                'streak' => $streak, 'reason' => "L3 MASTER momentum last5={$last5Big}B/{$last5Sml}S → {$masterPred}", 'algorithm' => 'first'];
    }

    return ['prediction' => $opposite, 'score' => 60, 'streak' => $streak, 'reason' => 'fallback', 'algorithm' => 'first'];
}

if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(firstTimeAnalyse($input['trends'] ?? [], (int)($input['level'] ?? 1)));
}
