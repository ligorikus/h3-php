# Differential research

This directory contains manual, reproducible comparisons with the official
Uber H3 C library. It is intentionally separate from PHPUnit and CI.

The oracle image builds H3 v4.5.0 from the release archive, verifies the
archive SHA-256, and records the exact upstream commit in each report. The
runner keeps one oracle process alive, so process startup is not paid once per
case.

## Requirements

- the project's Composer dependencies;
- Docker with access to `github.com` when the oracle image is first built.

## Correctness

The full run uses two million deterministic sphere-uniform random coordinates,
all resolutions, poles, the antimeridian, every icosahedron face adjacency,
all pentagons and their boundaries, and official upstream vectors:

```bash
composer research:differential
```

For a quick smoke run:

```bash
composer research:differential -- --random=10000
```

To test the harness itself without walking the 407,000+ upstream vectors, add
`--skip-upstream`. Reports produced this way state that the corpus was skipped
and are not a substitute for the full research run.

The default report is `tools/research/reports/differential.md`. A non-zero exit
code means that mismatches were found (the report is still written). Use a
fixed custom seed or report path when needed:

```bash
composer research:differential -- --seed=42 --output=/tmp/differential.md
```

## Benchmark

Benchmarks are kept in a separate report:

```bash
composer research:benchmark
```

The default is 100,000 inputs, two warm-up passes, and the median of nine
measured passes. The timed regions exclude Docker startup, IPC, data generation,
and report rendering. The report is written to
`tools/research/reports/benchmark.md`.

Benchmark numbers are only comparable on the same machine and with matching
environment metadata. Rebuild the pinned oracle explicitly after changing its
source:

```bash
composer research:benchmark -- --rebuild
```

## Adding a public method

Extend all three layers together:

1. add a compact batch command to `oracle.c`;
2. add a generated and, where available, an upstream corpus in `research.php`;
3. add a separately timed benchmark with a checksum so results are consumed.

Keep correctness comparisons semantic: indexes and sets are exact; floating
coordinates use an explicit angular tolerance; unordered output is normalized
before comparison.
