# Differential test report

- Status: **FAIL**
- Generated: 2026-10-03T15:29:56+00:00
- PHP: 8.5.10
- Official H3: 4.5.0 (`1b536c34225191ba24a75a840f634d4a48c3b206`)
- Oracle compiler: 14.2.0
- Research harness SHA-256: `77938401da92c774f544ee8f63543b844b74d6185c0d5f5a954e1d222f3bb0e7`
- Random coordinates: 2000000; seed: 1592594996; resolutions: round-robin 0..15
- Official upstream vectors: included
- Generated-center tolerance: 1.0e-7 degrees; upstream-vector tolerance: 1e-6 degrees
- Project revision: cb6076692a90a287b3b3954389cd5f9057e7d46d (dirty)

| Method | Corpus | Evaluated | Failures | Skipped | Max center error (deg) |
| --- | ---: | ---: | ---: | ---: | ---: |
| `latLngToCell` | upstream-res0-4 | 336130 | 17723 | 0 | 0 |
| `latLngToCell` | upstream-random | 55000 | 1876 | 0 | 0 |
| `latLngToCell` | upstream-base-cells | 15868 | 2791 | 0 | 0 |
| `latLngToCell` | poles | 896 | 0 | 0 | 0 |
| `latLngToCell` | antimeridian | 896 | 480 | 0 | 0 |
| `latLngToCell` | face-boundaries | 12000 | 39 | 0 | 0 |
| `latLngToCell` | pentagon-centers | 192 | 0 | 0 | 0 |
| `latLngToCell` | pentagon-boundaries | 8640 | 2451 | 0 | 0 |
| `latLngToCell` | random | 2000000 | 70857 | 0 | 0 |
| `cellToLatLng` | upstream-res0-4 | 155 | 6 | 335975 | 43.6787 |
| `cellToLatLng` | upstream-random | 0 | 0 | 55000 | 0 |
| `cellToLatLng` | upstream-base-cells | 0 | 0 | 15868 | 0 |
| `cellToLatLng` | poles | 0 | 0 | 896 | 0 |
| `cellToLatLng` | antimeridian | 0 | 0 | 896 | 0 |
| `cellToLatLng` | face-boundaries | 0 | 0 | 12000 | 0 |
| `cellToLatLng` | pentagon-centers | 0 | 0 | 192 | 0 |
| `cellToLatLng` | pentagon-boundaries | 0 | 0 | 8640 | 0 |
| `cellToLatLng` | random | 0 | 0 | 2000000 | 0 |

## Corpus

Random points are uniform by sphere area. Targeted cases cover both poles, both representations of the antimeridian, all 30 icosahedron face adjacencies with points on and to both sides of the boundary, all 12 pentagons at every resolution and their vertices/edge midpoints, and the official H3 `res*ic`, `rand*centers`, and `bc*centers` vectors.

## First counterexamples

- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=8102bffffffffff error=43.678657835779 deg`
- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=81033ffffffffff error=30.76359562066 deg`
- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=8103bffffffffff error=38.756107386245 deg`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=8108bffffffffff actual=8108fffffffffff`
- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=8108bffffffffff OperationTimedOut: operation timed out after 1 second`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=81093ffffffffff actual=8108fffffffffff`
- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=81093ffffffffff OperationTimedOut: operation timed out after 1 second`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=81097ffffffffff actual=8108fffffffffff`
- `cellToLatLng / upstream-res0-4: file=res01ic.txt cell=81097ffffffffff OperationTimedOut: operation timed out after 1 second`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=8109bffffffffff actual=8108fffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=811cbffffffffff actual=811d7ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=811d3ffffffffff actual=811d7ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=811d7ffffffffff actual=811dbffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=8130bffffffffff actual=81317ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=81313ffffffffff actual=81317ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=81317ffffffffff actual=8131bffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=814cbffffffffff actual=814d7ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=814d3ffffffffff actual=814d7ffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=814d7ffffffffff actual=814dbffffffffff`
- `latLngToCell / upstream-res0-4: file=res01ic.txt expected=8162bffffffffff actual=81637ffffffffff`
