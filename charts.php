<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * charts.php — DHANI WIN v2.0
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * VISUAL PATTERN GUIDE & LIVE ALGORITHM DEMONSTRATION
 * 
 * This page shows:
 * - How the triple-algorithm system detects patterns
 * - When to bet opposite (reversal signals)
 * - Live examples with real engine calculations
 * - Profit strategy guide
 * 
 * © DHANI WIN 2025
 */

require_once __DIR__ . '/Calculation.php';
require_once __DIR__ . '/Help-to-take-accurate.php';

// ═══════════════════════════════════════════════════════════════════════════════
// PATTERN EXAMPLES — Each shows WHY the tool bets a certain way
// ═══════════════════════════════════════════════════════════════════════════════
$examples = [
    [
        'label'    => 'Pattern 1: Triple Streak (BBB)',
        'trends'   => ['BIG','BIG','BIG','Small','BIG','Small','BIG','BIG','BIG','BIG'],
        'explain'  => 'Last 4 are all BIG. Streak reversal rule fires → BET Small',
        'expected' => 'Small',
        'strength' => 'STRONG',
    ],
    [
        'label'    => 'Pattern 2: Double at End',
        'trends'   => ['Small','BIG','Small','BIG','Small','BIG','BIG','Small','BIG','BIG'],
        'explain'  => 'Last 2 are BIG. Weak reversal signal → lean to Small',
        'expected' => 'Small',
        'strength' => 'MEDIUM',
    ],
    [
        'label'    => 'Pattern 3: Perfect Alternating',
        'trends'   => ['Small','BIG','Small','BIG','Small','BIG','Small','BIG','Small','BIG'],
        'explain'  => 'Perfect alternation. Last was BIG → next is Small',
        'expected' => 'Small',
        'strength' => 'MEDIUM',
    ],
    [
        'label'    => 'Pattern 4: All Same (Max Streak)',
        'trends'   => ['BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG'],
        'explain'  => 'All 10 BIG — EDGE CORRECTION fires → FORCE bet Small',
        'expected' => 'Small',
        'strength' => 'MAX',
    ],
    [
        'label'    => 'Pattern 5: Small Dominant',
        'trends'   => ['Small','Small','BIG','Small','Small','Small','BIG','Small','Small','Small'],
        'explain'  => 'Heavy Small weight (8/10). Reversal expected → BIG',
        'expected' => 'BIG',
        'strength' => 'STRONG',
    ],
    [
        'label'    => 'Pattern 6: Recent Switch (BSS)',
        'trends'   => ['BIG','Small','BIG','Small','BIG','Small','BIG','BIG','Small','Small'],
        'explain'  => 'Last 3 = BSS → pattern table says BIG next',
        'expected' => 'BIG',
        'strength' => 'MEDIUM',
    ],
    [
        'label'    => 'Pattern 7: Balanced 50/50',
        'trends'   => ['BIG','Small','BIG','Small','BIG','Small','BIG','Small','BIG','Small'],
        'explain'  => 'Perfectly balanced — last was Small → next = BIG',
        'expected' => 'BIG',
        'strength' => 'MEDIUM',
    ],
    [
        'label'    => 'Pattern 8: SSS Streak',
        'trends'   => ['BIG','BIG','Small','BIG','BIG','Small','Small','Small','Small','Small'],
        'explain'  => 'Last 5 all Small. Streak reversal + edge correction → BET BIG',
        'expected' => 'BIG',
        'strength' => 'STRONG',
    ],
];

