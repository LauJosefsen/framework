# PHPBench validation results

PHPBench 1.7.0, PHP 8.5.11, x86-64, one pinned logical CPU, JIT and Xdebug activity disabled. Three alternating baseline/candidate rounds; 7,920 timed samples across 66 variants. All 360,480 correctness comparisons matched. The complete validation suite passed 1,305 tests and 6,304 assertions, including 45 new cache-focused cases. Inputs are synthetic and no network or database calls are made.

Parser requests perform 32 parses. Full-validator requests construct current data/rules and a new validator, check the expected outcome and consume errors for invalid input. Times are median per-request wall times, not CPU time or HTTP response times. Positive reductions mean faster; negative values are regressions. Paired percentages are calculated within each round, so they can differ from ratios of the displayed medians.

Persistent samples run 2,000 parser requests or 200 validator requests per process, after 50/20 warmup requests, with 10 independent iterations per round. Non-persistent samples run one request per fresh process with no warmup and 30 iterations per round. Process launch/bootstrap and before-method fixture preparation are excluded. The parser class is loaded before timing to verify its source; other first-use class loading remains included. Each fresh CLI process also starts with a fresh opcode cache, so these results do not estimate warmed PHP-FPM performance.

Cache guards confirm the selected parser source, an empty initial rule cache, expected cache use/fallback and the 256-entry bound. No retry threshold drops samples. Small fresh-process effects should be treated as noise unless reproduced in the intended server runtime.

## Parser workloads: persistent

| Case | Baseline → cached, µs/request | Paired time reduction | Range across rounds |
|---|---:|---:|---:|
| `aliases` | 7.04 → 3.89 | +44.8% | +44.4% to +44.9% |
| `array_rules` | 8.49 → 9.42 | -11.0% | -11.5% to -10.2% |
| `capacity_128` | 21.71 → 4.06 | +81.3% | +81.1% to +81.4% |
| `capacity_256` | 22.17 → 4.09 | +81.5% | +81.5% to +81.6% |
| `capacity_257` | 22.20 → 27.73 | -25.0% | -25.9% to -24.5% |
| `controls` | 23.97 → 27.57 | -14.7% | -16.1% to -14.6% |
| `csv` | 28.28 → 3.99 | +85.9% | +85.9% to +86.0% |
| `length_255` | 216.49 → 5.51 | +97.5% | +97.4% to +97.5% |
| `length_256` | 218.94 → 5.52 | +97.5% | +97.4% to +97.5% |
| `length_257` | 218.02 → 220.57 | -1.1% | -1.2% to -1.0% |
| `mixed` | 9.59 → 10.45 | -9.0% | -9.1% to -9.0% |
| `parameters` | 22.95 → 3.99 | +82.6% | +82.6% to +82.8% |
| `regex` | 9.19 → 4.00 | +56.4% | +56.3% to +56.7% |
| `rule_objects` | 1.50 → 1.54 | -2.8% | -3.7% to -2.1% |
| `simple` | 4.81 → 3.93 | +18.8% | +17.8% to +18.9% |
| `stringable` | 34.00 → 34.72 | -2.2% | -2.7% to -2.0% |
| `unicode` | 20.93 → 24.27 | -15.8% | -17.0% to -14.8% |
| `unique_4096` | 23.00 → 28.66 | -24.6% | -24.9% to -24.4% |

## Parser workloads: non-persistent

| Case | Baseline → cached, µs/request | Paired time reduction | Range across rounds |
|---|---:|---:|---:|
| `aliases` | 2134.50 → 2108.00 | +1.3% | +0.1% to +3.4% |
| `array_rules` | 3029.50 → 3031.50 | -0.1% | -1.9% to +1.8% |
| `capacity_128` | 2125.50 → 2146.50 | -1.3% | -1.4% to +1.2% |
| `capacity_256` | 2133.50 → 2140.50 | -0.5% | -1.1% to +0.9% |
| `capacity_257` | 2134.50 → 2134.50 | -0.1% | -1.2% to +0.4% |
| `controls` | 2122.00 → 2127.50 | +0.1% | -0.4% to +1.5% |
| `csv` | 2140.50 → 2117.50 | +1.1% | +1.0% to +2.4% |
| `length_255` | 2326.50 → 2112.50 | +9.2% | +8.7% to +9.6% |
| `length_256` | 2335.50 → 2101.00 | +9.3% | +8.8% to +10.4% |
| `length_257` | 2330.00 → 2324.00 | +0.4% | -1.8% to +0.6% |
| `mixed` | 2126.50 → 2114.50 | +0.9% | -0.0% to +1.0% |
| `parameters` | 2139.50 → 2126.00 | +1.3% | +0.6% to +2.9% |
| `regex` | 2113.00 → 2103.50 | +0.4% | +0.4% to +2.4% |
| `rule_objects` | 5.00 → 5.00 | +0.0% | +0.0% to +0.0% |
| `simple` | 2110.50 → 2106.00 | +0.4% | +0.2% to +1.9% |
| `stringable` | 2164.50 → 2161.00 | +0.3% | -1.6% to +1.6% |
| `unicode` | 2144.50 → 2131.00 | +1.3% | +0.3% to +1.4% |
| `unique_4096` | 2140.00 → 2126.50 | +0.6% | +0.0% to +1.6% |

