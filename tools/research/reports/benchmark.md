# Benchmark report

- Generated: 2026-10-03T15:36:10+00:00
- PHP: 8.5.10; JIT: disable
- Official H3: 4.5.0 (`1b536c34225191ba24a75a840f634d4a48c3b206`)
- Oracle compiler: 14.2.0
- Research harness SHA-256: `77938401da92c774f544ee8f63543b844b74d6185c0d5f5a954e1d222f3bb0e7`
- Host: Linux ligorikus-pc 6.12.108-1-MANJARO #1 SMP PREEMPT_DYNAMIC Sat, 05 Sep 2026 01:29:32 +0000 x86_64
- Dataset: 100000 sphere-uniform points, seed 1592594996, resolutions 0..15
- Protocol: 2 warm-up passes, 9 measured passes; table uses the median
- Project revision: cb6076692a90a287b3b3954389cd5f9057e7d46d (dirty)

| Method | Implementation | ns/op | ops/s | PHP/C ratio |
| --- | --- | ---: | ---: | ---: |
| `latLngToCell` | Official C, radians precomputed | 358.4 | 2789797 | baseline only |
| `latLngToCell` | Official C, degrees conversion | 353.6 | 2828358 | 1.00x |
| `latLngToCell` | PHP, prebuilt LatLng | 27764.4 | 36017 | 78.53x |
| `latLngToCell` | PHP, including LatLng allocation | 27627.3 | 36196 | 78.14x |
| `cellToLatLng` | Official C | 184.5 | 5418692 | 1.00x |
| `cellToLatLng` | PHP | unavailable | unavailable | timed out |

The fair `latLngToCell` comparison is PHP versus C “degrees”: both convert degree input on every call. C “radians” is included as a lower-level reference because the PHP public API has no radians entry point. Container startup, IPC, corpus generation, and report rendering are outside timed regions. Results are machine-specific and should only be compared when the environment metadata matches.
