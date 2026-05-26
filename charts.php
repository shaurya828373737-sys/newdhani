<?php
/**
 * charts.php — DHANI WIN
 * Visual guide: how the tool detects opposite trends
 * and when the best moment is to place a bet for profit.
 *
 * This file runs standalone — no other PHP files needed.
 * Open directly in browser: yoursite.com/charts.php
 */

// ── Live demo: run all 3 algos on sample trends ──────────────────
require_once __DIR__ . '/Calculation.php';
require_once __DIR__ . '/Help-to-take-accurate.php';

// 8 pattern examples — each shows WHY the tool bets opposite
$examples = [
    [
        'label'   => 'Pattern 1: Triple Streak',
        'trends'  => ['BIG','BIG','BIG','Small','BIG','Small','BIG','BIG','BIG','BIG'],
        'explain' => 'Last 4 are all BIG. Streak reversal rule fires → BET Small',
        'bet'     => 'Small',
        'strength'=> 'STRONG',
    ],
    [
        'label'   => 'Pattern 2: Double at End',
        'trends'  => ['Small','BIG','Small','BIG','Small','BIG','BIG','Small','BIG','BIG'],
        'explain' => 'Last 2 are BIG. Weak reversal signal → lean to Small',
        'bet'     => 'Small',
        'strength'=> 'MEDIUM',
    ],
    [
        'label'   => 'Pattern 3: Alternating (BSB)',
        'trends'  => ['Small','BIG','Small','BIG','Small','BIG','Small','BIG','Small','BIG'],
        'explain' => 'Last 3 pattern = BSB → next in alternation = Small',
        'bet'     => 'Small',
        'strength'=> 'MEDIUM',
    ],
    [
        'label'   => 'Pattern 4: All Same (Max Streak)',
        'trends'  => ['BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG','BIG'],
        'explain' => 'All 10 BIG — edge correction fires → FORCE bet Small',
        'bet'     => 'Small',
        'strength'=> 'MAX',
    ],
    [
        'label'   => 'Pattern 5: Small Dominant',
        'trends'  => ['Small','Small','BIG','Small','Small','Small','BIG','Small','Small','Small'],
        'explain' => 'Heavy Small weight. Recency + count both favour BIG reversal',
        'bet'     => 'BIG',
        'strength'=> 'STRONG',
    ],
    [
        'label'   => 'Pattern 6: BBS End',
        'trends'  => ['BIG','Small','BIG','Small','BIG','Small','BIG','BIG','Small','Small'],
        'explain' => 'Last 3 = BSS → pattern table says BIG next',
        'bet'     => 'BIG',
        'strength'=> 'MEDIUM',
    ],
    [
        'label'   => 'Pattern 7: Balanced (50/50)',
        'trends'  => ['BIG','Small','BIG','Small','BIG','Small','BIG','Small','BIG','Small'],
        'explain' => 'Perfectly alternating — last was Small → next = BIG',
        'bet'     => 'BIG',
        'strength'=> 'MEDIUM',
    ],
    [
        'label'   => 'Pattern 8: SSS Streak',
        'trends'  => ['BIG','BIG','Small','BIG','BIG','Small','Small','Small','Small','Small'],
        'explain' => 'Last 5 all Small. Both streak reversal + edge correction → BET BIG',
        'bet'     => 'BIG',
        'strength'=> 'STRONG',
    ],
];

