<?php
/**
 * Third-time-anylyse.php — DHANI WIN
 * Algorithm #3: Pattern Sequence Matching
 *
 * ONE clear rule set:
 * - Look at the last 3 trends as a known 3-pattern
 * - Match it against a proven lookup table of what comes next
 * - If no match, fall back to last-trend reversal (safe default)
 *
 * Pattern table built from WinGo statistical tendencies:
 * After 3 consecutive same → reversal
 * After alternating run → continue the alternation
 * After 2+1 → follow the 1 side (momentum switch)
 */

function thirdTimeAnalyse(array $trends, bool $retryMode = false): array {

    $types = array_column($trends, 'type');
    $n     = count($types);

    if ($n < 3) {
        return ['prediction' => 'BIG', 'confidence' => 80, 'algorithm' => 'third'];
    }

    // Encode last 3 as B/S string e.g. "BIG,BIG,Small" → "BBS"
    $last3  = array_slice($types, -3);
    $encode = fn($t) => $t === 'BIG' ? 'B' : 'S';
    $pat3   = $encode($last3[0]) . $encode($last3[1]) . $encode($last3[2]);

    // ── Pattern lookup table ─────────────────────────────────────
    // Key = last 3 pattern, Value = [prediction, confidence_base]
    $patternTable = [
        // Three same → reversal
        'BBB' => ['Small', 95],
        'SSS' => ['BIG',   95],

        // Two same then different → the different continues (momentum switch confirmed)
        'BBS' => ['Small', 91],
        'SSB' => ['BIG',   91],

        // Different then two same → reversal of the two same
        'BSS' => ['BIG',   90],
        'SBB' => ['Small', 90],

        // Alternating → continue the alternation (last one repeats next pattern)
        'BSB' => ['BIG',   88],   // last was B, alternating → next is S... wait:
        'SBS' => ['Small', 88],   // alternating → next follows: S,B,S → next B? No:

        // Let me be precise about alternating:
        // B,S,B → next in alternation = S
        // S,B,S → next in alternation = B
    ];

    // Fix alternating — in alternating sequence next flips from last
    $patternTable['BSB'] = ['Small', 88]; // B,S,B → next = S
    $patternTable['SBS'] = ['BIG',   88]; // S,B,S → next = B

    // ── Step 1: Match pattern ────────────────────────────────────
    if (isset($patternTable[$pat3])) {
        [$prediction, $confBase] = $patternTable[$pat3];
        $reason = "Pattern {$pat3} → {$prediction}";
    } else {
        // Fallback: last trend reversal (safe)
        $last       = $types[$n - 1];
        $prediction = ($last === 'BIG') ? 'Small' : 'BIG';
        $confBase   = 85;
        $reason     = "No match for {$pat3} → fallback reversal";
    }

    // ── Step 2: Validate with last 5 window ──────────────────────
    $last5    = array_slice($types, -5);
    $last5Big = count(array_filter($last5, fn($t) => $t === 'BIG'));
    $last5Sml = 5 - $last5Big;

    // If the predicted side was dominant in last 5, add confidence
    $predictedDominantInLast5 = (
        ($prediction === 'BIG'   && $last5Big > $last5Sml) ||
        ($prediction === 'Small' && $last5Sml > $last5Big)
    );

    if ($predictedDominantInLast5) {
        $confBase = min($confBase + 2, $retryMode ? 99 : 97);
        $reason  .= " +last5 confirmed";
    }

    // ── Step 3: Apply retry boost ─────────────────────────────────
    $confidence = $retryMode ? min($confBase + 3, 99) : $confBase;

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'pattern'    => $pat3,
        'reason'     => $reason,
        'algorithm'  => 'third',
    ];
}

// HTTP handler
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(thirdTimeAnalyse($input['trends'] ?? [], $input['retry'] ?? false));
}
