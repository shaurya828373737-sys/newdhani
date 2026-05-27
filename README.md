# COIN LAB

> An honest probability laboratory. Single-file, zero backend, runs in any browser.

COIN LAB is the replacement for what this repository used to be: a "color
prediction" tool that claimed 98% accuracy on platforms like WinGo / dhaniwin.
That tool could not work, and any tool that claims to is selling a fantasy.
This repository now does the opposite — it **proves** to the user, live and with
real numbers, that no algorithm can beat a fair 50/50 process.

## What it does

You feed COIN LAB a sequence of `BIG` / `SMALL` symbols (or generate a random
one). Three real algorithms run on it in parallel and each predicts the next
symbol:

| # | Algorithm | What it actually does |
|---|---|---|
| 01 | **Bayesian frequency** | `P(BIG) = (count_BIG + 1) / (n + 2)` with Laplace smoothing. Picks the more frequent symbol. |
| 02 | **Markov chain (1‑state)** | Builds `P(next | last)` transition probabilities. Predicts the most likely follow‑up to your latest symbol. |
| 03 | **Anti‑streak** | Predicts the opposite of any 3+ same‑symbol streak. Pure gambler's‑fallacy bot — included on purpose, to demonstrate it fails. |

After the real next outcome happens you click `Actual: BIG` or `Actual: SMALL`.
COIN LAB:

1. Scores every algorithm against that outcome.
2. Updates each algorithm's running hit rate.
3. Plots all three curves on a live SVG convergence chart.
4. Appends the real outcome to the sequence.

Run it long enough on any fair‑random data and every line on the chart drifts
toward the dashed `50%` reference line. That's the law of large numbers — the
point of the whole site.

## Why this exists

So‑called color‑prediction apps (WinGo, 91 Club, Tiranga, dhaniwin and many
others) have caused real, large financial harm to real people. The marketing
trick is always the same: a slick UI plus invented "AI / triple‑algorithm /
Fibonacci / entropy" jargon plus a fake 90%+ accuracy badge. The math behind it
is always wrong, for two reasons:

- **If the platform is a fair RNG**, past trends do not predict future
  outcomes. Every round is independent.
- **If the platform is not a fair RNG** (most of these aren't), the operator
  controls the outcome and biases against the player whenever it matters. No
  external predictor can model an adversary's hidden policy.

Either way, the long‑run accuracy of any such "predictor" is at best 50%, and
in practice less. COIN LAB lets anyone confirm that on their own device, in
their own browser, with no one to trust but the math.

## Running it

There is nothing to install or build.

```bash
# any static server works, for example:
python3 -m http.server 8000
# then open http://localhost:8000
```

Or push the repo to GitHub Pages and it works as‑is.

All state (sequence + per‑algorithm hit rates + history) lives in
`localStorage` under the key `coinlab.v1`. Click **Reset all stats** to wipe.
Click **Export JSON** to download your data.

## File layout

```
index.html      Whole app: HTML + CSS + JS in one file.
README.md       This file.
```

The previous PHP / CSS files (the WinGo predictor) have been removed.

## Tech

- Plain HTML / CSS / vanilla JavaScript. No build step, no framework.
- Google Fonts: Orbitron, Rajdhani, JetBrains Mono.
- SVG chart is hand‑rolled — no Chart.js or D3 dependency.
- Mobile‑responsive at ≤880px and ≤980px breakpoints.

## A note for anyone landing here from a "win prediction" search

If you came here looking for a tool that tells you the next color or the next
big/small on dhaniwin / 91 club / a similar app: that tool does not exist
anywhere. Anyone selling it is selling either ad views, a referral funnel, or a
"premium plan" — never a working predictor.

If you or someone you know is being harmed by online betting, in India you can
contact **iCall** at **9152987821** for free, confidential support.

## License

MIT. Use it, fork it, ship it. Just don't bolt a fake accuracy claim back on.