## Full validation workloads: persistent

| Case | Baseline → cached, µs/request | Paired time reduction | Range across rounds |
|---|---:|---:|---:|
| `conditional` | 40.26 → 20.45 | +49.1% | +49.0% to +49.2% |
| `confirmed` | 27.81 → 18.56 | +33.6% | +33.1% to +33.9% |
| `csv` | 14.92 → 9.41 | +36.8% | +36.4% to +37.6% |
| `dates` | 90.78 → 62.86 | +30.8% | +30.6% to +31.0% |
| `dynamic_in` | 15.59 → 10.37 | +33.5% | +33.1% to +34.2% |
| `dynamic_minimums` | 286.49 → 197.06 | +31.2% | +31.1% to +32.1% |
| `invalid_scalars` | 131.17 → 90.23 | +31.2% | +30.9% to +31.5% |
| `invalid_wildcards_100` | 3810.02 → 2984.53 | +21.6% | +21.5% to +22.2% |
| `nullable` | 40.38 → 26.03 | +35.6% | +35.4% to +36.3% |
| `numeric` | 55.02 → 30.20 | +45.2% | +44.9% to +45.4% |
| `objects` | 23.12 → 17.27 | +25.3% | +24.5% to +26.1% |
| `regex` | 16.78 → 13.10 | +22.1% | +21.0% to +22.1% |
| `scalars` | 72.44 → 39.89 | +44.8% | +44.7% to +45.0% |
| `unicode` | 14.17 → 15.39 | -8.3% | -9.8% to -8.2% |
| `wildcards_25` | 947.11 → 742.97 | +21.8% | +21.2% to +23.5% |

## Full validation workloads: non-persistent

| Case | Baseline → cached, µs/request | Paired time reduction | Range across rounds |
|---|---:|---:|---:|
| `conditional` | 11849.00 → 11810.00 | +0.3% | -4.8% to +0.3% |
| `confirmed` | 11782.00 → 11817.50 | -0.4% | -1.3% to +0.6% |
| `csv` | 9758.50 → 9709.00 | +0.9% | -0.2% to +1.6% |
| `dates` | 26874.00 → 26872.50 | +0.0% | -5.3% to +0.6% |
| `dynamic_in` | 10008.50 → 10049.00 | -0.2% | -0.7% to +0.5% |
| `dynamic_minimums` | 12137.00 → 11997.00 | +1.1% | +1.0% to +1.6% |
| `invalid_scalars` | 12009.50 → 11983.00 | +0.3% | -0.4% to +0.6% |
| `invalid_wildcards_100` | 15797.00 → 15052.50 | +5.1% | +3.2% to +5.2% |
| `nullable` | 9742.00 → 9717.00 | +0.1% | +0.1% to +1.8% |
| `numeric` | 12731.50 → 12742.50 | -0.3% | -0.4% to +1.3% |
| `objects` | 10128.00 → 10080.50 | +0.5% | -0.5% to +0.7% |
| `regex` | 9786.00 → 9725.50 | +0.8% | -0.4% to +1.3% |
| `scalars` | 11871.00 → 11854.50 | +0.1% | +0.0% to +1.0% |
| `unicode` | 9718.00 → 9706.50 | +0.1% | -0.3% to +0.6% |
| `wildcards_25` | 12942.50 → 12802.50 | +1.4% | -1.9% to +1.5% |

## Reproduction and data

[Reproduction commands and lifetime definitions](README.md). [Timing samples and paired summaries](validation-results.json). Raw XML/logs remain ignored because they can contain local paths and environment details.

Baseline: `5fd21848a2ab1b53213cd17635b9feaaa13cbc6f`. Candidate: `429aaad6a68f73badc842595f3db148de6b301a7`. Benchmark source: `92170297cd3eef9aadce594df96327b788ab348f`.
