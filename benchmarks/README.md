# Validation rule benchmarks

These PHPBench benchmarks compare the original Laravel parser with the bounded parsed-string-rule cache. All inputs and rules are synthetic. They require PHP 8.3+ and PHPBench 1.7.0 or later, and run with JIT disabled.

Install the framework's development dependencies normally, then install the isolated benchmark tool dependencies:

```sh
composer install --working-dir=benchmarks
```

Alternatively, use the official PHPBench 1.7.0 PHAR. The benchmark tool is isolated from the framework's Composer manifest. `FRAMEWORK_BENCH_AUTOLOAD` can point to another compatible installed dependency autoloader; framework classes are loaded from this checkout.

## Compare baseline and candidate

On a Unix-like system, use the original `13.x` parser as the baseline from the benchmark branch:

```sh
php benchmarks/compare.php 13.x benchmarks/vendor/bin/phpbench 3
```

The runner exports only `ValidationRuleParser.php` from that Git reference and overrides that class's autoload path for baseline samples. Both variants use identical benchmark code, dependencies, and all other framework classes. The bootstrap checks the parser source path and the presence/absence of its cache, then verifies that each process starts with an empty cache. Post-sample checks verify the cache bounds and the expected cacheability of each parser workload. A baseline PHP file may be supplied instead of a Git reference for environments without Git. With a PHAR, pass its path instead of `benchmarks/vendor/bin/phpbench`.

Before measuring, the runner compares 360,480 parser and full-validation outputs against the baseline. It stops on any mismatch or unexpected validation outcome. It then runs three alternating baseline/candidate rounds and produces raw XML, logs, correctness digests, and a timing-only summary under the ignored `benchmarks/results/` directory. Raw files can include local paths and environment details; review them before sharing.

The summary verifies every variant's revolutions, iterations, and warmup settings. It reports the median sample time in each round, the median of the within-round percentage reductions, and the range across paired rounds. Positive percentages mean less time; negative percentages mean a regression. No retry threshold discards inconvenient samples. Do not compare only each run's fastest sample.

For a shorter smoke run of all fixtures:

```sh
XDEBUG_MODE=off php -d opcache.jit=disable -d opcache.jit_buffer_size=0 \
  benchmarks/vendor/bin/phpbench run --iterations=1 --revs=2 --report=aggregate
```

This overrides process lifetimes and is not an eligible persistent/non-persistent comparison. Use `compare.php` for final evidence.

## Worker lifetimes

PHPBench's remote executor starts a new PHP process for each iteration.

| Setup | Parser request | Full validator request | Independent iterations per round |
|---|---|---|---:|
| Persistent | 2,000 requests in one process after 50 warmup requests | 200 requests in one process after 20 warmup requests | 10 |
| Non-persistent | One request in a fresh process, no warmup | One request in a fresh process, no warmup | 30 |

One parser request performs 32 parses, cycling through the selected rule pool. One full-validator request constructs the rules/data and a new validator, checks the validation result, and materializes errors for invalid input. Persistent samples retain only the normal framework static caches across requests; validator instances are recreated. Dynamic rule cases advance their constraints within each worker process.

PHPBench excludes PHP process launch, benchmark bootstrap, parameter-provider work, and before-method fixture setup from its timer. The parser class is loaded before timing so its source can be verified. First-use loading of other framework classes during the request remains included. CLI OPcache is enabled, but each fresh CLI process starts with its own opcode cache; this does not reproduce PHP-FPM's shared opcode cache across requests. These are isolated request-processing measurements, not HTTP-server, application-boot, FPM, or Octane throughput measurements. Small cold-process differences may be overwhelmed by class-loading cost and host noise.

Do not override revolutions or add warmup to the non-persistent setup: either would let the cache survive across multiple requests and invalidate its intended lifetime.

## Rule coverage

The parser suite covers 18 workloads:

- Simple names; aliases, empty and numeric names; parameterized constraints.
- CSV quotes, commas and escaping; regex parameters containing pipes, commas and colons.
- Unicode, invalid UTF-8 bytes and control characters.
- String lengths immediately below, at and above the 256-byte cache limit.
- Array rules, mutable object parameters, rule-contract objects and Stringable rules.
- Pools of 128, 256, 257 and 4,096 distinct rules; 75% repeated simple rules mixed with 25% changing constraints.

The full-validator suite covers 15 workloads: valid and invalid scalar data, nullable/optional fields, numeric/decimal/digit rules, CSV, regex, Unicode, dates, dependent/conditional rules, confirmation, rule objects, 25-item wildcard arrays, invalid 100-item wildcard arrays, changing `Rule::in` sets, and 16 changing numeric constraints per request. No database, DNS or external HTTP calls are made.

The separate `ValidationRuleParserCacheTest` exercises explicit expected outputs across repeated calls and eviction, returned-array mutation, numeric-string keys, typed array parameters, locale changes and non-cacheable input retention. Run it with the framework's PHPUnit setup. It belongs to this benchmark branch; the optimization PR remains unchanged.

## Interpreting results

Repeated parse hits should help most. Unique constraints incur cache-check/insertion work, and pools just larger than the capacity repeatedly evict the cache. Full-validator results include all validation work, so a large parser-only speedup need not produce a similarly large request improvement. Optional/null inputs may skip much of that work. A fresh-process result cannot support a claim about warm persistent workers, or vice versa.

Run on an otherwise idle machine, pin both variants to the same CPU if possible, retain all rounds, and inspect the unchanged object-rule control and paired ranges for noise. The default comparison uses three rounds; increase the final argument to gather more. The config disables JIT and Xdebug activity in each measured process.