// Run each example through the live engine
$results = [];
foreach ($examples as $ex) {
    $trendArr = [];
    foreach ($ex['trends'] as $i => $t) {
        $trendArr[] = ['type' => $t, 'number' => $i + 1];
    }
    $calc = runCalculation($trendArr, 1, []);
    $final = applyEdgeCorrection($calc, $trendArr);
    $results[] = array_merge($ex, [
        'live_prediction' => $final['prediction'],
        'live_confidence' => $final['confidence'],
        'live_agreed'     => $final['agreed'] ?? false,
        'live_votes'      => $final['votes'] ?? ['BIG' => 0, 'Small' => 0],
        'live_detail'     => $final['algorithm_results'] ?? [],
        'edge'            => $final['edge_correction'] ?? null,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DHANI WIN — Pattern Charts & Strategy Guide</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Rajdhani',sans-serif;
  background:#08080f;
  color:#e0e0f0;
  min-height:100vh;
  padding-bottom:4rem;
  line-height:1.5;
}
body::after{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
  background-image:
    linear-gradient(rgba(240,185,11,0.02) 1px,transparent 1px),
    linear-gradient(90deg,rgba(240,185,11,0.02) 1px,transparent 1px);
  background-size:50px 50px;
}
.wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 1.2rem}

/* TOP BAR */
.topbar{
  background:rgba(8,8,15,0.98);
  border-bottom:1px solid rgba(240,185,11,0.3);
  padding:0 1.5rem;height:56px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
}
.logo{font-family:'Orbitron',monospace;font-size:1.15rem;font-weight:900;
  color:#f0b90b;letter-spacing:2px;text-shadow:0 0 18px rgba(240,185,11,0.5)}
.logo span{color:#fff}
.back-btn{
  text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:0.82rem;
  color:#a0a0c0;background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.1);border-radius:20px;
  padding:6px 16px;transition:all 0.2s;
}
.back-btn:hover{color:#f0b90b;border-color:rgba(240,185,11,0.4);background:rgba(240,185,11,0.08)}

/* PAGE TITLE */
.page-title{text-align:center;padding:3rem 1rem 1rem}
.page-title h1{
  font-family:'Orbitron',monospace;font-size:clamp(1.6rem,4vw,2.6rem);
  font-weight:900;color:#fff;letter-spacing:2px;
}
.page-title h1 span{color:#f0b90b}
.page-title p{color:#a0a0c0;margin-top:0.7rem;font-size:1.05rem;line-height:1.6;max-width:700px;margin-inline:auto}

/* SECTION TITLE */
.section-title{
  font-family:'Orbitron',monospace;font-size:0.95rem;font-weight:700;
  color:#f0b90b;letter-spacing:2px;text-transform:uppercase;
  margin:2.5rem 0 1.2rem;padding-bottom:0.6rem;
  border-bottom:1px solid rgba(240,185,11,0.25);
}
</style>
</head>
<body>

<div class="topbar">
  <div class="logo">DHANI<span>WIN</span></div>
  <a href="index.html" class="back-btn">← Back to Tool</a>
</div>

<div class="wrap">
  <div class="page-title">
    <h1>Pattern <span>Charts</span></h1>
    <p>See exactly how the triple-algorithm engine detects patterns and when to place your bet. All results below are calculated live by the real prediction engine.</p>
  </div>


<style>
/* HOW IT WORKS */
.flow{display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:2rem}
.flow-step{
  flex:1;min-width:200px;
  background:rgba(15,15,26,0.95);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:16px;padding:1.2rem;
  position:relative;
}
.flow-step::after{
  content:'→';
  position:absolute;right:-20px;top:50%;transform:translateY(-50%);
  color:rgba(240,185,11,0.4);font-size:1.3rem;font-weight:700;
}
.flow-step:last-child::after{display:none}
.flow-num{
  font-family:'Orbitron',monospace;font-size:1.6rem;font-weight:900;
  color:rgba(240,185,11,0.25);margin-bottom:0.5rem;
}
.flow-step h4{font-family:'Orbitron',monospace;font-size:0.8rem;color:#f0b90b;letter-spacing:1px;margin-bottom:0.5rem}
.flow-step p{font-size:0.85rem;color:#a0a0c0;line-height:1.5}

/* PATTERN CARDS */
.pattern-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.3rem;margin-bottom:2rem}
.pat-card{
  background:rgba(13,13,22,0.96);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:18px;overflow:hidden;
  transition:border-color 0.2s;
}
.pat-card:hover{border-color:rgba(240,185,11,0.3)}
.pat-head{
  padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;
  border-bottom:1px solid rgba(255,255,255,0.06);
}
.pat-label{font-family:'Orbitron',monospace;font-size:0.78rem;font-weight:700;color:#f0b90b;letter-spacing:1px}
.pat-strength{
  font-size:0.68rem;font-weight:700;letter-spacing:1px;border-radius:20px;padding:3px 10px;
}
.str-MAX{background:rgba(255,23,68,0.15);color:#ff1744;border:1px solid rgba(255,23,68,0.35)}
.str-STRONG{background:rgba(240,185,11,0.12);color:#f0b90b;border:1px solid rgba(240,185,11,0.35)}
.str-MEDIUM{background:rgba(160,160,192,0.1);color:#a0a0c0;border:1px solid rgba(160,160,192,0.25)}

.pat-body{padding:1.1rem 1.2rem}
.trend-bar{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:1rem}
.tb{
  padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;letter-spacing:0.5px;
}
.tb.b{background:rgba(0,230,118,0.12);border:1px solid rgba(0,230,118,0.35);color:#00e676}
.tb.s{background:rgba(255,23,68,0.12);border:1px solid rgba(255,23,68,0.35);color:#ff5577}
.tb.last{border-width:2px;transform:scale(1.08)}

.pat-explain{font-size:0.85rem;color:#a0a0c0;line-height:1.5;margin-bottom:1rem}

.pat-result{
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(255,255,255,0.03);border-radius:12px;padding:0.8rem 1rem;
}
.pr-left{display:flex;flex-direction:column;gap:4px}
.pr-label{font-size:0.68rem;color:#5a5a7a;letter-spacing:1px;text-transform:uppercase}
.pr-bet{font-family:'Orbitron',monospace;font-size:1.2rem;font-weight:900;letter-spacing:2px}
.pr-bet.big{color:#00e676;text-shadow:0 0 14px rgba(0,230,118,0.45)}
.pr-bet.sml{color:#ff5577;text-shadow:0 0 14px rgba(255,23,68,0.45)}
.pr-conf{
  font-family:'Orbitron',monospace;font-size:1.1rem;font-weight:700;
  color:#f0b90b;text-align:right;
}
.pr-conf small{display:block;font-family:'Rajdhani',sans-serif;font-size:0.68rem;
  color:#5a5a7a;font-weight:400;letter-spacing:1px}
.pr-votes{font-size:0.72rem;color:#5a5a7a;text-align:right;margin-top:4px}
.pr-votes .ag{color:#00e676;font-weight:700}
.pr-votes .dis{color:#ff5577;font-weight:700}

.algo-row{display:flex;gap:7px;margin-top:0.8rem;flex-wrap:wrap}
.algo-chip{
  flex:1;min-width:85px;background:rgba(255,255,255,0.03);
  border:1px solid rgba(255,255,255,0.07);border-radius:10px;
  padding:6px 9px;text-align:center;
}
.algo-chip .ac-name{font-size:0.65rem;color:#5a5a7a;letter-spacing:1px;text-transform:uppercase}
.algo-chip .ac-pred{font-family:'Orbitron',monospace;font-size:0.8rem;font-weight:700;margin:3px 0}
.algo-chip .ac-pred.big{color:#00e676}
.algo-chip .ac-pred.sml{color:#ff5577}
.algo-chip .ac-score{font-size:0.68rem;color:#a0a0c0}

.edge-tag{
  margin-top:0.7rem;background:rgba(255,23,68,0.08);
  border:1px solid rgba(255,23,68,0.25);border-radius:10px;
  padding:6px 11px;font-size:0.78rem;color:#ff5577;
}

/* PROFIT GUIDE */
.profit-guide{
  background:rgba(15,15,26,0.95);border:1px solid rgba(0,230,118,0.25);
  border-radius:18px;padding:1.6rem;margin-bottom:2rem;
  border-top:3px solid #00e676;
}
.profit-guide h3{
  font-family:'Orbitron',monospace;font-size:0.9rem;font-weight:700;
  color:#00e676;letter-spacing:2px;margin-bottom:1.1rem;
}
.profit-row{
  display:flex;align-items:flex-start;gap:1.1rem;padding:0.8rem 0;
  border-bottom:1px solid rgba(255,255,255,0.05);
}
.profit-row:last-child{border-bottom:none}
.profit-icon{font-size:1.4rem;flex-shrink:0;margin-top:2px}
.profit-text h5{color:#fff;font-size:0.95rem;font-weight:700;margin-bottom:4px}
.profit-text p{color:#a0a0c0;font-size:0.85rem;line-height:1.55}
.tag{display:inline-block;font-size:0.72rem;font-weight:700;border-radius:4px;padding:2px 8px;margin-right:5px}
.tag-do{background:rgba(0,230,118,0.15);color:#00e676}
.tag-skip{background:rgba(255,23,68,0.12);color:#ff5577}
.tag-wait{background:rgba(240,185,11,0.12);color:#f0b90b}

.page-footer{text-align:center;padding:2.5rem;color:#3a3a5a;font-size:0.78rem}

@media(max-width:600px){
  .flow-step::after{display:none}
  .topbar{padding:0 1rem}
  .pattern-grid{grid-template-columns:1fr}
}
</style>



  <!-- HOW IT WORKS -->
  <div class="section-title">How the Tool Works — Step by Step</div>
  <div class="flow">
    <div class="flow-step">
      <div class="flow-num">01</div>
      <h4>Enter 10 Trends</h4>
      <p>From the game history, bottom to top: BIG or Small for each of the last 10 rounds.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">02</div>
      <h4>3 Algorithms Run</h4>
      <p>Streak detection, recency weighting, and pattern matching analyze the trends independently.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">03</div>
      <h4>Consensus Vote</h4>
      <p>3/3 agree = high confidence. 2/3 agree = medium. Score is averaged from agreeing algorithms.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">04</div>
      <h4>Edge Override</h4>
      <p>If last 5 or all 10 are the same, the tool forces a reversal — this is the strongest signal.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">05</div>
      <h4>Place Your Bet</h4>
      <p>The prediction shows what the next round is likely to be. Bet on that side for profit.</p>
    </div>
  </div>

  <!-- LIVE PATTERN EXAMPLES -->
  <div class="section-title">Live Pattern Examples — Real Engine Output</div>
  <div class="pattern-grid">
<?php foreach ($results as $r):
    $isBig    = $r['live_prediction'] === 'BIG';
    $betClass = $isBig ? 'big' : 'sml';
    $betLabel = $isBig ? '⬆ BET BIG' : '⬇ BET Small';
    $agreed   = $r['live_agreed'];
    $votes    = $r['live_votes'];
    $detail   = $r['live_detail'];
    $str      = $r['strength'];
?>
    <div class="pat-card">
      <div class="pat-head">
        <span class="pat-label"><?= htmlspecialchars($r['label']) ?></span>
        <span class="pat-strength str-<?= $str ?>"><?= $str ?></span>
      </div>
      <div class="pat-body">
        <div class="trend-bar">
          <?php foreach ($r['trends'] as $idx => $t):
            $isLast  = ($idx === count($r['trends']) - 1);
            $tbClass = ($t === 'BIG' ? 'b' : 's') . ($isLast ? ' last' : '');
          ?>
          <span class="tb <?= $tbClass ?>"><?= $t === 'BIG' ? '⬆B' : '⬇S' ?></span>
          <?php endforeach ?>
        </div>
        <div class="pat-explain"><?= htmlspecialchars($r['explain']) ?></div>
        <div class="pat-result">
          <div class="pr-left">
            <span class="pr-label">Next Prediction</span>
            <span class="pr-bet <?= $betClass ?>"><?= $betLabel ?></span>
          </div>
          <div>
            <div class="pr-conf">
              <?= $r['live_confidence'] ?>%
              <small>Confidence</small>
            </div>
            <div class="pr-votes">
              Votes: <span class="<?= $agreed ? 'ag' : 'dis' ?>">
                BIG <?= $votes['BIG'] ?>/3 · Small <?= $votes['Small'] ?>/3
              </span>
            </div>
          </div>
        </div>
        <?php if (!empty($detail)): ?>
        <div class="algo-row">
          <?php foreach ($detail as $name => $algo):
            $ac = ($algo['prediction'] ?? 'BIG') === 'BIG' ? 'big' : 'sml';
          ?>
          <div class="algo-chip">
            <div class="ac-name"><?= ucfirst($name) ?></div>
            <div class="ac-pred <?= $ac ?>"><?= ($algo['prediction'] ?? 'BIG') === 'BIG' ? '⬆B' : '⬇S' ?></div>
            <div class="ac-score"><?= $algo['score'] ?? '—' ?>pts</div>
          </div>
          <?php endforeach ?>
        </div>
        <?php endif ?>
        <?php if (!empty($r['edge'])): ?>
        <div class="edge-tag">🔴 Edge Override: <?= htmlspecialchars($r['edge']) ?></div>
        <?php endif ?>
      </div>
    </div>
<?php endforeach ?>
  </div>

  <!-- PROFIT STRATEGY -->
  <div class="section-title">Profit Strategy — How to Win</div>
  <div class="profit-guide">
    <h3>💰 PROFIT BETTING GUIDE</h3>
    <div class="profit-row">
      <div class="profit-icon">✅</div>
      <div class="profit-text">
        <h5>Only bet when confidence ≥ 65%</h5>
        <p><span class="tag tag-do">DO</span> Wait for 65%+ confidence. Below that, the pattern is too weak. Patience = profit.</p>
      </div>
    </div>
    <div class="profit-row">
      <div class="profit-icon">🔴</div>
      <div class="profit-text">
        <h5>Bet opposite when streak ≥ 3</h5>
        <p><span class="tag tag-do">DO</span> When 3+ consecutive same results appear, the tool bets opposite. This is the most reliable signal.</p>
      </div>
    </div>
    <div class="profit-row">
      <div class="profit-icon">⚡</div>
      <div class="profit-text">
        <h5>3/3 algorithms agree = best bet</h5>
        <p><span class="tag tag-do">DO</span> When all 3 algorithms agree, increase your bet size. This is the strongest signal.</p>
      </div>
    </div>
    <div class="profit-row">
      <div class="profit-icon">🚫</div>
      <div class="profit-text">
        <h5>Skip when confidence is below 60%</h5>
        <p><span class="tag tag-skip">SKIP</span> A score of 50-59% means no clear pattern. Wait for the next round.</p>
      </div>
    </div>
    <div class="profit-row">
      <div class="profit-icon">🔄</div>
      <div class="profit-text">
        <h5>If wrong — re-enter trends (Level 2/3)</h5>
        <p><span class="tag tag-wait">RETRY</span> Click NO after a wrong prediction. The tool escalates to deeper analysis automatically.</p>
      </div>
    </div>
  </div>

</div>

<div class="page-footer">
  DHANI WIN v2.0 — Pattern Charts powered by Triple-Algorithm Engine — For educational purposes only
</div>

</body>
</html>