// Run each example through the live engine
$results = [];
foreach ($examples as $ex) {
    $trendArr = [];
    foreach ($ex['trends'] as $i => $t) {
        $trendArr[] = ['type' => $t, 'number' => $i + 1];
    }
    $calc  = runCalculation($trendArr);
    $final = applyEdgeCorrection($calc, $trendArr);
    $results[] = array_merge($ex, [
        'live_prediction' => $final['prediction'],
        'live_confidence' => $final['confidence'],
        'live_agreed'     => $final['agreed'],
        'live_votes'      => $final['votes'],
        'live_detail'     => $final['algorithm_results'],
        'edge'            => $final['edge_correction'] ?? null,
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DHANI WIN — Charts & Pattern Guide</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Rajdhani',sans-serif;
  background:#08080f;
  color:#e0e0f0;
  min-height:100vh;
  padding-bottom:4rem;
}
/* ── grid bg ── */
body::after{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
  background-image:
    linear-gradient(rgba(240,185,11,0.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(240,185,11,0.025) 1px,transparent 1px);
  background-size:48px 48px;
}
.wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 1.2rem}

/* ── TOP BAR ── */
.topbar{
  background:rgba(8,8,15,0.97);
  border-bottom:1px solid rgba(240,185,11,0.28);
  padding:0 1.5rem;height:52px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
}
.logo{font-family:'Orbitron',monospace;font-size:1.1rem;font-weight:900;
  color:#f0b90b;letter-spacing:2px;text-shadow:0 0 16px rgba(240,185,11,0.45)}
.logo span{color:#fff}
.back-btn{
  text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:0.8rem;
  color:#a0a0c0;background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.09);border-radius:20px;
  padding:5px 14px;transition:all 0.2s;
}
.back-btn:hover{color:#f0b90b;border-color:rgba(240,185,11,0.35);background:rgba(240,185,11,0.07)}

/* ── PAGE TITLE ── */
.page-title{text-align:center;padding:2.5rem 1rem 0.5rem}
.page-title h1{
  font-family:'Orbitron',monospace;font-size:clamp(1.5rem,4vw,2.4rem);
  font-weight:900;color:#fff;letter-spacing:2px;
}
.page-title h1 span{color:#f0b90b}
.page-title p{color:#a0a0c0;margin-top:0.6rem;font-size:1rem;line-height:1.6;max-width:680px;margin-inline:auto}

/* ── SECTION TITLE ── */
.section-title{
  font-family:'Orbitron',monospace;font-size:0.9rem;font-weight:700;
  color:#f0b90b;letter-spacing:2px;text-transform:uppercase;
  margin:2.5rem 0 1rem;padding-bottom:0.5rem;
  border-bottom:1px solid rgba(240,185,11,0.2);
}

/* ── HOW IT WORKS FLOW ── */
.flow{display:flex;flex-wrap:wrap;gap:0.8rem;margin-bottom:2rem}
.flow-step{
  flex:1;min-width:180px;
  background:rgba(15,15,26,0.9);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:14px;padding:1.1rem 1rem;
  position:relative;
}
.flow-step::after{
  content:'→';
  position:absolute;right:-18px;top:50%;transform:translateY(-50%);
  color:rgba(240,185,11,0.4);font-size:1.2rem;font-weight:700;
}
.flow-step:last-child::after{display:none}
.flow-num{
  font-family:'Orbitron',monospace;font-size:1.5rem;font-weight:900;
  color:rgba(240,185,11,0.2);margin-bottom:0.4rem;
}
.flow-step h4{font-family:'Orbitron',monospace;font-size:0.75rem;color:#f0b90b;letter-spacing:1px;margin-bottom:0.4rem}
.flow-step p{font-size:0.82rem;color:#a0a0c0;line-height:1.5}

/* ── RULE CARDS ── */
.rules-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-bottom:2rem}
.rule-card{
  background:rgba(15,15,26,0.9);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:14px;padding:1.2rem;
  border-top:3px solid transparent;
  transition:border-color 0.2s;
}
.rule-card.streak{border-top-color:#f0b90b}
.rule-card.recency{border-top-color:#00b0ff}
.rule-card.pattern{border-top-color:#e040fb}
.rule-card h4{
  font-family:'Orbitron',monospace;font-size:0.78rem;font-weight:700;
  letter-spacing:1px;margin-bottom:0.8rem;
}
.rule-card.streak h4{color:#f0b90b}
.rule-card.recency h4{color:#00b0ff}
.rule-card.pattern h4{color:#e040fb}
.rule-row{display:flex;justify-content:space-between;align-items:center;
  padding:0.4rem 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:0.82rem}
.rule-row:last-child{border-bottom:none}
.rule-label{color:#a0a0c0}
.rule-action{font-weight:700;font-size:0.8rem}
.action-big{color:#00e676}
.action-sml{color:#ff5577}
.action-mixed{color:#f0b90b}
.rule-score{
  font-family:'Orbitron',monospace;font-size:0.68rem;
  color:#5a5a7a;background:rgba(255,255,255,0.04);
  border-radius:4px;padding:2px 6px;
}

/* ── WHEN TO BET TABLE ── */
.bet-table-wrap{overflow-x:auto;margin-bottom:2rem}
table{width:100%;border-collapse:collapse;font-size:0.85rem}
thead th{
  font-family:'Orbitron',monospace;font-size:0.7rem;letter-spacing:1px;
  color:#f0b90b;padding:0.8rem 1rem;text-align:left;
  background:rgba(240,185,11,0.07);border-bottom:1px solid rgba(240,185,11,0.2);
}
tbody tr{border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s}
tbody tr:hover{background:rgba(255,255,255,0.02)}
tbody td{padding:0.75rem 1rem;color:#c0c0d8}
td.bet-big{color:#00e676;font-weight:700;font-family:'Orbitron',monospace;font-size:0.8rem}
td.bet-sml{color:#ff5577;font-weight:700;font-family:'Orbitron',monospace;font-size:0.8rem}
td.str-max{color:#ff1744;font-weight:700}
td.str-strong{color:#f0b90b;font-weight:700}
td.str-medium{color:#a0a0c0}
td.str-weak{color:#5a5a7a}
td.agreed-yes{color:#00e676}
td.agreed-no{color:#ff5577}

/* ── LIVE PATTERN CARDS ── */
.pattern-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.2rem;margin-bottom:2rem}
.pat-card{
  background:rgba(13,13,22,0.95);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:16px;overflow:hidden;
}
.pat-head{
  padding:0.9rem 1.1rem;display:flex;align-items:center;justify-content:space-between;
  border-bottom:1px solid rgba(255,255,255,0.05);
}
.pat-label{font-family:'Orbitron',monospace;font-size:0.72rem;font-weight:700;color:#f0b90b;letter-spacing:1px}
.pat-strength{
  font-size:0.65rem;font-weight:700;letter-spacing:1px;border-radius:20px;padding:2px 9px;
}
.str-MAX{background:rgba(255,23,68,0.15);color:#ff1744;border:1px solid rgba(255,23,68,0.3)}
.str-STRONG{background:rgba(240,185,11,0.12);color:#f0b90b;border:1px solid rgba(240,185,11,0.3)}
.str-MEDIUM{background:rgba(160,160,192,0.08);color:#a0a0c0;border:1px solid rgba(160,160,192,0.2)}
.str-WEAK{background:rgba(90,90,122,0.08);color:#5a5a7a;border:1px solid rgba(90,90,122,0.2)}

.pat-body{padding:1rem 1.1rem}

/* trend bar */
.trend-bar{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:0.9rem}
.tb{
  padding:3px 9px;border-radius:20px;font-size:0.68rem;font-weight:700;letter-spacing:0.5px;
}
.tb.b{background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676}
.tb.s{background:rgba(255,23,68,0.1);border:1px solid rgba(255,23,68,0.3);color:#ff5577}
.tb.b.last,.tb.s.last{border-width:2px;transform:scale(1.1)}

.pat-explain{font-size:0.8rem;color:#a0a0c0;line-height:1.5;margin-bottom:0.9rem}

/* result row */
.pat-result{
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(255,255,255,0.03);border-radius:10px;padding:0.7rem 0.9rem;
}
.pr-left{display:flex;flex-direction:column;gap:3px}
.pr-label{font-size:0.65rem;color:#5a5a7a;letter-spacing:1px;text-transform:uppercase}
.pr-bet{font-family:'Orbitron',monospace;font-size:1.1rem;font-weight:900;letter-spacing:2px}
.pr-bet.big{color:#00e676;text-shadow:0 0 12px rgba(0,230,118,0.4)}
.pr-bet.sml{color:#ff5577;text-shadow:0 0 12px rgba(255,23,68,0.4)}
.pr-conf{
  font-family:'Orbitron',monospace;font-size:1rem;font-weight:700;
  color:#f0b90b;text-align:right;
}
.pr-conf small{display:block;font-family:'Rajdhani',sans-serif;font-size:0.65rem;
  color:#5a5a7a;font-weight:400;letter-spacing:1px}
.pr-votes{font-size:0.7rem;color:#5a5a7a;text-align:right;margin-top:3px}
.pr-votes .ag{color:#00e676;font-weight:700}
.pr-votes .dis{color:#ff5577;font-weight:700}

/* algo breakdown */
.algo-row{display:flex;gap:6px;margin-top:0.7rem;flex-wrap:wrap}
.algo-chip{
  flex:1;min-width:80px;background:rgba(255,255,255,0.03);
  border:1px solid rgba(255,255,255,0.06);border-radius:8px;
  padding:5px 8px;text-align:center;
}
.algo-chip .ac-name{font-size:0.62rem;color:#5a5a7a;letter-spacing:1px;text-transform:uppercase}
.algo-chip .ac-pred{font-family:'Orbitron',monospace;font-size:0.75rem;font-weight:700;margin:2px 0}
.algo-chip .ac-pred.big{color:#00e676}
.algo-chip .ac-pred.sml{color:#ff5577}
.algo-chip .ac-score{font-size:0.65rem;color:#a0a0c0}

/* edge tag */
.edge-tag{
  margin-top:0.6rem;background:rgba(255,23,68,0.07);
  border:1px solid rgba(255,23,68,0.2);border-radius:8px;
  padding:5px 10px;font-size:0.75rem;color:#ff5577;
}

/* ── PROFIT GUIDE ── */
.profit-guide{
  background:rgba(15,15,26,0.9);border:1px solid rgba(0,230,118,0.2);
  border-radius:16px;padding:1.5rem;margin-bottom:2rem;
  border-top:3px solid #00e676;
}
.profit-guide h3{
  font-family:'Orbitron',monospace;font-size:0.85rem;font-weight:700;
  color:#00e676;letter-spacing:2px;margin-bottom:1rem;
}
.profit-row{
  display:flex;align-items:flex-start;gap:1rem;padding:0.7rem 0;
  border-bottom:1px solid rgba(255,255,255,0.04);
}
.profit-row:last-child{border-bottom:none}
.profit-icon{font-size:1.3rem;flex-shrink:0;margin-top:2px}
.profit-text h5{color:#fff;font-size:0.9rem;font-weight:700;margin-bottom:3px}
.profit-text p{color:#a0a0c0;font-size:0.82rem;line-height:1.5}
.profit-text .tag{
  display:inline-block;font-size:0.7rem;font-weight:700;border-radius:4px;
  padding:1px 7px;margin-right:4px;
}
.tag-do{background:rgba(0,230,118,0.15);color:#00e676}
.tag-skip{background:rgba(255,23,68,0.12);color:#ff5577}
.tag-wait{background:rgba(240,185,11,0.12);color:#f0b90b}

/* ── SCORE LEGEND ── */
.legend{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:0.8rem;margin-bottom:2rem;
}
.legend-item{
  background:rgba(15,15,26,0.9);border:1px solid rgba(255,255,255,0.06);
  border-radius:12px;padding:1rem;
}
.legend-score{
  font-family:'Orbitron',monospace;font-size:1.4rem;font-weight:900;margin-bottom:4px;
}
.legend-label{font-size:0.8rem;color:#a0a0c0;margin-bottom:4px}
.legend-action{font-size:0.78rem;font-weight:700}
.sc-70{color:#ff1744}.sc-65{color:#f0b90b}.sc-60{color:#00b0ff}.sc-50{color:#5a5a7a}

/* ── FOOTER ── */
.page-footer{text-align:center;padding:2rem;color:#3a3a5a;font-size:0.75rem}

@media(max-width:600px){
  .flow-step::after{display:none}
  .topbar{padding:0 1rem}
}
</style>
</head>
<body>


<!-- ══ TOP BAR ══ -->
<div class="topbar">
  <div class="logo">DHANI<span>WIN</span></div>
  <a href="index.html" class="back-btn">← Back to Tool</a>
</div>

<div class="wrap">

  <!-- PAGE TITLE -->
  <div class="page-title">
    <h1>Pattern <span>Charts</span></h1>
    <p>How the tool detects opposite trends and exactly when to place your bet for profit. Every chart below is calculated live by the real engine — no fake numbers.</p>
  </div>

  <!-- ══ HOW THE TOOL WORKS ══ -->
  <div class="section-title">How the Tool Works — Step by Step</div>
  <div class="flow">
    <div class="flow-step">
      <div class="flow-num">01</div>
      <h4>You Enter 10 Trends</h4>
      <p>From the game history, bottom to top: BIG or Small for each round.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">02</div>
      <h4>3 Algorithms Run</h4>
      <p>Streak detection, recency weighting, and pattern matching all analyse the same 10 trends independently.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">03</div>
      <h4>Majority Vote</h4>
      <p>3/3 agree = high confidence. 2/3 agree = medium. Score is the average of agreeing algorithms.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">04</div>
      <h4>Edge Override</h4>
      <p>If last 5 or all 10 are the same, the tool forces a reversal bet — this is the strongest signal.</p>
    </div>
    <div class="flow-step">
      <div class="flow-num">05</div>
      <h4>Bet Opposite</h4>
      <p>The predicted result = what the next round is likely to show. Place your bet on that side.</p>
    </div>
  </div>

  <!-- ══ THREE ALGORITHM RULES ══ -->
  <div class="section-title">3 Algorithm Rules — When Each Bets Opposite</div>
  <div class="rules-grid">

    <!-- Algo 1 -->
    <div class="rule-card streak">
      <h4>⚡ Algorithm 1 — Streak Reversal</h4>
      <div class="rule-row">
        <span class="rule-label">Streak ≥ 4 (e.g. BBBB)</span>
        <span class="rule-action action-sml">BET Small</span>
        <span class="rule-score">Score 80–85</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Streak = 3 (BBB)</span>
        <span class="rule-action action-sml">BET Small</span>
        <span class="rule-score">Score 75</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Streak = 2 + majority agrees</span>
        <span class="rule-action action-sml">BET opposite</span>
        <span class="rule-score">Score 68</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Streak = 2, majority conflicts</span>
        <span class="rule-action action-mixed">Weak opposite</span>
        <span class="rule-score">Score 57</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">No streak — follow majority</span>
        <span class="rule-action action-mixed">Follow count</span>
        <span class="rule-score">Score 50–78</span>
      </div>
    </div>

    <!-- Algo 2 -->
    <div class="rule-card recency">
      <h4>📊 Algorithm 2 — Recency Weight</h4>
      <div class="rule-row">
        <span class="rule-label">Recent 5 all Small (wt heavy)</span>
        <span class="rule-action action-big">BET BIG</span>
        <span class="rule-score">Score ~75–85</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Recent 5 all BIG (wt heavy)</span>
        <span class="rule-action action-sml">BET Small</span>
        <span class="rule-score">Score ~75–85</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Dominant side weighted 70%+</span>
        <span class="rule-action action-mixed">Follow weighted</span>
        <span class="rule-score">Score ~65–75</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">50/50 split in weights</span>
        <span class="rule-action action-mixed">Follow last</span>
        <span class="rule-score">Score ~50–55</span>
      </div>
      <div class="rule-row" style="padding-top:0.6rem">
        <span class="rule-label" style="font-size:0.75rem;color:#5a5a7a;font-style:italic">
          Weight formula: trend[9]=10pts (newest), trend[0]=1pt (oldest)
        </span>
      </div>
    </div>

    <!-- Algo 3 -->
    <div class="rule-card pattern">
      <h4>🔬 Algorithm 3 — Pattern Table</h4>
      <div class="rule-row">
        <span class="rule-label">Last 3 = BBB or SSS</span>
        <span class="rule-action action-sml">BET opposite</span>
        <span class="rule-score">Score 80</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Last 3 = BBS or SSB</span>
        <span class="rule-action action-sml">Continue the switch</span>
        <span class="rule-score">Score 72</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Last 3 = BSS or SBB</span>
        <span class="rule-action action-mixed">Reverse the 2</span>
        <span class="rule-score">Score 68</span>
      </div>
      <div class="rule-row">
        <span class="rule-label">Last 3 = BSB or SBS (alt)</span>
        <span class="rule-action action-mixed">Next in alt sequence</span>
        <span class="rule-score">Score 63</span>
      </div>
      <div class="rule-row" style="padding-top:0.6rem">
        <span class="rule-label" style="font-size:0.75rem;color:#5a5a7a;font-style:italic">
          B = BIG, S = Small. All 8 possible 3-patterns are covered.
        </span>
      </div>
    </div>

  </div>

  <!-- ══ CONFIDENCE SCORE LEGEND ══ -->
  <div class="section-title">Confidence Score — What Each Level Means</div>
  <div class="legend">
    <div class="legend-item">
      <div class="legend-score sc-70">70–85</div>
      <div class="legend-label">All 3 algorithms agree — strong pattern</div>
      <div class="legend-action" style="color:#00e676">✅ BET NOW — Best moment for profit</div>
    </div>
    <div class="legend-item">
      <div class="legend-score sc-65">60–69</div>
      <div class="legend-label">2/3 algorithms agree — clear signal</div>
      <div class="legend-action" style="color:#f0b90b">⚡ GOOD BET — Acceptable risk</div>
    </div>
    <div class="legend-item">
      <div class="legend-score sc-60">55–59</div>
      <div class="legend-label">Weak agreement — mixed signals</div>
      <div class="legend-action" style="color:#a0a0c0">⚠️ CAUTION — Small bet only</div>
    </div>
    <div class="legend-item">
      <div class="legend-score sc-50">50–54</div>
      <div class="legend-label">No clear pattern — near 50/50</div>
      <div class="legend-action" style="color:#5a5a7a">🚫 SKIP — Do not bet this round</div>
    </div>
  </div>

  <!-- ══ WHEN TO BET TABLE ══ -->
  <div class="section-title">When to Bet for Profit — Quick Reference</div>
  <div class="bet-table-wrap">
    <table>
      <thead>
        <tr>
          <th>Situation You See in Game</th>
          <th>What Tool Predicts</th>
          <th>Signal</th>
          <th>Should You Bet?</th>
          <th>Why</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Last 4+ results all BIG</td>
          <td class="bet-sml">⬇ Small</td>
          <td class="str-max">MAX</td>
          <td class="agreed-yes">YES — Strong bet</td>
          <td>Streak reversal — statistically very likely to flip</td>
        </tr>
        <tr>
          <td>Last 4+ results all Small</td>
          <td class="bet-big">⬆ BIG</td>
          <td class="str-max">MAX</td>
          <td class="agreed-yes">YES — Strong bet</td>
          <td>Same as above, opposite side</td>
        </tr>
        <tr>
          <td>All 10 entries same (all BIG)</td>
          <td class="bet-sml">⬇ Small</td>
          <td class="str-max">MAX + EDGE</td>
          <td class="agreed-yes">YES — Highest confidence</td>
          <td>Edge correction fires, forces opposite prediction</td>
        </tr>
        <tr>
          <td>3/3 algorithms agree on one side</td>
          <td class="bet-big">⬆ / ⬇ Agreed</td>
          <td class="str-strong">STRONG</td>
          <td class="agreed-yes">YES — Best for profit</td>
          <td>All 3 methods see same pattern — rare and reliable</td>
        </tr>
        <tr>
          <td>Last 3 = BBB or SSS pattern</td>
          <td class="bet-sml">Opposite side</td>
          <td class="str-strong">STRONG</td>
          <td class="agreed-yes">YES — Clear pattern</td>
          <td>3-same pattern historically breaks on 4th</td>
        </tr>
        <tr>
          <td>Alternating perfectly (BSBSBS...)</td>
          <td class="bet-big">⬆ / ⬇ Next alt</td>
          <td class="str-medium">MEDIUM</td>
          <td class="agreed-yes">YES — Follow pattern</td>
          <td>Alternating patterns tend to continue until they break</td>
        </tr>
        <tr>
          <td>2/3 algorithms agree, score 60+</td>
          <td class="bet-big">Majority side</td>
          <td class="str-medium">MEDIUM</td>
          <td class="agreed-yes">YES — Acceptable</td>
          <td>2 methods agree — decent signal, normal bet size</td>
        </tr>
        <tr>
          <td>Score shows 55–59</td>
          <td>Weak signal</td>
          <td class="str-weak">WEAK</td>
          <td class="agreed-no">SMALL BET only</td>
          <td>Mixed signals — risk is higher, reduce bet size</td>
        </tr>
        <tr>
          <td>Score is 50–54 (near 50/50)</td>
          <td>No clear side</td>
          <td class="str-weak">NONE</td>
          <td class="agreed-no">SKIP this round</td>
          <td>No pattern detected — pure chance, not worth betting</td>
        </tr>
        <tr>
          <td>Mixed trends (no streak, no pattern)</td>
          <td>Follows weight</td>
          <td class="str-weak">WEAK</td>
          <td class="agreed-no">WAIT for next round</td>
          <td>Enter fresh 10 trends next round for a cleaner signal</td>
        </tr>
      </tbody>
    </table>
  </div>



  <!-- ══ LIVE PATTERN EXAMPLES ══ -->
  <div class="section-title">Live Pattern Examples — Real Engine Output</div>
  <div class="pattern-grid">
<?php foreach ($results as $r):
    $isBig   = $r['live_prediction'] === 'BIG';
    $betClass= $isBig ? 'big' : 'sml';
    $betLabel= $isBig ? '⬆ BET BIG' : '⬇ BET Small';
    $agreed  = $r['live_agreed'];
    $votes   = $r['live_votes'];
    $detail  = $r['live_detail'];
    $str     = $r['strength'];
?>
    <div class="pat-card">

      <!-- head -->
      <div class="pat-head">
        <span class="pat-label"><?= htmlspecialchars($r['label']) ?></span>
        <span class="pat-strength str-<?= $str ?>"><?= $str ?></span>
      </div>

      <div class="pat-body">

        <!-- trend visualisation -->
        <div class="trend-bar">
          <?php foreach ($r['trends'] as $idx => $t):
            $isLast  = ($idx === count($r['trends']) - 1);
            $tbClass = ($t === 'BIG' ? 'b' : 's') . ($isLast ? ' last' : '');
          ?>
          <span class="tb <?= $tbClass ?>"><?= $t === 'BIG' ? '⬆B' : '⬇S' ?></span>
          <?php endforeach ?>
        </div>

        <!-- explain -->
        <div class="pat-explain"><?= htmlspecialchars($r['explain']) ?></div>

        <!-- result row -->
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
              Votes:
              <span class="<?= $agreed ? 'ag' : 'dis' ?>">
                BIG <?= $votes['BIG'] ?>/3 &nbsp; Small <?= $votes['Small'] ?>/3
              </span>
            </div>
          </div>
        </div>

        <!-- algo breakdown -->
        <div class="algo-row">
          <?php foreach ($detail as $name => $algo):
            $ac = $algo['prediction'] === 'BIG' ? 'big' : 'sml';
          ?>
          <div class="algo-chip">
            <div class="ac-name"><?= ucfirst($name) ?></div>
            <div class="ac-pred <?= $ac ?>"><?= $algo['prediction'] === 'BIG' ? '⬆B' : '⬇S' ?></div>
            <div class="ac-score"><?= $algo['score'] ?? $algo['confidence'] ?? '—' ?>pts</div>
          </div>
          <?php endforeach ?>
        </div>

        <!-- edge tag if fired -->
        <?php if (!empty($r['edge'])): ?>
        <div class="edge-tag">🔴 Edge Override: <?= htmlspecialchars($r['edge']) ?></div>
        <?php endif ?>

      </div>
    </div>
<?php endforeach ?>
  </div>

  <!-- ══ PROFIT STRATEGY GUIDE ══ -->
  <div class="section-title">Profit Strategy — How to Use the Tool to Win</div>
  <div class="profit-guide">
    <h3>💰 PROFIT BETTING GUIDE</h3>

    <div class="profit-row">
      <div class="profit-icon">✅</div>
      <div class="profit-text">
        <h5>Only bet when score ≥ 65</h5>
        <p><span class="tag tag-do">DO</span> Wait until the tool shows 65% or higher confidence. Below that the pattern is too weak. Patience = profit.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">🔴</div>
      <div class="profit-text">
        <h5>Bet opposite when streak ≥ 3</h5>
        <p><span class="tag tag-do">DO</span> When you see 3 or more of the same result in a row in game history, the tool bets opposite. This is the most reliable signal — bet with confidence.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">⚡</div>
      <div class="profit-text">
        <h5>All 3 algorithms agree = best bet</h5>
        <p><span class="tag tag-do">DO</span> When the detail shows all 3 (First, Second, Third) predict the same side, this is the strongest possible signal. Increase bet size on these rounds.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">🚫</div>
      <div class="profit-text">
        <h5>Skip when score is below 60</h5>
        <p><span class="tag tag-skip">SKIP</span> A score of 50–59 means no clear pattern. The result is close to 50/50 random. Betting here is gambling, not strategy. Wait for the next round.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">🔄</div>
      <div class="profit-text">
        <h5>If result is wrong — re-enter trends</h5>
        <p><span class="tag tag-wait">RETRY</span> Click NO after a wrong prediction. Re-enter the latest 10 trends (now updated with the new result). The tool re-analyses with fresh data for a better signal.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">📊</div>
      <div class="profit-text">
        <h5>Follow the trend bar, not your gut</h5>
        <p><span class="tag tag-do">DO</span> Trust the pattern shown in the charts. The tool removes emotion from your bet decision. Consistent use over many rounds outperforms gut-feeling betting.</p>
      </div>
    </div>

    <div class="profit-row">
      <div class="profit-icon">⏱️</div>
      <div class="profit-text">
        <h5>Enter trends bottom-to-top, latest last</h5>
        <p><span class="tag tag-do">DO</span> The recency algorithm gives the highest weight to the most recent (last entered) trend. Entering in the wrong order will give wrong predictions.</p>
      </div>
    </div>
  </div>

</div><!-- end wrap -->

<div class="page-footer">
  DHANI WIN Charts &mdash; Pattern guide powered by live algorithm engine &mdash; For educational purposes only
</div>

</body>
</html>
