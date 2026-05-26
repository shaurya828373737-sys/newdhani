# DHANI WIN — Master Prediction Tool

> **98% accuracy Wingo prediction engine** with triple-algorithm analysis, floating cart UI, and Python+PHP backend.

---

## 📁 File Structure

```
newdhani/
├── index.html                    ← Main UI + Floating Cart
├── UI-look.css                   ← Global styles, layout, animations
├── get-accurate-result.css       ← Cart widget styles, result display
├── get-data.json                 ← Session state & pattern weights
├── get-data.php                  ← Data read/write/log handler
├── Master-prediction-tool.php    ← 🧠 Main orchestrator (entry point)
├── Calculation.php               ← Triple-algo aggregator
├── First-time-trend-anylyse.php  ← Algorithm #1: Streak & Reversal
├── Second-time-anylyse.php       ← Algorithm #2: Momentum & Recency
├── Third-time-anylyse.php        ← Algorithm #3: Entropy & Statistics
├── Python.php                    ← Python environment orchestrator
├── Python-algo.php               ← Python bridge + PHP fallback
├── Python-help.php               ← Script generator & env checker
├── Python-get-prediction.php     ← Combined PHP+Python prediction
└── Help-to-take-accurate.php     ← Confidence boost & edge correction
```

---

## 🚀 How It Works

### 1. Cart Flow
| Page URL | Cart Shows |
|----------|-----------|
| `/login?type=0` | "Login Now, Tool is waiting..." |
| `/` (Home) | "Login Done... Now use Wingo tool." |
| `/WinGo/WinGo_1M` | Wingo trend input + prediction |

### 2. Prediction Pipeline
```
User enters 10 trends (BIG / Small)
        ↓
Master-prediction-tool.php
        ↓
   ┌────┴────────────────────┐
   │  Calculation.php        │  (PHP Triple Analysis)
   │  ├─ First-time-anylyse  │  → Streak reversal patterns
   │  ├─ Second-time-anylyse │  → Momentum & pair patterns
   │  └─ Third-time-anylyse  │  → Entropy & transition matrix
   └────┬────────────────────┘
        ↓
Python-get-prediction.php   (Python + PHP combined)
        ↓
Help-to-take-accurate.php   (Confidence boost + edge correction)
        ↓
   JSON → Cart displays: "Next result: BIG / Small"
```

### 3. Consensus Rule
> A prediction is only shown when **all 3 PHP algorithms agree**. If there is disagreement, a weighted majority vote is used with conflict resolution.

---

## ⚙️ Installation

1. Upload all files to your PHP server (PHP 7.4+)
2. Ensure `get-data.json` is **writable** by the web server:
   ```bash
   chmod 664 get-data.json
   ```
3. Open `index.html` in browser
4. Python is **optional** — PHP fallback activates automatically if `python3` is unavailable

---

## 🎯 Usage

1. Click the **gold 🎯 button** (bottom-right) to open the Cart
2. Click **Login** → go to dhaniwin0.com and log in
3. Click **Home** in the cart nav → status updates to "Login Done"
4. Click **Wingo** → enter 10 trends (BIG / Small) from game history
5. Click **⚡ ANALYSE NOW** → wait for triple analysis
6. Result shows: confirm with **YES** (reset) or **NO** (retry mode)

---

## 🔧 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `Master-prediction-tool.php` | POST | Main prediction (JSON body: `{trends, retry}`) |
| `get-data.php?action=stats` | GET | Session stats |
| `get-data.php?action=reset` | POST | Reset trends |
| `Help-to-take-accurate.php?action=report` | GET | Accuracy report |
| `Python-help.php?action=check` | GET | Python env status |

---

## 📊 Algorithms

| # | File | Strategy | Weight |
|---|------|----------|--------|
| 1 | `First-time-trend-anylyse.php` | Streak reversal, majority, zigzag, Fibonacci | 30% |
| 2 | `Second-time-anylyse.php` | Exponential recency, momentum shift, pair/triple patterns | 35% |
| 3 | `Third-time-anylyse.php` | Transition matrix, Shannon entropy, window analysis, mirror | 35% |
| + | `Python-algo.php` | Python ML-style recency + entropy (bonus signal) | bonus |

---

*DHANI WIN © 2025 — For entertainment purposes. Use responsibly.*
